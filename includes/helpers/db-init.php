<?php
// Sicherstellen, dass WordPress-Umgebung existiert
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'custom_order_data';

// Prüfen, ob die Tabelle bereits existiert
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

if (!$table_exists) {
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

    // Überprüfen, ob die Tabelle nun existiert
    $table_check = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

    if ($table_check === $table_name) {
        error_log("DEBUG: Datenbanktabelle {$table_name} erfolgreich erstellt.");
    } else {
        error_log("ERROR: Datenbanktabelle {$table_name} konnte nicht erstellt werden.");
    }
} else {
    error_log("DEBUG: Datenbanktabelle {$table_name} existiert bereits.");
}

global $wpdb;
$table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';

// Prüfen, ob die Tabelle bereits existiert
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

if (!$table_exists) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        order_id mediumint(9) NOT NULL,
        surcharge_name varchar(255) NOT NULL,
        software_name varchar(255) NOT NULL,
        surcharge_type varchar(50) NOT NULL,
        surcharge_value float NOT NULL,
        tax_class varchar(100) NOT NULL,
        PRIMARY KEY  (id),
        KEY order_id (order_id)
      ) $charset_collate;";

    // Überprüfen, ob die Tabelle nun existiert
    $table_check = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

    if ($table_check === $table_name) {
        error_log("DEBUG: Datenbanktabelle {$table_name} erfolgreich erstellt.");
    } else {
        error_log("ERROR: Datenbanktabelle {$table_name} konnte nicht erstellt werden.");
    }
} else {
    error_log("DEBUG: Datenbanktabelle {$table_name} existiert bereits.");
}