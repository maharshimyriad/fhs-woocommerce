<?php
/**
 * Checkout field customizations.
 *
 * @package FHS\Checkout
 */

namespace FHS\Checkout;

defined( 'ABSPATH' ) || exit;

class Checkout_Fields {
	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_checkout_fields' ), 10, 1 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_fulfilment_aware_checkout_fields' ), 999 );
		add_filter( 'gettext', array( $this, 'filter_checkout_translations' ), 1000, 3 );
		add_filter( 'woocommerce_get_country_locale_default', array( $this, 'filter_country_locale_default' ) );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'filter_default_address_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_checkout_order_meta' ) );
		add_action( 'fhs_checkout_after_payment_methods', array( $this, 'render_payment_extra_fields' ) );
	}

	/**
	 * Base checkout field customizations.
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function filter_checkout_fields( $fields ) {
		$fields['billing']['billing_first_name']['priority'] = 10;
		$fields['billing']['billing_last_name']['priority']  = 20;
		$fields['billing']['billing_phone']['priority']      = 30;
		$fields['billing']['billing_email']['priority']      = 40;
		$fields['billing']['billing_address_1']['priority']  = 60;
		$fields['billing']['billing_address_2']['priority']  = 65;
		$fields['billing']['billing_city']['priority']       = 70;
		$fields['billing']['billing_postcode']['priority']   = 80;
		$fields['billing']['billing_country']['priority']    = 90;

		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['required']                     = true;
			$fields['billing']['billing_phone']['label']                        = __( 'Phone Number', 'woocommerce' );
			$fields['billing']['billing_phone']['custom_attributes']['required'] = 'required';
			$fields['billing']['billing_phone']['validate']                     = array_values( array_unique( array_merge(
				isset( $fields['billing']['billing_phone']['validate'] ) && is_array( $fields['billing']['billing_phone']['validate'] )
					? $fields['billing']['billing_phone']['validate']
					: array(),
				array( 'required' )
			) ) );
		}

		if ( isset( $fields['billing']['billing_address_1'] ) ) {
			$fields['billing']['billing_address_1']['label'] = __( 'Address Line 1', 'woocommerce' );
		}

		if ( isset( $fields['billing']['billing_address_2'] ) ) {
			$fields['billing']['billing_address_2']['label'] = __( 'Address Line 2', 'woocommerce' );
		}

		if ( ! empty( $fields['billing'] ) && is_array( $fields['billing'] ) ) {
			foreach ( $fields['billing'] as $billing_key => $billing_field ) {
				if ( empty( $fields['billing'][ $billing_key ]['class'] ) || ! is_array( $fields['billing'][ $billing_key ]['class'] ) ) {
					continue;
				}
				$fields['billing'][ $billing_key ]['class'] = array_values( array_filter(
					$fields['billing'][ $billing_key ]['class'],
					function ( $class_name ) {
						return 'update_totals_on_change' !== $class_name;
					}
				) );
			}
		}

		unset( $fields['billing']['thwma_hidden_field_billing'] );
		unset( $fields['billing']['thwma_checkbox_shipping'] );
		unset( $fields['billing']['billing_po_number'] );
		unset( $fields['billing']['billing_required_date'] );

		return $fields;
	}

	/**
	 * Fulfilment-aware checkout field customizations.
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function filter_fulfilment_aware_checkout_fields( $fields ) {
		$mode = Checkout::get_fulfilment_method();

		unset( $fields['billing']['thwma_hidden_field_billing'] );
		unset( $fields['billing']['thwma_checkbox_shipping'] );

		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['required']                     = true;
			$fields['billing']['billing_phone']['custom_attributes']['required'] = 'required';
			$fields['billing']['billing_phone']['validate']                     = array_values( array_unique( array_merge(
				isset( $fields['billing']['billing_phone']['validate'] ) && is_array( $fields['billing']['billing_phone']['validate'] )
					? $fields['billing']['billing_phone']['validate']
					: array(),
				array( 'required' )
			) ) );
		}

		if ( ! empty( $fields['billing'] ) && is_array( $fields['billing'] ) ) {
			foreach ( $fields['billing'] as $billing_key => $billing_field ) {
				if ( empty( $fields['billing'][ $billing_key ]['class'] ) || ! is_array( $fields['billing'][ $billing_key ]['class'] ) ) {
					continue;
				}
				$fields['billing'][ $billing_key ]['class'] = array_values( array_filter(
					$fields['billing'][ $billing_key ]['class'],
					function ( $class_name ) {
						return 'update_totals_on_change' !== $class_name;
					}
				) );
			}
		}

		if ( 'pickup' !== $mode || empty( $fields['shipping'] ) || ! is_array( $fields['shipping'] ) ) {
			return $fields;
		}

		foreach ( $fields['shipping'] as $field_key => $field_config ) {
			$fields['shipping'][ $field_key ]['required'] = false;
			if ( isset( $fields['shipping'][ $field_key ]['custom_attributes']['required'] ) ) {
				unset( $fields['shipping'][ $field_key ]['custom_attributes']['required'] );
			}

			if ( isset( $fields['shipping'][ $field_key ]['validate'] ) && is_array( $fields['shipping'][ $field_key ]['validate'] ) ) {
				$fields['shipping'][ $field_key ]['validate'] = array_values( array_filter(
					$fields['shipping'][ $field_key ]['validate'],
					function ( $rule ) {
						return 'required' !== $rule;
					}
				) );
			}
		}

		return $fields;
	}

	/**
	 * Preserve current label overrides.
	 *
	 * @param string $translated_text Translated text.
	 * @param string $text Source text.
	 * @param string $domain Domain.
	 * @return string
	 */
	public function filter_checkout_translations( $translated_text, $text, $domain ) {
		if ( 'woocommerce' === $domain ) {
			if ( 'PIN Code' === $text || 'PIN Code' === $translated_text ) {
				$translated_text = 'Postcode';
			}
			if ( 'Phone' === $text || 'Phone' === $translated_text ) {
				$translated_text = 'Phone Number';
			}
			if ( 'Town / City' === $text || 'Town / City' === $translated_text ) {
				$translated_text = 'Suburb / City';
			}
			if ( 'Street address' === $text || 'Street address' === $translated_text ) {
				$translated_text = 'Address Line 1';
			}
		}

		return $translated_text;
	}

	/**
	 * Adjust WooCommerce country locale defaults.
	 *
	 * @param array $locale Locale config.
	 * @return array
	 */
	public function filter_country_locale_default( $locale ) {
		if ( isset( $locale['address_1'] ) ) {
			$locale['address_1']['label']       = 'Address Line 1';
			$locale['address_1']['placeholder'] = 'Address Line 1';
			$locale['address_1']['required']    = true;
		}

		if ( isset( $locale['address_2'] ) ) {
			$locale['address_2']['label']       = 'Address Line 2';
			$locale['address_2']['placeholder'] = 'Address Line 2';
			$locale['address_2']['required']    = false;
			$locale['address_2']['label_class'] = array();
		}

		return $locale;
	}

	/**
	 * Adjust default address fields.
	 *
	 * @param array $fields Address fields.
	 * @return array
	 */
	public function filter_default_address_fields( $fields ) {
		if ( isset( $fields['address_1'] ) ) {
			$fields['address_1']['label']       = 'Address Line 1';
			$fields['address_1']['placeholder'] = 'Address Line 1';
			$fields['address_1']['required']    = true;
		}

		if ( isset( $fields['address_2'] ) ) {
			$fields['address_2']['label']       = 'Address Line 2';
			$fields['address_2']['placeholder'] = 'Address Line 2';
			$fields['address_2']['required']    = false;
			$fields['address_2']['label_class'] = array();
		}

		return $fields;
	}

	/**
	 * Save checkout-owned order metadata.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function save_checkout_order_meta( $order_id ) {
		if ( ! empty( $_POST['billing_po_number'] ) ) {
			update_post_meta( $order_id, '_product_order_number', sanitize_text_field( wp_unslash( $_POST['billing_po_number'] ) ) );
		}

		if ( ! empty( $_POST['fhs_required_date'] ) ) {
			update_post_meta( $order_id, '__order_required_date', sanitize_text_field( wp_unslash( $_POST['fhs_required_date'] ) ) );
		}
	}

	/**
	 * Render checkout payment-adjacent extra fields.
	 *
	 * @return void
	 */
	public function render_payment_extra_fields() {
		$checkout = WC()->checkout();
		echo '<div class="payment-extra-fields-grid">';
		echo '<div id="payment-required-date-wrap" class="payment-extra-field">';
		woocommerce_form_field(
			'fhs_required_date',
			array(
				'type'              => 'date',
				'class'             => array( 'form-row-wide' ),
				'label'             => 'Required Date',
				'required'          => false,
				'custom_attributes' => array(
					'min' => current_time( 'Y-m-d' ),
				),
			),
			$checkout->get_value( 'fhs_required_date' )
		);
		echo '</div>';
		echo '<div id="pay-later-po-number-wrap" class="pay-later-extra">';
		woocommerce_form_field(
			'billing_po_number',
			array(
				'type'     => 'text',
				'class'    => array( 'form-row-wide' ),
				'label'    => 'Purchase Order Number',
				'required' => false,
			),
			$checkout->get_value( 'billing_po_number' )
		);
		echo '</div>';
		echo '</div>';
	}
}
