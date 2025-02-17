<?php
/*
 * order-meta-handler.php
 *
 * Diese Datei aktualisiert die Bestellmetadaten in Zusammenhang mit der Kundenart und Steuerklasse.
 * Neben der Aktualisierung der Order‑Meta (die z. B. im Admin‑Bereich verwendet werden)
 * wird hier auch die Tabelle wp_custom_order_data (über save-customer-type.php) aktualisiert.
 *
 * Beim Abschluss des Checkouts (über den Hook woocommerce_checkout_update_order_meta)
 * oder beim Speichern der Bestellung im Admin‑Bereich (über den Hook save_post_shop_order bzw.
 * woocommerce_process_shop_order_meta) wird die Funktion cth_update_order_meta() aufgerufen, die:
 *  - in der Tabelle wp_custom_order_data einen Eintrag (oder ein Update) vornimmt,
 *  - den Zuschlag (Fee) sowie die Steuer neu berechnet und
 *  - die Tax‑Class der Produkte aktualisiert.
 *
 * Wichtig: Wird der Zuschlag prozentual berechnet, erfolgt die Berechnung auf Basis des Nettopreises.
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
    
    // Speichere in der Tabelle wp_custom_order_data
    if ( function_exists( 'cth_save_customer_type_to_order' ) ) {
        cth_save_customer_type_to_order( $order_id, $customer_type, $tax_class );
    }
    
    // Neuberechnung des Zuschlags und der Steuerwerte
    cth_recalc_order_fees( $order_id );
}
add_action( 'woocommerce_checkout_update_order_meta', 'cth_update_order_meta', 10, 3 );
add_action( 'save_post_shop_order', 'cth_update_order_meta', 20, 3 );
add_action( 'woocommerce_process_shop_order_meta', 'cth_update_order_meta', 99, 3 );

/**
 * Berechnet den Produkt-Subtotal (Summe aller Line Items) für die Bestellung auf Nettobasis.
 *
 * Verwendet wird hier get_subtotal(), da dieser Wert den Nettopreis (ohne Steuern) liefert.
 *
 * @param WC_Order $order
 * @return float
 */
function cth_get_order_product_subtotal( $order ) {
    $subtotal = 0;
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        $subtotal += floatval( $item->get_subtotal() );
    }
    return $subtotal;
}

/**
 * Aktualisiert die Zuschlag-Fee (und die Steuer) sowie die Tax‑Class der Produkte in der Bestellung.
 *
 * Ablauf:
 * 1. Ermittlung des Produkt-Subtotals (Brutto in diesem Fall, aber wir leiten daraus den Nettobetrag ab).
 * 2. Ermittlung des Steuersatzes anhand der gewählten Tax‑Class.
 * 3. Berechnung des Nettopreises: net = brutto / (1 + tax_rate_decimal)
 * 4. Berechnung des Zuschlags: Bei prozentualer Berechnung wird surcharge = net * (surcharge_percent/100) berechnet.
 * 5. Entfernen vorhandener Zuschlags-Fee-Items (die entweder den Marker "[CTH]" oder den surcharge_name enthalten).
 * 6. Hinzufügen eines neuen Fee-Items mit dem neu berechneten Zuschlag.
 * 7. Aktualisierung der Tax‑Class aller Produktzeilen.
 * 8. Neuberechnung der Bestellwerte inklusive Steuern.
 *
 * @param int $order_id
 */
function cth_recalc_order_fees( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    // Berechne den Produkt-Subtotal (Brutto-Summe aller Line Items vom Typ 'line_item')
    $product_subtotal_brutto = 0;
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        // get_total() liefert den Bruttobetrag (inklusive Steuern, falls Preise inkl. Steuer angegeben sind)
        $product_subtotal_brutto += floatval( $item->get_total() );
    }
    
    // Alternativ, falls Preise als Nettopreise gespeichert sind, könntest Du get_subtotal() verwenden:
    // $product_subtotal_net = cth_get_order_product_subtotal( $order );
    // In diesem Beispiel gehen wir davon aus, dass get_total() den Bruttobetrag liefert.
    
    // Um den Nettowert zu erhalten, benötigen wir den Steuersatz
    global $wpdb;
    $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    if ( empty( $customer_type ) ) {
        return;
    }
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $customer_type ) ) );
    if ( ! $option ) {
        return;
    }
    
    // Erhalte den Steuersatz (z. B. 7%) als Dezimalzahl aus WooCommerce
    $tax_rates = WC_Tax::get_rates( $option->tax_class );
    $first_rate = ! empty( $tax_rates ) ? reset( $tax_rates ) : false;
    $tax_rate_decimal = $first_rate ? floatval( $first_rate['tax_rate'] ) / 100 : 0;
    
    // Berechne den Nettopreis: net = brutto / (1 + tax_rate_decimal)
    if ( $tax_rate_decimal > 0 ) {
        $product_subtotal_net = $product_subtotal_brutto / ( 1 + $tax_rate_decimal );
    } else {
        $product_subtotal_net = $product_subtotal_brutto;
    }
    
    // Berechne den neuen Zuschlag
    if ( $option->surcharge_type === 'percentage' ) {
        $new_surcharge = ( $product_subtotal_net * floatval( $option->surcharge_value ) ) / 100;
    } else {
        $new_surcharge = floatval( $option->surcharge_value );
    }
    
    // Definiere den neuen Fee-Namen mit Marker [CTH]
    if ( $option->surcharge_type === 'percentage' ) {
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Entferne vorhandene Zuschlags-Fee-Items, die den Marker "[CTH]" oder den surcharge_name enthalten
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
    
    // Aktualisiere die Tax‑Class aller Produkt-Positionen in der Bestellung
    foreach ( $order->get_items( 'line_item' ) as $item ) {
        $item->set_tax_class( $option->tax_class );
        $item->save();
    }
    
    // Neuberechnung der Bestellwerte inklusive der aktualisierten Fees und Steuern
    $order->calculate_totals( true );
    $order->save();
}