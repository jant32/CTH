<?php
/*
 * admin-order.php
 *
 * Diese Datei fügt auf der Bestelldetailsseite im WooCommerce-Backend ein benutzerdefiniertes Feld hinzu,
 * in dem Du die Kundenart auswählen kannst. Das Dropdown-Feld wird in der Order-Daten-Box (unter den Feldern "Status" und "Kunde")
 * im gleichen Stil wie diese Felder dargestellt.
 *
 * Beim Speichern der Bestellung wird der ausgewählte Wert als Order‑Meta (_cth_customer_type) gespeichert.
 * Anschließend wird über die Order‑Meta‑Handler (in order-meta-handler.php und save-customer-type.php)
 * in der Tabelle wp_custom_order_data ein Eintrag (oder Update) vorgenommen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gibt das Dropdown-Feld "Kundenart" in der Order-Daten-Box aus.
 *
 * @param WC_Order $order
 */
function cth_admin_order_custom_field_display( $order ) {
    $order_id = $order->get_id();
    
    global $wpdb;
    // Lese den aktuell gespeicherten Kundenart-Wert (als ID) aus der Tabelle wp_custom_order_data.
    $order_table = $wpdb->prefix . 'custom_order_data';
    $current_customer_type = $wpdb->get_var( $wpdb->prepare( "SELECT customer_type FROM $order_table WHERE order_id = %d", $order_id ) );
    
    // Lade alle Optionen aus der Options-Tabelle.
    $surcharge_table = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $options = $wpdb->get_results( "SELECT * FROM $surcharge_table" );
    ?>
    <p class="form-field form-field-wide">
        <label for="cth_customer_type"><?php _e( 'Kundenart', 'text-domain' ); ?></label>
        <select name="cth_customer_type" id="cth_customer_type" class="wc-enhanced-select" data-order-id="<?php echo esc_attr( $order_id ); ?>">
            <option value=""><?php _e( 'Wähle eine Kundenart', 'text-domain' ); ?></option>
            <?php
            if ( $options ) {
                foreach ( $options as $option ) {
                    $formatted_value = cth_format_surcharge_display( $option );
                    // Vergleiche den Option-ID (als Integer)
                    $selected = ( intval( $current_customer_type ) === intval( $option->id ) ) ? 'selected="selected"' : '';
                    echo '<option value="' . esc_attr( $option->id ) . '" ' . $selected . '>' . esc_html( $formatted_value ) . '</option>';
                }
            }
            ?>
        </select>
    </p>
    <?php
}
add_action( 'woocommerce_admin_order_data_after_order_details', 'cth_admin_order_custom_field_display' );

/**
 * Speichert den ausgewählten Kundenart-Wert, wenn die Bestellung im Admin-Backend aktualisiert wird.
 * Der Wert wird als Option-ID gespeichert. Anschließend wird die Funktion cth_save_customer_type_to_order()
 * aufgerufen, um in der Tabelle wp_custom_order_data die Felder customer_type (als ID) und tax_class (als tax_rate_id)
 * zu aktualisieren.
 *
 * @param int $post_id
 */
function cth_admin_order_save_custom_field( $post_id ) {
    if ( isset( $_POST['cth_customer_type'] ) ) {
        $new_customer_type = sanitize_text_field( $_POST['cth_customer_type'] );
        update_post_meta( $post_id, '_cth_customer_type', $new_customer_type );
        
        global $wpdb;
        $surcharge_table = $wpdb->prefix . 'custom_tax_surcharge_handler';
        // Hole den Datensatz zur ausgewählten Option
        $option = $wpdb->get_row( $wpdb->prepare( "SELECT surcharge_name, tax_class, surcharge_value, surcharge_type FROM $surcharge_table WHERE id = %d", intval( $new_customer_type ) ) );
        if ( $option ) {
            if ( function_exists( 'cth_save_customer_type_to_order' ) ) {
                cth_save_customer_type_to_order( $post_id, $new_customer_type, $option->tax_class );
            }
        }
    }
}
add_action( 'woocommerce_process_shop_order_meta', 'cth_admin_order_save_custom_field', 99 );