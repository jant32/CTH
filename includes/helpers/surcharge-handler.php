<?php
if (!defined('ABSPATH')) {
    exit;
}

// Zuschlag korrekt berechnen und doppelte Berechnungen verhindern
add_action('woocommerce_cart_calculate_fees', function() {
    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $cart = WC()->cart;
    $surcharge_percentage = 0;
    $surcharge_name = 'Kundenart-Zuschlag';

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
            $cart->remove_fee($fee_key);
        }
    }

    // 2️⃣ Neuberechnung & hinzufügen des neuen Zuschlags (aber nur EINMAL pro Berechnung)
    if ($surcharge_percentage > 0 && !WC()->session->get('surcharge_applied')) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        $cart->add_fee($surcharge_name, $surcharge_amount, true);

        // Session-Variable setzen, um Mehrfachberechnung zu verhindern
        WC()->session->set('surcharge_applied', true);
    }

    // 3️⃣ Session-Variable nach Abschluss des Bestellprozesses zurücksetzen
    add_action('woocommerce_cart_updated', function() {
        WC()->session->set('surcharge_applied', false);
    });

}, 10);