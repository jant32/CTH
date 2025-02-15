<?php
if (!defined('ABSPATH')) {
    exit;
}

// Zuschläge berechnen
add_action('woocommerce_cart_calculate_fees', function() {
    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $cart = WC()->cart;
    $surcharge = 0;

    // Zunächst alle bestehenden Zuschläge entfernen, damit es nicht zu Mehrfachberechnungen kommt
    foreach ($cart->get_fees() as $key => $fee) {
        if ($fee->name === 'Kundenart-Zuschlag') {
            $cart->remove_fee($key);
        }
    }

    // Zuschlag basierend auf der Kundenart berechnen
    switch ($customer_type) {
        case 'verein_non_ssb':
            $surcharge = 0.05;
            break;
        case 'privatperson':
            $surcharge = 0.10;
            break;
        case 'kommerziell':
            $surcharge = 0.15;
            break;
    }

    // Zuschlag hinzufügen, wenn er größer als 0 ist
    if ($surcharge > 0) {
        $cart->add_fee('Kundenart-Zuschlag', $cart->cart_contents_total * $surcharge, true);
    }
});