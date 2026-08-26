/**
 * FHS Product Configurator — Client-side selection behaviour
 *
 * Temporary selection state (left side) and committed configuration state
 * (right side) are intentionally separate.
 *
 * @package FHS_WOO
 * @version 1.3.0
 */

( function () {
	'use strict';

	var SECTION_ORDER = [
		'machine_packages',
		'liner_sets',
		'replacement_parts',
		'accessories',
		'data_logging',
		'consumables',
		'tooling_extras',
	];

	function init() {
		var wrappers = document.querySelectorAll( '.fhs-configurator' );
		wrappers.forEach( function ( wrapper ) {
			initConfigurator( wrapper );
		} );
	}

	function initConfigurator( wrapper ) {
		var tabs = wrapper.querySelectorAll( '.fhs-configurator__tab' );
		var panels = wrapper.querySelectorAll( '.fhs-configurator__panel' );
		var summary = getSummaryElements( wrapper );

		wrapper.fhsCommittedConfiguration = createInitialCommittedConfiguration( wrapper );
		wrapper.fhsCartRequestInFlight = false;

		// Restore any previously saved configuration from localStorage.
		restoreConfiguration( wrapper );

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				switchTab( wrapper, tabs, panels, tab.getAttribute( 'data-section-key' ) );
			} );
		} );

		initTabDropdown( wrapper, tabs, panels );

		wrapper.addEventListener( 'change', function ( event ) {
			var input = event.target;
			if ( ! input.classList.contains( 'fhs-configurator__card-input' ) ) {
				return;
			}

			var sectionKey = input.getAttribute( 'data-section-key' );
			syncSectionSelectedState( wrapper, sectionKey );
			updateSelectAllButton( wrapper, sectionKey );
			dispatchTemporaryChangeEvent( wrapper );

			// Show "Updating…" immediately; commit fires after debounce.
			scheduleSectionCommit( wrapper, sectionKey );
		} );

		wrapper.querySelectorAll( '.fhs-configurator__select-all' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				handleSelectAll( wrapper, btn.getAttribute( 'data-section-key' ) );
			} );
		} );

		if ( summary.root ) {
			summary.root.addEventListener( 'click', function ( event ) {
				var removeBtn = event.target.closest( '[data-fhs-config-remove]' );
				if ( removeBtn ) {
					removeCommittedProduct(
						wrapper,
						removeBtn.getAttribute( 'data-section-key' ),
						parseInt( removeBtn.getAttribute( 'data-product-id' ), 10 )
					);
					return;
				}

				var clearBtn = event.target.closest( '[data-fhs-config-clear]' );
				if ( clearBtn ) {
					clearConfiguration( wrapper );
					return;
				}

				var addAllBtn = event.target.closest( '[data-fhs-config-add-all]' );
				if ( addAllBtn ) {
					handleAddAllToCart( wrapper, addAllBtn );
				}
			} );
		}

		syncAllSelectedStates( wrapper );
		updateAllSelectAllButtons( wrapper );
		renderCommittedConfigurationPanel( wrapper );
		dispatchCommittedChangeEvent( wrapper );

		// New features: qty steppers + view-more collapse.
		initQtySteppers( wrapper );
		initViewMore( wrapper );
	}

	function createInitialCommittedConfiguration( wrapper ) {
		var baseProduct = getBaseProduct( wrapper );
		return {
			baseProductId: baseProduct ? baseProduct.id : 0,
			// No machine is pre-committed — right panel starts empty.
			// activeMachineProductId is 0 until the user actively commits a choice.
			activeMachineProductId: 0,
			activeMachineSource: 'none',
			sections: createEmptySectionsState(),
		};
	}

	function createEmptySectionsState() {
		var sections = {};
		SECTION_ORDER.forEach( function ( sectionKey ) {
			sections[ sectionKey ] = [];
		} );
		return sections;
	}

	function initTabDropdown( wrapper, tabs, panels ) {
		var dropdown   = wrapper.querySelector( '.fhs-configurator__tab-dropdown' );
		if ( ! dropdown ) {
			return;
		}

		var trigger    = dropdown.querySelector( '.fhs-configurator__tab-dropdown-trigger' );
		var menu       = dropdown.querySelector( '.fhs-configurator__tab-dropdown-menu' );
		var labelEl    = dropdown.querySelector( '.fhs-configurator__tab-dropdown-label' );
		var iconEl     = dropdown.querySelector( '.fhs-configurator__tab-dropdown-icon i' );

		function openDropdown() {
			trigger.setAttribute( 'aria-expanded', 'true' );
			dropdown.classList.add( 'is-open' );
		}

		function closeDropdown() {
			trigger.setAttribute( 'aria-expanded', 'false' );
			dropdown.classList.remove( 'is-open' );
		}

		trigger.addEventListener( 'click', function () {
			var isOpen = dropdown.classList.contains( 'is-open' );
			isOpen ? closeDropdown() : openDropdown();
		} );

		menu.addEventListener( 'click', function ( event ) {
			var item = event.target.closest( '.fhs-configurator__tab-dropdown-item' );
			if ( ! item ) {
				return;
			}

			var sectionKey = item.getAttribute( 'data-section-key' );
			var iconClass  = item.getAttribute( 'data-icon-class' );

			// Update trigger label and icon.
			if ( labelEl ) {
				labelEl.textContent = item.textContent.trim();
			}
			if ( iconEl ) {
				iconEl.className = 'icofont ' + ( iconClass || 'icofont-box' );
			}

			// Mark active item.
			menu.querySelectorAll( '.fhs-configurator__tab-dropdown-item' ).forEach( function ( el ) {
				var isTarget = el.getAttribute( 'data-section-key' ) === sectionKey;
				el.classList.toggle( 'is-active', isTarget );
				el.setAttribute( 'aria-selected', isTarget ? 'true' : 'false' );
			} );

			closeDropdown();
			switchTab( wrapper, tabs, panels, sectionKey );
		} );

		// Close when clicking outside.
		document.addEventListener( 'click', function ( event ) {
			if ( ! dropdown.contains( event.target ) ) {
				closeDropdown();
			}
		} );
	}

	function switchTab( wrapper, tabs, panels, targetKey ) {
		tabs.forEach( function ( t ) {
			var isTarget = t.getAttribute( 'data-section-key' ) === targetKey;
			t.classList.toggle( 'is-active', isTarget );
			t.setAttribute( 'aria-selected', isTarget ? 'true' : 'false' );
		} );

		panels.forEach( function ( panel ) {
			var isTarget = panel.getAttribute( 'data-section-key' ) === targetKey;
			panel.classList.toggle( 'is-active', isTarget );
			if ( isTarget ) {
				panel.removeAttribute( 'hidden' );
			} else {
				panel.setAttribute( 'hidden', '' );
			}
		} );

		// Keep the mobile dropdown label in sync.
		var dropdown = wrapper.querySelector( '.fhs-configurator__tab-dropdown' );
		if ( dropdown ) {
			var item = dropdown.querySelector( '.fhs-configurator__tab-dropdown-item[data-section-key="' + targetKey + '"]' );
			if ( item ) {
				var labelEl = dropdown.querySelector( '.fhs-configurator__tab-dropdown-label' );
				var iconEl  = dropdown.querySelector( '.fhs-configurator__tab-dropdown-icon i' );
				if ( labelEl ) { labelEl.textContent = item.textContent.trim(); }
				if ( iconEl )  { iconEl.className = 'icofont ' + ( item.getAttribute( 'data-icon-class' ) || 'icofont-box' ); }
				dropdown.querySelectorAll( '.fhs-configurator__tab-dropdown-item' ).forEach( function ( el ) {
					var isTarget = el.getAttribute( 'data-section-key' ) === targetKey;
					el.classList.toggle( 'is-active', isTarget );
					el.setAttribute( 'aria-selected', isTarget ? 'true' : 'false' );
				} );
			}
		}
	}

	function syncSectionSelectedState( wrapper, sectionKey ) {
		var cards = wrapper.querySelectorAll(
			'.fhs-configurator__card[data-section-key="' + sectionKey + '"]'
		);
		cards.forEach( function ( card ) {
			var input = card.querySelector( '.fhs-configurator__card-input' );
			if ( input ) {
				card.classList.toggle( 'is-selected', input.checked );
			}
		} );
	}

	function syncAllSelectedStates( wrapper ) {
		wrapper.querySelectorAll( '.fhs-configurator__panel' ).forEach( function ( panel ) {
			syncSectionSelectedState( wrapper, panel.getAttribute( 'data-section-key' ) );
		} );
	}

	function handleSelectAll( wrapper, sectionKey ) {
		var inputs = getSectionInputs( wrapper, sectionKey );
		var allChecked = inputs.length > 0 && inputs.every( function ( i ) { return i.checked; } );
		var newState = ! allChecked;

		inputs.forEach( function ( input ) {
			input.checked = newState;
		} );

		syncSectionSelectedState( wrapper, sectionKey );
		updateSelectAllButton( wrapper, sectionKey );
		dispatchTemporaryChangeEvent( wrapper );

		// Show "Updating…" immediately; commit fires after debounce.
		scheduleSectionCommit( wrapper, sectionKey );
	}

	function updateSelectAllButton( wrapper, sectionKey ) {
		var btn = wrapper.querySelector(
			'.fhs-configurator__select-all[data-section-key="' + sectionKey + '"]'
		);
		if ( ! btn ) {
			return;
		}

		var inputs = getSectionInputs( wrapper, sectionKey );
		var allChecked = inputs.length > 0 && inputs.every( function ( i ) { return i.checked; } );
		btn.textContent = allChecked ? 'Deselect all' : 'Select all';
	}

	function updateAllSelectAllButtons( wrapper ) {
		wrapper.querySelectorAll( '.fhs-configurator__select-all' ).forEach( function ( btn ) {
			updateSelectAllButton( wrapper, btn.getAttribute( 'data-section-key' ) );
		} );
	}

	function getConfiguratorSelections( wrapper ) {
		wrapper = wrapper || document.querySelector( '.fhs-configurator' );
		if ( ! wrapper ) {
			return {};
		}

		var state = {};
		wrapper.querySelectorAll( '.fhs-configurator__panel' ).forEach( function ( panel ) {
			var sectionKey = panel.getAttribute( 'data-section-key' );
			var selected = [];
			getSectionInputs( wrapper, sectionKey ).forEach( function ( input ) {
				if ( input.checked ) {
					selected.push( parseInt( input.value, 10 ) );
				}
			} );
			state[ sectionKey ] = selected;
		} );
		return state;
	}

	function commitSectionSelection( wrapper, sectionKey ) {
		var temporarySelections = getConfiguratorSelections( wrapper );
		var committed = getCommittedConfiguration( wrapper );
		var selectedIds = Array.isArray( temporarySelections[ sectionKey ] ) ? temporarySelections[ sectionKey ].slice() : [];

		if ( sectionKey === 'machine_packages' ) {
			var baseProduct = getBaseProduct( wrapper );
			var baseProductId = baseProduct ? baseProduct.id : 0;

			if ( selectedIds.length ) {
				var chosenId = selectedIds[0];
				if ( chosenId === baseProductId ) {
					// User chose the base product card — treat as base_product source.
					committed.sections.machine_packages = [];
					committed.activeMachineProductId = baseProductId;
					committed.activeMachineSource = 'base_product';
				} else {
					// User chose an actual machine package.
					committed.sections.machine_packages = [ chosenId ];
					committed.activeMachineProductId = chosenId;
					committed.activeMachineSource = 'machine_packages';
				}
			} else {
				// Nothing selected — clear machine entirely.
				committed.sections.machine_packages = [];
				committed.activeMachineProductId = 0;
				committed.activeMachineSource = 'none';
			}
		} else {
			committed.sections[ sectionKey ] = selectedIds;
		}

		renderCommittedConfigurationPanel( wrapper );
		dispatchCommittedChangeEvent( wrapper );
	}

	function removeCommittedProduct( wrapper, sectionKey, productId ) {
		var committed = getCommittedConfiguration( wrapper );
		if ( sectionKey === 'machine_packages' || sectionKey === 'base_product' ) {
			committed.sections.machine_packages = [];
			committed.activeMachineProductId = 0;
			committed.activeMachineSource = 'none';

			// Uncheck the radio in the left panel.
			var machineInputs = getSectionInputs( wrapper, 'machine_packages' );
			machineInputs.forEach( function ( input ) {
				input.checked = false;
			} );
			syncSectionSelectedState( wrapper, 'machine_packages' );
			updateSelectAllButton( wrapper, 'machine_packages' );
		} else if ( Array.isArray( committed.sections[ sectionKey ] ) ) {
			committed.sections[ sectionKey ] = committed.sections[ sectionKey ].filter( function ( id ) {
				return id !== productId;
			} );

			// Uncheck the matching input in the left panel.
			var input = wrapper.querySelector(
				'.fhs-configurator__card-input[data-section-key="' + sectionKey + '"][value="' + productId + '"]'
			);
			if ( input ) {
				input.checked = false;
			}
			syncSectionSelectedState( wrapper, sectionKey );
			updateSelectAllButton( wrapper, sectionKey );
		}

		renderCommittedConfigurationPanel( wrapper );
		dispatchCommittedChangeEvent( wrapper );
	}

	function clearConfiguration( wrapper ) {
		wrapper.querySelectorAll( '.fhs-configurator__card-input' ).forEach( function ( input ) {
			input.checked = false;
		} );

		syncAllSelectedStates( wrapper );
		updateAllSelectAllButtons( wrapper );
		dispatchTemporaryChangeEvent( wrapper );

		var committed = getCommittedConfiguration( wrapper );
		committed.sections = createEmptySectionsState();
		committed.activeMachineProductId = 0;
		committed.activeMachineSource = 'none';

		renderCommittedConfigurationPanel( wrapper );
		dispatchCommittedChangeEvent( wrapper );
		clearSummaryMessage( wrapper );
		clearSavedConfiguration( wrapper );
	}

	function handleAddAllToCart( wrapper, button ) {
		if ( wrapper.fhsCartRequestInFlight ) {
			return;
		}
		doAddAllToCart( wrapper, button, false );
	}

	/**
	 * @param {boolean} isRetry  true when called after a nonce refresh — prevents infinite loop.
	 */
	function doAddAllToCart( wrapper, button, isRetry ) {
		var ajaxUrl = wrapper.getAttribute( 'data-ajax-url' );
		var nonce = wrapper.getAttribute( 'data-cart-nonce' );
		var baseProductId = parseInt( wrapper.getAttribute( 'data-product-id' ), 10 ) || 0;
		var committed = cloneCommittedConfiguration( getCommittedConfiguration( wrapper ) );

		if ( ! ajaxUrl || ! nonce || ! baseProductId ) {
			setSummaryMessage( wrapper, 'Unable to add this configuration to the cart. Please refresh the page and try again.' );
			return;
		}

		// Build a qty map: { productId: qty } for every committed product.
		var quantities = {};
		var allIds = [];

		// Machine product
		if ( committed.activeMachineSource !== 'none' && committed.activeMachineProductId ) {
			allIds.push( committed.activeMachineProductId );
		}

		// All other sections
		SECTION_ORDER.forEach( function ( sectionKey ) {
			var ids = Array.isArray( committed.sections[ sectionKey ] ) ? committed.sections[ sectionKey ] : [];
			ids.forEach( function ( id ) { allIds.push( id ); } );
		} );

		allIds.forEach( function ( id ) {
			if ( ! id ) return;
			quantities[ id ] = getCardQty( wrapper, id );
		} );

		wrapper.fhsCartRequestInFlight = true;
		setAddAllButtonState( button, true );
		setSummaryMessage( wrapper, '' );

		var formData = new FormData();
		formData.append( 'action', 'fhs_configurator_add_all_to_cart' );
		formData.append( 'nonce', nonce );
		formData.append( 'base_product_id', String( baseProductId ) );
		formData.append( 'configuration', JSON.stringify( committed ) );
		formData.append( 'quantities', JSON.stringify( quantities ) );

		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				// Nonce expired — 403 from check_ajax_referer.
				if ( ( response.status === 403 || response.status === 500 ) && ! isRetry ) {
					return refreshNonceAndRetry( wrapper, button, ajaxUrl );
				}
				return response.json().catch( function () {
					return {
						success: false,
						data: { message: 'Unable to add this configuration to the cart. Please refresh the page and try again.' },
					};
				} );
			} )
			.then( function ( result ) {
				if ( ! result || result.__retrying ) {
					return; // nonce refresh path already handles continuation
				}
				if ( ! result.success || ! result.data ) {
					var message = result && result.data && result.data.message
						? result.data.message
						: 'Unable to add this configuration to the cart. Please refresh the page and try again.';
					throw new Error( message );
				}

				// Inject the WooCommerce notice into the page.
				if ( result.data.notices_html ) {
					var existingWrapper = document.querySelector( '.woocommerce-notices-wrapper' );
					if ( existingWrapper ) {
						existingWrapper.outerHTML = result.data.notices_html;
					} else {
						var productEl = document.querySelector( '.product' ) || document.querySelector( '.single-product-content-container' );
						if ( productEl ) {
							productEl.insertAdjacentHTML( 'beforebegin', result.data.notices_html );
						}
					}
					var notice = document.querySelector( '.woocommerce-message' );
					if ( notice ) {
						notice.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
						notice.focus();
					}
				}

				if ( typeof jQuery !== 'undefined' ) {
					jQuery( document.body ).trigger( 'wc_fragment_refresh' );
				}

				wrapper.fhsCartRequestInFlight = false;
				setAddAllButtonState( button, false );
			} )
			.catch( function ( error ) {
				setSummaryMessage( wrapper, error && error.message ? error.message : 'Unable to add this configuration to the cart. Please refresh the page and try again.' );
				wrapper.fhsCartRequestInFlight = false;
				setAddAllButtonState( button, false );
			} );
	}

	/**
	 * Fetch a fresh nonce then retry the cart request once.
	 */
	function refreshNonceAndRetry( wrapper, button, ajaxUrl ) {
		var refreshData = new FormData();
		refreshData.append( 'action', 'fhs_configurator_refresh_nonce' );

		return fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: refreshData,
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( result ) {
				if ( result && result.success && result.data && result.data.nonce ) {
					// Update the DOM so future requests also use the fresh nonce.
					wrapper.setAttribute( 'data-cart-nonce', result.data.nonce );
					// Reset in-flight flag before retrying.
					wrapper.fhsCartRequestInFlight = false;
					doAddAllToCart( wrapper, button, true );
				} else {
					throw new Error( 'Session expired. Please refresh the page and try again.' );
				}
			} )
			.then( function () {
				return { __retrying: true }; // signal to outer .then to no-op
			} )
			.catch( function ( err ) {
				throw err;
			} );
	}

	function setAddAllButtonState( button, isLoading ) {
		if ( ! button ) {
			return;
		}

		if ( ! button.getAttribute( 'data-default-label' ) ) {
			button.setAttribute( 'data-default-label', button.textContent );
		}

		button.disabled = isLoading;
		button.textContent = isLoading ? 'Adding…' : button.getAttribute( 'data-default-label' );
	}

	function renderCommittedConfigurationPanel( wrapper ) {
		var summary = getSummaryElements( wrapper );
		if ( ! summary.root || ! summary.body || ! summary.count || ! summary.subtotal ) {
			return;
		}

		var data = buildCommittedConfigurationViewModel( wrapper );
		clearElement( summary.body );

		if ( data.itemCount === 0 ) {
			var empty = document.createElement( 'p' );
			empty.className = 'fhs-configurator__summary-empty';
			empty.textContent = 'No products added yet.';
			summary.body.appendChild( empty );
		} else {
			data.sections.forEach( function ( section ) {
				summary.body.appendChild( renderCommittedSection( section ) );
			} );
		}

		summary.count.textContent = formatItemCount( data.itemCount );
		summary.subtotal.innerHTML =
			data.subtotalHtml +
			'<span class="fhs-configurator__tax-label"> (Ex. GST)</span>';

		// Disable/enable Add All to Cart button based on item count
		var addAllBtn = summary.root ? summary.root.querySelector( '[data-fhs-config-add-all]' ) : null;
		if ( addAllBtn ) {
			addAllBtn.disabled = data.itemCount === 0;
		}
	}

	function buildCommittedConfigurationViewModel( wrapper ) {
		var committed = getCommittedConfiguration( wrapper );
		var productMap = getProductMap( wrapper );
		var sectionLabels = getSectionLabels( wrapper );
		var baseProduct = getBaseProduct( wrapper );
		var sections = [];
		var subtotal = 0;
		var itemCount = 0;
		var machineProduct = null;
		if ( committed.activeMachineSource === 'machine_packages' ) {
			machineProduct = productMap[ String( committed.activeMachineProductId ) ] || null;
		} else if ( committed.activeMachineSource === 'base_product' ) {
			machineProduct = baseProduct || null;
		}
		// activeMachineSource === 'none' → machineProduct stays null → nothing rendered.

		if ( machineProduct ) {
			var machineQty = getCardQty( wrapper, machineProduct.id );
			var machineWithQty = Object.assign( {}, machineProduct, { qty: machineQty } );
			sections.push( {
				key: committed.activeMachineSource === 'machine_packages' ? 'machine_packages' : 'base_product',
				label: sectionLabels.machine_packages || 'Machine Packages',
				items: [ machineWithQty ],
			} );
			subtotal += getNumericPrice( machineProduct.price_value ) * machineQty;
			itemCount += machineQty;
		}

		SECTION_ORDER.forEach( function ( sectionKey ) {
			if ( sectionKey === 'machine_packages' ) {
				return;
			}

			var ids = Array.isArray( committed.sections[ sectionKey ] ) ? committed.sections[ sectionKey ] : [];
			var items = [];

			ids.forEach( function ( id ) {
				var product = productMap[ String( id ) ];
				if ( ! product ) {
					return;
				}
				var qty = getCardQty( wrapper, id );
				var productWithQty = Object.assign( {}, product, { qty: qty } );
				items.push( productWithQty );
				subtotal += getNumericPrice( product.price_value ) * qty;
				itemCount += qty;
			} );

			if ( items.length ) {
				sections.push( {
					key: sectionKey,
					label: sectionLabels[ sectionKey ] || formatSectionLabel( sectionKey ),
					items: items,
				} );
			}
		} );

		return {
			sections: sections,
			itemCount: itemCount,
			subtotal: subtotal,
			subtotalHtml: formatCurrency( subtotal ),
		};
	}

	function renderCommittedSection( section ) {
		var sectionEl = document.createElement( 'section' );
		sectionEl.className = 'fhs-configurator__summary-section';

		var header = document.createElement( 'div' );
		header.className = 'fhs-configurator__summary-section-header';

		var title = document.createElement( 'h3' );
		title.className = 'fhs-configurator__summary-section-title';
		title.textContent = section.label;
		header.appendChild( title );

		sectionEl.appendChild( header );

		section.items.forEach( function ( item ) {
			sectionEl.appendChild( renderCommittedItem( section.key, item ) );
		} );

		return sectionEl;
	}

	function renderCommittedItem( sectionKey, item ) {
		var article = document.createElement( 'article' );
		article.className = 'fhs-configurator__summary-item';

		var img = document.createElement( 'img' );
		img.className = 'fhs-configurator__summary-item-image';
		img.src = item.image_url || '';
		img.alt = item.name || '';
		img.loading = 'lazy';

		var body = document.createElement( 'div' );
		body.className = 'fhs-configurator__summary-item-body';

		var name = document.createElement( 'p' );
		name.className = 'fhs-configurator__summary-item-name';
		name.textContent = item.name || '';

		var sku = document.createElement( 'p' );
		sku.className = 'fhs-configurator__summary-item-sku';
		sku.textContent = item.sku || '';

		var price = document.createElement( 'div' );
		price.className = 'fhs-configurator__summary-item-price';
		if ( item.price_html || item.price_display ) {
			price.innerHTML =
				( item.price_html || item.price_display ) +
				'<span class="fhs-configurator__tax-label"> (Ex. GST)</span>';
		}

		// Quantity label — shown before price, format "Quantity: N"
		var qty = item.qty || 1;
		var qtyBadge = document.createElement( 'div' );
		qtyBadge.className = 'fhs-configurator__summary-item-qty';
		qtyBadge.innerHTML =
			'<span class="fhs-conf-qty-label">Quantity:</span>' +
			'<span class="fhs-conf-qty-badge">' + qty + '</span>';

		body.appendChild( name );
		if ( item.sku ) {
			body.appendChild( sku );
		}
		body.appendChild( qtyBadge );
		body.appendChild( price );

		var removeSection = sectionKey === 'base_product' ? 'machine_packages' : sectionKey;
		var remove = document.createElement( 'button' );
		remove.type = 'button';
		remove.className = 'fhs-configurator__summary-remove';
		remove.setAttribute( 'data-fhs-config-remove', '1' );
		remove.setAttribute( 'data-section-key', removeSection );
		remove.setAttribute( 'data-product-id', String( item.id ) );
		remove.textContent = '\u2715';
		remove.setAttribute( 'aria-label', 'Remove' );
		remove.setAttribute( 'title', 'Remove' );

		article.appendChild( img );
		article.appendChild( body );
		article.appendChild( remove );

		return article;
	}

	function getCommittedConfiguration( wrapper ) {
		if ( ! wrapper.fhsCommittedConfiguration ) {
			wrapper.fhsCommittedConfiguration = createInitialCommittedConfiguration( wrapper );
		}
		return wrapper.fhsCommittedConfiguration;
	}

	// ── localStorage persistence ─────────────────────────────────────────────

	function getStorageKey( wrapper ) {
		var productId = wrapper.getAttribute( 'data-product-id' );
		return 'fhs_configurator_' + ( productId || 'default' );
	}

	/** Collect the current qty for every card in the wrapper into a plain object. */
	function collectAllQtys( wrapper ) {
		var qtys = {};
		wrapper.querySelectorAll( '.fhs-configurator__card[data-product-id]' ).forEach( function ( card ) {
			var pid = card.getAttribute( 'data-product-id' );
			if ( pid ) {
				qtys[ pid ] = parseInt( card.getAttribute( 'data-qty' ), 10 ) || 1;
			}
		} );
		return qtys;
	}

	function saveConfiguration( wrapper ) {
		try {
			var config = cloneCommittedConfiguration( getCommittedConfiguration( wrapper ) );
			// Attach per-product quantities so they survive a page reload.
			config.quantities = collectAllQtys( wrapper );
			localStorage.setItem( getStorageKey( wrapper ), JSON.stringify( config ) );
		} catch ( e ) {
			// localStorage unavailable — silently ignore.
		}
	}

	function clearSavedConfiguration( wrapper ) {
		try {
			localStorage.removeItem( getStorageKey( wrapper ) );
		} catch ( e ) {}
	}

	/**
	 * Restore a previously saved configuration on page load.
	 * Replaces the initial committed state, then syncs card inputs to match.
	 */
	function restoreConfiguration( wrapper ) {
		var saved;
		try {
			var raw = localStorage.getItem( getStorageKey( wrapper ) );
			if ( ! raw ) {
				return;
			}
			saved = JSON.parse( raw );
		} catch ( e ) {
			return;
		}

		if ( ! saved || typeof saved !== 'object' ) {
			return;
		}

		var committed = getCommittedConfiguration( wrapper );
		var baseProduct = getBaseProduct( wrapper );
		var baseProductId = baseProduct ? baseProduct.id : 0;

		// Restore scalar fields with safe fallbacks.
		committed.activeMachineSource    = saved.activeMachineSource    || 'none';
		committed.activeMachineProductId = saved.activeMachineProductId || 0;

		// Restore sections — only known keys, all must be arrays.
		if ( saved.sections && typeof saved.sections === 'object' ) {
			SECTION_ORDER.forEach( function ( key ) {
				if ( Array.isArray( saved.sections[ key ] ) ) {
					committed.sections[ key ] = saved.sections[ key ].map( Number ).filter( Boolean );
				}
			} );
		}

		// Restore per-product quantities onto the card DOM elements.
		if ( saved.quantities && typeof saved.quantities === 'object' ) {
			Object.keys( saved.quantities ).forEach( function ( pid ) {
				var qty  = Math.max( 1, parseInt( saved.quantities[ pid ], 10 ) || 1 );
				var card = wrapper.querySelector( '.fhs-configurator__card[data-product-id="' + pid + '"]' );
				if ( card ) {
					card.setAttribute( 'data-qty', qty );
					var input = card.querySelector( '.fhs-conf-qty-input' );
					if ( input ) {
						input.value = qty;
					}
				}
			} );
		}

		// Sync the left-panel card inputs to match the restored committed state.
		// Machine packages: check the saved machine card (base or package).
		var machineInputs = getSectionInputs( wrapper, 'machine_packages' );
		machineInputs.forEach( function ( input ) {
			var inputId = parseInt( input.value, 10 );
			if ( committed.activeMachineSource === 'machine_packages' ) {
				input.checked = inputId === committed.activeMachineProductId;
			} else if ( committed.activeMachineSource === 'base_product' ) {
				input.checked = inputId === baseProductId;
			} else {
				input.checked = false;
			}
		} );

		// All other sections: check inputs whose IDs are in the restored array.
		SECTION_ORDER.forEach( function ( sectionKey ) {
			if ( sectionKey === 'machine_packages' ) {
				return;
			}
			var ids = committed.sections[ sectionKey ] || [];
			getSectionInputs( wrapper, sectionKey ).forEach( function ( input ) {
				input.checked = ids.indexOf( parseInt( input.value, 10 ) ) !== -1;
			} );
		} );
	}

	function dispatchTemporaryChangeEvent( wrapper ) {
		var event = new CustomEvent( 'fhs:configurator:change', {
			bubbles: true,
			cancelable: false,
			detail: {
				selections: getConfiguratorSelections( wrapper ),
			},
		} );
		wrapper.dispatchEvent( event );
	}

	function dispatchCommittedChangeEvent( wrapper ) {
		var committed = getCommittedConfiguration( wrapper );

		// Persist every committed change to localStorage.
		saveConfiguration( wrapper );

		var event = new CustomEvent( 'fhs:configurator:committed-change', {
			bubbles: true,
			cancelable: false,
			detail: {
				configuration: cloneCommittedConfiguration( committed ),
			},
		} );
		wrapper.dispatchEvent( event );
	}

	function cloneCommittedConfiguration( configuration ) {
		return {
			baseProductId: configuration.baseProductId,
			activeMachineProductId: configuration.activeMachineProductId,
			activeMachineSource: configuration.activeMachineSource,
			sections: JSON.parse( JSON.stringify( configuration.sections ) ),
		};
	}

	function getSummaryElements( wrapper ) {
		var pageContainer = wrapper.closest( '.single-product-content-container' ) || document;
		var sidebar = pageContainer.querySelector( '.fhs-configurator-sidebar' );
		return {
			root: sidebar,
			body: sidebar ? sidebar.querySelector( '[data-fhs-config-body]' ) : null,
			count: sidebar ? sidebar.querySelector( '[data-fhs-config-count]' ) : null,
			subtotal: sidebar ? sidebar.querySelector( '[data-fhs-config-subtotal]' ) : null,
			message: sidebar ? sidebar.querySelector( '[data-fhs-config-message]' ) : null,
		};
	}

	function setSummaryMessage( wrapper, message ) {
		var summary = getSummaryElements( wrapper );
		if ( summary.message ) {
			summary.message.textContent = message || '';
			summary.message.classList.toggle( 'is-success', !! message && message.charAt( 0 ) === '\u2713' );
		}
	}

	function clearSummaryMessage( wrapper ) {
		setSummaryMessage( wrapper, '' );
	}

	function getProductMap( wrapper ) {
		return parseJsonDataAttribute( wrapper, 'data-product-map' );
	}

	function getSectionLabels( wrapper ) {
		return parseJsonDataAttribute( wrapper, 'data-section-labels' );
	}

	function getBaseProduct( wrapper ) {
		return parseJsonDataAttribute( wrapper, 'data-base-product' );
	}

	function parseJsonDataAttribute( element, attr ) {
		if ( ! element ) {
			return {};
		}
		var value = element.getAttribute( attr );
		if ( ! value ) {
			return {};
		}
		try {
			return JSON.parse( value );
		} catch ( error ) {
			return {};
		}
	}

	function formatCurrency( amount ) {
		var symbol = getCurrencySymbolFromWrapper();
		return symbol + Number( amount || 0 ).toFixed( 2 );
	}

	function getCurrencySymbolFromWrapper() {
		var wrapper = document.querySelector( '.fhs-configurator' );
		var base = wrapper ? getBaseProduct( wrapper ) : null;
		if ( base && base.price_display ) {
			var baseMatch = base.price_display.match( /[^\d\s.,-]+/ );
			if ( baseMatch ) {
				return baseMatch[0];
			}
		}
		var map = wrapper ? getProductMap( wrapper ) : {};
		var keys = Object.keys( map );
		for ( var i = 0; i < keys.length; i++ ) {
			var item = map[ keys[i] ];
			if ( item && item.price_display ) {
				var match = item.price_display.match( /[^\d\s.,-]+/ );
				if ( match ) {
					return match[0];
				}
			}
		}
		return '$';
	}

	function formatItemCount( count ) {
		return count + ' ' + ( count === 1 ? 'item' : 'items' );
	}

	function formatSectionLabel( sectionKey ) {
		return String( sectionKey || '' ).replace( /_/g, ' ' ).replace( /\b\w/g, function ( char ) {
			return char.toUpperCase();
		} );
	}

	function getNumericPrice( value ) {
		var parsed = parseFloat( value );
		return isNaN( parsed ) ? 0 : parsed;
	}

	// Read the committed qty for a product from its card DOM element.
	// Falls back to 1 if the card isn't found or has no data-qty.
	function getCardQty( wrapper, productId ) {
		var card = wrapper
			? wrapper.querySelector( '.fhs-configurator__card[data-product-id="' + productId + '"]' )
			: null;
		if ( ! card ) return 1;
		var qty = parseInt( card.getAttribute( 'data-qty' ), 10 );
		return ( qty > 0 ) ? qty : 1;
	}

	function clearElement( element ) {
		while ( element.firstChild ) {
			element.removeChild( element.firstChild );
		}
	}

	function getSectionInputs( wrapper, sectionKey ) {
		return Array.prototype.slice.call(
			wrapper.querySelectorAll( '.fhs-configurator__card-input[data-section-key="' + sectionKey + '"]' )
		);
	}

	// ── Section status indicator ─────────────────────────────────────────────

	// Per-section debounce timers (commit is deferred until user stops changing).
	var sectionCommitTimers = {};
	// Per-section hide timers (auto-clear the "Updated ✓" pill after a delay).
	var sectionHideTimers = {};

	/** Debounce delay in ms before committing after the last change. */
	var COMMIT_DEBOUNCE_MS = 600;
	/** How long "Updated ✓" stays visible before fading out. */
	var UPDATED_HIDE_MS = 2500;

	/**
	 * Trigger a debounced auto-commit for a section.
	 * Shows "Updating…" immediately; after COMMIT_DEBOUNCE_MS of inactivity
	 * the commit fires and the pill switches to "Updated ✓".
	 *
	 * @param {Element} wrapper
	 * @param {string}  sectionKey
	 */
	function scheduleSectionCommit( wrapper, sectionKey ) {
		// Show "Updating…" right away and cancel any pending hide timer.
		setSectionStatus( wrapper, sectionKey, 'updating' );

		// Reset the debounce timer.
		if ( sectionCommitTimers[ sectionKey ] ) {
			clearTimeout( sectionCommitTimers[ sectionKey ] );
		}

		sectionCommitTimers[ sectionKey ] = setTimeout( function () {
			sectionCommitTimers[ sectionKey ] = null;
			commitSectionSelection( wrapper, sectionKey );
			setSectionStatus( wrapper, sectionKey, 'updated' );
		}, COMMIT_DEBOUNCE_MS );
	}

	/**
	 * Set the status pill next to a section heading.
	 *
	 * @param {Element} wrapper
	 * @param {string}  sectionKey
	 * @param {'updating'|'updated'|''} state
	 */
	function setSectionStatus( wrapper, sectionKey, state ) {
		var el = wrapper.querySelector(
			'[data-section-status="' + sectionKey + '"]'
		);
		if ( ! el ) {
			return;
		}

		// Always cancel the pending hide timer when state changes.
		if ( sectionHideTimers[ sectionKey ] ) {
			clearTimeout( sectionHideTimers[ sectionKey ] );
			sectionHideTimers[ sectionKey ] = null;
		}

		el.className = 'fhs-configurator__section-status';

		if ( state === 'updating' ) {
			el.classList.add( 'is-updating' );
			el.textContent = 'Updating…';
		} else if ( state === 'updated' ) {
			el.classList.add( 'is-updated' );
			el.textContent = 'Updated ✓';
			// Auto-hide after UPDATED_HIDE_MS.
			sectionHideTimers[ sectionKey ] = setTimeout( function () {
				el.classList.add( 'is-hiding' );
				sectionHideTimers[ sectionKey ] = setTimeout( function () {
					el.className = 'fhs-configurator__section-status';
					el.textContent = '';
					sectionHideTimers[ sectionKey ] = null;
				}, 300 ); // matches the CSS transition duration
			}, UPDATED_HIDE_MS );
		} else {
			el.textContent = '';
		}
	}

	// ── Quantity steppers ────────────────────────────────────────────────────
	//
	// Stepper controls use <span role="button"> instead of <button> so the
	// browser never triggers label-forwarding (only interactive elements
	// inside a <label> activate the associated input).  Keyboard users
	// can still activate them via Enter/Space.

	function initQtySteppers( wrapper ) {

		function handleQtyTrigger( target ) {
			var isMinus = target.classList.contains( 'fhs-conf-qty-minus' );
			var isPlus  = target.classList.contains( 'fhs-conf-qty-plus' );
			if ( ! isMinus && ! isPlus ) return false;

			var wrap  = target.closest( '.fhs-configurator__card-qty-wrap' );
			if ( ! wrap ) return true;

			var input = wrap.querySelector( '.fhs-conf-qty-input' );
			if ( ! input ) return true;

			var current = parseInt( input.value, 10 ) || 1;
			var min     = parseInt( input.getAttribute( 'min' ), 10 ) || 1;
			var max     = parseInt( input.getAttribute( 'max' ), 10 ) || 999;
			var next    = isPlus ? Math.min( current + 1, max ) : Math.max( current - 1, min );

			if ( next === current ) return true;

			input.value = next;

			var card = wrap.closest( '.fhs-configurator__card' );
			if ( card ) {
				card.setAttribute( 'data-qty', next );
				var sectionKey = card.getAttribute( 'data-section-key' );
				if ( sectionKey ) {
					scheduleSectionCommit( wrapper, sectionKey );
				}
			}
			return true;
		}

		// Click — works for mouse and touch.
		wrapper.addEventListener( 'click', function ( event ) {
			if ( handleQtyTrigger( event.target ) ) {
				event.stopPropagation();
			}
		} );

		// Keyboard — Enter or Space on a focused span[role="button"].
		wrapper.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Enter' || event.key === ' ' ) {
				if ( handleQtyTrigger( event.target ) ) {
					event.preventDefault();
					event.stopPropagation();
				}
			}
		} );

		// Direct keyboard entry into the number input.
		wrapper.addEventListener( 'change', function ( event ) {
			var input = event.target;
			if ( ! input.classList.contains( 'fhs-conf-qty-input' ) ) return;

			var min   = parseInt( input.getAttribute( 'min' ), 10 ) || 1;
			var max   = parseInt( input.getAttribute( 'max' ), 10 ) || 999;
			var value = parseInt( input.value, 10 );

			if ( isNaN( value ) || value < min ) value = min;
			if ( value > max ) value = max;
			input.value = value;

			var card = input.closest( '.fhs-configurator__card' );
			if ( card ) {
				card.setAttribute( 'data-qty', value );
				var sectionKey = card.getAttribute( 'data-section-key' );
				if ( sectionKey ) {
					scheduleSectionCommit( wrapper, sectionKey );
				}
			}
		} );
	}

	// ── View more / less toggle ──────────────────────────────────────────────
	//
	// Reads data-cols from .fhs-configurator__grid-wrap (set in the PHP template)
	// to know how many cards fill one row.  Cards beyond that first row get the
	// .fhs-configurator__card--overflow class and are hidden by CSS.
	// A "View more ▾" button is shown below the grid; clicking it toggles
	// visibility and flips the button label / chevron.
	//
	// initViewMore must be called once per configurator wrapper on init AND
	// again whenever a tab switch shows a panel for the first time (panels
	// start [hidden] so their layout hasn't been measured yet).  We handle
	// both cases by calling setupPanelViewMore inside switchTab as well.

	function initViewMore( wrapper ) {
		// Wire up every panel that is already visible.
		wrapper.querySelectorAll( '.fhs-configurator__panel' ).forEach( function ( panel ) {
			setupPanelViewMore( panel );
		} );

		// Re-run whenever a tab is activated (panels start hidden so offsetTop
		// is 0 until they are shown for the first time).
		var tabs = wrapper.querySelectorAll( '.fhs-configurator__tab' );
		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var key   = tab.getAttribute( 'data-section-key' );
				var panel = wrapper.querySelector(
					'#fhs-conf-panel-' + key
				);
				if ( panel ) {
					// Small delay so the panel is visible before we measure.
					setTimeout( function () {
						setupPanelViewMore( panel );
					}, 30 );
				}
			} );
		} );
	}

	function setupPanelViewMore( panel ) {
		var gridWrap = panel.querySelector( '.fhs-configurator__grid-wrap' );
		if ( ! gridWrap ) return;

		var grid   = gridWrap.querySelector( '.fhs-configurator__grid' );
		var moreBtnWrap = gridWrap.querySelector( '.fhs-configurator__view-more' );
		if ( ! grid || ! moreBtnWrap ) return;

		var cards  = Array.prototype.slice.call(
			grid.querySelectorAll( '.fhs-configurator__card' )
		);

		// Determine columns from data-cols attribute or by counting.
		var cols = parseInt( gridWrap.getAttribute( 'data-cols' ), 10 ) || 4;

		// Recalculate from actual rendered layout when panel is visible.
		if ( panel.offsetParent !== null ) {
			cols = getRenderedColumns( grid, cards );
		}

		var threshold = cols; // hide everything after the first row

		if ( cards.length <= threshold ) {
			// All cards fit in one row — no toggle needed.
			moreBtnWrap.style.display = 'none';
			cards.forEach( function ( c ) {
				c.classList.remove( 'fhs-configurator__card--overflow' );
				c.classList.remove( 'is-visible' );
			} );
			return;
		}

		// Mark overflow cards.
		cards.forEach( function ( card, idx ) {
			if ( idx >= threshold ) {
				card.classList.add( 'fhs-configurator__card--overflow' );
				card.classList.remove( 'is-visible' );
			} else {
				card.classList.remove( 'fhs-configurator__card--overflow' );
			}
		} );

		// Show the toggle bar.
		moreBtnWrap.style.display = '';

		var btn = moreBtnWrap.querySelector( '.fhs-configurator__view-more-btn' );
		if ( ! btn ) return;

		// Remove any previously attached listener by cloning.
		var freshBtn = btn.cloneNode( true );
		moreBtnWrap.replaceChild( freshBtn, btn );
		btn = freshBtn;

		var labelEl = btn.querySelector( '.fhs-configurator__view-more-label' );
		var expanded = false;

		btn.setAttribute( 'aria-expanded', 'false' );

		btn.addEventListener( 'click', function () {
			expanded = ! expanded;
			btn.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );

			cards.forEach( function ( card, idx ) {
				if ( idx >= threshold ) {
					if ( expanded ) {
						card.classList.add( 'is-visible' );
					} else {
						card.classList.remove( 'is-visible' );
					}
				}
			} );

			if ( labelEl ) {
				labelEl.textContent = expanded ? 'View less' : 'View more';
			}

			// Scroll the newly revealed cards into view when expanding.
			if ( expanded ) {
				var firstHidden = cards[ threshold ];
				if ( firstHidden ) {
					firstHidden.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
				}
			}
		} );
	}

	// Count how many grid columns are actually rendered by comparing offsetTop
	// of successive cards.  Falls back to data-cols if cards aren't visible.
	function getRenderedColumns( grid, cards ) {
		if ( ! cards.length ) return 4;
		var firstTop = cards[0].offsetTop;
		var count    = 0;
		for ( var i = 0; i < cards.length; i++ ) {
			if ( cards[i].offsetTop === firstTop ) {
				count++;
			} else {
				break;
			}
		}
		return count > 0 ? count : 4;
	}

	// ── Expose per-card quantities on getConfiguratorSelections ─────────────
	//
	// Wraps the original function so callers (including the Add-to-Cart handler)
	// get quantity information alongside the selected product IDs.

	var _originalGetSelections = getConfiguratorSelections;
	getConfiguratorSelections = function ( wrapper ) {
		var state = _originalGetSelections( wrapper );
		// Annotate each selected ID with its quantity.
		if ( wrapper || document.querySelector( '.fhs-configurator' ) ) {
			var w = wrapper || document.querySelector( '.fhs-configurator' );
			Object.keys( state ).forEach( function ( sectionKey ) {
				var ids = state[ sectionKey ];
				if ( ! ids || ! ids.length ) return;
				state[ sectionKey ] = ids.map( function ( id ) {
					var card = w.querySelector(
						'.fhs-configurator__card[data-product-id="' + id + '"]'
					);
					var qty = card ? ( parseInt( card.getAttribute( 'data-qty' ), 10 ) || 1 ) : 1;
					// Return the id as-is (backwards compat) but also expose qty
					// on a parallel map so cart code can read it.
					return id;
				} );
				// Build parallel qty map: state.__qty[sectionKey][id] = qty
				if ( ! state.__qty ) state.__qty = {};
				if ( ! state.__qty[ sectionKey ] ) state.__qty[ sectionKey ] = {};
				ids.forEach( function ( id ) {
					var card = w.querySelector(
						'.fhs-configurator__card[data-product-id="' + id + '"]'
					);
					state.__qty[ sectionKey ][ id ] =
						card ? ( parseInt( card.getAttribute( 'data-qty' ), 10 ) || 1 ) : 1;
				} );
			} );
		}
		return state;
	};

	window.fhsConfigurator = {
		getSelections: function () {
			return getConfiguratorSelections();
		},
		getCommittedConfiguration: function () {
			var wrapper = document.querySelector( '.fhs-configurator' );
			return wrapper ? cloneCommittedConfiguration( getCommittedConfiguration( wrapper ) ) : {};
		},
		// Helper: get quantity for a specific product card.
		getProductQty: function ( productId ) {
			var card = document.querySelector(
				'.fhs-configurator__card[data-product-id="' + productId + '"]'
			);
			return card ? ( parseInt( card.getAttribute( 'data-qty' ), 10 ) || 1 ) : 1;
		},
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();