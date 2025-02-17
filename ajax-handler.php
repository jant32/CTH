<?php
/*
 * ajax-handler.php
 *
 * Handhabt Ajax-Anfragen für den Custom Tax Surcharge Handler.
 * Insbesondere wird hier das Speichern der vom Kunden im Checkout ausgewählten Kundenart in der Session umgesetzt.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'wp_ajax_cth_save_customer_type', 'cth_ajax_save_customer_type' );
add_action( 'wp_ajax_nopriv_cth_save_customer_type', 'cth_ajax_save_customer_type' );

function cth_ajax_save_customer_type() {
    check_ajax_referer( 'cth_ajax_nonce', 'nonce' );
    $customer_type = sanitize_text_field( $_POST['customer_type'] );
    // Speichern der Kundenart in der WooCommerce-Session
    WC()->session->set( 'cth_customer_type', $customer_type );
    wp_send_json_success();
}