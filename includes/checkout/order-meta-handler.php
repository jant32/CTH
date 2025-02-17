<?php
/*
 * order-meta-handler.php
 *
 * Diese Datei aktualisiert die Bestellmetadaten in Zusammenhang mit der Kundenart und Steuerklasse.
 * Sie wird sowohl beim Checkout (über den Hook woocommerce_checkout_update_order_meta)
 * als auch im Admin-Backend (über den Hook save_post_shop_order bzw. woocommerce_process_shop_order_meta)
 * aufgerufen.
 *
 * Es werden Order‑Meta-Werte (_cth_customer_type und _cth_tax_class) gesetzt – 
 * falls diese im Frontend noch nicht vorhanden sind, wird der Wert aus WC()->session genutzt.
 * Anschließend wird über die Funktion cth_save_customer_type_to_order() auch in der Tabelle
 * wp_custom_order_data der Eintrag (oder ein Update) vorgenommen.
 * Danach wird der Zuschlag (Fee) sowie die Steuer neu berechnet und – falls nötig – die Tax‑Class der Produkte angepasst.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_update_order_meta( $order_id, $post = false, $update = false ) {
    if ( ! $order_id ) {
        return;
    }
    
    // Lese Order-Meta-Werte
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    $tax_class = get_post_meta( $order_id, '_cth_tax_class', true );
    
    // Im Frontend: Falls noch nicht gesetzt, nutze den Wert aus der WooCommerce-Session.
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
    
    // Speichere in Order-Meta (dieser Wert fließt auch in die Tabelle wp_custom_order_data ein)
    // Wir nehmen an, dass die Funktion cth_save_customer_type_to_order() bereits korrekt implementiert ist.
    if ( function_exists( 'cth_save_customer_type_to_order' ) ) {
        cth_save_customer_type_to_order( $order_id, $customer_type, $tax_class );
    }
    
    // Neuberechnung des Zuschlags (Fee) und Aktualisierung der Tax‑Class der Produkte
    cth_recalc_order_fees( $order_id );
}
add_action( 'woocommerce_checkout_update_order_meta', 'cth_update_order_meta', 10, 3 );
add_action( 'save_post_shop_order', 'cth_update_order_meta', 20, 3 );
add_action( 'woocommerce_process_shop_order_meta', 'cth_update_order_meta', 99, 3 );

/**
 * Berechnet den Produkt-Subtotal (Summe aller Line Items) für die Bestellung.
 *
 * @param WC_Order $order
 * @return float
 */
function cth_get_order_product_subtotal( $order ) {
    $subtotal = 0;
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        $subtotal += floatval( $item->get_total() );
    }
    return $subtotal;
}

/**
 * Aktualisiert die Zuschlag-Fee (und die Steuer) sowie die Tax‑Class der Produkte in der Bestellung.
 *
 * @param int $order_id
 */
function cth_recalc_order_fees( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    // Berechne den Produkt-Subtotal
    $product_subtotal = cth_get_order_product_subtotal( $order );
    
    // Lese den aktuell gewählten Kundenart-Wert (als Option-ID) aus den Order-Meta
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    if ( empty( $customer_type ) ) {
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $customer_type ) ) );
    if ( ! $option ) {
        return;
    }
    
    // Aktualisiere die Tax‑Class aller Produkt-Positionen in der Bestellung
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        $item->set_tax_class( $option->tax_class );
        $item->save();
    }
    
    // Berechne den neuen Zuschlag
    $new_surcharge = 0;
    if ( $option->surcharge_type === 'percentage' ) {
        $new_surcharge = ( $product_subtotal * floatval( $option->surcharge_value ) ) / 100;
    } else {
        $new_surcharge = floatval( $option->surcharge_value );
    }
    
    // Definiere den neuen Fee-Namen mit Marker [CTH]
    if ( $option->surcharge_type === 'percentage' ) {
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Entferne vorhandene Zuschlags-Fee-Items, deren Name entweder den Marker "[CTH]" oder den surcharge_name enthält
    foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {
        $fee_name = $item->get_name();
        if ( strpos( $fee_name, '[CTH]' ) !== false || strpos( $fee_name, $option->surcharge_name ) !== false ) {
            $order->remove_item( $item_id );
        }
    }
    
    // Füge den neuen Zuschlag als Fee-Item hinzu, falls ein Zuschlag berechnet wurde
    if ( $new_surcharge > 0 ) {
        $fee = new WC_Order_Item_Fee();
        $fee->set_name( $fee_label );
        $fee->set_total( $new_surcharge );
        $fee->set_tax_class( $option->tax_class );
        $fee->set_tax_status( 'taxable' );
        $order->add_item( $fee );
    }
    
    // Neuberechnung der Bestellwerte inklusive der aktualisierten Fees und Steuern
    $order->calculate_totals( true );
    $order->save();
}