<?php
/*
 * order-meta-handler.php
 *
 * Diese Datei aktualisiert die Bestellmetadaten in Zusammenhang mit der Kundenart.
 * Sie wird sowohl beim Checkout (über woocommerce_checkout_update_order_meta)
 * als auch im Admin-Bereich (über save_post_shop_order bzw. woocommerce_process_shop_order_meta) aufgerufen.
 *
 * Der Ablauf:
 * 1. Der im Checkout übermittelte Kundenart-Wert (als ID) wird ausgelesen und in die Order Meta (_cth_customer_type) gespeichert.
 * 2. Über cth_save_customer_type_to_order() wird dieser Wert in der Tabelle wp_custom_order_data gespeichert.
 * 3. Anschließend wird cth_recalc_order_fees() aufgerufen, die:
 *    - Alle vorhandenen Zuschlags-Fee-Items entfernt, deren Name den alten Custom Tag (bzw. den alten surcharge_name) enthält,
 *    - Den neuen Zuschlag basierend auf dem Nettopreis der Produkte berechnet,
 *    - Ein neues Fee-Item mit dem Custom Tag und den korrekten Zuschlags- und Steuerwerten hinzufügt,
 *    - Die Tax-Class aller Produkte aktualisiert und die Bestellwerte neu kalkuliert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_update_order_meta( $order_id, $post = false, $update = false ) {
    if ( ! $order_id ) {
        return;
    }
    
    // Lese den Kundenart-Wert aus den Order-Meta-Daten
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    // Speichere in wp_custom_order_data (über cth_save_customer_type_to_order)
    if ( function_exists( 'cth_save_customer_type_to_order' ) ) {
        cth_save_customer_type_to_order( $order_id, $customer_type );
    }
    
    // Neuberechnung des Zuschlags und der Steuern
    cth_recalc_order_fees( $order_id );
}
add_action( 'woocommerce_checkout_update_order_meta', 'cth_update_order_meta', 10, 1 );
add_action( 'save_post_shop_order', 'cth_update_order_meta', 20, 1 );
add_action( 'woocommerce_process_shop_order_meta', 'cth_update_order_meta', 99, 1 );

/**
 * Berechnet den Nettopreis der Produkte in der Bestellung.
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
 * Aktualisiert die Zuschlags-Fee und die Tax-Class der Produkte in der Bestellung.
 *
 * Der neue Custom Tag wird aus der Option 'cth_custom_fee_tag' geladen, und falls nicht gesetzt, wird 'CTH' als Default verwendet.
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
    
    // Lese den Kundenart-Wert (als Option-ID) aus der Order Meta
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
    
    // Lade den Custom Fee Tag aus den Optionen, falls vorhanden (max. 8 Zeichen); ansonsten Default 'CTH'
    $custom_tag = get_option( 'cth_custom_fee_tag', 'CTH' );
    // Der Custom Tag wird in eckigen Klammern dargestellt:
    $custom_tag = '[' . $custom_tag . ']';
    
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
    
    // Erstelle den neuen Fee-Namen unter Verwendung des Custom Fee Tags
    if ( $option->surcharge_type === 'percentage' ) {
        $fee_label = $custom_tag . ' ' . $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $fee_label = $custom_tag . ' ' . $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Entferne alle existierenden Fee-Items, deren Name den Custom Tag (bzw. den alten Standardtag) oder den surcharge_name enthält.
    foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {
        $fee_name = $item->get_name();
        // Prüfe, ob der Name entweder den aktuellen Custom Tag (mit eckigen Klammern) oder den alten Standard ("[CTH]") oder den surcharge_name enthält.
        if ( strpos( $fee_name, $custom_tag ) !== false || strpos( $fee_name, '[CTH]' ) !== false || strpos( $fee_name, $option->surcharge_name ) !== false ) {
            $order->remove_item( $item_id );
        }
    }
    
    // Füge ein neues Fee-Item hinzu, falls ein Zuschlag berechnet wurde.
    if ( $new_surcharge > 0 ) {
        $fee = new WC_Order_Item_Fee();
        $fee->set_name( $fee_label );
        $fee->set_total( $new_surcharge );
        $fee->set_tax_class( $option->tax_class );
        $fee->set_tax_status( 'taxable' );
        $order->add_item( $fee );
    }
    
    // Aktualisiere die Tax-Class aller Produktzeilen in der Bestellung
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        $item->set_tax_class( $option->tax_class );
        // Leere vorhandene Steuern, damit diese neu berechnet werden
        $item->set_taxes( array() );
        $item->save();
    }
    
    // Neuberechnung der Bestellwerte inklusive der aktualisierten Fees und Steuern
    $order->calculate_totals( true );
    $order->save();
}