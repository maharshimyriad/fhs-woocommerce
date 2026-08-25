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

defined('ABSPATH') || exit;

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
function fhs_configurator_is_active($product)
{

	// Resolve product object if an ID was passed.
	if (is_int($product) || (is_string($product) && ctype_digit($product))) {
		$product = wc_get_product((int) $product);
	}

	// Must be a valid WC_Product.
	if (! $product instanceof WC_Product) {
		return false;
	}

	// Must be a Simple product.
	if (! $product->is_type('simple')) {
		return false;
	}

	// ACF must be available and the field must be truthy.
	if (! function_exists('get_field')) {
		return false;
	}

	return (bool) get_field('enable_product_configurator', $product->get_id());
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
function fhs_configurator_filter_astra_structure($structure)
{

	if (! is_product()) {
		return $structure;
	}

	global $product;

	if (! $product instanceof WC_Product) {
		$product = wc_get_product(get_the_ID());
	}

	if (! fhs_configurator_is_active($product)) {
		return $structure;
	}

	// Strip 'add_cart' and re-index so Astra's foreach stays clean.
	return array_values(array_diff((array) $structure, array('add_cart')));
}

add_filter('astra_woo_single_product_structure', 'fhs_configurator_filter_astra_structure');

// Remove related products from all single product pages.
add_action( 'init', function () {
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
} );

/**
 * Add `fhs-configurator-active` to the body classes when the configurator
 * is active for the current product. Useful for page-level CSS targeting.
 */
add_filter('body_class', function ( $classes ) {
	if ( ! is_product() ) {
		return $classes;
	}
	$product = wc_get_product( get_the_ID() );
	if ( $product && fhs_configurator_is_active( $product ) ) {
		$classes[] = 'fhs-configurator-active';
	}
	return $classes;
});

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
function fhs_configurator_get_product_data($product_id)
{

	$product = wc_get_product(absint($product_id));

	if (! $product || ! $product->is_type('simple')) {
		return array();
	}

	// Only show published products that are in stock or on backorder.
	// Exclude draft, pending, private, out-of-stock.
	if ( 'publish' !== $product->get_status() ) {
		return array();
	}

	$stock_status = $product->get_stock_status(); // 'instock' | 'outofstock' | 'onbackorder'
	if ( 'outofstock' === $stock_status ) {
		return array();
	}

	// Image: product thumbnail → parent fallback → WC placeholder.
	$image_id  = $product->get_image_id();
	$image_url = $image_id
		? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail')
		: wc_placeholder_img_src('woocommerce_thumbnail');

	$price_html    = '';
	$price_value   = 0.0;
	$price_display = '';

	if (is_user_logged_in()) {
		if (current_user_can('manage_woocommerce')) {
			$level_a_regular = function_exists('fhs_get_level_a_regular_price')
				? fhs_get_level_a_regular_price($product)
				: '';
			$level_a_sale    = function_exists('fhs_get_level_a_sale_price')
				? fhs_get_level_a_sale_price($product)
				: '';

			if ('' !== $level_a_regular && null !== $level_a_regular) {
				$level_a_regular = (float) $level_a_regular;
				$level_a_sale    = '' !== $level_a_sale && null !== $level_a_sale ? (float) $level_a_sale : 0.0;

				if ($level_a_sale > 0 && $level_a_sale < $level_a_regular) {
					$price_value   = $level_a_sale;
					$price_html    = wc_format_sale_price($level_a_regular, $level_a_sale);
					$price_display = wp_strip_all_tags(wc_price($level_a_sale));
				} else {
					$price_value   = $level_a_regular;
					$price_html    = wc_price($level_a_regular);
					$price_display = wp_strip_all_tags(wc_price($level_a_regular));
				}
			} else {
				$regular_price = (float) $product->get_regular_price();
				$sale_price    = (float) $product->get_sale_price();

				if ($sale_price > 0 && $sale_price < $regular_price) {
					$price_value   = $sale_price;
					$price_html    = wc_format_sale_price($regular_price, $sale_price);
					$price_display = wp_strip_all_tags(wc_price($sale_price));
				} else {
					$active_price  = $regular_price > 0 ? $regular_price : (float) $product->get_price();
					$price_value   = $active_price;
					$price_html    = $active_price > 0 ? wc_price($active_price) : '';
					$price_display = $active_price > 0 ? wp_strip_all_tags(wc_price($active_price)) : '';
				}
			}
		} else {
			$base_price = (float) $product->get_regular_price();
			$tier_price = (float) $product->get_price();
			$suffix     = method_exists($product, 'get_price_suffix') ? $product->get_price_suffix() : '';

			if ($tier_price > 0 && $base_price > 0 && $tier_price < $base_price) {
				$price_value   = $tier_price;
				$price_html    = wc_format_sale_price($base_price, $tier_price) . $suffix;
				$price_display = wp_strip_all_tags(wc_price($tier_price) . $suffix);
			} else {
				$active_price  = $tier_price > 0 ? $tier_price : $base_price;
				$price_value   = $active_price;
				$price_html    = $active_price > 0 ? wc_price($active_price) . $suffix : '';
				$price_display = $active_price > 0 ? wp_strip_all_tags(wc_price($active_price) . $suffix) : '';
			}
		}
	}

	return array(
		'id'            => $product->get_id(),
		'name'          => $product->get_name(),
		'sku'           => $product->get_sku(),
		'image_url'     => $image_url ?: wc_placeholder_img_src('woocommerce_thumbnail'),
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
function fhs_configurator_get_sections($product_id)
{

	$product_id = absint($product_id);

	if (! $product_id || ! function_exists('get_field')) {
		return array();
	}

	// Read the ACF Group field once — all sub-fields are keys in this array.
	$group = get_field('configurator_options', $product_id);

	if (! is_array($group)) {
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

	foreach ($section_defs as $def) {

		// Read the Relationship value from the group array.
		$raw_products = isset($group[$def['products_field']]) && is_array($group[$def['products_field']])
			? $group[$def['products_field']]
			: array();

		if (empty($raw_products) || ! is_array($raw_products)) {
			continue; // No products assigned — skip this section entirely.
		}

		// Validate each product: absint, wc_get_product, is_type('simple').
		$valid_products = array();
		foreach ($raw_products as $raw) {
			// ACF Relationship can return WP_Post objects or post IDs.
			$pid = is_object($raw) ? absint($raw->ID) : absint($raw);

			if (! $pid) {
				continue;
			}

			$data = fhs_configurator_get_product_data($pid);

			if (empty($data)) {
				// fhs_configurator_get_product_data() already validates simple type.
				continue;
			}

			$valid_products[] = $data;
		}

		if (empty($valid_products)) {
			continue; // All products in this section were invalid — skip.
		}

		// Determine selection type.
		if (null !== $def['fixed_type']) {
			$selection_type = $def['fixed_type'];
		} else {
			$raw_type       = isset($group[$def['type_field']]) ? $group[$def['type_field']] : 'multiple';
			$selection_type = in_array($raw_type, array('single', 'multiple'), true)
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
function fhs_get_current_configurator_product()
{

	if (! is_product()) {
		return null;
	}

	global $product;

	if (! $product instanceof WC_Product) {
		$product = wc_get_product(get_the_ID());
	}

	if (! $product instanceof WC_Product || ! fhs_configurator_is_active($product)) {
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
function fhs_configurator_enqueue_assets()
{

	$asset_base = get_stylesheet_directory_uri() . '/woocommerce/single-product/add-to-cart/';

	if (! wp_style_is('fhs-configurator', 'enqueued')) {
		wp_enqueue_style(
			'fhs-configurator',
			$asset_base . 'configurator.css',
			array('woocommerce-general'),
			'3.1.0'
		);
	}

	if (! wp_script_is('fhs-configurator', 'enqueued')) {
		wp_enqueue_script(
			'fhs-configurator',
			$asset_base . 'configurator.js',
			array(),
			'3.1.0',
			true
		);
	}
}

function fhs_render_configurator()
{

	$product = fhs_get_current_configurator_product();

	if (! $product) {
		return;
	}

	$sections = fhs_configurator_get_sections($product->get_id());

	if (empty($sections)) {
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
function fhs_render_configurator_sidebar()
{

	$product = fhs_get_current_configurator_product();

	if (! $product) {
		return;
	}

	$sections = fhs_configurator_get_sections($product->get_id());

	if (empty($sections)) {
		return;
	}

	fhs_configurator_enqueue_assets();

	$current_url = get_permalink($product->get_id());
	$login_url   = add_query_arg('redirect_to', urlencode($current_url), site_url('/my-account'));
	$can_add_to_cart = is_user_logged_in() && $product->is_purchasable() && $product->is_in_stock();

	echo '<aside class="fhs-configurator-sidebar" aria-label="' . esc_attr__('Your Configuration', 'woocommerce') . '">';
	echo '	<div class="fhs-configurator__right">';
	echo '		<div class="fhs-configurator__summary-card">';
	echo '			<div class="fhs-configurator__summary-header">';
	echo '				<div class="fhs-configurator__summary-heading-group">';
	echo '					<h2 class="fhs-configurator__summary-title">' . esc_html__('Your Configuration', 'woocommerce') . '</h2>';
	echo '					<p class="fhs-configurator__summary-count" data-fhs-config-count>0 items</p>';
	echo '				</div>';
	echo '				<button type="button" class="fhs-configurator__clear-all" data-fhs-config-clear>' . esc_html__('Clear all', 'woocommerce') . '</button>';
	echo '			</div>';
	echo '			<div class="fhs-configurator__summary-body" data-fhs-config-body></div>';
	echo '			<div class="fhs-configurator__summary-footer">';
	echo '				<div class="fhs-configurator__summary-subtotal-row">';
	echo '					<span class="fhs-configurator__summary-subtotal-label">' . esc_html__('Subtotal', 'woocommerce') . '</span>';
	echo '					<span class="fhs-configurator__summary-subtotal-value" data-fhs-config-subtotal></span>';
	echo '				</div>';
	echo '				<p class="fhs-configurator__tax-note" data-fhs-config-tax-note style="display:none">' . esc_html__( 'GST & shipping calculated at cart.', 'woocommerce' ) . '</p>';
	echo '				<div class="fhs-configurator__summary-cart-actions">';
	if ($can_add_to_cart) {
		echo '					<button type="button" class="fhs-configurator__add-all-to-cart" data-fhs-config-add-all>' . esc_html__('Add All to Cart', 'woocommerce') . '</button>';
	} else {
		echo '					<div class="login-prompt fhs-configurator__login-prompt">';
		echo '						<span>' . esc_html__('Login or register to view prices.', 'woocommerce') . '</span>';
		echo '						<a href="' . esc_url($login_url) . '">';
		echo '							<i class="icofont icofont-ui-user"></i>';
		echo '							' . esc_html__('Login / Register to see pricing', 'woocommerce');
		echo '						</a>';
		echo '					</div>';
	}
	echo '					<p class="fhs-configurator__summary-message" data-fhs-config-message aria-live="polite"></p>';
	echo '				</div>';
	echo '			</div>';
	echo '		</div>';
	echo '	</div>';
	echo '</aside>';
}

/**
 * Return the raw configurator_options group for a validated configurator base product.
 *
 * @param int $base_product_id
 * @return array
 */
function fhs_configurator_get_options_group($base_product_id)
{

	$base_product_id = absint($base_product_id);
	$base_product    = wc_get_product($base_product_id);

	if (! $base_product || ! fhs_configurator_is_active($base_product) || ! function_exists('get_field')) {
		return array();
	}

	$group = get_field('configurator_options', $base_product_id);

	return is_array($group) ? $group : array();
}

/**
 * Validate and normalize a submitted configurator cart request.
 *
 * @param int   $base_product_id Base configurator product ID.
 * @param array $submitted_state Submitted committed configuration identifiers.
 * @return array|WP_Error
 */
function fhs_configurator_validate_committed_request($base_product_id, $submitted_state)
{

	$base_product_id = absint($base_product_id);
	$base_product    = wc_get_product($base_product_id);

	if (! $base_product || ! $base_product->is_type('simple') || ! fhs_configurator_is_active($base_product)) {
		return new WP_Error('invalid_base_product', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
	}

	if (! is_array($submitted_state)) {
		return new WP_Error('invalid_configuration', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
	}

	$group = fhs_configurator_get_options_group($base_product_id);
	if (empty($group)) {
		return new WP_Error('invalid_configuration', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
	}

	$definitions = array(
		'machine_packages' => array(
			'products_field' => 'machine_packages',
			'selection_type' => 'single',
		),
		'liner_sets' => array(
			'products_field' => 'liner_sets',
			'type_field'     => 'liner_sets_selection_type',
		),
		'replacement_parts' => array(
			'products_field' => 'replacement_parts',
			'type_field'     => 'replacement_parts_selection_type',
		),
		'accessories' => array(
			'products_field' => 'accessories',
			'type_field'     => 'accessories_selection_type',
		),
		'data_logging' => array(
			'products_field' => 'data_logging',
			'type_field'     => 'data_logging_selection_type',
		),
		'consumables' => array(
			'products_field' => 'consumables',
			'type_field'     => 'consumables_selection_type',
		),
		'tooling_extras' => array(
			'products_field' => 'tooling_extras',
			'type_field'     => 'tooling_extras_selection_type',
		),
	);

	$machine_source = isset($submitted_state['activeMachineSource']) ? sanitize_key($submitted_state['activeMachineSource']) : 'base_product';
	$machine_id     = isset($submitted_state['activeMachineProductId']) ? absint($submitted_state['activeMachineProductId']) : 0;
	$submitted_sections = isset($submitted_state['sections']) && is_array($submitted_state['sections'])
		? $submitted_state['sections']
		: array();

	$validated = array(
		'base_product_id'         => $base_product_id,
		'active_machine_source'   => 'base_product',
		'active_machine_product_id' => $base_product_id,
		'sections'                => array(),
		'product_ids'             => array(),
	);

	if ('machine_packages' === $machine_source) {
		$allowed_machine_ids = array();
		$raw_machine_ids     = isset($group['machine_packages']) && is_array($group['machine_packages']) ? $group['machine_packages'] : array();

		foreach ($raw_machine_ids as $raw_machine_id) {
			$allowed_machine_ids[] = is_object($raw_machine_id) ? absint($raw_machine_id->ID) : absint($raw_machine_id);
		}

		$allowed_machine_ids = array_values(array_filter(array_unique($allowed_machine_ids)));

		if (! $machine_id || ! in_array($machine_id, $allowed_machine_ids, true)) {
			return new WP_Error('invalid_machine_package', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
		}

		$machine_product = wc_get_product($machine_id);
		if (! $machine_product || ! $machine_product->is_type('simple')) {
			return new WP_Error('invalid_machine_package', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
		}

		$validated['active_machine_source']     = 'machine_packages';
		$validated['active_machine_product_id'] = $machine_id;
	} elseif ($machine_id && $machine_id !== $base_product_id) {
		return new WP_Error('invalid_machine_source', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
	}

	$validated['product_ids'][] = $validated['active_machine_product_id'];

	foreach ($definitions as $section_key => $definition) {
		$submitted_ids = isset($submitted_sections[$section_key]) && is_array($submitted_sections[$section_key])
			? array_values(array_unique(array_map('absint', $submitted_sections[$section_key])))
			: array();

		$submitted_ids = array_values(array_filter($submitted_ids));

		if ('machine_packages' === $section_key) {
			if (count($submitted_ids) > 1) {
				return new WP_Error('invalid_machine_count', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
			}

			if ('machine_packages' === $validated['active_machine_source']) {
				if (1 !== count($submitted_ids) || $submitted_ids[0] !== $validated['active_machine_product_id']) {
					return new WP_Error('invalid_machine_state', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
				}
			} elseif (! empty($submitted_ids)) {
				return new WP_Error('invalid_machine_state', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
			}

			$validated['sections'][$section_key] = $submitted_ids;
			continue;
		}

		$selection_type = 'multiple';
		if (isset($definition['type_field'])) {
			$raw_type = isset($group[$definition['type_field']]) ? $group[$definition['type_field']] : 'multiple';
			$selection_type = in_array($raw_type, array('single', 'multiple'), true) ? $raw_type : 'multiple';
		}

		if ('single' === $selection_type && count($submitted_ids) > 1) {
			return new WP_Error('invalid_selection_count', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
		}

		$raw_allowed_ids = isset($group[$definition['products_field']]) && is_array($group[$definition['products_field']])
			? $group[$definition['products_field']]
			: array();
		$allowed_ids = array();

		foreach ($raw_allowed_ids as $raw_allowed_id) {
			$allowed_ids[] = is_object($raw_allowed_id) ? absint($raw_allowed_id->ID) : absint($raw_allowed_id);
		}

		$allowed_ids = array_values(array_filter(array_unique($allowed_ids)));
		$validated_ids = array();

		foreach ($submitted_ids as $submitted_id) {
			if (! in_array($submitted_id, $allowed_ids, true)) {
				return new WP_Error('invalid_section_product', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
			}

			$submitted_product = wc_get_product($submitted_id);
			if (! $submitted_product || ! $submitted_product->is_type('simple')) {
				return new WP_Error('invalid_section_product', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
			}

			$validated_ids[] = $submitted_id;
		}

		$validated['sections'][$section_key] = $validated_ids;
		$validated['product_ids']             = array_merge($validated['product_ids'], $validated_ids);
	}

	$validated['product_ids'] = array_values(array_unique(array_filter(array_map('absint', $validated['product_ids']))));

	if (empty($validated['product_ids'])) {
		return new WP_Error('empty_configuration', __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'));
	}

	return $validated;
}

/**
 * AJAX: Add committed configurator products to cart.
 */
function fhs_configurator_add_all_to_cart()
{

	if (! check_ajax_referer('fhs_configurator_add_all_to_cart', 'nonce', false)) {
		wp_send_json_error(array(
			'message' => __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'),
		), 403);
	}

	$base_product_id = isset($_POST['base_product_id']) ? absint(wp_unslash($_POST['base_product_id'])) : 0;
	$configuration_json = isset($_POST['configuration']) ? wp_unslash($_POST['configuration']) : '';
	$configuration = json_decode($configuration_json, true);

	if (! $base_product_id || ! is_array($configuration)) {
		wp_send_json_error(array(
			'message' => __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'),
		), 400);
	}

	$validated = fhs_configurator_validate_committed_request($base_product_id, $configuration);
	if (is_wp_error($validated)) {
		wp_send_json_error(array(
			'message' => $validated->get_error_message(),
		), 400);
	}

	if (! function_exists('WC') || ! WC()->cart) {
		wp_send_json_error(array(
			'message' => __('Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce'),
		), 500);
	}

	// Ensure the WC session is initialized — required for is_purchasable() checks
	// to work correctly when called via AJAX with a valid session cookie.
	if ( WC()->session && ! WC()->session->has_session() ) {
		WC()->session->set_customer_session_cookie( true );
	}

	$cart_keys    = array();
	$added_names  = array();

	foreach ( $validated['product_ids'] as $product_id ) {
		$p = wc_get_product( $product_id );

		if ( ! $p || ! $p->is_purchasable() ) {
			wp_send_json_error( array(
				'message' => __( 'Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce' ),
			), 400 );
		}

		$cart_key = WC()->cart->add_to_cart( $product_id, 1 );
		if ( ! $cart_key ) {
			wp_send_json_error( array(
				'message' => __( 'Unable to add this configuration to the cart. Please refresh the page and try again.', 'woocommerce' ),
			), 500 );
		}
		$cart_keys[] = $cart_key;

		$added_names[] = $p->get_name();
	}

	// Build a WooCommerce-style notice matching the native add-to-cart format.
	$cart_url = wc_get_cart_url();
	if ( count( $added_names ) === 1 ) {
		/* translators: %s: product name */
		$notice_text = sprintf(
			__( '&ldquo;%s&rdquo; has been added to your cart.', 'woocommerce' ),
			esc_html( $added_names[0] )
		);
	} else {
		$notice_text = __( 'All products have been added to your cart.', 'woocommerce' );
	}

	$notices_html =
		'<div class="woocommerce-notices-wrapper">' .
		'<div class="woocommerce-message" role="alert" tabindex="-1">' .
		$notice_text .
		' <a href="' . esc_url( $cart_url ) . '" class="button wc-forward">' .
		esc_html__( 'View cart', 'woocommerce' ) .
		'</a>' .
		'</div></div>';

	wp_send_json_success( array(
		'notices_html' => $notices_html,
		'cart_url'     => $cart_url,
		'cart_count'   => WC()->cart->get_cart_contents_count(),
	) );
}

add_action('wp_ajax_fhs_configurator_add_all_to_cart', 'fhs_configurator_add_all_to_cart');
add_action('wp_ajax_nopriv_fhs_configurator_add_all_to_cart', 'fhs_configurator_add_all_to_cart');

/**
 * AJAX: Return a fresh nonce for the configurator cart action.
 * Called by JS when the existing nonce has expired (after ~12 hours).
 */
function fhs_configurator_refresh_nonce() {
	wp_send_json_success( array(
		'nonce' => wp_create_nonce( 'fhs_configurator_add_all_to_cart' ),
	) );
}
add_action( 'wp_ajax_fhs_configurator_refresh_nonce',        'fhs_configurator_refresh_nonce' );
add_action( 'wp_ajax_nopriv_fhs_configurator_refresh_nonce', 'fhs_configurator_refresh_nonce' );

add_action('fhs_inside_product_main_container', 'fhs_render_configurator');
add_action('fhs_configurator_sidebar_area', 'fhs_render_configurator_sidebar');