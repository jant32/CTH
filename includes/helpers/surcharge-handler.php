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

// Debugging: AJAX-Handler wird geladen
error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: ajax-handler.php geladen" );

// AJAX-Hooks für die Aktualisierung des Zuschlags
add_action( 'wp_ajax_update_surcharge', 'update_surcharge' );
add_action( 'wp_ajax_nopriv_update_surcharge', 'update_surcharge' );

/**
 * AJAX-Handler: Aktualisiert den Kundenart-Wert (z. B. in der Session)
 * und gibt eine JSON-Antwort zurück.
 */
function update_surcharge() {
    // Alle aktiven Output-Puffer leeren
    while ( ob_get_level() > 0 ) {
        ob_end_clean();
    }
    
    if ( ! WC()->cart ) {
        error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Warenkorb nicht geladen." );
        wp_send_json_error( [ 'message' => 'Warenkorb nicht geladen' ] );
        return;
    }
    
    // Kundenart aus POST übernehmen, Standard: 'verein_ssb'
    $customer_type = isset( $_POST['customer_type'] ) ? sanitize_text_field( $_POST['customer_type'] ) : 'verein_ssb';
    
    // Speichere den Wert in der Session – so steht er beim nächsten Neuberechnen des Warenkorbs zur Verfügung
    WC()->session->set( 'customer_type', $customer_type );
    
    // Optional: Falls du auch in der Bestellung diesen Wert speichern möchtest, kannst du hier Order‑Meta aktualisieren.
    // z.B.: update_post_meta( $order_id, '_customer_type', $customer_type );
    
    error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: AJAX – Kundenart aktualisiert auf: " . $customer_type );
    
    // Nochmals sicherstellen, dass keine Ausgabe im Buffer ist
    while ( ob_get_level() > 0 ) {
        ob_end_clean();
    }
    
    // Sende die JSON-Antwort
    wp_send_json_success( [ 'message' => 'Kundenart aktualisiert' ] );
}

/**
 * Hook: Berechnet den Zuschlag im Warenkorb
 * basierend auf dem in der Session gespeicherten Kundenart-Wert.
 */
add_action( 'woocommerce_cart_calculate_fees', 'apply_surcharge_to_cart' );
function apply_surcharge_to_cart() {
    if ( ! WC()->cart ) {
        return;
    }
    
    $cart           = WC()->cart;
    // Kundenart aus der Session; falls nicht gesetzt, Standard 'verein_ssb'
    $customer_type  = WC()->session->get( 'customer_type', 'verein_ssb' );
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