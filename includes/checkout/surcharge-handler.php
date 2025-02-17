<?php
/*
 * surcharge-handler.php
 *
 * Diese Datei berechnet und wendet den Kundenart-Zuschlag auf den Warenkorb an.
 * Sie liest die in der Session gespeicherte Kundenart aus, ermittelt die zugehörigen Einstellungen (Zuschlagstyp, -höhe, Steuerklasse) aus der Datenbank und fügt über WooCommerce's add_fee() den Zuschlag hinzu.
 *
 * Funktionen:
 * - cth_apply_customer_surcharge(): Berechnet den Zuschlag und wendet ihn auf den Warenkorb an.
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
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $customer_type_id = intval( $_SESSION['cth_customer_type'] );
    $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $customer_type_id ) );
    if ( ! $option ) {
        return;
    }

    $cart_total = $cart->subtotal;
    $surcharge = 0;
    if ( $option->surcharge_type === 'percentage' ) {
        $surcharge = ( $cart_total * floatval( $option->surcharge_value ) ) / 100;
        $fee_label = $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $surcharge = floatval( $option->surcharge_value );
        $fee_label = $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }

    // Zuschlag als Fee hinzufügen.
    // Hier verwenden wir die Steuerklasse, die in der Tabelle wp_custom_tax_surcharge_handler hinterlegt ist.
    if ( $surcharge > 0 ) {
        $cart->add_fee( $fee_label, $surcharge, true, $option->tax_class );
    }
}