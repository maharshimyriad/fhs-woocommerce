<?php
/**
 * The template for displaying product category thumbnails within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product-cat.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<li <?php wc_product_cat_class( '', $category ); ?>>
    <?php
    /**
     * The woocommerce_before_subcategory hook.
     *
     * @hooked woocommerce_template_loop_category_link_open - 10
     */
    do_action( 'woocommerce_before_subcategory', $category );

    /**
     * Custom thumbnail replacement (replaces woocommerce_before_subcategory_title)
     */
    $thumbnail_id = get_woocommerce_term_meta($category->term_id, 'thumbnail_id', true);
    if ($thumbnail_id) {
        $full_image = wp_get_attachment_image_src($thumbnail_id, 'full');
        if ($full_image) {
            echo '<img src="' . esc_url($full_image[0]) . '" alt="' . esc_attr($category->name) . '" width="' . esc_attr($full_image[1]) . '" height="' . esc_attr($full_image[2]) . '" />';
        }
    } else {
        echo '<img src="' . wc_placeholder_img_src() . '" alt="' . esc_attr($category->name) . '" width="300" height="300" />';
        
    }

    /**
     * The woocommerce_shop_loop_subcategory_title hook.
     *
     * @hooked woocommerce_template_loop_category_title - 10
     */
    do_action( 'woocommerce_shop_loop_subcategory_title', $category );

    /**
     * The woocommerce_after_subcategory_title hook.
     */
    do_action( 'woocommerce_after_subcategory_title', $category );

    /**
     * The woocommerce_after_subcategory hook.
     *
     * @hooked woocommerce_template_loop_category_link_close - 10
     */
    do_action( 'woocommerce_after_subcategory', $category );
    ?>
</li>