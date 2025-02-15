<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Fehleranzeige unterdrücken (idealerweise in der wp-config.php global eingestellt)
ini_set('display_errors', 0);
error_reporting(0);

if ( ob_get_length() ) {
    ob_clean();
}

// Debugging: AJAX-Handler wird geladen
error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: ajax-handler.php loaded");

// AJAX-Hooks für die Aktualisierung des Kundenart-Werts
add_action('wp_ajax_update_surcharge', 'update_surcharge');
add_action('wp_ajax_nopriv_update_surcharge', 'update_surcharge');

function update_surcharge() {
    // Alle aktiven Output-Puffer leeren
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Im Admin-Bereich: Falls order_id übergeben wird, aktualisiere Order-Meta
    if ( is_admin() && isset($_POST['order_id']) ) {
        $order_id = intval($_POST['order_id']);
        $customer_type = isset($_POST['customer_type']) ? sanitize_text_field($_POST['customer_type']) : 'verein_ssb';
        update_post_meta( $order_id, '_customer_type', $customer_type );
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Admin – Customer type for order $order_id updated to: " . $customer_type);
        wp_send_json_success(['message' => 'Kundenart aktualisiert (Admin)']);
        return;
    }
    
    // Im Frontend: Prüfe, ob der Warenkorb geladen ist
    if ( ! WC()->cart ) {
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Warenkorb nicht geladen.");
        wp_send_json_error(['message' => 'Warenkorb nicht geladen']);
        return;
    }
    
    // Kundenart aus POST übernehmen, Standard: 'verein_ssb'
    $customer_type = isset($_POST['customer_type']) ? sanitize_text_field($_POST['customer_type']) : 'verein_ssb';
    WC()->session->set('customer_type', $customer_type);
    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: AJAX – Customer type updated to: " . $customer_type);
    
    // Nochmals sicherstellen, dass keine Ausgabe im Buffer ist
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    wp_send_json_success(['message' => 'Kundenart aktualisiert']);
}