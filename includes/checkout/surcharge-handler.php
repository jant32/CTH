<?php
/*
 * surcharge-handler.php
 *
 * Diese Datei berechnet und wendet den Kundenart-Zuschlag auf den Warenkorb an.
 * Der Zuschlag wird auf Basis des Nettowerts der Produkte berechnet und als steuerpflichtige Fee hinzugefügt.
 * Zusätzlich überschreiben die Filter die Tax‑Class der Produkte im Warenkorb basierend auf der in der Session gespeicherten Kundenart.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_apply_customer_surcharge( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    
    if ( empty( $_SESSION['cth_customer_type'] ) ) {
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_SESSION['cth_customer_type'] ) ) );
    if ( ! $option ) {
        return;
    }
    
    // Nehme an, dass $cart->cart_contents_total den Nettobetrag liefert (sonst Umrechnung vornehmen)
    $net_total = $cart->cart_contents_total;
    
    if ( $option->surcharge_type == 'percentage' ) {
        $surcharge_percentage = floatval( $option->surcharge_value ) / 100;
        $surcharge_amount = $net_total * $surcharge_percentage;
        $fee_label = $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $surcharge_amount = floatval( $option->surcharge_value );
        $fee_label = $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Lade den Custom Fee Tag aus den Optionen, Standard ist "CTH"
    $custom_tag = get_option( 'cth_custom_fee_tag', 'CTH' );
    $custom_tag = '[' . $custom_tag . ']';
    
    // Baue den finalen Fee-Namen auf
    if ( $option->surcharge_type == 'percentage' ) {
        $final_fee_label = $custom_tag . ' ' . $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $final_fee_label = $custom_tag . ' ' . $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Entferne vorhandene Fee-Items, die den Custom Tag, den Standardtag oder den surcharge_name enthalten
    foreach ( $cart->get_fees() as $key => $fee ) {
        $fee_name = $fee->name;
        if ( strpos( $fee_name, $custom_tag ) !== false || strpos( $fee_name, '[CTH]' ) !== false || strpos( $fee_name, $option->surcharge_name ) !== false ) {
            unset( $cart->fees[ $key ] );
        }
    }
    
    $cart->add_fee( $final_fee_label, $surcharge_amount, true, $option->tax_class );
}
add_action( 'woocommerce_cart_calculate_fees', 'cth_apply_customer_surcharge' );

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