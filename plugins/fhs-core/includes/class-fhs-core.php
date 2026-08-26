<?php
/**
 * Core plugin bootstrap.
 *
 * @package FHS\Core
 */

namespace FHS\Core;

defined( 'ABSPATH' ) || exit;

class Plugin {
	/**
	 * Plugin version.
	 */
	const VERSION = '1.0.0';

	/**
	 * Boot the plugin services.
	 *
	 * @return void
	 */
	public static function boot() {
		new Account();
		new Quotes();
		new Invoices();
		new Myob();
		new Customer();
		new Orders();
	}

	/**
	 * Flush rewrite rules on activation after registering endpoints.
	 *
	 * @return void
	 */
	public static function activate() {
		$account = new Account();
		$account->register_endpoints();
		flush_rewrite_rules();
	}

	/**
	 * Flush rewrite rules on deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
