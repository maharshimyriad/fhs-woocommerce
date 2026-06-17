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
 * Get Level A pricing data for a product
 *
 * @param int|WC_Product $product Product ID or WC_Product object
 * @return array {
 *     @type string $regular_price Level A regular price
 *     @type string $pricing_type Type of pricing (fixed, percentage, tiered)
 *     @type string $discount_type Discount type if applicable
 *     @type array $rules Fixed or percentage rules
 * }
 */
function fhs_get_level_a_pricing( $product ) {
	if ( is_int( $product ) ) {
		$product = wc_get_product( $product );
	}

	if ( ! $product ) {
		return array();
	}

	$level_a_data = array(
		'regular_price'   => '',
		'pricing_type'    => '',
		'discount_type'   => '',
		'fixed_rules'     => array(),
		'percentage_rules' => array(),
	);

	// Get Level A regular price
	$regular_price = get_post_meta( $product->get_id(), '_LevelA_tiered_price_regular_price', true );
	if ( $regular_price ) {
		$level_a_data['regular_price'] = wc_price( $regular_price );
	}

	// Get pricing type (fixed, percentage, tiered)
	$pricing_type = get_post_meta( $product->get_id(), '_LevelA_tiered_price_pricing_type', true );
	$level_a_data['pricing_type'] = $pricing_type ? ucfirst( $pricing_type ) : 'Standard';

	// Get discount type
	$discount_type = get_post_meta( $product->get_id(), '_LevelA_tiered_price_discount_type', true );
	$level_a_data['discount_type'] = $discount_type ? ucfirst( $discount_type ) : '';

	// Get fixed price rules (if any)
	$fixed_rules = get_post_meta( $product->get_id(), '_LevelA_fixed_price_rules', true );
	if ( $fixed_rules && is_array( $fixed_rules ) ) {
		$level_a_data['fixed_rules'] = $fixed_rules;
	}

	// Get percentage rules (if any)
	$percentage_rules = get_post_meta( $product->get_id(), '_LevelA_percentage_price_rules', true );
	if ( $percentage_rules && is_array( $percentage_rules ) ) {
		$level_a_data['percentage_rules'] = $percentage_rules;
	}

	return $level_a_data;
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
	$product     = wc_get_product( $post->ID );
	$level_a_data = fhs_get_level_a_pricing( $product );

	?>
	<div class="fhs-level-a-pricing-box">
		<style>
			.fhs-level-a-pricing-box {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
			.fhs-pricing-rules {
				margin-top: 15px;
				padding: 10px;
				border: 1px solid #e0e0e0;
				border-radius: 4px;
				background: #fafafa;
			}
			.fhs-pricing-rules h4 {
				margin: 0 0 10px 0;
				font-size: 13px;
				color: #333;
				text-transform: uppercase;
			}
			.fhs-rule-item {
				padding: 8px;
				margin: 5px 0;
				background: #fff;
				border-left: 3px solid #0073aa;
				font-size: 12px;
			}
		</style>

		<?php if ( ! empty( $level_a_data['regular_price'] ) ) : ?>
			<div class="fhs-pricing-item">
				<label><?php esc_html_e( 'Regular Price', 'woocommerce' ); ?></label>
				<div class="value"><?php echo $level_a_data['regular_price']; ?></div>
			</div>
		<?php endif; ?>

		<div class="fhs-pricing-item">
			<label><?php esc_html_e( 'Pricing Type', 'woocommerce' ); ?></label>
			<div class="value"><?php echo esc_html( $level_a_data['pricing_type'] ); ?></div>
		</div>

		<?php if ( ! empty( $level_a_data['discount_type'] ) ) : ?>
			<div class="fhs-pricing-item">
				<label><?php esc_html_e( 'Discount Type', 'woocommerce' ); ?></label>
				<div class="value"><?php echo esc_html( $level_a_data['discount_type'] ); ?></div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $level_a_data['fixed_rules'] ) ) : ?>
			<div class="fhs-pricing-rules">
				<h4><?php esc_html_e( 'Fixed Price Rules', 'woocommerce' ); ?></h4>
				<?php foreach ( $level_a_data['fixed_rules'] as $rule ) : ?>
					<div class="fhs-rule-item">
						<?php
						if ( isset( $rule['min'], $rule['max'], $rule['price'] ) ) {
							printf(
								esc_html__( 'Qty %s - %s: %s', 'woocommerce' ),
								esc_html( $rule['min'] ),
								esc_html( $rule['max'] ? $rule['max'] : '∞' ),
								wc_price( $rule['price'] )
							);
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $level_a_data['percentage_rules'] ) ) : ?>
			<div class="fhs-pricing-rules">
				<h4><?php esc_html_e( 'Percentage Discount Rules', 'woocommerce' ); ?></h4>
				<?php foreach ( $level_a_data['percentage_rules'] as $rule ) : ?>
					<div class="fhs-rule-item">
						<?php
						if ( isset( $rule['min'], $rule['max'], $rule['price'] ) ) {
							printf(
								esc_html__( 'Qty %s - %s: %s%%', 'woocommerce' ),
								esc_html( $rule['min'] ),
								esc_html( $rule['max'] ? $rule['max'] : '∞' ),
								esc_html( $rule['price'] )
							);
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( empty( $level_a_data['regular_price'] ) && empty( $level_a_data['fixed_rules'] ) && empty( $level_a_data['percentage_rules'] ) ) : ?>
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
