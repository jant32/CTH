<?php
/*
Plugin Name: Custom Tax Surcharge Handler by PixelTeich
Description: Fügt WooCommerce einen dynamischen Zuschlag basierend auf der Kundenart hinzu und passt den Steuersatz entsprechend an. 
             Die Kundenarten, Zuschlagshöhen und zugehörigen Steuerklassen werden in der Datenbank (wp_custom_tax_surcharge_handler) hinterlegt.
Version: 4.0.2
Author: Jan Teichmann
Author URI: https://pixelteich.de

Dateien und ihre Funktionen:
- db-init.php: Initialisiert die benötigte Datenbanktabelle beim Aktivieren des Plugins.
- helpers.php: Stellt Hilfsfunktionen bereit (z. B. Laden aller Kundenarten und Formatierung der Anzeige).
- session-handler.php: Startet die Session und setzt eine Standard-Kundenart, wenn keine gewählt wurde.
- ajax-handler.php: Behandelt Ajax-Anfragen (z. B. zum Speichern der Kundenart vom Checkout).
- save-customer-type.php: (Integration der Ajax-Funktion erfolgt in ajax-handler.php.)
- checkout-handler.php: Fügt auf der Kassenseite Radio-Buttons hinzu, die dynamisch aus der DB geladen werden.
- display-customer-type-checkout.php: Zeigt auf der Dankeseite die gewählte Kundenart an.
- surcharge-handler.php: Berechnet und wendet den Zuschlag im Warenkorb an.
- tax-handler.php: Setzt die Steuerklasse anhand der gewählten Kundenart.
- tax-class-handler.php: Erzeugt das Dropdown zur Auswahl der Steuerklasse im Adminbereich.
- admin-menu.php: Fügt im Adminbereich das Menü für die Einstellungen hinzu.
- admin-order.php: Zeigt im Bestell-Backend Dropdowns zur Auswahl der Kundenart und Steuerklasse an.
- tax-surcharge-settings.php: Erlaubt im Backend die Verwaltung der Kundenarten, Zuschläge und Steuerklassen.
- order-meta-handler.php: Aktualisiert bei Änderungen im Backend die Bestellmetadaten und berechnet neu.

Sämtliche Funktionen zur Berechnung, Anzeige und Speicherung der Kundenart und Zuschläge bleiben erhalten – lediglich die zuvor hart codierten Werte werden nun dynamisch aus der Datenbank geholt.
*/

// Verhindere direkten Aufruf
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Einbinden aller erforderlichen Dateien
require_once plugin_dir_path( __FILE__ ) . 'db-init.php';
require_once plugin_dir_path( __FILE__ ) . 'helpers.php';
require_once plugin_dir_path( __FILE__ ) . 'session-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'ajax-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'save-customer-type.php';
require_once plugin_dir_path( __FILE__ ) . 'checkout-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'display-customer-type-checkout.php';
require_once plugin_dir_path( __FILE__ ) . 'surcharge-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'tax-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'tax-class-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'admin-menu.php';
require_once plugin_dir_path( __FILE__ ) . 'admin-order.php';
require_once plugin_dir_path( __FILE__ ) . 'tax-surcharge-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'order-meta-handler.php';

// Aktivierungshook: Erstellen der DB-Tabelle
register_activation_hook( __FILE__, 'cth_create_db_tables' );