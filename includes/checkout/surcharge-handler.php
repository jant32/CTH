<?php
/*
 * surcharge-handler.php
 *
 * Diese Datei berechnet und wendet den Kundenart-Zuschlag auf den Warenkorb an.
 * Der Zuschlag wird auf Basis des Nettowerts der Produkte berechnet – das heißt,
 * es wird angenommen, dass $cart->cart_contents_total den Nettobetrag liefert.
 *
 * Anschließend wird der Zuschlag als steuerpflichtige Fee hinzugefügt, sodass WooCommerce
 * die Steuer für Produkte und Zuschlag separat berechnet (was in der Summe dem Steuerbetrag
 * auf den Gesamtwert entspricht).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_apply_customer_surcharge( $cart ) {
    // Nur im Frontend (oder per AJAX) ausführen
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    
    // Prüfe, ob die Kundenart in der Session gesetzt ist
    if ( empty( $_SESSION['cth_customer_type'] ) ) {
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $_SESSION['cth_customer_type'] ) ) );
    if ( ! $option ) {
        return;
    }
    
    // Nehme an, dass cart_contents_total den Nettowert der Produkte liefert
    $net_total = $cart->cart_contents_total;
    
    if ( $option->surcharge_type == 'percentage' ) {
        $surcharge_percentage = floatval( $option->surcharge_value ) / 100;
        // Zuschlag auf Basis des Nettowerts berechnen
        $surcharge_amount = $net_total * $surcharge_percentage;
        $fee_label = $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $surcharge_amount = floatval( $option->surcharge_value );
        $fee_label = $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Füge den Zuschlag als steuerpflichtige Fee hinzu.
    // Wenn der gleiche Steuersatz wie bei den Produkten angewendet wird, ergibt sich bei separater Berechnung mathematisch das Gleiche.
    $cart->add_fee( $fee_label, $surcharge_amount, true, $option->tax_class );
}
add_action( 'woocommerce_cart_calculate_fees', 'cth_apply_customer_surcharge' );