<?php
/**
 * Plugin Name: Custom Tax Handler by PixelTeich
 * Plugin URI: https://pixelteich.de
 * Description: Passt die Mehrwertsteuer und Zuschläge basierend auf der Kundenart an.
 * Version: 3.0.0
 * Author: Jan Teichmann
 * Author URI: https://pixelteich.de
 */

if (!defined('ABSPATH')) {
    exit; // Sicherheit: Direktes Aufrufen der Datei verhindern
}

//JS aufrufen
add_action('admin_enqueue_scripts', 'cth_enqueue_admin_scripts');
function cth_enqueue_admin_scripts($hook) {
    if ('post.php' != $hook && 'post-new.php' != $hook) {
        return;
    }
    wp_enqueue_script('cth-admin-js', CTH_PLUGIN_URL.'assets/js/admin.js', array('jquery'), '3.0.0', true );
}

// Plugin-Pfade definieren
define('CTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CTH_PLUGIN_URL', plugin_dir_url(__FILE__));

// Modul-Dateien einbinden
require_once CTH_PLUGIN_DIR . 'includes/backend/admin-menu.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/session-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/checkout-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/order-meta-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/tax-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/surcharge-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/display-customer-type-checkout.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/db-init.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/save-customer-type.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/helpers.php';
require_once CTH_PLUGIN_DIR . 'includes/backend/admin-order.php';
require_once CTH_PLUGIN_DIR . 'includes/helpers/ajax-handler.php';