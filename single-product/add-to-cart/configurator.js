/**
 * FHS Product Configurator — Client-side selection behaviour
 *
 * Responsibilities (this file only):
 *   1. Tab switching — show/hide section panels.
 *   2. Single-selection (radio) — one product per section, no toggle-off.
 *   3. Multiple-selection (checkbox) — independent toggles.
 *   4. Select All / Deselect All for multiple sections.
 *   5. .is-selected class on cards — mirrors input checked state.
 *   6. getConfiguratorSelections() — returns current state grouped by key.
 *   7. fhs:configurator:change custom event — dispatched after any selection change.
 *   8. Your Configuration panel — reads existing selection state and rebuilds UI.
 *
 * NOT in this file:
 *   - Add All to Cart
 *   - AJAX
 *   - PHP sessions / localStorage / persistence
 *
 * Plain vanilla JS — no jQuery, no framework dependency.
 *
 * @package FHS_WOO
 * @version 1.1.0
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

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var targetKey = tab.getAttribute( 'data-section-key' );
				switchTab( wrapper, tabs, panels, targetKey );
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
			dispatchChangeEvent( wrapper );
		} );

		var selectAllBtns = wrapper.querySelectorAll( '.fhs-configurator__select-all' );
		selectAllBtns.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var sectionKey = btn.getAttribute( 'data-section-key' );
				handleSelectAll( wrapper, sectionKey, btn );
			} );
		} );

		if ( summary.root ) {
			summary.root.addEventListener( 'click', function ( event ) {
				var removeBtn = event.target.closest( '[data-fhs-config-remove]' );
				if ( removeBtn ) {
					removeConfigurationProduct(
						wrapper,
						removeBtn.getAttribute( 'data-section-key' ),
						removeBtn.getAttribute( 'data-product-id' )
					);
					return;
				}

				var editBtn = event.target.closest( '[data-fhs-config-edit]' );
				if ( editBtn ) {
					openConfigurationSection( wrapper, editBtn.getAttribute( 'data-section-key' ) );
					return;
				}

				var clearBtn = event.target.closest( '[data-fhs-config-clear]' );
				if ( clearBtn ) {
					clearConfiguration( wrapper );
				}
			} );
		}

		syncAllSelectedStates( wrapper );
		updateAllSelectAllButtons( wrapper );
		renderConfigurationPanel( wrapper );

		wrapper.addEventListener( 'fhs:configurator:change', function () {
			renderConfigurationPanel( wrapper );
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
		var panels = wrapper.querySelectorAll( '.fhs-configurator__panel' );
		panels.forEach( function ( panel ) {
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
		dispatchChangeEvent( wrapper );
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
		var buttons = wrapper.querySelectorAll( '.fhs-configurator__select-all' );
		buttons.forEach( function ( btn ) {
			updateSelectAllButton( wrapper, btn.getAttribute( 'data-section-key' ) );
		} );
	}

	function getConfiguratorSelections( wrapper ) {
		wrapper = wrapper || document.querySelector( '.fhs-configurator' );
		if ( ! wrapper ) {
			return {};
		}

		var state = {};
		var panels = wrapper.querySelectorAll( '.fhs-configurator__panel' );

		panels.forEach( function ( panel ) {
			var sectionKey = panel.getAttribute( 'data-section-key' );
			var inputs = getSectionInputs( wrapper, sectionKey );
			var selected = [];

			inputs.forEach( function ( input ) {
				if ( input.checked ) {
					selected.push( parseInt( input.value, 10 ) );
				}
			} );

			state[ sectionKey ] = selected;
		} );

		return state;
	}

	function dispatchChangeEvent( wrapper ) {
		var event = new CustomEvent( 'fhs:configurator:change', {
			bubbles: true,
			cancelable: false,
			detail: {
				selections: getConfiguratorSelections( wrapper ),
			},
		} );
		wrapper.dispatchEvent( event );
	}

	function renderConfigurationPanel( wrapper ) {
		var summary = getSummaryElements( wrapper );
		if ( ! summary.root || ! summary.body || ! summary.count || ! summary.subtotal ) {
			return;
		}

		var data = buildConfigurationData( wrapper, getConfiguratorSelections( wrapper ) );
		clearElement( summary.body );

		if ( data.sections.length === 0 ) {
			appendEmptyState( summary.body );
		} else {
			data.sections.forEach( function ( section ) {
				summary.body.appendChild( renderConfigurationSection( section ) );
			} );
		}

		summary.count.textContent = formatItemCount( data.itemCount );
		summary.subtotal.innerHTML = data.subtotalHtml;
	}

	function buildConfigurationData( wrapper, selections ) {
		var productMap = getProductMap( wrapper );
		var sectionLabels = getSectionLabels( wrapper );
		var sections = [];
		var subtotal = 0;
		var itemCount = 0;

		SECTION_ORDER.forEach( function ( sectionKey ) {
			var ids = Array.isArray( selections[ sectionKey ] ) ? selections[ sectionKey ] : [];
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

	function renderConfigurationSection( section ) {
		var wrapper = document.createElement( 'section' );
		wrapper.className = 'fhs-configurator__summary-section';

		var header = document.createElement( 'div' );
		header.className = 'fhs-configurator__summary-section-header';

		var title = document.createElement( 'h3' );
		title.className = 'fhs-configurator__summary-section-title';
		title.textContent = section.label;

		var editBtn = document.createElement( 'button' );
		editBtn.type = 'button';
		editBtn.className = 'fhs-configurator__summary-edit';
		editBtn.setAttribute( 'data-fhs-config-edit', '1' );
		editBtn.setAttribute( 'data-section-key', section.key );
		editBtn.textContent = 'Edit';

		header.appendChild( title );
		header.appendChild( editBtn );
		wrapper.appendChild( header );

		section.items.forEach( function ( item ) {
			wrapper.appendChild( renderConfigurationItem( section.key, item ) );
		} );

		return wrapper;
	}

	function renderConfigurationItem( sectionKey, item ) {
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

		var remove = document.createElement( 'button' );
		remove.type = 'button';
		remove.className = 'fhs-configurator__summary-remove';
		remove.setAttribute( 'data-fhs-config-remove', '1' );
		remove.setAttribute( 'data-section-key', sectionKey );
		remove.setAttribute( 'data-product-id', String( item.id ) );
		remove.textContent = 'Remove';

		body.appendChild( name );
		if ( item.sku ) {
			body.appendChild( sku );
		}
		body.appendChild( price );
		body.appendChild( remove );

		article.appendChild( img );
		article.appendChild( body );

		return article;
	}

	function removeConfigurationProduct( wrapper, sectionKey, productId ) {
		var input = wrapper.querySelector(
			'.fhs-configurator__card-input[data-section-key="' + sectionKey + '"][data-product-id="' + productId + '"]'
		);
		if ( ! input ) {
			return;
		}

		input.checked = false;
		syncSectionSelectedState( wrapper, sectionKey );
		updateSelectAllButton( wrapper, sectionKey );
		dispatchChangeEvent( wrapper );
	}

	function openConfigurationSection( wrapper, sectionKey ) {
		var tabs = wrapper.querySelectorAll( '.fhs-configurator__tab' );
		var panels = wrapper.querySelectorAll( '.fhs-configurator__panel' );
		switchTab( wrapper, tabs, panels, sectionKey );

		var tab = wrapper.querySelector(
			'.fhs-configurator__tab[data-section-key="' + sectionKey + '"]'
		);
		if ( tab && typeof tab.scrollIntoView === 'function' ) {
			tab.scrollIntoView( { behavior: 'smooth', block: 'nearest', inline: 'nearest' } );
		}
	}

	function clearConfiguration( wrapper ) {
		var inputs = wrapper.querySelectorAll( '.fhs-configurator__card-input' );
		inputs.forEach( function ( input ) {
			input.checked = false;
		} );

		syncAllSelectedStates( wrapper );
		updateAllSelectAllButtons( wrapper );
		dispatchChangeEvent( wrapper );
	}

	function getSummaryElements( wrapper ) {
		var pageContainer = wrapper.closest( '.single-product-content-container' ) || document;
		var sidebar = pageContainer.querySelector( '.fhs-configurator-sidebar' );

		return {
			root: sidebar,
			body: sidebar ? sidebar.querySelector( '[data-fhs-config-body]' ) : null,
			count: sidebar ? sidebar.querySelector( '[data-fhs-config-count]' ) : null,
			subtotal: sidebar ? sidebar.querySelector( '[data-fhs-config-subtotal]' ) : null,
		};
	}

	function appendEmptyState( container ) {
		var empty = document.createElement( 'div' );
		empty.className = 'fhs-configurator__summary-empty';
		empty.textContent = 'No optional configurator items selected yet.';
		container.appendChild( empty );
	}

	function getProductMap( wrapper ) {
		return parseJsonDataAttribute( wrapper, 'data-product-map' );
	}

	function getSectionLabels( wrapper ) {
		return parseJsonDataAttribute( wrapper, 'data-section-labels' );
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
		if ( typeof window.fhsWooPriceFormatter === 'function' ) {
			return window.fhsWooPriceFormatter( amount );
		}

		var symbol = getCurrencySymbolFromProductMap();
		return symbol + Number( amount || 0 ).toFixed( 2 );
	}

	function getCurrencySymbolFromProductMap() {
		var anyWrapper = document.querySelector( '.fhs-configurator[data-product-map]' );
		var map = getProductMap( anyWrapper );
		var keys = Object.keys( map );
		for ( var i = 0; i < keys.length; i++ ) {
			var item = map[ keys[ i ] ];
			if ( item && item.price_display ) {
				var match = item.price_display.match( /[^\d\s.,-]+/ );
				if ( match ) {
					return match[0];
				}
			}
		}
		return '$';
	}

	function getNumericPrice( value ) {
		var parsed = parseFloat( value );
		return isNaN( parsed ) ? 0 : parsed;
	}

	function formatItemCount( count ) {
		return count + ' ' + ( count === 1 ? 'item' : 'items' );
	}

	function formatSectionLabel( sectionKey ) {
		return String( sectionKey || '' )
			.replace( /_/g, ' ' )
			.replace( /\b\w/g, function ( char ) {
				return char.toUpperCase();
			} );
	}

	function clearElement( element ) {
		while ( element.firstChild ) {
			element.removeChild( element.firstChild );
		}
	}

	function getSectionInputs( wrapper, sectionKey ) {
		return Array.prototype.slice.call(
			wrapper.querySelectorAll(
				'.fhs-configurator__card-input[data-section-key="' + sectionKey + '"]'
			)
		);
	}

	window.fhsConfigurator = {
		getSelections: function () {
			return getConfiguratorSelections();
		},
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

} )();
