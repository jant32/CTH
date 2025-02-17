<?php
/*
 * db-init.php
 *
 * Diese Datei initialisiert die für das Plugin benötigten Datenbanktabellen:
 * - wp_custom_tax_surcharge_handler: Speichert die Kundenart, den Zuschlagstyp (prozentual/fest), die Zuschlagshöhe und die zugehörige Steuerklasse.
 * - wp_custom_order_data: Speichert für jede Bestellung die zugewiesene Kundenart und Steuerklasse.
 *
 * Funktionen:
 * - cth_init_db_tables(): Erstellt bzw. aktualisiert die notwendigen Datenbanktabellen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_init_db_tables() {
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $charset_collate = $wpdb->get_charset_collate();

    $table1 = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $sql1 = "CREATE TABLE $table1 (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        surcharge_name VARCHAR(50) NOT NULL,
        surcharge_type VARCHAR(20) NOT NULL,
        surcharge_value DECIMAL(26,2) NOT NULL,
        tax_class VARCHAR(50) DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $table2 = $wpdb->prefix . 'custom_order_data';
    $sql2 = "CREATE TABLE $table2 (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT(20) UNSIGNED NOT NULL,
        customer_type VARCHAR(50) NOT NULL,
        tax_class VARCHAR(50) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY order_id (order_id)
    ) $charset_collate;";

    dbDelta( $sql1 );
    dbDelta( $sql2 );
}