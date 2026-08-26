<?php
/**
 * Order and account business information display.
 *
 * @package FHS\Core
 */

namespace FHS\Core;

defined( 'ABSPATH' ) || exit;

class Orders {
	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_my_account_my_orders_columns', array( $this, 'filter_account_order_columns' ), 20 );
		add_action( 'woocommerce_my_account_my_orders_column_product_order_number', array( $this, 'render_product_order_number_column' ) );
		add_action( 'woocommerce_my_account_my_orders_column_required_date', array( $this, 'render_required_date_column' ) );
		add_action( 'woocommerce_my_account_my_orders_column_pay_now', array( $this, 'render_pay_now_column' ) );
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'filter_order_actions' ), 10, 2 );
		add_filter( 'woocommerce_my_account_my_orders_columns', array( $this, 'add_outstanding_amount_column' ) );
		add_action( 'woocommerce_my_account_my_orders_column_outstanding-amount', array( $this, 'render_outstanding_amount_column' ) );
		add_filter( 'woocommerce_my_account_my_orders_columns', array( $this, 'order_account_columns' ), 20 );
		add_action( 'woocommerce_my_account_my_orders_column_fulfilment_method', array( $this, 'render_fulfilment_method_column' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'render_admin_order_business_details' ), 20 );
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'filter_legacy_admin_order_columns' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_legacy_admin_order_column' ), 20, 2 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'filter_hpos_admin_order_columns' ), 20 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_hpos_admin_order_column' ), 20, 2 );
	}

	/**
	 * Add PO / required date / pay now around current order columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function filter_account_order_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $column;

			if ( 'order-total' === $key ) {
				$user_id       = get_current_user_id();
				$payment_terms = get_user_meta( $user_id, Myob::META_PAYMENT_TERMS, true );

				if ( 'DayOfMonthAfterEOM' === $payment_terms ) {
					$new_columns['pay_now'] = __( 'Pay Now', 'woocommerce' );
				}

				$new_columns['product_order_number'] = __( 'Product Order Number', 'woocommerce' );
				$new_columns['required_date']        = __( 'Required Date', 'woocommerce' );
			}
		}

		return $new_columns;
	}

	/**
	 * Add outstanding amount column.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_outstanding_amount_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $name ) {
			$new_columns[ $key ] = $name;
			if ( 'order-total' === $key ) {
				$new_columns['outstanding-amount'] = __( 'Outstanding Amount', 'woocommerce' );
			}
		}
		return $new_columns;
	}

	/**
	 * Override account order column order to match current customer portal layout.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function order_account_columns( $columns ) {
		return array(
			'order-number'         => __( 'Orders', 'woocommerce' ),
			'order-date'           => __( 'Order Date', 'woocommerce' ),
			'fulfilment_method'    => __( 'Fulfilment', 'woocommerce' ),
			'required_date'        => __( 'Required Date', 'woocommerce' ),
			'product_order_number' => __( 'Purchase Order Number Number', 'woocommerce' ),
			'order-total'          => __( 'Amount', 'woocommerce' ),
			'outstanding-amount'   => __( 'Outstanding', 'woocommerce' ),
			'order-actions'        => __( 'Actions', 'woocommerce' ),
			'pay_now'              => __( 'Pay Now', 'woocommerce' ),
		);
	}

	/**
	 * Render product order number.
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public function render_product_order_number_column( $order ) {
		$product_order_number = get_post_meta( $order->get_id(), '_product_order_number', true );
		echo $product_order_number ? esc_html( $product_order_number ) : '-';
	}

	/**
	 * Render required date.
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public function render_required_date_column( $order ) {
		$required_date = get_post_meta( $order->get_id(), '__order_required_date', true );
		echo $required_date ? esc_html( $required_date ) : '-';
	}

	/**
	 * Render pay now button.
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public function render_pay_now_column( $order ) {
		if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
			echo '<a class="button pay" href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay Now', 'woocommerce' ) . '</a>';
			return;
		}

		echo '-';
	}

	/**
	 * Remove duplicate pay/cancel actions when custom pay now column is used.
	 *
	 * @param array    $actions Actions.
	 * @param \WC_Order $order Order.
	 * @return array
	 */
	public function filter_order_actions( $actions, $order ) {
		if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
			unset( $actions['pay'], $actions['cancel'] );
		}

		return $actions;
	}

	/**
	 * Render outstanding amount.
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public function render_outstanding_amount_column( $order ) {
		$outstanding_amount = 0;
		if ( in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
			$outstanding_amount = $order->get_total();
		}

		if ( $outstanding_amount > 0 ) {
			echo wc_price( $outstanding_amount );
			return;
		}

		echo '–';
	}

	/**
	 * Render fulfilment method column.
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public function render_fulfilment_method_column( $order ) {
		echo esc_html( $this->get_fulfilment_label_from_order_id( $order->get_id() ) );
	}

	/**
	 * Render business information in the admin order screen.
	 *
	 * Preserves current order meta presentation for PO number and fulfilment method.
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public function render_admin_order_business_details( $order ) {
		$product_order_number = get_post_meta( $order->get_id(), '_product_order_number', true );
		if ( $product_order_number ) {
			echo '<p><strong>' . esc_html__( 'Product Order Number', 'woocommerce' ) . ':</strong> ' . esc_html( $product_order_number ) . '</p>';
		}

		$method = (string) $order->get_meta( '_fhs_fulfilment_method' );
		if ( 'pickup' !== $method && 'delivery' !== $method ) {
			echo '<p><strong>' . esc_html__( 'Fulfilment Method', 'woocommerce' ) . ':</strong> -</p>';
			return;
		}

		echo '<p><strong>' . esc_html__( 'Fulfilment Method', 'woocommerce' ) . ':</strong> ' . esc_html( $this->get_fulfilment_label( $method ) ) . '</p>';
	}

	/**
	 * Add fulfilment column for legacy orders table.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function filter_legacy_admin_order_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$new_columns['fhs_fulfilment_method'] = __( 'Fulfilment', 'woocommerce' );
			}
		}
		if ( ! isset( $new_columns['fhs_fulfilment_method'] ) ) {
			$new_columns['fhs_fulfilment_method'] = __( 'Fulfilment', 'woocommerce' );
		}
		return $new_columns;
	}

	/**
	 * Render legacy admin order fulfilment column.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Order post ID.
	 * @return void
	 */
	public function render_legacy_admin_order_column( $column, $post_id ) {
		if ( 'fhs_fulfilment_method' !== $column ) {
			return;
		}

		echo esc_html( $this->get_fulfilment_label_from_order_id( $post_id ) );
	}

	/**
	 * Add fulfilment column for HPOS orders table.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function filter_hpos_admin_order_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$new_columns['fhs_fulfilment_method'] = __( 'Fulfilment', 'woocommerce' );
			}
		}
		if ( ! isset( $new_columns['fhs_fulfilment_method'] ) ) {
			$new_columns['fhs_fulfilment_method'] = __( 'Fulfilment', 'woocommerce' );
		}
		return $new_columns;
	}

	/**
	 * Render HPOS admin order fulfilment column.
	 *
	 * @param string         $column Column name.
	 * @param int|\WC_Order $order_or_id Order or order ID.
	 * @return void
	 */
	public function render_hpos_admin_order_column( $column, $order_or_id ) {
		if ( 'fhs_fulfilment_method' !== $column ) {
			return;
		}

		$order = is_a( $order_or_id, 'WC_Order' ) ? $order_or_id : wc_get_order( $order_or_id );
		if ( ! $order ) {
			echo '-';
			return;
		}

		echo esc_html( $this->get_fulfilment_label_from_order_id( $order->get_id() ) );
	}

	/**
	 * Resolve fulfilment label from order ID.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	private function get_fulfilment_label_from_order_id( $order_id ) {
		$method = (string) get_post_meta( $order_id, '_fhs_fulfilment_method', true );
		if ( 'pickup' !== $method && 'delivery' !== $method ) {
			return '-';
		}

		return $this->get_fulfilment_label( $method );
	}

	/**
	 * Resolve fulfilment label using existing helper when available.
	 *
	 * @param string $method Fulfilment method.
	 * @return string
	 */
	private function get_fulfilment_label( $method ) {
		if ( function_exists( 'fhs_get_fulfilment_label' ) ) {
			return (string) fhs_get_fulfilment_label( $method );
		}

		return 'pickup' === $method ? __( 'Pick up', 'woocommerce' ) : __( 'Delivery', 'woocommerce' );
	}
}
