<?php
/*
 * tax-handler.php
 *
 * Diese Datei passt die Steuerklasse einer Bestellung anhand der ausgewählten Kundenart an.
 * Statt hart codierter Steuerklassen wird hier der in der Datenbank hinterlegte Wert verwendet.
 *
 * Funktionen:
 * - cth_adjust_order_tax_class(): Liest die Steuerklasse aus wp_custom_tax_surcharge_handler und aktualisiert die Bestellung.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_adjust_order_tax_class( $order_id ) {
    $customer_type_id = get_post_meta( $order_id, '_cth_customer_type', true );
    if ( ! $customer_type_id ) {
        return;
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT tax_class FROM $table_name WHERE id = %d", $customer_type_id ) );
    if ( $option && $option->tax_class ) {
        update_post_meta( $order_id, '_cth_tax_class', sanitize_text_field( $option->tax_class ) );
    }
}
add_action( 'woocommerce_thankyou', 'cth_adjust_order_tax_class', 30 );