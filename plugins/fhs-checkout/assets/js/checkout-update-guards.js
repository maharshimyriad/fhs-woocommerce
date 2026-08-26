jQuery(function($){
	const debugEnabled = /(?:\?|&)fhs_uc_debug=1(?:&|$)/.test(window.location.search);
	let lastBillingEventAt = 0;
	let patchAttempts = 0;
	let patchTimer = null;
	const checkoutBootAt = Date.now();
	let initialUpdateConsumed = false;
	let userHasInteracted = false;
	let initialUpdateAjaxSeen = false;
	let initialTriggerAttempts = 0;
	let initialTriggerTimer = null;

	const isBillingElement = function(el){
		if(!el) return false;
		const id = String(el.id || '');
		const name = String(el.name || '');
		return id.indexOf('billing_') === 0 || name.indexOf('billing_') === 0;
	};

	const markTrustedInteraction = function(event){
		if (event && event.isTrusted) {
			userHasInteracted = true;
		}
	};

	document.addEventListener('input', markTrustedInteraction, true);
	document.addEventListener('change', markTrustedInteraction, true);
	document.addEventListener('keydown', markTrustedInteraction, true);

	$(document).ajaxSend(function(event, jqxhr, settings){
		const url = String((settings && settings.url) || '');
		if (url.indexOf('wc-ajax=update_order_review') !== -1) {
			initialUpdateAjaxSeen = true;
			if (debugEnabled) {
				console.log('[FHS-UC-DEBUG] detected update_order_review ajax', url);
			}
		}
	});

	const shouldBypassCheckoutGuards = function(){
		return Number(window.fhsBypassUpdateGuardsUntil || 0) > Date.now();
	};

	const shouldBlockExtraInitialUpdate = function(){
		if (shouldBypassCheckoutGuards()) {
			return false;
		}

		const forceUntil = Number(window.fhsForceInitialCheckoutUntil || 0);
		if (forceUntil > Date.now()) {
			return false;
		}

		const now = Date.now();
		const inInitialWindow = !userHasInteracted && (now - checkoutBootAt) < 5000;
		if (!inInitialWindow) {
			return false;
		}

		if (initialUpdateConsumed) {
			return true;
		}

		initialUpdateConsumed = true;
		return false;
	};

	const shouldBlockBillingCheckout = function(){
		if (shouldBypassCheckoutGuards()) {
			return false;
		}

		const forceUntil = Number(window.fhsForceInitialCheckoutUntil || 0);
		if (forceUntil > Date.now()) {
			return false;
		}

		const now = Date.now();
		const allowUntil = Number(window.fhsAllowBillingTriggeredCheckoutUntil || 0);
		if (allowUntil > now) {
			return false;
		}

		const active = document.activeElement;
		const billingActive = isBillingElement(active);
		const nearBillingEvent = (now - lastBillingEventAt) < 900;
		return billingActive || nearBillingEvent;
	};

	const triggerInitialCheckoutOnce = function(){
		if (!window.jQuery) {
			return;
		}
		if (window.fhsInitialCheckoutTriggered && initialTriggerAttempts > 0) {
			return;
		}

		window.fhsForceInitialCheckoutUntil = Date.now() + 2500;
		window.fhsAllowBillingTriggeredCheckoutUntil = Date.now() + 2000;
		window.fhsBypassUpdateGuardsUntil = Date.now() + 2500;
		initialTriggerAttempts++;

		if (debugEnabled) {
			console.log('[FHS-UC-DEBUG] forcing initial update_checkout attempt #' + initialTriggerAttempts);
		}

		if (window.wc_checkout_form && typeof window.wc_checkout_form.update_checkout === 'function') {
			window.wc_checkout_form.update_checkout();
		} else {
			window.jQuery(document.body).trigger('update_checkout');
		}

		window.fhsInitialCheckoutTriggered = true;
	};

	const startInitialCheckoutRetries = function(){
		if (initialTriggerTimer) {
			return;
		}

		initialTriggerTimer = window.setInterval(function(){
			if (initialUpdateAjaxSeen) {
				window.clearInterval(initialTriggerTimer);
				initialTriggerTimer = null;
				return;
			}

			if (initialTriggerAttempts >= 6) {
				window.clearInterval(initialTriggerTimer);
				initialTriggerTimer = null;
				if (debugEnabled) {
					console.log('[FHS-UC-DEBUG] no update_order_review ajax seen after retries');
				}
				return;
			}

			triggerInitialCheckoutOnce();
		}, 700);
	};

	if ($.fn && typeof $.fn.trigger === 'function' && !$.fn.trigger.__fhsBillingGuardPatched) {
		const originalTrigger = $.fn.trigger;
		const guardedTrigger = function(type){
			const eventType = typeof type === 'string' ? type : (type && type.type ? type.type : '');
			const isBodyTarget = this && this.length && this[0] === document.body;
			if (isBodyTarget && eventType === 'update_checkout' && shouldBlockBillingCheckout()) {
				if (debugEnabled) {
					console.log('[FHS-UC-DEBUG] blocked document.body trigger(update_checkout) from billing field');
				}
				return this;
			}
			return originalTrigger.apply(this, arguments);
		};
		guardedTrigger.__fhsBillingGuardPatched = true;
		$.fn.trigger = guardedTrigger;
	}

	$(document).on('input change keyup keydown', 'input[name^="billing_"], select[name^="billing_"], textarea[name^="billing_"]', function(){
		lastBillingEventAt = Date.now();
	});

	const patchCheckoutForm = function(){
		if(!window.wc_checkout_form){
			return false;
		}

		if (typeof window.wc_checkout_form.trigger_update_checkout === 'function') {
			const originalTriggerUpdate = window.wc_checkout_form.trigger_update_checkout;
			window.wc_checkout_form.trigger_update_checkout = function(event){
				const target = event && event.target ? event.target : null;
				if (isBillingElement(target)) {
					if (debugEnabled) {
						console.log('[FHS-UC-DEBUG] blocked trigger_update_checkout from billing field');
					}
					return;
				}
				return originalTriggerUpdate.apply(this, arguments);
			};
		}

		if(typeof window.wc_checkout_form.queue_update_checkout === 'function'){
			const originalQueue = window.wc_checkout_form.queue_update_checkout;
			window.wc_checkout_form.queue_update_checkout = function(){
				if (shouldBlockBillingCheckout()) {
					if (debugEnabled) {
						console.log('[FHS-UC-DEBUG] blocked queue_update_checkout from billing field');
					}
					return;
				}

				return originalQueue.apply(this, arguments);
			};
		}

		if(typeof window.wc_checkout_form.update_checkout === 'function'){
			const originalUpdateCheckout = window.wc_checkout_form.update_checkout;
			window.wc_checkout_form.update_checkout = function(){
				if (shouldBlockBillingCheckout()) {
					if (debugEnabled) {
						console.log('[FHS-UC-DEBUG] blocked update_checkout from billing field');
					}
					return;
				}
				if (shouldBlockExtraInitialUpdate()) {
					if (debugEnabled) {
						console.log('[FHS-UC-DEBUG] blocked extra initial update_checkout');
					}
					return;
				}

				return originalUpdateCheckout.apply(this, arguments);
			};
		}

		return true;
	};

	const runInitialAfterLoadWhenReady = function(){
		let readyChecks = 0;
		const readyTimer = window.setInterval(function(){
			readyChecks++;
			const hasCheckoutForm = $('form.checkout').length > 0;
			const hasOrderReview = $('#order_review').length > 0 || $('.woocommerce-checkout-review-order-table').length > 0;
			const hasCheckoutApi = !!(window.wc_checkout_form && typeof window.wc_checkout_form.update_checkout === 'function');
			const ready = hasCheckoutForm && hasOrderReview && hasCheckoutApi;

			if (!ready && readyChecks < 60) {
				return;
			}

			window.clearInterval(readyTimer);

			if (debugEnabled) {
				console.log('[FHS-UC-DEBUG] initial trigger readiness', {
					hasCheckoutForm: hasCheckoutForm,
					hasOrderReview: hasOrderReview,
					hasCheckoutApi: hasCheckoutApi,
					forcedAfterTimeout: !ready
				});
			}

			triggerInitialCheckoutOnce();
			window.setTimeout(startInitialCheckoutRetries, 250);

			if (!window.fhsInitialOrderReviewAjaxRequested) {
				window.fhsInitialOrderReviewAjaxRequested = true;
				window.setTimeout(function(){
					if (initialUpdateAjaxSeen) {
						return;
					}

					const $form = $('form.checkout');
					const postData = $form.length ? $form.serialize() : '';

					if (window.wc_checkout_form && typeof window.wc_checkout_form.update_checkout === 'function') {
						window.fhsForceInitialCheckoutUntil = Date.now() + 3000;
						window.fhsAllowBillingTriggeredCheckoutUntil = Date.now() + 3000;
						window.fhsBypassUpdateGuardsUntil = Date.now() + 3000;
						window.wc_checkout_form.update_checkout();
						return;
					}

					if (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.wc_ajax_url) {
						$.ajax({
							type: 'POST',
							url: String(wc_checkout_params.wc_ajax_url).replace('%%endpoint%%', 'update_order_review'),
							data: {
								security: wc_checkout_params.update_order_review_nonce || '',
								post_data: postData
							}
						});
						return;
					}

					$(document.body).trigger('update_checkout');
				}, 800);
			}
		}, 200);
	};

	if (!patchCheckoutForm()) {
		patchTimer = window.setInterval(function(){
			patchAttempts++;
			if (patchCheckoutForm() || patchAttempts > 40) {
				window.clearInterval(patchTimer);
				if (document.readyState === 'complete') {
					runInitialAfterLoadWhenReady();
				} else {
					window.addEventListener('load', runInitialAfterLoadWhenReady, { once: true });
				}
			}
		}, 100);
	} else if (document.readyState === 'complete') {
		runInitialAfterLoadWhenReady();
	} else {
		window.addEventListener('load', runInitialAfterLoadWhenReady, { once: true });
	}

	window.addEventListener('load', function(){
		window.setTimeout(function(){
			if (window.fhsLoadForcedCheckoutTriggered) {
				return;
			}

			window.fhsLoadForcedCheckoutTriggered = true;
			window.fhsForceInitialCheckoutUntil = Date.now() + 4000;
			window.fhsAllowBillingTriggeredCheckoutUntil = Date.now() + 4000;
			window.fhsBypassUpdateGuardsUntil = Date.now() + 4000;

			if (window.wc_checkout_form && typeof window.wc_checkout_form.update_checkout === 'function') {
				window.wc_checkout_form.update_checkout();
			} else {
				$(document.body).trigger('update_checkout');
			}
		}, 1200);
	}, { once: true });
});
