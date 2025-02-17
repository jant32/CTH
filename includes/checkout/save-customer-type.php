<?php
/*
 * save-customer-type.php
 *
 * Diese Datei ist verantwortlich für das Speichern bzw. Aktualisieren der vom Kunden gewählten
 * Kundenart und Steuerklasse in der Datenbanktabelle wp_custom_order_data.
 *
 * Funktionen:
 * - cth_save_customer_type_to_order(): Speichert oder aktualisiert die Kundenart und Steuerklasse zu einer Bestellung.
 *   Hier werden in wp_custom_order_data statt des Namens die IDs gespeichert:
 *   - customer_type: Die ID aus wp_custom_tax_surcharge_handler
 *   - tax_class: Der tax_rate_id aus wp_woocommerce_tax_rates (für die entsprechende tax_rate_class)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_save_customer_type_to_order( $order_id, $customer_type, $tax_class ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_order_data';

    // Wir erwarten, dass $customer_type hier die Option-ID (Integer) ist.
    $customer_type = intval( $customer_type );
    
    // Für tax_class: Ausgehend vom Tax-Class-Slug, holen wir die tax_rate_id aus wp_woocommerce_tax_rates.
    $tax_rate_id = '';
    if ( ! empty( $tax_class ) ) {
        $tax_table = $wpdb->prefix . 'woocommerce_tax_rates';
        $tax_rate_id = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate_id FROM $tax_table WHERE tax_rate_class = %s LIMIT 1", $tax_class ) );
    }
    // Wenn nichts gefunden, bleibt tax_rate_id leer.
    
    // Prüfe, ob bereits ein Eintrag für diese Bestellung existiert.
    $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE order_id = %d", $order_id ) );
    if ( $existing ) {
        $wpdb->update(
            $table_name,
            array(
                'customer_type' => $customer_type, // Speichere die Kundenart-ID
                'tax_class'     => $tax_rate_id,    // Speichere den Tax Rate ID
            ),
            array( 'order_id' => $order_id ),
            array( '%d', '%s' ),
            array( '%d' )
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'order_id'      => $order_id,
                'customer_type' => $customer_type,
                'tax_class'     => $tax_rate_id,
            ),
            array( '%d', '%d', '%s' )
        );
    }
}