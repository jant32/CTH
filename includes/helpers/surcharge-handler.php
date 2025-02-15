<?php
if (!defined('ABSPATH')) {
    exit;
}

// Debugging-Log aktivieren
error_log("DEBUG: surcharge-handler.php geladen");

// Zuschlag korrekt berechnen und doppelte Berechnungen verhindern
add_action('woocommerce_cart_calculate_fees', function() {
    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $cart = WC()->cart;
    $surcharge_percentage = 0;
    $surcharge_name = 'Kundenart-Zuschlag';

    error_log("DEBUG: Berechnung gestartet für Kundenart: " . $customer_type);

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

    // 1️⃣ Sicherstellen, dass KEIN doppelter Zuschlag existiert – alle entfernen
    foreach ($cart->get_fees() as $fee_key => $fee) {
        if ($fee->name === $surcharge_name) {
            error_log("DEBUG: Entferne alten Zuschlag!");
            $cart->remove_fee($fee_key);
        }
    }

    // 2️⃣ Prüfen, ob bereits eine Berechnung durchgeführt wurde
    if (WC()->session->get('surcharge_applied')) {
        error_log("DEBUG: Zuschlag wurde bereits angewendet – Abbruch!");
        return;
    }

    // 3️⃣ Neuberechnung & hinzufügen des neuen Zuschlags
    if ($surcharge_percentage > 0) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        error_log("DEBUG: Zuschlag hinzugefügt: " . $surcharge_amount . " EUR");
        $cart->add_fee($surcharge_name, $surcharge_amount, true);

        // Session-Variable setzen, um Mehrfachberechnung zu verhindern
        WC()->session->set('surcharge_applied', true);
    }

    // 4️⃣ Session-Variable nach Abschluss des Bestellprozesses zurücksetzen
    add_action('woocommerce_cart_updated', function() {
        error_log("DEBUG: Session variable surcharge_applied zurückgesetzt.");
        WC()->session->set('surcharge_applied', false);
    });

}, 10);