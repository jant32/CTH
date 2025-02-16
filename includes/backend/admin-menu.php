<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('admin_menu', 'cth_add_admin_menu');
function cth_add_admin_menu() {
    // Füge einen Untermenüpunkt unter dem Hauptmenü deines Plugins hinzu
    add_submenu_page(
        'custom-tax-handler', // Hauptmenü-Slug (anpassen, falls nötig)
        'Steuereinstellungen', // Seiten-Titel
        'Steuereinstellungen', // Menü-Titel
        'manage_options',      // Berechtigung
        'cth-tax-surcharge-settings', // Menü-Slug
        'cth_tax_surcharge_settings_page' // Callback-Funktion
    );
}

function cth_tax_surcharge_settings_page() {
    require_once CTH_PLUGIN_DIR . 'includes/backend/tax-surcharge-settings.php';
    cth_render_tax_surcharge_settings_page();
}