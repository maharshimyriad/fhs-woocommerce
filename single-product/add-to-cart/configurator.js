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

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				switchTab( wrapper, tabs, panels, tab.getAttribute( 'data-section-key' ) );
			} );
		} );

		wrapper.addEventListener( 'change', function ( event ) {
			var input = event.target;
			if ( ! input.classList.contains( 'fhs-configurator__card-input' ) ) {
				return;
			}

			var sectionKey = input.getAttribute( 'data-section-key' );
			syncSectionSelectedState( wrapper, sectionKey );
			updateSelectAllButton( wrapper, sectionKey );
			dispatchTemporaryChangeEvent( wrapper );
		} );

		wrapper.querySelectorAll( '.fhs-configurator__select-all' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				handleSelectAll( wrapper, btn.getAttribute( 'data-section-key' ) );
			} );
		} );

		wrapper.querySelectorAll( '.fhs-configurator__commit-section' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				commitSectionSelection( wrapper, btn.getAttribute( 'data-section-key' ) );
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
		} else if ( Array.isArray( committed.sections[ sectionKey ] ) ) {
			committed.sections[ sectionKey ] = committed.sections[ sectionKey ].filter( function ( id ) {
				return id !== productId;
			} );
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
	}

	function handleAddAllToCart( wrapper, button ) {
		if ( wrapper.fhsCartRequestInFlight ) {
			return;
		}

		var ajaxUrl = wrapper.getAttribute( 'data-ajax-url' );
		var nonce = wrapper.getAttribute( 'data-cart-nonce' );
		var baseProductId = parseInt( wrapper.getAttribute( 'data-product-id' ), 10 ) || 0;
		var committed = cloneCommittedConfiguration( getCommittedConfiguration( wrapper ) );

		if ( ! ajaxUrl || ! nonce || ! baseProductId ) {
			setSummaryMessage( wrapper, 'Unable to add this configuration to the cart. Please refresh the page and try again.' );
			return;
		}

		wrapper.fhsCartRequestInFlight = true;
		setAddAllButtonState( button, true );
		setSummaryMessage( wrapper, '' );

		var formData = new FormData();
		formData.append( 'action', 'fhs_configurator_add_all_to_cart' );
		formData.append( 'nonce', nonce );
		formData.append( 'base_product_id', String( baseProductId ) );
		formData.append( 'configuration', JSON.stringify( committed ) );

		fetch( ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				return response.json().catch( function () {
					return {
						success: false,
						data: {
							message: 'Unable to add this configuration to the cart. Please refresh the page and try again.',
						},
					};
				} );
			} )
			.then( function ( result ) {
				if ( ! result || ! result.success || ! result.data || ! result.data.cart_url ) {
					var message = result && result.data && result.data.message
						? result.data.message
						: 'Unable to add this configuration to the cart. Please refresh the page and try again.';
					throw new Error( message );
				}

				window.location.href = result.data.cart_url;
			} )
			.catch( function ( error ) {
				setSummaryMessage( wrapper, error && error.message ? error.message : 'Unable to add this configuration to the cart. Please refresh the page and try again.' );
				wrapper.fhsCartRequestInFlight = false;
				setAddAllButtonState( button, false );
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
		summary.subtotal.innerHTML = data.subtotalHtml;
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
			sections.push( {
				key: committed.activeMachineSource === 'machine_packages' ? 'machine_packages' : 'base_product',
				label: committed.activeMachineSource === 'machine_packages'
					? ( sectionLabels.machine_packages === 'Machine Packages' ),
				items: [ machineProduct ],
			} );
			subtotal += getNumericPrice( machineProduct.price_value );
			itemCount += 1;
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
				items.push( product );
				subtotal += getNumericPrice( product.price_value );
				itemCount += 1;
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
		price.innerHTML = item.price_html || item.price_display || formatCurrency( 0 );

		body.appendChild( name );
		if ( item.sku ) {
			body.appendChild( sku );
		}
		body.appendChild( price );

		article.appendChild( img );
		article.appendChild( body );

		if ( sectionKey !== 'base_product' ) {
			var remove = document.createElement( 'button' );
			remove.type = 'button';
			remove.className = 'fhs-configurator__summary-remove';
			remove.setAttribute( 'data-fhs-config-remove', '1' );
			remove.setAttribute( 'data-section-key', sectionKey );
			remove.setAttribute( 'data-product-id', String( item.id ) );
			remove.setAttribute( 'aria-label', 'Remove ' + ( item.name || '' ) );
			remove.textContent = '×';
			article.appendChild( remove );
		}

		return article;
	}

	function getCommittedConfiguration( wrapper ) {
		if ( ! wrapper.fhsCommittedConfiguration ) {
			wrapper.fhsCommittedConfiguration = createInitialCommittedConfiguration( wrapper );
		}
		return wrapper.fhsCommittedConfiguration;
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

	window.fhsConfigurator = {
		getSelections: function () {
			return getConfiguratorSelections();
		},
		getCommittedConfiguration: function () {
			var wrapper = document.querySelector( '.fhs-configurator' );
			return wrapper ? cloneCommittedConfiguration( getCommittedConfiguration( wrapper ) ) : {};
		},
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
