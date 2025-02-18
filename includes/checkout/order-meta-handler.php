<?php
/*
 * order-meta-handler.php
 *
 * Diese Datei aktualisiert die Order-Meta-Daten in Zusammenhang mit der Kundenart.
 * Beim Abschluss des Checkouts (über den Hook woocommerce_checkout_update_order_meta)
 * oder beim Speichern der Bestellung im Admin-Bereich (über den Hook save_post_shop_order)
 * wird die Funktion cth_update_order_meta() aufgerufen, die:
 *  - den im Formular übergebenen Wert aus dem Feld "cth_customer_type" ausliest,
 *  - diesen als Order Meta (_cth_customer_type) speichert und
 *  - anschließend über die Funktion cth_save_customer_type_to_order() in der Tabelle wp_custom_order_data speichert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_update_order_meta( $order_id ) {
    if ( ! $order_id ) {
        return;
    }
    
    // Prüfe, ob im Checkout-Formular ein Wert für cth_customer_type übermittelt wurde
    if ( isset( $_POST['cth_customer_type'] ) && ! empty( $_POST['cth_customer_type'] ) ) {
        $customer_type = intval( $_POST['cth_customer_type'] );
    } else {
        // Fallback: Lese den Wert aus den bestehenden Order-Meta
        $customer_type = intval( get_post_meta( $order_id, '_cth_customer_type', true ) );
    }
    
    // Speichere diesen Wert als Order Meta
    update_post_meta( $order_id, '_cth_customer_type', $customer_type );
    
    // Aktualisiere die Tabelle wp_custom_order_data
    if ( function_exists( 'cth_save_customer_type_to_order' ) ) {
        cth_save_customer_type_to_order( $order_id, $customer_type );
    }
}
add_action( 'woocommerce_checkout_update_order_meta', 'cth_update_order_meta', 10, 1 );
add_action( 'save_post_shop_order', 'cth_update_order_meta', 20, 1 );