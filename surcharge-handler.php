<?php
/*
 * surcharge-handler.php
 *
 * Berechnet und wendet den Zuschlag (basierend auf der gewählten Kundenart) auf den WooCommerce-Warenkorb an.
 * Liest die Kundenart aus der Session aus und berechnet den Zuschlag (prozentual oder als fester Betrag).
 * Der Zuschlag wird über den Hook woocommerce_cart_calculate_fees in den Warenkorb integriert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_cart_calculate_fees', 'cth_apply_customer_surcharge' );
function cth_apply_customer_surcharge() {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    if ( ! WC()->cart ) {
        return;
    }
    
    $customer_type = WC()->session->get( 'cth_customer_type' );
    if ( ! $customer_type ) {
        return;
    }
    
    $customer_types = cth_get_all_customer_types();
    $selected       = null;
    if ( $customer_types ) {
        foreach ( $customer_types as $type ) {
            if ( $type->surcharge_name == $customer_type ) {
                $selected = $type;
                break;
            }
        }
    }
    if ( $selected ) {
        $cart_total = WC()->cart->subtotal;
        if ( $selected->surcharge_type == 'percent' ) {
            $surcharge = ( $cart_total * $selected->surcharge_value / 100 );
        } else {
            $surcharge = $selected->surcharge_value;
        }
        WC()->cart->add_fee( __( 'Kundenart Zuschlag', 'cth' ), $surcharge, true, $selected->tax_class );
    }
}