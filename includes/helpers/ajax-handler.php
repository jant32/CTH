<?php
add_action('wp_ajax_update_order_customer_type', function() {
    if (!isset($_POST['order_id'], $_POST['customer_type'])) {
        wp_send_json_error(['message' => 'Ungültige Anfrage']);
        return;
    }

    $order_id = intval($_POST['order_id']);
    $customer_type = sanitize_text_field($_POST['customer_type']);

    // Kundenart in unserer eigenen Datenbank aktualisieren
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_order_data';
    $wpdb->update(
        $table_name,
        ['customer_type' => $customer_type],
        ['order_id' => $order_id],
        ['%s'],
        ['%d']
    );

    // Bestellung abrufen
    $order = wc_get_order($order_id);

    // Zuschlagstabelle
    $surcharge_percentage = [
        'verein_ssb'     => 0.00,
        'verein_non_ssb' => 0.05,
        'privatperson'   => 0.10,
        'kommerziell'    => 0.15,
    ];

    // Neuen Zuschlag berechnen
    $new_surcharge_rate = isset($surcharge_percentage[$customer_type]) ? $surcharge_percentage[$customer_type] : 0;
    $order_total = floatval($order->get_subtotal());
    $surcharge_amount = floatval($order_total * $new_surcharge_rate);

    // Bisherige Gebühren entfernen
    foreach ($order->get_items('fee') as $fee_id => $fee) {
        $order->remove_item($fee_id);
    }

    // Neuen Zuschlag hinzufügen
    if ($surcharge_amount > 0) {
        $fee = new WC_Order_Item_Fee();
        $fee->set_name('Kundenart-Zuschlag');
        $fee->set_amount($surcharge_amount);
        $fee->set_tax_class(''); // Steuerklasse bleibt unverändert
        $fee->set_total($surcharge_amount);
        $order->add_item($fee);
    }

    // Bestellung speichern & Summen neu berechnen
    $order->calculate_totals();
    $order->save();

    wp_send_json_success(['message' => 'Kundenart erfolgreich aktualisiert.']);
});