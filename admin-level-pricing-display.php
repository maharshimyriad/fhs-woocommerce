<?php
/**
 * Display Level A Tier Pricing in WooCommerce Admin Product Editor
 *
 * This file adds a custom metabox to the product editor showing Level A pricing
 * data from Tier Pricing Table Premium plugin.
 *
 * @package FHS_WOO
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Level A regular price
 *
 * @param int|WC_Product $product Product ID or WC_Product object
 * @return float|string Level A regular price or empty string
 */
function fhs_get_level_a_regular_price( $product ) {
	if ( is_int( $product ) ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product ) {
		return '';
	}

	return get_post_meta( $product->get_id(), '_LevelA_tiered_price_regular_price', true );
}

/**
 * Get Level A sale price (minimum from fixed rules or percentage discount)
 *
 * @param int|WC_Product $product Product ID or WC_Product object
 * @return float|string Level A sale price or empty string
 */
function fhs_get_level_a_sale_price( $product ) {
	if ( is_int( $product ) ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product ) {
		return '';
	}

	$product_id = $product->get_id();
	$regular_price = get_post_meta( $product_id, '_LevelA_tiered_price_regular_price', true );

	if ( ! $regular_price ) {
		return '';
	}

	// Check fixed price rules (use minimum)
	$fixed_rules = get_post_meta( $product_id, '_LevelA_fixed_price_rules', true );
	if ( $fixed_rules && is_array( $fixed_rules ) && count( $fixed_rules ) > 0 ) {
		$min_price = min( array_column( $fixed_rules, 'price' ) );
		if ( $min_price && $min_price < (float) $regular_price ) {
			return $min_price;
		}
	}

	// Check percentage rules (calculate discount from first rule)
	$percentage_rules = get_post_meta( $product_id, '_LevelA_percentage_price_rules', true );
	if ( $percentage_rules && is_array( $percentage_rules ) && count( $percentage_rules ) > 0 ) {
		$first_rule = $percentage_rules[0];
		if ( isset( $first_rule['price'] ) ) {
			$discount_percent = (float) $first_rule['price'];
			$sale_price = (float) $regular_price * ( 1 - ( $discount_percent / 100 ) );
			return $sale_price;
		}
	}

	return '';
}

/**
 * Get Level A pricing data for a product
 *
 * @param int|WC_Product $product Product ID or WC_Product object
 * @return array {
 *     @type string $regular_price Level A regular price
 *     @type string $sale_price Level A sale price (if applicable)
 *     @type string $pricing_type Type of pricing (fixed, percentage, tiered)
 * }
 */
function fhs_get_level_a_pricing( $product ) {
	if ( is_int( $product ) ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product ) {
		return array();
	}

	$regular_price = fhs_get_level_a_regular_price( $product );
	$sale_price = fhs_get_level_a_sale_price( $product );

	return array(
		'regular_price' => $regular_price ? wc_price( $regular_price ) : '',
		'sale_price'    => $sale_price ? wc_price( $sale_price ) : '',
		'raw_regular'   => $regular_price,
		'raw_sale'      => $sale_price,
	);
}

/**
 * Add Level A Pricing metabox to product editor
 */
function fhs_add_level_a_pricing_metabox() {
	add_meta_box(
		'fhs_level_a_pricing',
		'Level A Tier Pricing',
		'fhs_render_level_a_pricing_metabox',
		'product',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'fhs_add_level_a_pricing_metabox' );

/**
 * Render the Level A Pricing metabox
 *
 * @param WP_Post $post The post object (product)
 */
function fhs_render_level_a_pricing_metabox( $post ) {
	$product      = wc_get_product( $post->ID );
	$level_a_data = fhs_get_level_a_pricing( $product );

	?>
	<div class="fhs-level-a-pricing-box">
		<style>
			.fhs-level-a-pricing-box {
				display: grid;
				grid-template-columns: 1fr 1fr;
				gap: 15px;
				padding: 10px;
			}
			.fhs-pricing-item {
				padding: 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				background: #f9f9f9;
			}
			.fhs-pricing-item label {
				display: block;
				font-weight: 600;
				color: #555;
				margin-bottom: 5px;
				font-size: 12px;
				text-transform: uppercase;
			}
			.fhs-pricing-item .value {
				font-size: 16px;
				font-weight: 700;
				color: #0073aa;
			}
		</style>

		<?php if ( ! empty( $level_a_data['regular_price'] ) ) : ?>
			<div class="fhs-pricing-item">
				<label><?php esc_html_e( 'Regular Price', 'woocommerce' ); ?></label>
				<div class="value"><?php echo $level_a_data['regular_price']; ?></div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $level_a_data['sale_price'] ) ) : ?>
			<div class="fhs-pricing-item">
				<label><?php esc_html_e( 'Sale Price', 'woocommerce' ); ?></label>
				<div class="value" style="color: #a02020;"><?php echo $level_a_data['sale_price']; ?></div>
			</div>
		<?php endif; ?>

		<?php if ( empty( $level_a_data['regular_price'] ) && empty( $level_a_data['sale_price'] ) ) : ?>
			<p style="color: #999; font-size: 13px; margin: 0;">
				<?php esc_html_e( 'No Level A pricing configured for this product.', 'woocommerce' ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Add Level A Pricing column to product list admin table
 *
 * @param array $columns The product list columns
 * @return array Modified columns
 */
function fhs_add_level_a_column( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		if ( 'price' === $key ) {
			$new_columns['level_a_price'] = __( 'Level A Price', 'woocommerce' );
		}
	}

	return $new_columns;
}
add_filter( 'manage_product_posts_columns', 'fhs_add_level_a_column' );

/**
 * Display Level A Pricing in product list column
 *
 * @param string $column The column name
 * @param int    $post_id The product post ID
 */
function fhs_display_level_a_column( $column, $post_id ) {
	if ( 'level_a_price' === $column ) {
		$product       = wc_get_product( $post_id );
		$regular_price = get_post_meta( $post_id, '_LevelA_tiered_price_regular_price', true );

		if ( $regular_price ) {
			echo wp_kses_post( wc_price( $regular_price ) );
		} else {
			echo '<span style="color: #999;">—</span>';
		}
	}
}
add_action( 'manage_product_posts_custom_column', 'fhs_display_level_a_column', 10, 2 );

/**
 * Make Level A Price column sortable
 *
 * @param array $columns The sortable columns
 * @return array Modified columns
 */
function fhs_level_a_sortable_columns( $columns ) {
	$columns['level_a_price'] = 'level_a_price';
	return $columns;
}
add_filter( 'manage_edit-product_sortable_columns', 'fhs_level_a_sortable_columns' );
