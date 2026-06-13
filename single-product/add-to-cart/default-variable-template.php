<?php
/**
 * Default variable product template
 *
 * Standard WooCommerce variation dropdowns / table UI.
 * Loaded by variable.php when `category_variation_template` ACF field is false
 * (or not set) on all assigned categories and their parents.
 *
 * Available variables (passed from variable.php via wc_get_template):
 *   $available_variations  — array of variation data arrays
 *   $attributes            — array of attribute name => options
 *   $selected_attributes   — array of pre-selected attribute values
 *
 * @package WooCommerce\Templates
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' )
	? wc_esc_json( $variations_json )
	: _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' );
?>

<form class="variations_form cart"
	action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
	method="post"
	enctype="multipart/form-data"
	data-product_id="<?php echo absint( $product->get_id() ); ?>"
	data-product_variations="<?php echo $variations_attr; ?>">

	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>

		<p class="stock out-of-stock">
			<?php esc_html_e( 'This product is currently out of stock and unavailable.', 'woocommerce' ); ?>
		</p>

	<?php else : ?>

		<table class="variations" cellspacing="0" role="presentation">
			<tbody>
				<?php foreach ( $attributes as $attribute_name => $options ) : ?>
					<tr>
						<th class="label">
							<label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>">
								<?php echo wc_attribute_label( $attribute_name ); ?>
							</label>
						</th>
						<td class="value">
							<?php
							wc_dropdown_variation_attribute_options(
								array(
									'options'   => $options,
									'attribute' => $attribute_name,
									'product'   => $product,
								)
							);

							echo end( $attribute_keys ) === $attribute_name
								? wp_kses_post(
									apply_filters(
										'woocommerce_reset_variations_link',
										'<a class="reset_variations" href="#" aria-label="' . esc_attr__( 'Clear options', 'woocommerce' ) . '">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>'
									)
								)
								: '';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="reset_variations_alert screen-reader-text" role="alert"></div>

		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">

			<?php do_action( 'woocommerce_before_single_variation' ); ?>

			<?php if ( ! is_user_logged_in() ) : ?>
				<div class="login-prompt">
					<span><?php esc_html_e( 'Login or register to view prices.', 'woocommerce' ); ?></span>
					<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
						<i class="icofont icofont-ui-user"></i>
						<?php esc_html_e( 'Login / Register to see pricing', 'woocommerce' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php do_action( 'woocommerce_single_variation' ); ?>

			<?php do_action( 'woocommerce_after_single_variation' ); ?>

		</div>

	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>

</form>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
