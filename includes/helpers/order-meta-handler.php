<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Aktualisiert den Kundenart- und Steuerklasse-Wert in der Custom-Tabelle (und als Order-Meta)
 * und berechnet den Kundenart-Zuschlag neu sowie passt die Steuerklasse der Bestellpositionen an.
 *
 * Diese Funktion wird aufgerufen, wenn im Admin-Bereich die Bestellung gespeichert wird.
 *
 * @param int    $order_id Die Bestell-ID.
 * @param object $post     Das Post-Objekt der Bestellung.
 */
add_action( 'woocommerce_process_shop_order_meta', 'cth_update_order_customer_type_and_tax_class', 20, 2 );
function cth_update_order_customer_type_and_tax_class( $order_id, $post ) {
    if ( isset( $_POST['customer_type'] ) ) {
        $customer_type = sanitize_text_field( $_POST['customer_type'] );
        $tax_class = isset( $_POST['tax_class'] ) ? sanitize_text_field( $_POST['tax_class'] ) : 'standard';
        
        // Aktualisiere den Wert in der Custom-Tabelle
        global $wpdb;
        $table = $wpdb->prefix . 'custom_order_data';
        $wpdb->replace(
            $table,
            [
                'order_id'      => $order_id,
                'customer_type' => $customer_type,
                'tax_class'     => $tax_class,
            ],
            [
                '%d',
                '%s',
                '%s',
            ]
        );
        error_log( "DEBUG: Kundenart für Bestellung $order_id auf $customer_type gesetzt." );
        
        // Speichere die Werte auch als Order-Meta
        update_post_meta( $order_id, '_customer_type', $customer_type );
        update_post_meta( $order_id, '_tax_class', $tax_class );
        
        // Hole die Bestellung
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            error_log( "DEBUG: Bestellung $order_id nicht gefunden." );
            return;
        }
        
        // Entferne vorhandene Fee-Items mit dem Namen "Kundenart-Zuschlag"
        foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {
            if ( $item->get_name() === 'Kundenart-Zuschlag' ) {
                $order->remove_item( $item_id );
            }
        }
        
        // Bestimme den Zuschlagsprozentsatz basierend auf der Kundenart
        $surcharge_percentage = 0;
        switch ( $customer_type ) {
            case 'verein_non_ssb':
                $surcharge_percentage = 0.05;
                break;
            case 'privatperson':
                $surcharge_percentage = 0.10;
                break;
            case 'kommerziell':
                $surcharge_percentage = 0.15;
                break;
            default:
                $surcharge_percentage = 0;
                break;
        }
        
        if ( $surcharge_percentage > 0 ) {
            // Hier verwenden wir das Subtotal der Bestellung (ohne Steuern, Rabatte etc.)
            $order_subtotal = $order->get_subtotal();
            $surcharge_amount = $order_subtotal * $surcharge_percentage;
            if ( $surcharge_amount > 0 ) {
                $fee = new WC_Order_Item_Fee();
                $fee->set_name( 'Kundenart-Zuschlag' );
                $fee->set_total( $surcharge_amount );
                $fee->set_tax_status( 'taxable' ); // Falls Steuern berechnet werden sollen
                if ( method_exists( $fee, 'set_tax_class' ) ) {
                    $fee->set_tax_class( $tax_class );
                }
                $order->add_item( $fee );
                error_log( "DEBUG: Zuschlag für Bestellung $order_id neu hinzugefügt: " . $surcharge_amount . " EUR" );
            }
        }
        
        // Aktualisiere die Steuerklasse für alle Bestellpositionen (Produkte und Fees)
        foreach ( $order->get_items() as $item_id => $item ) {
            if ( method_exists( $item, 'set_tax_class' ) ) {
                $item->set_tax_class( $tax_class );
                $item->save();
            }
        }
        
        // Bestellsumme neu berechnen und Bestellung speichern
        $order->calculate_totals( true );
        $order->save();
        error_log( "DEBUG: Bestellung $order_id neu berechnet und gespeichert." );
    }
}