<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'cth_register_admin_menu' );
function cth_register_admin_menu() {
    // Hauptmenü hinzufügen (falls noch nicht vorhanden)
    add_menu_page(
        __( 'Custom Tax Handler', 'custom-tax-handler' ),  // Seiten-Titel
        __( 'Custom Tax Handler', 'custom-tax-handler' ),  // Menü-Titel
        'manage_options',                                   // Berechtigung
        'custom-tax-handler',                               // Menü-Slug
        'cth_main_page',                                    // Callback-Funktion für die Hauptseite
        'dashicons-admin-generic',                          // Icon
        66                                                // Position (optional)
    );
    
    // Untermenü hinzufügen für die Steuereinstellungen
    add_submenu_page(
        'custom-tax-handler',                   // Eltern-Slug (das Hauptmenü)
        __( 'Steuereinstellungen', 'custom-tax-handler' ), // Seiten-Titel
        __( 'Steuereinstellungen', 'custom-tax-handler' ), // Menü-Titel
        'manage_options',                       // Berechtigung
        'cth-tax-surcharge-settings',           // Menü-Slug
        'cth_tax_surcharge_settings_page'       // Callback-Funktion
    );
}

function cth_main_page() {
    echo '<div class="wrap"><h1>' . esc_html__( 'Custom Tax Handler', 'custom-tax-handler' ) . '</h1><p>' . esc_html__( 'Willkommen bei Custom Tax Handler.', 'custom-tax-handler' ) . '</p></div>';
}

function cth_tax_surcharge_settings_page() {
    // Diese Funktion wird in unserer tax-surcharge-settings.php definiert.
    cth_render_tax_surcharge_settings_page();
}