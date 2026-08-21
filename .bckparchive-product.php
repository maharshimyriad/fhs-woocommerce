<?php

/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined('ABSPATH') || exit;

get_header('shop');

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action('woocommerce_before_main_content');

/**
 * Hook: woocommerce_shop_loop_header.
 *
 * @since 8.6.0
 *
 * @hooked woocommerce_product_taxonomy_archive_header - 10
 */
do_action('woocommerce_shop_loop_header');


$excluded_skus = array();

$extras_query = new WP_Query(array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => 'optional_extras',
            'value' => '',
            'compare' => '!=',
        ),
    ),
    'fields' => 'ids',
));

if ($extras_query->have_posts()) {
    foreach ($extras_query->posts as $extras_product_id) {
        $val = get_field('optional_extras', $extras_product_id);
        if (!empty($val)) {
            $skus = explode(',', $val);
            foreach ($skus as $sku) {
                $sku = trim($sku);
                if ($sku !== '') {
                    $excluded_skus[$sku] = true;
                }
            }
        }
    }
}

wp_reset_postdata();

global $wp_query;
$visible_total = 0;

if ($wp_query->have_posts()) {
    foreach ($wp_query->posts as $p) {
        $product = wc_get_product($p->ID);
        if (!$product) {
            continue;
        }
        $sku = $product->get_sku();
        if ($sku && isset($excluded_skus[$sku])) {
            continue;
        }
        $visible_total++;
    }
}

wc_set_loop_prop('total', $visible_total);



/**
 * Hook: woocommerce_before_shop_loop.
 *
 * @hooked woocommerce_output_all_notices - 10
 * @hooked woocommerce_result_count - 20
 * @hooked woocommerce_catalog_ordering - 30
 */
echo '<div class="products-wrapper single-custom-container">';


echo '<div class="filter-container">';
do_action('woocommerce_before_shop_loop');
echo '</div>';
echo '<div class="demo-div-0">';
echo '<div class="demo-div-1">';
echo '<div class="filter-form-header">';
echo '<h2 class="filter-heading">Refine</h2>';
echo '<div class="clear-form-container">';
echo '</div>';
echo '<div class="dropdown-icon-container">';
echo '<i class="icofont-rounded-down"></i>';
echo '</div>';
echo '</div>';
$body_classes = get_body_class();

if (in_array('tax-product_brand', $body_classes)) {
    $brands = get_terms(array(
        'taxonomy' => 'product_brand',
        'hide_empty' => false,
    ));
    if (!empty($brands) && !is_wp_error($brands)) {
        echo '<ul class="brands-filter">';
        foreach ($brands as $brand) {
            $current_term = get_term_by('slug', get_query_var('term'), 'product_brand');
            $current_term_id = $current_term ? $current_term->term_id : 0;
            $active_class = ($brand->term_id === $current_term_id) ? 'active' : '';
            $li_classes = 'brands-filter-item' . ($active_class ? ' ' . $active_class : '');
            echo '<li class="' . esc_attr($li_classes) . '"><a href="' . esc_url(get_term_link($brand)) . '">' . esc_html($brand->name) . '</a></li>';
			
        }
        echo '</ul>';
    } else {
        echo 'No brands found.';
    }
} else {
    echo do_shortcode('[wpf-filters id=12]'); 
}


echo '</div>';


if (woocommerce_product_loop()) {
    echo '<div class="demo-div-2">';
    woocommerce_product_loop_start();

    if (wc_get_loop_prop('total')) {
        while (have_posts()) {
            the_post();

            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
            $product_sku = $product ? $product->get_sku() : '';
			//Maharshi 
			//Excluded_sku code is above the filter-container in this file.
            if ($product_sku && isset($excluded_skus[$product_sku])) {
                continue;
            }

            do_action('woocommerce_shop_loop');
            wc_get_template_part('content', 'product');
        }
    }

/* ✅ ADD YOUR CUSTOM CATEGORY CARD HERE */
echo '<li class="product-category product pipe">
        <a href="https://fhs.com.au/pipe-fittings-and-sheet/">
            <img src="https://fhs.com.au/wp-content/uploads/2026/03/Pipe-Fittings-Sheet.png" alt="Pipe Fittings & Sheet">
            <div class="custom-category-heading-container">
                <div class="custom-category-heading">
                    <span>Pipe Fittings & Sheet</span>
                </div>
                <button class="custom-category-button">View More</button>
            </div>
        </a>
      </li>';
    woocommerce_product_loop_end();
	
    echo '</div>'; // demo-div-2

    echo '</div>'; // demo-div-0

    echo '</div>'; // products-wrapper

    /**
     * Hook: woocommerce_after_shop_loop.
     *
     * @hooked woocommerce_pagination - 10
     */
    do_action('woocommerce_after_shop_loop');
} else {
    /**
     * Hook: woocommerce_no_products_found.
     *
     * @hooked wc_no_products_found - 10
     */

    echo '<div class="demo-div-2">';
    do_action('woocommerce_no_products_found');
    echo '</div>'; // demo-div-2
    echo '</div>'; // demo-div-0
    echo '</div>'; // products-wrapper
}

/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action('woocommerce_after_main_content');

/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
do_action('woocommerce_sidebar');

get_footer('shop');
