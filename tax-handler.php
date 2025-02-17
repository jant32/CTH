<?php
/*
 * tax-handler.php
 *
 * Passt die Steuerklasse für Produkte im Frontend anhand der gewählten Kundenart an.
 * Die vorher hart codierten Steuerklassen werden nun durch die in der Datenbank gespeicherten Werte ersetzt.
 * Mithilfe eines Filters (woocommerce_product_get_tax_class) wird die Steuerklasse für Produkte dynamisch gesetzt.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_before_calculate_totals', 'cth_set_tax_class_based_on_customer_type' );
function cth_set_tax_class_based_on_customer_type() {
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
        add_filter( 'woocommerce_product_get_tax_class', function( $tax_class, $product ) use ( $selected ) {
            return $selected->tax_class ? $selected->tax_class : $tax_class;
        }, 10, 2 );
    }
}