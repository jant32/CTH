<?php
/*
 * helpers.php
 *
 * Diese Datei enthält Hilfsfunktionen, die im Plugin verwendet werden.
 *
 * Funktionen:
 * - cth_format_surcharge_display(): Formatiert den Anzeigenamen einer Kundenart inkl. Zuschlagshöhe 
 *   (z. B. "Name | 25%" oder "Name | +25,00€").
 * - cth_get_customer_type_options(): Ruft alle Kundenart-Optionen aus der Datenbank ab.
 * - cth_display_checkout_customer_type_options(): Gibt auf der Kassenseite die Radio-Buttons zur Auswahl
 *   der Kundenart aus, inklusive einer h5-Überschrift.
 * - cth_display_customer_type_thank_you(): Zeigt im Bestelldetails-Bereich (Thank-You-/Detailseite) die
 *   ausgewählte Kundenart an. Dabei wird sichergestellt, dass die Ausgabe nur einmal erfolgt.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'cth_format_surcharge_display' ) ) {
    function cth_format_surcharge_display( $option ) {
        $display = $option->surcharge_name . ' | ';
        if ( $option->surcharge_type === 'percentage' ) {
            $display .= floatval( $option->surcharge_value ) . '%';
        } else {
            $display .= '+' . number_format( $option->surcharge_value, 2 ) . '€';
        }
        return $display;
    }
}

if ( ! function_exists( 'cth_get_customer_type_options' ) ) {
    function cth_get_customer_type_options() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
        return $wpdb->get_results( "SELECT * FROM $table_name" );
    }
}

if ( ! function_exists( 'cth_display_checkout_customer_type_options' ) ) {
    function cth_display_checkout_customer_type_options() {
        echo '<h5>Kundenart auswählen <span style="color: red;">*</span></h5>';
        $options = cth_get_customer_type_options();
        $current_selection = isset( $_SESSION['cth_customer_type'] ) ? intval( $_SESSION['cth_customer_type'] ) : '';
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
}

if ( ! function_exists( 'cth_display_customer_type_thank_you' ) ) {
    function cth_display_customer_type_thank_you( $order ) {
        // Ausgabe nur einmal durchführen, auch wenn der Hook mehrfach ausgeführt wird.
        static $output_done = false;
        if ( $output_done ) {
            return;
        }
        $output_done = true;
    
        // Bestimme die Order-ID
        if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
            $order_id = $order->get_id();
        } else {
            $order_id = intval( $order );
        }
        global $wpdb;
        $order_table = $wpdb->prefix . 'custom_order_data';
        // Es wird erwartet, dass in der Spalte customer_type die Kundenart-ID gespeichert ist.
        $customer_type_id = intval( $wpdb->get_var( $wpdb->prepare( "SELECT customer_type FROM $order_table WHERE order_id = %d", $order_id ) ) );
        if ( $customer_type_id ) {
            $table = $wpdb->prefix . 'custom_tax_surcharge_handler';
            $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $customer_type_id ) );
            if ( $option ) {
                echo '<p><strong>Kundenart:</strong> ' . esc_html( cth_format_surcharge_display( $option ) ) . '</p>';
            }
        }
    }
    add_action( 'woocommerce_order_details_after_customer_details', 'cth_display_customer_type_thank_you' );
}