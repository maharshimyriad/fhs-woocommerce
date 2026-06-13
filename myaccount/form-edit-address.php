<?php
/**
 * Edit address form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-address.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$page_title = ( 'billing' === $load_address ) ? esc_html__( 'Billing address', 'woocommerce' ) : esc_html__( 'Shipping address', 'woocommerce' );

do_action( 'woocommerce_before_edit_account_address_form' ); ?>

<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>

	<form method="post" novalidate>
        <div class="add-shipping-address-container">
    		<h2><?php echo apply_filters( 'woocommerce_my_account_edit_address_title', $page_title, $load_address ); ?></h2><?php // @codingStandardsIgnoreLine ?>
		<p>
				<button type="submit" class="button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="save_address" value="<?php esc_attr_e( 'Save address', 'woocommerce' ); ?>">
				    <i class="icofont-tick-mark"></i><?php esc_html_e( 'Save', 'woocommerce' ); ?></button>
				<?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
				<input type="hidden" name="action" value="edit_address" />
		</p>
        </div>   
		<div class="woocommerce-address-fields">
			<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>
			
			<div class="woocommerce-address-fields__field-wrapper">
				<?php
				// this code for desired address line 2 column 
				        unset($address['shipping_address_2']);
                        $address['shipping_address_2'] = array(
                            'label'       => 'Street address Line 2',
                            'placeholder' => 'Street address Line 2',
                            'class'       => array(
                                'form-row-wide',
                                'address-field',
                            ),
                            'autocomplete' => 'address-line2',
                            'priority'     => 60,
                        );
                        uasort($address, function ($a, $b) {
                            $priorityA = isset($a['priority']) ? $a['priority'] : 0;
                            $priorityB = isset($b['priority']) ? $b['priority'] : 0;
                            if ($priorityA == $priorityB) {
                                return 0;
                            }
                            return ($priorityA < $priorityB) ? -1 : 1;
                        });
                        // echo "<pre>";
                        // print_r($address);
				// code bloack end     
				foreach ( $address as $key => $field ) {
					woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
				}
				?>
			</div>

			<?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

			
		</div>

	</form>

<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
