<?php
if (!defined('ABSPATH')) {
    exit;
}

// Zuschläge berechnen und doppelte Berechnungen verhindern
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

    // Überprüfung, ob der Zuschlag bereits existiert
    $existing_surcharge = false;
    foreach ($cart->get_fees() as $fee) {
        if ($fee->name === $surcharge_name) {
            $existing_surcharge = true;
            break;
        }
    }

    // Falls der Zuschlag bereits existiert, entfernen
    if ($existing_surcharge) {
        foreach ($cart->get_fees() as $fee_key => $fee) {
            if ($fee->name === $surcharge_name) {
                $cart->remove_fee($fee_key);
            }
        }
    }

    // Neuen Zuschlag berechnen und hinzufügen
    if ($surcharge_percentage > 0) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        $cart->add_fee($surcharge_name, $surcharge_amount, true);
    }
}, 20, 1);