<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Debugging: surcharge-handler.php geladen
error_log( '[' . date( 'Y-m-d H:i:s' ) . "] DEBUG: surcharge-handler.php loaded" );

/**
 * AJAX-Handler: Aktualisiert Kundenart und berechnet den Zuschlag.
 */
add_action( 'wp_ajax_update_order_customer_type', 'update_order_customer_type_ajax' );
function update_order_customer_type_ajax() {

	// Berechtigung prüfen – nur User, die Shop-Bestellungen bearbeiten dürfen
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_send_json_error( array( 'message' => 'Keine Berechtigung' ) );
		exit;
	}

	// (Optional: Nonce-Check einbauen, wenn ihr diesen in der JS mitgebt)
	// if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'update_order_customer_type_nonce' ) ) {
	//     wp_send_json_error( array( 'message' => 'Ungültiger Nonce' ) );
	//     exit;
	// }

	// Benötigte Parameter prüfen
	if ( ! isset( $_POST['order_id'] ) || ! isset( $_POST['customer_type'] ) ) {
		wp_send_json_error( array( 'message' => 'Fehlende Parameter' ) );
		exit;
	}

	$order_id      = intval( $_POST['order_id'] );
	$customer_type = sanitize_text_field( $_POST['customer_type'] );

	// Update in der Custom-Tabelle: custom_order_data
	global $wpdb;
	$table_name = $wpdb->prefix . 'custom_order_data';
	$wpdb->replace(
		$table_name,
		array(
			'order_id'      => $order_id,
			'customer_type' => $customer_type,
		),
		array( '%d', '%s' )
	);

	// Hole die Bestellung
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => 'Bestellung nicht gefunden' ) );
		exit;
	}

	// Speichere den Kundenart-Wert als Order-Meta (optional)
	$order->update_meta_data( '_customer_type', $customer_type );

	// Zuschlag berechnen: Bestehende Zuschlag-Items entfernen
	$surcharge_name = 'Kundenart-Zuschlag';
	foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {
		if ( $item->get_name() === $surcharge_name ) {
			$order->remove_item( $item_id );
		}
	}

	// Zuschlag-Prozentsatz anhand des Kundenart-Werts bestimmen
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

	// Falls ein Zuschlag anfällt, diesen berechnen und hinzufügen
	if ( $surcharge_percentage > 0 ) {
		// Basis: Bestellsubtotal (ohne Zuschläge)
		$order_subtotal  = $order->get_subtotal();
		$surcharge_amount = $order_subtotal * $surcharge_percentage;

		// Neues Fee-Item anlegen
		$fee = new WC_Order_Item_Fee();
		$fee->set_name( $surcharge_name );
		$fee->set_amount( $surcharge_amount );
		$fee->set_total( $surcharge_amount );
		$fee->set_tax_status( 'none' ); // anpassen, falls Steuern berechnet werden sollen
		$fee->set_order_id( $order_id );
		$order->add_item( $fee );

		// Optional: Zuschlag in der Bestellung als Meta speichern
		$order->update_meta_data( '_customer_surcharge', $surcharge_amount );
	} else {
		$order->delete_meta_data( '_customer_surcharge' );
	}

	// Totals neu berechnen und Bestellung speichern
	$order->calculate_totals( true );
	$order->save();

	wp_send_json_success( array( 'message' => 'Kundenart und Zuschlag aktualisiert' ) );
}