<?php
/*
 * session-handler.php
 *
 * Diese Datei initialisiert die PHP-Session für das Plugin und setzt, falls noch nicht vorhanden, eine Standard-Kundenart in der Session.
 *
 * Funktionen:
 * - cth_start_session(): Startet die Session, falls noch nicht aktiv.
 * - cth_set_default_customer_type(): Legt die Standard-Kundenart (erstes Element aus der Datenbank) in der Session fest.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_start_session() {
    if ( ! session_id() ) {
        session_start();
    }
}
add_action( 'init', 'cth_start_session', 1 );

function cth_set_default_customer_type() {
    if ( ! isset( $_SESSION['cth_customer_type'] ) ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $default_option = $wpdb->get_row( "SELECT id FROM $table_name ORDER BY id ASC LIMIT 1" );
        if ( $default_option ) {
            $_SESSION['cth_customer_type'] = $default_option->id;
        }
    }
}
add_action( 'init', 'cth_set_default_customer_type', 2 );