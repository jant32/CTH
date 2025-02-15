<?php
if (!defined('ABSPATH')) {
    exit;
}

// Kundenart speichern
add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    if (!empty($_POST['customer_type'])) {
        update_post_meta($order_id, '_customer_type', sanitize_text_field($_POST['customer_type']));
    }
});