<?php
/**
 * Checkout validation rules.
 *
 * @package FHS\Checkout
 */

namespace FHS\Checkout;

defined( 'ABSPATH' ) || exit;

class Checkout_Validation {
	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_pay_later_requirements' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_fulfilment_and_shipping' ) );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_billing_phone' ), 10, 2 );
	}

	/**
	 * Validate pay-later checkout requirements.
	 *
	 * @return void
	 */
	public function validate_pay_later_requirements() {
		$payment_method = sanitize_text_field( (string) ( $_POST['payment_method'] ?? '' ) );

		if ( 'pay_later' === $payment_method || false !== strpos( $payment_method, 'pay_later' ) ) {
			if ( empty( trim( (string) ( $_POST['billing_po_number'] ?? '' ) ) ) ) {
				wc_add_notice( 'PO number is required for On Account payments.', 'error' );
			}

			if ( empty( $_POST['pay_later_po_file_url'] ) ) {
				wc_add_notice( 'PO file is required for On Account payments.', 'error' );
			}
		}
	}

	/**
	 * Validate billing phone format.
	 *
	 * @param array            $data Checkout data.
	 * @param \WP_Error|object $errors Validation errors.
	 * @return void
	 */
	public function validate_billing_phone( $data, $errors ) {
		if ( ! empty( $data['billing_phone'] ) ) {
			$phone_raw = $data['billing_phone'];

			if ( ! preg_match( '/^[0-9]+$/', $phone_raw ) ) {
				$errors->add(
					'billing_phone_error',
					__( 'Billing Phone must contain digits only (no + or special characters).', 'woocommerce' )
				);
				return;
			}

			if ( strlen( $phone_raw ) > 12 ) {
				$errors->add(
					'billing_phone_error',
					__( 'Billing Phone must be less than 12 digits.', 'woocommerce' )
				);
			}
		}
	}

	/**
	 * Validate fulfilment mode and shipping requirements.
	 *
	 * @return void
	 */
	public function validate_fulfilment_and_shipping() {
		$mode = Checkout::get_fulfilment_method();

		if ( ! in_array( $mode, array( 'delivery', 'pickup' ), true ) ) {
			wc_add_notice( 'Please choose Delivery or Pick up before placing your order.', 'error' );
			return;
		}

		if ( 'pickup' === $mode ) {
			return;
		}

		$packages = WC()->shipping()->get_packages();

		foreach ( $packages as $package ) {
			if ( empty( $package['rates'] ) ) {
				wc_add_notice( 'Shipping is required. Please select a shipping method.', 'error' );
				return;
			}
		}

		if ( WC()->cart->needs_shipping() ) {
			$required = array(
				'shipping_first_name',
				'shipping_last_name',
				'shipping_address_1',
				'shipping_city',
				'shipping_postcode',
				'shipping_country',
			);

			foreach ( $required as $field ) {
				if ( empty( trim( (string) ( $_POST[ $field ] ?? '' ) ) ) ) {
					wc_add_notice( 'Please enter a valid shipping address.', 'error' );
					break;
				}
			}
		}
	}
}
