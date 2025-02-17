<?php
/*
 * surcharge-handler.php
 *
 * Diese Datei berechnet und wendet den Kundenart-Zuschlag auf den Warenkorb an.
 * Sie liest die in der Session gespeicherte Kundenart aus, ermittelt die zugehörigen Einstellungen 
 * (Zuschlagstyp, -höhe, Steuerklasse) aus der Datenbank und fügt über WooCommerce's add_fee() den Zuschlag hinzu.
 *
 * In dieser Version wird der Zuschlag als nicht steuerpflichtige Fee hinzugefügt, um zu verhindern,
 * dass WooCommerce ihn separat besteuert – stattdessen wird später über den Filter "woocommerce_calculated_total"
 * der Zuschlag zur Steuerbasis addiert, sodass die Steuer auf die Summe aus Produkten und Zuschlag berechnet wird.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Berechnet und fügt den Zuschlag hinzu.
 */
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
    
    // Produkte-Subtotal (ohne Zuschlag)
    $cart_total = $cart->subtotal;
    $surcharge = 0;
    
    if ( $option->surcharge_type === 'percentage' ) {
        $surcharge = ( $cart_total * floatval( $option->surcharge_value ) ) / 100;
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (' . floatval( $option->surcharge_value ) . '%)';
    } else {
        $surcharge = floatval( $option->surcharge_value );
        $fee_label = '[CTH] ' . $option->surcharge_name . ' (+' . number_format( $option->surcharge_value, 2 ) . '€)';
    }
    
    // Zuschlag als Fee hinzufügen – als NICHT steuerpflichtig, damit WooCommerce ihn nicht separat besteuert.
    if ( $surcharge > 0 ) {
        $cart->add_fee( $fee_label, $surcharge, false, $option->tax_class );
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'cth_apply_customer_surcharge' );

/**
 * Filter, um den Zuschlag (der als nicht steuerpflichtige Fee hinzugefügt wurde)
 * in die Steuerbasis einzubeziehen.
 *
 * Hier wird vor der Endberechnung der Totals der Zuschlagsbetrag ermittelt und
 * anhand des in der DB hinterlegten Tax-Class-Slugs der entsprechende Steuersatz ermittelt.
 * Anschließend wird der Zuschlagsteuerbetrag zum Gesamtbetrag addiert.
 */
add_filter( 'woocommerce_calculated_total', 'cth_add_surcharge_tax_to_total', 10, 2 );
function cth_add_surcharge_tax_to_total( $total, $cart ) {
    $surcharge = 0;
    // Summe aller von uns hinzugefügten Zuschlags-Fee ermitteln (erkennbar an unserem Marker "[CTH]" im Fee-Namen)
    foreach ( $cart->fees as $fee ) {
        if ( strpos( $fee->name, '[CTH]' ) !== false ) {
            $surcharge += $fee->amount;
        }
    }
    
    if ( $surcharge > 0 && isset( $_SESSION['cth_customer_type'] ) ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $customer_type_id = intval( $_SESSION['cth_customer_type'] );
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT tax_class FROM $table_name WHERE id = %d", $customer_type_id ) );
        if ( $option && ! empty( $option->tax_class ) ) {
            // Ermitteln des Steuersatzes für die hinterlegte Tax-Class.
            $tax_rates = WC_Tax::get_rates( $option->tax_class );
            if ( ! empty( $tax_rates ) ) {
                $rate = reset( $tax_rates );
                // tax_rate ist in der DB z. B. "19.0000"; Umwandlung in Dezimalzahl (z. B. 0.19)
                $tax_rate_decimal = floatval( $rate['tax_rate'] ) / 100;
                // Berechne den Steuerbetrag für den Zuschlag:
                $surcharge_tax = $surcharge * $tax_rate_decimal;
                // Füge den Zuschlagsteuerbetrag zum Gesamtbetrag hinzu.
                $total += $surcharge_tax;
            }
        }
    }
    return $total;
}

/**
 * (Optional) Filter für Produkte:
 * Überschreibt die Tax‑Class der Produkte im Warenkorb anhand der in der DB hinterlegten Kundenart.
 * Dies kann sinnvoll sein, wenn Du möchtest, dass auch die Produkte dem Kundenart-Steuersatz unterliegen.
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