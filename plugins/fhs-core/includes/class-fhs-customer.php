<?php
/**
 * Customer business metadata management.
 *
 * @package FHS\Core
 */

namespace FHS\Core;

defined( 'ABSPATH' ) || exit;

class Customer {
	/**
	 * Prefix for existing stored custom meta keys.
	 */
	const META_PREFIX = 'ms_fhs_custom_';

	/**
	 * Fields captured on admin user creation.
	 *
	 * @return array
	 */
	public static function get_business_fields() {
		return array(
			'registration_company_name' => 'Registration Company Name',
			'trading_company_name'      => 'Trading Company Name',
			'billing_first_name'        => 'Billing First Name',
			'billing_last_name'         => 'Billing Last Name',
			'billing_email'             => 'Billing Email Address',
			'billing_phone'             => 'Billing Phone Number',
			'phone_number'              => 'Phone Number',
			'abn_number'                => 'ABN Number',
			'business_address'          => 'Business Address',
			'shipping_address'          => 'Shipping Address',
			'recovery_email'            => 'Email for Password Recovery',
		);
	}

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'user_new_form', array( $this, 'render_admin_user_fields' ) );
		add_action( 'user_register', array( $this, 'save_admin_user_fields' ) );
	}

	/**
	 * Render custom business fields on the Add New User screen.
	 *
	 * @return void
	 */
	public function render_admin_user_fields() {
		$fields = self::get_business_fields();
		?>
		<h3><?php esc_html_e( 'Business Details', 'astra-child' ); ?></h3>
		<table class="form-table">
			<?php foreach ( $fields as $key => $label ) : ?>
				<tr>
					<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="regular-text" /></td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Save admin-entered business fields for new users.
	 *
	 * Preserves existing meta key format for backward compatibility.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function save_admin_user_fields( $user_id ) {
		foreach ( array_keys( self::get_business_fields() ) as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_user_meta( $user_id, self::META_PREFIX . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}
	}
}
