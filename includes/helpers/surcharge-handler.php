<?php
if (!defined('ABSPATH')) {
    exit;
}

// Zuschläge berechnen und doppelte Berechnungen verhindern
add_action('woocommerce_cart_calculate_fees', function() {
    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $cart = WC()->cart;
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

    // Vorhandene Zuschläge entfernen, um doppelte Einträge zu vermeiden
    foreach ($cart->get_fees() as $fee_key => $fee) {
        if ($fee->name === 'Kundenart-Zuschlag') {
            $cart->remove_fee($fee_key);
        }
    }

    // Neuen Zuschlag berechnen und hinzufügen
    if ($surcharge_percentage > 0) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        $cart->add_fee('Kundenart-Zuschlag', $surcharge_amount, true);
    }
}, 10, 1);