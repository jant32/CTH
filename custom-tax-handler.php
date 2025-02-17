<?php
/**
 * Plugin Name: Custom Tax and Surcharge Handler by PixelTeich
 * Plugin URI: https://pixelteich.de
 * Description: Passt die Mehrwertsteuer und Zuschläge basierend auf der Kundenart und Steuerklasse an.
 * Version: 5.2.12
 * 
 * Author: Jan Teichmann
 * Author URI: https://pixelteich.de
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'CTH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Einbinden der benötigten Dateien aus der neuen Ordnerstruktur.
require_once CTH_PLUGIN_DIR . 'includes/checkout/db-init.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/session-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/backend/admin-menu.php';
require_once CTH_PLUGIN_DIR . 'includes/backend/admin-order.php';
require_once CTH_PLUGIN_DIR . 'includes/backend/tax-surcharge-settings.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/helpers.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/ajax-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/order-meta-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/save-customer-type.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/surcharge-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/tax-class-handler.php';
require_once CTH_PLUGIN_DIR . 'includes/checkout/tax-handler.php';

// Plugin-Aktivierung: Initialisiert die benötigten Datenbanktabellen.
register_activation_hook( __FILE__, 'cth_init_db_tables' );

// Haken, um den Zuschlag beim Neuberechnen des Warenkorbs anzuwenden.
add_action( 'woocommerce_cart_calculate_fees', 'cth_apply_customer_surcharge' );

// Haken, um die Order-Metadaten (Kundenart und Steuerklasse) zu aktualisieren, wenn eine Bestellung gespeichert wird.
add_action( 'save_post_shop_order', 'cth_update_order_meta', 20, 3 );

// Enqueue Admin-Skripte und Styles.
function cth_enqueue_admin_assets( $hook ) {
    if ( strpos( $hook, 'cth_tax_surcharge_settings' ) !== false || strpos( $hook, 'shop_order' ) !== false ) {
        wp_enqueue_style( 'cth-admin-css', plugins_url( 'assets/css/admin.css', __FILE__ ) );
        wp_enqueue_script( 'cth-admin-js', plugins_url( 'assets/js/cth-admin.js', __FILE__ ), array( 'jquery' ), '1.0', true );
    }
}
add_action( 'admin_enqueue_scripts', 'cth_enqueue_admin_assets' );

// Enqueue Frontend-Skripte (z. B. für AJAX in der Kasse).
function cth_enqueue_frontend_assets() {
    if ( is_checkout() ) {
        wp_enqueue_script( 'cth-ajax-js', plugins_url( 'assets/js/cth-admin.js', __FILE__ ), array( 'jquery' ), '1.0', true );
        wp_localize_script( 'cth-ajax-js', 'cth_ajax_obj', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'cth_ajax_nonce' ),
        ) );
    }
}
add_action( 'wp_enqueue_scripts', 'cth_enqueue_frontend_assets' );

// Frontend: Anzeige der Kundenart-Radio-Buttons als Teil der Kundeninformationen
// (Wir verwenden den Hook 'woocommerce_before_checkout_billing_form', sodass die Auswahl über den Kundeninformationen – z. B. über dem Namensfeld – erscheint).
add_action( 'woocommerce_before_checkout_billing_form', 'cth_display_checkout_customer_type_options', 10 );

// Danke-Seite: Darstellung der ausgewählten Kundenart.
add_action( 'woocommerce_thankyou', 'cth_display_customer_type_thank_you', 20 );