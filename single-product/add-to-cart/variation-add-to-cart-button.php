    <?php
    /**
     * Single variation cart button
     *
     * @see https://woocommerce.com/document/template-structure/
     * @package WooCommerce\Templates
     * @version 7.0.1
     */

    defined( 'ABSPATH' ) || exit;

    global $product;

    $acf_field_value = get_field( 'product_poa', $product->get_id() );
    $can_view_purchase_controls = is_user_logged_in();
    ?>

    <?php if ( $acf_field_value ): ?>
        <div id="custom-variation-button-wrapper">
        </div>
    <?php else: ?>
        <div class="woocommerce-variation-add-to-cart variations_button">
            <?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
            <?php if ( $can_view_purchase_controls ) : ?>
                <?php do_action( 'woocommerce_before_add_to_cart_quantity' ); ?>

                <div class="addtocart-wrapper">
                <?php
                if ( is_product() ) {

    			echo '<div class="price-wrapper ms-custom-price-wrapper" style="position: relative; display: flex; flex-direction: column; gap: 0; align-items: start; margin-right:20px;">';

                    

                        // Admin: show regular price only
                        if ( $product->is_type( 'variable' ) ) {
                            $regular_price = $product->get_variation_regular_price( 'min', true );
                        } else {
                            $regular_price = $product->get_regular_price();
                        }

                    

    				// Non-admin: default WooCommerce price output
    				echo '<div class="ms-custom-price-html">' . $product->get_price_html() . '</div>';
                    

                    echo '<div class="price-preloader"></div>';
    			echo '<span class="gst-text">(Ex GST)</span>';
                    echo '</div>';
                }
                ?>

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

                    <button type="submit" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>">
                        <i class="icofont-shopping-cart"></i>
                        <?php echo esc_html( $product->single_add_to_cart_text() ); ?>
                    </button>
                </div>
            <?php endif; ?>

			<?php 
				echo '<div class="woocommerce-other-btn">';
				echo do_shortcode('[yith_wcwl_add_to_wishlist]');
				echo do_shortcode('[stars_add_to_quote_button text="Add to Quote"]');
				echo '</div>';
				echo '
					<div class="ms-wishlist-container">
						<div id="ms-wishlist-text">
							<i class="icofont-heart"></i> Add to My Wishlist
							<i class="icofont-caret-down ms-wishlist-arrow"></i>
						</div>
						<div class="ms-wishlist-action">
							<button id="wishlist-dropdown-submit" type="button"><i class="icofont-plus"></i></button>
						</div>
					</div>';
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

    $product = wc_get_product( get_the_ID() );

    $show_delivery_enquiry = $product && $product->get_backorders() === 'notify';

    ?>

    <div class="complete-kit-section">
        <?php echo do_shortcode('[complete_kit field="optional_extras"]'); ?>
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
                            <span><?php echo esc_html($formatted_address); ?></span>
                        </details>

                    </span>
                </div>

                <div class="wpf_pickup-time">
                    Usually ready within 24 hours<br/>
                    <span style="color:#28a745;">(when in stock)</span>
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
                                <span><?php echo esc_html($shipping_address); ?></span>
                            </details>
                        </div>
                    </div>

                </div>

                <div class="wpf_delivery-time">
                    Estimated delivery time<br/>
                    <?php echo esc_html($est_time_string ?? 'not'); ?>
                </div>

            </div>


            <div class="wpf-delivery-note">
                <div class="wpf-delivery-note-text">
                    Item(s) weighing more than 22kgs will require appropriate lifting facilities to unload your delivery.
                </div>
            </div>

        </div>


        <?php if ( $show_delivery_enquiry ) : ?>

            <div class="wpf_delivery-enquiry-wrap">
                <button
                    type="button"
                    class="wpf_delivery-enquiry-btn"
                    data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('delivery_enquiry_nonce')); ?>"
                >
                    <i class="icofont-clock-time"></i>
<!--                     Enquire for Delivery Time Estimate -->
					Enquiry for Backorder Delivery Time Estimates

                </button>
            </div>

        <?php endif; ?>

    </div>


    <div id="wpf-delivery-enquiry-modal" class="wpf-enquiry-overlay">

        <div class="wpf-enquiry-modal-box">

            <button type="button" id="wpf-enquiry-modal-close" class="wpf-enquiry-modal-close">&times;</button>

            <h3 class="wpf-enquiry-modal-title">
<!--                 Enquire for Delivery Time Estimate -->
				Enquiry for Backorder Delivery Time Estimates

            </h3>

            <p class="wpf-enquiry-modal-desc">
                Enter your postcode and we'll get back to you with an estimated delivery time.
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
                    value="<?php echo esc_attr($current_user_email); ?>"
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

    <?php

            

        echo '<div class="complete-kit-section">';
    echo do_shortcode('[complete_kit]');
    echo '</div>';

            ?>

            <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

            <input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
            <input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
            <input type="hidden" name="variation_id" class="variation_id" value="0" />
        </div>
        
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
    <?php endif; ?>
