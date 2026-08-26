<?php
/**
 * Checkout plugin bootstrap and checkout state/session logic.
 *
 * @package FHS\Checkout
 */

namespace FHS\Checkout;

defined( 'ABSPATH' ) || exit;

class Plugin {
	/**
	 * Plugin version.
	 */
	const VERSION = '1.0.0';

	/**
	 * Boot services only when WooCommerce is active.
	 *
	 * @return void
	 */
	public static function boot() {
		if ( ! self::is_woocommerce_active() ) {
			return;
		}

		new Checkout();
		new Checkout_Fields();
		new Checkout_Validation();
		new Payment_Rules();
		new Po_Upload();
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( ! self::is_woocommerce_active() ) {
			return;
		}

		flush_rewrite_rules();
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Check whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' ) && function_exists( 'WC' );
	}
}

class Checkout {
	/**
	 * Register hooks.
	 */
	public function __construct() {
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
		add_action( 'woocommerce_custom_payment_relocation', 'woocommerce_checkout_payment', 20 );
		add_filter( 'woocommerce_checkout_get_value', array( $this, 'filter_checkout_value' ), 999, 2 );
		add_action( 'wp', array( $this, 'set_default_fulfilment_mode' ), 20 );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'sync_fulfilment_mode' ), 20 );
		add_filter( 'woocommerce_cart_needs_shipping_address', array( $this, 'filter_needs_shipping_address' ), 20 );
		add_filter( 'woocommerce_cart_needs_shipping', array( $this, 'filter_needs_shipping' ), 20 );
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'filter_shipping_packages' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue checkout assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}

		$base_url = plugin_dir_url( dirname( __FILE__ ) );

		wp_enqueue_style(
			'fhs-checkout-po-upload',
			$base_url . 'assets/css/checkout-po-upload.css',
			array(),
			Plugin::VERSION
		);

		wp_enqueue_script(
			'fhs-checkout-po-upload',
			$base_url . 'assets/js/checkout-po-upload.js',
			array( 'jquery' ),
			Plugin::VERSION,
			true
		);

		wp_enqueue_script(
			'fhs-checkout-pay-later-fields',
			$base_url . 'assets/js/checkout-pay-later-fields.js',
			array( 'jquery' ),
			Plugin::VERSION,
			true
		);

		wp_enqueue_script(
			'fhs-checkout-billing-trigger-guard',
			$base_url . 'assets/js/checkout-billing-trigger-guard.js',
			array( 'jquery' ),
			Plugin::VERSION,
			true
		);

		wp_enqueue_script(
			'fhs-checkout-update-guards',
			$base_url . 'assets/js/checkout-update-guards.js',
			array( 'jquery' ),
			Plugin::VERSION,
			true
		);

		wp_localize_script(
			'fhs-checkout-po-upload',
			'fhsCheckoutPoUpload',
			array(
				'ajaxUrl'           => function_exists( 'admin_url' ) ? admin_url( 'admin-ajax.php' ) : '',
				'fileTooLarge'      => 'File is too large. Maximum size allowed is 20MB.',
				'uploadingText'     => 'Uploading: ',
				'uploadingButton'   => 'Uploading PO...',
				'noFileChosen'      => 'No file chosen',
				'uploadFailedPrefix'=> 'Upload failed: ',
				'uploadFailed'      => 'Upload failed',
				'serverError'       => 'A server error occurred during upload. The file might be larger than what the server allows.',
				'serverErrorShort'  => 'Server error during upload',
				'successPrefix'     => 'Successfully uploaded: ',
			)
		);
	}

	/**
	 * Resolve fulfilment mode from posted data or session.
	 *
	 * @param array|null $posted_data Optional parsed posted data.
	 * @return string
	 */
	public static function get_fulfilment_method( $posted_data = null ) {
		if ( is_array( $posted_data ) && isset( $posted_data['fhs_fulfilment_method'] ) ) {
			$mode = sanitize_key( (string) $posted_data['fhs_fulfilment_method'] );
			if ( in_array( $mode, array( 'delivery', 'pickup' ), true ) ) {
				return $mode;
			}
		}

		if ( isset( $_POST['fhs_fulfilment_method'] ) ) {
			$mode = sanitize_key( (string) wp_unslash( $_POST['fhs_fulfilment_method'] ) );
			if ( in_array( $mode, array( 'delivery', 'pickup' ), true ) ) {
				return $mode;
			}
		}

		if ( isset( $_POST['post_data'] ) ) {
			$parsed = array();
			parse_str( wp_unslash( $_POST['post_data'] ), $parsed );
			$mode = sanitize_key( (string) ( $parsed['fhs_fulfilment_method'] ?? '' ) );
			if ( in_array( $mode, array( 'delivery', 'pickup' ), true ) ) {
				return $mode;
			}
		}

		if ( WC()->session ) {
			$mode = sanitize_key( (string) WC()->session->get( 'fhs_fulfilment_method', '' ) );
			if ( in_array( $mode, array( 'delivery', 'pickup' ), true ) ) {
				return $mode;
			}
		}

		return 'delivery';
	}

	/**
	 * Parse checkout posted data.
	 *
	 * @return array
	 */
	public static function get_checkout_posted_data() {
		static $posted_data = null;

		if ( null !== $posted_data ) {
			return $posted_data;
		}

		$posted_data = array();

		if ( isset( $_POST['post_data'] ) ) {
			parse_str( wp_unslash( $_POST['post_data'] ), $posted_data );
		} elseif ( ! empty( $_POST ) && is_array( $_POST ) ) {
			$posted_data = wp_unslash( $_POST );
		}

		return is_array( $posted_data ) ? $posted_data : array();
	}

	/**
	 * Determine whether checkout prefill should be skipped.
	 *
	 * @param string $field_key Field key.
	 * @return bool
	 */
	public static function should_skip_checkout_prefill( $field_key ) {
		if ( ! is_string( $field_key ) || '' === $field_key ) {
			return false;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return false;
		}

		$is_checkout_request = ( function_exists( 'is_checkout' ) && is_checkout() )
			|| ( wp_doing_ajax() && ( isset( $_POST['post_data'] ) || isset( $_POST['fhs_fulfilment_method'] ) ) );

		if ( ! $is_checkout_request ) {
			return false;
		}

		$fields_to_keep_empty_on_boot = array();

		if ( ! in_array( $field_key, $fields_to_keep_empty_on_boot, true ) ) {
			return false;
		}

		$posted_data = self::get_checkout_posted_data();

		if ( array_key_exists( $field_key, $posted_data ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get request-aware checkout value.
	 *
	 * @param string $field_key Field key.
	 * @param string $default Default value.
	 * @return string
	 */
	public static function get_checkout_request_value( $field_key, $default = '' ) {
		$posted_data = self::get_checkout_posted_data();

		if ( array_key_exists( $field_key, $posted_data ) ) {
			return wc_clean( $posted_data[ $field_key ] );
		}

		if ( self::should_skip_checkout_prefill( $field_key ) ) {
			return '';
		}

		return wc_clean( $default );
	}

	/**
	 * Filter checkout value prefill.
	 *
	 * @param mixed  $value Value.
	 * @param string $input Input key.
	 * @return mixed
	 */
	public function filter_checkout_value( $value, $input ) {
		if ( self::should_skip_checkout_prefill( $input ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Set default fulfilment mode in session.
	 *
	 * @return void
	 */
	public function set_default_fulfilment_mode() {
		if ( wp_doing_ajax() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}

		if ( ! WC()->session ) {
			return;
		}

		$has_mode_in_request = isset( $_POST['fhs_fulfilment_method'] ) || isset( $_POST['post_data'] );
		if ( ! $has_mode_in_request ) {
			WC()->session->set( 'fhs_fulfilment_method', 'delivery' );
		}
	}

	/**
	 * Sync fulfilment mode to session during checkout refresh.
	 *
	 * @param string $posted_data Serialized checkout data.
	 * @return void
	 */
	public function sync_fulfilment_mode( $posted_data ) {
		if ( ! WC()->session ) {
			return;
		}

		$parsed = array();
		parse_str( (string) $posted_data, $parsed );
		$mode = self::get_fulfilment_method( $parsed );
		WC()->session->set( 'fhs_fulfilment_method', $mode );
	}

	/**
	 * Filter shipping address requirement for pickup.
	 *
	 * @param bool $needs_shipping_address Needs shipping address.
	 * @return bool
	 */
	public function filter_needs_shipping_address( $needs_shipping_address ) {
		if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) {
			return $needs_shipping_address;
		}

		return 'pickup' === self::get_fulfilment_method() ? false : true;
	}

	/**
	 * Filter shipping need for pickup.
	 *
	 * @param bool $needs_shipping Needs shipping.
	 * @return bool
	 */
	public function filter_needs_shipping( $needs_shipping ) {
		if ( ! $needs_shipping ) {
			return $needs_shipping;
		}

		return 'pickup' === self::get_fulfilment_method() ? false : $needs_shipping;
	}

	/**
	 * Override shipping packages destination from current request values.
	 *
	 * @param array $packages Shipping packages.
	 * @return array
	 */
	public function filter_shipping_packages( $packages ) {
		if ( empty( $packages ) || ! is_array( $packages ) ) {
			return $packages;
		}

		if ( 'pickup' === self::get_fulfilment_method() ) {
			return $packages;
		}

		$destination = array(
			'country'   => '',
			'state'     => '',
			'postcode'  => '',
			'city'      => '',
			'address'   => '',
			'address_1' => '',
			'address_2' => '',
		);

		$destination['country']   = self::get_checkout_request_value( 'shipping_country', WC()->customer->get_shipping_country() );
		$destination['state']     = self::get_checkout_request_value( 'shipping_state', WC()->customer->get_shipping_state() );
		$destination['postcode']  = self::get_checkout_request_value( 'shipping_postcode', WC()->customer->get_shipping_postcode() );
		$destination['city']      = self::get_checkout_request_value( 'shipping_city', WC()->customer->get_shipping_city() );
		$destination['address']   = self::get_checkout_request_value( 'shipping_address_1', WC()->customer->get_shipping_address() );
		$destination['address_1'] = $destination['address'];
		$destination['address_2'] = self::get_checkout_request_value( 'shipping_address_2', WC()->customer->get_shipping_address_2() );

		foreach ( $packages as $index => $package ) {
			$packages[ $index ]['destination'] = $destination;
		}

		return $packages;
	}
}
