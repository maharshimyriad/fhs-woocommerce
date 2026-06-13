	<?php
	/**
	 * Order Customer Details
	 *
	 * This template can be overridden by copying it to yourtheme/woocommerce/order/order-details-customer.php.
	 *
	 * HOWEVER, on occasion WooCommerce will need to update template files and you
	 * (the theme developer) will need to copy the new files to your theme to
	 * maintain compatibility. We try to do this as little as possible, but it does
	 * happen. When this occurs the version of the template file will be bumped and
	 * the readme will list any important changes.
	 *
	 * @see     https://woocommerce.com/document/template-structure/
	 * @package WooCommerce\Templates
	 * @version 8.7.0
	 */

	defined( 'ABSPATH' ) || exit;

	$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
	?>
	<section class="woocommerce-customer-details">

		<?php if ( $show_shipping ) : ?>

		<section class="woocommerce-columns woocommerce-columns--2 woocommerce-columns--addresses col2-set addresses">
			<div class="woocommerce-column woocommerce-column--1 woocommerce-column--billing-address col-1">

		<?php endif; ?>
			<div class="order-details-header">
		<span>#<?php echo $order->get_order_number(); ?></span>
		<a href="<?php echo wc_get_account_endpoint_url('orders'); ?>">
		<i class="icofont-long-arrow-left"></i>
			<span>Back to Orders</span>
				</a>
	</div>
				<hr>
 		
		<div class="order-details-wrapper">

		<div class="view-order-content">
			<span class="view-order-content__title">Customer:</span> <span class="view-order-content__detail"><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?></span>
				
			  <span class="view-order-content__title">Sales Order Number:</span>
		<span class="view-order-content__detail"><?php echo $order->get_order_number(); ?></span>
			
		<span class="view-order-content__title">Issue Date:</span>
		<span class="view-order-content__detail"><?php echo date('d/m/Y', strtotime($order->get_date_created()->date('Y-m-d H:i:s'))); ?></span>
		
		<span class="view-order-content__title">Status:</span>
		<span class="view-order-content__detail"><?php echo $order->get_status(); ?></span>
	
		</div>
		<div class="woocommerce-column woocommerce-column--2 woocommerce-column--shipping-address col-2 shipping_billing">
			<div>
				
					<h2 class="woocommerce-column__title shipping_address_title"><?php esc_html_e( 'Shipping address', 'woocommerce' ); ?></h2>
			
				<address>
					<?php echo wp_kses_post( $order->get_formatted_shipping_address( esc_html__( 'N/A', 'woocommerce' ) ) ); ?>

					<?php if ( $order->get_shipping_phone() ) : ?>
						<p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_shipping_phone() ); ?></p>
					<?php endif; ?>

					<?php
						/**
						 * Action hook fired after an address in the order customer details.
						 *
						 * @since 8.7.0
						 * @param string $address_type Type of address (billing or shipping).
						 * @param WC_Order $order Order object.
						 */
						do_action( 'woocommerce_order_details_after_customer_address', 'shipping', $order );
					?>
				</address>
			</div>
			<div>
				<h2 class="woocommerce-column__title billing_address_title"><?php esc_html_e( 'Billing address', 'woocommerce' ); ?></h2>
		
<!-- 			<address class="view-order-content__detail billing_address">
			<?php echo esc_html(
		$order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . ', ' .
		($order->get_billing_address_1() ?: '') . ', ' .
		($order->get_billing_address_2() ?: '') . ', ' .
		($order->get_billing_city() ?: '') . ', ' .
		($order->get_billing_state() ?: '') . ', ' .
		($order->get_billing_postcode() ?: '') . ', ' .
		($order->get_billing_country() ?: '')
	); ?></address> -->
			<address class="billing_address">
	<?php
	echo wp_kses_post(
		$order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '<br>' .
		( $order->get_billing_address_1() ? $order->get_billing_address_1() . '<br>' : '' ) .
		( $order->get_billing_address_2() ? $order->get_billing_address_2() . '<br>' : '' ) .
		$order->get_billing_city() . ', ' .
		$order->get_billing_state() . ' ' .
		$order->get_billing_postcode() . '<br>' .
		$order->get_billing_country()
	);
	?>
</address>
			</div>

			</div>
				<div class="order-summary-wrapper">
		<div class="view-order-summary">
			<div class="order-total-final">
				<h2>
					Order Total 
				</h2>
			</div>
			<div>
				<span class="view-order-summary__title">Subtotal :</span>
				<span class="view-order-summary__detail"><?php echo wc_price($order->get_subtotal()); ?></span>
			</div>

			<div>
				<span class="view-order-summary__title">Freight :</span>
				<span class="view-order-summary__detail"><?php echo wc_price($order->get_shipping_total()); ?></span>
			</div>

			<div>
				<span class="view-order-summary__title">Tax :</span>
				<span class="view-order-summary__detail"><?php echo wc_price($order->get_total_tax()); ?></span>
			</div>
			<div class="order-totals">
				<span class="view-order-summary__title total_title">Total :</span>
				<span class="view-order-summary__detail total_title"><?php echo wc_price($order->get_total()); ?></span>
			</div>
		</div>
	</div>
				</div>
				<hr>
				<div class="order-detail">


		 <table border="1" cellpadding="5" cellspacing="0">
		<thead>
			<tr>
				<th>Item ID</th>
				<th>Description</th>
				<th>Category</th>
<!-- 				<th>Unit</th> -->
				<th>Order qty</th>
				<th>Unit price</th>
				<th>Discount (%)</th>
				<th>Amount ($)</th>
<!-- 				<th>Job</th> -->
<!-- 				<th>Tax code</th> -->
			</tr>
		</thead>
		<tbody style="text-align:center;">
			<?php

			foreach ($order->get_items() as $item_id => $item) {
				$product = $item->get_product(); 
				?>
				<tr class="order_table_tr">
					<td><?php echo esc_html($item->get_id()); ?></td> 
					<td><?php echo esc_html($item->get_name()); ?></td> 
					<td><?php echo esc_html(wp_get_post_terms($product->get_id(), 'product_cat')[0]->name ?? ''); ?></td> 
<!-- 					<td><?php echo esc_html($item->get_quantity()); ?></td>  -->
					<td><?php echo esc_html($item->get_quantity()); ?></td> 
					<td><?php echo wc_price($item->get_subtotal() / $item->get_quantity()); ?></td> 
					<td><?php echo esc_html($item->get_subtotal() - $item->get_total() > 0 ? round((($item->get_subtotal() - $item->get_total()) / $item->get_subtotal()) * 100, 2) : 0) . '%'; ?></td> 
					<td><?php echo wc_price($item->get_total()); ?></td> 
<!-- 					<td><?php echo esc_html(get_post_meta($item->get_id(), 'job_field', true) ?: ''); ?></td>  -->
<!-- 					<td><?php echo esc_html($item->get_tax_class() ?: ''); ?></td>  -->
				</tr>
			<?php
			}
			?>
		</tbody>
	</table>

<!-- 				<div class="order-summary-wrapper">
		<div class="view-order-summary">
			<div class="order-total-final">
				<h2>
					Order Total 
				</h2>
			</div>
			<div>
				<span class="view-order-summary__title">Subtotal:</span>
				<span class="view-order-summary__detail"><?php echo wc_price($order->get_subtotal()); ?></span>
			</div>

			<div>
				<span class="view-order-summary__title">Freight:</span>
				<span class="view-order-summary__detail"><?php echo wc_price($order->get_shipping_total()); ?></span>
			</div>

			<div>
				<span class="view-order-summary__title">Tax:</span>
				<span class="view-order-summary__detail"><?php echo wc_price($order->get_total_tax()); ?></span>
			</div>

			<div class="order-totals">
				<span class="view-order-summary__title">Total:</span>
				<span class="view-order-summary__detail"><?php echo wc_price($order->get_total()); ?></span>
			</div>
		</div>
	</div> -->
	</div>


		<?php if ( $show_shipping ) : ?>

			</div><!-- /.col-1 -->

	<!-- 		<div class="woocommerce-column woocommerce-column--2 woocommerce-column--shipping-address col-2">
				<h2 class="woocommerce-column__title"><?php esc_html_e( 'Shipping address', 'woocommerce' ); ?></h2>
				<address>
					<?php echo wp_kses_post( $order->get_formatted_shipping_address( esc_html__( 'N/A', 'woocommerce' ) ) ); ?>

					<?php if ( $order->get_shipping_phone() ) : ?>
						<p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_shipping_phone() ); ?></p>
					<?php endif; ?>

					<?php
						/**
						 * Action hook fired after an address in the order customer details.
						 *
						 * @since 8.7.0
						 * @param string $address_type Type of address (billing or shipping).
						 * @param WC_Order $order Order object.
						 */
						do_action( 'woocommerce_order_details_after_customer_address', 'shipping', $order );
					?>
				</address>
			</div> -->
			<!-- /.col-2 -->

		</section><!-- /.col2-set -->

		<?php endif; ?>

		<?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>

	</section>
