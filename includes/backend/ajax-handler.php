<?php
/*
 * ajax-handler.php
 *
 * Dieser AJAX-Handler verarbeitet die Aktualisierung der Kundenart auf der Bestelldetailsseite
 * im Admin-Backend. Er speichert den neuen Kundenart-Wert als Order-Meta und ruft anschließend
 * die Funktion zum Neuberechnen der Zuschläge und Steuerwerte (cth_update_order_meta) auf.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_update_order_fee() {
    // Überprüfe, ob der Benutzer berechtigt ist, Bestellungen zu bearbeiten
    if ( ! current_user_can( 'edit_shop_orders' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    
    $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
    $customer_type = isset( $_POST['customer_type'] ) ? sanitize_text_field( $_POST['customer_type'] ) : '';
    
    if ( ! $order_id || empty( $customer_type ) ) {
        wp_send_json_error( 'Missing parameters' );
    }
    
    // Speichere den neuen Kundenart-Wert als Order-Meta
    update_post_meta( $order_id, '_cth_customer_type', $customer_type );
    
    // Rufe die Funktion zum Aktualisieren der Order-Meta (einschließlich Neuberechnung der Fees) auf
    if ( function_exists( 'cth_update_order_meta' ) ) {
        cth_update_order_meta( $order_id );
    }
    
    wp_send_json_success( array( 'message' => 'Order fees updated' ) );
}
add_action( 'wp_ajax_cth_update_order_fee', 'cth_update_order_fee' );