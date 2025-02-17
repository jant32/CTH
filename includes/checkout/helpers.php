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

function cth_display_customer_type_thank_you( $order_id ) {
    // Zunächst den Kundenart-Wert aus den Bestellmetadaten abfragen
    $meta = get_post_meta( $order_id, '_cth_customer_type', true );
    // Falls der Rückgabewert ein Objekt ist, versuche ihn in einen String umzuwandeln
    if ( is_object( $meta ) && method_exists( $meta, '__toString' ) ) {
        $meta = $meta->__toString();
    } elseif ( is_object( $meta ) ) {
        // Falls keine __toString-Methode vorhanden ist, konvertiere in Array und nimm den ersten Wert
        $meta = ( is_array( $meta ) ) ? reset( $meta ) : '';
    } elseif ( is_array( $meta ) ) {
        $meta = reset( $meta );
    }
    $customer_type_id = intval( $meta );
    
    // Falls nichts gefunden wird, versuche in der custom_order_data Tabelle
    if ( empty( $customer_type_id ) ) {
        global $wpdb;
        $order_table = $wpdb->prefix . 'custom_order_data';
        $meta_from_db = $wpdb->get_var( $wpdb->prepare( "SELECT customer_type FROM $order_table WHERE order_id = %d", $order_id ) );
        if ( is_object( $meta_from_db ) && method_exists( $meta_from_db, '__toString' ) ) {
            $meta_from_db = $meta_from_db->__toString();
        } elseif ( is_object( $meta_from_db ) ) {
            $meta_from_db = ( is_array( $meta_from_db ) ) ? reset( $meta_from_db ) : '';
        } elseif ( is_array( $meta_from_db ) ) {
            $meta_from_db = reset( $meta_from_db );
        }
        $customer_type_id = intval( $meta_from_db );
    }
    
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