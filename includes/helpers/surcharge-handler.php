<?php
if (!defined('ABSPATH')) {
    exit;
}

// Debugging-Log aktivieren
error_log("DEBUG: surcharge-handler.php geladen");

// Funktion zum Hinzufügen des Zuschlags
function apply_customer_type_surcharge() {
    if (is_admin() && !defined('DOING_AJAX')) {
        return; // Sicherstellen, dass die Funktion nicht im Admin-Bereich ausgeführt wird
    }

    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $cart = WC()->cart;
    $surcharge_name = 'Kundenart-Zuschlag';
    $surcharge_percentage = 0;

    error_log("DEBUG: Zuschlagsberechnung gestartet für Kundenart: " . $customer_type);

    // Verhindern, dass die Funktion mehrfach läuft
    static $called = false;
    if ($called) {
        error_log("DEBUG: Zuschlag bereits berechnet, breche ab.");
        return;
    }
    $called = true;

    // Bestimmen des Zuschlags
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

    // Vorhandene Zuschläge entfernen, um doppelte Einträge zu verhindern
    foreach ($cart->get_fees() as $fee_key => $fee) {
        if ($fee->name === $surcharge_name) {
            error_log("DEBUG: Entferne alten Zuschlag.");
            unset($cart->fees_api()->fees[$fee_key]);
        }
    }

    // Neuen Zuschlag berechnen und hinzufügen
    if ($surcharge_percentage > 0) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        error_log("DEBUG: Neuer Zuschlag hinzugefügt: " . $surcharge_amount . " EUR");
        $cart->add_fee($surcharge_name, $surcharge_amount, true);
    }

    // Debugging: Logge alle aktuellen Gebühren
    error_log("DEBUG: Aktuelle Gebühren: " . print_r($cart->get_fees(), true));
}

// Stelle sicher, dass die Funktion nur einmal registriert wird
add_action('woocommerce_cart_calculate_fees', 'apply_customer_type_surcharge', 10);