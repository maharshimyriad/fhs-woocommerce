<?php
/**
 * Quotes endpoint integration.
 *
 * @package FHS\Core
 */

namespace FHS\Core;

defined( 'ABSPATH' ) || exit;

class Quotes {
	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_account_my-quotes_endpoint', array( $this, 'render_my_quotes_endpoint' ) );
		add_action( 'woocommerce_account_quotes_endpoint', array( $this, 'render_legacy_quotes_endpoint' ) );
	}

	/**
	 * Render the active My Quotes endpoint.
	 *
	 * @return void
	 */
	public function render_my_quotes_endpoint() {
		echo do_shortcode( '[stars_quote_page]' );
	}

	/**
	 * Preserve legacy quotes endpoint output if referenced elsewhere.
	 *
	 * @return void
	 */
	public function render_legacy_quotes_endpoint() {
		echo '<h3>' . esc_html__( 'My Quotes', 'astra-child' ) . '</h3>';
		echo '<p>' . esc_html__( 'Display quotes here (requires a custom implementation or plugin).', 'astra-child' ) . '</p>';
	}
}
