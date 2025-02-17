<?php
/*
 * db-init.php
 *
 * Erstellt bei der Plugin-Aktivierung die benötigte Datenbanktabelle (wp_custom_tax_surcharge_handler).
 * In dieser Tabelle werden die Kundenarten, Zuschlagsarten, Zuschlagshöhen und Steuerklassen gespeichert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_create_db_tables() {
    global $wpdb;
    $table_name    = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        surcharge_name varchar(255) NOT NULL,
        surcharge_type varchar(50) NOT NULL,
        surcharge_value decimal(24,2) NOT NULL,
        tax_class varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}