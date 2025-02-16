<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Aktualisiert den Kundenart-Wert in der Custom-Tabelle (und Order-Meta)
 * und berechnet den Kundenart-Zuschlag neu.
 *
 * Diese Funktion wird aufgerufen, wenn im Admin-Bereich die Bestellung gespeichert wird.
 *
 * @param int    $order_id Die Bestell-ID.
 * @param object $post     Das Post-Objekt der Bestellung.
 */
add_action( 'woocommerce_process_shop_order_meta', 'cth_update_order_customer_type_and_surcharge', 20, 2 );
function cth_update_order_customer_type_and_surcharge( $order_id, $post ) {
    if ( isset( $_POST['customer_type'] ) ) {
        $customer_type = sanitize_text_field( $_POST['customer_type'] );
        
        // Aktualisiere den Wert in der Custom-Tabelle
        global $wpdb;
        $table = $wpdb->prefix . 'custom_order_data';
        $wpdb->replace(
            $table,
            [
                'order_id'      => $order_id,
                'customer_type' => $customer_type,
            ],
            [
                '%d',
                '%s',
            ]
        );
        error_log( "DEBUG: Kundenart für Bestellung $order_id auf $customer_type gesetzt." );
        
        // Speichere den Wert auch als Order-Meta
        update_post_meta( $order_id, '_customer_type', $customer_type );
        
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
        
        // Bestimme den Zuschlagsprozentsatz
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
        
        // Falls ein Zuschlag anfällt, berechne ihn und füge ihn als Fee hinzu
        if ( $surcharge_percentage > 0 ) {
            // Hier verwenden wir das Subtotal der Bestellung (ohne Steuern, Rabatte etc.)
            $order_subtotal = $order->get_subtotal();
            $surcharge_amount = $order_subtotal * $surcharge_percentage;
            if ( $surcharge_amount > 0 ) {
                $fee = new WC_Order_Item_Fee();
                $fee->set_name( 'Kundenart-Zuschlag' );
                $fee->set_total( $surcharge_amount );
                $fee->set_tax_status( 'none' ); // ggf. 'taxable' anpassen
                $order->add_item( $fee );
                error_log( "DEBUG: Zuschlag für Bestellung $order_id neu hinzugefügt: " . $surcharge_amount . " EUR" );
            }
        }
        
        // Bestellsumme neu berechnen und Bestellung speichern
        $order->calculate_totals( true );
        $order->save();
        error_log( "DEBUG: Bestellung $order_id neu berechnet und gespeichert." );
    }
}