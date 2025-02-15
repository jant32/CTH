<?php
if (!defined('ABSPATH')) {
    exit;
}

// Zuschläge berechnen
add_action('woocommerce_cart_calculate_fees', function() {
    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $cart = WC()->cart;
    $surcharge = 0;

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

    if ($surcharge > 0) {
        $cart->add_fee('Kundenart-Zuschlag', $cart->cart_contents_total * $surcharge, true);
    }
});