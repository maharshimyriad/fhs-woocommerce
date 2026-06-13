<?php
/**
 * My Addresses
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-address.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


$override_file = dirname( __FILE__ ) . '/THWMA_Public_MyAccount_override.php';

if ( file_exists( $override_file ) ) {
    require_once $override_file;
} else {
    error_log( 'THWMA_Public_MyAccount_override.php not found in ' . dirname( __FILE__ ) );
}

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
	$get_addresses = apply_filters( 'woocommerce_my_account_get_addresses', array(
		'billing' => __( 'Billing address','woocommerce-multiple-addresses-pro' ),
		'shipping' => __( 'Shipping address','woocommerce-multiple-addresses-pro' ),
	), $customer_id );
} else {
	$get_addresses = apply_filters( 'woocommerce_my_account_get_addresses', array(
		'billing' => __( 'Billing address','woocommerce-multiple-addresses-pro' ),
	), $customer_id );
}

$oldcol = 1;
$col    = 1;
?>
<div class="">
    <h2>Shipping Address</h2>
    <hr/>
<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) : ?>
	<div class="u-columns woocommerce-Addresses col2-set addresses">
<?php endif; ?>
<?php foreach ( $get_addresses as $name => $title ) : ?>
    <?php if($name != 'billing') : ?>
	<div class="u-column<?php echo ( ( $col = $col * -1 ) < 0 ) ? 1 : 2; ?> col-<?php echo ( ( $oldcol = $oldcol * -1 ) < 0 ) ? 1 : 2; ?> woocommerce-Address">
	    
		
		<address style="background-color:#f1f1f1;">
		    <div class="address-info" style="display: flex;align-items: center;">
		    <div class="address-icon-container" style="background-color:white">
		    	<i class="icofont-ui-home"></i>
		    </div>
		    <div>
		        <div class="address-name-custom-title">Default Address</div>
        		<?php
                $address = wc_get_account_formatted_address( $name );
                if ( $address ) {
                    $address = str_replace( array( '<br/>', '<br />', '<br>' ), ', ', $address );
                    $char_limit = 50;
                    if ( strlen( $address ) > $char_limit ) {
                        $short_address = substr( $address, 0, $char_limit );
						echo '<div class="address-p">';
						echo '<span class="address-short-text">' . esc_html( $short_address ) . '</span>';
                        echo '<span class="fhs-more-toggle" data-full="' . esc_attr( $address ) . '" data-short="' . esc_attr( $short_address ) . '" style="color:#72bfdf;font-weight:500;cursor:pointer;">...more</span>';
						echo '</div>';
                    } else {
                        echo esc_html( $address );
                    }
                } else {
                    echo esc_html_e( 'You have not set up this type of address yet.', 'woocommerce-multiple-addresses-pro' );
                }
                ?>
		    </div>
		    </div>
		    </address>
		    <?php
		    do_action('thwma_after_address_display', $customer_id);
		    ?>
	</div>
  <?php endif;?>
<?php endforeach; ?>

<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) : ?>
	</div>
<?php endif; 


// $thwma_public_myaccount->thwma_after_address_display($customer_id);


// $settings = THWMA_Utils::get_advanced_settings();
// if(!empty($settings)){
// 	$user_roles = array();
// 	$current_user = array();
// 	$user = wp_get_current_user();
// 	$enable_usr_acnt = (isset($settings['enable_user_account'])) ? $settings['enable_user_account'] : '';
// 	$user_roles = (isset($settings['select_user_role'])) ? $settings['select_user_role'] : '';
// 	$userroles = explode(',', $user_roles);
// 	$current_user = $user->roles;
// 	if($enable_usr_acnt == 'yes'){
// 		if(!empty($user_roles)){
// 			foreach( $current_user as $cur_user ){
// 				if (in_array($cur_user, $userroles, TRUE)) { 
// 					do_action('thwma_after_address_display', $customer_id);
// 				}
// 			}
// 		} else {
// 			do_action('thwma_after_address_display', $customer_id);
// 		}
// 	}
// }
// Initialize array to hold all addresses in key-value pairs
// echo "<pre>";
// $countries = WC()->countries->get_countries();
// $states = WC()->countries->get_states();
// print_r($states);
$countries = WC()->countries->get_countries();
$states    = WC()->countries->get_states();

// Extract THWMA extra addresses from user meta
$thwma_full_addresses = [];
$thwma_meta = get_user_meta( $customer_id, 'thwma_custom_address', true );

if ( is_array($thwma_meta) && isset($thwma_meta['shipping']) && is_array($thwma_meta['shipping']) ) {
    $default_key = isset($thwma_meta['default_shipping']) ? $thwma_meta['default_shipping'] : '';
    
    foreach ( $thwma_meta['shipping'] as $key => $addr ) {
        // Skip the default address (which is already rendered by standard WooCommerce code)
        if ( $key === $default_key ) continue;
        
        if ( is_array($addr) ) {
            $norm = [
                'first_name' => $addr['shipping_first_name'] ?? '',
                'last_name'  => $addr['shipping_last_name']  ?? '',
                'address_1'  => $addr['shipping_address_1']  ?? '',
                'address_2'  => $addr['shipping_address_2']  ?? '',
                'city'       => $addr['shipping_city']       ?? '',
                'state'      => $addr['shipping_state']      ?? '',
                'postcode'   => $addr['shipping_postcode']   ?? '',
                'country'    => $addr['shipping_country']    ?? '',
            ];
            
            // Format it exactly like WooCommerce does
            $fmt = WC()->countries->get_formatted_address( $norm );
            $thwma_full_addresses[] = trim( strip_tags( str_replace( ['<br/>', '<br />', '<br>'], ', ', $fmt ) ), ', ' );
        }
    }
}
?>
<!-- <script type="text/javascript">
    var wcCountries       = <?php echo json_encode($countries); ?>;
    var wcStates          = <?php echo json_encode($states); ?>;
    var fhsThwmaAddresses = <?php echo json_encode( array_values($thwma_full_addresses) ); ?>;

jQuery(function($) {

    // ── Default Address (fhs-more-toggle) ────────────────────────────────────
    $(document).on('click', '.fhs-more-toggle', function() {
        var $btn = $(this);
        if ( $btn.data('expanded') ) {
            $btn.prev('.address-short-text').text( $btn.data('short') );
            $btn.text('...more').data('expanded', false);
        } else {
            $btn.prev('.address-short-text').text( $btn.data('full') );
            $btn.text('...less').data('expanded', true);
        }
    });

    // ── THWMA extra addresses ─────────────────────────────────────────────────
    // Initialize: add cursor + class + data-full to every plain "...more" span
    // (we do this via JS so we never touch THWMA's HTML and don't break its events)
    var thwmaIdx = 0;
    $('span').filter(function() {
        return $.trim($(this).text()) === '...more' && !$(this).hasClass('fhs-more-toggle');
    }).each(function() {
        var $span = $(this);
        $span.addClass('thwma-more-span').css('cursor', 'pointer');
        if ( fhsThwmaAddresses[thwmaIdx] ) {
            $span.data('full', fhsThwmaAddresses[thwmaIdx]);
        }
        thwmaIdx++;
    });

    // Click handler for THWMA spans
    $(document).on('click', '.thwma-more-span', function() {
        var $span = $(this);
        var $par  = $span.parent();

        // Find the raw text node sitting immediately before the span
        var textNode = null;
        $par.contents().each(function() {
            if ( this.nodeType === 3 ) textNode = this;
        });

        if ( $span.data('thwma-expanded') ) {
            // Collapse: restore saved short text
            if ( textNode ) textNode.nodeValue = ( $span.data('saved-short') || '' ) + ' ';
            $span.text('...more').data('thwma-expanded', false);
        } else {
            // Expand: save current text, replace with full address
            var curText = textNode ? $.trim(textNode.nodeValue) : '';
            $span.data('saved-short', curText);

            var full = $span.data('full') || '';
            if ( full ) {
                if ( textNode ) {
                    textNode.nodeValue = full + ' ';
                } else {
                    $span.before( document.createTextNode(full + ' ') );
                }
            }
            $span.text('...less').data('thwma-expanded', true);
        }
    });

});
</script> -->
<script type="text/javascript">
var wcCountries       = <?php echo json_encode($countries); ?>;
var wcStates          = <?php echo json_encode($states); ?>;
var fhsThwmaAddresses = <?php echo json_encode( array_values($thwma_full_addresses) ); ?>;

jQuery(function($){

    // Default address toggle
    $(document).on('click', '.fhs-more-toggle', function() {

        var $btn = $(this);

        if ( $btn.data('expanded') ) {

            $btn.prev('.address-short-text')
                .text( $btn.data('short') );

            $btn.text('...more')
                .data('expanded', false);

        } else {

            $btn.prev('.address-short-text')
                .text( $btn.data('full') );

            $btn.text('...less')
                .data('expanded', true);
        }
    });

    // THWMA extra address setup
    var thwmaIdx = 0;

    $('span').filter(function() {

        return $.trim($(this).text()) === '...more'
            && !$(this).hasClass('fhs-more-toggle');

    }).each(function() {

        var $span = $(this);

        $span.addClass('thwma-more-span')
             .css('cursor', 'pointer');

        if ( fhsThwmaAddresses[thwmaIdx] ) {

            $span.attr(
                'data-full',
                fhsThwmaAddresses[thwmaIdx]
            );
        }

        thwmaIdx++;
    });

    // THWMA toggle click
    $(document).on('click', '.thwma-more-span', function() {

        var $span = $(this);
        var $par  = $span.parent();
        var $p    = $par.find('p');

        if ( !$p.length ) {
            return;
        }

        if ( $span.data('expanded') ) {

            // Collapse
            $p.text( $span.data('short') );

            $span.text('...more')
                 .data('expanded', false);

        } else {

            // Save short text once
            if ( !$span.data('short') ) {

                $span.data(
                    'short',
                    $.trim($p.text())
                );
            }

            // Expand
            $p.text( $span.attr('data-full') );

            $span.text('...less')
                 .data('expanded', true);
        }
    });

});
</script>
<div id="address-modal" title="Address Preview" style="display:none;">
    <form id="modal-address-form"></form>
</div>
