<?php
/*
 * helpers.php
 *
 * Diese Datei enthält Hilfsfunktionen, die im Plugin verwendet werden.
 *
 * Funktionen:
 * - cth_format_surcharge_display(): Formatiert den Anzeigenamen einer Kundenart, inkl. Zuschlagshöhe
 *   (z. B. "Name | 25%" oder "Name | +25,00€").
 * - cth_get_customer_type_options(): Ruft alle Kundenart-Optionen aus der Datenbank ab.
 * - cth_display_checkout_customer_type_options(): Gibt auf der Kassenseite die Radio-Buttons zur
 *   Auswahl der Kundenart aus, inklusive einer h5-Überschrift.
 * - cth_display_customer_type_thank_you(): Zeigt im Bestelldetails-Bereich (Thank-You-Seite) die
 *   ausgewählte Kundenart an. Hier wird der in wp_custom_order_data gespeicherte Wert (als ID)
 *   verwendet, um den entsprechenden Datensatz aus wp_custom_tax_surcharge_handler zu laden.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_format_surcharge_display( $option ) {
    $display = $option->surcharge_name . ' | ';
    if ( $option->surcharge_type === 'percentage' ) {
        $display .= floatval( $option->surcharge_value ) . '%';
    } else {
        $display .= '+' . number_format( $option->surcharge_value, 2 ) . '€';
    }
    return $display;
}

function cth_get_customer_type_options() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
    return $wpdb->get_results( "SELECT * FROM $table_name" );
}

function cth_display_checkout_customer_type_options() {
    echo '<h5>Kundenart auswählen <span style="color: red;">*</span></h5>';
    $options = cth_get_customer_type_options();
    $current_selection = isset( $_SESSION['cth_customer_type'] ) ? intval($_SESSION['cth_customer_type']) : '';
    if ( $options ) {
        echo '<div id="cth-customer-type-options">';
        foreach ( $options as $option ) {
            $formatted_value = cth_format_surcharge_display( $option );
            $checked = ( $current_selection == $option->id ) ? 'checked' : '';
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="radio" name="cth_customer_type" value="' . esc_attr( $option->id ) . '" ' . $checked . '>';
            echo esc_html( $formatted_value );
            echo '</label>';
        }
        echo '</div>';
    }
}

function cth_display_customer_type_thank_you( $order ) {
    if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
        $order_id = $order->get_id();
    } else {
        $order_id = intval( $order );
    }
    global $wpdb;
    $order_table = $wpdb->prefix . 'custom_order_data';
    $customer_type = $wpdb->get_var( $wpdb->prepare( "SELECT customer_type FROM $order_table WHERE order_id = %d", $order_id ) );
    if ( $customer_type ) {
        // $customer_type ist jetzt die ID der Kundenart.
        $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", intval( $customer_type ) ) );
        if ( $option ) {
            echo '<p><strong>Kundenart:</strong> ' . esc_html( cth_format_surcharge_display( $option ) ) . '</p>';
        }
    }
}

add_action( 'woocommerce_order_details_after_customer_details', 'cth_display_customer_type_thank_you' );