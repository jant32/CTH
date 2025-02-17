<?php
/*
 * display-customer-type-checkout.php
 *
 * Zeigt auf der WooCommerce-Dankeseite nach Abschluss der Bestellung die vom Kunden gewählte Kundenart an.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_thankyou', 'cth_display_customer_type', 20 );
function cth_display_customer_type( $order_id ) {
    $customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    if ( $customer_type ) {
        echo '<p>' . sprintf( __( 'Kundenart: %s', 'cth' ), esc_html( $customer_type ) ) . '</p>';
    }
}