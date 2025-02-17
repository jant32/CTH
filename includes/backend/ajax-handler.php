<?php
/*
 * ajax-handler.php
 *
 * Dieser AJAX-Handler verarbeitet die Aktualisierung der Kundenart auf der Bestelldetailsseite
 * im Admin-Backend. Er speichert den neuen Kundenart-Wert als Order-Meta und ruft anschließend
 * die Funktion zum Neuberechnen der Zuschläge und Steuerwerte (cth_update_order_meta) auf.
 *
 * Debugging: Es werden über error_log() Meldungen in das PHP-Error-Log geschrieben.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_update_order_fee() {
    // Debug-Ausgabe: AJAX-Handler aufgerufen.
    error_log( 'cth_update_order_fee: Handler wurde aufgerufen.' );

    // Berechtigungsprüfung
    if ( ! current_user_can( 'edit_shop_orders' ) ) {
        error_log( 'cth_update_order_fee: User nicht berechtigt.' );
        wp_send_json_error( 'Unauthorized' );
    }
    
    $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
    $customer_type = isset( $_POST['customer_type'] ) ? sanitize_text_field( $_POST['customer_type'] ) : '';
    error_log( 'cth_update_order_fee: Order ID: ' . $order_id . ', Customer Type: ' . $customer_type );
    
    if ( ! $order_id || empty( $customer_type ) ) {
        error_log( 'cth_update_order_fee: Fehlende Parameter.' );
        wp_send_json_error( 'Missing parameters' );
    }
    
    // Speichere den neuen Kundenart-Wert als Order-Meta
    update_post_meta( $order_id, '_cth_customer_type', $customer_type );
    error_log( 'cth_update_order_fee: Order-Meta _cth_customer_type aktualisiert.' );
    
    // Rufe die Funktion zum Aktualisieren der Order-Meta und Neuberechnung der Fees auf
    if ( function_exists( 'cth_update_order_meta' ) ) {
        cth_update_order_meta( $order_id );
        error_log( 'cth_update_order_fee: cth_update_order_meta wurde aufgerufen.' );
    } else {
        error_log( 'cth_update_order_fee: cth_update_order_meta existiert nicht.' );
    }
    
    wp_send_json_success( array( 'message' => 'Order fees updated' ) );
}
add_action( 'wp_ajax_cth_update_order_fee', 'cth_update_order_fee' );