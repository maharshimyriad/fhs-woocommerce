<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}
$is_out_of_stock = ! $product->is_in_stock();

echo wc_get_stock_html( $product ); // WPCS: XSS ok.

?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>
<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
	<div class="woocommerce-variation-add-to-cart variations_button">
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<?php do_action( 'woocommerce_before_add_to_cart_quantity' ); ?>
<div class="addtocart-wrapper">

<?php if ( is_user_logged_in() ) : ?>

    <!-- PRICE -->
<div style="position: relative; display: flex; flex-direction: column; gap: 0; align-items: start; margin-right:20px;">
    <p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'price' ) ); ?>">
        <?php
        if ( current_user_can( 'manage_woocommerce' ) ) {
            // Admin view: Show Level A pricing if available
            $level_a_regular = get_post_meta( $product->get_id(), '_LevelA_tiered_price_regular_price', true );
            
            if ( $level_a_regular ) {
                // Level A pricing available - show with sale price format
                $level_a_sale = '';
                
                // Check fixed price rules for sale price
                $fixed_rules = get_post_meta( $product->get_id(), '_LevelA_fixed_price_rules', true );
                if ( $fixed_rules && is_array( $fixed_rules ) && count( $fixed_rules ) > 0 ) {
                    $min_price = min( array_column( $fixed_rules, 'price' ) );
                    if ( $min_price && $min_price < (float) $level_a_regular ) {
                        $level_a_sale = $min_price;
                    }
                }
                
                // Check percentage rules for sale price
                if ( ! $level_a_sale ) {
                    $percentage_rules = get_post_meta( $product->get_id(), '_LevelA_percentage_price_rules', true );
                    if ( $percentage_rules && is_array( $percentage_rules ) && count( $percentage_rules ) > 0 ) {
                        $first_rule = $percentage_rules[0];
                        if ( isset( $first_rule['price'] ) ) {
                            $discount_percent = (float) $first_rule['price'];
                            $level_a_sale = (float) $level_a_regular * ( 1 - ( $discount_percent / 100 ) );
                        }
                    }
                }
                
                // Display Level A pricing with strikethrough + sale price
                if ( $level_a_sale && $level_a_sale < (float) $level_a_regular ) {
                    echo wc_format_sale_price( $level_a_regular, $level_a_sale );
                } else {
                    echo wc_price( $level_a_regular );
                }
            } else {
                // No Level A pricing - show product's regular and sale price
                $regular = (float) $product->get_regular_price();
                $sale = (float) $product->get_sale_price();
                
                if ( $sale && $sale < $regular ) {
                    echo wc_format_sale_price( $regular, $sale );
                } else {
                    echo wc_price( $regular );
                }
            }

        } else {
            // Regular user - show tier pricing
            $base_price = (float) $product->get_regular_price();
            $tier_price = (float) $product->get_price();

            if ( $tier_price < $base_price ) {
                echo wc_format_sale_price( $base_price, $tier_price ) . $product->get_price_suffix();
            } else {
                echo wc_price( $tier_price ) . $product->get_price_suffix();
            }
        }
        ?>
    </p>
    <span class="gst-text">(Ex GST)</span>
</div>

    <!-- QUANTITY -->
    <div class="quantity-container">
        <button type="button" class="minus">-</button>

        <div class="quantity">
            <?php
            woocommerce_quantity_input(
                array(
                    'min_value'   => 1,
                    'max_value'   => '',
                    'input_value' => 1,
                ),
                $product,
                true
            );
            ?>
        </div>

        <button type="button" class="plus">+</button>
    </div>

    <?php do_action( 'woocommerce_after_add_to_cart_quantity' ); ?>

    <!-- ADD TO CART -->
    <button 
        type="submit"
        name="add-to-cart"
        value="<?php echo esc_attr( $product->get_id() ); ?>"
        class="single_add_to_cart_button button alt<?php echo $is_out_of_stock ? ' disabled' : ''; ?>"
        <?php echo $is_out_of_stock ? 'disabled="disabled"' : ''; ?>
    >
        <i class="icofont-shopping-cart"></i>
        <?php 
        echo $is_out_of_stock 
            ? esc_html__( 'Out of Stock', 'woocommerce' ) 
            : esc_html( $product->single_add_to_cart_text() ); 
        ?>
    </button>

<?php else : ?>

    <?php
    $current_url = get_permalink();
	$login_url = add_query_arg( 'redirect_to', urlencode( $current_url ), site_url( '/my-account' ) );
		?>
			<div class="login-prompt">
				<span><?php esc_html_e( 'Login or register to view prices.', 'woocommerce' ); ?></span>
				<a href="<?php echo esc_url( $login_url ); ?>">
					<i class="icofont icofont-ui-user"></i>
					<?php esc_html_e( 'Login / Register to see pricing', 'woocommerce' ); ?>
				</a>
		</div>

<?php endif; ?>

</div>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</div>


	<?php 
		echo '<div class="woocommerce-other-btn">';
		echo do_shortcode('[yith_wcwl_add_to_wishlist]');
		echo do_shortcode('[stars_add_to_quote_button text="Add to Quote"]');
		echo '</div>';
?>
		<details class="product-specs-details">
    
        					<summary class="dropdown-header product-specs-summary" style="width: 100%; display: flex; justify-content: space-between;">
					
						<span> <i class="icofont icofont-file-alt" style="margin-right:7px;"></i> Features</span>
						<span class="dropdown-icon"><i class="icofont-rounded-down"></i></span>
					</summary>


    <div class="summary-container product-specs-content">

        <div class="product-specs-row-detail">

        
                <?php echo ( get_field('product_features') ?: '-' ); ?>
            </span>
        </div>



    </div>
</details>
<?php

$store_address_1 = get_option('woocommerce_store_address');
$store_address_2 = get_option('woocommerce_store_address_2');
$store_city      = get_option('woocommerce_store_city');
$store_postcode  = get_option('woocommerce_store_postcode');
$store_country   = get_option('woocommerce_default_country');

$country_parts = explode(':', $store_country);
$country_code  = $country_parts[0];

$countries = WC()->countries->countries;
$country_name = $countries[$country_code] ?? '';

$formatted_address = array_filter([
    $store_address_1,
    $store_address_2,
    $store_city,
    $store_postcode,
    $country_name
]);

$formatted_address = implode(', ', $formatted_address);

$current_user_email = '';

if ( is_user_logged_in() ) {
    $user_id = get_current_user_id();
    if ( $user_id ) {
        $current_user_email = get_userdata( $user_id )->user_email ?? '';
    }
}

$delivery_enquiry_nonce = wp_create_nonce( 'delivery_enquiry_nonce' );
$show_delivery_enquiry = $product && $product->get_backorders() === 'notify';
?>
<div class="complete-kit-section">
    <?php echo do_shortcode( '[complete_kit field="optional_extras" ]' ); ?>
</div>

<div class="wpf_available-address-container">
    <div class="wpf_available-address-header">
        <i class="icofont-tick-mark"></i>
        <span>Delivery Timeframes</span>
    </div>

    <div class="wpf_available-address-content">
        <div class="wpf_pickup-section">
            <div class="wpf_pickup-label">
                <span class="wpf_pickup-text">
                    <i class="icofont-vehicle-delivery-van"></i>
                    <span>Pickup From FHS Poly</span>
                    <details>
                        <summary>Store Details</summary>
                        <span><?php echo wp_kses_post( $formatted_address ); ?></span>
                    </details>
                </span>
            </div>
            <div class="wpf_pickup-time">
                Usually ready within 24 hours<br/>
                <span style="color: #28a745;">(when in stock)</span>
            </div>
        </div>

        <div class="wpf_delivery-section">
            <div class="wpf_delivery-label">
                <i class="icofont-ui-home"></i>
                <div>
                    <span class="wpf_delivery-text">Delivery To</span>
                    <div class="wpf_delivery-address">
                        <details>
                            <summary>My delivery address</summary>
                            <span><?php echo wp_kses_post( $shipping_address ); ?></span>
                        </details>
                    </div>
                </div>
            </div>
            <div class="wpf_delivery-time">
                Estimated delivery time<br/><?php echo esc_html( $est_time_string ?? 'not' ); ?>
            </div>
        </div>
    </div>

    <div class="wpf-delivery-note">
        <div class="wpf-delivery-note-text">
            Item(s) weighing more than 22kgs will require appropriate lifting facilities to unload your delivery.
        </div>
    </div>

    <?php if ( $show_delivery_enquiry ) : ?>
        <div class="wpf_delivery-enquiry-wrap">
            <button
                type="button"
                class="wpf_delivery-enquiry-btn"
                data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
                data-nonce="<?php echo esc_attr( $delivery_enquiry_nonce ); ?>"
            >
                <i class="icofont-clock-time"></i>
                Enquiry for Backorder Delivery Time Estimates
            </button>
        </div>
    <?php endif; ?>
</div>

<div id="wpf-delivery-enquiry-modal" class="wpf-enquiry-overlay">
    <div class="wpf-enquiry-modal-box">
        <button type="button" id="wpf-enquiry-modal-close" class="wpf-enquiry-modal-close">&times;</button>

        <h3 class="wpf-enquiry-modal-title">Enquiry for Backorder Delivery Time Estimates</h3>
        <p class="wpf-enquiry-modal-desc">
            Enter your postcode and we'll get back to you with an estimated delivery time on backorders.
        </p>

        <div id="wpf-enquiry-success-msg" class="wpf-enquiry-success-msg">
            Thanks, We've received your enquiry. Will reach out to you soon.
        </div>

        <div id="wpf-enquiry-form-area">
            <label class="wpf-enquiry-label">
                Your Email <span class="wpf-enquiry-required">*</span>
            </label>
            <input
                type="email"
                id="wpf-enquiry-email"
                class="wpf-enquiry-input"
                placeholder="Enter your email"
                value="<?php echo esc_attr( $current_user_email ); ?>"
            />

            <label class="wpf-enquiry-label">
                Your postcode <span class="wpf-enquiry-required">*</span>
            </label>
            <input
                type="text"
                id="wpf-enquiry-postcode"
                class="wpf-enquiry-input"
                placeholder="Enter postcode"
                maxlength="20"
            />

            <input type="hidden" id="wpf-enquiry-sku" value="">

            <div id="wpf-enquiry-error" class="wpf-enquiry-error"></div>

            <button type="button" id="wpf-enquiry-submit-btn" class="wpf-enquiry-submit-btn">
                Submit Enquiry
            </button>
        </div>
    </div>
</div>

<div class="complete-kit-section">
    <?php echo do_shortcode( '[complete_kit]' ); ?>
</div>
</form>

<script>
    jQuery(function ($) {

function updateSku() {
    var sku = '';

    if ($('.product').hasClass('product-type-variable')) {
        var variation = $('.single_variation_wrap').find('.sku').text();
        sku = variation ? variation.trim() : '';
    } else {
        sku = $('.product .sku').first().text().trim();
    }

    $('#wpf-enquiry-sku').val(sku);
}

updateSku();

$('form.variations_form').on('found_variation', function (event, variation) {
    if (variation.sku) {
        $('#wpf-enquiry-sku').val(variation.sku);
    }
});

$('form.variations_form').on('reset_data', function () {
    updateSku();
});

});
</script>
<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
