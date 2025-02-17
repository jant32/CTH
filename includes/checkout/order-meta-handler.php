<?php
/*
 * order-meta-handler.php
 *
 * Diese Datei aktualisiert die Bestellmetadaten in Zusammenhang mit der Kundenart und Steuerklasse.
 * Neben der Aktualisierung der Order‑Meta (die z. B. im Admin‑Bereich verwendet werden)
 * wird hier auch die Tabelle wp_custom_order_data (über save-customer-type.php) aktualisiert.
 *
 * Zusätzlich wird beim Speichern der Bestellung (sowohl im Frontend als auch im Admin‑Backend)
 * die Zuschlagsberechnung neu durchgeführt – basierend auf der gewählten Kundenart.
 * Dabei wird die gleiche Berechnungslogik wie im Frontend angewendet:
 *   - Berechnung des Produkt‑Subtotals aus den Line Items,
 *   - Neuberechnung des Zuschlags (prozentual oder fester Betrag) und
 *   - Aktualisierung der Fee‑Order Items sowie der Gesamt‑ und Steuerwerte der Bestellung.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Aktualisiert die Bestellmetadaten in Zusammenhang mit der Kundenart.
 * Ruft danach die Funktion zum Neuberechnen des Zuschlags auf.
 *
 * @param int   $order_id
 * @param mixed $post
 * @param bool  $update
 */
function cth_update_order_meta( $order_id, $post = false, $update = false ) {
    if ( ! $order_id ) {
        return;
    }
    
    // Lese Bestellmeta-Daten
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    $tax_class = get_post_meta( $order_id, '_cth_tax_class', true );
    
    // Falls nicht gesetzt, Fallback auf den Session-Wert (sofern vorhanden)
    if ( empty( $customer_type ) && isset( $_SESSION['cth_customer_type'] ) ) {
        $customer_type = $_SESSION['cth_customer_type'];
    }
    if ( empty( $tax_class ) && isset( $_SESSION['cth_customer_type'] ) ) {
        global $wpdb;
        $surcharge_table = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT tax_class FROM $surcharge_table WHERE id = %d", intval( $_SESSION['cth_customer_type'] ) ) );
        if ( $option && ! empty( $option->tax_class ) ) {
            $tax_class = $option->tax_class;
        }
    }
    
    // Speichere in der Tabelle wp_custom_order_data (über die Funktion in save-customer-type.php)
    if ( function_exists( 'cth_save_customer_type_to_order' ) ) {
        cth_save_customer_type_to_order( $order_id, $customer_type, $tax_class );
    }
    
    // Rechne den Zuschlag (Fee) und die Steuer neu
    cth_recalc_order_fees( $order_id );
}
add_action( 'woocommerce_checkout_update_order_meta', 'cth_update_order_meta', 10, 3 );
add_action( 'save_post_shop_order', 'cth_update_order_meta', 20, 3 );

/**
 * Berechnet den Produkt-Subtotal für die Bestellung.
 *
 * @param WC_Order $order
 * @return float
 */
function cth_get_order_product_subtotal( $order ) {
    $subtotal = 0;
    foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
        // Hier verwenden wir den Gesamtwert des Items (exkl. Versand, Gebühren etc.)
        $subtotal += floatval( $item->get_total() );
    }
    return $subtotal;
}

/**
 * Rechnet den Zuschlag (Fee) für die Bestellung neu und aktualisiert bzw. fügt den entsprechenden Fee-Order Item ein.
 *
 * @param int $order_id
 */
function cth_recalc_order_fees( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    // Berechne den Produkt-Subtotal (alle Line Items vom Typ 'line_item')
    $product_subtotal = cth_get_order_product_subtotal( $order );
    
    // Bestimme die gewählte Kundenart; verwende Order-Meta oder Session als Fallback
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    if ( empty( $customer_type ) && isset( $_SESSION['cth_customer_type'] ) ) {
        $customer_type = $_SESSION['cth_customer_type'];
    }
    if ( empty( $customer_type ) ) {
        return;
    }
    
    global $wpdb;
    $surcharge_table = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $surcharge_table WHERE id = %d", intval( $customer_type ) ) );
    if ( ! $option ) {
        return;
    }
    
    // Berechne den neuen Zuschlag
    $new_surcharge = 0;
    if ( $option->surcharge_type === 'percentage' ) {
        $new_surcharge = ( $product_subtotal * floatval( $option->surcharge_value ) ) / 100;
    } else {
        $new_surcharge = floatval( $option->surcharge_value );
    }
    
    // Bestimme den neuen Fee-Name (Marker [CTH] hilft, unser Fee-Item zu identifizieren)
    if ( $option->surcharge_type === 'percentage' ) {
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Entferne vorhandene Fee-Items, die unseren Marker enthalten
    foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {
        if ( strpos( $item->get_name(), '[CTH]' ) !== false ) {
            $order->remove_item( $item_id );
        }
    }
    
    // Füge ein neues Fee-Item hinzu, wenn ein Zuschlag vorhanden ist
    if ( $new_surcharge > 0 ) {
        $fee = new WC_Order_Item_Fee();
        $fee->set_name( $fee_label );
        $fee->set_total( $new_surcharge );
        // Setze die Tax-Class, damit bei der Neuberechnung die Steuer angewendet wird
        $fee->set_tax_class( $option->tax_class );
        // WooCommerce kann die Steuer für das Fee-Item berechnen, wenn wir es als steuerpflichtig markieren:
        $fee->set_tax_status( 'taxable' );
        $order->add_item( $fee );
    }
    
    // Neuberechnung der Bestellwerte inklusive der neuen Fee und Steuer
    $order->calculate_totals( true );
    $order->save();
}