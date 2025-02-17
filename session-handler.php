<?php
/*
 * session-handler.php
 *
 * Stellt sicher, dass eine Session (oder die WooCommerce-Session) gestartet wird und setzt eine Standard-Kundenart,
 * falls der Kunde noch keine Auswahl getroffen hat.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'cth_start_session', 1 );
function cth_start_session() {
    if ( ! session_id() ) {
        session_start();
    }
    if ( ! WC()->session ) {
        return;
    }
    if ( ! WC()->session->get( 'cth_customer_type' ) ) {
        // Standard-Kundenart setzen: Verwende hier den ersten Eintrag aus der DB (sofern vorhanden)
        $customer_types = cth_get_all_customer_types();
        if ( $customer_types && count( $customer_types ) > 0 ) {
            WC()->session->set( 'cth_customer_type', $customer_types[0]->surcharge_name );
        }
    }
}