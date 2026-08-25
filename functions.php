<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @package Astra Child
 * @since 1.0.0
 */

define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

require_once get_stylesheet_directory() . '/woocommerce/single-product/delivery-enquiry-handler.php';
require_once get_stylesheet_directory() . '/inc/checkout-fields.php';


// ============================================================
// GOOGLE ANALYTICS
// ============================================================

add_action( 'wp_head', function () {
	?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-241F22NJGZ"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){ dataLayer.push(arguments); }
		gtag('js', new Date());
		gtag('config', 'G-241F22NJGZ');
	</script>
	<?php
} );


// ============================================================
// THANK YOU PAGE — pending payment notice
// ============================================================

add_action( 'woocommerce_before_thankyou', 'show_pending_payment_error' );
function show_pending_payment_error( $order_id ) {
	if ( ! $order_id ) return;
	$order = wc_get_order( $order_id );
	if ( ! $order ) return;
	if ( $order->has_status( 'pending' ) ) {
		wc_print_notice(
			__( 'There\'s an issue with processing your payment and it\'s still pending. Please click on "Back to Orders" to try again.', 'woocommerce' ),
			'error'
		);
		remove_all_filters( 'woocommerce_thankyou_order_received_text' );
	}
}


// ============================================================
// CUSTOM POST TYPE — Downloads
// ============================================================

add_action( 'init', 'create_downloads_post_type' );
function create_downloads_post_type() {
	register_post_type( 'downloads', array(
		'labels'             => array(
			'name'               => 'Downloads',
			'singular_name'      => 'Download',
			'menu_name'          => 'Downloads',
			'add_new_item'       => 'Add New Download',
			'edit_item'          => 'Edit Download',
			'view_item'          => 'View Download',
			'all_items'          => 'All Downloads',
			'search_items'       => 'Search Downloads',
			'not_found'          => 'No downloads found.',
			'not_found_in_trash' => 'No downloads found in Trash.',
		),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'downloads' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
		'menu_icon'          => 'dashicons-download',
	) );
}


// ============================================================
// NAVIGATION MENUS
// ============================================================

add_action( 'init', 'register_menus' );
function register_menus() {
	register_nav_menu( 'primary',  __( 'Primary Menu' ) );
	register_nav_menu( 'secondary', __( 'Secondary Menu' ) );
}


// ============================================================
// ASSET ENQUEUE
// ============================================================

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );
function child_enqueue_styles() {
	wp_enqueue_style(
		'astra-child-theme-css',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'astra-theme-css' ),
		CHILD_THEME_ASTRA_CHILD_VERSION,
		'all'
	);
}

add_action( 'wp_enqueue_scripts', 'enqueue_jquery_ui' );
function enqueue_jquery_ui() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
}

add_action( 'wp_enqueue_scripts', 'enqueue_splidejs_scripts' );
function enqueue_splidejs_scripts() {
	wp_enqueue_style( 'splide-css', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css' );
	wp_enqueue_script( 'splide-js', 'https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', array(), null, true );
}

add_action( 'wp_enqueue_scripts', 'load_custom_assets' );
function load_custom_assets() {
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full.min.js', array( 'jquery' ), WC_VERSION, true );
		wp_enqueue_style( 'selectWoo', WC()->plugin_url() . '/assets/css/select2.css', array(), WC_VERSION );
		wp_enqueue_script( 'woocommerce-edit_address', get_stylesheet_directory_uri() . '/custom-assets/js/select2-init-file.js', array( 'jquery' ), null, true );
	}

	wp_enqueue_style( 'custom-style',             get_stylesheet_directory_uri() . '/custom-assets/css/style.css' );
	wp_enqueue_style( 'tabs-style',               get_stylesheet_directory_uri() . '/custom-assets/css/tabs-style.css' );
	wp_enqueue_style( 'main-style',               get_stylesheet_directory_uri() . '/custom-assets/css/product.css' );
	wp_enqueue_style( 'custom-responsive-style',  get_stylesheet_directory_uri() . '/custom-assets/css/responsive-style.css' );

	// Replace bundled jQuery with a CDN copy.
	wp_deregister_script( 'jquery' );
	wp_register_script( 'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js', array(), '3.6.0', true );
	wp_enqueue_script( 'jquery' );

	wp_enqueue_script( 'custom-script', get_stylesheet_directory_uri() . '/custom-assets/js/script.js', array( 'jquery' ), null, true );
	wp_localize_script( 'custom-script', 'wpfDeliveryEnquiryData', array(
		'userEmail' => is_user_logged_in() ? wp_get_current_user()->user_email : '',
	) );

	wp_enqueue_script( 'custom-tabs-script',  get_stylesheet_directory_uri() . '/custom-assets/js/tabs-handle-script.js',       array( 'jquery' ), null, true );
	wp_enqueue_script( 'hire-page-dialog',    get_stylesheet_directory_uri() . '/custom-assets/js/hire-page-dialog-handle.js',   array( 'jquery' ), null, true );
	wp_enqueue_script( 'modal-edit_address',  get_stylesheet_directory_uri() . '/custom-assets/js/ajax-handle-with-nonce.js',    array( 'jquery' ), null, true );

	wp_localize_script( 'modal-edit_address', 'ajaxParams', array(
		'nonce'   => wp_create_nonce( 'woocommerce-edit_address' ),
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'siteurl' => get_site_url(),
	) );

	wp_enqueue_script( 'my-acc-cart-handle', get_stylesheet_directory_uri() . '/custom-assets/js/cart-handle.js', array( 'jquery' ), null, true );
	wp_localize_script( 'my-acc-cart-handle', 'wc_cart_params', array(
		'ajax_url'           => admin_url( 'admin-ajax.php' ),
		'update_cart_nonce'  => wp_create_nonce( 'update-cart' ),
	) );
}


// ============================================================
// NAVIGATION — megamenu product include/exclude helpers
// ============================================================

add_filter( 'wp_nav_menu_objects', 'exclude_products_from_secondary_menu', 10, 2 );
function exclude_products_from_secondary_menu( $items, $args ) {
	if ( $args->theme_location !== 'primary' || empty( $args->exclude_products ) ) {
		return $items;
	}
	$exclude_ids = array();
	foreach ( $items as $item ) {
		if ( in_array( 'products-megamenu', $item->classes ) ) {
			$exclude_ids[] = $item->ID;
		}
	}
	if ( ! empty( $exclude_ids ) ) {
		$found = true;
		while ( $found ) {
			$found = false;
			foreach ( $items as $item ) {
				if ( in_array( $item->menu_item_parent, $exclude_ids ) && ! in_array( $item->ID, $exclude_ids ) ) {
					$exclude_ids[] = $item->ID;
					$found = true;
				}
			}
		}
	}
	return array_filter( $items, function ( $item ) use ( $exclude_ids ) {
		return ! in_array( $item->ID, $exclude_ids );
	} );
}

add_filter( 'wp_nav_menu_objects', 'include_products_in_menu', 10, 2 );
function include_products_in_menu( $items, $args ) {
	if ( $args->theme_location !== 'primary' || empty( $args->include_products ) ) {
		return $items;
	}
	$include_ids = array();
	foreach ( $items as $item ) {
		if ( in_array( 'products-megamenu', $item->classes ) ) {
			$include_ids[] = $item->ID;
		}
	}
	if ( ! empty( $include_ids ) ) {
		$found = true;
		while ( $found ) {
			$found = false;
			foreach ( $items as $item ) {
				if ( in_array( $item->menu_item_parent, $include_ids ) && ! in_array( $item->ID, $include_ids ) ) {
					$include_ids[] = $item->ID;
					$found = true;
				}
			}
		}
	}
	return array_filter( $items, function ( $item ) use ( $include_ids ) {
		return in_array( $item->ID, $include_ids );
	} );
}


// ============================================================
// CATEGORY / BRAND ARCHIVE PAGES
// ============================================================

// Custom breadcrumb for brand taxonomy pages.
add_filter( 'woocommerce_get_breadcrumb', 'custom_brand_breadcrumb', 10, 2 );
function custom_brand_breadcrumb( $crumbs, $breadcrumb ) {
	if ( is_tax( 'product_brand' ) || is_tax( 'brands' ) ) {
		$term    = get_queried_object();
		$crumbs  = array(
			array( 'Home',   home_url() ),
			array( 'Brands', home_url( '/brands/' ) ),
			array( $term->name, '' ),
		);
	}
	return $crumbs;
}

add_filter( 'woocommerce_breadcrumb_defaults', 'custom_woocommerce_breadcrumbs_separator' );
function custom_woocommerce_breadcrumbs_separator( $defaults ) {
	$defaults['delimiter'] = ' > ';
	return $defaults;
}

remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 );
add_action( 'woocommerce_shop_loop_header', 'custom_banner_with_breadcrumbs', 5 );

function custom_banner_with_breadcrumbs() {
	if ( ! is_product_category() && ! is_tax( 'product_brand' ) ) return;

	ob_start();
	woocommerce_breadcrumb( array(
		'delimiter'   => '<span class="bradcrumbs-delimiter"> > </span> ',
		'wrap_before' => '<nav class="custom-breadcrumbs">',
		'wrap_after'  => '</nav>',
	) );
	$breadcrumbs   = ob_get_clean();
	$current_term  = get_queried_object();
	$archive_title = is_product_category() ? woocommerce_page_title( false ) : $current_term->name;
	$banner_image_id = get_term_meta( $current_term->term_id, 'category_detail_banner_image', true );
	$image_url     = $banner_image_id ? wp_get_attachment_url( $banner_image_id ) : '';

	echo '<div class="category-banner"><div class="single-custom-container"><div>';
	echo '<h2 class="archive-header">' . esc_html( $archive_title ) . '</h2>';
	echo $breadcrumbs;
	echo '</div><div class="custom-category-image">';
	if ( $image_url ) {
		echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $archive_title ) . ' Banner">';
	} else {
		echo '<img src="' . esc_url( home_url( '/wp-content/uploads/2025/02/malepolyfittings.png' ) ) . '" alt="' . esc_attr( $archive_title ) . ' Banner">';
	}
	echo '</div></div></div>';
}

// Category loop title with icon.
if ( ! function_exists( 'woocommerce_template_loop_category_title' ) ) {
	function woocommerce_template_loop_category_title( $category ) {
		$image_id  = get_field( 'category_icon', 'product_cat_' . $category->term_id );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
		?>
		<div class="custom-category-heading-container">
			<div class="custom-category-heading">
				<?php if ( $image_url ) : ?>
					<img class="category-heading-icon" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $category->name ); ?>">
				<?php endif; ?>
				<span><?php echo esc_html( $category->name ); ?></span>
			</div>
			<button class="custom-category-button">View More</button>
		</div>
		<?php
	}
}

// Elementor sections after category content.
add_action( 'woocommerce_after_main_content', 'add_custom_section_to_category_page' );
function add_custom_section_to_category_page() {
	if ( ! is_tax( 'product_cat' ) ) return;
	echo '<div class="custom-section-category">';
	if ( class_exists( '\Elementor\Plugin' ) ) {
		foreach ( array( 14294, 13897, 13889, 13892 ) as $post_id ) {
			echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post_id );
		}
	}
	echo '</div>';
}

// Body class: has-subcategories vs leaf.
add_filter( 'body_class', 'add_category_type_to_body_class' );
function add_category_type_to_body_class( $classes ) {
	if ( ! is_tax( 'product_cat' ) ) return $classes;
	$children = get_terms( array(
		'taxonomy'   => 'product_cat',
		'parent'     => get_queried_object_id(),
		'hide_empty' => false,
	) );
	$classes[] = ! empty( $children ) ? 'category-has-subcategories' : 'category-is-leaf';
	return $classes;
}

// Only show direct subcategories (not grandchildren) on parent category pages.
add_filter( 'woocommerce_product_subcategories_args', 'hide_subcategory_products_on_parent' );
function hide_subcategory_products_on_parent( $args ) {
	$args['parent'] = get_queried_object_id();
	return $args;
}

// Exclude subcategory products from parent category loops.
add_action( 'woocommerce_before_shop_loop', 'remove_subcategory_products', 5 );
function remove_subcategory_products() {
	if ( ! is_product_category() ) return;
	add_filter( 'woocommerce_product_query', function ( $query ) {
		$query->set( 'tax_query', array( array(
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => get_queried_object_id(),
			'operator'         => 'IN',
			'include_children' => false,
		) ) );
	} );
}

// Breadcrumb: replace uncategorized with Blogs.
add_filter( 'woocommerce_get_breadcrumb', function ( $crumbs ) {
	foreach ( $crumbs as $key => $crumb ) {
		if ( strpos( $crumb[1], 'category/uncategorized' ) !== false ) {
			$crumbs[ $key ] = array( 'Blogs', site_url( '/blogs/' ) );
		}
	}
	return $crumbs;
} );

// Our Brands taxonomy — rename labels and slug.
add_action( 'init', 'rename_product_brands_labels' );
function rename_product_brands_labels() {
	global $wp_taxonomies;
	if ( ! isset( $wp_taxonomies['product_brand'] ) ) return;
	$labels              = &$wp_taxonomies['product_brand']->labels;
	$labels->name        = 'Brands';
	$labels->singular_name = 'Brands';
	$labels->menu_name   = 'Brands';
}

add_filter( 'register_taxonomy_product_brand', 'customise_product_brand_slug' );
function customise_product_brand_slug( $tax ) {
	$tax['rewrite']['slug'] = 'brands';
	return $tax;
}

add_action( 'admin_menu', 'change_brand_menu_label', 999 );
function change_brand_menu_label() {
	global $submenu;
	if ( ! isset( $submenu['edit.php?post_type=product'] ) ) return;
	foreach ( $submenu['edit.php?post_type=product'] as $key => $value ) {
		if ( $value[0] === 'Brands' ) {
			$submenu['edit.php?post_type=product'][ $key ][0] = 'Our Brands';
		}
	}
}


// ============================================================
// SHOP / CATALOG SORTING
// ============================================================

// Remove popularity, add custom sort options.
add_filter( 'woocommerce_catalog_orderby', 'custom_woocommerce_catalog_orderby' );
add_filter( 'woocommerce_default_catalog_orderby_options', 'custom_woocommerce_catalog_orderby' );
function custom_woocommerce_catalog_orderby( $options ) {
	unset( $options['popularity'] );
	$options['price']       = 'Sort by cheapest first';
	$options['custom_sort'] = 'Sort by Custom Rule';
	return $options;
}

// Separate full catalog orderby options for the frontend filter widget.
add_filter( 'woocommerce_catalog_orderby', 'custom_catalog_orderby_options' );
function custom_catalog_orderby_options( $options ) {
	return array(
		'menu_order' => 'Default sorting',
		'rating'     => 'Top Rated',
		'date'       => 'Newest First',
		'price'      => 'Lowest Price',
		'price-desc' => 'Highest Price',
	);
}

add_action( 'pre_get_posts', 'custom_woocommerce_orderby_logic' );
function custom_woocommerce_orderby_logic( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) return;
	if ( is_shop() && isset( $_GET['orderby'] ) && $_GET['orderby'] === 'custom_sort' ) {
		$query->set( 'meta_key', '_price' );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'order', 'ASC' );
	}
	if ( $query->is_search() ) {
		$query->set( 'post_type', array( 'product', 'page' ) );
	}
}

// Sort search results: products first, then by price desc.
add_filter( 'posts_clauses', 'custom_search_orderby_product_price_desc', 10, 2 );
function custom_search_orderby_product_price_desc( $clauses, $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) return $clauses;
	global $wpdb;
	$clauses['join']    .= " LEFT JOIN {$wpdb->postmeta} AS search_price_meta ON ({$wpdb->posts}.ID = search_price_meta.post_id AND search_price_meta.meta_key = '_price')";
	$clauses['orderby']  = "CASE WHEN {$wpdb->posts}.post_type = 'product' THEN 0 ELSE 1 END ASC, CAST(search_price_meta.meta_value AS DECIMAL(20,6)) DESC, {$wpdb->posts}.post_title ASC";
	return $clauses;
}

// SKU search — include products whose SKU matches the search term.
add_filter( 'posts_where', function ( $where, $wp_query ) {
	global $wpdb;
	if ( is_admin() || ! $wp_query->is_main_query() ) return $where;
	$search_term = trim( $wp_query->get( 's' ) );
	if ( empty( $search_term ) ) return $where;
	$like_pattern = '%' . $wpdb->esc_like( $search_term ) . '%';
	$subquery     = $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s", $like_pattern );
	$where       .= " OR {$wpdb->posts}.ID IN ($subquery)";
	return $where;
}, 10, 2 );

add_filter( 'loop_shop_per_page', 'set_products_per_page', 20 );
function set_products_per_page() {
	return 30;
}


// ============================================================
// MY ACCOUNT — menus, endpoints, content
// ============================================================

add_filter( 'woocommerce_account_menu_items', 'custom_account_menu_items_order', 10, 1 );
function custom_account_menu_items_order( $items ) {
	unset( $items['dashboard'] );
	unset( $items['edit-account'] );
	return array(
		'edit-account'    => __( 'My Account', 'astra-child' ),
		'my-quotes'       => __( 'My Quotes', 'astra-child' ),
		'orders'          => __( 'My Past Orders', 'astra-child' ),
		'cart'            => __( 'My Shopping Cart', 'astra-child' ),
		'wishlist'        => __( 'My Wish Lists', 'astra-child' ),
		'edit-address'    => __( 'Shipping Address', 'astra-child' ),
		'customer-logout' => __( 'Logout', 'astra-child' ),
	);
}

add_action( 'init', 'custom_register_my_account_endpoints' );
function custom_register_my_account_endpoints() {
	foreach ( array( 'orders', 'invoices', 'my-quotes', 'cart' ) as $endpoint ) {
		add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES );
	}
}

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'my-quotes';
	return $vars;
} );

add_action( 'woocommerce_account_my-quotes_endpoint', function () {
	echo do_shortcode( '[stars_quote_page]' );
} );

add_action( 'woocommerce_account_quotes_endpoint', 'quotes_content' );
function quotes_content() {
	echo '<h3>' . esc_html__( 'My Quotes', 'astra-child' ) . '</h3>';
	echo '<p>' . esc_html__( 'Display quotes here (requires a custom implementation or plugin).', 'astra-child' ) . '</p>';
}

add_action( 'woocommerce_account_cart_endpoint', 'cart_content' );
function cart_content() {
	echo '<div class="custom-cart-container">' . do_shortcode( '[woocommerce_cart]' ) . '</div>';
}


// ============================================================
// MY ACCOUNT — Invoices
// ============================================================

add_action( 'woocommerce_account_invoices_endpoint', 'invoices_content' );
function invoices_content() {
	global $wpdb;

	$user_id     = get_current_user_id();
	$customer_id = get_the_author_meta( 'myob_customer_id', $user_id );
	$table_name  = $wpdb->prefix . 'myob_invoices';

	echo '<div class="invoices-main-container">';
	echo '<div class="invoices-header-container">';
	echo '<h2>' . esc_html__( 'My Invoices', 'astra-child' ) . '</h2>';
	echo '<div class="invoice-filter-container">';
	echo '<form action="" id="invoice-header-filter" method="get">';
	echo '<div class="invoice-filters">';
	echo '<div class="date-range-picker-container">';
	echo '<img src="https://fhs.com.au/wp-content/uploads/2025/04/calendar-1.svg">';
	echo '<input class="form-control form-control-solid" id="kt_daterangepicker_5" placeholder="' . esc_attr__( 'Pick Date Range', 'astra-child' ) . '" autocomplete="off">';
	echo '<input type="hidden" id="start_date" name="start_date">';
	echo '<input type="hidden" id="end_date" name="end_date">';
	echo '</div>';

	// FIX: use prepare() to prevent SQL injection.
	$status_rows = empty( $customer_id )
		? array()
		: $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT status FROM {$table_name} WHERE customer_id = %s", $customer_id ) );

	echo '<select name="status" id="invoice-status">';
	echo '<option value="">' . esc_html__( 'Status', 'astra-child' ) . '</option>';
	foreach ( $status_rows as $status ) {
		$selected = ( isset( $_GET['status'] ) && $_GET['status'] === $status ) ? 'selected' : '';
		echo '<option value="' . esc_attr( $status ) . '" ' . $selected . '>' . esc_html( ucwords( $status ) ) . '</option>';
	}
	echo '</select>';
	echo '</div></form></div></div>';

	if ( empty( $customer_id ) ) {
		echo '<div class="woocommerce-info"><p>' . esc_html__( 'No customer ID found for your account.', 'astra-child' ) . '</p></div></div>';
		return;
	}

	$per_page = 10;
	$paged    = absint( $_GET['pg'] ?? 1 );
	$offset   = ( $paged - 1 ) * $per_page;

	$conditions = array( 'customer_id = %s' );
	$params     = array( $customer_id );

	if ( ! empty( $_GET['start_date'] ) && ! empty( $_GET['end_date'] ) ) {
		$start = DateTime::createFromFormat( 'd-M-Y', sanitize_text_field( $_GET['start_date'] ) );
		$end   = DateTime::createFromFormat( 'd-M-Y', sanitize_text_field( $_GET['end_date'] ) );
		if ( $start && $end ) {
			$conditions[] = 'date BETWEEN %s AND %s';
			$params[]     = $start->format( 'Y-m-d' );
			$params[]     = $end->format( 'Y-m-d' );
		}
	}

	if ( ! empty( $_GET['status'] ) ) {
		$conditions[] = 'status = %s';
		$params[]     = sanitize_text_field( $_GET['status'] );
	}

	$where_clause = implode( ' AND ', $conditions );

	$total_items = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}", $params ) );
	$total_pages = ceil( $total_items / $per_page );
	$invoices    = $wpdb->get_results( $wpdb->prepare(
		"SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY date DESC LIMIT %d OFFSET %d",
		array_merge( $params, array( $per_page, $offset ) )
	) );

	if ( empty( $invoices ) ) {
		echo '<div class="woocommerce-message woocommerce-info">' . esc_html__( 'No invoices have been found yet.', 'astra-child' ) . '</div></div>';
		return;
	}

	echo '<table class="woocommerce-orders-table woocommerce-MyAccount-invoices shop_table"><thead><tr>
		<th>#INVOICE</th><th>DATE</th><th>DUE DATE</th><th>Purchase Order Number</th>
		<th>AMOUNT</th><th>OUTSTANDING</th><th>STATUS</th><th>ACTION</th>
	</tr></thead><tbody>';

	foreach ( $invoices as $invoice ) {
		echo '<tr>
			<td>#' . esc_html( $invoice->invoice_number ) . '</td>
			<td>' . esc_html( date( 'd/m/Y', strtotime( $invoice->date ) ) ) . '</td>
			<td>' . ( ! empty( $invoice->due_date ) ? esc_html( date( 'd/m/Y', strtotime( $invoice->due_date ) ) ) : '-' ) . '</td>
			<td>' . esc_html( $invoice->po_number ) . '</td>
			<td>$' . number_format( $invoice->amount, 2 ) . '</td>
			<td>$' . number_format( $invoice->outstanding, 2 ) . '</td>
			<td>' . format_invoice_status( $invoice->status ) . '</td>
			<td><a class="download-invoice-pdf" href="/wp-json/invoice/file_download?invoice_uid=' . esc_attr( $invoice->myob_uid ) . '&type=' . esc_attr( $invoice->invoice_type ) . '" target="_blank"><i class="icofont-eye-alt"></i></a></td>
		</tr>';
	}
	echo '</tbody></table>';

	if ( $total_pages > 1 ) {
		echo '<div class="woocommerce-pagination">';
		echo paginate_links( array(
			'base'      => trailingslashit( home_url( '/my-account/invoices' ) ) . '?pg=%#%',
			'format'    => '',
			'current'   => $paged,
			'total'     => $total_pages,
			'prev_text' => __( '« Prev' ),
			'next_text' => __( 'Next »' ),
		) );
		echo '</div>';
	}
	echo '</div>';
}

function format_invoice_status( $status ) {
	return '<span class="invoice-status ' . esc_attr( strtolower( $status ) ) . '">' . esc_html( strtoupper( $status ) ) . '</span>';
}


// ============================================================
// MY ACCOUNT — Orders (columns, filters, redirects)
// ============================================================

// Remove a plugin-added column we don't want.
add_action( 'plugins_loaded', function () {
	global $wp_filter;
	if ( ! isset( $wp_filter['woocommerce_account_orders_columns'] ) ) return;
	foreach ( $wp_filter['woocommerce_account_orders_columns']->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $key => $callback ) {
			if ( is_array( $callback['function'] ) && is_object( $callback['function'][0] ) ) {
				$object = $callback['function'][0];
				if ( method_exists( $object, 'add_my_account_orders_column' ) ) {
					remove_filter( 'woocommerce_account_orders_columns', array( $object, 'add_my_account_orders_column' ), $priority );
				}
			}
		}
	}
} );

/**
 * Single authoritative column definition for My Account > Orders.
 * Priority 30 so it runs after all additive filters (priority 20).
 */
add_filter( 'woocommerce_my_account_my_orders_columns', 'fhs_define_orders_columns', 30 );
function fhs_define_orders_columns( $columns ) {
	$user_id       = get_current_user_id();
	$payment_terms = get_user_meta( $user_id, 'myob_payment_terms', true );

	$cols = array(
		'order-number'         => __( 'Orders', 'woocommerce' ),
		'order-date'           => __( 'Order Date', 'woocommerce' ),
		'fulfilment_method'    => __( 'Fulfilment', 'woocommerce' ),
		'required_date'        => __( 'Required Date', 'woocommerce' ),
		'product_order_number' => __( 'Purchase Order Number', 'woocommerce' ),
		'order-total'          => __( 'Amount', 'woocommerce' ),
		'outstanding-amount'   => __( 'Outstanding', 'woocommerce' ),
		'order-actions'        => __( 'Actions', 'woocommerce' ),
	);

	if ( $payment_terms === 'DayOfMonthAfterEOM' ) {
		$cols['pay_now'] = __( 'Pay Now', 'woocommerce' );
	}

	return $cols;
}

// Column data renderers.
add_action( 'woocommerce_my_account_my_orders_column_product_order_number', function ( $order ) {
	$val = get_post_meta( $order->get_id(), '_product_order_number', true );
	echo $val ? esc_html( $val ) : '-';
} );

add_action( 'woocommerce_my_account_my_orders_column_required_date', function ( $order ) {
	$val = get_post_meta( $order->get_id(), '__order_required_date', true );
	echo $val ? esc_html( $val ) : '-';
} );

add_action( 'woocommerce_my_account_my_orders_column_pay_now', function ( $order ) {
	if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
		echo '<a class="button pay" href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay Now', 'woocommerce' ) . '</a>';
	} else {
		echo '-';
	}
} );

add_action( 'woocommerce_my_account_my_orders_column_outstanding-amount', 'display_outstanding_amount_column' );
function display_outstanding_amount_column( $order ) {
	$outstanding = in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ? $order->get_total() : 0;
	echo $outstanding > 0 ? wc_price( $outstanding ) : '–';
}

add_action( 'woocommerce_my_account_my_orders_column_fulfilment_method', function ( $order ) {
	$method = (string) get_post_meta( $order->get_id(), '_fhs_fulfilment_method', true );
	if ( $method !== 'pickup' && $method !== 'delivery' ) { echo '-'; return; }
	echo esc_html( function_exists( 'fhs_get_fulfilment_label' )
		? fhs_get_fulfilment_label( $method )
		: ( $method === 'pickup' ? __( 'Pick up', 'woocommerce' ) : __( 'Delivery', 'woocommerce' ) )
	);
} );

// Remove default Pay/Cancel from order actions (Pay Now column handles it).
add_filter( 'woocommerce_my_account_my_orders_actions', function ( $actions, $order ) {
	if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
		unset( $actions['pay'], $actions['cancel'] );
	}
	return $actions;
}, 10, 2 );

// Date filter above the orders table.
add_action( 'woocommerce_before_account_orders', 'add_date_filter_form' );
function add_date_filter_form( $has_orders ) {
	echo '<div class="order-filter-container">';
	echo '<h2>' . esc_html__( 'My Past Orders', 'astra-child' ) . '</h2>';
	echo '<form action="#" id="order-header-filter" method="get"><div class="order-filters">';
	echo '<div class="date-range-picker-container">';
	echo '<img src="https://fhs.com.au/wp-content/uploads/2025/04/calendar-1.svg">';
	echo '<input class="form-control form-control-solid" id="kt_daterangepicker_4" placeholder="' . esc_attr__( 'Pick Date Range', 'astra-child' ) . '" autocomplete="off">';
	echo '<input type="hidden" id="start_date" name="start_date">';
	echo '<input type="hidden" id="end_date" name="end_date">';
	echo '</div></div></form></div>';
}

add_filter( 'woocommerce_my_account_my_orders_query', 'filter_my_account_orders_by_date_status_and_order_id', 10, 1 );
function filter_my_account_orders_by_date_status_and_order_id( $query_args ) {
	if ( ! empty( $_GET['start_date'] ) && ! empty( $_GET['end_date'] ) ) {
		$query_args['date_query'] = array( array(
			'after'     => sanitize_text_field( $_GET['start_date'] ) . ' 00:00:00',
			'before'    => sanitize_text_field( $_GET['end_date'] ) . ' 23:59:59',
			'inclusive' => true,
		) );
	}
	if ( ! empty( $_GET['status'] ) ) {
		$status = sanitize_text_field( $_GET['status'] );
		if ( array_key_exists( $status, wc_get_order_statuses() ) ) {
			$query_args['post_status'] = $status;
		}
	}
	if ( ( ! empty( $_GET['start_date'] ) || ! empty( $_GET['end_date'] ) || ! empty( $_GET['status'] ) ) && empty( $_GET['paged'] ) ) {
		$query_args['paged'] = 1;
	}
	return $query_args;
}

// Redirect /cart/ → /my-account/cart/.
add_action( 'template_redirect', function () {
	if ( $_SERVER['REQUEST_URI'] === '/cart/' ) {
		wp_redirect( '/my-account/cart/', 301 );
		exit();
	}
} );

add_action( 'woocommerce_my_account_my_orders_column_reorder', 'custom_reorder_redirect', 20 );
function custom_reorder_redirect() {
	wp_safe_redirect( home_url( '/my-account/cart/' ) );
	exit;
}

add_filter( 'woocommerce_get_cart_url', 'custom_cart_url' );
function custom_cart_url() {
	return home_url( '/my-account/cart/' );
}


// ============================================================
// ADMIN — Users list (MYOB columns + new-user form)
// ============================================================

add_filter( 'manage_users_columns', 'new_modify_user_table' );
function new_modify_user_table( $columns ) {
	$columns['myob_uid']              = 'MYOB UID';
	$columns['myob_payment_terms']    = 'Payment Terms';
	$columns['myob_user_designation'] = 'User Designation';
	return $columns;
}

add_filter( 'manage_users_custom_column', 'new_modify_user_table_row', 10, 3 );
function new_modify_user_table_row( $val, $column_name, $user_id ) {
	switch ( $column_name ) {
		case 'myob_uid':              return get_the_author_meta( 'myob_customer_id', $user_id );
		case 'myob_payment_terms':    return get_the_author_meta( 'myob_payment_terms', $user_id );
		case 'myob_user_designation': return get_the_author_meta( 'myob_user_designation', $user_id );
		default:                      return $val;
	}
}

add_action( 'user_new_form', 'add_custom_user_fields_admin' );
function add_custom_user_fields_admin( $operation ) {
	$fields = array(
		'registration_company_name' => 'Registration Company Name',
		'trading_company_name'      => 'Trading Company Name',
		'billing_first_name'        => 'Billing First Name',
		'billing_last_name'         => 'Billing Last Name',
		'billing_email'             => 'Billing Email Address',
		'billing_phone'             => 'Billing Phone Number',
		'phone_number'              => 'Phone Number',
		'abn_number'                => 'ABN Number',
		'business_address'          => 'Business Address',
		'shipping_address'          => 'Shipping Address',
		'recovery_email'            => 'Email for Password Recovery',
	);
	echo '<h3>' . esc_html__( 'Business Details', 'astra-child' ) . '</h3><table class="form-table">';
	foreach ( $fields as $key => $label ) {
		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td><input type="text" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" class="regular-text"></td></tr>';
	}
	echo '</table>';
}

add_action( 'user_register', 'save_custom_user_fields_admin' );
function save_custom_user_fields_admin( $user_id ) {
	$fields = array( 'registration_company_name','trading_company_name','billing_first_name','billing_last_name','billing_email','billing_phone','phone_number','abn_number','business_address','shipping_address','recovery_email' );
	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_user_meta( $user_id, 'ms_fhs_custom_' . $field, sanitize_text_field( $_POST[ $field ] ) );
		}
	}
}


// ============================================================
// ADMIN — Orders list (fulfilment method column)
// ============================================================

// Classic post list (shop_order).
add_filter( 'manage_edit-shop_order_columns', 'fhs_admin_orders_add_fulfilment_column', 20 );
function fhs_admin_orders_add_fulfilment_column( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'order_status' === $key ) {
			$new['fhs_fulfilment_method'] = __( 'Fulfilment', 'woocommerce' );
		}
	}
	return isset( $new['fhs_fulfilment_method'] ) ? $new : array_merge( $new, array( 'fhs_fulfilment_method' => __( 'Fulfilment', 'woocommerce' ) ) );
}

add_action( 'manage_shop_order_posts_custom_column', 'fhs_admin_orders_render_fulfilment_column', 20, 2 );
function fhs_admin_orders_render_fulfilment_column( $column, $post_id ) {
	if ( 'fhs_fulfilment_method' !== $column ) return;
	$method = (string) get_post_meta( $post_id, '_fhs_fulfilment_method', true );
	if ( $method !== 'pickup' && $method !== 'delivery' ) { echo '-'; return; }
	echo esc_html( function_exists( 'fhs_get_fulfilment_label' ) ? fhs_get_fulfilment_label( $method ) : ( $method === 'pickup' ? __( 'Pick up', 'woocommerce' ) : __( 'Delivery', 'woocommerce' ) ) );
}

// HPOS orders list (wc-orders).
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'fhs_admin_orders_add_fulfilment_column', 20 );

add_action( 'manage_woocommerce_page_wc-orders_custom_column', function ( $column, $order_or_id ) {
	if ( 'fhs_fulfilment_method' !== $column ) return;
	$order  = is_a( $order_or_id, 'WC_Order' ) ? $order_or_id : wc_get_order( $order_or_id );
	if ( ! $order ) { echo '-'; return; }
	$method = (string) $order->get_meta( '_fhs_fulfilment_method' );
	if ( $method !== 'pickup' && $method !== 'delivery' ) { echo '-'; return; }
	echo esc_html( function_exists( 'fhs_get_fulfilment_label' ) ? fhs_get_fulfilment_label( $method ) : ( $method === 'pickup' ? __( 'Pick up', 'woocommerce' ) : __( 'Delivery', 'woocommerce' ) ) );
}, 20, 2 );

// Admin order detail — fulfilment method + PO number + PO file.
add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	// Fulfilment method.
	$method = (string) $order->get_meta( '_fhs_fulfilment_method' );
	$label  = ( $method === 'pickup' || $method === 'delivery' )
		? ( function_exists( 'fhs_get_fulfilment_label' ) ? fhs_get_fulfilment_label( $method ) : ( $method === 'pickup' ? __( 'Pick up', 'woocommerce' ) : __( 'Delivery', 'woocommerce' ) ) )
		: '-';
	echo '<p><strong>' . esc_html__( 'Fulfilment Method', 'woocommerce' ) . ':</strong> ' . esc_html( $label ) . '</p>';

	// Product order number.
	$po_number = get_post_meta( $order->get_id(), '_product_order_number', true );
	if ( $po_number ) {
		echo '<p><strong>' . esc_html__( 'Product Order Number', 'woocommerce' ) . ':</strong> ' . esc_html( $po_number ) . '</p>';
	}

	// PO file.
	$file = $order->get_meta( '_pay_later_po_file' );
	if ( $file ) {
		echo '<p><strong>' . esc_html__( 'PO File', 'woocommerce' ) . ':</strong> <a href="' . esc_url( $file ) . '" target="_blank">' . esc_html__( 'View File', 'woocommerce' ) . '</a></p>';
	}
}, 25 );


// ============================================================
// SINGLE PRODUCT
// ============================================================

// Material / colour variation tabs above variations form.
add_action( 'woocommerce_before_variations_form', 'add_custom_variation_tabs' );
function add_custom_variation_tabs() {
	global $product;
	if ( ! $product->is_type( 'variable' ) ) return;

	foreach ( array( 'material', 'color' ) as $attr ) {
		$values = $product->get_attribute( $attr );
		if ( ! $values ) continue;
		$css_class = $attr === 'material' ? 'custom-variations-container' : 'custom-color-container';
		$inner     = $attr === 'material' ? 'variations-tabs' : 'color-options';
		echo '<div class="' . esc_attr( $css_class ) . '">';
		echo '<div class="variatoins-label"><span>' . esc_html( $attr ) . ': </span><span class="dynamic-' . esc_attr( $attr ) . '-input-content var-dynamic-content"></span></div>';
		echo '<div class="' . esc_attr( $inner ) . '">';
		foreach ( array_map( 'trim', explode( '|', $values ) ) as $value ) {
			if ( $attr === 'material' ) {
				echo '<p class="variation-tab" data-value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '</p>';
			} else {
				echo '<div class="color-option" data-color="' . esc_attr( $value ) . '" style="--shadow-color:' . esc_attr( $value ) . ';background-color:' . esc_attr( $value ) . ';"></div>';
			}
		}
		echo '</div></div>';
	}
}

// ACF product download button.
add_action( 'woocommerce_single_product_summary', 'show_acf_product_download', 25 );
function show_acf_product_download() {
	$download = get_field( 'product_download' );
	if ( ! $download ) return;
	$url  = is_array( $download ) ? $download['url']      : $download;
	$name = is_array( $download ) ? $download['filename'] : 'Download File';
	echo '<p><a href="' . esc_url( $url ) . '" class="button" download>' . esc_html( $name ) . '</a></p>';
}

// Load all variations (important for configurator).
add_filter( 'woocommerce_ajax_variation_threshold', function () { return 9999; } );

// "Complete Your Kit" shortcode.
add_action( 'init', function () {
	add_shortcode( 'complete_kit', 'render_complete_kit_shortcode' );
} );

function render_complete_kit_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'field'      => 'complete-your-kit',
		'product_id' => 0,
		'fallback'   => '/test.png',
	), $atts, 'complete_kit' );

	$field      = sanitize_text_field( $atts['field'] );
	$fallback   = esc_url_raw( $atts['fallback'] );
	$product_id = intval( $atts['product_id'] );

	if ( ! $product_id ) {
		global $product;
		if ( ! $product || ! is_object( $product ) ) return '';
		$product_id = $product->get_id();
	} else {
		$product = wc_get_product( $product_id );
		if ( ! $product ) return '';
	}

	$meta = get_post_meta( $product_id, $field, true );
	if ( ! $meta ) return '';

	$tokens = array_filter( array_map( 'trim', explode( ',', $meta ) ) );
	$ids    = array();
	foreach ( $tokens as $t ) {
		if ( is_numeric( $t ) ) {
			$ids[] = absint( $t );
		} else {
			$by_sku = wc_get_product_id_by_sku( $t );
			if ( $by_sku ) $ids[] = $by_sku;
		}
	}
	$ids = array_values( array_unique( array_filter( $ids ) ) );
	if ( empty( $ids ) ) return '';

	$wrap_in_dropdown = ( $field === 'optional_extras' );
	ob_start();

	if ( $wrap_in_dropdown ) {
		echo '<details class="features-dropdown"><summary class="dropdown-header"><span><i class="icofont icofont-file-alt" style="margin-right:7px;"></i> Optional Extras</span><span class="dropdown-icon"><i class="icofont-rounded-down"></i></span></summary><div class="dropdown-content">';
	} else {
		echo '<h2 class="cyt-heading">Complete Your Kit</h2>';
	}

	echo '<div class="kit-wrapper"><div class="kit-list">';
	foreach ( $ids as $aid ) {
		$ap = wc_get_product( $aid );
		if ( ! $ap ) continue;
		$thumb         = $ap->get_image( 'thumbnail' ) ?: '<img src="' . esc_url( $fallback ) . '" alt="' . esc_attr( $ap->get_name() ) . '">';
		$is_variable   = $ap->is_type( 'variable' ) || $ap->is_type( 'variable-subscription' );
		$is_purchasable = $ap->is_purchasable() && $ap->is_in_stock();
		echo '<div class="kit-item" data-id="' . esc_attr( $aid ) . '">';
		echo '<div class="kit-thumb">' . $thumb . '</div>';
		echo '<div class="kit-info"><h4 class="kit-title">' . esc_html( $ap->get_name() ) . '</h4><div class="kit-price">' . $ap->get_price_html() . '</div></div>';
		echo '<div class="kit-action">';
		if ( $is_variable ) {
			echo '<a class="kit-view" href="' . esc_url( get_permalink( $aid ) ) . '" target="_blank">View options</a>';
		} elseif ( ! $is_purchasable ) {
			echo '<span class="kit-unavailable">Unavailable</span>';
		} else {
			echo '<button class="kit-add" data-product_id="' . esc_attr( $aid ) . '">Add to Cart</button>';
		}
		echo '</div></div>';
	}
	echo '</div></div>';

	if ( $wrap_in_dropdown ) {
		echo '</div></details>';
	}
	?>
	<script>
	(function(){
		var defaultThumb = <?php echo wp_json_encode( $fallback ); ?>;
		function getWcAjaxUrl(action){
			if(typeof wc_add_to_cart_params!=='undefined'&&wc_add_to_cart_params.wc_ajax_url){return wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%',action);}
			return window.location.origin+'/?wc-ajax='+action;
		}
		function addToCartViaWcAjax(id,qty){
			return fetch(getWcAjaxUrl('add_to_cart'),{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:new URLSearchParams({product_id:id,quantity:qty||1})}).then(function(r){return r.json().catch(function(){return{error:'invalid_json'};});});
		}
		function flash(el){if(!el)return;el.classList.remove('flash');void el.offsetWidth;el.classList.add('flash');}
		function toast(name){try{if(window.Toastify){Toastify({text:name||'Added',duration:2500}).showToast();return;}}catch(e){}console.log('toast:',name);}
		document.addEventListener('click',function(e){
			var t=e.target;
			if(t.matches('.kit-add')){
				e.preventDefault();var id=t.getAttribute('data-product_id');if(!id)return;
				t.disabled=true;var item=t.closest('.kit-item');
				var name=item&&item.querySelector('.kit-title')?item.querySelector('.kit-title').textContent.trim():'';
				addToCartViaWcAjax(id,1).then(function(r){t.disabled=false;if(r&&(r.success||r.fragments)){try{jQuery(document.body).trigger('wc_fragment_refresh');}catch(e){}toast(name);flash(item);}else{toast(name);}}).catch(function(err){t.disabled=false;console.error(err);toast(name);});
			}
		},false);
	})();
	</script>
	<?php
	return ob_get_clean();
}


// ============================================================
// CART
// ============================================================

add_action( 'woocommerce_before_cart_table', 'cart_section_header_and_filter', 10 );
function cart_section_header_and_filter() {
	echo '<div class="cart-heading-wrapper">';
	echo '<h3>' . esc_html__( 'My Shopping Cart', 'astra-child' ) . '</h3>';
	echo '<div class="filter-wrapper"><div class="search-box"><input type="text" id="cart-search" placeholder="' . esc_attr__( 'Search by name...', 'astra-child' ) . '"><span class="search-icon"><i class="icofont-search-1"></i></span></div></div>';
	echo '</div>';
}

add_action( 'woocommerce_cart_collaterals_custom', 'custom_cart_price_details', 10 );
function custom_cart_price_details() {
	if ( ! WC()->cart ) return;
	WC()->cart->calculate_totals();
	$cart = WC()->cart;
	ob_start();
	?>
	<div class="custom-cart-totals">
		<h2><span class="icofont-price"></span> <?php esc_html_e( 'Price Details', 'astra-child' ); ?></h2>
		<table class="shop_table shop_table_responsive custom-price-table"><tbody>
			<tr class="cart-subtotal">
				<th><?php echo esc_html( 'Subtotal (' . $cart->get_cart_contents_count() . ' items)' ); ?></th>
				<td><?php echo wc_price( $cart->get_subtotal() ); ?> <span class="gst-message">(Ex GST)</span></td>
			</tr>
			<?php if ( $cart->get_discount_total() > 0 ) : ?>
			<tr class="cart-discount">
				<th><?php esc_html_e( 'Total Discount', 'woocommerce' ); ?></th>
				<td><?php echo wc_price( -$cart->get_discount_total() ); ?></td>
			</tr>
			<?php endif; ?>
			<?php foreach ( $cart->get_fees() as $fee ) : ?>
			<tr class="cart-fee">
				<th><?php echo esc_html( $fee->name ); ?></th>
				<td><?php echo wc_price( $fee->amount ); ?></td>
			</tr>
			<?php endforeach; ?>
			<tr class="cart-shipping">
				<th><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></th>
				<td><?php esc_html_e( 'Shipping will be calculated on checkout', 'woocommerce' ); ?></td>
			</tr>
			<?php if ( wc_tax_enabled() ) : ?>
			<tr class="cart-tax">
				<th><?php esc_html_e( 'GST', 'astra-child' ); ?></th>
				<td><?php echo wc_price( $cart->get_total_tax() ); ?></td>
			</tr>
			<?php endif; ?>
			<tr class="order-total">
				<th><?php esc_html_e( 'Grand Total', 'woocommerce' ); ?></th>
				<td><strong><?php echo wc_price( $cart->get_total( 'edit' ) ); ?></strong> <span class="gst-message">(Inc GST)</span></td>
			</tr>
		</tbody></table>
		<div class="secure-cart">
			<i class="icofont-safety"></i>
			<p><?php esc_html_e( 'Safe and Secure Payments. Trusted Australian Industry Supplier.', 'astra-child' ); ?></p>
		</div>
		<div class="wc-proceed-to-checkout">
			<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
		</div>
	</div>
	<?php
	echo ob_get_clean();
}

// Merge duplicate line items into one.
add_action( 'woocommerce_before_calculate_totals', 'merge_duplicated_products_in_cart', 20, 1 );
function merge_duplicated_products_in_cart( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
	if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;
	$seen = array();
	foreach ( $cart->get_cart() as $key => $item ) {
		$pid = $item['data']->get_id();
		if ( isset( $seen[ $pid ] ) ) {
			$cart->set_quantity( $seen[ $pid ]['key'], $seen[ $pid ]['quantity'] + $item['quantity'] );
			$cart->remove_cart_item( $key );
		} else {
			$seen[ $pid ] = array( 'key' => $key, 'quantity' => $item['quantity'] );
		}
	}
}

// AJAX cart quantity update.
add_action( 'wp_ajax_update_cart_item_quantity',        'update_cart_item_quantity_callback' );
add_action( 'wp_ajax_nopriv_update_cart_item_quantity', 'update_cart_item_quantity_callback' );
function update_cart_item_quantity_callback() {
	check_ajax_referer( 'update-cart', 'security' );
	$key      = sanitize_text_field( $_POST['cart_item_key'] );
	$quantity = intval( $_POST['quantity'] );
	$cart     = WC()->cart;
	$item     = $cart->get_cart_item( $key );
	if ( $item && $quantity >= 0 ) {
		$cart->set_quantity( $key, $quantity, true );
		$cart->calculate_totals();
		$cart->maybe_set_cart_cookies();
		wp_send_json_success( array( 'message' => 'Cart updated successfully' ) );
	} else {
		wp_send_json_error( array( 'message' => 'Invalid cart item or quantity' ) );
	}
	wp_die();
}

// Remove "Shipping Amount" fee added by Machship to avoid double-display.
add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
	$fees = $cart->get_fees();
	if ( ! $fees ) return;
	foreach ( $fees as $key => $fee ) {
		if ( $fee->name === 'Shipping Amount' ) unset( $fees[ $key ] );
	}
	$cart->fees_api()->set_fees( $fees );
}, 999 );


// ============================================================
// CHECKOUT — field ordering, labels, validation
// ============================================================

/**
 * Single authoritative woocommerce_checkout_fields filter.
 * Priority 999 so it runs after WC core and all plugins.
 */
add_filter( 'woocommerce_checkout_fields', 'fhs_customize_checkout_fields', 999 );
function fhs_customize_checkout_fields( $fields ) {
	$mode = fhs_get_checkout_fulfilment_method();

	// ── Billing field priorities ──
	$billing_priorities = array(
		'billing_first_name' => 10,
		'billing_last_name'  => 20,
		'billing_phone'      => 30,
		'billing_email'      => 40,
		'billing_address_1'  => 60,
		'billing_address_2'  => 65,
		'billing_city'       => 70,
		'billing_postcode'   => 80,
		'billing_country'    => 90,
	);
	foreach ( $billing_priorities as $key => $priority ) {
		if ( isset( $fields['billing'][ $key ] ) ) {
			$fields['billing'][ $key ]['priority'] = $priority;
		}
	}

	// ── Billing label overrides ──
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['required'] = true;
		$fields['billing']['billing_phone']['label']    = __( 'Phone Number', 'woocommerce' );
		$fields['billing']['billing_phone']['custom_attributes']['required'] = 'required';
		$fields['billing']['billing_phone']['validate'] = array_values( array_unique( array_merge(
			is_array( $fields['billing']['billing_phone']['validate'] ?? null ) ? $fields['billing']['billing_phone']['validate'] : array(),
			array( 'required' )
		) ) );
	}
	if ( isset( $fields['billing']['billing_address_1'] ) ) {
		$fields['billing']['billing_address_1']['label'] = __( 'Address Line 1', 'woocommerce' );
	}
	if ( isset( $fields['billing']['billing_address_2'] ) ) {
		$fields['billing']['billing_address_2']['label'] = __( 'Address Line 2', 'woocommerce' );
	}

	// ── Strip update_totals_on_change from billing fields ──
	if ( ! empty( $fields['billing'] ) && is_array( $fields['billing'] ) ) {
		foreach ( $fields['billing'] as $billing_key => $billing_field ) {
			if ( empty( $fields['billing'][ $billing_key ]['class'] ) || ! is_array( $fields['billing'][ $billing_key ]['class'] ) ) continue;
			$fields['billing'][ $billing_key ]['class'] = array_values( array_filter(
				$fields['billing'][ $billing_key ]['class'],
				function ( $c ) { return 'update_totals_on_change' !== $c; }
			) );
		}
	}

	// ── Remove THWMA checkbox fields that conflict with our shipping flow ──
	unset( $fields['billing']['thwma_hidden_field_billing'], $fields['billing']['thwma_checkbox_shipping'] );
	// Remove PO/date from billing group (rendered separately in payment section).
	unset( $fields['billing']['billing_po_number'], $fields['billing']['billing_required_date'] );

	// ── Pickup mode: shipping fields not required ──
	if ( 'pickup' === $mode && ! empty( $fields['shipping'] ) && is_array( $fields['shipping'] ) ) {
		foreach ( $fields['shipping'] as $field_key => $field_config ) {
			$fields['shipping'][ $field_key ]['required'] = false;
			unset( $fields['shipping'][ $field_key ]['custom_attributes']['required'] );
			if ( isset( $fields['shipping'][ $field_key ]['validate'] ) && is_array( $fields['shipping'][ $field_key ]['validate'] ) ) {
				$fields['shipping'][ $field_key ]['validate'] = array_values( array_filter(
					$fields['shipping'][ $field_key ]['validate'],
					function ( $r ) { return 'required' !== $r; }
				) );
			}
		}
	}

	return $fields;
}

// Label translation overrides (PIN Code → Postcode, etc.).
add_filter( 'gettext', 'force_billing_postcode_label_translation', 1000, 3 );
function force_billing_postcode_label_translation( $translated_text, $text, $domain ) {
	if ( $domain !== 'woocommerce' ) return $translated_text;
	$map = array(
		'PIN Code'       => 'Postcode',
		'Phone'          => 'Phone Number',
		'Town / City'    => 'Suburb / City',
		'Street address' => 'Address Line 1',
	);
	return $map[ $translated_text ] ?? $translated_text;
}

// Default and locale address field labels.
add_filter( 'woocommerce_get_country_locale_default', function ( $locale ) {
	if ( isset( $locale['address_1'] ) ) { $locale['address_1']['label'] = 'Address Line 1'; $locale['address_1']['placeholder'] = 'Address Line 1'; $locale['address_1']['required'] = true; }
	if ( isset( $locale['address_2'] ) ) { $locale['address_2']['label'] = 'Address Line 2'; $locale['address_2']['placeholder'] = 'Address Line 2'; $locale['address_2']['required'] = false; $locale['address_2']['label_class'] = array(); }
	return $locale;
} );

add_filter( 'woocommerce_default_address_fields', function ( $fields ) {
	if ( isset( $fields['address_1'] ) ) { $fields['address_1']['label'] = 'Address Line 1'; $fields['address_1']['placeholder'] = 'Address Line 1'; $fields['address_1']['required'] = true; }
	if ( isset( $fields['address_2'] ) ) { $fields['address_2']['label'] = 'Address Line 2'; $fields['address_2']['placeholder'] = 'Address Line 2'; $fields['address_2']['required'] = false; $fields['address_2']['label_class'] = array(); }
	return $fields;
} );

// Phone number validation — digits only, max 12.
add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	if ( empty( $data['billing_phone'] ) ) return;
	$phone = $data['billing_phone'];
	if ( ! preg_match( '/^[0-9]+$/', $phone ) ) {
		$errors->add( 'billing_phone_error', __( 'Billing Phone must contain digits only (no + or special characters).', 'woocommerce' ) );
		return;
	}
	if ( strlen( $phone ) > 12 ) {
		$errors->add( 'billing_phone_error', __( 'Billing Phone must be less than 12 digits.', 'woocommerce' ) );
	}
}, 10, 2 );

// Save PO number and required date on order creation.
add_action( 'woocommerce_checkout_update_order_meta', 'save_purchase_order_number' );
function save_purchase_order_number( $order_id ) {
	if ( ! empty( $_POST['billing_po_number'] ) ) {
		update_post_meta( $order_id, '_product_order_number', sanitize_text_field( $_POST['billing_po_number'] ) );
	}
	if ( ! empty( $_POST['fhs_required_date'] ) ) {
		update_post_meta( $order_id, '__order_required_date', sanitize_text_field( $_POST['fhs_required_date'] ) );
	}
}

// Force multipart/form-data on checkout (needed for file upload).
add_filter( 'woocommerce_checkout_form_enctype', function () { return 'multipart/form-data'; } );

// Relocate payment section inside the billing form wrapper.
remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
add_action( 'woocommerce_custom_payment_relocation', 'woocommerce_checkout_payment', 20 );

// Ship to billing only = false (keep shipping fields).
add_filter( 'woocommerce_ship_to_billing_address_only', '__return_false' );


// ============================================================
// CHECKOUT — PO file upload + payment extra fields
// ============================================================

add_action( 'fhs_checkout_after_payment_methods', function () {
	$checkout = WC()->checkout();
	echo '<div class="payment-extra-fields-grid">';
	echo '<div id="payment-required-date-wrap" class="payment-extra-field">';
	woocommerce_form_field( 'fhs_required_date', array(
		'type'              => 'date',
		'class'             => array( 'form-row-wide' ),
		'label'             => __( 'Required Date', 'woocommerce' ),
		'required'          => false,
		'custom_attributes' => array( 'min' => current_time( 'Y-m-d' ) ),
	), $checkout->get_value( 'fhs_required_date' ) );
	echo '</div>';

	echo '<div id="pay-later-po-number-wrap" class="pay-later-extra">';
	woocommerce_form_field( 'billing_po_number', array(
		'type'     => 'text',
		'class'    => array( 'form-row-wide' ),
		'label'    => __( 'Purchase Order Number', 'woocommerce' ),
		'required' => false,
	), $checkout->get_value( 'billing_po_number' ) );
	echo '</div>';

	echo '<div id="pay-later-po-upload" class="pay-later-extra form-row form-row-wide">
		<label class="po-upload-box" for="pay_later_po_file">
			<div class="po-upload-content">
				<div class="po-upload-icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none">
						<path d="M12 16V4M12 4L7 9M12 4L17 9" stroke="#000" stroke-width="2"/>
						<path d="M20 16.5V19C20 20.1 19.1 21 18 21H6C4.9 21 4 20.1 4 19V16.5" stroke="#000" stroke-width="2"/>
					</svg>
				</div>
				<div class="po-upload-text"><strong>' . esc_html__( 'Upload Purchase Order', 'astra-child' ) . '</strong><span>(PDF or Image)</span></div>
				<div class="po-file-name">' . esc_html__( 'No file chosen', 'astra-child' ) . '</div>
			</div>
			<input type="file" id="pay_later_po_file" accept=".pdf,.jpg,.jpeg,.png">
			<input type="hidden" name="pay_later_po_file_url" id="pay_later_po_file_url">
		</label>
	</div>';

	echo '</div>';
} );

add_action( 'wp_ajax_upload_po_file',        'upload_po_file' );
add_action( 'wp_ajax_nopriv_upload_po_file', 'upload_po_file' );
function upload_po_file() {
	if ( empty( $_FILES['po_file'] ) ) {
		wp_send_json_error( array( 'message' => 'No file received.' ) );
	}
	$file = $_FILES['po_file'];
	if ( $file['error'] !== UPLOAD_ERR_OK ) {
		$msgs = array( UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize.', UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE.', UPLOAD_ERR_PARTIAL => 'File only partially uploaded.', UPLOAD_ERR_NO_FILE => 'No file uploaded.', UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.', UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.', UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.' );
		wp_send_json_error( array( 'message' => $msgs[ $file['error'] ] ?? 'Unknown upload error.' ) );
	}
	$file_type = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
	if ( empty( $file_type['type'] ) || ! in_array( $file_type['type'], array( 'application/pdf', 'image/jpeg', 'image/png' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Invalid file type. Only PDF and Images are allowed.' ) );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );
	if ( isset( $uploaded['error'] ) ) {
		wp_send_json_error( array( 'message' => $uploaded['error'] ) );
	}
	wp_send_json_success( array( 'url' => $uploaded['url'] ) );
}

// Allow 20 MB uploads for PO files.
add_filter( 'upload_size_limit', function () { return 20 * 1024 * 1024; }, 20 );

// Validate PO fields on checkout submission.
add_action( 'woocommerce_checkout_process', function () {
	$method = sanitize_text_field( (string) ( $_POST['payment_method'] ?? '' ) );
	if ( $method === 'pay_later' || strpos( $method, 'pay_later' ) !== false ) {
		if ( empty( trim( (string) ( $_POST['billing_po_number'] ?? '' ) ) ) ) {
			wc_add_notice( __( 'PO number is required for On Account payments.', 'woocommerce' ), 'error' );
		}
		if ( empty( $_POST['pay_later_po_file_url'] ) ) {
			wc_add_notice( __( 'PO file is required for On Account payments.', 'woocommerce' ), 'error' );
		}
	}
} );

// Save PO file URL to order meta.
add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	if ( ! empty( $_POST['pay_later_po_file_url'] ) ) {
		$order->update_meta_data( '_pay_later_po_file', esc_url_raw( $_POST['pay_later_po_file_url'] ) );
	}
} );

/**
 * Combined checkout JS — Pay Later toggle, PO required toggle, billing trigger guard.
 * Single output on woocommerce_after_checkout_form instead of three separate blocks.
 */
add_action( 'woocommerce_after_checkout_form', 'fhs_checkout_inline_styles_and_scripts' );
function fhs_checkout_inline_styles_and_scripts() {
	?>
	<style>
	.pay-later-extra { display: none; }
	.payment-extra-fields-grid { display: grid; grid-template-columns: 1fr 1fr; column-gap: 18px; align-items: start; }
	#payment-required-date-wrap { grid-column: 1; }
	#pay-later-po-number-wrap   { grid-column: 1; }
	#pay-later-po-upload        { grid-column: 2; grid-row: 1 / span 2; }
	.po-upload-box { display: block; border: 2px dashed #d9d9d9; border-radius: 8px; padding: 18px; cursor: pointer; background: #fafafa; text-align: center; transition: all .2s ease; }
	.po-upload-box:hover { border-color: #000; background: #fff; }
	.po-upload-content { display: flex; flex-direction: column; align-items: center; gap: 6px; }
	.po-file-name { margin-top: 6px; font-size: 14px; }
	.po-spinner { display: inline-block; width: 14px; height: 14px; border: 2px solid rgba(0,0,0,.1); border-top: 2px solid #000; border-radius: 50%; animation: po-spin .8s linear infinite; vertical-align: middle; margin-right: 8px; }
	@keyframes po-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
	.po-upload-box.is-uploading { opacity: .6; cursor: not-allowed; pointer-events: none; }
	#pay_later_po_file { display: none; }
	@media (max-width: 768px) { .payment-extra-fields-grid { grid-template-columns: 1fr; } #payment-required-date-wrap, #pay-later-po-number-wrap, #pay-later-po-upload { grid-column: auto; grid-row: auto; } }
	</style>

	<script>
	jQuery(function($){

		/* ── Pay Later visibility toggle ── */
		function isPayLaterSelected(){
			return String($('input[name="payment_method"]:checked').val()||'').indexOf('pay_later') !== -1;
		}
		function togglePayLaterFields(){
			var on = isPayLaterSelected();
			$('#pay-later-po-upload').toggle(on);
		}
		function togglePoRequired(){
			var on   = isPayLaterSelected();
			var field = $('#billing_po_number');
			var row   = $('#pay-later-po-number-wrap #billing_po_number_field');
			var label = row.find('label');
			$('#pay-later-po-number-wrap').toggle(on);
			if(!row.length||!field.length) return;
			if(on){
				row.addClass('validate-required');
				row.find('.optional').hide();
				if(!label.find('.required').length) label.append(' <span class="required" aria-hidden="true">*</span>');
				field.prop('required',true).attr('aria-required','true');
			} else {
				row.removeClass('validate-required');
				row.find('.optional').show();
				label.find('.required').remove();
				field.prop('required',false).attr('aria-required','false');
			}
		}
		$(document.body).on('change','input[name="payment_method"]',function(){ togglePayLaterFields(); togglePoRequired(); });
		$(document.body).on('updated_checkout',function(){ togglePayLaterFields(); togglePoRequired(); });
		togglePayLaterFields(); togglePoRequired();

		/* ── PO file upload ── */
		$(document.body).on('change','#pay_later_po_file',function(){
			var file = this.files[0];
			if(!file) return;
			if(file.size > 20*1024*1024){ alert('File is too large. Maximum size allowed is 20MB.'); this.value=''; $('.po-file-name').text('No file chosen'); return; }
			$('.po-file-name').text('Uploading: '+file.name);
			var $btn = $('button#place_order'), origText = $btn.text();
			$btn.prop('disabled',true).html('<span class="po-spinner"></span> Uploading PO...');
			$('.po-upload-box').addClass('is-uploading');
			var fd = new FormData();
			fd.append('action','upload_po_file');
			fd.append('po_file',file);
			$.ajax({ url:wc_checkout_params.ajax_url, type:'POST', data:fd, processData:false, contentType:false,
				success:function(r){ if(r.success){ $('#pay_later_po_file_url').val(r.data.url); $('.po-file-name').text('Successfully uploaded: '+file.name); } else { alert('Upload failed: '+(r.data?r.data.message:'Unknown error')); $('.po-file-name').text('Upload failed'); $('#pay_later_po_file').val(''); } },
				error:function(){ alert('A server error occurred during upload.'); $('.po-file-name').text('Server error during upload'); $('#pay_later_po_file').val(''); },
				complete:function(){ $btn.prop('disabled',false).html(origText); $('.po-upload-box').removeClass('is-uploading'); }
			});
		});

		/* ── Billing field trigger guard ── */
		function disableBillingCheckoutTriggers(){
			var $rows = $('div[id^="billing_"][id$="_field"]');
			if(!$rows.length) return;
			$rows.removeClass('update_totals_on_change address-field');
			$rows.find('input, select, textarea').removeClass('update_totals_on_change');
		}
		disableBillingCheckoutTriggers();
		$(document.body).on('updated_checkout', disableBillingCheckoutTriggers);

	});
	</script>
	<?php
}


// ============================================================
// CHECKOUT — Fulfilment method (delivery / pickup)
// ============================================================

function fhs_get_checkout_fulfilment_method( $posted_data = null ) {
	if ( is_array( $posted_data ) && isset( $posted_data['fhs_fulfilment_method'] ) ) {
		$mode = sanitize_key( (string) $posted_data['fhs_fulfilment_method'] );
		if ( in_array( $mode, array( 'delivery', 'pickup' ), true ) ) return $mode;
	}
	if ( isset( $_POST['fhs_fulfilment_method'] ) ) {
		$mode = sanitize_key( (string) wp_unslash( $_POST['fhs_fulfilment_method'] ) );
		if ( in_array( $mode, array( 'delivery', 'pickup' ), true ) ) return $mode;
	}
	if ( isset( $_POST['post_data'] ) ) {
		$parsed = array();
		parse_str( wp_unslash( $_POST['post_data'] ), $parsed );
		$mode = sanitize_key( (string) ( $parsed['fhs_fulfilment_method'] ?? '' ) );
		if ( in_array( $mode, array( 'delivery', 'pickup' ), true ) ) return $mode;
	}
	if ( WC()->session ) {
		$mode = sanitize_key( (string) WC()->session->get( 'fhs_fulfilment_method', '' ) );
		if ( in_array( $mode, array( 'delivery', 'pickup' ), true ) ) return $mode;
	}
	return 'delivery';
}

function fhs_get_checkout_posted_data() {
	static $posted_data = null;
	if ( null !== $posted_data ) return $posted_data;
	$posted_data = array();
	if ( isset( $_POST['post_data'] ) ) {
		parse_str( wp_unslash( $_POST['post_data'] ), $posted_data );
	} elseif ( ! empty( $_POST ) && is_array( $_POST ) ) {
		$posted_data = wp_unslash( $_POST );
	}
	return is_array( $posted_data ) ? $posted_data : array();
}

function fhs_should_skip_checkout_prefill( $field_key ) {
	if ( ! is_string( $field_key ) || '' === $field_key ) return false;
	if ( is_admin() && ! wp_doing_ajax() ) return false;
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) return false;
	$is_checkout_request = ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( wp_doing_ajax() && ( isset( $_POST['post_data'] ) || isset( $_POST['fhs_fulfilment_method'] ) ) );
	if ( ! $is_checkout_request ) return false;
	// No fields currently in this list; kept as an extension point.
	$fields_to_keep_empty = array();
	if ( ! in_array( $field_key, $fields_to_keep_empty, true ) ) return false;
	$posted_data = fhs_get_checkout_posted_data();
	return ! array_key_exists( $field_key, $posted_data );
}

function fhs_get_checkout_request_value( $field_key, $default = '' ) {
	$posted_data = fhs_get_checkout_posted_data();
	if ( array_key_exists( $field_key, $posted_data ) ) return wc_clean( $posted_data[ $field_key ] );
	if ( fhs_should_skip_checkout_prefill( $field_key ) ) return '';
	return wc_clean( $default );
}

add_filter( 'woocommerce_checkout_get_value', function ( $value, $input ) {
	return fhs_should_skip_checkout_prefill( $input ) ? '' : $value;
}, 999, 2 );

// Reset fulfilment mode to 'delivery' on fresh checkout page loads (not AJAX).
add_action( 'wp', function () {
	if ( wp_doing_ajax() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) return;
	if ( ! WC()->session ) return;
	if ( ! isset( $_POST['fhs_fulfilment_method'] ) && ! isset( $_POST['post_data'] ) ) {
		WC()->session->set( 'fhs_fulfilment_method', 'delivery' );
	}
}, 20 );

// Persist fulfilment mode from AJAX update_order_review calls.
add_action( 'woocommerce_checkout_update_order_review', function ( $posted_data ) {
	if ( ! WC()->session ) return;
	$parsed = array();
	parse_str( (string) $posted_data, $parsed );
	WC()->session->set( 'fhs_fulfilment_method', fhs_get_checkout_fulfilment_method( $parsed ) );
}, 20 );

// Pickup = no shipping address needed.
add_filter( 'woocommerce_cart_needs_shipping_address', function ( $v ) {
	if ( ! WC()->cart || ! WC()->cart->needs_shipping() ) return $v;
	return 'pickup' === fhs_get_checkout_fulfilment_method() ? false : true;
}, 20 );

add_filter( 'woocommerce_cart_needs_shipping', function ( $v ) {
	if ( ! $v ) return $v;
	return 'pickup' === fhs_get_checkout_fulfilment_method() ? false : $v;
}, 20 );

// Route shipping packages to the address typed in the form.
add_filter( 'woocommerce_cart_shipping_packages', function ( $packages ) {
	if ( empty( $packages ) || ! is_array( $packages ) ) return $packages;
	if ( 'pickup' === fhs_get_checkout_fulfilment_method() ) return $packages;
	$dest = array(
		'country'   => fhs_get_checkout_request_value( 'shipping_country',   WC()->customer->get_shipping_country() ),
		'state'     => fhs_get_checkout_request_value( 'shipping_state',     WC()->customer->get_shipping_state() ),
		'postcode'  => fhs_get_checkout_request_value( 'shipping_postcode',  WC()->customer->get_shipping_postcode() ),
		'city'      => fhs_get_checkout_request_value( 'shipping_city',      WC()->customer->get_shipping_city() ),
		'address'   => fhs_get_checkout_request_value( 'shipping_address_1', WC()->customer->get_shipping_address() ),
		'address_2' => fhs_get_checkout_request_value( 'shipping_address_2', WC()->customer->get_shipping_address_2() ),
	);
	$dest['address_1'] = $dest['address'];
	foreach ( $packages as $i => $package ) {
		$packages[ $i ]['destination'] = $dest;
	}
	return $packages;
}, 20 );

// Validate fulfilment + shipping on order submission.
add_action( 'woocommerce_checkout_process', function () {
	$mode = fhs_get_checkout_fulfilment_method();
	if ( ! in_array( $mode, array( 'delivery', 'pickup' ), true ) ) {
		wc_add_notice( __( 'Please choose Delivery or Pick up before placing your order.', 'woocommerce' ), 'error' );
		return;
	}
	if ( 'pickup' === $mode ) return;
	foreach ( WC()->shipping()->get_packages() as $package ) {
		if ( empty( $package['rates'] ) ) {
			wc_add_notice( __( 'Shipping is required. Please select a shipping method.', 'woocommerce' ), 'error' );
			return;
		}
	}
	if ( WC()->cart->needs_shipping() ) {
		foreach ( array( 'shipping_first_name','shipping_last_name','shipping_address_1','shipping_city','shipping_postcode','shipping_country' ) as $field ) {
			if ( empty( trim( $_POST[ $field ] ?? '' ) ) ) {
				wc_add_notice( __( 'Please enter a valid shipping address.', 'woocommerce' ), 'error' );
				break;
			}
		}
	}
} );

// Save fulfilment mode to order meta.
add_action( 'woocommerce_checkout_update_order_meta', function ( $order_id ) {
	$mode = sanitize_key( fhs_get_checkout_fulfilment_method() );
	if ( in_array( $mode, array( 'delivery', 'pickup' ), true ) ) {
		update_post_meta( $order_id, '_fhs_fulfilment_method', $mode );
	}
} );

/**
 * Billing-triggered checkout guard + initial update_order_review firer.
 * Prevents billing field changes from spamming update_checkout while still
 * ensuring Machship gets exactly one valid initial AJAX call.
 */
add_action( 'woocommerce_after_checkout_form', function () {
	?>
	<script>
	jQuery(function($){
		var debugEnabled = /(?:\?|&)fhs_uc_debug=1(?:&|$)/.test(window.location.search);
		var lastBillingEventAt = 0, patchAttempts = 0, patchTimer = null;
		var checkoutBootAt = Date.now(), initialUpdateConsumed = false;
		var userHasInteracted = false, initialUpdateAjaxSeen = false;
		var initialTriggerAttempts = 0, initialTriggerTimer = null;

		function isBillingEl(el){ if(!el) return false; var id=String(el.id||''),name=String(el.name||''); return id.indexOf('billing_')===0||name.indexOf('billing_')===0; }
		function markInteraction(e){ if(e&&e.isTrusted) userHasInteracted=true; }
		document.addEventListener('input',markInteraction,true);
		document.addEventListener('change',markInteraction,true);
		document.addEventListener('keydown',markInteraction,true);

		$(document).ajaxSend(function(e,jqxhr,s){
			if(String((s&&s.url)||'').indexOf('wc-ajax=update_order_review')!==-1){
				initialUpdateAjaxSeen=true;
				if(debugEnabled) console.log('[FHS-UC-DEBUG] detected update_order_review');
			}
		});

		function shouldBypass(){ return Number(window.fhsBypassUpdateGuardsUntil||0)>Date.now(); }
		function shouldBlockInitial(){
			if(shouldBypass()) return false;
			if(Number(window.fhsForceInitialCheckoutUntil||0)>Date.now()) return false;
			if(userHasInteracted||(Date.now()-checkoutBootAt)>=5000) return false;
			if(initialUpdateConsumed) return true;
			initialUpdateConsumed=true; return false;
		}
		function shouldBlockBilling(){
			if(shouldBypass()) return false;
			if(Number(window.fhsForceInitialCheckoutUntil||0)>Date.now()) return false;
			if(Number(window.fhsAllowBillingTriggeredCheckoutUntil||0)>Date.now()) return false;
			var active=document.activeElement;
			return isBillingEl(active)||((Date.now()-lastBillingEventAt)<900);
		}

		function triggerInitialOnce(){
			if(!window.jQuery) return;
			if(window.fhsInitialCheckoutTriggered&&initialTriggerAttempts>0) return;
			window.fhsForceInitialCheckoutUntil=Date.now()+2500;
			window.fhsAllowBillingTriggeredCheckoutUntil=Date.now()+2000;
			window.fhsBypassUpdateGuardsUntil=Date.now()+2500;
			initialTriggerAttempts++;
			if(debugEnabled) console.log('[FHS-UC-DEBUG] forcing initial attempt #'+initialTriggerAttempts);
			if(window.wc_checkout_form&&typeof window.wc_checkout_form.update_checkout==='function'){
				window.wc_checkout_form.update_checkout();
			} else {
				$(document.body).trigger('update_checkout');
			}
			window.fhsInitialCheckoutTriggered=true;
		}

		function startRetries(){
			if(initialTriggerTimer) return;
			initialTriggerTimer=window.setInterval(function(){
				if(initialUpdateAjaxSeen||initialTriggerAttempts>=6){ window.clearInterval(initialTriggerTimer); initialTriggerTimer=null; return; }
				triggerInitialOnce();
			},700);
		}

		if($.fn&&typeof $.fn.trigger==='function'&&!$.fn.trigger.__fhsBillingGuardPatched){
			var origTrigger=$.fn.trigger;
			var guardedTrigger=function(type){
				var t=typeof type==='string'?type:(type&&type.type?type.type:'');
				if(this&&this.length&&this[0]===document.body&&t==='update_checkout'&&shouldBlockBilling()){
					if(debugEnabled) console.log('[FHS-UC-DEBUG] blocked update_checkout from billing field');
					return this;
				}
				return origTrigger.apply(this,arguments);
			};
			guardedTrigger.__fhsBillingGuardPatched=true;
			$.fn.trigger=guardedTrigger;
		}

		$(document).on('input change keyup keydown','input[name^="billing_"],select[name^="billing_"],textarea[name^="billing_"]',function(){ lastBillingEventAt=Date.now(); });

		function patchCheckoutForm(){
			if(!window.wc_checkout_form) return false;
			if(typeof window.wc_checkout_form.trigger_update_checkout==='function'){
				var origTU=window.wc_checkout_form.trigger_update_checkout;
				window.wc_checkout_form.trigger_update_checkout=function(event){
					if(event&&event.target&&isBillingEl(event.target)){ if(debugEnabled) console.log('[FHS-UC-DEBUG] blocked trigger_update_checkout from billing field'); return; }
					return origTU.apply(this,arguments);
				};
			}
			if(typeof window.wc_checkout_form.queue_update_checkout==='function'){
				var origQ=window.wc_checkout_form.queue_update_checkout;
				window.wc_checkout_form.queue_update_checkout=function(){
					if(shouldBlockBilling()){ if(debugEnabled) console.log('[FHS-UC-DEBUG] blocked queue_update_checkout'); return; }
					return origQ.apply(this,arguments);
				};
			}
			if(typeof window.wc_checkout_form.update_checkout==='function'){
				var origU=window.wc_checkout_form.update_checkout;
				window.wc_checkout_form.update_checkout=function(){
					if(shouldBlockBilling()){ if(debugEnabled) console.log('[FHS-UC-DEBUG] blocked update_checkout (billing)'); return; }
					if(shouldBlockInitial()){ if(debugEnabled) console.log('[FHS-UC-DEBUG] blocked extra initial update_checkout'); return; }
					return origU.apply(this,arguments);
				};
			}
			return true;
		}

		function runAfterLoad(){
			var checks=0;
			var t=window.setInterval(function(){
				checks++;
				var ready=$('form.checkout').length>0&&($('#order_review').length>0||$('.woocommerce-checkout-review-order-table').length>0)&&!!(window.wc_checkout_form&&typeof window.wc_checkout_form.update_checkout==='function');
				if(!ready&&checks<60) return;
				window.clearInterval(t);
				triggerInitialOnce();
				window.setTimeout(startRetries,250);
				if(!window.fhsInitialOrderReviewAjaxRequested){
					window.fhsInitialOrderReviewAjaxRequested=true;
					window.setTimeout(function(){
						if(initialUpdateAjaxSeen) return;
						window.fhsForceInitialCheckoutUntil=Date.now()+3000;
						window.fhsAllowBillingTriggeredCheckoutUntil=Date.now()+3000;
						window.fhsBypassUpdateGuardsUntil=Date.now()+3000;
						if(window.wc_checkout_form&&typeof window.wc_checkout_form.update_checkout==='function'){
							window.wc_checkout_form.update_checkout();
						} else if(typeof wc_checkout_params!=='undefined'&&wc_checkout_params.wc_ajax_url){
							$.ajax({type:'POST',url:String(wc_checkout_params.wc_ajax_url).replace('%%endpoint%%','update_order_review'),data:{security:wc_checkout_params.update_order_review_nonce||'',post_data:$('form.checkout').serialize()}});
						} else {
							$(document.body).trigger('update_checkout');
						}
					},800);
				}
			},200);
		}

		if(!patchCheckoutForm()){
			patchTimer=window.setInterval(function(){
				patchAttempts++;
				if(patchCheckoutForm()||patchAttempts>40){
					window.clearInterval(patchTimer);
					document.readyState==='complete'?runAfterLoad():window.addEventListener('load',runAfterLoad,{once:true});
				}
			},100);
		} else {
			document.readyState==='complete'?runAfterLoad():window.addEventListener('load',runAfterLoad,{once:true});
		}

		window.addEventListener('load',function(){
			window.setTimeout(function(){
				if(window.fhsLoadForcedCheckoutTriggered) return;
				window.fhsLoadForcedCheckoutTriggered=true;
				window.fhsForceInitialCheckoutUntil=Date.now()+4000;
				window.fhsAllowBillingTriggeredCheckoutUntil=Date.now()+4000;
				window.fhsBypassUpdateGuardsUntil=Date.now()+4000;
				if(window.wc_checkout_form&&typeof window.wc_checkout_form.update_checkout==='function'){
					window.wc_checkout_form.update_checkout();
				} else {
					$(document.body).trigger('update_checkout');
				}
			},1200);
		},{once:true});
	});
	</script>
	<?php
}, 20 );


// ============================================================
// PAYMENT GATEWAYS
// ============================================================

// Hide "Pay Later" for guests, COD, and prepaid accounts.
add_filter( 'woocommerce_available_payment_gateways', 'restrict_pay_later_gateway', 20 );
function restrict_pay_later_gateway( $available_gateways ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return $available_gateways;
	if ( ! is_user_logged_in() ) return $available_gateways;
	$terms = strtolower( trim( get_user_meta( get_current_user_id(), 'myob_payment_terms', true ) ) );
	if ( in_array( $terms, array( 'cod', 'prepaid' ), true ) ) {
		unset( $available_gateways['pay_later'] );
	}
	return $available_gateways;
}


// ============================================================
// GUEST — hide prices for non-logged-in users
// ============================================================

add_action( 'init', function () {
	if ( is_user_logged_in() || is_admin() ) return;
	add_filter( 'woocommerce_get_price_html',            '__return_empty_string', 99999 );
	add_filter( 'woocommerce_get_variation_price_html',  '__return_empty_string', 99999 );
	add_filter( 'woocommerce_variable_price_html',       '__return_empty_string', 99999 );
	add_filter( 'woocommerce_variable_sale_price_html',  '__return_empty_string', 99999 );
	add_filter( 'woocommerce_grouped_price_html',        '__return_empty_string', 99999 );
	add_filter( 'wc_price',                              '__return_empty_string', 99999 );
	add_filter( 'woocommerce_available_variation', function ( $v ) {
		$v['price_html'] = ''; $v['display_price'] = 0; $v['display_regular_price'] = 0;
		return $v;
	}, 99999 );
	add_filter( 'woocommerce_structured_data_product', function ( $data ) {
		unset( $data['offers'] ); return $data;
	}, 99999 );
} );

// Stock text.
add_filter( 'woocommerce_get_availability_text', function ( $availability, $product ) {
	if ( $product->is_in_stock() && $product->backorders_allowed() ) {
		$stock = $product->get_stock_quantity();
		if ( $stock !== 0 && $stock !== null ) return $stock . ' in stock (Additional can be backordered)';
	}
	return $availability;
}, 10, 2 );


// ============================================================
// AUTH
// ============================================================

add_filter( 'login_redirect', 'custom_login_redirect', 10, 3 );
function custom_login_redirect( $redirect_to, $request, $user ) {
	if ( ! is_a( $user, 'WP_User' ) ) return $redirect_to;
	if ( ! empty( $redirect_to ) && strpos( $redirect_to, home_url() ) !== false ) {
		return esc_url_raw( $redirect_to );
	}
	return home_url( '/my-account/edit-account' );
}

add_shortcode( 'login_register_button', 'custom_login_register_button' );
function custom_login_register_button() {
	$url = wc_get_page_permalink( 'myaccount' );
	if ( is_user_logged_in() ) {
		$user      = wp_get_current_user();
		$first     = ! empty( $user->first_name ) ? $user->first_name : $user->display_name;
		$btn_text  = '<span class="user-icon"><i class="icofont-ui-user"></i></span>' . esc_html( $first );
	} else {
		$btn_text  = '<i class="icofont-ui-user"></i><span class="user-icon">Login / Register</span>';
	}
	return '<a href="' . esc_url( $url ) . '" class="custom-button">' . $btn_text . '</a>';
}

// Save account details — only triggers when the form is posted.
add_action( 'init', function () {
	if ( isset( $_POST['cwea_save_account_details'] ) && is_user_logged_in() ) {
		do_action( 'woocommerce_save_account_details', get_current_user_id() );
	}
}, 20 );


// ============================================================
// WISHLIST
// ============================================================

add_action( 'yith_wcwl_before_wishlist_title', 'add_navbar_in_wishlist' );
function add_navbar_in_wishlist() {
	echo "<div class='ms-custom-wishlist-flex-box'>";
	wc_get_template( 'myaccount/navigation.php' );
	echo "<div class='wishlist-content-handler'>";
	echo "<script>jQuery('.woocommerce-MyAccount-navigation-link--wishlist').addClass('is-active');</script>";
}

add_action( 'yith_wcwl_deleted_wishlist', 'custom_redirect_after_wishlist_delete', 10, 1 );
function custom_redirect_after_wishlist_delete( $wishlist_id ) {
	wp_redirect( home_url( '/wishlist/' ) );
	exit;
}


// ============================================================
// BLOG / SEARCH
// ============================================================

add_action( 'custom_add_before_search_content', 'custom_astra_read_more_button' );
function custom_astra_read_more_button() {
	if ( ! is_search() ) return;
	global $post;
	$search_term = strtolower( trim( get_search_query() ) );
	if ( $search_term === 'download' ) {
		$url = get_field( 'download_file', $post->ID );
		if ( $url ) {
			echo '<a href="' . esc_url( $url ) . '" class="astra-download-button" download target="_blank"><span class="icofont-download"></span> Download</a>';
			return;
		}
	}
	echo '<a href="' . esc_url( get_permalink() ) . '" class="astra-read-more-button">Read More</a>';
}

function astra_blog_post_thumbnail_and_title_order( $remove_elements = array() ) {
	$blog_post_thumb_title_order = astra_get_option( 'blog-post-structure' );
	$remove_post_element         = apply_filters( 'astra_remove_post_elements', $remove_elements );

	if ( isset( $blog_post_thumb_title_order ) && isset( $remove_post_element ) ) {
		foreach ( $remove_post_element as $single ) {
			$key = array_search( $single, $blog_post_thumb_title_order );
			if ( false !== $key ) unset( $blog_post_thumb_title_order[ $key ] );
		}
	}

	if ( is_singular() ) return astra_banner_elements_order();

	if ( is_array( $blog_post_thumb_title_order ) ) {
		$inside_wrapper = false;
		foreach ( $blog_post_thumb_title_order as $el ) {
			switch ( $el ) {
				case 'image':
					do_action( 'astra_blog_archive_featured_image_before' );
					astra_get_blog_post_thumbnail( 'archive' );
					do_action( 'astra_blog_archive_featured_image_after' );
					break;
				default:
					if ( ! $inside_wrapper ) { echo "<div class='ms-custom-content-wrapper'>"; $inside_wrapper = true; }
					switch ( $el ) {
						case 'category':   do_action( 'astra_blog_archive_category_before' );   echo astra_post_categories( 'astra_blog_archive_category', 'blog-category-style', true ); do_action( 'astra_blog_archive_category_after' ); break;
						case 'tag':        do_action( 'astra_blog_archive_tag_before' );        echo astra_post_tags( 'astra_blog_archive_tag', 'blog-tag-style', true ); do_action( 'astra_blog_archive_tag_after' ); break;
						case 'title':      do_action( 'astra_blog_archive_title_before' );      astra_get_blog_post_title(); do_action( 'astra_blog_archive_title_after' ); break;
						case 'title-meta': do_action( 'astra_blog_archive_title_meta_before' ); astra_get_blog_post_title_meta(); do_action( 'astra_blog_archive_title_meta_after' ); break;
						case 'excerpt':    do_action( 'astra_blog_archive_excerpt_before' );    astra_the_excerpt(); do_action( 'astra_blog_archive_excerpt_after' ); break;
						case 'read-more':  do_action( 'astra_blog_archive_read_more_before' );  astra_post_link(); do_action( 'astra_blog_archive_read_more_after' ); break;
					}
					break;
			}
		}
		if ( $inside_wrapper ) { do_action( 'custom_add_before_search_content' ); echo "</div>"; }
	}
}


// ============================================================
// ELEMENTOR
// ============================================================

add_filter( 'elementor/frontend/the_content', function ( $content ) {
	return do_shortcode( $content );
} );


// ============================================================
// GRAVITY FORMS
// ============================================================

add_filter( 'gform_field_validation', 'gf_phone_numbers_only_clean', 10, 4 );
function gf_phone_numbers_only_clean( $result, $value, $form, $field ) {
	if ( $field->type === 'phone' && preg_match( '/[a-zA-Z]/', $value ) ) {
		$result['is_valid'] = false;
		$result['message']  = 'Please enter numbers only.';
	}
	return $result;
}

add_filter( 'gform_address_types', 'custom_au_address_type' );
function custom_au_address_type( $address_types ) {
	$address_types['australia'] = array(
		'label'       => 'Australia',
		'country'     => 'Australia',
		'zip_label'   => 'Postcode',
		'state_label' => 'State',
		'states'      => array(
			'NSW' => 'New South Wales', 'VIC' => 'Victoria', 'QLD' => 'Queensland',
			'WA'  => 'Western Australia', 'SA' => 'South Australia', 'TAS' => 'Tasmania',
			'ACT' => 'Australian Capital Territory', 'NT' => 'Northern Territory',
		),
	);
	add_action( 'wp_footer', function () {
		?>
		<script>
		document.addEventListener('DOMContentLoaded',function(){
			document.querySelectorAll('.customer-mainauth-container .ginput_container_address').forEach(function(f){
				if(!f.querySelector('.address_country')){
					f.insertAdjacentHTML('beforeend','<span class="ginput_right address_country ginput_address_country gform-grid-col"><select><option value="AU" selected>Australia</option></select><input type="hidden" name="country_static" value="AU"><label class="gform-field-label gform-field-label--type-sub">Country</label></span>');
				}
			});
		});
		</script>
		<?php
	} );
	return $address_types;
}

add_filter( 'gform_confirmation', 'custom_confirmation', 10, 4 );
function custom_confirmation( $confirmation, $form, $entry, $ajax ) {
	if ( $form['id'] == '6' ) {
		$confirmation = array( 'redirect' => home_url( 'butt-welders' ) );
	}
	return $confirmation;
}

add_filter( 'gform_confirmation_6', function ( $confirmation, $form, $entry, $ajax ) {
	if ( $ajax ) return '<div class="gform_confirmation_message">Thank you! We will contact you shortly.</div>';
	return $confirmation;
}, 10, 4 );

// Author display name → first name.
add_filter( 'the_author', 'display_author_first_name' );
function display_author_first_name( $display_name ) {
	$user_id    = get_the_author_meta( 'ID' );
	$first_name = get_the_author_meta( 'first_name', $user_id );
	return $first_name ?: $display_name;
}
