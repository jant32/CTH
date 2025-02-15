<?php
// Kundenart in eigene Datenbank speichern (bei Bestellabschluss & Admin-Änderung)
function cth_save_customer_type($order_id) {
    if (!$order_id) return;

    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_order_data';

    // Kundenart aus WooCommerce-Metadaten abrufen
    $customer_type = get_post_meta($order_id, '_customer_type', true);
    if (!$customer_type) {
        return;
    }

    // Steuerklasse basierend auf Kundenart bestimmen
    $tax_class = in_array($customer_type, ['verein_ssb', 'verein_non_ssb']) ? 'reduced-rate' : 'standard-rate';

    // Prüfen, ob die Bestellung bereits existiert, dann updaten oder einfügen
    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE order_id = %d", $order_id));

    if ($existing) {
        $wpdb->update(
            $table_name,
            ['customer_type' => $customer_type, 'tax_class' => $tax_class],
            ['order_id' => $order_id],
            ['%s', '%s'],
            ['%d']
        );
    } else {
        $wpdb->insert(
            $table_name,
            ['order_id' => $order_id, 'customer_type' => $customer_type, 'tax_class' => $tax_class],
            ['%d', '%s', '%s']
        );
    }
}

// Hooks für Checkout & Admin-Speicherung
add_action('woocommerce_checkout_update_order_meta', 'cth_save_customer_type');
add_action('woocommerce_process_shop_order_meta', 'cth_save_customer_type');