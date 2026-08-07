<?php
/**
 * FHS Product Configurator — Bootstrap
 *
 * Step 2: Activation guard and add-to-cart suppression only.
 *
 * This file is required from the child theme's functions.php:
 *   require_once get_stylesheet_directory() . '/woocommerce/fhs-configurator.php';
 *
 * What this file does right now
 * ─────────────────────────────
 *  1. fhs_configurator_is_active( $product ) — single source of truth for
 *     whether the configurator should run for a given product.
 *     Condition: product is a WooCommerce Simple type
 *                AND the ACF field `enable_product_configurator` is true.
 *
 *  2. fhs_maybe_suppress_add_to_cart() — hooked at priority 29 on
 *     woocommerce_single_product_summary.
 *     When the configurator is active it removes the standard
 *     woocommerce_template_single_add_to_cart callback (priority 30)
 *     so that simple.php does not render.
 *     On every other product it returns immediately without doing anything.
 *
 * What this file does NOT do yet
 * ───────────────────────────────
 *  - No template output
 *  - No session handling
 *  - No AJAX handlers
 *  - No asset enqueuing
 *  - No ACF field registration (fields already exist in WordPress)
 *  - No modification to woocommerce_template_single_meta (priority 40)
 *
 * @package FHS_WOO
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// 1. Activation guard
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Determines whether the Product Configurator should be active for a product.
 *
 * Both conditions must be true:
 *   a) The product is a WooCommerce Simple product.
 *   b) The ACF field `enable_product_configurator` is truthy for that product.
 *
 * If ACF is not available, the function safely returns false.
 * If the product object cannot be resolved, the function safely returns false.
 *
 * @param WC_Product|int $product WC_Product object or product post ID.
 * @return bool
 */
function fhs_configurator_is_active( $product ) {

	// Resolve product object if an ID was passed.
	if ( is_int( $product ) || ( is_string( $product ) && ctype_digit( $product ) ) ) {
		$product = wc_get_product( (int) $product );
	}

	// Must be a valid WC_Product.
	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	// Must be a Simple product.
	if ( ! $product->is_type( 'simple' ) ) {
		return false;
	}

	// ACF must be available and the field must be truthy.
	if ( ! function_exists( 'get_field' ) ) {
		return false;
	}

	return (bool) get_field( 'enable_product_configurator', $product->get_id() );
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. Suppress woocommerce_template_single_add_to_cart when configurator is on
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Hooked at priority 29 on woocommerce_single_product_summary.
 *
 * Fires one priority step before woocommerce_template_single_add_to_cart (30).
 * If the configurator is active for the current product, removes that callback
 * so simple.php (or any other add-to-cart template) does not render.
 *
 * On every other product this function returns immediately — no side effects.
 */
function fhs_maybe_suppress_add_to_cart() {

	// Only relevant on single product pages.
	if ( ! is_product() ) {
		return;
	}

	global $product;

	// Ensure $product is populated. WooCommerce sets this during the loop
	// in single-product.php, so it should always be available here.
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}

	if ( ! fhs_configurator_is_active( $product ) ) {
		// Not a configurator product — do nothing, let existing flow run.
		return;
	}

	// Remove the standard add-to-cart hook that would otherwise load
	// simple.php via woocommerce_template_single_add_to_cart().
	remove_action(
		'woocommerce_single_product_summary',
		'woocommerce_template_single_add_to_cart',
		30
	);
}

add_action( 'woocommerce_single_product_summary', 'fhs_maybe_suppress_add_to_cart', 29 );
