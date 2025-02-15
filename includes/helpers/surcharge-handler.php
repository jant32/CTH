<?php
if (!defined('ABSPATH')) {
    exit;
}

// Debugging: surcharge-handler.php geladen
error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: surcharge-handler.php geladen");

// AJAX-Hook für die manuelle Aktualisierung des Zuschlags aus dem Admin-Backend
add_action('wp_ajax_admin_update_surcharge', 'admin_update_surcharge');

function admin_update_surcharge() {
    if (!isset($_POST['order_id']) || !isset($_POST['customer_type'])) {
        error_log("[" . date("Y-m-d H:i:s") . "] ERROR: Fehlende Parameter für admin_update_surcharge");
        wp_send_json_error(['message' => 'Fehlende Parameter']);
        return;
    }

    $order_id = intval($_POST['order_id']);
    $customer_type = sanitize_text_field($_POST['customer_type']);
    $order = wc_get_order($order_id);

    if (!$order) {
        error_log("[" . date("Y-m-d H:i:s") . "] ERROR: Bestellung nicht gefunden (ID: $order_id)");
        wp_send_json_error(['message' => 'Bestellung nicht gefunden']);
        return;
    }

    $surcharge_name = 'Kundenart-Zuschlag';

    // Vorhandene Zuschläge entfernen
    foreach ($order->get_fees() as $fee_id => $fee) {
        if ($fee->get_name() === $surcharge_name) {
            error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Entferne alten Zuschlag in Bestellung (ID: $order_id)");
            $order->remove_item($fee_id);
        }
    }

    // Bestimmen des Zuschlags
    $surcharge_percentage = 0;
    switch ($customer_type) {
        case 'verein_non_ssb':
            $surcharge_percentage = 0.05;
            break;
        case 'privatperson':
            $surcharge_percentage = 0.10;
            break;
        case 'kommerziell':
            $surcharge_percentage = 0.15;
            break;
    }

    if ($surcharge_percentage > 0) {
        $surcharge_amount = $order->get_subtotal() * $surcharge_percentage;
        $fee = new WC_Order_Item_Fee();
        $fee->set_name($surcharge_name);
        $fee->set_amount($surcharge_amount);
        $fee->set_total($surcharge_amount);
        $order->add_item($fee);
        
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Neuer Zuschlag in Bestellung hinzugefügt: " . $surcharge_amount . " EUR");
    }

    $order->calculate_totals();
    $order->save();

    wp_send_json_success(['message' => 'Zuschlag aktualisiert']);
}

// Enqueue das Script für das Admin-Backend
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'edit.php' || $hook === 'post.php') {
        wp_enqueue_script('admin-surcharge-handler', plugin_dir_url(__FILE__) . 'admin-surcharge.js', ['jquery'], null, true);
        wp_localize_script('admin-surcharge-handler', 'adminSurcharge', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('admin_surcharge_nonce'),
        ]);
    }
});