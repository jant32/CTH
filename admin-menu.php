<?php
/*
 * admin-menu.php
 *
 * Fügt im WordPress-Backend ein Menü für die Einstellungen des Custom Tax Surcharge Handlers hinzu.
 * Die Seite ermöglicht die Verwaltung der Kundenarten, Zuschläge und Steuerklassen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'cth_add_admin_menu' );
function cth_add_admin_menu() {
    add_menu_page(
        __( 'Tax Surcharge Settings', 'cth' ), // Page title
        __( 'Tax Surcharge', 'cth' ),          // Menu title
        'manage_options',                      // Capability
        'cth-tax-surcharge-settings',          // Menu slug
        'cth_render_tax_surcharge_settings',   // Callback-Funktion
        'dashicons-admin-generic',             // Icon
        56                                     // Position
    );
}

function cth_render_tax_surcharge_settings() {
    include plugin_dir_path( __FILE__ ) . 'tax-surcharge-settings.php';
}