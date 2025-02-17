<?php
/*
 * order-meta-handler.php
 *
 * Diese Datei aktualisiert die Bestellmetadaten in Zusammenhang mit der Kundenart und Steuerklasse.
 * Neben der Aktualisierung der Order‑Meta (die z. B. im Admin‑Bereich verwendet werden)
 * wird hier auch die Tabelle wp_custom_order_data (über save-customer-type.php) aktualisiert.
 *
 * Beim Abschluss des Checkouts (über den Hook woocommerce_checkout_update_order_meta)
 * oder beim Speichern der Bestellung im Admin‑Bereich (über den Hook save_post_shop_order)
 * wird die Funktion cth_update_order_meta() aufgerufen, die in der Tabelle wp_custom_order_data
 * einen Eintrag (oder ein Update) vornimmt:
 * - order_id: Die ID der Bestellung (Verbindung zu wp_wc_orders)
 * - customer_type: Der in der Tabelle wp_custom_tax_surcharge_handler hinterlegte Wert aus der Spalte surcharge_name
 * - tax_class: Der Tax-Class-Slug, der in wp_woocommerce_tax_rates verwendet wird
 * - created_at: Wird per Datenbankdefault automatisch gesetzt
 *
 * Außerdem wird der Zuschlag (Fee) neu berechnet, sodass bei einer Änderung der Kundenart die
 * Zuschlags- und Steuerwerte aktualisiert werden.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_update_order_meta( $order_id, $post = false, $update = false ) {
    if ( ! $order_id ) {
        return;
    }
    
    // Lese Bestellmeta-Daten
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    $tax_class = get_post_meta( $order_id, '_cth_tax_class', true );
    
    // Nur im Frontend (nicht im Admin) als Fallback die Session nutzen:
    if ( ! is_admin() ) {
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
 * (Hier folgen die Funktionen cth_recalc_order_fees() und cth_get_order_product_subtotal() sowie alle dazugehörigen Funktionen,
 * wie in der vorherigen Version implementiert.)
 *
 * Diese Funktionen berechnen den Produkt-Subtotal, den neuen Zuschlag und aktualisieren die Bestell-Fees.
 */

function cth_get_order_product_subtotal( $order ) {
    $subtotal = 0;
    foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
        $subtotal += floatval( $item->get_total() );
    }
    return $subtotal;
}

function cth_recalc_order_fees( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    // Berechne den Produkt-Subtotal (alle Line Items vom Typ 'line_item')
    $product_subtotal = cth_get_order_product_subtotal( $order );
    
    // Bestimme die gewählte Kundenart; verwende Order-Meta (ohne Fallback, da Admin-Bereich)
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
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
        $fee->set_tax_class( $option->tax_class );
        $fee->set_tax_status( 'taxable' );
        $order->add_item( $fee );
    }
    
    // Neuberechnung der Bestellwerte inklusive der neuen Fee und Steuer
    $order->calculate_totals( true );
    $order->save();
}