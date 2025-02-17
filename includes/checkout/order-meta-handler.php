<?php
/*
 * order-meta-handler.php
 *
 * Diese Datei aktualisiert die Bestellmetadaten in Zusammenhang mit der Kundenart und Steuerklasse.
 * Neben der Aktualisierung der Post-Meta (welche in admin-order.php verwendet wird) wird hier auch die
 * custom_order_data-Tabelle (über save-customer-type.php) aktualisiert.
 *
 * Funktionen:
 * - cth_update_order_meta(): Wird beim Speichern einer Bestellung aufgerufen, um Kundenart und Steuerklasse zu aktualisieren sowie den Zuschlag neu zu berechnen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_update_order_meta( $order_id, $post, $update ) {
    // Kundenart und Steuerklasse aus den Order-Metadaten abrufen.
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    $tax_class = get_post_meta( $order_id, '_cth_tax_class', true );

    // Aktualisierung der custom_order_data-Tabelle.
    if ( function_exists( 'cth_save_customer_type_to_order' ) ) {
        cth_save_customer_type_to_order( $order_id, $customer_type, $tax_class );
    }

    // Hier kann ggf. weiterer Code ergänzt werden, um den Zuschlag in der Bestellung neu zu berechnen.
}