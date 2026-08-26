<?php
/**
 * Customer account endpoints and menu customizations.
 *
 * @package FHS\Core
 */

namespace FHS\Core;

defined( 'ABSPATH' ) || exit;

class Account {
	/**
	 * Endpoint slugs that must remain backward compatible.
	 */
	const ENDPOINT_INVOICES = 'invoices';
	const ENDPOINT_QUOTES   = 'my-quotes';
	const ENDPOINT_ORDERS   = 'orders';
	const ENDPOINT_CART     = 'cart';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_endpoints' ) );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'filter_account_menu_items' ), 10, 1 );
	}

	/**
	 * Register custom WooCommerce account endpoints.
	 *
	 * Orders and cart are preserved for compatibility even though their content
	 * is still handled outside this plugin in later migration phases.
	 *
	 * @return void
	 */
	public function register_endpoints() {
		$endpoints = array(
			self::ENDPOINT_ORDERS,
			self::ENDPOINT_INVOICES,
			self::ENDPOINT_QUOTES,
			self::ENDPOINT_CART,
		);

		foreach ( $endpoints as $endpoint ) {
			add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES );
		}
	}

	/**
	 * Register custom query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function register_query_vars( $vars ) {
		$vars[] = self::ENDPOINT_QUOTES;
		return $vars;
	}

	/**
	 * Customize account menu ordering.
	 *
	 * This preserves the current customer portal navigation labels and order.
	 *
	 * @param array $items Existing account menu items.
	 * @return array
	 */
	public function filter_account_menu_items( $items ) {
		unset( $items['dashboard'] );
		unset( $items['edit-account'] );

		return array(
			'edit-account'    => $items['edit-account'] ?? __( 'My Account', 'astra-child' ),
			self::ENDPOINT_QUOTES => __( 'My Quotes', 'astra-child' ),
			self::ENDPOINT_ORDERS => $items['My Past orders'] ?? __( 'My Past Orders', 'astra-child' ),
			self::ENDPOINT_CART   => $items['cart'] ?? __( 'My Shopping Cart', 'astra-child' ),
			'wishlist'        => $items['wishlist'] ?? __( 'My Wish Lists', 'astra-child' ),
			'edit-address'    => $items['edit-address'] ?? __( 'Shipping Address', 'astra-child' ),
			'customer-logout' => $items['customer-logout'] ?? __( 'Logout', 'astra-child' ),
		);
	}
}
