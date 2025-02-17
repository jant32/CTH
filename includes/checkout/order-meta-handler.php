<?php
/*
 * order-meta-handler.php
 *
 * Diese Datei aktualisiert die Bestellmetadaten in Zusammenhang mit der Kundenart und Steuerklasse.
 * Neben der Aktualisierung der Order-Meta (die z. B. im Admin-Bereich verwendet werden)
 * wird hier auch die Tabelle wp_custom_order_data (über save-customer-type.php) aktualisiert.
 *
 * Beim Abschluss des Checkouts (über den Hook woocommerce_checkout_update_order_meta)
 * wird die Funktion cth_update_order_meta() aufgerufen, die in der Tabelle wp_custom_order_data
 * einen Eintrag (oder ein Update) vornimmt:
 * - order_id: Die ID der Bestellung
 * - customer_type: Der in der Tabelle wp_custom_tax_surcharge_handler hinterlegte Wert aus der Spalte surcharge_name
 * - tax_class: Der Tax-Class-Slug, der in wp_woocommerce_tax_rates verwendet wird
 * - created_at: Wird per Datenbankdefault automatisch gesetzt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_update_order_meta( $order_id, $post, $update ) {
    // Kundenart und Steuerklasse aus den Bestellmetadaten abfragen.
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    $tax_class = get_post_meta( $order_id, '_cth_tax_class', true );
    
    // Falls diese Werte noch nicht gesetzt sind, nutze den Session-Wert als Fallback.
    if ( empty( $customer_type ) && isset( $_SESSION['cth_customer_type'] ) ) {
        $customer_type = $_SESSION['cth_customer_type'];
    }
    if ( empty( $tax_class ) && isset( $_SESSION['cth_customer_type'] ) ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT tax_class FROM $table_name WHERE id = %d", intval( $_SESSION['cth_customer_type'] ) ) );
        if ( $option ) {
            $tax_class = $option->tax_class;
        }
    }
    
    if ( function_exists( 'cth_save_customer_type_to_order' ) ) {
        cth_save_customer_type_to_order( $order_id, $customer_type, $tax_class );
    }
}
add_action( 'woocommerce_checkout_update_order_meta', 'cth_update_order_meta', 10, 3 );