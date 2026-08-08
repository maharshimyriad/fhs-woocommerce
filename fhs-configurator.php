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
// 4a. Product data helper
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Returns a sanitised data array for a single product.
 *
 * Used by fhs_configurator_get_sections() for each validated product.
 * Pricing is intentionally omitted here — that is Step 5.
 *
 * @param int $product_id Validated, absint product ID.
 * @return array {
 *     @type int    $id             Product ID.
 *     @type string $name           Product display name.
 *     @type string $sku            Product SKU (empty string if none).
 *     @type string $image_url      Full URL to the product thumbnail, or placeholder.
 *     @type string $price_html     Customer-facing HTML price string.
 *     @type float  $price_value    Numeric price used for subtotal calculation.
 *     @type string $price_display  Plain-text display version of the active price.
 * }
 */
function fhs_configurator_get_product_data( $product_id ) {

	$product = wc_get_product( absint( $product_id ) );

	if ( ! $product || ! $product->is_type( 'simple' ) ) {
		return array();
	}

	// Image: product thumbnail → parent fallback → WC placeholder.
	$image_id  = $product->get_image_id();
	$image_url = $image_id
		? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' )
		: wc_placeholder_img_src( 'woocommerce_thumbnail' );

	$price_html    = '';
	$price_value   = 0.0;
	$price_display = '';

	if ( is_user_logged_in() ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			$level_a_regular = function_exists( 'fhs_get_level_a_regular_price' )
				? fhs_get_level_a_regular_price( $product )
				: '';
			$level_a_sale    = function_exists( 'fhs_get_level_a_sale_price' )
				? fhs_get_level_a_sale_price( $product )
				: '';

			if ( '' !== $level_a_regular && null !== $level_a_regular ) {
				$level_a_regular = (float) $level_a_regular;
				$level_a_sale    = '' !== $level_a_sale && null !== $level_a_sale ? (float) $level_a_sale : 0.0;

				if ( $level_a_sale > 0 && $level_a_sale < $level_a_regular ) {
					$price_value   = $level_a_sale;
					$price_html    = wc_format_sale_price( $level_a_regular, $level_a_sale );
					$price_display = wp_strip_all_tags( wc_price( $level_a_sale ) );
				} else {
					$price_value   = $level_a_regular;
					$price_html    = wc_price( $level_a_regular );
					$price_display = wp_strip_all_tags( wc_price( $level_a_regular ) );
				}
			} else {
				$regular_price = (float) $product->get_regular_price();
				$sale_price    = (float) $product->get_sale_price();

				if ( $sale_price > 0 && $sale_price < $regular_price ) {
					$price_value   = $sale_price;
					$price_html    = wc_format_sale_price( $regular_price, $sale_price );
					$price_display = wp_strip_all_tags( wc_price( $sale_price ) );
				} else {
					$active_price  = $regular_price > 0 ? $regular_price : (float) $product->get_price();
					$price_value   = $active_price;
					$price_html    = $active_price > 0 ? wc_price( $active_price ) : '';
					$price_display = $active_price > 0 ? wp_strip_all_tags( wc_price( $active_price ) ) : '';
				}
			}
		} else {
			$base_price = (float) $product->get_regular_price();
			$tier_price = (float) $product->get_price();
			$suffix     = method_exists( $product, 'get_price_suffix' ) ? $product->get_price_suffix() : '';

			if ( $tier_price > 0 && $base_price > 0 && $tier_price < $base_price ) {
				$price_value   = $tier_price;
				$price_html    = wc_format_sale_price( $base_price, $tier_price ) . $suffix;
				$price_display = wp_strip_all_tags( wc_price( $tier_price ) . $suffix );
			} else {
				$active_price  = $tier_price > 0 ? $tier_price : $base_price;
				$price_value   = $active_price;
				$price_html    = $active_price > 0 ? wc_price( $active_price ) . $suffix : '';
				$price_display = $active_price > 0 ? wp_strip_all_tags( wc_price( $active_price ) . $suffix ) : '';
			}
		}
	}

	return array(
		'id'            => $product->get_id(),
		'name'          => $product->get_name(),
		'sku'           => $product->get_sku(),
		'image_url'     => $image_url ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
		'price_html'    => $price_html,
		'price_value'   => $price_value,
		'price_display' => $price_display,
	);
}

// ─────────────────────────────────────────────────────────────────────────────
// 4b. Section loader
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Reads the configurator_options ACF group for a product and returns all
 * non-empty sections with validated, simple-product-only product data.
 *
 * Machine Packages
 * ─────────────────
 * Always selection_type 'single'. No separate ACF selection_type field.
 * Returned as the first element of the array when non-empty.
 *
 * Additive sections
 * ─────────────────
 * Each has a product Relationship field and a matching *_selection_type field.
 * selection_type is either 'single' or 'multiple'. Defaults to 'multiple' if the
 * ACF field returns an unexpected value (matches the ACF field's default value).
 *
 * Validation applied to every product ID from every Relationship field:
 *   - absint()
 *   - wc_get_product() must return a valid object
 *   - is_type('simple') must be true
 * Invalid or non-simple products are silently skipped.
 * Sections that end up with zero valid products are excluded from the return value.
 *
 * ACF Relationship ordering is preserved — no re-sorting is applied.
 *
 * @param int $product_id The configurator base product ID.
 * @return array[] Ordered array of section arrays. Each section:
 *   {
 *     @type string  $key            Internal section key, e.g. 'machine_packages'.
 *     @type string  $label          Human-readable section label.
 *     @type string  $selection_type 'single' or 'multiple'.
 *     @type array[] $products       Array of fhs_configurator_get_product_data() results.
 *   }
 */
function fhs_configurator_get_sections( $product_id ) {

	$product_id = absint( $product_id );

	if ( ! $product_id || ! function_exists( 'get_field' ) ) {
		return array();
	}

	// Read the ACF Group field once — all sub-fields are keys in this array.
	$group = get_field( 'configurator_options', $product_id );

	if ( ! is_array( $group ) ) {
		return array();
	}

	// ── Section definitions ───────────────────────────────────────────────────
	// Order here is the display order.
	// machine_packages has no selection_type ACF field — hardcoded 'single'.
	$section_defs = array(
		array(
			'key'              => 'machine_packages',
			'label'            => 'Machine Packages',
			'products_field'   => 'machine_packages',
			'type_field'       => null,   // always single
			'fixed_type'       => 'single',
		),
		array(
			'key'              => 'liner_sets',
			'label'            => 'Liner Sets',
			'products_field'   => 'liner_sets',
			'type_field'       => 'liner_sets_selection_type',
			'fixed_type'       => null,
		),
		array(
			'key'              => 'replacement_parts',
			'label'            => 'Replacement Parts',
			'products_field'   => 'replacement_parts',
			'type_field'       => 'replacement_parts_selection_type',
			'fixed_type'       => null,
		),
		array(
			'key'              => 'accessories',
			'label'            => 'Accessories',
			'products_field'   => 'accessories',
			'type_field'       => 'accessories_selection_type',
			'fixed_type'       => null,
		),
		array(
			'key'              => 'data_logging',
			'label'            => 'Data Logging',
			'products_field'   => 'data_logging',
			'type_field'       => 'data_logging_selection_type',
			'fixed_type'       => null,
		),
		array(
			'key'              => 'consumables',
			'label'            => 'Consumables',
			'products_field'   => 'consumables',
			'type_field'       => 'consumables_selection_type',
			'fixed_type'       => null,
		),
		array(
			'key'              => 'tooling_extras',
			'label'            => 'Tooling & Extras',
			'products_field'   => 'tooling_extras',
			'type_field'       => 'tooling_extras_selection_type',
			'fixed_type'       => null,
		),
	);

	$sections = array();

	foreach ( $section_defs as $def ) {

		// Read the Relationship value from the group array.
		$raw_products = isset( $group[ $def['products_field'] ] ) && is_array( $group[ $def['products_field'] ] )
			? $group[ $def['products_field'] ]
			: array();

		if ( empty( $raw_products ) || ! is_array( $raw_products ) ) {
			continue; // No products assigned — skip this section entirely.
		}

		// Validate each product: absint, wc_get_product, is_type('simple').
		$valid_products = array();
		foreach ( $raw_products as $raw ) {
			// ACF Relationship can return WP_Post objects or post IDs.
			$pid = is_object( $raw ) ? absint( $raw->ID ) : absint( $raw );

			if ( ! $pid ) {
				continue;
			}

			$data = fhs_configurator_get_product_data( $pid );

			if ( empty( $data ) ) {
				// fhs_configurator_get_product_data() already validates simple type.
				continue;
			}

			$valid_products[] = $data;
		}

		if ( empty( $valid_products ) ) {
			continue; // All products in this section were invalid — skip.
		}

		// Determine selection type.
		if ( null !== $def['fixed_type'] ) {
			$selection_type = $def['fixed_type'];
		} else {
			$raw_type       = isset( $group[ $def['type_field'] ] ) ? $group[ $def['type_field'] ] : 'multiple';
			$selection_type = in_array( $raw_type, array( 'single', 'multiple' ), true )
				? $raw_type
				: 'multiple'; // Matches ACF field default value.
		}

		$sections[] = array(
			'key'            => $def['key'],
			'label'          => $def['label'],
			'selection_type' => $selection_type,
			'products'       => $valid_products,
		);
	}

	return $sections;
}

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
function fhs_get_current_configurator_product() {

	if ( ! is_product() ) {
		return null;
	}

	global $product;

	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}

	if ( ! $product instanceof WC_Product || ! fhs_configurator_is_active( $product ) ) {
		return null;
	}

	return $product;
}

/**
 * Enqueues configurator assets once per page.
 *
 * Safe to call from either the main render callback or the sidebar callback.
 * WordPress will ignore duplicate enqueue attempts for the same handles.
 */
function fhs_configurator_enqueue_assets() {

	$asset_base = get_stylesheet_directory_uri() . '/woocommerce/single-product/add-to-cart/';

	if ( ! wp_style_is( 'fhs-configurator', 'enqueued' ) ) {
		wp_enqueue_style(
			'fhs-configurator',
			$asset_base . 'configurator.css',
			array( 'woocommerce-general' ),
			'3.1.0'
		);
	}

	if ( ! wp_script_is( 'fhs-configurator', 'enqueued' ) ) {
		wp_enqueue_script(
			'fhs-configurator',
			$asset_base . 'configurator.js',
			array(),
			'3.1.0',
			true
		);
	}
}

function fhs_render_configurator() {

	$product = fhs_get_current_configurator_product();

	if ( ! $product ) {
		return;
	}

	$sections = fhs_configurator_get_sections( $product->get_id() );

	if ( empty( $sections ) ) {
		return;
	}

	fhs_configurator_enqueue_assets();

	wc_get_template(
		'single-product/add-to-cart/configurator-template.php',
		array(
			'configurator_product' => $product,
			'sections'             => $sections,
		)
	);
}

/**
 * Outputs the right-hand configuration summary column beside the full left
 * product/configurator area.
 *
 * This is intentionally separate from fhs_render_configurator() so the sidebar
 * can be rendered as a sibling of the entire left column wrapper created in
 * content-single-product.php.
 */
function fhs_render_configurator_sidebar() {

	$product = fhs_get_current_configurator_product();

	if ( ! $product ) {
		return;
	}

	$sections = fhs_configurator_get_sections( $product->get_id() );

	if ( empty( $sections ) ) {
		return;
	}

	fhs_configurator_enqueue_assets();

	echo '<aside class="fhs-configurator-sidebar" aria-label="' . esc_attr__( 'Your Configuration', 'woocommerce' ) . '">';
	echo '	<div class="fhs-configurator__right">';
	echo '		<div class="fhs-configurator__summary-card">';
	echo '			<div class="fhs-configurator__summary-header">';
	echo '				<div class="fhs-configurator__summary-heading-group">';
	echo '					<h2 class="fhs-configurator__summary-title">' . esc_html__( 'Your Configuration', 'woocommerce' ) . '</h2>';
	echo '					<p class="fhs-configurator__summary-count" data-fhs-config-count>0 items</p>';
	echo '				</div>';
	echo '				<button type="button" class="fhs-configurator__clear-all" data-fhs-config-clear>' . esc_html__( 'Clear all', 'woocommerce' ) . '</button>';
	echo '			</div>';
	echo '			<div class="fhs-configurator__summary-body" data-fhs-config-body>';
	echo '				<div class="fhs-configurator__summary-empty" data-fhs-config-empty>' . esc_html__( 'No optional configurator items selected yet.', 'woocommerce' ) . '</div>';
	echo '			</div>';
	echo '			<div class="fhs-configurator__summary-footer">';
	echo '				<div class="fhs-configurator__summary-subtotal-row">';
	echo '					<span class="fhs-configurator__summary-subtotal-label">' . esc_html__( 'Subtotal', 'woocommerce' ) . '</span>';
	echo '					<span class="fhs-configurator__summary-subtotal-value" data-fhs-config-subtotal>' . wp_kses_post( wc_price( 0 ) ) . '</span>';
	echo '				</div>';
	echo '			</div>';
	echo '		</div>';
	echo '	</div>';
	echo '</aside>';
}

add_action( 'fhs_inside_product_main_container', 'fhs_render_configurator' );
add_action( 'fhs_configurator_sidebar_area', 'fhs_render_configurator_sidebar' );


