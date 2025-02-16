<?php
if (!defined('ABSPATH')) {
    exit;
}

// Steuerklasse setzen
add_filter('woocommerce_product_get_tax_class', function($tax_class, $product) {
    if (!$product || !WC()->session) {
        return $tax_class;
    }

    $customer_type = WC()->session->get('customer_type', 'verein_ssb');

    switch ($customer_type) {
        case 'verein_ssb':
        case 'verein_non_ssb':
            return 'reduced-rate'; // 7% Steuer
        case 'privatperson':
        case 'kommerziell':
            return 'standard-rate'; // 19% Steuer
    }

    return $tax_class;
}, 10, 2);