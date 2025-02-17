<?php
/*
 * save-customer-type.php
 *
 * Diese Datei ist verantwortlich für das Speichern bzw. Aktualisieren der vom Kunden gewählten Kundenart und Steuerklasse in der Datenbanktabelle custom_order_data.
 *
 * Funktionen:
 * - cth_save_customer_type_to_order(): Speichert oder aktualisiert die Kundenart und Steuerklasse zu einer Bestellung.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_save_customer_type_to_order( $order_id, $customer_type, $tax_class ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_order_data';

    // Prüfen, ob bereits ein Eintrag für diese Bestellung existiert.
    $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE order_id = %d", $order_id ) );
    if ( $existing ) {
        // Vorhandenen Datensatz aktualisieren.
        $wpdb->update(
            $table_name,
            array(
                'customer_type' => sanitize_text_field( $customer_type ),
                'tax_class'     => sanitize_text_field( $tax_class ),
            ),
            array( 'order_id' => $order_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    } else {
        // Neuen Datensatz einfügen.
        $wpdb->insert(
            $table_name,
            array(
                'order_id'      => $order_id,
                'customer_type' => sanitize_text_field( $customer_type ),
                'tax_class'     => sanitize_text_field( $tax_class ),
            ),
            array( '%d', '%s', '%s' )
        );
    }
}