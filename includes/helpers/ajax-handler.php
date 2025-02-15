<?php
if (!defined('ABSPATH')) {
    exit;
}

if (ob_get_length()) {
    ob_clean();
}

// Debugging: AJAX-Handler wird aufgerufen
error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: ajax-handler.php geladen");

// AJAX-Hook für die Aktualisierung des Zuschlags
add_action('wp_ajax_update_surcharge', 'update_surcharge');
add_action('wp_ajax_nopriv_update_surcharge', 'update_surcharge');

function update_surcharge() {
    if (!WC()->cart) {
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Warenkorb nicht geladen.");
        wp_send_json_error(['message' => 'Warenkorb nicht geladen']);
        return;
    }

    $cart = WC()->cart;
    $customer_type = isset($_POST['customer_type']) ? sanitize_text_field($_POST['customer_type']) : 'verein_ssb';
    $surcharge_name = 'Kundenart-Zuschlag';

    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: AJAX-Zuschlagsberechnung gestartet für Kundenart: " . $customer_type);

    // Vorhandene Zuschläge entfernen
    foreach ($cart->get_fees() as $fee_key => $fee) {
        if ($fee->name === $surcharge_name) {
            error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Entferne alten Zuschlag.");
            unset(WC()->cart->fees_api()->fees[$fee_key]);
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
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;

        // Prüfen, ob der Zuschlag nicht bereits existiert
        $existing_fees = array_map(function($fee) {
            return $fee->name;
        }, $cart->get_fees());

        if (!in_array($surcharge_name, $existing_fees)) {
            error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Neuer Zuschlag hinzugefügt: " . $surcharge_amount . " EUR");
            $cart->add_fee($surcharge_name, $surcharge_amount, true);
        } else {
            error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlag existiert bereits, wird nicht erneut hinzugefügt.");
        }
    }

    // Debugging: Alle aktuellen Gebühren loggen
    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Aktuelle Gebühren nach AJAX-Berechnung: " . print_r($cart->get_fees(), true));

    // Erfolgreiche Rückmeldung
    wp_send_json_success(['message' => 'Zuschlag aktualisiert']);
}

// Sicherstellen, dass die Berechnung auch bei Aktualisierung des Warenkorbs passiert
add_action('woocommerce_cart_calculate_fees', 'apply_surcharge_to_cart');

function apply_surcharge_to_cart() {
    if (!WC()->cart) {
        return;
    }

    $cart = WC()->cart;
    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $surcharge_name = 'Kundenart-Zuschlag';

    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Berechnung des Zuschlags im Warenkorb gestartet für Kundenart: " . $customer_type);

    // Vorhandene Zuschläge entfernen
    foreach ($cart->get_fees() as $fee_key => $fee) {
        if ($fee->name === $surcharge_name) {
            unset(WC()->cart->fees_api()->fees[$fee_key]);
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
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;

        // Prüfen, ob der Zuschlag nicht bereits existiert
        $existing_fees = array_map(function($fee) {
            return $fee->name;
        }, $cart->get_fees());

        if (!in_array($surcharge_name, $existing_fees)) {
            $cart->add_fee($surcharge_name, $surcharge_amount, true);
            error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlag im Warenkorb neu hinzugefügt: " . $surcharge_amount . " EUR");
        } else {
            error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlag existiert bereits im Warenkorb.");
        }
    }

    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Endgültige Gebühren im Warenkorb: " . print_r($cart->get_fees(), true));
}