<?php
/**
 * Plugin Name: FHS Checkout
 * Description: Checkout business functionality for FHS WooCommerce.
 * Version: 1.0.0
 * Author: FHS
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-fhs-checkout.php';
require_once __DIR__ . '/includes/class-fhs-checkout-fields.php';
require_once __DIR__ . '/includes/class-fhs-checkout-validation.php';
require_once __DIR__ . '/includes/class-fhs-payment-rules.php';
require_once __DIR__ . '/includes/class-fhs-po-upload.php';

register_activation_hook( __FILE__, array( '\\FHS\\Checkout\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\FHS\\Checkout\\Plugin', 'deactivate' ) );

\\FHS\\Checkout\\Plugin::boot();
