<?php
/**
 * Review order table
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;

// Coupon actions — these come in via the review-order AJAX fragment.
if ( isset( $_POST['apply_coupon'] ) && ! empty( $_POST['coupon_code'] ) ) {
	$coupon_code = sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) );
	if ( ! WC()->cart->has_discount( $coupon_code ) ) {
		WC()->cart->apply_coupon( $coupon_code );
	}
}

if ( isset( $_POST['remove_coupon'] ) ) {
	foreach ( WC()->cart->get_applied_coupons() as $code ) {
		WC()->cart->remove_coupon( $code );
	}
}

// Disable tax on shipping (consistent with cart page), then recalculate.
add_filter( 'woocommerce_shipping_rate_taxes', '__return_empty_array' );
WC()->cart->calculate_totals();

wc_print_notices();
?>

<div class="woocommerce-checkout-review-order-table">

	<!-- ── Cart items ─────────────────────────────────────────── -->
	<div class="checkout-cart-items">

		<?php do_action( 'woocommerce_review_order_before_cart_contents' ); ?>

		<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) continue;
			if ( ! apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) continue;
		?>
			<div class="checkout-cart-item">

				<div class="checkout-cart-item__image">
					<?php echo wp_kses_post( $_product->get_image() ); ?>
					<?php echo apply_filters(
						'woocommerce_checkout_cart_item_quantity',
						'<span class="product-quantity">&times; ' . esc_html( $cart_item['quantity'] ) . '</span>',
						$cart_item,
						$cart_item_key
					); ?>
				</div>

				<div class="checkout-cart-item__details">
					<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
					<?php $sku = $_product->get_sku(); if ( $sku ) : ?>
						<p class="product-sku">SKU: <?php echo esc_html( $sku ); ?></p>
					<?php endif; ?>
				</div>

				<div class="checkout-cart-item__subtotal">
					<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
					<span class="gst-message">(Ex GST)</span>
				</div>

			</div>
		<?php endforeach; ?>

		<?php do_action( 'woocommerce_review_order_after_cart_contents' ); ?>

	</div><!-- .checkout-cart-items -->

	<!-- ── Totals ─────────────────────────────────────────────── -->
	<div class="checkout-totals">

		<!-- Subtotal -->
		<div class="checkout-totals__row checkout-totals__subtotal">
			<?php $item_count = WC()->cart->get_cart_contents_count(); ?>
			<span class="checkout-totals__label">
				<?php printf(
					esc_html( _n( 'Subtotal (%d item):', 'Subtotal (%d items):', $item_count, 'woocommerce' ) ),
					$item_count
				); ?>
			</span>
			<span class="checkout-totals__value">
				<?php echo wc_price( WC()->cart->get_subtotal() ); ?>
				<span class="gst-message">(Ex GST)</span>
			</span>
		</div>

		<!-- Coupon -->
		<?php if ( WC()->cart->get_applied_coupons() ) : ?>
			<div class="checkout-totals__row checkout-totals__coupon">
				<form method="post" class="checkout-coupon-remove">
					<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
						<span class="checkout-totals__label">
							<?php esc_html_e( 'Coupon:', 'woocommerce' ); ?>
							<strong><?php echo esc_html( $coupon->get_code() ); ?></strong>
						</span>
						<button type="submit" name="remove_coupon" class="checkout-coupon-remove__btn" aria-label="<?php esc_attr_e( 'Remove coupon', 'woocommerce' ); ?>">
							<span class="icofont icofont-bin"></span>
						</button>
					<?php endforeach; ?>
				</form>
			</div>
		<?php else : ?>
			<div class="checkout-totals__row checkout-totals__coupon-form">
				<form method="post" class="checkout-coupon-apply">
					<input type="text" name="coupon_code" placeholder="<?php esc_attr_e( 'Discount code', 'woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Discount code', 'woocommerce' ); ?>">
					<button type="submit" name="apply_coupon"><?php esc_html_e( 'Apply', 'woocommerce' ); ?></button>
				</form>
			</div>
		<?php endif; ?>

		<!-- Coupon discount amount -->
		<?php if ( WC()->cart->get_discount_total() > 0 ) : ?>
			<div class="checkout-totals__row checkout-totals__discount">
				<span class="checkout-totals__label"><?php esc_html_e( 'Coupon Discount:', 'woocommerce' ); ?></span>
				<span class="checkout-totals__value checkout-totals__value--discount">
					-<?php echo wc_price( WC()->cart->get_discount_total() ); ?>
				</span>
			</div>
		<?php endif; ?>

		<!-- Tax -->
		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
					<div class="checkout-totals__row checkout-totals__tax tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<span class="checkout-totals__label"><?php echo esc_html( $tax->label ); ?></span>
						<span class="checkout-totals__value"><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="checkout-totals__row checkout-totals__tax">
					<span class="checkout-totals__label"><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></span>
					<span class="checkout-totals__value"><?php wc_cart_totals_taxes_total_html(); ?></span>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<!-- Shipping -->
		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) :
			$packages       = WC()->shipping()->get_packages();
			$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
			foreach ( $packages as $i => $package ) :
				if ( ! isset( $chosen_methods[ $i ], $package['rates'][ $chosen_methods[ $i ] ] ) ) continue;
				$chosen_method = $package['rates'][ $chosen_methods[ $i ] ];
		?>
				<div class="checkout-totals__row checkout-totals__shipping">
					<span class="checkout-totals__label"><?php esc_html_e( 'Shipping', 'woocommerce' ); ?></span>
					<div class="checkout-totals__value">
						<?php echo wc_price( $chosen_method->cost ); ?>
						<span class="gst-message">(Inc GST)</span>
					</div>
				</div>
				<div class="checkout-totals__shipping-label machship-message-container">
					<span class="machship-message-text"><?php echo esc_html( $chosen_method->label ); ?></span>
				</div>
		<?php endforeach; endif; ?>

		<!-- Fees -->
		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<div class="checkout-totals__row checkout-totals__fee">
				<span class="checkout-totals__label"><?php echo esc_html( $fee->name ); ?></span>
				<span class="checkout-totals__value"><?php wc_cart_totals_fee_html( $fee ); ?></span>
			</div>
		<?php endforeach; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<!-- Grand total -->
		<div class="checkout-totals__row checkout-totals__grand-total">
			<span class="checkout-totals__label"><?php esc_html_e( 'Grand Total:', 'woocommerce' ); ?></span>
			<div class="checkout-totals__value">
				<?php echo wc_price( WC()->cart->get_total( 'edit' ) ); ?>
				<span class="gst-message">(Inc GST)</span>
			</div>
		</div>

		<!-- Trust badge -->
		<div class="checkout-secure-badge">
			<i class="icofont-safety"></i>
			<p><?php esc_html_e( 'Safe and Secure Payments. Trusted Australian Industry Supplier.', 'woocommerce' ); ?></p>
		</div>

		<!-- Place order -->
		<div class="place-order">
			<?php
			$total          = WC()->cart->get_total( 'edit' );
			$formatted_total = strip_tags( wc_price( $total ) );
			$button_class   = 'button alt' . ( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ) : '' );
			$button_text    = esc_html__( 'Pay Now', 'woocommerce' ) . ' ' . $formatted_total;

			echo apply_filters(
				'woocommerce_order_button_html',
				'<button type="submit"'
				. ' class="' . esc_attr( $button_class ) . '"'
				. ' name="woocommerce_checkout_place_order"'
				. ' id="place_order"'
				. ' value="' . esc_attr( $button_text ) . '"'
				. ' data-value="' . esc_attr( $button_text ) . '">'
				. esc_html( $button_text )
				. '</button>'
			);
			?>
		</div>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

	</div><!-- .checkout-totals -->

</div><!-- .woocommerce-checkout-review-order-table -->
