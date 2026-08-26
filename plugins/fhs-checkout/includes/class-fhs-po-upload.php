<?php
/**
 * Purchase order upload behavior.
 *
 * @package FHS\Checkout
 */

namespace FHS\Checkout;

defined( 'ABSPATH' ) || exit;

class Po_Upload {
	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_checkout_form_enctype', array( $this, 'filter_checkout_form_enctype' ) );
		add_action( 'fhs_checkout_after_payment_methods', array( $this, 'render_po_upload_field' ) );
		add_action( 'wp_ajax_upload_po_file', array( $this, 'handle_po_upload' ) );
		add_action( 'wp_ajax_nopriv_upload_po_file', array( $this, 'handle_po_upload' ) );
		add_filter( 'upload_size_limit', array( $this, 'filter_upload_size_limit' ), 20 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_po_file_meta' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'render_admin_po_file' ) );
	}

	/**
	 * Enable multipart checkout form.
	 *
	 * @return string
	 */
	public function filter_checkout_form_enctype() {
		return 'multipart/form-data';
	}

	/**
	 * Render checkout PO upload field.
	 *
	 * @return void
	 */
	public function render_po_upload_field() {
		echo '<div id="pay-later-po-upload" class="pay-later-extra form-row form-row-wide">';
		echo '<label class="po-upload-box" for="pay_later_po_file">';
		echo '<div class="po-upload-content">';
		echo '<div class="po-upload-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 16V4M12 4L7 9M12 4L17 9" stroke="#000" stroke-width="2"/><path d="M20 16.5V19C20 20.1 19.1 21 18 21H6C4.9 21 4 20.1 4 19V16.5" stroke="#000" stroke-width="2"/></svg></div>';
		echo '<div class="po-upload-text"><strong>Upload Purchase Order</strong><span>(PDF or Image)</span></div>';
		echo '<div class="po-file-name">No file chosen</div>';
		echo '</div>';
		echo '<input type="file" id="pay_later_po_file" accept=".pdf,.jpg,.jpeg,.png">';
		echo '<input type="hidden" name="pay_later_po_file_url" id="pay_later_po_file_url">';
		echo '</label>';
		echo '</div>';
	}

	/**
	 * Handle AJAX PO upload.
	 *
	 * @return void
	 */
	public function handle_po_upload() {
		if ( empty( $_FILES['po_file'] ) ) {
			wp_send_json_error( array( 'message' => 'No file received.' ) );
		}

		$file = $_FILES['po_file'];

		if ( $file['error'] !== UPLOAD_ERR_OK ) {
			$error_messages = array(
				UPLOAD_ERR_INI_SIZE   => 'The file exceeds the upload_max_filesize in php.ini.',
				UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the MAX_FILE_SIZE specified in the form.',
				UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
				UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
				UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
				UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
				UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
			);
			$message = isset( $error_messages[ $file['error'] ] ) ? $error_messages[ $file['error'] ] : 'Unknown upload error.';
			wp_send_json_error( array( 'message' => $message ) );
		}

		$allowed_mimes = array(
			'application/pdf',
			'image/jpeg',
			'image/png',
		);

		$file_type = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		if ( empty( $file_type['type'] ) || ! in_array( $file_type['type'], $allowed_mimes, true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid file type. Only PDF and Images are allowed.' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		$uploaded = wp_handle_upload( $file, array( 'test_form' => false ) );

		if ( isset( $uploaded['error'] ) ) {
			wp_send_json_error( array( 'message' => $uploaded['error'] ) );
		}

		wp_send_json_success(
			array(
				'url' => $uploaded['url'],
			)
		);
	}

	/**
	 * Increase upload size for purchase order attachments.
	 *
	 * @param int $size Existing size.
	 * @return int
	 */
	public function filter_upload_size_limit( $size ) {
		return 20 * 1024 * 1024;
	}

	/**
	 * Save uploaded PO file URL to order meta.
	 *
	 * @param \WC_Order $order Order object.
	 * @return void
	 */
	public function save_po_file_meta( $order ) {
		if ( ! empty( $_POST['pay_later_po_file_url'] ) ) {
			$order->update_meta_data( '_pay_later_po_file', esc_url_raw( wp_unslash( $_POST['pay_later_po_file_url'] ) ) );
		}
	}

	/**
	 * Render PO file link in order admin.
	 *
	 * @param \WC_Order $order Order object.
	 * @return void
	 */
	public function render_admin_po_file( $order ) {
		$file = $order->get_meta( '_pay_later_po_file' );
		if ( $file ) {
			echo '<p><strong>PO File:</strong> <a href="' . esc_url( $file ) . '" target="_blank">View File</a></p>';
		}
	}
}
