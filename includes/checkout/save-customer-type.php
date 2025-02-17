<?php
/*
 * save-customer-type.php
 *
 * Diese Datei ist verantwortlich für das Speichern bzw. Aktualisieren der vom Kunden gewählten Kundenart und Steuerklasse in der Datenbanktabelle wp_custom_order_data.
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
    $surcharge_table = $wpdb->prefix . 'custom_tax_surcharge_handler';

    // Da $customer_type hier die ID der ausgewählten Option ist, holen wir den entsprechenden Datensatz.
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT surcharge_name, tax_class FROM $surcharge_table WHERE id = %d", intval( $customer_type ) ) );
    
    if ( $option ) {
        // Verwende den in der Option hinterlegten surcharge_name und tax_class.
        $customer_type_value = sanitize_text_field( $option->surcharge_name );
        $tax_class_value = sanitize_text_field( $option->tax_class );
    } else {
        // Falls kein Datensatz gefunden wird, verwende die übergebenen Werte als Fallback.
        $customer_type_value = sanitize_text_field( $customer_type );
        $tax_class_value = sanitize_text_field( $tax_class );
    }

    // Prüfen, ob bereits ein Eintrag für diese Bestellung existiert.
    $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE order_id = %d", $order_id ) );
    if ( $existing ) {
        // Vorhandenen Datensatz aktualisieren.
        $wpdb->update(
            $table_name,
            array(
                'customer_type' => $customer_type_value,
                'tax_class'     => $tax_class_value,
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
                'customer_type' => $customer_type_value,
                'tax_class'     => $tax_class_value,
            ),
            array( '%d', '%s', '%s' )
        );
    }
}