<?php
/**
 * Variation card grid template
 *
 * Renders each product variation as an individual card — image, name, SKU,
 * price, quantity stepper, Add to Cart and Add to Quote buttons.
 *
 * Loaded by variable.php when any assigned category (or parent category) has
 * the ACF field `category_variation_template` set to true.
 *
 * Layout note
 * ───────────
 * This template is called from inside woocommerce_template_single_add_to_cart
 * (priority 30 on woocommerce_single_product_summary), which sits inside the
 * .single-product-content-container div.
 *
 * The card grid must render AFTER that container closes, so we:
 *   1. Output only the hidden native variations_form here (keeps WC JS happy).
 *   2. Register a one-time woocommerce_after_single_product_summary hook that
 *      outputs the visible card grid — which fires after the container div.
 *
 * Available variables (passed from variable.php via wc_get_template):
 *   $available_variations  — array of variation data arrays
 *   $attributes            — array of attribute name => options
 *   $selected_attributes   — array of pre-selected attribute values
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

// ── Step 1: capture variables needed inside the hook closure ──────────────────
$_fhs_product             = $product;
$_fhs_available_variations = $available_variations;
$_fhs_variations_attr      = $variations_attr;

// ── Step 2: register the card grid to print AFTER the product summary container
// Use priority 5 so it sits before the tabs / upsells (priority 10+).
// The remove_action inside ensures it only fires once per page load.
if ( ! has_action( 'woocommerce_after_single_product_summary', 'fhs_render_variation_card_grid' ) ) {

	/**
	 * Prints the variation card grid after .single-product-content-container.
	 *
	 * Hooked onto woocommerce_after_single_product_summary (priority 5).
	 * Self-removes after first execution.
	 */
	function fhs_render_variation_card_grid() {
		// Pull the stashed globals back in.
		global $_fhs_product, $_fhs_available_variations, $_fhs_variations_attr;

		// Self-remove so this never fires twice on the same page.
		remove_action( 'woocommerce_after_single_product_summary', 'fhs_render_variation_card_grid', 5 );

		$product             = $_fhs_product;
		$available_variations = $_fhs_available_variations;
		$variations_attr      = $_fhs_variations_attr;

		if ( empty( $available_variations ) && false !== $available_variations ) : ?>
			<p class="stock out-of-stock product-main-container">
				<?php esc_html_e( 'This product is currently out of stock and unavailable.', 'woocommerce' ); ?>
			</p>
		<?php return;
		endif;
		?>

		<!-- ═══════════════════════════════════════════════════════════════
		     Variation card grid — rendered after .product-main-container
		     ═══════════════════════════════════════════════════════════════ -->
		<div class="fhs-variation-cards product-main-container"
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

				// ── Image (falls back to parent product image) ────────────
				$image_id   = $variation_product->get_image_id() ?: $product->get_image_id();
				$image_html = $image_id
					? wp_get_attachment_image( $image_id, 'woocommerce_thumbnail', false, array( 'class' => 'fhs-variation-card__image' ) )
					: wc_placeholder_img( 'woocommerce_thumbnail', array( 'class' => 'fhs-variation-card__image' ) );

				// ── Stock / purchasable state ─────────────────────────────
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

								<!-- Add to Quote -->
								<div class="woocommerce-other-btn fhs-variation-card__other-btns">
									<?php echo do_shortcode( '[stars_add_to_quote_button text="Add to Quote"]' ); ?>
								</div>

								<!-- Hidden fields: tell WooCommerce exactly which variation to add -->
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

	add_action( 'woocommerce_after_single_product_summary', 'fhs_render_variation_card_grid', 5 );
}

// ── Step 3: output only the hidden native form HERE (inside the summary) ──────
// This keeps wc-add-to-cart-variation.js initialised without rendering any
// visible variation UI inside the .single-product-content-container div.
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
