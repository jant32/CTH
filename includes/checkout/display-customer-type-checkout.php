<?php
// Kundenart auf der Bestätigungsseite (Danke-Seite) aus eigener Tabelle anzeigen
add_action('woocommerce_thankyou', function($order_id) {
    if (!$order_id) return;

    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_order_data';

    // Kundenart abrufen
    $customer_type = $wpdb->get_var($wpdb->prepare(
        "SELECT customer_type FROM $table_name WHERE order_id = %d",
        $order_id
    ));

    if (!$customer_type) {
        $customer_type = 'Unbekannt';
    }

    echo '<p><strong>Kundenart:</strong> ' . esc_html(get_customer_type_label($customer_type)) . '</p>';
});