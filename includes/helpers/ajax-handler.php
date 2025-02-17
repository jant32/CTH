<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fehleranzeige unterdrücken
@ini_set('display_errors', 0);
error_reporting(0);

// Starte einen Output-Buffer (falls noch nicht aktiv)
if (!ob_get_level()) {
    ob_start();
}

error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: ajax-handler.php loaded");

add_action('wp_ajax_update_surcharge', 'update_surcharge');
add_action('wp_ajax_nopriv_update_surcharge', 'update_surcharge');

function update_surcharge() {
    // Starte (oder reinige) den Buffer
    ob_start();
    
    // Admin-Bereich: Wenn order_id übergeben wird, speichere den Kundenart-Wert als Order-Meta
    if ( is_admin() && isset($_POST['order_id']) ) {
        $order_id = intval($_POST['order_id']);
        $customer_type = isset($_POST['customer_type']) ? sanitize_text_field($_POST['customer_type']) : 'verein_ssb';
        update_post_meta( $order_id, '_customer_type', $customer_type );
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Admin – Customer type for order $order_id updated to: " . $customer_type);
        // Leere den Buffer und setze Header
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        wp_send_json_success(['message' => 'Customer type updated (Admin)']);
        exit;
    }
    
    // Frontend: Prüfe, ob der Warenkorb geladen ist
    if ( ! WC()->cart ) {
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Cart not loaded.");
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        wp_send_json_error(['message' => 'Cart not loaded']);
        exit;
    }
    
    $customer_type = isset($_POST['customer_type']) ? sanitize_text_field($_POST['customer_type']) : 'verein_ssb';
    WC()->session->set('customer_type', $customer_type);
    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: AJAX – Customer type updated to: " . $customer_type);
    
    // Leere den Buffer vor dem Senden der Antwort
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    wp_send_json_success(['message' => 'Customer type updated']);
}