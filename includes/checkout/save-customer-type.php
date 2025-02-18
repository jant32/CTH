<?php
/*
 * save-customer-type.php
 *
 * Diese Datei speichert bzw. aktualisiert die vom Kunden gewählte Kundenart
 * in der Datenbanktabelle wp_custom_order_data.
 *
 * Es werden folgende Spalten befüllt:
 * - order_id: die ID der Bestellung (wie in wp_wc_orders.id)
 * - customer_type: die ID der Kundenart (wie in wp_custom_tax_surcharge_handler.id)
 * - created_at: wird per Datenbankdefault automatisch gesetzt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_save_customer_type_to_order( $order_id, $customer_type ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_order_data';
    
    // Stelle sicher, dass $customer_type als Integer vorliegt.
    $customer_type = intval( $customer_type );
    
    // Prüfe, ob bereits ein Eintrag für diese Bestellung existiert.
    $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE order_id = %d", $order_id ) );
    if ( $existing ) {
        $wpdb->update(
            $table_name,
            array(
                'customer_type' => $customer_type,
            ),
            array( 'order_id' => $order_id ),
            array( '%d' ),
            array( '%d' )
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'order_id'      => $order_id,
                'customer_type' => $customer_type,
            ),
            array( '%d', '%d' )
        );
    }
}