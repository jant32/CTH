<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Debugging: surcharge-handler.php geladen
error_log( '[' . date( 'Y-m-d H:i:s' ) . "] DEBUG: surcharge-handler.php geladen" );

/**
 * AJAX-Handler für die Zuschlagsberechnung im Backend.
 */
add_action( 'wp_ajax_admin_update_surcharge', 'admin_update_surcharge' );

function admin_update_surcharge() {

	// Berechtigung prüfen
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_send_json_error( [ 'message' => 'Keine Berechtigung' ] );
	}

	// Nonce prüfen
	check_ajax_referer( 'admin_surcharge_nonce', 'nonce' );

	// Parameter prüfen
	if ( ! isset( $_POST['order_id'], $_POST['customer_type'] ) ) {
		error_log( '[' . date( 'Y-m-d H:i:s' ) . "] ERROR: Fehlende Parameter für admin_update_surcharge" );
		wp_send_json_error( [ 'message' => 'Fehlende Parameter' ] );
		return;
	}

	$order_id      = intval( $_POST['order_id'] );
	$customer_type = sanitize_text_field( $_POST['customer_type'] );
	$order         = wc_get_order( $order_id );

	if ( ! $order ) {
		error_log( '[' . date( 'Y-m-d H:i:s' ) . "] ERROR: Bestellung nicht gefunden (ID: $order_id)" );
		wp_send_json_error( [ 'message' => 'Bestellung nicht gefunden' ] );
		return;
	}

	// Speichere die Kundenart in der Bestellung
	$order->update_meta_data( '_customer_type', $customer_type );

	$surcharge_name = 'Kundenart-Zuschlag';

	// Alte Zuschläge entfernen
	foreach ( $order->get_items( 'fee' ) as $fee_id => $fee ) {
		if ( $fee->get_name() === $surcharge_name ) {
			error_log( '[' . date( 'Y-m-d H:i:s' ) . "] DEBUG: Entferne alten Zuschlag in Bestellung (ID: $order_id)" );
			$order->remove_item( $fee_id );
		}
	}

	// Zuschlag-Prozentsatz bestimmen
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
	}

	// Neuen Zuschlag berechnen und hinzufügen, wenn erforderlich
	if ( $surcharge_percentage > 0 ) {
		// Basis: Bestellsubtotal (ohne bisherige Gebühren)
		$surcharge_amount = $order->get_subtotal() * $surcharge_percentage;

		$fee = new WC_Order_Item_Fee();
		$fee->set_name( $surcharge_name );
		$fee->set_amount( $surcharge_amount );
		$fee->set_total( $surcharge_amount );
		// Falls keine Steuern auf den Zuschlag anfallen:
		$fee->set_tax_status( 'none' );
		$fee->set_order_id( $order_id );
		$order->add_item( $fee );

		error_log( '[' . date( 'Y-m-d H:i:s' ) . "] DEBUG: Neuer Zuschlag in Bestellung hinzugefügt: {$surcharge_amount} EUR" );
	}

	// Totals neu berechnen und Bestellung speichern
	$order->calculate_totals( true );
	$order->save();

	wp_send_json_success( [ 'message' => 'Zuschlag aktualisiert' ] );
}

/**
 * Enqueue des Scripts für das Admin-Backend.
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
	// Skript nur auf den Bearbeitungsseiten (z. B. Shop-Bestellungen) laden
	if ( 'post.php' === $hook || 'edit.php' === $hook ) {
		wp_enqueue_script(
			'admin-surcharge-handler',
			plugin_dir_url( __DIR__ ) . 'assets/js/admin-surcharge.js',
			[ 'jquery' ],
			null,
			true
		);
		wp_localize_script(
			'admin-surcharge-handler',
			'adminSurcharge',
			[
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'admin_surcharge_nonce' ),
			]
		);
	}
} );