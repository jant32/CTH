<?php
/*
 * ajax-handler.php
 *
 * Diese Datei verarbeitet AJAX-Anfragen des Plugins, insbesondere das Speichern der vom Kunden auf der Kasse ausgewählten Kundenart.
 *
 * Funktionen:
 * - cth_ajax_save_customer_type(): Nimmt die per AJAX übermittelte Kundenart entgegen und speichert diese in der Session.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_ajax_save_customer_type() {
    check_ajax_referer( 'cth_ajax_nonce', 'nonce' );

    if ( isset( $_POST['customer_type'] ) ) {
        $customer_type = sanitize_text_field( $_POST['customer_type'] );
        // Kundenart in der Session speichern.
        $_SESSION['cth_customer_type'] = $customer_type;
        wp_send_json_success( array( 'message' => 'Customer type saved.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'No customer type provided.' ) );
    }
}
add_action( 'wp_ajax_cth_save_customer_type', 'cth_ajax_save_customer_type' );
add_action( 'wp_ajax_nopriv_cth_save_customer_type', 'cth_ajax_save_customer_type' );