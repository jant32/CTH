<?php
/*
 * surcharge-handler.php
 *
 * Diese Datei berechnet und wendet den Kundenart-Zuschlag auf den Warenkorb an.
 * Der Zuschlag wird auf Basis des Nettowerts der Produkte berechnet und als steuerpflichtige Fee hinzugefügt.
 * Zusätzlich überschreiben die Filter 'woocommerce_cart_item_tax_class' und 'woocommerce_product_get_tax_class'
 * die Tax‑Class der Produkte im Warenkorb basierend auf der in der Session gespeicherten Kundenart.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_apply_customer_surcharge( $cart ) {
    // Nur im Frontend (oder per AJAX) ausführen.
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    
    // Prüfe, ob die Kundenart in der Session gesetzt ist.
    if ( empty( $_SESSION['cth_customer_type'] ) ) {
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_SESSION['cth_customer_type'] ) ) );
    if ( ! $option ) {
        return;
    }
    
    /*
     * Annahme: $cart->cart_contents_total liefert den Nettobetrag der Produkte.
     * Falls Dein Shop Bruttopreise verwendet, müsstest Du hier den Nettowert berechnen,
     * z. B. durch: $net_total = $cart->cart_contents_total / (1 + $tax_rate_decimal);
     */
    $net_total = $cart->cart_contents_total;
    
    if ( $option->surcharge_type == 'percentage' ) {
        $surcharge_percentage = floatval( $option->surcharge_value ) / 100;
        $surcharge_amount = $net_total * $surcharge_percentage;
        $fee_label = $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $surcharge_amount = floatval( $option->surcharge_value );
        $fee_label = $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Füge den Zuschlag als steuerpflichtige Fee hinzu.
    $cart->add_fee( $fee_label, $surcharge_amount, true, $option->tax_class );
}
add_action( 'woocommerce_cart_calculate_fees', 'cth_apply_customer_surcharge' );


// Überschreibe die Tax‑Class der Produkte im Warenkorb anhand der in der Session gespeicherten Kundenart.
add_filter( 'woocommerce_cart_item_tax_class', 'cth_override_cart_item_tax_class', 10, 3 );
function cth_override_cart_item_tax_class( $tax_class, $cart_item, $cart_item_key ) {
    if ( isset( $_SESSION['cth_customer_type'] ) && ! empty( $_SESSION['cth_customer_type'] ) ) {
        global $wpdb;
        $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT tax_class FROM $table WHERE id = %d", intval( $_SESSION['cth_customer_type'] ) ) );
        if ( $option && ! empty( $option->tax_class ) ) {
            return $option->tax_class;
        }
    }
    return $tax_class;
}

// Fallback-Filter: Überschreibe die Tax‑Class eines Produkts, wenn diese abgefragt wird.
add_filter( 'woocommerce_product_get_tax_class', 'cth_override_product_tax_class', 10, 2 );
function cth_override_product_tax_class( $tax_class, $product ) {
    if ( isset( $_SESSION['cth_customer_type'] ) && ! empty( $_SESSION['cth_customer_type'] ) ) {
        global $wpdb;
        $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT tax_class FROM $table WHERE id = %d", intval( $_SESSION['cth_customer_type'] ) ) );
        if ( $option && ! empty( $option->tax_class ) ) {
            return $option->tax_class;
        }
    }
    return $tax_class;
}