<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

require_once get_stylesheet_directory() . '/woocommerce/single-product/delivery-enquiry-handler.php';
require_once get_stylesheet_directory() . '/inc/checkout-fields.php';

add_action('woocommerce_before_thankyou', 'show_pending_payment_error');

add_action('wp_head', function() {
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-241F22NJGZ"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-241F22NJGZ');
    </script>
    <?php
});

function show_pending_payment_error($order_id) {

    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    if ($order->has_status('pending')) {

        wc_print_notice(
            __('There\'s an issue with processing your payment and it\'s still pending. Please click on "Back to Orders" to try again.', 'woocommerce'),
            'error'
        );
        remove_all_filters('woocommerce_thankyou_order_received_text');
    }

}

// download-post

// submission of User registration form
//add_action('gform_confirmation_12', 'gf_form_12_alert', 10, 4);
function gf_form_12_alert($confirmation, $form, $entry, $ajax){

/*
    print_r($entry);

    if (class_exists('Opmc_Myob_Connector')) {


     //$email = $entry['11']; // Assuming email is in field 11
     $email = 'accounts@advancedpiping.com.au';$myobconnector = new Opmc_Myob_Connector();

      $result = $myobconnector->get_customer_with_email($email);
     //   $result = $myobconnector->get_customer_with_abn('90 796 677 400');

            echo 'in here';
        
          print_r($result);
            
            
            // error_log($result);
            
    }

    $script = "<script>
        alert('Form 12 submitted successfully!');
    </script>";

    return $confirmation . $script;
    */

}


function create_downloads_post_type() {
    $labels = array(
        'name'               => 'Downloads',
        'singular_name'      => 'Download',
        'menu_name'          => 'Downloads',
        'name_admin_bar'     => 'Download',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Download',
        'new_item'           => 'New Download',
        'edit_item'          => 'Edit Download',
        'view_item'          => 'View Download',
        'all_items'          => 'All Downloads',
        'search_items'       => 'Search Downloads',
        'not_found'          => 'No downloads found.',
        'not_found_in_trash' => 'No downloads found in Trash.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable'  => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'downloads'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'menu_icon'          => 'dashicons-download', // Custom icon in dashboard
    );

    register_post_type('downloads', $args);
}


function enqueue_jquery_ui() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
}
add_action('wp_enqueue_scripts', 'enqueue_jquery_ui');

add_action('init', 'create_downloads_post_type');

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );
// function register_primary_menu() {
//     register_nav_menu('primary', __('Primary Menu')); 
// 	register_nav_menu('secondary', __('Top-Menu'));
	
// }

// add_action('init', 'register_primary_menu');

function register_menus() {
    register_nav_menu('primary', __('Primary Menu')); 
	register_nav_menu('secondary', __('Secondary Menu'));
}
add_action('init', 'register_menus');



function load_custom_assets() {
	// select2 cnd added here start here
    if (class_exists('WooCommerce')) {
        wp_enqueue_script('selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full.min.js', array('jquery'), WC_VERSION, true);
        wp_enqueue_style('selectWoo', WC()->plugin_url() . '/assets/css/select2.css', array(), WC_VERSION);
		wp_enqueue_script('woocommerce-edit_address', get_stylesheet_directory_uri() . '/custom-assets/js/select2-init-file.js', array('jquery'), null, true);
		
    }
	// select2 cnd added here end here

   
    wp_enqueue_style('custom-style', get_stylesheet_directory_uri() . '/custom-assets/css/style.css');
	wp_enqueue_style('tabs-style', get_stylesheet_directory_uri() . '/custom-assets/css/tabs-style.css');
    
// 	wp_enqueue_style('main-style', get_stylesheet_directory_uri() . '/custom-assets/css/main.css');
	wp_enqueue_style('main-style', get_stylesheet_directory_uri() . '/custom-assets/css/product.css');
	wp_enqueue_style('custom-responsive-style', get_stylesheet_directory_uri() . '/custom-assets/css/responsive-style.css');
    
   
    wp_deregister_script('jquery');
    wp_register_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js', array(), '3.6.0', true);
    wp_enqueue_script('jquery');
    
   
    wp_enqueue_script('custom-script', get_stylesheet_directory_uri() . '/custom-assets/js/script.js', array('jquery'), null, true);
    wp_localize_script(
    'custom-script',
    'wpfDeliveryEnquiryData',
    array(
        'userEmail' => is_user_logged_in()
            ? wp_get_current_user()->user_email
            : '',
    )
);
	wp_enqueue_script('custom-tabs-script', get_stylesheet_directory_uri() . '/custom-assets/js/tabs-handle-script.js', array('jquery'), null, true);
	wp_enqueue_script('hire-page-dialog', get_stylesheet_directory_uri() . '/custom-assets/js/hire-page-dialog-handle.js', array('jquery'), null, true);
	wp_enqueue_script('modal-edit_address', get_stylesheet_directory_uri() . '/custom-assets/js/ajax-handle-with-nonce.js', array('jquery'), null, true);

    wp_localize_script('modal-edit_address', 'ajaxParams', array(
        'nonce' => wp_create_nonce('woocommerce-edit_address'),
        'ajaxurl' => admin_url('admin-ajax.php'),
		'siteurl' => get_site_url()
    ));
	wp_enqueue_script('my-acc-cart-handle', get_stylesheet_directory_uri() . '/custom-assets/js/cart-handle.js', array('jquery'), null, true);

    $wc_cart_params = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'update_cart_nonce' => wp_create_nonce('update-cart')
    );

    wp_localize_script('my-acc-cart-handle', 'wc_cart_params', $wc_cart_params);
}
add_action('wp_enqueue_scripts', 'load_custom_assets');

function enqueue_splidejs_scripts() {
    // Enqueue SplideJS CSS
    wp_enqueue_style( 'splide-css', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css' );
    
    // Enqueue SplideJS JavaScript
    wp_enqueue_script( 'splide-js', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', array(), null, true );
}
add_action( 'wp_enqueue_scripts', 'enqueue_splidejs_scripts' );



function exclude_products_from_secondary_menu($items, $args) {
    
    if ($args->theme_location == 'primary' && isset($args->exclude_products) && $args->exclude_products === true) {
        $exclude_ids = array();

        
        foreach ($items as $item) {
            if (in_array('products-megamenu', $item->classes)) {
                $exclude_ids[] = $item->ID;
            }
        }

        
        if (!empty($exclude_ids)) {
            $found = true;
            while ($found) {
                $found = false;
                foreach ($items as $item) {
                    if (in_array($item->menu_item_parent, $exclude_ids) && !in_array($item->ID, $exclude_ids)) {
                        $exclude_ids[] = $item->ID;
                        $found = true;
                    }
                }
            }
        }

        
        $filtered_items = array();
        foreach ($items as $item) {
            if (!in_array($item->ID, $exclude_ids)) {
                $filtered_items[] = $item;
            }
        }

        return $filtered_items;
    }

    
    return $items;
}
add_filter('wp_nav_menu_objects', 'exclude_products_from_secondary_menu', 10, 2);

function include_products_in_menu($items, $args) {
    
    if ($args->theme_location == 'primary' && isset($args->include_products) && $args->include_products === true) {
        $include_ids = array();
        
        
        foreach ($items as $item) {
            if (in_array('products-megamenu', $item->classes)) {
                $include_ids[] = $item->ID;
            }
        }
        
        
        if (!empty($include_ids)) {
            $found = true;
            while ($found) {
                $found = false;
                foreach ($items as $item) {
                    if (in_array($item->menu_item_parent, $include_ids) && !in_array($item->ID, $include_ids)) {
                        $include_ids[] = $item->ID;
                        $found = true;
                    }
                }
            }
        }
        
        
        $filtered_items = array();
        foreach ($items as $item) {
            if (in_array($item->ID, $include_ids)) {
                $filtered_items[] = $item;
            }
        }

        return $filtered_items;
    }
    
    return $items;
}
add_filter('wp_nav_menu_objects', 'include_products_in_menu', 10, 2);


// ------------ Categories page Custom code ------------------------  //
add_filter('woocommerce_get_breadcrumb', 'custom_brand_breadcrumb', 10, 2);

function custom_brand_breadcrumb($crumbs, $breadcrumb) {

    if ( is_tax('product_brand') || is_tax('brands') ) { // adjust taxonomy if needed

        $term = get_queried_object();

        $crumbs = array();

        // Home
        $crumbs[] = array('Home', home_url());

        // Our Brands page
        $crumbs[] = array('Brands', home_url('/brands/'));

        // Current Brand Name
        $crumbs[] = array($term->name, '');

    }

    return $crumbs;
}

remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
remove_action('woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10);
add_action('woocommerce_shop_loop_header', 'custom_banner_with_breadcrumbs', 5);

function custom_banner_with_breadcrumbs() {
    if (is_product_category() || is_tax('product_brand')) { 
        ob_start();
        woocommerce_breadcrumb(array(
            'delimiter' => '<span class="bradcrumbs-delimiter"> > </span> ', 
            'wrap_before' => '<nav class="custom-breadcrumbs">',
            'wrap_after' => '</nav>',
        ));
        $breadcrumbs = ob_get_clean();
        
        $current_term = get_queried_object();
        $archive_title = is_product_category() ? woocommerce_page_title(false) : $current_term->name;

        $banner_image_id = get_term_meta($current_term->term_id, 'category_detail_banner_image', true);
        
        $image_url = '';
        if ($banner_image_id) {
            $image_url = wp_get_attachment_url($banner_image_id);
        }

        echo '<div class="category-banner">';
        echo '<div class="single-custom-container">';
        echo '<div>';
        echo '<h2 class="archive-header">' . esc_html($archive_title) . '</h2>'; 
        echo $breadcrumbs;
        echo '</div>';

        if ($image_url) {
            echo '<div class="custom-category-image">';
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($archive_title) . ' Banner">';
            echo '</div>';
        } else {
            echo '<div class="custom-category-image">';
            echo '<img src="' . home_url() . '/wp-content/uploads/2025/02/malepolyfittings.png" alt="' . esc_attr($archive_title) . ' Banner">';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }
}


if (isset($_POST['cwea_save_account_details'])) {
    error_log("Form submitted. Manually triggering WooCommerce hook...");
    do_action('woocommerce_save_account_details', get_current_user_id());
}


add_filter('woocommerce_breadcrumb_defaults', 'custom_woocommerce_breadcrumbs_separator');
function custom_woocommerce_breadcrumbs_separator($defaults) {
  
    $defaults['delimiter'] = ' > ';
    return $defaults;
}


if ( ! function_exists( 'woocommerce_template_loop_category_title' ) ) {
    function woocommerce_template_loop_category_title( $category ) {
        
        $image_id = get_field( 'category_icon', 'product_cat_' . $category->term_id );

        
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';

        ?>
        <div class="custom-category-heading-container">
			<div class="custom-category-heading">
            <?php
            
            if ( $image_url ) {
				
                echo '<img class="category-heading-icon" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $category->name ) . '">';
            }
            echo '<span>';
            echo esc_html( $category->name );
            echo '</span>';
			
            ?>
			</div>
			<button class="custom-category-button">
				View More
			</button>
        </div>
        <?php
    }
}

add_action('woocommerce_after_main_content', 'add_custom_section_to_category_page');
function add_custom_section_to_category_page() {
    if (is_tax('product_cat')) {
        echo '<div class="custom-section-category">';
		
		if (class_exists('\Elementor\Plugin')) {
            echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display(14294); 
        }
		
		if (class_exists('\Elementor\Plugin')) {
            echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display(13897); 
        }
		
        if (class_exists('\Elementor\Plugin')) {
            echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display(13889); 
        }
        
        if (class_exists('\Elementor\Plugin')) {
            echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display(13892); 
        }

        echo '</div>';
    }
}

add_filter('body_class', 'add_category_type_to_body_class');
function add_category_type_to_body_class($classes) {
    // Check if this is a product category page
    if (is_tax('product_cat')) {
        $current_term = get_queried_object(); // Get the current category
        // Check for subcategories
        $children = get_terms(array(
            'taxonomy' => 'product_cat',
            'parent' => $current_term->term_id,
            'hide_empty' => false, // Include empty subcategories
        ));
        if (!empty($children)) {
            // If there are subcategories, add this class
            $classes[] = 'category-has-subcategories';
        } else {
            // If there are no subcategories, this is a leaf category with products
            $classes[] = 'category-is-leaf';
        }
    }
    return $classes;
}




add_filter('woocommerce_product_subcategories_args', 'hide_subcategory_products_on_parent');
function hide_subcategory_products_on_parent($args) {
    $args['parent'] = get_queried_object_id(); // Only show subcategories of the current category
    return $args;
}

add_action('woocommerce_before_shop_loop', 'remove_subcategory_products', 5);
function remove_subcategory_products() {
    if (is_product_category()) {
        add_filter('woocommerce_product_query', function ($query) {
            $query->set('tax_query', array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => get_queried_object_id(),
                    'operator' => 'IN',
                    'include_children' => false, // Prevents subcategory products from showing
                ),
            ));
        });
    }
}


function custom_woocommerce_catalog_orderby( $options ) {
    
    unset( $options['popularity'] ); 
    
    $options['price'] = 'Sort by cheapest first';

    $options['custom_sort'] = 'Sort by Custom Rule';

    return $options;
}
add_filter( 'woocommerce_catalog_orderby', 'custom_woocommerce_catalog_orderby' );
add_filter( 'woocommerce_default_catalog_orderby_options', 'custom_woocommerce_catalog_orderby' );


function custom_woocommerce_orderby_logic( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( is_shop() ) {
        if ( isset($_GET['orderby']) && $_GET['orderby'] == 'custom_sort' ) {
            $query->set('meta_key', '_price');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'ASC');
        }
    }

    if ( $query->is_search() ) {
        $query->set('post_type', array('product', 'page'));
    }
}
add_action( 'pre_get_posts', 'custom_woocommerce_orderby_logic' );

add_filter( 'posts_clauses', 'custom_search_orderby_product_price_desc', 10, 2 );
function custom_search_orderby_product_price_desc( $clauses, $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
        return $clauses;
    }

    global $wpdb;

    $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} AS search_price_meta
        ON ({$wpdb->posts}.ID = search_price_meta.post_id AND search_price_meta.meta_key = '_price')";

    $clauses['orderby'] = "CASE WHEN {$wpdb->posts}.post_type = 'product' THEN 0 ELSE 1 END ASC,
        CAST(search_price_meta.meta_value AS DECIMAL(20,6)) DESC,
        {$wpdb->posts}.post_title ASC";

    return $clauses;
}


////////nav custom code start
// Modify WooCommerce My Account menu items and order
add_filter('woocommerce_account_menu_items', 'custom_account_menu_items_order', 10, 1);
function custom_account_menu_items_order($items) {
    // Remove unwanted default items
    unset($items['dashboard']);
    unset($items['edit-account']); // We'll re-add it as "My Account"

    // Rebuild menu items in the desired order
    $new_items = array(
        'edit-account'    => $items['edit-account'] ?? __('My Account', 'astra-child'),
        'my-quotes'       => __('My Quotes', 'astra-child'),            // <-- Unified endpoint key
//         'orders-invoices' => $items['orders-invoices'] ?? __('My Orders', 'astra-child'),
        'orders' => $items['My Past orders'] ?? __('My Past Orders', 'astra-child'),
        'cart'            => $items['cart'] ?? __('My Shopping Cart', 'astra-child'),
        'wishlist'        => $items['wishlist'] ?? __('My Wish Lists', 'astra-child'),
        'edit-address'    => $items['edit-address'] ?? __('Shipping Address', 'astra-child'),
        'customer-logout' => $items['customer-logout'] ?? __('Logout', 'astra-child'),
    );

    return $new_items;
}

// Register custom endpoints
add_action('init', 'custom_register_my_account_endpoints');
function custom_register_my_account_endpoints() {
    $endpoints = array(
//         'orders-invoices',
        'orders',
        'invoices',
        'my-quotes', // unified quotes endpoint
        'cart',
    );

    foreach ($endpoints as $endpoint) {
        add_rewrite_endpoint($endpoint, EP_ROOT | EP_PAGES);
    }
}

// Add query var for the quotes endpoint
add_filter('query_vars', function($vars) {
    $vars[] = 'my-quotes';
    return $vars;
});

// Display content for "My Quotes" endpoint
add_action('woocommerce_account_my-quotes_endpoint', function() {
    echo do_shortcode('[stars_quote_page]');
});



add_action('woocommerce_account_invoices_endpoint', 'invoices_content');
// invoice code starts here------------------------------------------------------------------------------------start
function invoices_content() {
    global $wpdb;

    $user_id = get_current_user_id();
    $customer_id = get_the_author_meta('myob_customer_id', $user_id);

    $table_name = $wpdb->prefix . 'myob_invoices';

    echo '<div class="invoices-main-container">';
    echo '<div class="invoices-header-container">';
    echo '<h2>My Invoices</h2>';
    echo '<div class="invoice-filter-container">';
    echo '<form action="" id="invoice-header-filter" method="get">';
    echo '<div class="invoice-filters">';
    echo '<div class="date-range-picker-container">';
    echo '<img src="https://fhs.com.com/wp-content/uploads/2025/04/calendar-1.svg">';
    echo '<input class="form-control form-control-solid" id="kt_daterangepicker_5" placeholder="Pick Date Range" autocomplete="off">';
    echo '<input type="hidden" id="start_date" name="start_date">';
    echo '<input type="hidden" id="end_date" name="end_date">';
    echo '</div>';

    $status_rows = $wpdb->get_col("SELECT DISTINCT status FROM $table_name WHERE customer_id = '$customer_id'");
    echo '<select name="status" id="invoice-status">';
    echo "<option value=''>Status</option>";
    foreach ($status_rows as $status) {
        $selected = (isset($_GET['status']) && $_GET['status'] === $status) ? 'selected' : '';
        echo "<option value='" . esc_attr($status) . "' $selected>" . esc_html(ucwords($status)) . "</option>";
    }
    echo '</select>';

    echo '</div></form></div></div>';

    if (empty($customer_id)) {
        echo '<div class="woocommerce-info"><p>No customer ID found for your account.</p></div></div>';
        return;
    }

	$per_page = 10;
    $paged = $_GET['pg'] ?? 1;
    $offset = ($paged - 1) * $per_page;

    $conditions = ["customer_id = %s"];
    $params = [$customer_id];

    if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
        $start = DateTime::createFromFormat('d-M-Y', sanitize_text_field($_GET['start_date']));
        $end = DateTime::createFromFormat('d-M-Y', sanitize_text_field($_GET['end_date']));
        if ($start && $end) {
            $conditions[] = "date BETWEEN %s AND %s";
            $params[] = $start->format('Y-m-d');
            $params[] = $end->format('Y-m-d');
        }
    }

    if (!empty($_GET['status'])) {
        $conditions[] = "status = %s";
        $params[] = sanitize_text_field($_GET['status']);
    }

    $where_clause = implode(' AND ', $conditions);

    $count_query = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $where_clause", $params);
    $total_items = $wpdb->get_var($count_query);
    $total_pages = ceil($total_items / $per_page);

    $query = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE $where_clause ORDER BY date DESC LIMIT %d OFFSET %d",
        array_merge($params, [$per_page, $offset])
    );
    $invoices = $wpdb->get_results($query);

    if (empty($invoices)) {
        echo '<div class="woocommerce-message woocommerce-info">No invoices have been found yet.</div></div>';
        return;
    }

    echo '<table class="woocommerce-orders-table woocommerce-MyAccount-invoices shop_table">
            <thead><tr>
                <th>#INVOICE</th>
                <th>DATE</th>
                <th>DUE DATE</th>
                <th>Purchase Order Number</th>
                <th>AMOUNT</th>
                <th>OUTSTANDING</th>
                <th>STATUS</th>
                <th>ACTION</th>
            </tr></thead><tbody>';

    foreach ($invoices as $invoice) {
        echo '<tr>
            <td>#' . esc_html($invoice->invoice_number) . '</td>
            <td>' . esc_html(date('d/m/Y', strtotime($invoice->date))) . '</td>
            <td>' . (!empty($invoice->due_date) ? esc_html(date('d/m/Y', strtotime($invoice->due_date))) : '-') . '</td>
            <td>' . esc_html($invoice->po_number) . '</td>
            <td>$' . number_format($invoice->amount, 2) . '</td>
            <td>$' . number_format($invoice->outstanding, 2) . '</td>
            <td>' . format_invoice_status($invoice->status) . '</td>
            <td><a class="download-invoice-pdf" href="/wp-json/invoice/file_download?invoice_uid=' . esc_attr($invoice->myob_uid) . '&type=' . esc_attr($invoice->invoice_type) . '" target="_blank"><i class="icofont-eye-alt"></i></a></td>
        </tr>';
    }

    echo '</tbody></table>';

    if ($total_pages > 1) {
        echo '<div class="woocommerce-pagination">';
		echo paginate_links(array(
			'base' => trailingslashit(home_url('/my-account/invoices')) . '?pg=%#%',
			'format' => '',
			'current' => $paged,
			'total' => $total_pages,
			'prev_text' => __('« Prev'),
			'next_text' => __('Next »'),
		));
        echo '</div>';
    }

    echo '</div>';
}

function format_invoice_status($status) {
    $class = strtolower($status);
   
    return '<span class="invoice-status ' . $class . '">' . strtoupper(esc_html($status)) . '</span>';

}
// invoice code ends here-------------------------------------------------------------------------------------end

add_action('woocommerce_account_quotes_endpoint', 'quotes_content');
function quotes_content() {
    echo '<h3>My Quotes</h3>';
    echo '<p>Display quotes here (requires a custom implementation or plugin).</p>';
}
add_action('woocommerce_account_cart_endpoint', 'cart_content');
function cart_content() {
	echo '<div class="custom-cart-container">';
    echo do_shortcode('[woocommerce_cart]');
	echo '</div>';
}

// Flush permalinks after adding endpoints
add_action('init', 'flush_rewrite_rules_on_init');
function flush_rewrite_rules_on_init() {
    flush_rewrite_rules();
}

//// nav custom code end
//


add_filter('elementor/frontend/the_content', function ($content) {
    return do_shortcode($content);
});




function add_custom_variation_tabs() {
    global $product;
    
    if ($product->is_type('variable')) {
        
        $attribute_name = 'material';
        $attribute_values = $product->get_attribute($attribute_name);
        
        if ($attribute_values) {
            echo '<div class="custom-variations-container">';
            echo '<div class="variatoins-label">
			<span>' . esc_html($attribute_name) . ': </span>
			<span class="dynamic-material-input-content var-dynamic-content"></span>
			</div>';
            echo '<div class="variations-tabs">';
            $values = explode('|', $attribute_values);
            foreach ($values as $value) {
                $value = trim($value);
                echo '<p class="variation-tab" data-value="' . esc_attr($value) . '">' 
					. esc_html($value) . 
					'</p>';
            }
            echo '</div>';
            echo '</div>';
        }

        
        $attribute_name = 'color';
        $attribute_values = $product->get_attribute($attribute_name);
        
        if ($attribute_values) {
            echo '<div class="custom-color-container">';
            echo '<div class="variatoins-label">
			<span>' . esc_html($attribute_name) . ': </span>
			<span class="dynamic-color-input-content var-dynamic-content"></span>
			</div>';
            echo '<div class="color-options">';
            $values = explode('|', $attribute_values);
            foreach ($values as $value) {
                $value = trim($value);
                echo '<div class="color-option" data-color="' . esc_attr($value) . '" 
						style="--shadow-color: ' . esc_attr($value) . '; background-color: ' . esc_attr($value) . ';">
					</div>';

            }
            echo '</div>';
            echo '</div>';
        }
    }
}

add_action('woocommerce_before_variations_form', 'add_custom_variation_tabs');






add_filter('the_author', 'display_author_first_name');
function display_author_first_name($display_name) {
//     if (is_admin()) return $display_name; // Keep username in admin area
    $user_id = get_the_author_meta('ID');
    $first_name = get_the_author_meta('first_name', $user_id);
    return $first_name ? $first_name : $display_name; // Fallback to display name if no first name
}

// adding a wishlist after a cart 



// Redirect To The Current Page After Login - Starts

add_filter('login_redirect', 'custom_login_redirect', 10, 3);
function custom_login_redirect($redirect_to, $request, $user) {
    if (!empty($redirect_to) && strpos($redirect_to, home_url()) !== false) {
        return esc_url_raw($redirect_to);
    }

    return home_url('/my-account/edit-account');
}

// Redirect To The Current Page After Login - Ends




// add_action('elementor/widget/posts/skins_init', function($widget) {
//     class Custom_Card_Skin extends \ElementorPro\Modules\Posts\Skins\Skin_Cards {
//         protected function render_post_meta() {
//             // Original metadata rendering logic
//             parent::render_post_meta();
            
//             // Add avatar
//             echo sprintf(
//                 '<div class="elementor-post-avatar">%s</div>',
//                 get_avatar(get_the_author_meta('ID'), 48)
//             );
//         }
//     }
    
//     $widget->add_skin(new Custom_Card_Skin($widget));
// });

function custom_login_register_button() {
  $myaccount_url = wc_get_page_permalink('myaccount');

  if (is_user_logged_in()) {
      $current_user = wp_get_current_user();
      $first_name = !empty($current_user->first_name) ? $current_user->first_name : $current_user->display_name;
      $button_text = '<span class="user-icon"><i class="icofont-ui-user"></i></span>' . esc_html($first_name);
  } else {
      $button_text = '<i class="icofont-ui-user"></i><span class="user-icon">Login / Register</span>' ;
  }

  return '<a href="' . esc_url($myaccount_url) . '" class="custom-button">' . $button_text . '</a>';
}
add_shortcode('login_register_button', 'custom_login_register_button');
add_action('yith_wcwl_before_wishlist_title', 'add_navbar_in_wishlist');
function add_navbar_in_wishlist() {
	echo "<div class='ms-custom-wishlist-flex-box'>";
    wc_get_template('myaccount/navigation.php');
    echo "<div class='wishlist-content-handler'>";

    echo "<script>
            $('.woocommerce-MyAccount-navigation-link--wishlist').addClass('is-active');
    </script>";
}

add_action('yith_wcwl_deleted_wishlist', 'custom_redirect_after_wishlist_delete', 10, 1); 
//this is a action for handling a redirection after deleting wishlist 
function custom_redirect_after_wishlist_delete($wishlist_id) {
    wp_redirect(home_url('/wishlist/'));
    exit;
}

add_filter( 'gform_confirmation', 'custom_confirmation', 10, 4 );
function custom_confirmation( $confirmation, $form, $entry, $ajax ) {
    if( $form['id'] == '6' ) {
		$current_url = home_url('butt-welders');
        $confirmation = array( 'redirect' => $current_url );
    }
    return $confirmation;
}

add_filter('woocommerce_get_breadcrumb', function ($crumbs) {
    foreach ($crumbs as $key => $crumb) {
        if (strpos($crumb[1], 'category/uncategorized') !== false) {
            $crumbs[$key] = ['Blogs', site_url('/blogs/')]; 
        }
    }
    return $crumbs;
});

// this is a code for cart edit ajax handle 
add_action('wp_ajax_update_cart_item_quantity', 'update_cart_item_quantity_callback');
add_action('wp_ajax_nopriv_update_cart_item_quantity', 'update_cart_item_quantity_callback');

function update_cart_item_quantity_callback() {
    check_ajax_referer('update-cart', 'security');

    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
    $quantity = intval($_POST['quantity']);

    $cart = WC()->cart;
    $cart_item = $cart->get_cart_item($cart_item_key);

    if ($cart_item && $quantity >= 0) {
        $cart->set_quantity($cart_item_key, $quantity, true);
        WC()->cart->calculate_totals();
        WC()->cart->maybe_set_cart_cookies();

        // Return success response
        wp_send_json_success(array('message' => 'Cart updated successfully'));
    } else {
        wp_send_json_error(array('message' => 'Invalid cart item or quantity'));
    }

    wp_die();
}

//this is for adding a header to cart table section
add_action('woocommerce_cart_collaterals_custom', 'custom_cart_price_details', 10);

function custom_cart_price_details() {

    if ( ! WC()->cart ) return;

    // Force recalculation
    WC()->cart->calculate_totals();

    $cart = WC()->cart;

    ob_start();
    ?>
    <div class="custom-cart-totals">
        <h2><span class="icofont-price"></span> Price Details</h2>
<table class="shop_table shop_table_responsive custom-price-table">
    <tbody>

        <!-- Subtotal -->
        <tr class="cart-subtotal">
            <th>
                <?php echo esc_html( 'Subtotal (' . $cart->get_cart_contents_count() . ' items)' ); ?>
            </th>
            <td data-title="Subtotal">
                <?php echo wc_price( $cart->get_subtotal() ); ?>
                <span class="gst-message">(Ex GST)</span>
            </td>
        </tr>

        <!-- Discount -->
        <?php if ( $cart->get_discount_total() > 0 ) : ?>
        <tr class="cart-discount">
            <th><?php esc_html_e( 'Total Discount', 'woocommerce' ); ?></th>
            <td data-title="Discount">
                <?php echo wc_price( -$cart->get_discount_total() ); ?>
            </td>
        </tr>
        <?php endif; ?>

        <!-- Fees -->
        <?php foreach ( $cart->get_fees() as $fee ) : ?>
        <tr class="cart-fee">
            <th><?php echo esc_html( $fee->name ); ?></th>
            <td data-title="<?php echo esc_attr( $fee->name ); ?>">
                <?php echo wc_price( $fee->amount ); ?>
            </td>
        </tr>
        <?php endforeach; ?>

        <!-- Shipping -->
        <tr class="cart-shipping">
            <th><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></th>
            <td data-title="Shipping">
                <?php esc_html_e( 'Shipping will be calculated on checkout', 'woocommerce' ); ?>
            </td>
        </tr>

        <!-- Tax -->
        <?php if ( wc_tax_enabled() ) : ?>
        <tr class="cart-tax">
            <th><?php esc_html_e( 'GST', 'woocommerce' ); ?></th>
            <td data-title="GST">
                <?php echo wc_price( $cart->get_total_tax() ); ?>
            </td>
        </tr>
        <?php endif; ?>

        <!-- Grand Total -->
        <tr class="order-total">
            <th><?php esc_html_e( 'Grand Total', 'woocommerce' ); ?></th>
            <td data-title="Total">
                <strong><?php echo wc_price( $cart->get_total( 'edit' ) ); ?></strong>
                <span class="gst-message">(Inc GST)</span>
            </td>
        </tr>

    </tbody>
</table>

        <div class="secure-cart">
            <i class="icofont-safety"></i>
            <p>Safe and Secure Payments. Trusted Australian Industry Supplier.</p>
        </div>

        <div class="wc-proceed-to-checkout">
            <?php do_action('woocommerce_proceed_to_checkout'); ?>
        </div>
    </div>
    <?php

    echo ob_get_clean();
}



// product download
add_action('woocommerce_single_product_summary', 'show_acf_product_download', 25);

function show_acf_product_download() {
    $download = get_field('product_download');

    if ($download) {
        $download_url = is_array($download) ? $download['url'] : $download;
        $download_name = is_array($download) ? $download['filename'] : 'Download File';

        echo '<p><a href="' . esc_url($download_url) . '" class="button" download>' . esc_html($download_name) . '</a></p>';
    }
}

// Product Faq

$product_id = 123; // change to actual product ID
if (have_rows('product_faq', $product_id)):
    while (have_rows('product_faq', $product_id)): the_row();
        $question = get_sub_field('question');
        $answer = get_sub_field('answer');
        echo '<p><strong>' . esc_html($question) . '</strong><br>' . wp_kses_post($answer) . '</p>';
    endwhile;
endif;

// for adding a heading and filter 
add_action('woocommerce_before_cart_table','cart_section_header_and_filter',10);
function cart_section_header_and_filter(){
	echo '<div class="cart-heading-wrapper">';
	echo '<h3>My Shopping Cart</h3>';
	echo '<div class="filter-wrapper">';
	echo '  <div class="search-box">';
	echo '    <input type="text" id="cart-search" placeholder="Search by name..." />';
	echo '    <span class="search-icon"><i class="icofont-search-1"></i></span>';
	echo '  </div>';
	echo '</div>';
	echo '</div>';
}

// cart product merge 
add_action('woocommerce_before_calculate_totals', 'merge_duplicated_products_in_cart', 20, 1);

function merge_duplicated_products_in_cart($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (did_action('woocommerce_before_calculate_totals') >= 2) return;

    $items_data = [];

    // Loop through cart items
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product_id = $cart_item['data']->get_id();

        // If product already exists, increase its quantity
        if (array_key_exists($product_id, $items_data)) {
            $new_quantity = $items_data[$product_id]['quantity'] + $cart_item['quantity'];
            $cart->set_quantity($items_data[$product_id]['key'], $new_quantity);
            $cart->remove_cart_item($cart_item_key); // Remove the duplicate
        } else {
            // Store the product info for future checks
            $items_data[$product_id] = array(
                'key' => $cart_item_key,
                'quantity' => $cart_item['quantity']
            );
        }
    }
}

//this is for removing a column from order table
add_action( 'plugins_loaded', function() {
    global $wp_filter;

    if ( isset( $wp_filter['woocommerce_account_orders_columns'] ) ) {
        foreach ( $wp_filter['woocommerce_account_orders_columns']->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $key => $callback ) {
                if ( is_array( $callback['function'] ) && is_object( $callback['function'][0] ) ) {
                    $object = $callback['function'][0];
                    $method = $callback['function'][1];

                    if ( method_exists( $object, 'add_my_account_orders_column' ) ) {
                        remove_filter( 'woocommerce_account_orders_columns', array( $object, 'add_my_account_orders_column' ), $priority );
                    }
                }
            }
        }
    }
} );


//this is a testing code for resolve cart redirection
add_action('init', 'custom_add_cart_endpoint');

function custom_add_cart_endpoint() {
    add_rewrite_endpoint('cart', EP_PAGES);
}

add_action('after_switch_theme', 'flush_rewrite_rules');
add_action('after_switch_theme', 'flush_rewrite_rules');

add_action('woocommerce_my_account_my_orders_column_reorder', 'custom_reorder_redirect', 20);
function custom_reorder_redirect() {
    wp_safe_redirect(home_url('/my-account/cart/'));
    exit;
}


add_filter('woocommerce_get_cart_url', 'custom_cart_url');
function custom_cart_url($cart_url) {
    return home_url('/my-account/cart/');
}
//code end

//this is a code adding a date filter 
// Add date filter fields before "My Account" orders table



add_action('woocommerce_before_account_orders', 'add_date_filter_form');

function add_date_filter_form($has_orders) {
    echo '<div class="order-filter-container">';
    echo '<h2>My Past Orders</h2>';
    
    echo '<form action="#" id="order-header-filter" method="get">';
	echo '<div class="order-filters">';
	echo '<div class="date-range-picker-container">';
		echo '<img src="https://fhs.com.au/wp-content/uploads/2025/04/calendar-1.svg">';
		echo '
		<input class="form-control form-control-solid" id="kt_daterangepicker_4" placeholder="Pick Date Range" autocomplete="off">
		<input type="hidden" id="start_date" name="start_date">
		<input type="hidden" id="end_date" name="end_date">
		';


	
	echo '</div>';
//     echo '<select name="status" id="order-status">';
//     echo "<option value=''>Status</option>";
//     foreach (wc_get_order_statuses() as $key => $value) {
//         echo "<option value='" . esc_attr($key) . "' " . selected($_GET['status'], $key, false) . ">" . esc_html($value) . "</option>";
//     }
//     echo '</select>';
	echo '</div>'; // filter div end
    echo '</form>';
    
    echo '</div>';
}

// Modify query to filter orders by date range, status, and search by order ID
add_filter('woocommerce_my_account_my_orders_query', 'filter_my_account_orders_by_date_status_and_order_id', 10, 1);

function filter_my_account_orders_by_date_status_and_order_id($query_args) {
    // Date filter
if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {

    $start_date = sanitize_text_field($_GET['start_date']);
    $end_date   = sanitize_text_field($_GET['end_date']);

    $query_args['date_query'] = array(
        array(
            'after'     => $start_date . ' 00:00:00',
            'before'    => $end_date . ' 23:59:59',
            'inclusive' => true,
        ),
    );
}

    // Status filter
		if (!empty($_GET['status'])) {

			$status = sanitize_text_field($_GET['status']);
			$valid_statuses = array_keys(wc_get_order_statuses());

			if (in_array($status, $valid_statuses)) {
				$query_args['post_status'] = $status;
			}
		}

    // ONLY reset to page 1 when filters are FIRST applied (not on pagination clicks)
    // This is the key fix:
    if (
        (!empty($_GET['start_date']) || !empty($_GET['end_date']) || !empty($_GET['status'])) 
        && empty($_GET['paged']) // Only reset if paged parameter isn't set (first filter submission)
    ) {
        $query_args['paged'] = 1;
    }

    return $query_args;
}
// end of code section
//add a read-more button
add_action( 'custom_add_before_search_content', 'custom_astra_read_more_button' );
function custom_astra_read_more_button() {
    if ( is_search() ) {
        global $post;
        $search_term = strtolower( trim( get_search_query() ) );

        if ( $search_term === 'download' ) {
            $download_url = get_field( 'download_file', $post->ID ); // ACF field

            if ( $download_url ) {
                echo '<a href="' . esc_url( $download_url ) . '" class="astra-download-button" download target="_blank">
                        <span class="icofont-download"></span> Download
                      </a>';
            } else {
                // Fallback if download file not found
                echo '<a href="' . get_permalink() . '" class="astra-read-more-button">Read More</a>';
            }
        } else {
            echo '<a href="' . get_permalink() . '" class="astra-read-more-button">Read More</a>';
        }
    }
}

//end code section

// attemp to override function from blog.php 
function astra_blog_post_thumbnail_and_title_order( $remove_elements = array() ) {

	$blog_post_thumb_title_order = astra_get_option( 'blog-post-structure' );

	$remove_post_element = apply_filters( 'astra_remove_post_elements', $remove_elements );

	if ( isset( $blog_post_thumb_title_order ) && isset( $remove_post_element ) ) {
		foreach ( $remove_post_element as $single ) {
			$key = array_search( $single, $blog_post_thumb_title_order );
			if ( ( $key ) !== false ) {
				unset( $blog_post_thumb_title_order[ $key ] );
			}
		}
	}

	if ( is_singular() ) {
		return astra_banner_elements_order();
	}

	if ( is_array( $blog_post_thumb_title_order ) ) {
		$inside_wrapper = false;

		foreach ( $blog_post_thumb_title_order as $post_thumb_title_order ) {
			switch ( $post_thumb_title_order ) {

				case 'image':
					do_action( 'astra_blog_archive_featured_image_before' );
					astra_get_blog_post_thumbnail( 'archive' );
					do_action( 'astra_blog_archive_featured_image_after' );
					break;

				default:
					// Open the wrapper before the first non-image element.
					if ( ! $inside_wrapper ) {
						echo "<div class='ms-custom-content-wrapper'>";
						$inside_wrapper = true;
					}

					switch ( $post_thumb_title_order ) {
						case 'category':
							do_action( 'astra_blog_archive_category_before' );
							echo astra_post_categories( 'astra_blog_archive_category', 'blog-category-style', true );
							do_action( 'astra_blog_archive_category_after' );
							break;

						case 'tag':
							do_action( 'astra_blog_archive_tag_before' );
							echo astra_post_tags( 'astra_blog_archive_tag', 'blog-tag-style', true );
							do_action( 'astra_blog_archive_tag_after' );
							break;

						case 'title':
							do_action( 'astra_blog_archive_title_before' );
							astra_get_blog_post_title();
							do_action( 'astra_blog_archive_title_after' );
							break;

						case 'title-meta':
							do_action( 'astra_blog_archive_title_meta_before' );
							astra_get_blog_post_title_meta();
							do_action( 'astra_blog_archive_title_meta_after' );
							break;

						case 'excerpt':
							do_action( 'astra_blog_archive_excerpt_before' );
							astra_the_excerpt();
							do_action( 'astra_blog_archive_excerpt_after' );
							break;

						case 'read-more':
							do_action( 'astra_blog_archive_read_more_before' );
							astra_post_link();
							do_action( 'astra_blog_archive_read_more_after' );
							break;
					}
					break;
			}
		}

		// Close wrapper if it was opened
		if ( $inside_wrapper ) {
			do_action( 'custom_add_before_search_content' );
			echo "</div>";
		}
	}
}
// end section

// end section

add_action('template_redirect', function() {
    if (is_search()) {
        error_log('Search Query: ' . get_search_query());
    }
});



/*
function new_contact_methods( $contactmethods ) {
    $contactmethods['phone'] = 'Phone Number';
    return $contactmethods;
}
add_filter( 'user_contactmethods', 'new_contact_methods', 10, 1 );
*/


// This is for adding a MYOB UID column in the admin side ------------------------------------------start
function new_modify_user_table( $columns ) {
    $columns['myob_uid'] = 'MYOB UID';
    $columns['myob_payment_terms'] = 'Payment Terms';
    $columns['myob_user_designation'] = 'User Designation';
    return $columns;
}
add_filter( 'manage_users_columns', 'new_modify_user_table' );

// This is for adding values to the newly created columns --------------------------------------start
function new_modify_user_table_row( $val, $column_name, $user_id ) {
    switch ($column_name) {
        case 'myob_uid' :
            return get_the_author_meta( 'myob_customer_id', $user_id );
        
        case 'myob_payment_terms' :
            return get_the_author_meta( 'myob_payment_terms', $user_id );
            
        case 'myob_user_designation' :
            return get_the_author_meta( 'myob_user_designation', $user_id );
        
        default:
            return $val;
    }
}
add_filter( 'manage_users_custom_column', 'new_modify_user_table_row', 10, 3 );
// -----------------------------------------------end

add_filter( 'woocommerce_catalog_orderby', 'custom_catalog_orderby_options' );

function custom_catalog_orderby_options( $options ) {
    $options = array(
        'menu_order'  => 'Default sorting',
        'rating'      => 'Top Rated',
        'date'        => 'Newest First',
        'price'       => 'Lowest Price',
        'price-desc'  => 'Highest Price'
    );
    return $options;
}


add_action('template_redirect', function() {
    if ($_SERVER['REQUEST_URI'] == '/cart/') {
        wp_redirect('/my-account/cart/', 301);
        exit();
    }
});

// 1. Add a "Product Order Number" field to checkout----------------------------------------------

add_action('woocommerce_after_order_notes', function($checkout) {
//     echo '<div id="product_order_number_field"><h3>' . __('Product Order Number') . '</h3>';

//     woocommerce_form_field('product_order_number', array(
//         'type'          => 'text',
//         'class'         => array('form-row-wide'),
//         'label'         => __('Enter Product Order Number'),
//         'placeholder'   => __('e.g., 123456'),
//         'required'      => true, // Set to false if optional
//     ), $checkout->get_value('product_order_number'));

//     echo '</div>';
// });

// 2. Save the "Product Order Number" field value
// add_action('woocommerce_checkout_update_order_meta', function($order_id) {
//     if (!empty($_POST['product_order_number'])) {
//         update_post_meta($order_id, '_product_order_number', sanitize_text_field($_POST['product_order_number']));
//     }
});

// 3. Display the "Product Order Number" field value in the order admin
add_action('woocommerce_admin_order_data_after_billing_address', function($order){
    $product_order_number = get_post_meta($order->get_id(), '_product_order_number', true);
    if ($product_order_number) {
        echo '<p><strong>' . __('Product Order Number') . ':</strong> ' . esc_html($product_order_number) . '</p>';
    }
});

// Show "Product Order Number" in My Account → Orders table
// Add custom columns to My Account > Orders
// Add custom columns: Pay Now and Product Order Number
add_filter('woocommerce_my_account_my_orders_columns', function($columns) {
    $new_columns = array();

    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;

        if ('order-total' === $key) {
            $user_id = get_current_user_id();
            $payment_terms = get_user_meta($user_id, 'myob_payment_terms', true);
            
            if ($payment_terms === 'DayOfMonthAfterEOM') {
                $new_columns['pay_now'] = __('Pay Now');
            }

            $new_columns['product_order_number'] = __('Product Order Number');
            $new_columns['required_date'] = __('Required Date');
        }
    }

    return $new_columns;
}, 20);

// Display Product Order Number
add_action('woocommerce_my_account_my_orders_column_product_order_number', function($order) {
    $product_order_number = get_post_meta($order->get_id(), '_product_order_number', true);
    echo $product_order_number ? esc_html($product_order_number) : '-';
});

// Display Required Date
add_action('woocommerce_my_account_my_orders_column_required_date', function($order) {
    $required_date = get_post_meta($order->get_id(), '__order_required_date', true);
    echo $required_date ? esc_html($required_date) :  esc_html('-');
});

// Display Pay Now Button
add_action('woocommerce_my_account_my_orders_column_pay_now', function($order) {
    if ($order->has_status(array('pending', 'failed'))) {
        echo '<a class="button pay" href="' . esc_url($order->get_checkout_payment_url()) . '">' . __('Pay Now') . '</a>';
    } else {
        echo '-';
    }
});

// Remove default "Pay" and "Cancel" from Actions
add_filter('woocommerce_my_account_my_orders_actions', function($actions, $order) {
    if ($order->has_status(array('pending', 'failed'))) {
        unset($actions['pay']);
        unset($actions['cancel']);
    }
    return $actions;
}, 10, 2);


// 1. Add a "Product Order Number" field to checkout----------------------------------------------end

// this is for relocating a payment section ------------------------------------------------------start
remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);
add_action('woocommerce_custom_payment_relocation', 'woocommerce_checkout_payment', 20);
// this is for relocating a payment section ------------------------------------------------------end

// Adding po number and required date column in checkout page and ordering it correctly-------------------------------start
add_filter('woocommerce_checkout_fields', 'reorder_and_customize_billing_fields', 10, 1);
function reorder_and_customize_billing_fields($fields) {
    // Define the desired order with priorities
    $fields['billing']['billing_first_name']['priority'] = 10;
    $fields['billing']['billing_last_name']['priority'] = 20;
    $fields['billing']['billing_phone']['priority'] = 30;
    $fields['billing']['billing_email']['priority'] = 40;
    
    $fields['billing']['billing_address_1']['priority'] = 60;
    $fields['billing']['billing_address_2']['priority'] = 65;
    $fields['billing']['billing_city']['priority'] = 70;
    $fields['billing']['billing_postcode']['priority'] = 80;
    $fields['billing']['billing_country']['priority'] = 90;

    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['required'] = true;
        $fields['billing']['billing_phone']['label'] = __('Phone Number', 'woocommerce');
        $fields['billing']['billing_phone']['custom_attributes']['required'] = 'required';
        $fields['billing']['billing_phone']['validate'] = array_values(array_unique(array_merge(
            isset($fields['billing']['billing_phone']['validate']) && is_array($fields['billing']['billing_phone']['validate'])
                ? $fields['billing']['billing_phone']['validate']
                : [],
            ['required']
        )));
    }

    // Set labels for address fields
    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['label'] = __('Address Line 1', 'woocommerce');
    }
    
    if (isset($fields['billing']['billing_address_2'])) {
        $fields['billing']['billing_address_2']['label'] = __('Address Line 2', 'woocommerce');
    }

    // Billing edits should not trigger checkout totals refresh; shipping drives totals.
    if (!empty($fields['billing']) && is_array($fields['billing'])) {
        foreach ($fields['billing'] as $billing_key => $billing_field) {
            if (empty($fields['billing'][$billing_key]['class']) || !is_array($fields['billing'][$billing_key]['class'])) {
                continue;
            }
            $fields['billing'][$billing_key]['class'] = array_values(array_filter(
                $fields['billing'][$billing_key]['class'],
                function ($class_name) {
                    return 'update_totals_on_change' !== $class_name;
                }
            ));
        }
    }
    
    // Prevent THWMA checkout checkbox logic from overriding the custom
    // "Use same as billing" flow in the shipping template.
    unset($fields['billing']['thwma_hidden_field_billing']);
    unset($fields['billing']['thwma_checkbox_shipping']);
    
    
    unset($fields['billing']['billing_po_number']);
    unset($fields['billing']['billing_required_date']);
    // unset($fields['billing']['billing_state']); 
    
    return $fields;
}


add_action('woocommerce_checkout_update_order_meta', 'save_purchase_order_number');
function save_purchase_order_number($order_id) {
    if (!empty($_POST['billing_po_number'])) {
			update_post_meta($order_id, '_product_order_number', sanitize_text_field($_POST['billing_po_number']));
	}
	
	if (!empty($_POST['fhs_required_date'])) {
        update_post_meta($order_id, '__order_required_date', sanitize_text_field($_POST['fhs_required_date']));
    }
}


add_filter('gettext', 'force_billing_postcode_label_translation', 1000, 3);
function force_billing_postcode_label_translation($translated_text, $text, $domain) {
    if ($domain === 'woocommerce') {
        if ($text === 'PIN Code' || $translated_text === 'PIN Code') {
            $translated_text = "Postcode";
        }
        if ($text === 'Phone' || $translated_text === 'Phone') {
            $translated_text = "Phone Number";
        }
		
		 if ($text === 'Town / City' || $translated_text === 'Town / City') {
            $translated_text = "Suburb / City";
        }
		
		 if ($text === 'Street address' || $translated_text === 'Street address') {
            $translated_text = "Address Line 1";
        }

    }
    return $translated_text;
}
// Adding po number and required date column in checkout page and ordering it correctly-------------------------------end

//add a outstanding amount column in orders page-----------------------------------------------------------------start
// Add "Outstanding Amount" column to My Account > Orders table
add_filter( 'woocommerce_my_account_my_orders_columns', 'add_outstanding_amount_column' );
function add_outstanding_amount_column( $columns ) {
    $new_columns = [];
    foreach ( $columns as $key => $name ) {
        $new_columns[ $key ] = $name;
        // Add the new column after the "order-total" column
        if ( 'order-total' === $key ) {
            $new_columns['outstanding-amount'] = __( 'Outstanding Amount', 'woocommerce' );
        }
    }
    return $new_columns;
}

// Populate the "Outstanding Amount" column with data
add_action( 'woocommerce_my_account_my_orders_column_outstanding-amount', 'display_outstanding_amount_column' );
function display_outstanding_amount_column( $order ) {
    $order_status = $order->get_status();
    $outstanding_amount = 0;

    // Calculate outstanding amount based on order status
    if ( in_array( $order_status, [ 'pending', 'on-hold' ], true ) ) {
        $outstanding_amount = $order->get_total();
    }
    // Add custom logic here if you track partial payments via meta fields
    // Example: $paid_amount = $order->get_meta('_paid_amount', true);
    // $outstanding_amount = $order->get_total() - $paid_amount;

    // Display the outstanding amount (formatted with currency)
    if ( $outstanding_amount > 0 ) {
        echo wc_price( $outstanding_amount );
    } else {
        echo '–';
    }
}
//add a outstanding amount column in orders page-----------------------------------------------------------------end

// orders page ordering------------------------------------------------------------------------------------------start
add_filter('woocommerce_my_account_my_orders_columns', function($columns) {
    return [
        'order-number'         => __('Orders', 'woocommerce'),
        'order-date'           => __('Order Date', 'woocommerce'),
        'fulfilment_method'    => __('Fulfilment', 'woocommerce'),
        'required_date'        => __('Required Date', 'woocommerce'),
        'product_order_number' => __('Purchase Order Number Number', 'woocommerce'),
        'order-total'          => __('Amount', 'woocommerce'),
        'outstanding-amount'   => __('Outstanding', 'woocommerce'),
//         'order-status'         => __('Status', 'woocommerce'),
        'order-actions'        => __('Actions', 'woocommerce'),
        'pay_now'              => __('Pay Now', 'woocommerce'),
    ];
}, 20);
// orders page ordering------------------------------------------------------------------------------------------end

add_action('woocommerce_my_account_my_orders_column_fulfilment_method', function($order) {
    $method = (string) get_post_meta($order->get_id(), '_fhs_fulfilment_method', true);
    if ($method !== 'pickup' && $method !== 'delivery') {
        echo '-';
        return;
    }

    if (function_exists('fhs_get_fulfilment_label')) {
        echo esc_html(fhs_get_fulfilment_label($method));
        return;
    }

    echo esc_html($method === 'pickup' ? __('Pick up', 'woocommerce') : __('Delivery', 'woocommerce'));
});

add_action('woocommerce_admin_order_data_after_billing_address', function($order){
    $method = (string) $order->get_meta('_fhs_fulfilment_method');
    if ($method !== 'pickup' && $method !== 'delivery') {
        echo '<p><strong>' . esc_html__('Fulfilment Method', 'woocommerce') . ':</strong> -</p>';
        return;
    }

    $label = function_exists('fhs_get_fulfilment_label')
        ? fhs_get_fulfilment_label($method)
        : ($method === 'pickup' ? __('Pick up', 'woocommerce') : __('Delivery', 'woocommerce'));

    echo '<p><strong>' . esc_html__('Fulfilment Method', 'woocommerce') . ':</strong> ' . esc_html($label) . '</p>';
}, 25);

add_filter('manage_edit-shop_order_columns', function($columns) {
    $new_columns = [];

    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;
        if ('order_status' === $key) {
            $new_columns['fhs_fulfilment_method'] = __('Fulfilment', 'woocommerce');
        }
    }

    if (!isset($new_columns['fhs_fulfilment_method'])) {
        $new_columns['fhs_fulfilment_method'] = __('Fulfilment', 'woocommerce');
    }

    return $new_columns;
}, 20);

add_action('manage_shop_order_posts_custom_column', function($column, $post_id) {
    if ('fhs_fulfilment_method' !== $column) {
        return;
    }

    $method = (string) get_post_meta($post_id, '_fhs_fulfilment_method', true);
    if ($method !== 'pickup' && $method !== 'delivery') {
        echo '-';
        return;
    }

    if (function_exists('fhs_get_fulfilment_label')) {
        echo esc_html(fhs_get_fulfilment_label($method));
        return;
    }

    echo esc_html($method === 'pickup' ? __('Pick up', 'woocommerce') : __('Delivery', 'woocommerce'));
}, 20, 2);

add_filter('manage_woocommerce_page_wc-orders_columns', function($columns) {
    $new_columns = [];

    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;
        if ('order_status' === $key) {
            $new_columns['fhs_fulfilment_method'] = __('Fulfilment', 'woocommerce');
        }
    }

    if (!isset($new_columns['fhs_fulfilment_method'])) {
        $new_columns['fhs_fulfilment_method'] = __('Fulfilment', 'woocommerce');
    }

    return $new_columns;
}, 20);

add_action('manage_woocommerce_page_wc-orders_custom_column', function($column, $order_or_id) {
    if ('fhs_fulfilment_method' !== $column) {
        return;
    }

    $order = is_a($order_or_id, 'WC_Order') ? $order_or_id : wc_get_order($order_or_id);
    if (!$order) {
        echo '-';
        return;
    }

    $method = (string) $order->get_meta('_fhs_fulfilment_method');
    if ($method !== 'pickup' && $method !== 'delivery') {
        echo '-';
        return;
    }

    if (function_exists('fhs_get_fulfilment_label')) {
        echo esc_html(fhs_get_fulfilment_label($method));
        return;
    }

    echo esc_html($method === 'pickup' ? __('Pick up', 'woocommerce') : __('Delivery', 'woocommerce'));
}, 20, 2);
// 
function add_custom_user_fields_admin($operation) {
    ?>
    <h3><?php esc_html_e('Business Details', 'your-textdomain'); ?></h3>
    <table class="form-table">
        <?php
        $fields = [
            'registration_company_name' => 'Registration Company Name',
            'trading_company_name' => 'Trading Company Name',
            'billing_first_name' => 'Billing First Name',
            'billing_last_name' => 'Billing Last Name',
            'billing_email' => 'Billing Email Address',
            'billing_phone' => 'Billing Phone Number',
            'phone_number' => 'Phone Number',
            'abn_number' => 'ABN Number',
            'business_address' => 'Business Address',
            'shipping_address' => 'Shipping Address',
            'recovery_email' => 'Email for Password Recovery',
        ];

        foreach ($fields as $key => $label) {
            echo '<tr>
                    <th><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label></th>
                    <td><input type="text" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" class="regular-text" /></td>
                  </tr>';
        }
        ?>
    </table>
    <?php
}
add_action('user_new_form', 'add_custom_user_fields_admin');
function save_custom_user_fields_admin($user_id) {
    $fields = [
        'registration_company_name',
        'trading_company_name',
        'billing_first_name',
        'billing_last_name',
        'billing_email',
        'billing_phone',
        'phone_number',
        'abn_number',
        'business_address',
        'shipping_address',
        'recovery_email',
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_user_meta($user_id, 'ms_fhs_custom_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('user_register', 'save_custom_user_fields_admin');



// Send notifications based on category, Onesignal


// add_filter('onesignal_send_notification', 'onesignal_send_notification_filter', 10, 4);

// function onesignal_send_notification_filter($fields, $new_status, $old_status, $post)
// {
//     $categories = get_the_category($post->ID);

//     // Change which segment the notification goes to, will always be the first category
//     $fields['included_segments'] = array($categories[0]->name);
//     return $fields;
// }

// 
// 


add_action( 'rest_api_init', function() {

    add_filter( 'rest_pre_dispatch', function( $result, $server, $request ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            $headers = $request->get_headers();
            $method  = $request->get_method();
            $route   = $request->get_route();
            $params  = $request->get_body(); // Raw body data (e.g. JSON or file data)

            $log_entry = "=== REST API REQUEST LOG ===\n";
            $log_entry .= "Method: $method\n";
            $log_entry .= "Route: $route\n";
            $log_entry .= "Headers:\n" . print_r( $headers, true );
            $log_entry .= "Body:\n" . print_r( $params, true );
            $log_entry .= "=============================\n\n";

            file_put_contents( WP_CONTENT_DIR . '/debug.log', $log_entry, FILE_APPEND );
        }

        return $result;
    }, 10, 3 );
});


// function mj_add_demo_review_to_product($product_id, $content = 'Demo review', $rating = 5, $author = 'Demo User', $email = 'demo@example.com') {
//     if (!function_exists('wc_get_product')) return 0;
//     $product = wc_get_product($product_id);
//     if (!$product) return 0;

//     $now = current_time('mysql');
//     $now_gmt = current_time('mysql', 1);

//     $comment_id = wp_insert_comment(wp_slash(array(
//         'comment_post_ID' => $product_id,
//         'comment_author' => $author,
//         'comment_author_email' => $email,
//         'comment_content' => $content,
//         'comment_type' => 'review',
//         'comment_approved' => 0,
//         'comment_date' => $now,
//         'comment_date_gmt' => $now_gmt,
//         'user_id' => 0,
//         'comment_author_IP' => '127.0.0.1',
//         'comment_agent' => 'demo-script'
//     )));
//     if (!$comment_id || is_wp_error($comment_id)) return 0;

//     update_comment_meta($comment_id, 'rating', intval($rating));
//     update_comment_meta($comment_id, 'verified', 1);
//     wp_set_comment_status($comment_id, 'approve', true);

//     if (function_exists('wc_update_product_review_count')) wc_update_product_review_count($product_id);
//     if (function_exists('wc_update_product_rating')) wc_update_product_rating($product_id);
//     if (function_exists('wc_delete_product_transients')) wc_delete_product_transients($product_id);
//     if (function_exists('wc_update_product_lookup_tables')) wc_update_product_lookup_tables($product_id);

//     return $comment_id;
// }

// add_action('admin_init', function() {
//     if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) return;
//     if (isset($_GET['add_demo_review'], $_GET['pid'])) {
//         $pid = absint($_GET['pid']);
//         $content = isset($_GET['content']) ? sanitize_text_field(wp_unslash($_GET['content'])) : 'This is a demo review added programmatically.';
//         $rating = isset($_GET['rating']) ? max(1, min(5, intval($_GET['rating']))) : 5;
//         $id = mj_add_demo_review_to_product($pid, $content, $rating);
//         wp_die($id ? 'Added review ID '.$id.' for product '.$pid : 'Failed to add review');
//     }
// });
// try 7jan start

add_filter('posts_where', function($where, $wp_query) {
    global $wpdb;


    $search_term = trim($wp_query->get('s'));
    if (empty($search_term)) {
        return $where;
    }

    // Escape the search term for use in a LIKE clause
    $sku_escaped = $wpdb->esc_like($search_term);
    $like_pattern = '%' . $sku_escaped . '%';

    // Subquery to find products with matching SKU
    $subquery = $wpdb->prepare("
        SELECT post_id 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_sku' 
        AND meta_value LIKE %s
    ", $like_pattern);

    // Append condition to include matching products
    $where .= " OR {$wpdb->posts}.ID IN ($subquery)";

    return $where;
}, 10, 2);


add_action('init', function(){
  add_shortcode('complete_kit', 'render_complete_kit_shortcode');
});

function render_complete_kit_shortcode($atts){
  $atts = shortcode_atts(array(
    'field' => 'complete-your-kit',
    'product_id' => 0,
    'fallback' => '/test.png'
  ), $atts, 'complete_kit');

  $field = sanitize_text_field($atts['field']);
  $fallback = esc_url_raw($atts['fallback']);
  $product_id = intval($atts['product_id']);

  if(!$product_id){
    global $product;
    if(!$product || !is_object($product)) return '';
    $product_id = $product->get_id();
  } else {
    $product = wc_get_product($product_id);
    if(!$product) return '';
  }

  $meta = get_post_meta($product_id, $field, true);
  if(!$meta) return '';

  $tokens = array_filter(array_map('trim', explode(',', $meta)));
  $ids = array();
  foreach($tokens as $t){
    if(is_numeric($t)){
      $ids[] = absint($t);
    } else {
      $by_sku = wc_get_product_id_by_sku($t);
      if($by_sku) $ids[] = $by_sku;
    }
  }
  $ids = array_values(array_unique(array_filter($ids)));
  if(empty($ids)) return '';

  $wrap_in_dropdown = ($field === 'optional_extras');
  ob_start();
 

	
  if($wrap_in_dropdown){
    echo '<details class="features-dropdown">';
    echo '<summary class="dropdown-header">';
    echo '<span><i class="icofont icofont-file-alt" style="margin-right:7px;"></i> Optional Extras</span>';
    echo '<span class="dropdown-icon"><i class="icofont-rounded-down"></i></span>';
    echo '</summary>';
    echo '<div class="dropdown-content">';
	  
  } 
	else {
// 		echo '<details class="features-dropdown">';
//     echo '<summary class="dropdown-header">';
//     echo '<span><i class="icofont icofont-file-alt" style="margin-right:7px;"></i>Complete Your Kit</span>';
// 		echo '<span class="dropdown-icon"><i class="icofont-rounded-down"></i></span>';
//     echo '</summary>';
//     echo '<div class="dropdown-content">';
    echo '<h2 class="cyt-heading">Complete Your Kit</h2>';
  }

  echo '<div class="kit-wrapper"><div class="kit-list">';

  foreach($ids as $aid){
    $ap = wc_get_product($aid);
    if(!$ap) continue;
    $thumb = $ap->get_image('thumbnail');
    if(!$thumb) $thumb = '<img src="'.esc_url($fallback).'" alt="'.esc_attr($ap->get_name()).'">';
    $price_html = $ap->get_price_html();
    $is_variable = $ap->is_type('variable') || $ap->is_type('variable-subscription');
    $is_purchasable = $ap->is_purchasable() && $ap->is_in_stock();

    echo '<div class="kit-item" data-id="'.esc_attr($aid).'">';
      echo '<div class="kit-thumb">'.$thumb.'</div>';
      echo '<div class="kit-info">';
        echo '<h4 class="kit-title">'.esc_html($ap->get_name()).'</h4>';
        echo '<div class="kit-price">'.$price_html.'</div>';
      echo '</div>';
      echo '<div class="kit-action">';
        if($is_variable){
          echo '<a class="kit-view" href="'.esc_url(get_permalink($aid)).'" target="_blank">View options</a>';
        } elseif(!$is_purchasable){
          echo '<span class="kit-unavailable">Unavailable</span>';
        } else {
          echo '<button class="kit-add" data-product_id="'.esc_attr($aid).'">Add to Cart</button>';
        }
      echo '</div>';
    echo '</div>';
  }

  echo '</div></div>';

  if($wrap_in_dropdown){
    echo '</div></details>';
  }
	
// add_action('woocommerce_after_add_to_cart_form',
// 		   function(){ 
// 	echo do_shortcode('[complete_kit]'); });
	
  ?>
  <script>
  (function(){
    var defaultThumb = <?php echo json_encode($fallback); ?>;
    function getWcAjaxUrl(action){
      if(typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url){
        return wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', action);
      }
      return window.location.origin + '/?wc-ajax=' + action;
    }
    function addToCartViaWcAjax(productId, qty){
      var url = getWcAjaxUrl('add_to_cart');
      return fetch(url, {
        method:'POST',
        credentials:'same-origin',
        headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body:new URLSearchParams({product_id:productId,quantity:qty||1})
      }).then(function(r){ return r.json().catch(function(){ return {error:'invalid_json'}; }); });
    }
    function flashItem(el){
      if(!el) return;
      el.classList.remove('flash');
      void el.offsetWidth;
      el.classList.add('flash');
    }
    function showToast(name, img){
      try{
        if(window.Toastify){
          Toastify({text: name || 'Added', duration: 2500}).showToast();
          return;
        }
      }catch(e){}
      console.log('toast:', name, img);
    }
    document.addEventListener('click', function(e){
      var t = e.target;
      if(t.matches('.kit-add')){
        e.preventDefault();
        var id = t.getAttribute('data-product_id');
        if(!id) return;
        t.disabled = true;
        var item = t.closest('.kit-item');
        var name = item && item.querySelector('.kit-title') ? item.querySelector('.kit-title').textContent.trim() : '';
        var img = item && item.querySelector('.kit-thumb img') ? item.querySelector('.kit-thumb img').src : defaultThumb;
        addToCartViaWcAjax(id,1).then(function(resp){
          t.disabled = false;
          if(resp && (resp.success || resp.fragments)){
            try{ jQuery(document.body).trigger('wc_fragment_refresh'); }catch(e){}
            showToast(name,img);
            flashItem(item);
          } else {
            showToast(name,img);
            console.warn('add_to_cart response',resp);
          }
        }).catch(function(err){
          t.disabled = false;
          console.error(err);
          showToast(name,img);
        });
      }
      if(t.matches('#kit-add-all')){
        e.preventDefault();
        var btn = t; btn.disabled = true;
        var ids = Array.from(document.querySelectorAll('.kit-item')).map(function(n){ return n.getAttribute('data-id'); }).filter(Boolean);
        var items = Array.from(document.querySelectorAll('.kit-item'));
        var idx = 0;
        (function next(){
          if(idx>=ids.length){ btn.disabled = false; try{ jQuery(document.body).trigger('wc_fragment_refresh'); }catch(e){} showToast('All accessories',''); return; }
          var id = ids[idx];
          var item = items[idx];
          var name = item && item.querySelector('.kit-title') ? item.querySelector('.kit-title').textContent.trim() : '';
          var img = item && item.querySelector('.kit-thumb img') ? item.querySelector('.kit-thumb img').src : defaultThumb;
          addToCartViaWcAjax(id,1).then(function(resp){
            if(resp && (resp.success || resp.fragments)){
              try{ jQuery(document.body).trigger('wc_fragment_refresh'); }catch(e){}
              showToast(name,img);
              flashItem(item);
            } else {
              showToast(name,img);
            }
            idx++; next();
          }).catch(function(){ showToast(name,img); idx++; next(); });
        })();
      }
    }, false);
  })();
  </script>
  <?php

  return ob_get_clean();
}



/**
 * Control visible gateways based on MYOB terms
 *
 * Rules:
 * - Always allow: COD, NAB
 * - Pay Later:
 *   - Hidden for guests
 *   - Hidden if MYOB terms = prepaid or cod
 */
add_filter('woocommerce_available_payment_gateways', 'restrict_pay_later_gateway', 20);

function restrict_pay_later_gateway($available_gateways) {

    if (is_admin() && !defined('DOING_AJAX')) {
        return $available_gateways;
    }

    if (!is_user_logged_in()) {
        return $available_gateways;
    }

    $user_id = get_current_user_id();
    $payment_terms = strtolower(trim(get_user_meta($user_id, 'myob_payment_terms', true)));

    if (in_array($payment_terms, ['cod', 'prepaid'])) {
        unset($available_gateways['pay_later']);
    }

    return $available_gateways;
}



add_filter('woocommerce_checkout_form_enctype', function () {
    return 'multipart/form-data';
});


add_action('fhs_checkout_after_payment_methods', function () {

$checkout = WC()->checkout();

echo '
<div class="payment-extra-fields-grid">

<div id="payment-required-date-wrap" class="payment-extra-field">
';

woocommerce_form_field('fhs_required_date', array(
    'type'        => 'date',
    'class'       => array('form-row-wide'),
    'label'       => 'Required Date',
    'required'    => false,
	'custom_attributes' => array(
        'min' => current_time('Y-m-d'),
    ),
), $checkout->get_value('fhs_required_date'));

echo '
</div>

<div id="pay-later-po-number-wrap" class="pay-later-extra">
';

woocommerce_form_field('billing_po_number', array(
    'type'        => 'text',
    'class'       => array('form-row-wide'),
    'label'       => 'Purchase Order Number',
    'required'    => false,
), $checkout->get_value('billing_po_number'));

echo '
</div>

<div id="pay-later-po-upload" class="pay-later-extra form-row form-row-wide">

<label class="po-upload-box" for="pay_later_po_file">

<div class="po-upload-content">

<div class="po-upload-icon">
<svg width="22" height="22" viewBox="0 0 24 24" fill="none">
<path d="M12 16V4M12 4L7 9M12 4L17 9" stroke="#000" stroke-width="2"/>
<path d="M20 16.5V19C20 20.1 19.1 21 18 21H6C4.9 21 4 20.1 4 19V16.5" stroke="#000" stroke-width="2"/>
</svg>
</div>

<div class="po-upload-text">
<strong>Upload Purchase Order</strong>
<span>(PDF or Image)</span>
</div>

<div class="po-file-name">No file chosen</div>

</div>

<input type="file" id="pay_later_po_file" accept=".pdf,.jpg,.jpeg,.png">
<input type="hidden" name="pay_later_po_file_url" id="pay_later_po_file_url">

</label>
</div>

</div>
';

});


add_action('woocommerce_after_checkout_form', function () {
?>
<style>

.pay-later-extra{
display:none;
}

.payment-extra-fields-grid{
display:grid;
grid-template-columns:1fr 1fr;
column-gap:18px;
align-items:start;
}

#payment-required-date-wrap{
grid-column:1;
}

#pay-later-po-number-wrap{
grid-column:1;
}

#pay-later-po-upload{
grid-column:2;
grid-row:1 / span 2;
}

.po-upload-box{
display:block;
border:2px dashed #d9d9d9;
border-radius:8px;
padding:18px;
cursor:pointer;
background:#fafafa;
text-align:center;
transition:all .2s ease;
}

.po-upload-box:hover{
border-color:#000;
background:#fff;
}

.po-upload-content{
display:flex;
flex-direction:column;
align-items:center;
gap:6px;
}

.po-file-name{
margin-top:6px;
font-size:14px;
}

/* Spinner CSS */
.po-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(0,0,0,.1);
  border-top: 2px solid #000;
  border-radius: 50%;
  animation: po-spin 0.8s linear infinite;
  vertical-align: middle;
  margin-right: 8px;
}

@keyframes po-spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.po-upload-box.is-uploading {
    opacity: 0.6;
    cursor: not-allowed;
    pointer-events: none;
}

#pay_later_po_file{
display:none;
}

@media (max-width: 768px){
.payment-extra-fields-grid{
grid-template-columns:1fr;
}
#payment-required-date-wrap,
#pay-later-po-number-wrap,
#pay-later-po-upload{
grid-column:auto;
grid-row:auto;
}
}

</style>

<script>
jQuery(function($){

function isPayLaterSelected(){
const selected = String($('input[name="payment_method"]:checked').val() || '');
return selected === 'pay_later' || selected.indexOf('pay_later') !== -1;
}

function togglePayLaterFields(){

const isPayLater = isPayLaterSelected();

$('#pay-later-po-upload').toggle(isPayLater);

}

$(document.body).on('change','input[name="payment_method"]',togglePayLaterFields);
$(document.body).on('updated_checkout',togglePayLaterFields);

togglePayLaterFields();


$(document.body).on('change', '#pay_later_po_file', function(){

const file = this.files[0];

if(!file) return;

// Client side size check (20MB)
const maxSize = 20 * 1024 * 1024;
if (file.size > maxSize) {
    alert('File is too large. Maximum size allowed is 20MB.');
    this.value = '';
    $('.po-file-name').text('No file chosen');
    return;
}

$('.po-file-name').text('Uploading: ' + file.name);

// Disable the "Place Order" button while uploading
const $placeOrderBtn = $('button#place_order');
const originalBtnText = $placeOrderBtn.text();
$placeOrderBtn.prop('disabled', true).html('<span class="po-spinner"></span> Uploading PO...');
$('.po-upload-box').addClass('is-uploading');

let formData = new FormData();

formData.append('action','upload_po_file');
formData.append('po_file',file);

$.ajax({
url: wc_checkout_params.ajax_url,
type:'POST',
data:formData,
processData:false,
contentType:false,
success:function(response){

if(response.success){

$('#pay_later_po_file_url').val(response.data.url);
$('.po-file-name').text('Successfully uploaded: ' + file.name);

} else {
    alert('Upload failed: ' + (response.data ? response.data.message : 'Unknown error'));
    $('.po-file-name').text('Upload failed');
    $('#pay_later_po_file').val('');
}

},
error: function(xhr, status, error) {
    alert('A server error occurred during upload. The file might be larger than what the server allows.');
    $('.po-file-name').text('Server error during upload');
    $('#pay_later_po_file').val('');
},
complete: function() {
    // Re-enable the button
    $placeOrderBtn.prop('disabled', false).html(originalBtnText);
    $('.po-upload-box').removeClass('is-uploading');
}
});

});
});
</script>
<?php
});


add_action('wp_ajax_upload_po_file','upload_po_file');
add_action('wp_ajax_nopriv_upload_po_file','upload_po_file');

function upload_po_file(){

if(empty($_FILES['po_file'])) {
    wp_send_json_error(['message' => 'No file received.']);
}

$file = $_FILES['po_file'];

// Check for PHP upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE   => 'The file exceeds the upload_max_filesize in php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the MAX_FILE_SIZE specified in the form.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];
    $msg = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : 'Unknown upload error.';
    wp_send_json_error(['message' => $msg]);
}

$allowed_mimes = [
    'application/pdf',
    'image/jpeg',
    'image/png'
];

$file_type = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);

if (empty($file_type['type']) || !in_array($file_type['type'], $allowed_mimes, true)) {
    wp_send_json_error(['message' => 'Invalid file type. Only PDF and Images are allowed.']);
}

require_once ABSPATH . 'wp-admin/includes/file.php';

$uploaded = wp_handle_upload($file, ['test_form'=>false]);

if(isset($uploaded['error'])){
    wp_send_json_error(['message' => $uploaded['error']]);
}

wp_send_json_success([
    'url' => $uploaded['url']
]);

}

// Increase the upload size limit specifically for WordPress
add_filter('upload_size_limit', function($size) {
    return 20 * 1024 * 1024; // 20MB
}, 20);


add_action('woocommerce_checkout_process', function(){

$payment_method = sanitize_text_field((string) ($_POST['payment_method'] ?? ''));

if($payment_method === 'pay_later' || strpos($payment_method, 'pay_later') !== false){

if (empty(trim((string) ($_POST['billing_po_number'] ?? '')))) {
wc_add_notice('PO number is required for On Account payments.', 'error');
// wc_add_notice('PO file is required for On Account payments.', 'error');
}

if(empty($_POST['pay_later_po_file_url'])){
// wc_add_notice('PO file is required for Pay Later.', 'error');
wc_add_notice('PO file is required for On Account payments.', 'error');
}

}

});


add_action('woocommerce_checkout_create_order', function($order){

if(!empty($_POST['pay_later_po_file_url'])){
$order->update_meta_data('_pay_later_po_file', esc_url_raw($_POST['pay_later_po_file_url']));
}

});


add_action('woocommerce_admin_order_data_after_billing_address', function($order){

$file = $order->get_meta('_pay_later_po_file');

if($file){
echo '<p><strong>PO File:</strong> <a href="'.esc_url($file).'" target="_blank">View File</a></p>';
}

});


add_filter( 'loop_shop_per_page', 'set_products_per_page', 20 );
function set_products_per_page( $cols ) {
  return 30;
}


add_action('woocommerce_after_checkout_form', function () {
?>
<script>
jQuery(function($){

function isPayLaterSelected(){
const selected = String($('input[name="payment_method"]:checked').val() || '');
return selected === 'pay_later' || selected.indexOf('pay_later') !== -1;
}

function togglePoRequired(){

const isPayLater = isPayLaterSelected();

const field = $('#billing_po_number');
const row = $('#pay-later-po-number-wrap #billing_po_number_field');
const label = row.find('label');

$('#pay-later-po-number-wrap').toggle(isPayLater);

if(!row.length || !field.length){
return;
}

if(isPayLater){

row.addClass('validate-required');

row.find('.optional').hide();

if(!label.find('.required').length){
label.append(' <span class="required" aria-hidden="true">*</span>');
}

field.prop('required', true).attr('aria-required','true');

}else{

row.removeClass('validate-required');

row.find('.optional').show();

label.find('.required').remove();

field.prop('required', false).attr('aria-required','false');

}

}

$(document.body).on('change','input[name="payment_method"]',togglePoRequired);
$(document.body).on('updated_checkout',togglePoRequired);

togglePoRequired();

});
</script>
<?php
});

add_action('woocommerce_after_checkout_form', function () {
?>
<script>
jQuery(function($){
	function disableBillingCheckoutTriggers(){
		const $billingRows = $('div[id^="billing_"][id$="_field"]');
		if(!$billingRows.length){
			return;
		}

		$billingRows.removeClass('update_totals_on_change address-field');
		$billingRows.find('input, select, textarea').removeClass('update_totals_on_change');
	}

	disableBillingCheckoutTriggers();
	$(document.body).on('updated_checkout', disableBillingCheckoutTriggers);
});
</script>
<?php
});

add_action('woocommerce_after_checkout_form', function () {
?>
<script>
jQuery(function($){
	const debugEnabled = /(?:\?|&)fhs_uc_debug=1(?:&|$)/.test(window.location.search);
	let lastBillingEventAt = 0;
	let patchAttempts = 0;
	let patchTimer = null;
	const checkoutBootAt = Date.now();
	let initialUpdateConsumed = false;
	let userHasInteracted = false;
	let initialUpdateAjaxSeen = false;
	let initialTriggerAttempts = 0;
	let initialTriggerTimer = null;

	const isBillingElement = function(el){
		if(!el) return false;
		const id = String(el.id || '');
		const name = String(el.name || '');
		return id.indexOf('billing_') === 0 || name.indexOf('billing_') === 0;
	};

	const markTrustedInteraction = function(event){
		if (event && event.isTrusted) {
			userHasInteracted = true;
		}
	};

	document.addEventListener('input', markTrustedInteraction, true);
	document.addEventListener('change', markTrustedInteraction, true);
	document.addEventListener('keydown', markTrustedInteraction, true);

	$(document).ajaxSend(function(event, jqxhr, settings){
		const url = String((settings && settings.url) || '');
		if (url.indexOf('wc-ajax=update_order_review') !== -1) {
			initialUpdateAjaxSeen = true;
			if (debugEnabled) {
				console.log('[FHS-UC-DEBUG] detected update_order_review ajax', url);
			}
		}
	});

	const shouldBypassCheckoutGuards = function(){
		return Number(window.fhsBypassUpdateGuardsUntil || 0) > Date.now();
	};

	const shouldBlockExtraInitialUpdate = function(){
		if (shouldBypassCheckoutGuards()) {
			return false;
		}

		const forceUntil = Number(window.fhsForceInitialCheckoutUntil || 0);
		if (forceUntil > Date.now()) {
			return false;
		}

		const now = Date.now();
		const inInitialWindow = !userHasInteracted && (now - checkoutBootAt) < 5000;
		if (!inInitialWindow) {
			return false;
		}

		if (initialUpdateConsumed) {
			return true;
		}

		initialUpdateConsumed = true;
		return false;
	};

	const shouldBlockBillingCheckout = function(){
		if (shouldBypassCheckoutGuards()) {
			return false;
		}

		const forceUntil = Number(window.fhsForceInitialCheckoutUntil || 0);
		if (forceUntil > Date.now()) {
			return false;
		}

		const now = Date.now();
		const allowUntil = Number(window.fhsAllowBillingTriggeredCheckoutUntil || 0);
		if (allowUntil > now) {
			return false;
		}

		const active = document.activeElement;
		const billingActive = isBillingElement(active);
		const nearBillingEvent = (now - lastBillingEventAt) < 900;
		return billingActive || nearBillingEvent;
	};

	const triggerInitialCheckoutOnce = function(){
		if (!window.jQuery) {
			return;
		}
		if (window.fhsInitialCheckoutTriggered && initialTriggerAttempts > 0) {
			return;
		}

		window.fhsForceInitialCheckoutUntil = Date.now() + 2500;
		window.fhsAllowBillingTriggeredCheckoutUntil = Date.now() + 2000;
		window.fhsBypassUpdateGuardsUntil = Date.now() + 2500;
		initialTriggerAttempts++;

		if (debugEnabled) {
			console.log('[FHS-UC-DEBUG] forcing initial update_checkout attempt #' + initialTriggerAttempts);
		}

		if (window.wc_checkout_form && typeof window.wc_checkout_form.update_checkout === 'function') {
			window.wc_checkout_form.update_checkout();
		} else {
			window.jQuery(document.body).trigger('update_checkout');
		}

		window.fhsInitialCheckoutTriggered = true;
	};

	const startInitialCheckoutRetries = function(){
		if (initialTriggerTimer) {
			return;
		}

		initialTriggerTimer = window.setInterval(function(){
			if (initialUpdateAjaxSeen) {
				window.clearInterval(initialTriggerTimer);
				initialTriggerTimer = null;
				return;
			}

			if (initialTriggerAttempts >= 6) {
				window.clearInterval(initialTriggerTimer);
				initialTriggerTimer = null;
				if (debugEnabled) {
					console.log('[FHS-UC-DEBUG] no update_order_review ajax seen after retries');
				}
				return;
			}

			triggerInitialCheckoutOnce();
		}, 700);
	};

	if ($.fn && typeof $.fn.trigger === 'function' && !$.fn.trigger.__fhsBillingGuardPatched) {
		const originalTrigger = $.fn.trigger;
		const guardedTrigger = function(type){
			const eventType = typeof type === 'string' ? type : (type && type.type ? type.type : '');
			const isBodyTarget = this && this.length && this[0] === document.body;
			if (isBodyTarget && eventType === 'update_checkout' && shouldBlockBillingCheckout()) {
				if (debugEnabled) {
					console.log('[FHS-UC-DEBUG] blocked document.body trigger(update_checkout) from billing field');
				}
				return this;
			}
			return originalTrigger.apply(this, arguments);
		};
		guardedTrigger.__fhsBillingGuardPatched = true;
		$.fn.trigger = guardedTrigger;
	}

	$(document).on('input change keyup keydown', 'input[name^="billing_"], select[name^="billing_"], textarea[name^="billing_"]', function(){
		lastBillingEventAt = Date.now();
	});

	const patchCheckoutForm = function(){
		if(!window.wc_checkout_form){
			return false;
		}

		if (typeof window.wc_checkout_form.trigger_update_checkout === 'function') {
			const originalTriggerUpdate = window.wc_checkout_form.trigger_update_checkout;
			window.wc_checkout_form.trigger_update_checkout = function(event){
				const target = event && event.target ? event.target : null;
				if (isBillingElement(target)) {
					if (debugEnabled) {
						console.log('[FHS-UC-DEBUG] blocked trigger_update_checkout from billing field');
					}
					return;
				}
				return originalTriggerUpdate.apply(this, arguments);
			};
		}

		if(typeof window.wc_checkout_form.queue_update_checkout === 'function'){
			const originalQueue = window.wc_checkout_form.queue_update_checkout;
			window.wc_checkout_form.queue_update_checkout = function(){
				if (shouldBlockBillingCheckout()) {
					if (debugEnabled) {
						console.log('[FHS-UC-DEBUG] blocked queue_update_checkout from billing field');
					}
					return;
				}

				return originalQueue.apply(this, arguments);
			};
		}

		if(typeof window.wc_checkout_form.update_checkout === 'function'){
			const originalUpdateCheckout = window.wc_checkout_form.update_checkout;
			window.wc_checkout_form.update_checkout = function(){
				if (shouldBlockBillingCheckout()) {
					if (debugEnabled) {
						console.log('[FHS-UC-DEBUG] blocked update_checkout from billing field');
					}
					return;
				}
				if (shouldBlockExtraInitialUpdate()) {
					if (debugEnabled) {
						console.log('[FHS-UC-DEBUG] blocked extra initial update_checkout');
					}
					return;
				}

				return originalUpdateCheckout.apply(this, arguments);
			};
		}

		return true;
	};

	const runInitialAfterLoadWhenReady = function(){
		let readyChecks = 0;
		const readyTimer = window.setInterval(function(){
			readyChecks++;
			const hasCheckoutForm = $('form.checkout').length > 0;
			const hasOrderReview = $('#order_review').length > 0 || $('.woocommerce-checkout-review-order-table').length > 0;
			const hasCheckoutApi = !!(window.wc_checkout_form && typeof window.wc_checkout_form.update_checkout === 'function');
			const ready = hasCheckoutForm && hasOrderReview && hasCheckoutApi;

			if (!ready && readyChecks < 60) {
				return;
			}

			window.clearInterval(readyTimer);

			if (debugEnabled) {
				console.log('[FHS-UC-DEBUG] initial trigger readiness', {
					hasCheckoutForm: hasCheckoutForm,
					hasOrderReview: hasOrderReview,
					hasCheckoutApi: hasCheckoutApi,
					forcedAfterTimeout: !ready
				});
			}

			triggerInitialCheckoutOnce();
			window.setTimeout(startInitialCheckoutRetries, 250);

			// Hard fallback: force exactly one update_order_review AJAX after load.
			if (!window.fhsInitialOrderReviewAjaxRequested) {
				window.fhsInitialOrderReviewAjaxRequested = true;
				window.setTimeout(function(){
					if (initialUpdateAjaxSeen) {
						return;
					}

					const $form = $('form.checkout');
					const postData = $form.length ? $form.serialize() : '';

					if (window.wc_checkout_form && typeof window.wc_checkout_form.update_checkout === 'function') {
						window.fhsForceInitialCheckoutUntil = Date.now() + 3000;
						window.fhsAllowBillingTriggeredCheckoutUntil = Date.now() + 3000;
						window.fhsBypassUpdateGuardsUntil = Date.now() + 3000;
						window.wc_checkout_form.update_checkout();
						return;
					}

					if (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.wc_ajax_url) {
						$.ajax({
							type: 'POST',
							url: String(wc_checkout_params.wc_ajax_url).replace('%%endpoint%%', 'update_order_review'),
							data: {
								security: wc_checkout_params.update_order_review_nonce || '',
								post_data: postData
							}
						});
						return;
					}

					$(document.body).trigger('update_checkout');
				}, 800);
			}
		}, 200);
	};

	if (!patchCheckoutForm()) {
		patchTimer = window.setInterval(function(){
			patchAttempts++;
			if (patchCheckoutForm() || patchAttempts > 40) {
				window.clearInterval(patchTimer);
				if (document.readyState === 'complete') {
					runInitialAfterLoadWhenReady();
				} else {
					window.addEventListener('load', runInitialAfterLoadWhenReady, { once: true });
				}
			}
		}, 100);
	} else if (document.readyState === 'complete') {
		runInitialAfterLoadWhenReady();
	} else {
		window.addEventListener('load', runInitialAfterLoadWhenReady, { once: true });
	}

	window.addEventListener('load', function(){
		window.setTimeout(function(){
			if (window.fhsLoadForcedCheckoutTriggered) {
				return;
			}

			window.fhsLoadForcedCheckoutTriggered = true;
			window.fhsForceInitialCheckoutUntil = Date.now() + 4000;
			window.fhsAllowBillingTriggeredCheckoutUntil = Date.now() + 4000;
			window.fhsBypassUpdateGuardsUntil = Date.now() + 4000;

			if (window.wc_checkout_form && typeof window.wc_checkout_form.update_checkout === 'function') {
				window.wc_checkout_form.update_checkout();
			} else {
				$(document.body).trigger('update_checkout');
			}
		}, 1200);
	}, { once: true });
});
</script>
<?php
});







add_filter('gform_field_validation', 'gf_phone_numbers_only_clean', 10, 4);
function gf_phone_numbers_only_clean($result, $value, $form, $field) {

    if ($field->type === 'phone' && preg_match('/[a-zA-Z]/', $value)) {
        $result['is_valid'] = false;
        $result['message'] = 'Please enter numbers only.';
    }

    return $result;
}


add_filter('gform_address_types', 'custom_au_address_type');

function custom_au_address_type($address_types) {

    $address_types['australia'] = array(
        'label'       => 'Australia',
        'country'     => 'Australia',
        'zip_label'   => 'Postcode',
        'state_label' => 'State',
        'states'      => array(
            'NSW' => 'New South Wales',
            'VIC' => 'Victoria',
            'QLD' => 'Queensland',
            'WA'  => 'Western Australia',
            'SA'  => 'South Australia',
            'TAS' => 'Tasmania',
            'ACT' => 'Australian Capital Territory',
            'NT'  => 'Northern Territory',
        ),
    );

    add_action('wp_footer', function () {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {

            document.querySelectorAll('.customer-mainauth-container .ginput_container_address').forEach(function(addressField) {

                let countryContainer = addressField.querySelector('.address_country');

                if (!countryContainer) {

                    let html = `
                        <span class="ginput_right address_country ginput_address_country gform-grid-col">
                            <select>
                                <option value="AU" selected>Australia</option>
                            </select>

                            <input type="hidden" name="country_static" value="AU">

                            <label class="gform-field-label gform-field-label--type-sub">
                                Country
                            </label>
                        </span>
                    `;

                    addressField.insertAdjacentHTML('beforeend', html);
                }

            });

        });
        </script>
        <?php
    });

    return $address_types;
}

add_action('woocommerce_cart_calculate_fees', function($cart) {

    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    $fees = $cart->get_fees();

    if (!$fees) {
        return;
    }

    foreach ($fees as $fee_key => $fee) {

        if ($fee->name === 'Shipping Amount') {
            unset($fees[$fee_key]);
        }

    }

    $cart->fees_api()->set_fees($fees);

}, 999);


add_action('woocommerce_after_checkout_validation', function ($data, $errors) {

    if (!empty($data['billing_phone'])) {

        $phone_raw = $data['billing_phone'];

        // Reject if contains anything other than digits
        if (!preg_match('/^[0-9]+$/', $phone_raw)) {
            $errors->add(
                'billing_phone_error',
                __('Billing Phone must contain digits only (no + or special characters).', 'woocommerce')
            );
            return;
        }

        // Length check
        if (strlen($phone_raw) > 12) {
            $errors->add(
                'billing_phone_error',
                __('Billing Phone must be less than 12 digits.', 'woocommerce')
            );
        }
    }

}, 10, 2);

// our brands start
function change_brand_menu_label() {
    global $submenu;

    if ( isset($submenu['edit.php?post_type=product']) ) {
        foreach ($submenu['edit.php?post_type=product'] as $key => $value) {
            if ($value[0] == 'Brands') {
                $submenu['edit.php?post_type=product'][$key][0] = 'Our Brands';
            }
        }
    }
}
add_action('admin_menu', 'change_brand_menu_label', 999);
function rename_product_brands_labels() {
    global $wp_taxonomies;

    if (!isset($wp_taxonomies['product_brand'])) return;

    $labels = &$wp_taxonomies['product_brand']->labels;
    $labels->name = 'Brands';
    $labels->singular_name = 'Brands';
    $labels->menu_name = 'Brands';
}
add_action('init', 'rename_product_brands_labels');

function customise_product_brand_slug( $tax ) {
    $tax['rewrite']['slug'] = 'brands'; // change this
    return $tax;
}
add_filter( 'register_taxonomy_product_brand', 'customise_product_brand_slug' );

// our brands end


add_filter('woocommerce_get_availability_text', function($availability, $product) {

    
    if ($product->is_in_stock() && $product->backorders_allowed()) {

        $stock = $product->get_stock_quantity();
        
        if ($stock !== 0 && $stock !==null) {
                return $stock . ' in stock (Additional can be backordered)';
        }

    }

    
    return $availability;

}, 10, 2);


add_filter('gform_confirmation_6', function ($confirmation, $form, $entry, $ajax) {
    
    if ($ajax) {
        return '<div class="gform_confirmation_message">Thank you! We will contact you shortly.</div>';
    }

    return $confirmation;
}, 10, 4);



add_filter('woocommerce_ajax_variation_threshold', function () {
    return 9999;
});



add_filter('woocommerce_ship_to_billing_address_only', '__return_false');

function fhs_get_checkout_fulfilment_method($posted_data = null) {
    if (is_array($posted_data) && isset($posted_data['fhs_fulfilment_method'])) {
        $mode = sanitize_key((string) $posted_data['fhs_fulfilment_method']);
        if (in_array($mode, ['delivery', 'pickup'], true)) {
            return $mode;
        }
    }

    if (isset($_POST['fhs_fulfilment_method'])) {
        $mode = sanitize_key((string) wp_unslash($_POST['fhs_fulfilment_method']));
        if (in_array($mode, ['delivery', 'pickup'], true)) {
            return $mode;
        }
    }

    if (isset($_POST['post_data'])) {
        $parsed = [];
        parse_str(wp_unslash($_POST['post_data']), $parsed);
        $mode = sanitize_key((string) ($parsed['fhs_fulfilment_method'] ?? ''));
        if (in_array($mode, ['delivery', 'pickup'], true)) {
            return $mode;
        }
    }

    if (WC()->session) {
        $mode = sanitize_key((string) WC()->session->get('fhs_fulfilment_method', ''));
        if (in_array($mode, ['delivery', 'pickup'], true)) {
            return $mode;
        }
    }

    return 'delivery';
}

function fhs_get_checkout_posted_data() {
    static $posted_data = null;

    if (null !== $posted_data) {
        return $posted_data;
    }

    $posted_data = [];

    if (isset($_POST['post_data'])) {
        parse_str(wp_unslash($_POST['post_data']), $posted_data);
    } elseif (!empty($_POST) && is_array($_POST)) {
        $posted_data = wp_unslash($_POST);
    }

    return is_array($posted_data) ? $posted_data : [];
}

function fhs_should_skip_checkout_prefill($field_key) {
    if (!is_string($field_key) || '' === $field_key) {
        return false;
    }

    if (is_admin() && !wp_doing_ajax()) {
        return false;
    }

    if (function_exists('is_order_received_page') && is_order_received_page()) {
        return false;
    }

    $is_checkout_request = (function_exists('is_checkout') && is_checkout())
        || (wp_doing_ajax() && (isset($_POST['post_data']) || isset($_POST['fhs_fulfilment_method'])));

    if (!$is_checkout_request) {
        return false;
    }

    $fields_to_keep_empty_on_boot = [
//         'billing_first_name',
//         'billing_last_name',
//         'billing_country',
//         'billing_address_1',
//         'billing_city',
//         'billing_state',
//         'billing_postcode',
//         'shipping_first_name',
//         'shipping_last_name',
//         'shipping_country',
//         'shipping_address_1',
//         'shipping_city',
//         'shipping_state',
//         'shipping_postcode',
    ];

    if (!in_array($field_key, $fields_to_keep_empty_on_boot, true)) {
        return false;
    }

    $posted_data = fhs_get_checkout_posted_data();

    if (array_key_exists($field_key, $posted_data)) {
        return false;
    }

    return true;
}

function fhs_get_checkout_request_value($field_key, $default = '') {
    $posted_data = fhs_get_checkout_posted_data();

    if (array_key_exists($field_key, $posted_data)) {
        return wc_clean($posted_data[$field_key]);
    }

    if (fhs_should_skip_checkout_prefill($field_key)) {
        return '';
    }

    return wc_clean($default);
}

add_filter('woocommerce_checkout_get_value', function($value, $input) {
    if (fhs_should_skip_checkout_prefill($input)) {
        return '';
    }

    return $value;
}, 999, 2);

add_action('wp', function() {
    if (wp_doing_ajax() || !function_exists('is_checkout') || !is_checkout() || is_order_received_page()) {
        return;
    }

    if (!WC()->session) {
        return;
    }

    $has_mode_in_request = isset($_POST['fhs_fulfilment_method']) || isset($_POST['post_data']);
    if (!$has_mode_in_request) {
        WC()->session->set('fhs_fulfilment_method', 'delivery');
    }
}, 20);

add_action('woocommerce_checkout_update_order_review', function($posted_data) {
    if (!WC()->session) {
        return;
    }

    $parsed = [];
    parse_str((string) $posted_data, $parsed);

    $mode = fhs_get_checkout_fulfilment_method($parsed);
    WC()->session->set('fhs_fulfilment_method', $mode);
}, 20);

add_filter('woocommerce_cart_needs_shipping_address', function($needs_shipping_address) {
    if (!WC()->cart || !WC()->cart->needs_shipping()) {
        return $needs_shipping_address;
    }

    return 'pickup' === fhs_get_checkout_fulfilment_method() ? false : true;
}, 20);

add_filter('woocommerce_cart_needs_shipping', function($needs_shipping) {
    if (!$needs_shipping) {
        return $needs_shipping;
    }

    return 'pickup' === fhs_get_checkout_fulfilment_method() ? false : $needs_shipping;
}, 20);

add_filter('woocommerce_checkout_fields', function($fields) {
    $mode = fhs_get_checkout_fulfilment_method();

    // Keep THWMA's billing-side helper fields out of checkout to avoid
    // plugin JS syncing shipping -> billing.
    unset($fields['billing']['thwma_hidden_field_billing']);
    unset($fields['billing']['thwma_checkbox_shipping']);

    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['required'] = true;
        $fields['billing']['billing_phone']['custom_attributes']['required'] = 'required';
        $fields['billing']['billing_phone']['validate'] = array_values(array_unique(array_merge(
            isset($fields['billing']['billing_phone']['validate']) && is_array($fields['billing']['billing_phone']['validate'])
                ? $fields['billing']['billing_phone']['validate']
                : [],
            ['required']
        )));
    }

    if (!empty($fields['billing']) && is_array($fields['billing'])) {
        foreach ($fields['billing'] as $billing_key => $billing_field) {
            if (empty($fields['billing'][$billing_key]['class']) || !is_array($fields['billing'][$billing_key]['class'])) {
                continue;
            }
            $fields['billing'][$billing_key]['class'] = array_values(array_filter(
                $fields['billing'][$billing_key]['class'],
                function($class_name) {
                    return 'update_totals_on_change' !== $class_name;
                }
            ));
        }
    }

    if ('pickup' !== $mode || empty($fields['shipping']) || !is_array($fields['shipping'])) {
        return $fields;
    }

    foreach ($fields['shipping'] as $field_key => $field_config) {
        $fields['shipping'][$field_key]['required'] = false;
        if (isset($fields['shipping'][$field_key]['custom_attributes']['required'])) {
            unset($fields['shipping'][$field_key]['custom_attributes']['required']);
        }

        if (isset($fields['shipping'][$field_key]['validate']) && is_array($fields['shipping'][$field_key]['validate'])) {
            $fields['shipping'][$field_key]['validate'] = array_values(array_filter(
                $fields['shipping'][$field_key]['validate'],
                function($rule) {
                    return 'required' !== $rule;
                }
            ));
        }
    }

    return $fields;
}, 999);

add_filter('woocommerce_cart_shipping_packages', function($packages) {
    if (empty($packages) || !is_array($packages)) {
        return $packages;
    }

    if ('pickup' === fhs_get_checkout_fulfilment_method()) {
        return $packages;
    }

    $destination = [
        'country'  => '',
        'state'    => '',
        'postcode' => '',
        'city'     => '',
        'address'  => '',
        'address_1'=> '',
        'address_2'=> '',
    ];

    $destination['country']   = fhs_get_checkout_request_value('shipping_country', WC()->customer->get_shipping_country());
    $destination['state']     = fhs_get_checkout_request_value('shipping_state', WC()->customer->get_shipping_state());
    $destination['postcode']  = fhs_get_checkout_request_value('shipping_postcode', WC()->customer->get_shipping_postcode());
    $destination['city']      = fhs_get_checkout_request_value('shipping_city', WC()->customer->get_shipping_city());
    $destination['address']   = fhs_get_checkout_request_value('shipping_address_1', WC()->customer->get_shipping_address());
    $destination['address_1'] = $destination['address'];
    $destination['address_2'] = fhs_get_checkout_request_value('shipping_address_2', WC()->customer->get_shipping_address_2());

    foreach ($packages as $index => $package) {
        $packages[$index]['destination'] = $destination;
    }

    return $packages;
}, 20);

add_action('woocommerce_checkout_process', function() {
    $mode = fhs_get_checkout_fulfilment_method();

    if (!in_array($mode, ['delivery', 'pickup'], true)) {
        wc_add_notice('Please choose Delivery or Pick up before placing your order.', 'error');
        return;
    }

    if ('pickup' === $mode) {
        return;
    }

    $packages = WC()->shipping()->get_packages();

    foreach ($packages as $package) {
        if (empty($package['rates'])) {
            wc_add_notice('Shipping is required. Please select a shipping method.', 'error');
            return;
        }
    }

    if (WC()->cart->needs_shipping()) {
        $required = [
            'shipping_first_name',
            'shipping_last_name',
            'shipping_address_1',
            'shipping_city',
            'shipping_postcode',
            'shipping_country',
        ];

        foreach ($required as $field) {
            if (empty(trim($_POST[$field] ?? ''))) {
                wc_add_notice('Please enter a valid shipping address.', 'error');
                break;
            }
        }
    }
});


add_action('init', function () {

    
    if (is_user_logged_in() || is_admin()) {
        return;
    }

    
    add_filter('woocommerce_get_price_html', '__return_empty_string', 99999);
    add_filter('woocommerce_get_variation_price_html', '__return_empty_string', 99999);
    add_filter('woocommerce_variable_price_html', '__return_empty_string', 99999);
    add_filter('woocommerce_variable_sale_price_html', '__return_empty_string', 99999);
    add_filter('woocommerce_grouped_price_html', '__return_empty_string', 99999);

    
    add_filter('wc_price', '__return_empty_string', 99999);

    
    add_filter('woocommerce_available_variation', function ($variation_data) {
        $variation_data['price_html'] = '';
        $variation_data['display_price'] = 0;
        $variation_data['display_regular_price'] = 0;
        return $variation_data;
    }, 99999);

    
    add_filter('woocommerce_structured_data_product', function ($data) {
        unset($data['offers']);
        return $data;
    }, 99999);
});


add_filter( 'woocommerce_get_country_locale_default', function ( $locale ) {

	if ( isset( $locale['address_1'] ) ) {
		$locale['address_1']['label']       = 'Address Line 1';
		$locale['address_1']['placeholder'] = 'Address Line 1';
		$locale['address_1']['required']    = true;
	}

	if ( isset( $locale['address_2'] ) ) {
		$locale['address_2']['label']         = 'Address Line 2';
		$locale['address_2']['placeholder']   = 'Address Line 2';
		$locale['address_2']['required']      = false;
		$locale['address_2']['label_class']   = array();
	}

	return $locale;
} );

add_filter( 'woocommerce_default_address_fields', function ( $fields ) {

	// Address Line 1
	if ( isset( $fields['address_1'] ) ) {
		$fields['address_1']['label']       = 'Address Line 1';
		$fields['address_1']['placeholder'] = 'Address Line 1';
		$fields['address_1']['required']    = true;
	}

	// Address Line 2
	if ( isset( $fields['address_2'] ) ) {
		$fields['address_2']['label']         = 'Address Line 2';
		$fields['address_2']['placeholder']   = 'Address Line 2';
		$fields['address_2']['required']      = false;
		$fields['address_2']['label_class']   = array();
	}

	return $fields;
} );
