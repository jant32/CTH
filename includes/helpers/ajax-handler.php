<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sicherstellen, dass keine Fehler direkt ausgegeben werden
@ini_set('display_errors', 0);
error_reporting(0);

// Falls Output bereits gestartet wurde, starte einen eigenen Puffer
if (!ob_get_length()) {
    ob_start();
}

error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: ajax-handler.php loaded");

add_action('wp_ajax_update_surcharge', 'update_surcharge');
add_action('wp_ajax_nopriv_update_surcharge', 'update_surcharge');

function update_surcharge() {
    // Leere alle aktiven Puffer (damit keine unerwünschte Ausgabe mitgesendet wird)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Im Admin-Bereich: Wenn eine order_id übergeben wird, speichere den Kundenart-Wert als Order-Meta
    if ( is_admin() && isset($_POST['order_id']) ) {
        $order_id = intval($_POST['order_id']);
        $customer_type = isset($_POST['customer_type']) ? sanitize_text_field($_POST['customer_type']) : 'verein_ssb';
        update_post_meta( $order_id, '_customer_type', $customer_type );
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Admin – Customer type for order $order_id updated to: " . $customer_type);
        // Nutze ob_start() nicht mehr, wenn schon output vorhanden ist
        wp_send_json_success(['message' => 'Customer type updated (Admin)']);
        exit;
    }
    
    // Für den Frontend-Fall: Prüfe, ob der Warenkorb verfügbar ist
    if ( ! WC()->cart ) {
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Cart not loaded.");
        wp_send_json_error(['message' => 'Cart not loaded']);
        exit;
    }
    
    $customer_type = isset($_POST['customer_type']) ? sanitize_text_field($_POST['customer_type']) : 'verein_ssb';
    WC()->session->set('customer_type', $customer_type);
    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: AJAX – Customer type updated to: " . $customer_type);
    
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    wp_send_json_success(['message' => 'Customer type updated']);
}