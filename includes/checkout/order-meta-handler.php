<?php
/*
 * order-meta-handler.php
 *
 * Diese Datei aktualisiert die Bestellmetadaten in Zusammenhang mit der Kundenart und Steuerklasse.
 * Sie wird sowohl beim Checkout (über den Hook woocommerce_checkout_update_order_meta)
 * als auch im Admin-Bereich (über den Hook save_post_shop_order bzw. woocommerce_process_shop_order_meta)
 * aufgerufen.
 *
 * Folgendes geschieht:
 * 1. Es wird der im Formular (oder in der Order Meta) übermittelte Kundenart-Wert (_cth_customer_type) verwendet.
 * 2. Über die Funktion cth_save_customer_type_to_order() wird dieser Wert (als ID) in wp_custom_order_data gespeichert.
 * 3. Anschließend wird die gesamte Bestellberechnung neu durchgeführt:
 *    - Alle existierenden Fee-Items (Zuschläge), deren Name den Marker "[CTH]" oder den alten surcharge_name enthält, werden entfernt.
 *    - Für alle Produktzeilen wird die Tax‑Class auf den neuen Wert gesetzt und deren Steuer-Daten gelöscht.
 *    - Der neue Zuschlag wird basierend auf dem Nettopreis der Produkte (berechnet aus get_subtotal()) und dem in der Option definierten Zuschlagssatz berechnet.
 *    - Ein neues Fee-Item mit der entsprechenden Tax‑Class wird hinzugefügt.
 *    - Zum Schluss werden die Bestellwerte (Gesamt, Steuer) neu kalkuliert und gespeichert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Aktualisiert die Order-Meta-Daten und führt eine Neuberechnung der Zuschläge und Steuern durch.
 *
 * @param int   $order_id
 * @param mixed $post (optional)
 * @param bool  $update (optional)
 */
function cth_update_order_meta( $order_id, $post = false, $update = false ) {
    if ( ! $order_id ) {
        return;
    }
    
    // Lese den Kundenart-Wert aus den Order-Meta-Daten
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    $tax_class = get_post_meta( $order_id, '_cth_tax_class', true ); // (falls vorhanden)
    
    // Im Frontend (nicht im Admin) können Fallbacks aus der Session genutzt werden – im Admin nicht.
    if ( ! is_admin() ) {
        if ( empty( $customer_type ) && WC()->session && WC()->session->get('cth_customer_type') ) {
            $customer_type = WC()->session->get('cth_customer_type');
        }
        if ( empty( $tax_class ) && ! empty( $customer_type ) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
            $option = $wpdb->get_row( $wpdb->prepare( "SELECT tax_class FROM $table WHERE id = %d", intval( $customer_type ) ) );
            if ( $option && ! empty( $option->tax_class ) ) {
                $tax_class = $option->tax_class;
            }
        }
    }
    
    // Speichere in der Tabelle wp_custom_order_data (nur der Kundenart, als ID)
    if ( function_exists( 'cth_save_customer_type_to_order' ) ) {
        cth_save_customer_type_to_order( $order_id, $customer_type );
    }
    
    // Neuberechnung der Zuschläge und Steuern
    cth_recalc_order_fees( $order_id );
}
add_action( 'woocommerce_checkout_update_order_meta', 'cth_update_order_meta', 10, 3 );
add_action( 'save_post_shop_order', 'cth_update_order_meta', 20, 3 );
add_action( 'woocommerce_process_shop_order_meta', 'cth_update_order_meta', 99, 3 );

/**
 * Berechnet den Nettopreis für alle Produkte in der Bestellung.
 *
 * Wir nutzen get_subtotal(), da dies den Nettopreis (ohne Steuern) liefert.
 *
 * @param WC_Order $order
 * @return float
 */
function cth_get_order_product_net_total( $order ) {
    $net_total = 0;
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        $net_total += floatval( $item->get_subtotal() );
    }
    return $net_total;
}

/**
 * Aktualisiert die Zuschlag-Fee und die Tax-Class der Produkte in der Bestellung.
 *
 * Der Zuschlag wird auf Basis des Nettopreises berechnet.
 *
 * @param int $order_id
 */
function cth_recalc_order_fees( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    // Ermittele den Nettopreis der Produkte
    $product_net_total = cth_get_order_product_net_total( $order );
    
    // Lese den aktuell gewählten Kundenart-Wert (als Option-ID) aus der Order Meta
    $customer_type = intval( get_post_meta( $order_id, '_cth_customer_type', true ) );
    if ( empty( $customer_type ) ) {
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $customer_type ) );
    if ( ! $option ) {
        return;
    }
    
    // Ermittle den Steuersatz für die gewählte Tax-Class
    $tax_rates = WC_Tax::get_rates( $option->tax_class );
    $first_rate = ! empty( $tax_rates ) ? reset( $tax_rates ) : false;
    $tax_rate_decimal = $first_rate ? floatval( $first_rate['tax_rate'] ) / 100 : 0;
    
    // Berechne den neuen Zuschlag basierend auf der Zuschlagsart:
    if ( $option->surcharge_type === 'percentage' ) {
        $new_surcharge = ( $product_net_total * floatval( $option->surcharge_value ) ) / 100;
    } else {
        $new_surcharge = floatval( $option->surcharge_value );
    }
    
    // Definiere den neuen Fee-Namen mit Marker "[CTH]"
    if ( $option->surcharge_type === 'percentage' ) {
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Entferne vorhandene Zuschlags-Fee-Items, deren Name entweder den Marker "[CTH]" oder den alten surcharge_name enthält
    foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {
        $fee_name = $item->get_name();
        if ( strpos( $fee_name, '[CTH]' ) !== false || strpos( $fee_name, $option->surcharge_name ) !== false ) {
            $order->remove_item( $item_id );
        }
    }
    
    // Füge ein neues Fee-Item hinzu, falls ein Zuschlag berechnet wurde
    if ( $new_surcharge > 0 ) {
        $fee = new WC_Order_Item_Fee();
        $fee->set_name( $fee_label );
        $fee->set_total( $new_surcharge );
        // Setze die Tax-Class, damit WooCommerce den Zuschlag mit dem richtigen Steuersatz behandelt
        $fee->set_tax_class( $option->tax_class );
        $fee->set_tax_status( 'taxable' );
        $order->add_item( $fee );
    }
    
    // Aktualisiere die Tax-Class aller Produktzeilen in der Bestellung
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        $item->set_tax_class( $option->tax_class );
        // Leere vorhandene Steuer-Daten, damit sie neu berechnet werden
        $item->set_taxes( array() );
        $item->save();
    }
    
    // Neuberechnung der Bestellwerte inklusive der aktualisierten Fees und Steuern
    $order->calculate_totals( true );
    $order->save();
}