<?php
/**
 * FHS WooCommerce - Level A Tier Pricing Display
 * 
 * Initialize Level A pricing display in admin and frontend
 * 
 * Add this to your theme's functions.php or as a mu-plugin:
 * require_once get_template_directory() . '/woocommerce/fhs-tier-pricing-init.php';
 * 
 * @package FHS_WOO
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the admin display functions
require_once __DIR__ . '/admin-level-pricing-display.php';

/**
 * Verify Tier Pricing Table Premium is active
 */
function fhs_check_tier_pricing_plugin() {
	if ( ! class_exists( 'Woo_Tier_Pricing_Table' ) && ! function_exists( 'get_woo_tier_pricing_table' ) ) {
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-warning"><p>';
			esc_html_e( 'FHS Tier Pricing Display: Tier Pricing Table Premium plugin is required but not active.', 'woocommerce' );
			echo '</p></div>';
		});
	}
}
add_action( 'plugins_loaded', 'fhs_check_tier_pricing_plugin' );
