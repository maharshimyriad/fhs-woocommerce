<?php
/**
 * Plugin Name: FHS Core
 * Description: Core business functionality for FHS WooCommerce.
 * Version: 1.0.0
 * Author: FHS
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-fhs-core.php';
require_once __DIR__ . '/includes/class-fhs-account.php';
require_once __DIR__ . '/includes/class-fhs-customer.php';
require_once __DIR__ . '/includes/class-fhs-invoices.php';
require_once __DIR__ . '/includes/class-fhs-myob.php';
require_once __DIR__ . '/includes/class-fhs-orders.php';
require_once __DIR__ . '/includes/class-fhs-quotes.php';

register_activation_hook( __FILE__, array( '\\FHS\\Core\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\FHS\\Core\\Plugin', 'deactivate' ) );

\\FHS\\Core\\Plugin::boot();
