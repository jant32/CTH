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
 * einen Eintrag (oder ein Update) vornimmt und den Zuschlag (Fee) sowie die Steuer neu berechnet:
 * - Der bestehende Zuschlag (Fee), dessen Name entweder den Marker "[CTH]" oder den in der Options-Tabelle 
 *   hinterlegten surcharge_name enthält, wird entfernt.
 * - Anschließend werden alle Produkt-Positionen (Line Items) mit der neuen Tax‑Class aktualisiert.
 * - Danach wird der neue Zuschlag basierend auf dem Produktsubtotal neu berechnet und hinzugefügt.
 * - Zum Schluss werden die Bestellwerte inklusive Steuern neu kalkuliert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_update_order_meta( $order_id, $post = false, $update = false ) {
    if ( ! $order_id ) {
        return;
    }
    
    // Bestellmetadaten auslesen
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    $tax_class = get_post_meta( $order_id, '_cth_tax_class', true );
    
    // Im Admin-Bereich werden keine Session-Fallbacks genutzt.
    if ( function_exists( 'cth_save_customer_type_to_order' ) ) {
        cth_save_customer_type_to_order( $order_id, $customer_type, $tax_class );
    }
    
    // Neuberechnung des Zuschlags und der Steuerwerte
    cth_recalc_order_fees( $order_id );
}
add_action( 'woocommerce_checkout_update_order_meta', 'cth_update_order_meta', 10, 3 );
add_action( 'save_post_shop_order', 'cth_update_order_meta', 20, 3 );

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
    
    // Berechne den Produkt-Subtotal (Summe aller Line Items vom Typ 'line_item')
    $product_subtotal = cth_get_order_product_subtotal( $order );
    
    // Lese den aktuell gewählten Kundenart-Wert (als Option-ID) aus den Order-Meta-Daten
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
    
    // Aktualisiere die Tax-Class aller Produkt-Positionen in der Bestellung
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        $item->set_tax_class( $option->tax_class );
        // Statt update_item() rufen wir jetzt save() auf, um die Änderung zu persistieren.
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
    
    // Entferne vorhandene Fee-Items, deren Name entweder den Marker "[CTH]" oder den surcharge_name enthält
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
        // Setze die Tax-Class, damit WooCommerce den Zuschlag korrekt versteuert
        $fee->set_tax_class( $option->tax_class );
        $fee->set_tax_status( 'taxable' );
        $order->add_item( $fee );
    }
    
    // Neuberechnung der Bestellwerte inklusive der aktualisierten Fees und Steuerwerte
    $order->calculate_totals( true );
    $order->save();
}