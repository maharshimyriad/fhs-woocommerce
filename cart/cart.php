<?php
/**
 * Cart Page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.9.0
 */

defined( 'ABSPATH' ) || exit;

// Disable tax calculation for shipping
add_filter( 'woocommerce_shipping_rate_taxes', '__return_empty_array' );
WC()->cart->calculate_totals();

do_action( 'woocommerce_before_cart' ); ?>
<div class="shoping-cart">
<form class="woocommerce-cart-form shopping-cart-detail" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>
	<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
		<thead>
			<tr class=" tr-title">
				<th class="product-name"><?php esc_html_e( 'PRODUCT', 'woocommerce' ); ?></th>
				<th class="product-price"><?php esc_html_e( 'PRICE', 'woocommerce' ); ?></th>
				<th class="product-quantity"><?php esc_html_e( 'QUANTITY', 'woocommerce' ); ?></th>
				<th class="product-subtotal"><?php esc_html_e( 'TOTAL', 'woocommerce' ); ?></th>
				<th colspan="2" class="product-remove"><?php esc_html_e( 'ACTION', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php do_action( 'woocommerce_before_cart_contents' ); ?>

			<?php
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
				$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );

				if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
					?>
					<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>" data-prod-name="<?php echo $_product->get_name() ?>">

						<td class="product-item" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
							<div class="product-card">
								<div class="product-thumbnail">
									<?php
									$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
									if ( ! $product_permalink ) {
										echo $thumbnail; // PHPCS: XSS ok.
									} else {
										echo '<a href="' . esc_url( $product_permalink ) . '">' . $thumbnail . '</a>'; // PHPCS: XSS ok.
									}
									?>
								</div>
<!-- 								<div class="product-details">
									<?php echo $product_name; ?>
								</div> -->
<div class="product-details">
    <?php
    // Product name
    if ( ! $product_permalink ) {
        echo wp_kses_post( $product_name );
    } else {
        echo wp_kses_post(
            apply_filters(
                'woocommerce_cart_item_name',
                sprintf(
                    '<a href="%s">%s</a>',
                    esc_url( $product_permalink ),
                    $_product->get_name()
                ),
                $cart_item,
                $cart_item_key
            )
        );
    }

    // SKU below name
    $sku = $_product->get_sku();
    if ( $sku ) {
        echo '<p class="product-sku">SKU: ' . esc_html( $sku ) . '</p>';
    }

    do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

    // Meta data
    echo wc_get_formatted_cart_item_data( $cart_item );

    // Backorder notification
    if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {

        $stock_quantity = (int) $_product->get_stock_quantity();
        $message = $stock_quantity . ' available. Balance on Backorder';

        echo wp_kses_post(
            apply_filters(
                'woocommerce_cart_item_backorder_notification',
                '<p class="backorder_notification">' . esc_html( $message ) . '</p>',
                $product_id
            )
        );
    }
    ?>
</div>
							</div>
						</td>

						<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
							<?php
								echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
							?>
							<div class="gst-message">(Ex GST)</div>
						</td>

						<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
							<div class="quantity">
								<button type="button" class="minus">-</button>
								<?php
									$product_quantity = woocommerce_quantity_input(
										array(
											'input_name'   => "cart[{$cart_item_key}][qty]",
											'input_value'  => $cart_item['quantity'],
											'max_value'    => $_product->get_max_purchase_quantity(),
											'min_value'    => '0',
											'product_name' => $_product->get_name(),
										),
										$_product,
										false
									);
									echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item );
								?>
								<button type="button" class="plus">+</button>
							</div>
						</td>

						<td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
							<?php
								echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // PHPCS: XSS ok.
							?>
							<div class="gst-message">(Ex GST)</div>
						</td>
						<td class="product-remove">
							<div class="cart-action-container">
								<div class="update-action">
									 <button type="button" class="cart-edit-button update-cart-item" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>">
                                        <i class="icofont-refresh"></i>
                                    </button>
								</div>
								 <div class="remove-action">
                                    <?php
                                    echo apply_filters(
                                        'woocommerce_cart_item_remove_link',
                                        sprintf(
                                            '<a href="%s" aria-label="%s" data-product_id="%s" style="color:#877e7e;" data-product_sku="%s"><span class="icofont icofont-bin"></span></a>',
                                            esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                                            /* translators: %s is the product name */
                                            esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
                                            esc_attr( $product_id ),
                                            esc_attr( $_product->get_sku() )
                                        ),
                                        $cart_item_key
                                    );
                                    ?>
    						    </div>
							</div>
						</td>
					</tr>
					<?php
				}
			}
			?>

			<?php do_action( 'woocommerce_cart_contents' ); ?>
		</tbody>
	</table>
	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

<div class="cart-collaterals price-details-cart">
	<?php
		do_action( 'woocommerce_cart_collaterals_custom' );
	?>
</div>
</div>
<?php do_action( 'woocommerce_after_cart' ); ?>
