<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

// Bestehende Tabelle: wp_custom_order_data (bereits vorhanden)
$table_name = $wpdb->prefix . 'custom_order_data';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
if ( ! $table_exists ) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT(20) UNSIGNED NOT NULL,
        customer_type VARCHAR(50) NOT NULL,
        tax_class VARCHAR(50) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY order_id (order_id)
    ) $charset_collate;";
    dbDelta($sql);
}

// Neue Tabelle: wp_custom_tax_surcharge_handler
$new_table = $wpdb->prefix . 'custom_tax_surcharge_handler';
$new_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$new_table}'");
if ( ! $new_table_exists ) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();
    $sql_new = "CREATE TABLE {$new_table} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        surcharge_name varchar(255) NOT NULL,
        surcharge_type varchar(50) NOT NULL,
        surcharge_value float NOT NULL,
        tax_class varchar(100) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_new);
}