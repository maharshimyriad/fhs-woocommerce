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
 *
 * NOT in this file:
 *   - Your Configuration panel (Step 6)
 *   - Pricing / totals
 *   - Add All to Cart
 *   - AJAX
 *   - PHP sessions / localStorage / persistence
 *
 * Plain vanilla JS — no jQuery, no framework dependency.
 *
 * @package FHS_WOO
 * @version 1.0.0
 */

( function () {

	'use strict';

	// ── Bootstrap ─────────────────────────────────────────────────────────────

	/**
	 * Initialise all configurator instances on the page.
	 * (There will normally be exactly one per page load.)
	 */
	function init() {
		var wrappers = document.querySelectorAll( '.fhs-configurator' );
		wrappers.forEach( function ( wrapper ) {
			initConfigurator( wrapper );
		} );
	}

	// ── Per-instance initialisation ───────────────────────────────────────────

	/**
	 * Wire up all behaviour for one .fhs-configurator element.
	 *
	 * @param {HTMLElement} wrapper  The .fhs-configurator root element.
	 */
	function initConfigurator( wrapper ) {

		// ── Tab switching ──────────────────────────────────────────────────

		var tabs   = wrapper.querySelectorAll( '.fhs-configurator__tab' );
		var panels = wrapper.querySelectorAll( '.fhs-configurator__panel' );

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var targetKey = tab.getAttribute( 'data-section-key' );
				switchTab( wrapper, tabs, panels, targetKey );
			} );
		} );

		// ── Card selection ─────────────────────────────────────────────────

		/*
		 * Cards are <label> elements wrapping an <input type="radio|checkbox">.
		 * The browser handles checking/unchecking the input natively when the
		 * label is clicked. We listen for 'change' on the wrapper to:
		 *   - sync .is-selected class on the parent card
		 *   - for single/radio sections: ensure the previously selected card
		 *     loses .is-selected (the browser already unchecks the other radio,
		 *     but we need to remove the CSS class from its label)
		 *   - update Select All button text
		 *   - dispatch the change event
		 */
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

		/*
		 * Radio behaviour: prevent deselecting an already-selected radio by
		 * clicking it again.  The spec says single-selection keeps the choice.
		 * We intercept mousedown/keydown to suppress the uncheck — but since
		 * we are using native radio inputs the browser will not uncheck on a
		 * second click anyway. No extra code needed for that case.
		 */

		// ── Select All buttons ─────────────────────────────────────────────

		var selectAllBtns = wrapper.querySelectorAll( '.fhs-configurator__select-all' );
		selectAllBtns.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var sectionKey = btn.getAttribute( 'data-section-key' );
				handleSelectAll( wrapper, sectionKey, btn );
			} );
		} );

		// ── Sync initial state (all unchecked on load) ─────────────────────
		// Nothing to sync — all inputs start unchecked. This is a no-op but
		// kept as a hook for future initial-state restoration (Step 6).
	}

	// ── Tab switching ─────────────────────────────────────────────────────────

	/**
	 * Activate the tab and panel matching targetKey; deactivate all others.
	 * Does NOT reset any input selections.
	 *
	 * @param {HTMLElement}     wrapper
	 * @param {NodeList}        tabs
	 * @param {NodeList}        panels
	 * @param {string}          targetKey
	 */
	function switchTab( wrapper, tabs, panels, targetKey ) {

		// Deactivate all tabs.
		tabs.forEach( function ( t ) {
			var isTarget = t.getAttribute( 'data-section-key' ) === targetKey;
			t.classList.toggle( 'is-active', isTarget );
			t.setAttribute( 'aria-selected', isTarget ? 'true' : 'false' );
		} );

		// Show/hide panels.
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

	// ── Card selected-state sync ──────────────────────────────────────────────

	/**
	 * Sync .is-selected on every card in a section to match its input's
	 * checked state.  Called after any change event in that section.
	 *
	 * @param {HTMLElement} wrapper
	 * @param {string}      sectionKey
	 */
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

	// ── Select All / Deselect All ─────────────────────────────────────────────

	/**
	 * Handle a click on a Select All / Deselect All button.
	 *
	 * If all products in the section are already checked → uncheck all.
	 * Otherwise → check all.
	 *
	 * Updates .is-selected on cards and dispatches the change event.
	 *
	 * @param {HTMLElement} wrapper
	 * @param {string}      sectionKey
	 * @param {HTMLElement} btn
	 */
	function handleSelectAll( wrapper, sectionKey, btn ) {
		var inputs = getSectionInputs( wrapper, sectionKey );
		var allChecked = inputs.length > 0 && inputs.every( function ( i ) { return i.checked; } );
		var newState   = ! allChecked;

		inputs.forEach( function ( input ) {
			input.checked = newState;
		} );

		syncSectionSelectedState( wrapper, sectionKey );
		updateSelectAllButton( wrapper, sectionKey );
		dispatchChangeEvent( wrapper );
	}

	/**
	 * Update the Select All button text to reflect the current section state.
	 *
	 * "Select all"   — when at least one product is unchecked.
	 * "Deselect all" — when every product is checked.
	 *
	 * @param {HTMLElement} wrapper
	 * @param {string}      sectionKey
	 */
	function updateSelectAllButton( wrapper, sectionKey ) {
		var btn = wrapper.querySelector(
			'.fhs-configurator__select-all[data-section-key="' + sectionKey + '"]'
		);
		if ( ! btn ) {
			return; // Single-selection sections have no Select All button.
		}

		var inputs    = getSectionInputs( wrapper, sectionKey );
		var allChecked = inputs.length > 0 && inputs.every( function ( i ) { return i.checked; } );

		btn.textContent = allChecked ? 'Deselect all' : 'Select all';
	}

	// ── State reader ──────────────────────────────────────────────────────────

	/**
	 * Return the complete current selection state grouped by section key.
	 *
	 * For each section, the value is an array of selected product IDs (integers).
	 * Single-selection sections will have [] or [id].
	 * Multiple-selection sections will have [] or [id, id, ...].
	 *
	 * Example return value:
	 * {
	 *   machine_packages:  [43409],
	 *   liner_sets:        [40053, 40025],
	 *   replacement_parts: [],
	 *   accessories:       [],
	 *   data_logging:      [],
	 *   consumables:       [],
	 *   tooling_extras:    [],
	 * }
	 *
	 * Exposed on window so it can be called from browser console or the next step:
	 *   window.fhsConfigurator.getSelections()
	 *
	 * @param  {HTMLElement} [wrapper]  Defaults to first .fhs-configurator on page.
	 * @return {Object}
	 */
	function getConfiguratorSelections( wrapper ) {
		wrapper = wrapper || document.querySelector( '.fhs-configurator' );
		if ( ! wrapper ) {
			return {};
		}

		var state   = {};
		var panels  = wrapper.querySelectorAll( '.fhs-configurator__panel' );

		panels.forEach( function ( panel ) {
			var sectionKey = panel.getAttribute( 'data-section-key' );
			var inputs     = getSectionInputs( wrapper, sectionKey );
			var selected   = [];

			inputs.forEach( function ( input ) {
				if ( input.checked ) {
					selected.push( parseInt( input.value, 10 ) );
				}
			} );

			state[ sectionKey ] = selected;
		} );

		return state;
	}

	// ── Custom event dispatch ─────────────────────────────────────────────────

	/**
	 * Dispatch fhs:configurator:change from the wrapper element.
	 * The event detail contains the complete current selection state.
	 *
	 * Listeners can attach like:
	 *   document.querySelector('.fhs-configurator')
	 *     .addEventListener('fhs:configurator:change', function(e) {
	 *       console.log(e.detail.selections);
	 *     });
	 *
	 * @param {HTMLElement} wrapper
	 */
	function dispatchChangeEvent( wrapper ) {
		var event = new CustomEvent( 'fhs:configurator:change', {
			bubbles:    true,
			cancelable: false,
			detail: {
				selections: getConfiguratorSelections( wrapper ),
			},
		} );
		wrapper.dispatchEvent( event );
	}

	// ── Utility ───────────────────────────────────────────────────────────────

	/**
	 * Return all card inputs belonging to a given section.
	 *
	 * @param  {HTMLElement} wrapper
	 * @param  {string}      sectionKey
	 * @return {HTMLElement[]}
	 */
	function getSectionInputs( wrapper, sectionKey ) {
		return Array.prototype.slice.call(
			wrapper.querySelectorAll(
				'.fhs-configurator__card-input[data-section-key="' + sectionKey + '"]'
			)
		);
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/*
	 * Expose a small public API on window so the next implementation step
	 * (Your Configuration panel) can call getSelections() directly, and so
	 * developers can test from the browser console.
	 */
	window.fhsConfigurator = {
		/**
		 * Returns the current selection state for all sections.
		 * @return {Object}
		 */
		getSelections: function () {
			return getConfiguratorSelections();
		},
	};

	// ── Run on DOM ready ──────────────────────────────────────────────────────

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		// DOM already ready (script loaded in footer with defer or after parse).
		init();
	}

} )();
