<?php
/**
 * Checkout Payment Section
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>

<div id="payment" class="woocommerce-checkout-payment">

	<h3><?php esc_html_e( 'Payment Information', 'woocommerce' ); ?></h3>

	<?php if ( WC()->cart->needs_payment() ) : ?>
		<ul class="wc_payment_methods payment_methods methods">
			<?php if ( ! empty( $available_gateways ) ) :
				foreach ( $available_gateways as $gateway ) :
					wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
				endforeach;
			else : ?>
				<li>
					<?php wc_print_notice(
						apply_filters(
							'woocommerce_no_available_payment_methods_message',
							WC()->customer->get_billing_country()
								? esc_html__( 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' )
								: esc_html__( 'Please fill in your details above to see available payment methods.', 'woocommerce' )
						),
						'notice'
					); ?>
				</li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>

	<?php do_action( 'fhs_checkout_after_payment_methods' ); ?>

	<div class="checkout-payment-footer">
		<noscript>
			<p><?php printf(
				/* translators: %1$s opening em, %2$s closing em */
				esc_html__( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ),
				'<em>', '</em>'
			); ?></p>
			<button type="submit"
				class="button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
				name="woocommerce_checkout_update_totals"
				value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>">
				<?php esc_html_e( 'Update totals', 'woocommerce' ); ?>
			</button>
		</noscript>

		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>
		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>

</div>

<?php
if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_after_payment' );
}
