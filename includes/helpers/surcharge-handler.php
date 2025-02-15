<?php
if (!defined('ABSPATH')) {
    exit;
}

error_log("DEBUG: surcharge-handler.php geladen");

// Prüfen, ob die Funktion mehrfach registriert wird
global $wp_filter;
if (isset($wp_filter['woocommerce_cart_calculate_fees'])) {
    error_log("DEBUG: Hook 'woocommerce_cart_calculate_fees' ist registriert: " . count($wp_filter['woocommerce_cart_calculate_fees']->callbacks) . " Mal.");
}

add_action('woocommerce_cart_calculate_fees', function() {
    error_log("DEBUG: Hook 'woocommerce_cart_calculate_fees' ausgeführt von: " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), true));
}, 999);

// Funktion zum Anwenden des Zuschlags
function apply_customer_type_surcharge() {
    if (is_admin() && !defined('DOING_AJAX')) {
        return; // Im Backend keine Berechnung ausführen
    }

    // Sicherstellen, dass WooCommerce geladen ist
    if (!WC()->cart) {
        return;
    }

    $cart = WC()->cart;
    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $surcharge_name = 'Kundenart-Zuschlag';

    error_log("DEBUG: Zuschlagsberechnung gestartet für Kundenart: " . $customer_type);

    // Vorhandene Zuschläge entfernen, bevor neue hinzugefügt werden
    foreach ($cart->get_fees() as $fee_key => $fee) {
        if ($fee->name === $surcharge_name) {
            error_log("DEBUG: Entferne alten Zuschlag.");
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

    // Zuschlag nur hinzufügen, wenn Prozentsatz größer als 0
    if ($surcharge_percentage > 0) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        error_log("DEBUG: Neuer Zuschlag hinzugefügt: " . $surcharge_amount . " EUR");
        $cart->add_fee($surcharge_name, $surcharge_amount, true);
    }

    // Debugging: Alle aktuellen Gebühren loggen
    error_log("DEBUG: Aktuelle Gebühren nach Berechnung: " . print_r($cart->get_fees(), true));
}

// Hook korrekt registrieren, falls noch nicht geschehen
add_action('woocommerce_cart_calculate_fees', 'apply_customer_type_surcharge', 1);

add_action('woocommerce_cart_calculate_fees', function() {
    global $woocommerce;
    error_log("DEBUG: Aktuelle Gebühren im Warenkorb: " . print_r(WC()->cart->get_fees(), true));
}, 10);