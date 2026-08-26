jQuery(function($){
	function disableBillingCheckoutTriggers(){
		const $billingRows = $('div[id^="billing_"][id$="_field"]');
		if(!$billingRows.length){
			return;
		}

		$billingRows.removeClass('update_totals_on_change address-field');
		$billingRows.find('input, select, textarea').removeClass('update_totals_on_change');
	}

	disableBillingCheckoutTriggers();
	$(document.body).on('updated_checkout', disableBillingCheckoutTriggers);
});
