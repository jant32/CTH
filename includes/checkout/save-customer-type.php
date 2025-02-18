<?php
/*
 * save-customer-type.php
 *
 * Diese Datei speichert bzw. aktualisiert die vom Kunden gewählte Kundenart und Steuerklasse
 * in der Datenbanktabelle wp_custom_order_data.
 *
 * Es werden folgende Spalten befüllt:
 * - order_id: die ID der Bestellung (wie in wp_wc_orders.id)
 * - customer_type: die ID der Kundenart (wie in wp_custom_tax_surcharge_handler.id)
 * - tax_class: die ID der Steuerklasse (wie in wp_woocommerce_tax_rates.tax_rate_id)
 * - created_at: wird per Datenbankdefault automatisch gesetzt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_save_customer_type_to_order( $order_id, $customer_type, $tax_class ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_order_data';

    // Stelle sicher, dass customer_type als Integer vorliegt.
    $customer_type = intval( $customer_type );
    
    // Für tax_class: Wir erwarten hier einen Tax-Class-Slug (z.B. "reduced-rate").
    // Wir suchen nun in der Tabelle wp_woocommerce_tax_rates den entsprechenden tax_rate_id.
    $tax_rate_id = '';
    if ( ! empty( $tax_class ) ) {
        $tax_table = $wpdb->prefix . 'woocommerce_tax_rates';
        $tax_rate_id = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate_id FROM $tax_table WHERE tax_rate_class = %s LIMIT 1", $tax_class ) );
        // Falls nichts gefunden wird, bleibt $tax_rate_id leer.
    }
    
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