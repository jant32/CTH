<?php
/*
 * helpers.php
 *
 * Diese Datei enthält Hilfsfunktionen, die im Plugin verwendet werden.
 *
 * Funktionen:
 * - cth_format_surcharge_display(): Formatiert den Anzeigenamen einer Kundenart, inkl. Zuschlagshöhe (z. B. "Name | 25%" oder "Name | +25,00€").
 * - cth_get_customer_type_options(): Ruft alle Kundenart-Optionen aus der Datenbank ab.
 * - cth_display_checkout_customer_type_options(): Gibt auf der Kasse-Seite die Radio-Buttons zur Auswahl der Kundenart aus, inklusive einer h5-Überschrift.
 * - cth_display_customer_type_thank_you(): Zeigt auf der "Danke"-Seite die ausgewählte Kundenart an.
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

function cth_display_customer_type_thank_you( $order_id ) {
    // Kundenart aus den Order-Metadaten abrufen.
    $customer_type_id = get_post_meta( $order_id, '_cth_customer_type', true );
    if ( $customer_type_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $customer_type_id ) );
        if ( $option ) {
            echo '<p><strong>Kundenart:</strong> ' . esc_html( cth_format_surcharge_display( $option ) ) . '</p>';
        }
    }
}