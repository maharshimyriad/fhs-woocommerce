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

// ─────────────────────────────────────────────────────────────────────────────
// 3. Render configurator skeleton on fhs_inside_product_main_container
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Outputs the configurator mount point inside .single-product-content-container,
 * after .single-product-layout-wrap closes.
 *
 * Hooked to the custom fhs_inside_product_main_container action defined in
 * content-single-product.php. That hook fires at the same level used by the
 * variation card grid, so the configurator renders full-width below the
 * image + summary row.
 *
 * When the configurator is inactive this function outputs nothing, leaving
 * fhs_inside_product_main_container available for other callbacks (e.g. the
 * variation card grid) without interference.
 */
function fhs_render_configurator() {

	if ( ! is_product() ) {
		return;
	}

	global $product;

	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}

	if ( ! fhs_configurator_is_active( $product ) ) {
		return;
	}

	echo '<div class="fhs-configurator">Configurator placeholder</div>';
}

add_action( 'fhs_inside_product_main_container', 'fhs_render_configurator' );

// ─────────────────────────────────────────────────────────────────────────────
// TEMPORARY DIAGNOSTICS — remove after debugging
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Writes a line to wp-content/uploads/fhs-configurator-debug.log
 *
 * @param string $message
 */
function fhs_conf_log( $message ) {
	$upload_dir = wp_upload_dir();
	$log_file   = $upload_dir['basedir'] . '/fhs-configurator-debug.log';
	$line       = '[' . date( 'Y-m-d H:i:s' ) . '] ' . $message . PHP_EOL;
	file_put_contents( $log_file, $line, FILE_APPEND | LOCK_EX );
}

add_action( 'woocommerce_before_single_product', function() {

	fhs_conf_log( '=== PAGE LOAD START (product ID: ' . get_the_ID() . ') ===' );

	// 1. Which content-single-product.php is actually loaded?
	$located = wc_locate_template( 'content-single-product.php' );
	fhs_conf_log( '1. content-single-product.php resolved to: ' . $located );

	// 2. Does the child theme woocommerce folder contain our override?
	$child_path = get_stylesheet_directory() . '/woocommerce/content-single-product.php';
	fhs_conf_log( '2. Child theme override path: ' . $child_path );
	fhs_conf_log( '2. Child theme override exists: ' . ( file_exists( $child_path ) ? 'YES' : 'NO' ) );

	// 3. Child theme directory itself.
	fhs_conf_log( '3. get_stylesheet_directory(): ' . get_stylesheet_directory() );

	// 4. Current product type.
	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	$type = $product ? $product->get_type() : 'COULD NOT RESOLVE PRODUCT';
	fhs_conf_log( '4. Product type: ' . $type );

	// 5. fhs_configurator_is_active() result.
	$active = $product ? fhs_configurator_is_active( $product ) : false;
	fhs_conf_log( '5. fhs_configurator_is_active(): ' . ( $active ? 'TRUE' : 'FALSE' ) );

	// 6. ACF raw field value.
	if ( function_exists( 'get_field' ) && $product ) {
		$raw = get_field( 'enable_product_configurator', $product->get_id() );
		fhs_conf_log( '6. ACF enable_product_configurator raw: ' . var_export( $raw, true ) );
	} else {
		fhs_conf_log( '6. ACF get_field() not available or product not resolved.' );
	}

	// 7. Is fhs_render_configurator registered on the hook?
	$registered = has_action( 'fhs_inside_product_main_container', 'fhs_render_configurator' );
	fhs_conf_log( '7. fhs_render_configurator registered on fhs_inside_product_main_container: ' . var_export( $registered, true ) );

	fhs_conf_log( '=== PAGE LOAD END ===' );
	fhs_conf_log( '' );

} );
