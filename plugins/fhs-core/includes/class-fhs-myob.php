<?php
/**
 * MYOB-related admin display helpers and metadata access.
 *
 * @package FHS\Core
 */

namespace FHS\Core;

defined( 'ABSPATH' ) || exit;

class Myob {
	/**
	 * User meta keys.
	 */
	const META_CUSTOMER_ID    = 'myob_customer_id';
	const META_PAYMENT_TERMS  = 'myob_payment_terms';
	const META_DESIGNATION    = 'myob_user_designation';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'manage_users_columns', array( $this, 'filter_user_columns' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'render_user_column' ), 10, 3 );
	}

	/**
	 * Add MYOB-related columns to the users table.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function filter_user_columns( $columns ) {
		$columns['myob_uid']              = 'MYOB UID';
		$columns['myob_payment_terms']    = 'Payment Terms';
		$columns['myob_user_designation'] = 'User Designation';

		return $columns;
	}

	/**
	 * Render MYOB-related user columns.
	 *
	 * @param string $value Existing cell value.
	 * @param string $column_name Column name.
	 * @param int    $user_id User ID.
	 * @return string
	 */
	public function render_user_column( $value, $column_name, $user_id ) {
		switch ( $column_name ) {
			case 'myob_uid':
				return (string) get_the_author_meta( self::META_CUSTOMER_ID, $user_id );
			case 'myob_payment_terms':
				return (string) get_the_author_meta( self::META_PAYMENT_TERMS, $user_id );
			case 'myob_user_designation':
				return (string) get_the_author_meta( self::META_DESIGNATION, $user_id );
			default:
				return $value;
		}
	}
}
