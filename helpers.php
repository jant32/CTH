<?php
/*
 * helpers.php
 *
 * Enthält Hilfsfunktionen für den Custom Tax Surcharge Handler:
 *  - cth_get_all_customer_types(): Lädt alle Kundenarten aus der Datenbank.
 *  - cth_format_customer_type_display(): Formatiert die Anzeige einer Kundenart (z. B. "Name | 25%" oder "Name | +25,00€").
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_get_all_customer_types() {
    global $wpdb;
    $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
    return $wpdb->get_results( "SELECT * FROM $table" );
}

function cth_format_customer_type_display( $type ) {
    $display = $type->surcharge_name . ' | ' . ( ( $type->surcharge_type == 'percent' ) ? $type->surcharge_value . '%' : '+' . number_format( $type->surcharge_value, 2, ',', '.' ) . '€' );
    return $display;
}