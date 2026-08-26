<?php
/**
 * Payment rules and notices.
 *
 * @package FHS\Checkout
 */

namespace FHS\Checkout;

defined( 'ABSPATH' ) || exit;

class Payment_Rules {
	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_available_payment_gateways' ), 20 );
		add_action( 'woocommerce_before_thankyou', array( $this, 'show_pending_payment_error' ) );
	}

	/**
	 * Restrict pay-later gateway visibility based on current customer MYOB terms.
	 *
	 * @param array $available_gateways Available gateways.
	 * @return array
	 */
	public function filter_available_payment_gateways( $available_gateways ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return $available_gateways;
		}

		if ( ! is_user_logged_in() ) {
			return $available_gateways;
		}

		$user_id       = get_current_user_id();
		$payment_terms = strtolower( trim( (string) get_user_meta( $user_id, 'myob_payment_terms', true ) ) );

		if ( in_array( $payment_terms, array( 'cod', 'prepaid' ), true ) ) {
			unset( $available_gateways['pay_later'] );
		}

		return $available_gateways;
	}

	/**
	 * Preserve pending payment notice on the thank-you page.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function show_pending_payment_error( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( $order->has_status( 'pending' ) ) {
			wc_print_notice(
				__( 'There\'s an issue with processing your payment and it\'s still pending. Please click on "Back to Orders" to try again.', 'woocommerce' ),
				'error'
			);
			remove_all_filters( 'woocommerce_thankyou_order_received_text' );
		}
	}
}
