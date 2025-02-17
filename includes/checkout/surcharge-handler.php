<?php
/*
 * surcharge-handler.php
 *
 * Diese Datei berechnet und wendet den Kundenart-Zuschlag auf den Warenkorb an.
 * Hier wird der Zuschlag als Fee hinzugefügt, und mittels Filter wird auch die Tax‑Class
 * der Produkte im Warenkorb überschrieben.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_apply_customer_surcharge( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    $customer_type = isset($_SESSION['cth_customer_type']) ? $_SESSION['cth_customer_type'] : (WC()->session ? WC()->session->get('cth_customer_type') : '');
    if ( empty( $customer_type ) ) {
        return;
    }
    global $wpdb;
    $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $customer_type ) ) );
    if ( ! $option ) {
        return;
    }
    $cart_total = $cart->subtotal;
    $surcharge = 0;
    if ( $option->surcharge_type === 'percentage' ) {
        $surcharge = ( $cart_total * floatval( $option->surcharge_value ) ) / 100;
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $surcharge = floatval( $option->surcharge_value );
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Entferne vorhandene Zuschlags-Fee-Items
    foreach ( $cart->get_fees() as $key => $fee ) {
        $fee_name = $fee->name;
        if ( strpos( $fee_name, '[CTH]' ) !== false || strpos( $fee_name, $option->surcharge_name ) !== false ) {
            unset( $cart->fees[ $key ] );
        }
    }
    
    if ( $surcharge > 0 ) {
        $cart->add_fee( $fee_label, $surcharge, true, $option->tax_class );
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'cth_apply_customer_surcharge' );

// Filter, um die Tax‑Class der Produkte im Warenkorb basierend auf der Kundenart zu überschreiben
add_filter( 'woocommerce_product_get_tax_class', 'cth_override_product_tax_class', 10, 2 );
function cth_override_product_tax_class( $tax_class, $product ) {
    if ( ! $product || ! WC()->session ) {
        return $tax_class;
    }
    $customer_type = isset($_SESSION['cth_customer_type']) ? $_SESSION['cth_customer_type'] : WC()->session->get('cth_customer_type');
    if ( $customer_type ) {
        global $wpdb;
        $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT tax_class FROM $table WHERE id = %d", intval( $customer_type ) ) );
        if ( $option && ! empty( $option->tax_class ) ) {
            return $option->tax_class;
        }
    }
    return $tax_class;
}