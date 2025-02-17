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
 * - cth_display_checkout_customer_type_options(): Gibt auf der Kasse-Seite die Radio-Buttons zur 
 *   Auswahl der Kundenart aus, inklusive einer h5-Überschrift.
 * - cth_display_customer_type_thank_you(): Zeigt im Bestelldetails-Bereich unter den Kundendaten 
 *   (z. B. E-Mail, Telefon) die ausgewählte Kundenart an.
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
    // Überschrift mit Pflichtkennzeichnung als h5 ausgeben
    echo '<h5>Kundenart auswählen <span style="color: red;">*</span></h5>';
    
    $options = cth_get_customer_type_options();
    // Aktuell ausgewählte Kundenart aus der Session.
    $current_selection = isset( $_SESSION['cth_customer_type'] ) ? $_SESSION['cth_customer_type'] : '';
    
    if ( $options ) {
        echo '<div id="cth-customer-type-options">';
        foreach ( $options as $option ) {
            $formatted_value = cth_format_surcharge_display( $option );
            $checked = ( $current_selection == $option->id ) ? 'checked' : '';
            // Jeder Radio-Button wird als Blockelement dargestellt, sodass sie untereinander stehen.
            echo '<label style="display: block; margin-bottom: 5px;">';
            echo '<input type="radio" name="cth_customer_type" value="' . esc_attr( $option->id ) . '" ' . $checked . '>';
            echo esc_html( $formatted_value );
            echo '</label>';
        }
        echo '</div>';
    }
}

function cth_display_customer_type_thank_you( $order ) {
    // Bestimme zunächst die Order-ID
    if ( is_object( $order ) && method_exists( $order, 'get_id' ) ) {
        $order_id = $order->get_id();
    } else {
        $order_id = intval( $order );
    }
    
    // Versuche zunächst, den Kundenart-Wert aus den Bestellmetadaten zu lesen
    $meta = get_post_meta( $order_id, '_cth_customer_type', true );
    
    // Falls der Meta-Wert leer oder kein Skalar ist, versuche, ihn in einen String zu konvertieren
    if ( empty( $meta ) || is_array( $meta ) || is_object( $meta ) ) {
        if ( is_array( $meta ) ) {
            $meta = reset( $meta );
        } elseif ( is_object( $meta ) ) {
            // Falls das Objekt eine __toString-Methode besitzt
            if ( method_exists( $meta, '__toString' ) ) {
                $meta = $meta->__toString();
            } else {
                $meta = '';
            }
        }
    }
    
    $customer_type_id = intval( $meta );
    
    // Falls der Meta-Wert nicht gesetzt ist, versuche, den Wert aus der Tabelle custom_order_data zu lesen
    if ( empty( $customer_type_id ) ) {
        global $wpdb;
        $order_table = $wpdb->prefix . 'custom_order_data';
        $meta_from_db = $wpdb->get_var( $wpdb->prepare( "SELECT customer_type FROM $order_table WHERE order_id = %d", $order_id ) );
        $customer_type_id = intval( $meta_from_db );
    }
    
    // Wenn wir eine gültige Kundenart-ID haben, rufe den zugehörigen Datensatz ab und zeige ihn an
    if ( $customer_type_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $customer_type_id ) );
        if ( $option ) {
            echo '<p><strong>Kundenart:</strong> ' . esc_html( cth_format_surcharge_display( $option ) ) . '</p>';
        }
    }
}

add_action( 'woocommerce_order_details_after_customer_details', 'cth_display_customer_type_thank_you' );