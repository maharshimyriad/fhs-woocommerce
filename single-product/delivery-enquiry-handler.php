<?php
/**
 * Delivery Time Enquiry – AJAX Handler
 *
 * @package FHSPoly
 */

defined( 'ABSPATH' ) || exit;

/* -----------------------------------------------------------------------
 * 1. Create / upgrade the custom DB table
 * --------------------------------------------------------------------- */
function wpf_delivery_enquiry_create_table() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'delivery_time_enquiries';
    $charset_collate = $wpdb->get_charset_collate();
    $installed_ver   = get_option( 'wpf_delivery_enquiry_db_version', '0' );
    $current_ver     = '1.0';

    if ( $installed_ver === $current_ver ) {
        return;
    }

    $sql = "CREATE TABLE {$table_name} (
        id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        user_name     VARCHAR(200)        NOT NULL DEFAULT '',
        user_email    VARCHAR(200)        NOT NULL DEFAULT '',
        product_id    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        product_name  VARCHAR(500)        NOT NULL DEFAULT '',
        postcode       VARCHAR(50)         NOT NULL DEFAULT '',
        enquiry_date  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id   (user_id),
        KEY product_id (product_id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'wpf_delivery_enquiry_db_version', $current_ver );
}
add_action( 'init', 'wpf_delivery_enquiry_create_table' );

/* -----------------------------------------------------------------------
 * 2. Force non-empty subject & body at wp_mail level
 * --------------------------------------------------------------------- */
add_filter( 'wp_mail', function ( $args ) {

    if ( empty( $args['subject'] ) ) {
        $args['subject'] = 'Delivery Time Estimate Enquiry';
    }

    if ( empty( $args['message'] ) ) {
        $args['message'] = 'Delivery time enquiry submitted.';
    }

    return $args;
}, 9999 );

/* -----------------------------------------------------------------------
 * 3. Force non-empty PHPMailer Body (Stars SMTP fix)
 * --------------------------------------------------------------------- */
add_action( 'phpmailer_init', function ( $phpmailer ) {

    if ( empty( $phpmailer->Body ) ) {
        $phpmailer->isHTML( true );
        $phpmailer->Body = nl2br(
            esc_html(
                $phpmailer->AltBody ?: 'Delivery time enquiry submitted.'
            )
        );
    }

}, 5 );

/* -----------------------------------------------------------------------
 * 4. AJAX handler
 * --------------------------------------------------------------------- */
function wpf_handle_delivery_time_enquiry() {

    if ( ! check_ajax_referer( 'delivery_enquiry_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Security check failed.' );
    }

    $product_id = absint( $_POST['product_id'] ?? 0 );
    $postcode    = sanitize_text_field( $_POST['postcode'] ?? '' );
    $form_email = sanitize_email( $_POST['email'] ?? '' );
    $sku = sanitize_text_field( $_POST['sku'] ?? '' );

    if ( ! $product_id || $postcode === '' ) {
        wp_send_json_error( 'Missing required fields.' );
    }

    if ( ! is_email( $form_email ) ) {
        wp_send_json_error( 'Invalid email address.' );
    }

    $current_user = wp_get_current_user();
    $user_id      = (int) $current_user->ID;
    $user_name    = $current_user->display_name ?: 'Guest';
    $user_email   = $form_email;

    $product      = wc_get_product( $product_id );
    $product_name = $product ? $product->get_name() : "Product #{$product_id}";

    global $wpdb;
    $table_name = $wpdb->prefix . 'delivery_time_enquiries';

  $inserted = $wpdb->insert(
    $table_name,
    array(
        'user_id'      => $user_id,
        'user_name'    => $user_name,
        'user_email'   => $user_email,
        'product_id'   => $product_id,
        'product_name' => $product_name,
        'sku'          => $sku,
        'postcode'      => $postcode,
        'enquiry_date' => current_time( 'mysql' ),
    ),
    array('%d','%s','%s','%d','%s','%s','%s','%s')
);

error_log('INSERT RESULT: ' . $inserted);
error_log('DB ERROR: ' . $wpdb->last_error);
error_log('LAST QUERY: ' . $wpdb->last_query);

//     $admin_email = array(
//     'info@myriadsolutionz.com',
//     'maharshi@myriadsolutionz.com',
// );

$from_email = get_option('admin_email');
$to_email = "sales@fhs.com.au";
$from_name  = get_bloginfo('name');

    $subject = sprintf(
        'Delivery Time Estimate Enquiry – %s',
        $product_name
    );

     $body = '
    <table width="100%" cellpadding="0" cellspacing="0" style="font-family: Arial, Helvetica, sans-serif; background:#f6f7f9; padding:20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border:1px solid #e5e5e5;">
                    <tr>
                        <td style="padding:20px; border-bottom:1px solid #e5e5e5;">
                            <h2 style="margin:0; font-size:18px; color:#333;">
                                Delivery Time Estimate Enquiry
                            </h2>
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding:20px;">
                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
                                <tr>
                                    <td style="border:1px solid #ddd; font-weight:bold; width:35%;">Customer Name</td>
                                    <td style="border:1px solid #ddd;">' . esc_html( $user_name ) . '</td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #ddd; font-weight:bold;">Customer Email</td>
                                    <td style="border:1px solid #ddd;">' . esc_html( $user_email ) . '</td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #ddd; font-weight:bold;">Product</td>
                                    <td style="border:1px solid #ddd;">' . esc_html( $product_name ) . '</td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #ddd; font-weight:bold;">Product ID</td>
                                    <td style="border:1px solid #ddd;">' . esc_html( $product_id ) . '</td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #ddd; font-weight:bold;">Product SKU</td>
                                    <td style="border:1px solid #ddd;">' . esc_html( $sku ) . '</td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #ddd; font-weight:bold;">Postcode</td>
                                    <td style="border:1px solid #ddd;">' . esc_html( $postcode ) . '</td>
                                </tr>
                                <tr>
                                    <td style="border:1px solid #ddd; font-weight:bold;">Enquiry Date</td>
                                    <td style="border:1px solid #ddd;">' . esc_html( current_time( 'mysql' ) ) . '</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
    
                    <tr>
                        <td style="padding:15px; border-top:1px solid #e5e5e5; font-size:12px; color:#777;">
                            This enquiry was submitted from the product page.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';
    

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $user_email,
    );

    $sent = wp_mail( $to_email, $subject, $body, $headers );

    if ( ! $sent ) {
        error_log( 'Delivery enquiry email failed to send.' );
        wp_send_json_error( 'Enquiry saved but email failed to send.' );
    }

    wp_send_json_success( 'Enquiry submitted successfully.' );
}

add_action( 'wp_ajax_delivery_time_enquiry', 'wpf_handle_delivery_time_enquiry' );
add_action( 'wp_ajax_nopriv_delivery_time_enquiry', 'wpf_handle_delivery_time_enquiry' );