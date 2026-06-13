<?php
/**
 * Single product short description
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/short-description.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// global $post;

// $short_description = apply_filters( 'woocommerce_short_description', $post->post_excerpt );



?>


<?php
global $product;

if ( is_user_logged_in() && $product ) :

    $stock_class = 'out-of-stock';

    if ( $product->is_type( 'variable' ) ) {

        $variations = $product->get_children();
        $any_in_stock = false;

        foreach ( $variations as $variation_id ) {
            $variation = wc_get_product( $variation_id );

            if ( $variation && $variation->is_in_stock() ) {
                $any_in_stock = true;
                break;
            }
        }

        $stock_class = $any_in_stock ? 'in-stock' : 'out-of-stock';

    } else {

        $stock_class = $product->is_in_stock() ? 'in-stock' : 'out-of-stock';
    }
?>

<div class="stock-placeholder-container <?php echo esc_attr( $stock_class ); ?>">
    <div class="icon-container">
        <div class="stock-icon first"></div>
        <div class="stock-icon second"></div>
        <div class="stock-icon third"></div>
    </div>
    <div class="stock-placeholder"></div>
</div>

<?php
endif;
?>



<!-- <div class="woocommerce-product-details__short-description">
    <?php the_content(); ?>
</div> -->
<div class="woocommerce-product-details__short-description">
    <?php
    global $post;
    echo apply_filters( 'woocommerce_short_description', $post->post_excerpt );
    ?>
</div>
