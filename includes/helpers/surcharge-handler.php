<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Optionale Fehleranzeige unterdrücken – idealerweise in der wp-config.php global eingestellt
@ini_set( 'display_errors', 0 );
@error_reporting( 0 );

// Falls schon Output vorliegt, leeren wir diesen
if ( ob_get_length() ) {
    ob_clean();
}

// Debugging: surcharge-handler.php geladen
error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: surcharge-handler.php geladen" );

// AJAX-Hooks für die Aktualisierung des Zuschlags
add_action( 'wp_ajax_update_surcharge', 'update_surcharge_handler' );
add_action( 'wp_ajax_nopriv_update_surcharge', 'update_surcharge_handler' );

/**
 * AJAX-Handler: Aktualisiert den Kundenart-Wert.
 * - Im Frontend wird der Wert in der Session gespeichert.
 * - Im Admin-Bereich (Order-Details) wird – falls eine order_id übergeben wird – der Order-Meta aktualisiert.
 */
function update_surcharge_handler() {
    // Alle aktiven Output-Puffer leeren
    while ( ob_get_level() > 0 ) {
        ob_end_clean();
    }
    
    // Prüfen, ob ein Warenkorb vorhanden ist (normalerweise nur im Frontend)
    if ( ! WC()->cart ) {
        // Im Admin-Bereich ist der Warenkorb oft nicht verfügbar.
        if ( is_admin() ) {
            if ( isset( $_POST['order_id'] ) ) {
                $order_id = intval( $_POST['order_id'] );
                $customer_type = isset( $_POST['customer_type'] ) ? sanitize_text_field( $_POST['customer_type'] ) : 'verein_ssb';
                update_post_meta( $order_id, '_customer_type', $customer_type );
                error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Admin – Kundenart für Bestellung $order_id aktualisiert auf: " . $customer_type );
                wp_send_json_success( [ 'message' => 'Kundenart aktualisiert (Admin)' ] );
            } else {
                wp_send_json_success( [ 'message' => 'Kundenart aktualisiert (Admin, kein order_id)' ] );
            }
        } else {
            error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Warenkorb nicht geladen." );
            wp_send_json_error( [ 'message' => 'Warenkorb nicht geladen' ] );
        }
        return;
    }
    
    // Im Frontend: Kundenart aus POST übernehmen, Standard: 'verein_ssb'
    $customer_type = isset( $_POST['customer_type'] ) ? sanitize_text_field( $_POST['customer_type'] ) : 'verein_ssb';
    WC()->session->set( 'customer_type', $customer_type );
    error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: AJAX – Kundenart aktualisiert auf: " . $customer_type );
    
    while ( ob_get_level() > 0 ) {
        ob_end_clean();
    }
    
    wp_send_json_success( [ 'message' => 'Kundenart aktualisiert' ] );
}

/**
 * Hook: Berechnet den Zuschlag im Warenkorb
 * basierend auf dem in der Session gespeicherten Kundenart-Wert.
 * Dieser Hook wird nur im Frontend ausgeführt.
 */
add_action( 'woocommerce_cart_calculate_fees', 'apply_surcharge_to_cart_handler' );
function apply_surcharge_to_cart_handler() {
    if ( ! WC()->cart ) {
        return;
    }
    
    $cart = WC()->cart;
    // Kundenart aus der Session; falls nicht gesetzt, Standard 'verein_ssb'
    $customer_type = WC()->session->get( 'customer_type', 'verein_ssb' );
    $surcharge_name = 'Kundenart-Zuschlag';
    
    error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlagsberechnung im Warenkorb gestartet für Kundenart: " . $customer_type );
    
    // Entferne vorhandene Zuschläge, falls vorhanden
    foreach ( $cart->get_fees() as $fee_key => $fee ) {
        if ( $fee->name === $surcharge_name ) {
            unset( WC()->cart->fees_api()->fees[ $fee_key ] );
        }
    }
    
    // Bestimme den Zuschlag-Prozentsatz
    $surcharge_percentage = 0;
    switch ( $customer_type ) {
        case 'verein_non_ssb':
            $surcharge_percentage = 0.05;
            break;
        case 'privatperson':
            $surcharge_percentage = 0.10;
            break;
        case 'kommerziell':
            $surcharge_percentage = 0.15;
            break;
        default:
            $surcharge_percentage = 0;
            break;
    }
    
    if ( $surcharge_percentage > 0 ) {
        // Berechne den Zuschlag anhand des Warenkorbsubtotals
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        // Füge den Zuschlag als Fee hinzu – da wir vorher alle bestehenden Zuschlag-Fees entfernt haben,
        // wird er nur einmal hinzugefügt.
        $cart->add_fee( $surcharge_name, $surcharge_amount, true );
        error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlag im Warenkorb hinzugefügt: " . $surcharge_amount . " EUR" );
    }
    
    error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Endgültige Gebühren im Warenkorb: " . print_r( $cart->get_fees(), true ) );
}