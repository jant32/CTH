<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Berechnet und wendet den Kundenart-Zuschlag auf den Warenkorb an.
 *
 * @param WC_Cart $cart         Die Warenkorb-Instanz.
 * @param string  $customer_type Der Kundenart-Wert (z. B. 'verein_non_ssb', 'privatperson', 'kommerziell').
 */
function apply_customer_surcharge( $cart, $customer_type ) {
    $surcharge_name = 'Kundenart-Zuschlag';

    // Entferne vorhandene Zuschlag-Fees (falls bereits vorhanden)
    foreach ( $cart->get_fees() as $fee_key => $fee ) {
        if ( $fee->name === $surcharge_name ) {
            unset( WC()->cart->fees_api()->fees[ $fee_key ] );
        }
    }

    // Bestimme den Zuschlagsprozentsatz anhand der Kundenart
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

    // Falls ein Prozentsatz definiert ist, berechne und füge den Zuschlag hinzu
    if ( $surcharge_percentage > 0 ) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        $cart->add_fee( $surcharge_name, $surcharge_amount, true );
        error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlag im Warenkorb hinzugefügt: " . $surcharge_amount . " EUR" );
    }
}

/**
 * Hook-Funktion, die beim Neuberechnen des Warenkorbs aufgerufen wird.
 * Liest den Kundenart-Wert aus der Session (Standard: 'verein_ssb') und
 * wendet den Zuschlag über die Funktion apply_customer_surcharge() an.
 *
 * @param WC_Cart $cart Die aktuelle Warenkorb-Instanz.
 */
function apply_customer_surcharge_to_cart( $cart ) {
    if ( ! $cart ) {
        return;
    }
    // Kundenart aus der Session (Standard: 'verein_ssb')
    $customer_type = WC()->session->get( 'customer_type', 'verein_ssb' );
    error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlagsberechnung im Warenkorb gestartet für Kundenart: " . $customer_type );
    apply_customer_surcharge( $cart, $customer_type );
    error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Endgültige Gebühren im Warenkorb: " . print_r( $cart->get_fees(), true ) );
}
add_action( 'woocommerce_cart_calculate_fees', 'apply_customer_surcharge_to_cart', 20 );