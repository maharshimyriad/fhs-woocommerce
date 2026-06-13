<?php
/**
 * Lost password form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-lost-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_lost_password_form' );
?>
<div class="customer-mainauth-container">
	<div id="customer-forgotpass-form" class="form-container">
	<h2 class="customer-fogotpass-heading heading-2"><?php esc_html_e( 'Forgot Password', 'woocommerce' ); ?></h2>
	<div class="mainauth-wrapper">
	<div class="authform-container">
		
	
<form method="post" class="woocommerce-ResetPassword lost_reset_password">


	
	  <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="user_login"><?php esc_html_e( 'Email', 'woocommerce' ); ?> <span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
         <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" placeholder="Email address" name="user_login" id="user_login" autocomplete="username" required aria-required="true" />
                </p>

	<div class="clear"></div>

	<?php do_action( 'woocommerce_lostpassword_form' ); ?>

	<p class="woocommerce-form-row form-row">
		<input type="hidden" name="wc_reset_password" value="true" />
		<button type="submit" class="auth-submit-button woocommerce-Button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" value="<?php esc_attr_e( 'Reset password', 'woocommerce' ); ?>"><?php esc_html_e( 'Submit', 'woocommerce' ); ?></button>
	</p>
	<p class="woocommerce-form-row form-row">
                       <a class="authform-cancelbtn " href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
    <?php esc_html_e( 'Cancel', 'woocommerce' ); ?>
</a>

                    </p>

	<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>

</form>
 </div>
	   <div class="form-other-content-container">
			<div class="blurdrop-filter"></div>
      <p class="form-other-content">
        Your shopping cart is waiting! Log in to view your order history, save your favorite items, and enjoy a seamless shopping experience.
      </p>
    </div>
</div>
	</div>
</div>
<?php
do_action( 'woocommerce_after_lost_password_form' );
