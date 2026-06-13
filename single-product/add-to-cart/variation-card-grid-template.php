<?php
/**
 * Variation card grid template
 *
 * Loaded by variable.php when any assigned category (or parent category) has
 * the ACF field `category_variation_template` set to true.
 *
 * What renders where
 * ──────────────────
 * This file is included from inside woocommerce_template_single_add_to_cart
 * (priority 30 on woocommerce_single_product_summary), so anything echoed
 * directly here lands inside .summary.entry-summary.
 *
 *   IN SUMMARY (echoed directly):
 *     - Features accordion
 *     - Delivery Timeframes block + enquiry modal
 *     - Hidden native variations_form (keeps WC JS working)
 *
 *   AFTER .single-product-layout-wrap (via fhs_inside_product_main_container hook):
 *     - Variation card grid (image, name, SKU, price, qty, ATC, wishlist, quote)
 *
 * @package WooCommerce\Templates
 * @version 9.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' )
	? wc_esc_json( $variations_json )
	: _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

// Enqueue card grid styles once per page.
if ( ! wp_style_is( 'fhs-variation-cards', 'enqueued' ) ) {
	wp_enqueue_style(
		'fhs-variation-cards',
		get_stylesheet_directory_uri() . '/woocommerce/single-product/add-to-cart/variation-cards.css',
		array( 'woocommerce-general' ),
		'1.0.0'
	);
}

do_action( 'woocommerce_before_add_to_cart_form' );

// ── Compute shared data ───────────────────────────────────────────────────────

$store_address_1   = get_option( 'woocommerce_store_address' );
$store_address_2   = get_option( 'woocommerce_store_address_2' );
$store_city        = get_option( 'woocommerce_store_city' );
$store_postcode    = get_option( 'woocommerce_store_postcode' );
$store_country     = get_option( 'woocommerce_default_country' );
$country_parts     = explode( ':', $store_country );
$country_code      = $country_parts[0];
$countries         = WC()->countries->countries;
$country_name      = $countries[ $country_code ] ?? '';
$formatted_address = implode( ', ', array_filter( array(
	$store_address_1,
	$store_address_2,
	$store_city,
	$store_postcode,
	$country_name,
) ) );

$current_user_email    = '';
$shipping_address      = '';
$est_time_string       = '-';

if ( is_user_logged_in() ) {
	$user_data          = get_userdata( get_current_user_id() );
	$current_user_email = $user_data ? $user_data->user_email : '';
	$customer           = WC()->customer;
	if ( $customer ) {
		$shipping_address = implode( ', ', array_filter( array(
			$customer->get_shipping_address(),
			$customer->get_shipping_address_2(),
			$customer->get_shipping_city(),
			$customer->get_shipping_state(),
			$customer->get_shipping_postcode(),
			$customer->get_shipping_country(),
		) ) );
	}
}

$product_features      = get_field( 'product_features', $product->get_id() ) ?: '-';
$show_delivery_enquiry = ( $product->get_backorders() === 'notify' );

// ── SUMMARY: Features accordion ───────────────────────────────────────────────
?>

<details class="product-specs-details">
	<summary class="dropdown-header product-specs-summary"
		style="width:100%;display:flex;justify-content:space-between;">
		<span>
			<i class="icofont icofont-file-alt" style="margin-right:7px;"></i>
			Features
		</span>
		<span class="dropdown-icon"><i class="icofont-rounded-down"></i></span>
	</summary>
	<div class="summary-container product-specs-content">
		<div class="product-specs-row-detail">
			<?php echo wp_kses_post( $product_features ); ?>
		</div>
	</div>
</details>

<!-- ── SUMMARY: Delivery Timeframes ─────────────────────────────────────────── -->
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
						<span><?php echo esc_html( $formatted_address ); ?></span>
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
							<span><?php echo esc_html( $shipping_address ); ?></span>
						</details>
					</div>
				</div>
			</div>
			<div class="wpf_delivery-time">
				Estimated delivery time<br/>
				<?php echo esc_html( $est_time_string ); ?>
			</div>
		</div>

		<div class="wpf-delivery-note">
			<div class="wpf-delivery-note-text">
				Item(s) weighing more than 22kgs will require appropriate lifting facilities to unload your delivery.
			</div>
		</div>

	</div><!-- /.wpf_available-address-content -->

	<?php if ( $show_delivery_enquiry ) : ?>
		<div class="wpf_delivery-enquiry-wrap">
			<button
				type="button"
				class="wpf_delivery-enquiry-btn"
				data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'delivery_enquiry_nonce' ) ); ?>"
			>
				<i class="icofont-clock-time"></i>
				Enquiry for Backorder Delivery Time Estimates
			</button>
		</div>
	<?php endif; ?>

</div><!-- /.wpf_available-address-container -->

<!-- ── SUMMARY: Delivery enquiry modal ──────────────────────────────────────── -->
<div id="wpf-delivery-enquiry-modal" class="wpf-enquiry-overlay">
	<div class="wpf-enquiry-modal-box">

		<button type="button" id="wpf-enquiry-modal-close" class="wpf-enquiry-modal-close">&times;</button>

		<h3 class="wpf-enquiry-modal-title">
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
			<input type="email" id="wpf-enquiry-email" class="wpf-enquiry-input"
				placeholder="Enter your email"
				value="<?php echo esc_attr( $current_user_email ); ?>" />

			<label class="wpf-enquiry-label">
				Your postcode <span class="wpf-enquiry-required">*</span>
			</label>
			<input type="text" id="wpf-enquiry-postcode" class="wpf-enquiry-input"
				placeholder="Enter postcode" maxlength="20" />

			<input type="hidden" id="wpf-enquiry-sku" value="">
			<div id="wpf-enquiry-error" class="wpf-enquiry-error"></div>

			<button type="button" id="wpf-enquiry-submit-btn" class="wpf-enquiry-submit-btn">
				Submit Enquiry
			</button>
		</div>

	</div>
</div><!-- /#wpf-delivery-enquiry-modal -->

<?php
// ── Stash data for the hook callback ─────────────────────────────────────────
$GLOBALS['_fhs_grid_product']    = $product;
$GLOBALS['_fhs_grid_variations'] = $available_variations;
$GLOBALS['_fhs_grid_attr']       = $variations_attr;

// ── Register card grid on fhs_inside_product_main_container (fires once) ──────
if ( ! has_action( 'fhs_inside_product_main_container', 'fhs_render_variation_card_grid' ) ) {

	function fhs_render_variation_card_grid() {

		remove_action( 'fhs_inside_product_main_container', 'fhs_render_variation_card_grid' );

		$product              = $GLOBALS['_fhs_grid_product'];
		$available_variations = $GLOBALS['_fhs_grid_variations'];

		if ( empty( $available_variations ) && false !== $available_variations ) {
			echo '<p class="stock out-of-stock">'
				. esc_html__( 'This product is currently out of stock and unavailable.', 'woocommerce' )
				. '</p>';
			return;
		}

		?>
		<!-- ═══════════════════════════════════════════════════════════════════
		     Variation card grid
		     ═══════════════════════════════════════════════════════════════════ -->
		<div class="fhs-variation-cards"
			data-product_id="<?php echo absint( $product->get_id() ); ?>">

			<?php foreach ( $available_variations as $variation_data ) :

				$variation_id      = absint( $variation_data['variation_id'] );
				$variation_product = wc_get_product( $variation_id );

				if ( ! $variation_product ) {
					continue;
				}

				// ── Variation display name ────────────────────────────────
				$attr_labels = array();
				foreach ( $variation_data['attributes'] as $attr_key => $attr_value ) {
					if ( ! $attr_value ) {
						continue;
					}
					$taxonomy      = str_replace( 'attribute_', '', $attr_key );
					$term          = get_term_by( 'slug', $attr_value, $taxonomy );
					$attr_labels[] = $term ? $term->name : ucfirst( str_replace( '-', ' ', $attr_value ) );
				}
				$variation_name = $attr_labels
					? implode( ' / ', $attr_labels )
					: $variation_product->get_name();

				// ── Image (falls back to parent) ──────────────────────────
				$image_id   = $variation_product->get_image_id() ?: $product->get_image_id();
				$image_html = $image_id
					? wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'class' => 'fhs-variation-card__image' ) )
					: wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'fhs-variation-card__image' ) );

				// ── Stock / purchasable ───────────────────────────────────
				$sku            = $variation_product->get_sku();
				$is_in_stock    = $variation_product->is_in_stock();
				$is_purchasable = $variation_product->is_purchasable();
				$can_add        = $is_in_stock && $is_purchasable;
				$price_html     = $variation_product->get_price_html();
				$max_qty        = $variation_product->get_max_purchase_quantity();

			?>

			<div class="fhs-variation-card<?php echo ! $can_add ? ' fhs-variation-card--out-of-stock' : ''; ?>"
				data-variation_id="<?php echo $variation_id; ?>">

				<!-- Card image -->
				<div class="fhs-variation-card__img-wrap">
					<?php echo $image_html; ?>
				</div>

				<!-- Card body -->
				<div class="fhs-variation-card__body">

					<h4 class="fhs-variation-card__name">
						<?php echo esc_html( $variation_name ); ?>
					</h4>

					<?php if ( $sku ) : ?>
						<p class="fhs-variation-card__sku">
							<?php esc_html_e( 'SKU', 'woocommerce' ); ?>: <span><?php echo esc_html( $sku ); ?></span>
						</p>
					<?php endif; ?>

					<?php if ( is_user_logged_in() ) : ?>

						<!-- Price -->
						<div class="fhs-variation-card__price-wrap">
							<?php echo wp_kses_post( $price_html ); ?>
							<span class="gst-text">(Ex GST)</span>
						</div>

						<?php if ( $can_add ) : ?>

							<!-- Per-variation add-to-cart form -->
							<form class="fhs-variation-card__form cart"
								action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
								method="post"
								enctype="multipart/form-data">

								<!-- Quantity stepper -->
								<div class="quantity-container">
									<button type="button" class="minus"
										aria-label="<?php esc_attr_e( 'Decrease quantity', 'woocommerce' ); ?>">-</button>
									<div class="quantity">
										<?php
										woocommerce_quantity_input(
											array(
												'min_value'   => 1,
												'max_value'   => $max_qty > 0 ? $max_qty : '',
												'input_value' => 1,
											),
											$variation_product,
											true
										);
										?>
									</div>
									<button type="button" class="plus"
										aria-label="<?php esc_attr_e( 'Increase quantity', 'woocommerce' ); ?>">+</button>
								</div>

								<!-- Add to cart -->
								<button type="submit" class="single_add_to_cart_button button alt fhs-variation-card__atc">
									<i class="icofont-shopping-cart"></i>
									<?php esc_html_e( 'Add to cart', 'woocommerce' ); ?>
								</button>

								<!-- Hidden fields -->
								<input type="hidden" name="add-to-cart"  value="<?php echo absint( $variation_id ); ?>" />
								<input type="hidden" name="product_id"   value="<?php echo absint( $product->get_id() ); ?>" />
								<input type="hidden" name="variation_id" value="<?php echo absint( $variation_id ); ?>" />
								<?php foreach ( $variation_data['attributes'] as $attr_key => $attr_value ) : ?>
									<input type="hidden"
										name="<?php echo esc_attr( $attr_key ); ?>"
										value="<?php echo esc_attr( $attr_value ); ?>" />
								<?php endforeach; ?>

							</form>

						<?php else : ?>
							<p class="stock out-of-stock">
								<?php esc_html_e( 'Out of stock', 'woocommerce' ); ?>
							</p>
						<?php endif; ?>

						<!-- Add to Quote + YITH wishlist -->
						<div class="woocommerce-other-btn">
							<?php echo do_shortcode( '[yith_wcwl_add_to_wishlist]' ); ?>
							<?php echo do_shortcode( '[stars_add_to_quote_button text="Add to Quote"]' ); ?>
						</div>

						<!-- Custom wishlist dropdown -->
						<div class="ms-wishlist-container">
							<div id="ms-wishlist-text">
								<i class="icofont-heart"></i> Add to My Wishlist
								<i class="icofont-caret-down ms-wishlist-arrow"></i>
							</div>
							<div class="ms-wishlist-action">
								<button id="wishlist-dropdown-submit" type="button">
									<i class="icofont-plus"></i>
								</button>
							</div>
						</div>

					<?php else : ?>

						<!-- Guest: prompt to log in -->
						<div class="login-prompt">
							<span><?php esc_html_e( 'Login or register to view prices.', 'woocommerce' ); ?></span>
							<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
								<i class="icofont icofont-ui-user"></i>
								<?php esc_html_e( 'Login / Register to see pricing', 'woocommerce' ); ?>
							</a>
						</div>

					<?php endif; ?>

				</div><!-- /.fhs-variation-card__body -->

			</div><!-- /.fhs-variation-card -->

			<?php endforeach; ?>

		</div><!-- /.fhs-variation-cards -->
		<?php
	}

	add_action( 'fhs_inside_product_main_container', 'fhs_render_variation_card_grid' );
}

// ── Hidden native form (inside .summary so WC JS initialises) ─────────────────
?>
<form class="variations_form cart"
	style="display:none!important;visibility:hidden!important;"
	action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
	method="post"
	enctype="multipart/form-data"
	data-product_id="<?php echo absint( $product->get_id() ); ?>"
	data-product_variations="<?php echo $variations_attr; ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>
	<div class="single_variation_wrap">
		<?php do_action( 'woocommerce_before_single_variation' ); ?>
		<?php do_action( 'woocommerce_single_variation' ); ?>
		<?php do_action( 'woocommerce_after_single_variation' ); ?>
	</div>
	<?php do_action( 'woocommerce_after_variations_form' ); ?>
	<input type="hidden" name="add-to-cart"  value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id"   value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</form>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
