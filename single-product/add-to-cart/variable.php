<?php
/**
 * Variable product add to cart — router
 *
 * Checks whether the product belongs to a category (or parent category) that
 * has the ACF field `category_variation_template` set to true.
 *
 *   true  → loads variation-card-grid-template.php  (card-per-variation layout)
 *   false → loads default-variable-template.php     (standard WooCommerce dropdowns)
 *
 * @package WooCommerce\Templates
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// ─── ACF category check ───────────────────────────────────────────────────────
// Walk every assigned category and its full parent chain.
// Returns the first WP_Term whose `category_variation_template` ACF field is true,
// or false if none is found.
$use_card_grid = false;

if ( function_exists( 'get_field' ) ) {

	$term_ids       = $product->get_category_ids();
	$terms_to_check = array();

	foreach ( $term_ids as $term_id ) {
		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			continue;
			
		}
		$terms_to_check[] = $term;

		// Walk up the parent chain.
		$parent_id = $term->parent;
		while ( $parent_id ) {
			$parent = get_term( $parent_id, 'product_cat' );
			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}
			$terms_to_check[] = $parent;
			$parent_id        = $parent->parent;
		}
	}

	// De-duplicate — direct categories keep priority over parents.
	$seen         = array();
	$unique_terms = array();
	foreach ( $terms_to_check as $t ) {
		if ( ! in_array( $t->term_id, $seen, true ) ) {
			$seen[]         = $t->term_id;
			$unique_terms[] = $t;
		}
	}

	// Check ACF field on each term — stop at the first match.
	foreach ( $unique_terms as $t ) {
		if ( get_field( 'category_variation_template', 'product_cat_' . $t->term_id ) ) {
			$use_card_grid = true;
			break;
		}
	}
}

// ─── Route to the correct template ───────────────────────────────────────────
if ( $use_card_grid ) {
	wc_get_template(
		'single-product/add-to-cart/variation-card-grid-template.php',
		array(
			'available_variations' => $available_variations,
			'attributes'           => $attributes,
			'selected_attributes'  => $selected_attributes,
		)
	);
} else {
	wc_get_template(
		'single-product/add-to-cart/default-variable-template.php',
		array(
			'available_variations' => $available_variations,
			'attributes'           => $attributes,
			'selected_attributes'  => $selected_attributes,
		)
	);
}
