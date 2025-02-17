<?php
/*
 * order-meta-handler.php
 *
 * Aktualisiert beim Speichern der Bestellung im Adminbereich die Order-Metadaten:
 *  - Speichert die gewählte Kundenart und Steuerklasse.
 *  - Berechnet den entsprechenden Zuschlag neu und passt die Steuerklasse der Bestellpositionen an.
 *
 * Die Neuberechnung wird über die Funktion cth_recalculate_order_surcharge() realisiert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_process_shop_order_meta', 'cth_update_order_meta', 10, 2 );
function cth_update_order_meta( $order_id, $post ) {
    if ( isset( $_POST['cth_customer_type'] ) ) {
        $customer_type = sanitize_text_field( $_POST['cth_customer_type'] );
        update_post_meta( $order_id, '_cth_customer_type', $customer_type );
    }
    if ( isset( $_POST['cth_tax_class'] ) ) {
        $tax_class = sanitize_text_field( $_POST['cth_tax_class'] );
        update_post_meta( $order_id, '_cth_tax_class', $tax_class );
    }
    // Neuberechnung des Zuschlags und Aktualisierung der Steuerklassen in den Bestellpositionen
    cth_recalculate_order_surcharge( $order_id );
}

function cth_recalculate_order_surcharge( $order_id ) {
    $order         = wc_get_order( $order_id );
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    $customer_types = cth_get_all_customer_types();
    $selected      = null;
    if ( $customer_types ) {
        foreach ( $customer_types as $type ) {
            if ( $type->surcharge_name == $customer_type ) {
                $selected = $type;
                break;
            }
        }
    }
    if ( $selected ) {
        // Zuschlag auf Basis des Bestellwertes berechnen
        $order_total = $order->get_total();
        if ( $selected->surcharge_type == 'percent' ) {
            $surcharge = ( $order_total * $selected->surcharge_value / 100 );
        } else {
            $surcharge = $selected->surcharge_value;
        }
        update_post_meta( $order_id, '_cth_surcharge', $surcharge );
        // Aktualisieren der Steuerklasse in den Bestellpositionen
        foreach ( $order->get_items() as $item_id => $item ) {
            wc_update_order_item_meta( $item_id, '_cth_tax_class', $selected->tax_class );
        }
    }
}