<?php
/*
 * surcharge-handler.php
 *
 * Diese Datei berechnet und wendet den Kundenart-Zuschlag auf den Warenkorb an.
 * Sie liest die in der Session gespeicherte Kundenart aus, ermittelt die zugehörigen Einstellungen 
 * (Zuschlagstyp, -höhe, Steuerklasse) aus der Datenbank und fügt über WooCommerce's add_fee() den Zuschlag hinzu.
 *
 * Um sicherzustellen, dass immer die aktuellen Werte berechnet werden, entfernen wir vor der Totals-Berechnung 
 * alle von uns hinzugefügten Fees (die den Marker "[CTH]" im Namen enthalten).
 *
 * Hinweis: Wir übergeben die Tax‑Class hier (in add_fee) zwar, aber in der aktualisierten Version
 * wird der Zuschlag als nicht steuerpflichtige Fee hinzugefügt (3. Parameter false), um eine Doppelbesteuerung zu vermeiden.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Entfernt alle von uns (CTH) hinzugefügten Fees, damit immer neu berechnet wird.
 */
add_action( 'woocommerce_before_calculate_totals', 'cth_reset_our_fees', 1 );
function cth_reset_our_fees( $cart ) {
    if ( ! empty( $cart->fees ) ) {
        foreach ( $cart->fees as $key => $fee ) {
            if ( strpos( $fee->name, '[CTH]' ) !== false ) {
                unset( $cart->fees[ $key ] );
            }
        }
    }
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
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $surcharge = floatval( $option->surcharge_value );
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    // Zuschlag als Fee hinzufügen – als nicht steuerpflichtig, damit der Zuschlag nicht nochmals besteuert wird.
    if ( $surcharge > 0 ) {
        $cart->add_fee( $fee_label, $surcharge, false, $option->tax_class );
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'cth_apply_customer_surcharge' );

/**
 * Filter für die Zuschlags-Fee: Überschreibt die Tax‑Class anhand der in der DB hinterlegten Kundenart.
 */
add_filter( 'woocommerce_fee_tax_class', 'cth_override_fee_tax_class', 10, 2 );
function cth_override_fee_tax_class( $tax_class, $fee ) {
    // Prüfen, ob der Fee-Name unseren Marker enthält.
    if ( strpos( $fee->name, '[CTH]' ) !== false ) {
        if ( isset( $_SESSION['cth_customer_type'] ) ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
            $customer_type_id = intval( $_SESSION['cth_customer_type'] );
            $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $customer_type_id ) );
            if ( $option && ! empty( $option->tax_class ) ) {
                return $option->tax_class;
            }
        }
    }
    return $tax_class;
}

/**
 * Filter für Produkte: Überschreibt die Tax‑Class der Produkte im Warenkorb anhand der in der DB hinterlegten Kundenart.
 * (Dieser Filter wird ggf. benötigt, wenn Du möchtest, dass auch alle Produkte mit dem Kundenart-Steuersatz besteuert werden.)
 */
add_filter( 'woocommerce_product_get_tax_class', 'cth_override_product_tax_class', 10, 2 );
function cth_override_product_tax_class( $tax_class, $product ) {
    if ( ! $product || ! WC()->session ) {
        return $tax_class;
    }
    if ( isset( $_SESSION['cth_customer_type'] ) ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $customer_type_id = intval( $_SESSION['cth_customer_type'] );
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT tax_class FROM $table_name WHERE id = %d", $customer_type_id ) );
        if ( $option && ! empty( $option->tax_class ) ) {
            return $option->tax_class;
        }
    }
    return $tax_class;
}