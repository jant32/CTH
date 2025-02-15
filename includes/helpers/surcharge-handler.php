<?php
if (!defined('ABSPATH')) {
    exit;
}

// Debugging-Log aktivieren
error_log("DEBUG: surcharge-handler.php geladen");

// Verhindern, dass die Funktion mehrfach registriert wird
if (!has_action('woocommerce_cart_calculate_fees', 'apply_customer_type_surcharge')) {
    add_action('woocommerce_cart_calculate_fees', 'apply_customer_type_surcharge', 10);
}

// Funktion zur Zuschlagsberechnung
function apply_customer_type_surcharge() {
    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $cart = WC()->cart;
    $surcharge_name = 'Kundenart-Zuschlag';
    $surcharge_percentage = 0;

    error_log("DEBUG: Zuschlagsberechnung gestartet für Kundenart: " . $customer_type);

    // 1️⃣ Prüfen, ob die Funktion mehrfach läuft
    static $called = false;
    if ($called) {
        error_log("DEBUG: Zuschlag bereits berechnet, breche ab.");
        return;
    }
    $called = true;

    // 2️⃣ Bestimmen des Zuschlagsatzes
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

    // 3️⃣ Entfernen alter Zuschläge, um doppelte Berechnungen zu vermeiden
    foreach ($cart->get_fees() as $fee_key => $fee) {
        if ($fee->name === $surcharge_name) {
            error_log("DEBUG: Entferne alten Zuschlag!");
            $cart->remove_fee($fee_key);
        }
    }

    // 4️⃣ Neuen Zuschlag hinzufügen
    if ($surcharge_percentage > 0) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        error_log("DEBUG: Zuschlag hinzugefügt: " . $surcharge_amount . " EUR");
        $cart->add_fee($surcharge_name, $surcharge_amount, true);
    }

    // 5️⃣ Session-Variable zurücksetzen, wenn der Warenkorb aktualisiert wird
    add_action('woocommerce_cart_updated', function() {
        error_log("DEBUG: Session variable surcharge_applied zurückgesetzt.");
        WC()->session->set('surcharge_applied', false);
    });
}