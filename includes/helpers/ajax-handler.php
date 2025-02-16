<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Temporär Fehleranzeigen aktivieren – bitte nur in einer Testumgebung nutzen!
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Falls bereits Output vorhanden ist, leere diesen
if (ob_get_length()) {
    ob_clean();
}

error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: ajax-handler.php loaded (update_surcharge)");

// Registriere den AJAX-Handler für "update_surcharge"
add_action('wp_ajax_update_surcharge', 'update_surcharge');
add_action('wp_ajax_nopriv_update_surcharge', 'update_surcharge');

function update_surcharge() {
    // Leere alle aktiven Output-Puffer
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Admin-Bereich: Wenn order_id gesetzt ist, speichere den Kundenart-Wert als Order-Meta
    if ( is_admin() && isset($_POST['order_id']) ) {
        $order_id = intval($_POST['order_id']);
        $customer_type = isset($_POST['customer_type']) ? sanitize_text_field($_POST['customer_type']) : 'verein_ssb';
        update_post_meta( $order_id, '_customer_type', $customer_type );
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Admin – Customer type for order $order_id updated to: " . $customer_type);
        wp_send_json_success(['message' => 'Customer type updated (Admin)']);
        return;
    }
    
    // Frontend: Überprüfe, ob der Warenkorb geladen ist
    if ( ! WC()->cart ) {
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Cart not loaded.");
        wp_send_json_error(['message' => 'Cart not loaded']);
        return;
    }
    
    $customer_type = isset($_POST['customer_type']) ? sanitize_text_field($_POST['customer_type']) : 'verein_ssb';
    WC()->session->set('customer_type', $customer_type);
    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: AJAX – Customer type updated to: " . $customer_type);
    
    // Leere nochmals den Output-Puffer
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    wp_send_json_success(['message' => 'Customer type updated']);
}