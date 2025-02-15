<?php
if (!defined('ABSPATH')) {
    exit;
}

// Debugging: AJAX-Handler wird aufgerufen
error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: ajax-handler.php geladen");

// AJAX-Hook für die Aktualisierung des Zuschlags
add_action('wp_ajax_update_surcharge', 'update_surcharge');
add_action('wp_ajax_nopriv_update_surcharge', 'update_surcharge');

function update_surcharge() {
    if (!WC()->cart) {
        wp_send_json_error(['message' => 'Warenkorb nicht geladen']);
        return;
    }

    $cart = WC()->cart;
    $customer_type = isset($_POST['customer_type']) ? sanitize_text_field($_POST['customer_type']) : 'verein_ssb';
    $surcharge_name = 'Kundenart-Zuschlag';

    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: AJAX-Zuschlagsberechnung gestartet für Kundenart: " . $customer_type);

    // Vorhandene Zuschläge entfernen
    $existing_surcharge = false;
    foreach ($cart->get_fees() as $fee_key => $fee) {
        if ($fee->name === $surcharge_name) {
            error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Entferne alten Zuschlag (Key: $fee_key).");
            unset(WC()->cart->fees_api()->fees[$fee_key]);
            $existing_surcharge = true;
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

    // Zuschlag nur hinzufügen, wenn nicht bereits aktiv
    if ($surcharge_percentage > 0) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;

        foreach ($cart->get_fees() as $fee) {
            if ($fee->name === $surcharge_name) {
                error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlag existiert bereits, wird nicht erneut hinzugefügt.");
                wp_send_json_success(['message' => 'Zuschlag existiert bereits']);
                return;
            }
        }

        // Zuschlag hinzufügen
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Neuer Zuschlag hinzugefügt: " . $surcharge_amount . " EUR");
        $cart->add_fee($surcharge_name, $surcharge_amount, true);
    }

    // Debugging: Alle aktuellen Gebühren loggen
    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Aktuelle Gebühren nach AJAX-Berechnung: " . print_r($cart->get_fees(), true));

    // Erfolgreiche Rückmeldung
    wp_send_json_success(['message' => 'Zuschlag aktualisiert']);
}