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
 *  2. fhs_configurator_filter_astra_structure() — filters the
 *     `astra_woo_single_product_structure` array before Astra's
 *     single_product_content_structure() iterates it.
 *     When the configurator is active, removes the 'add_cart' element so
 *     Astra never calls woocommerce_template_single_add_to_cart() directly.
 *     When the configurator is inactive, returns the array completely unchanged.
 *
 * Why this approach (not remove_action)
 * ──────────────────────────────────────
 * Astra (>= 3.9.2) removes all default woocommerce_single_product_summary
 * callbacks on the `wp` action and replaces them with a single callback —
 * single_product_content_structure() at priority 10 — which calls
 * woocommerce_template_single_add_to_cart() as a direct PHP function call
 * inside a switch/case block. remove_action() cannot intercept a direct
 * function call. The only correct intercept point is the
 * `astra_woo_single_product_structure` filter, which Astra applies to its
 * structure array before iterating it.
 *
 * What this file does NOT do yet
 * ───────────────────────────────
 *  - No template output
 *  - No session handling
 *  - No AJAX handlers
 *  - No asset enqueuing
 *  - No ACF field registration (fields already exist in WordPress)
 *  - No modification to woocommerce_template_single_meta behaviour
 *
 * @package FHS_WOO
 * @version 1.1.0
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
// 2. Remove 'add_cart' from Astra's product structure when configurator is on
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Filters the Astra single-product structure array.
 *
 * Astra iterates this array in single_product_content_structure() and calls
 * woocommerce_template_single_add_to_cart() directly for the 'add_cart' element.
 * Removing 'add_cart' here prevents that direct call from happening.
 *
 * When the configurator is inactive this filter returns $structure untouched,
 * so all existing products are completely unaffected.
 *
 * @param array $structure Astra's ordered structure array, e.g.
 *                         ['title', 'price', 'short_desc', 'add_cart', 'meta'].
 * @return array
 */
function fhs_configurator_filter_astra_structure( $structure ) {

	if ( ! is_product() ) {
		return $structure;
	}

	global $product;

	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}

	if ( ! fhs_configurator_is_active( $product ) ) {
		return $structure;
	}

	// Strip 'add_cart' and re-index so Astra's foreach stays clean.
	return array_values( array_diff( (array) $structure, array( 'add_cart' ) ) );
}

add_filter( 'astra_woo_single_product_structure', 'fhs_configurator_filter_astra_structure' );
