<?php
/**
 * Customer invoice account endpoint.
 *
 * @package FHS\Core
 */

namespace FHS\Core;

use DateTime;

defined( 'ABSPATH' ) || exit;

class Invoices {
	/**
	 * Invoice table suffix.
	 */
	const TABLE_SUFFIX = 'myob_invoices';

	/**
	 * Fallback calendar icon path.
	 */
	const DEFAULT_CALENDAR_ICON = '/wp-content/uploads/2025/04/calendar-1.svg';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_account_invoices_endpoint', array( $this, 'render_invoices_endpoint' ) );
	}

	/**
	 * Render invoices endpoint.
	 *
	 * @return void
	 */
	public function render_invoices_endpoint() {
		global $wpdb;

		$user_id     = get_current_user_id();
		$customer_id = get_the_author_meta( Myob::META_CUSTOMER_ID, $user_id );
		$table_name  = $wpdb->prefix . self::TABLE_SUFFIX;

		echo '<div class="invoices-main-container">';
		echo '<div class="invoices-header-container">';
		echo '<h2>' . esc_html__( 'My Invoices', 'astra-child' ) . '</h2>';
		echo '<div class="invoice-filter-container">';
		echo '<form action="" id="invoice-header-filter" method="get">';
		echo '<div class="invoice-filters">';
		echo '<div class="date-range-picker-container">';
		echo '<img src="' . esc_url( $this->get_calendar_icon_url() ) . '" alt="">';
		echo '<input class="form-control form-control-solid" id="kt_daterangepicker_5" placeholder="Pick Date Range" autocomplete="off">';
		echo '<input type="hidden" id="start_date" name="start_date">';
		echo '<input type="hidden" id="end_date" name="end_date">';
		echo '</div>';

		if ( empty( $customer_id ) ) {
			echo '</div></form></div></div>';
			echo '<div class="woocommerce-info"><p>' . esc_html__( 'No customer ID found for your account.', 'astra-child' ) . '</p></div></div>';
			return;
		}

		$status_rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT status FROM {$table_name} WHERE customer_id = %s",
				$customer_id
			)
		);

		echo '<select name="status" id="invoice-status">';
		echo "<option value=''>Status</option>";
		foreach ( $status_rows as $status ) {
			$selected = ( isset( $_GET['status'] ) && sanitize_text_field( wp_unslash( $_GET['status'] ) ) === $status ) ? 'selected' : '';
			echo "<option value='" . esc_attr( $status ) . "' {$selected}>" . esc_html( ucwords( $status ) ) . '</option>';
		}
		echo '</select>';
		echo '</div></form></div></div>';

		$per_page = 10;
		$paged    = isset( $_GET['pg'] ) ? max( 1, absint( $_GET['pg'] ) ) : 1;
		$offset   = ( $paged - 1 ) * $per_page;

		$conditions = array( 'customer_id = %s' );
		$params     = array( $customer_id );

		if ( ! empty( $_GET['start_date'] ) && ! empty( $_GET['end_date'] ) ) {
			$start = DateTime::createFromFormat( 'd-M-Y', sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) );
			$end   = DateTime::createFromFormat( 'd-M-Y', sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) );
			if ( $start && $end ) {
				$conditions[] = 'date BETWEEN %s AND %s';
				$params[]     = $start->format( 'Y-m-d' );
				$params[]     = $end->format( 'Y-m-d' );
			}
		}

		if ( ! empty( $_GET['status'] ) ) {
			$conditions[] = 'status = %s';
			$params[]     = sanitize_text_field( wp_unslash( $_GET['status'] ) );
		}

		$where_clause = implode( ' AND ', $conditions );
		$count_query  = $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}", $params );
		$total_items  = (int) $wpdb->get_var( $count_query );
		$total_pages  = (int) ceil( $total_items / $per_page );
		$query        = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY date DESC LIMIT %d OFFSET %d",
			array_merge( $params, array( $per_page, $offset ) )
		);
		$invoices     = $wpdb->get_results( $query );

		if ( empty( $invoices ) ) {
			echo '<div class="woocommerce-message woocommerce-info">' . esc_html__( 'No invoices have been found yet.', 'astra-child' ) . '</div></div>';
			return;
		}

		echo '<table class="woocommerce-orders-table woocommerce-MyAccount-invoices shop_table"><thead><tr>';
		echo '<th>#INVOICE</th><th>DATE</th><th>DUE DATE</th><th>Purchase Order Number</th><th>AMOUNT</th><th>OUTSTANDING</th><th>STATUS</th><th>ACTION</th>';
		echo '</tr></thead><tbody>';

		foreach ( $invoices as $invoice ) {
			echo '<tr>';
			echo '<td>#' . esc_html( $invoice->invoice_number ) . '</td>';
			echo '<td>' . esc_html( date( 'd/m/Y', strtotime( $invoice->date ) ) ) . '</td>';
			echo '<td>' . ( ! empty( $invoice->due_date ) ? esc_html( date( 'd/m/Y', strtotime( $invoice->due_date ) ) ) : '-' ) . '</td>';
			echo '<td>' . esc_html( $invoice->po_number ) . '</td>';
			echo '<td>$' . esc_html( number_format( (float) $invoice->amount, 2 ) ) . '</td>';
			echo '<td>$' . esc_html( number_format( (float) $invoice->outstanding, 2 ) ) . '</td>';
			echo '<td>' . $this->format_invoice_status( $invoice->status ) . '</td>';
			echo '<td><a class="download-invoice-pdf" href="' . esc_url( $this->get_invoice_download_url( $invoice ) ) . '" target="_blank" rel="noopener noreferrer"><i class="icofont-eye-alt"></i></a></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

		if ( $total_pages > 1 ) {
			echo '<div class="woocommerce-pagination">';
			echo paginate_links(
				array(
					'base'      => trailingslashit( home_url( '/my-account/invoices' ) ) . '?pg=%#%',
					'format'    => '',
					'current'   => $paged,
					'total'     => $total_pages,
					'prev_text' => __( '« Prev', 'astra-child' ),
					'next_text' => __( 'Next »', 'astra-child' ),
				)
			);
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Format invoice status badge.
	 *
	 * @param string $status Status text.
	 * @return string
	 */
	public function format_invoice_status( $status ) {
		$class = strtolower( (string) $status );
		return '<span class="invoice-status ' . esc_attr( $class ) . '">' . strtoupper( esc_html( $status ) ) . '</span>';
	}

	/**
	 * Resolve calendar icon URL.
	 *
	 * @return string
	 */
	private function get_calendar_icon_url() {
		return home_url( self::DEFAULT_CALENDAR_ICON );
	}

	/**
	 * Build invoice download URL while preserving the existing route contract.
	 *
	 * @param object $invoice Invoice row object.
	 * @return string
	 */
	private function get_invoice_download_url( $invoice ) {
		return add_query_arg(
			array(
				'invoice_uid' => isset( $invoice->myob_uid ) ? $invoice->myob_uid : '',
				'type'        => isset( $invoice->invoice_type ) ? $invoice->invoice_type : '',
			),
			home_url( '/wp-json/invoice/file_download' )
		);
	}
}
