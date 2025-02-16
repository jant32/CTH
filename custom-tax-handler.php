<?php
/**
 * Plugin Name: Custom Tax Handler by PixelTeich
 * Plugin URI: https://pixelteich.de
 * Description: Passt die Mehrwertsteuer und Zuschläge basierend auf der Kundenart und Steuerklasse an.
 * Version: 4.0.0
 * Author: Jan Teichmann
 * Author URI: https://pixelteich.de
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sicherheit: Direktes Aufrufen der Datei verhindern
}

// Plugin-Pfade definieren – dies sollte vor der Verwendung in Enqueues und require_once!
define('CTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CTH_PLUGIN_URL', plugin_dir_url(__FILE__));

/** Enqueue Scripts & Styles im Admin-Bereich **/
add_action('admin_enqueue_scripts', function($hook) {
    // Laden nur auf Seiten, wo Bestellungen bearbeitet werden (post.php, post-new.php)
    if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
        wp_enqueue_script('cth-admin-js', CTH_PLUGIN_URL . 'assets/js/cth-admin.js', ['jquery'], '3.0.0', true );
        wp_localize_script('cth-admin-js', 'cth_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);
    }
    // Admin CSS auf allen Admin-Seiten laden
    wp_enqueue_style('cth-admin-css', CTH_PLUGIN_URL . 'assets/css/admin.css');
});

/** Modul-Dateien einbinden **/
require_once CTH_PLUGIN_DIR . 'includes/backend/admin-menu.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/session-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/checkout-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/order-meta-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/tax-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/surcharge-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/display-customer-type-checkout.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/tax-class-handler.php';  // NEU: Steuerklasse-Dropdown
require_once CTH_PLUGIN_DIR . 'includes/helpers/db-init.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/save-customer-type.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/helpers.php';
require_once CTH_PLUGIN_DIR . 'includes/backend/admin-order.php';
require_once CTH_PLUGIN_DIR . 'includes/backend/tax-surcharge-settings.php';

// Alte AJAX-Dateien löschen – diese werden nicht mehr benötigt:
// require_once CTH_PLUGIN_DIR . 'includes/helpers/ajax-handler.php';
// require_once CTH_PLUGIN_DIR . 'assets/js/admin-surcharge.js';