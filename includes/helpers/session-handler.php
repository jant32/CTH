<?php
if (!defined('ABSPATH')) {
    exit;
}

// Standard-Kundenart in die Session setzen
add_action('woocommerce_init', function() {
    if (WC()->session) {
        WC()->session->set('customer_type', WC()->session->get('customer_type', 'verein_ssb'));
    }
});