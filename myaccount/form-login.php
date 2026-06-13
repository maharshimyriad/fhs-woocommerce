<?php
/**
 * Login Form with Toggleable Register Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

do_action( 'woocommerce_before_customer_login_form' ); ?>
<?php $show_register = isset( $_GET['register'] ) && $_GET['register'] === 'true'; ?>

<div class="customer-mainauth-container">

    <!-- Login Form -->
    <div id="customer-login-form" class="form-container" style="display: <?php echo $show_register ? 'none' : 'block'; ?>;">
        <h2 class="customer-login-heading heading-2"><?php esc_html_e( 'Customer Login', 'woocommerce' ); ?></h2>
        <div class="mainauth-wrapper">
        <div class="authform-container">
            
            <form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>
                <?php do_action( 'woocommerce_login_form_start' ); ?>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="username"><?php esc_html_e( 'Email', 'woocommerce' ); ?> <span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" placeholder="Email address" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" />
                </p>
                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
                    <input class="woocommerce-Input woocommerce-Input--text input-text" placeholder="Enter your password" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
                </p>

                <?php do_action( 'woocommerce_login_form' ); ?>

                
                    <div class="lost_password-container">
<!--                     <p class="woocommerce-LostPassword lost_password"> -->
                        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'woocommerce' ); ?></a>
                   
                    <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                    <button type="submit" class="auth-submit-button woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="login" value="<?php esc_attr_e( 'Log In', 'woocommerce' ); ?>"><?php esc_html_e( 'Log In', 'woocommerce' ); ?></button>
                    </div>
<!--                 </p> -->
                    <div class="login-form-register-container">
                <p class="form-row">
                    
                    <p>Don't have any account?</p> 
                    <!--<button type="button" class="toggle-form" onclick="toggleForms('register-form', 'customer-login-form')"><?php esc_html_e( 'Create an account', 'woocommerce' ); ?></button>-->
                     <a href="<?php echo esc_url( add_query_arg( 'register', 'true', home_url( $_SERVER['REQUEST_URI'] ) ) ); ?>">
                        <button type="button" class="toggle-form"><?php esc_html_e( 'Create an account', 'woocommerce' ); ?></button>
                    </a>
                </p>
                </div>

                <?php do_action( 'woocommerce_login_form_end' ); ?>
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


    <!-- Register Form (hidden by default) -->
    <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>
    <div id="register-form" class="form-container" style="display: <?php echo $show_register ? 'block' : 'none'; ?>;">
        <h2 class="heading-2"><?php esc_html_e( 'Register', 'woocommerce' ); ?></h2>
<div class="authform-container">
      <?php
gravity_form(12, false, false, false, '', true, 12, true);
?>

	<div class="form-extra-content">
            <p class="form-row">
				<p>Already have an account?</p>
				<a href="<?php echo esc_url( remove_query_arg( 'register', home_url( $_SERVER['REQUEST_URI'] ) ) ); ?>">
                <button type="button" class="woocommerce-button toggle-form" onclick="toggleForms('customer-login-form', 'register-form')"><?php esc_html_e( 'Login Now', 'woocommerce' ); ?></button>
                </a>
            </p>
	</div>
		</div>

            <?php do_action( 'woocommerce_register_form_end' ); ?>
    </div>
    <?php endif; ?>

</div>

<script type="text/javascript">
    function toggleForms(showId, hideId) {
        document.getElementById(showId).style.display = 'block';
        document.getElementById(hideId).style.display = 'none';
    }
</script>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>	