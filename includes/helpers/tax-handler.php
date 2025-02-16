<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Setzt die Steuerklasse für alle Produkte im Warenkorb, basierend auf der im Checkout ausgewählten Zuschlagsoption.
 */
function cth_apply_custom_tax_class_to_cart() {
    if ( is_admin() ) {
        return;
    }
    
    if ( isset( $_POST['custom_surcharge'] ) && ! empty( $_POST['custom_surcharge'] ) ) {
        $surcharge_id = intval( $_POST['custom_surcharge'] );
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $rule = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $surcharge_id ), ARRAY_A );
        if ( $rule ) {
            $tax_class = $rule['tax_class'];
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $product = $cart_item['data'];
                $product->set_tax_class( $tax_class );
            }
        }
    }
}
add_action( 'woocommerce_cart_calculate_fees', 'cth_apply_custom_tax_class_to_cart', 30 );