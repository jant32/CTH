<?php
/*
 * admin-menu.php
 *
 * Registriert das Admin-Menü für das Plugin.
 *
 * Funktionen:
 * - cth_register_admin_menu(): Fügt im WordPress-Backend einen Menüpunkt "Tax Surcharge" hinzu, der auf die Einstellungen-Seite verweist.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_register_admin_menu() {
    add_menu_page(
        'Tax & Surcharge Settings',
        'Tax Surcharge',
        'manage_options',
        'cth_tax_surcharge_settings',
        'cth_tax_surcharge_settings_page',
        'dashicons-admin-generic',
        56
    );
}
add_action( 'admin_menu', 'cth_register_admin_menu' );