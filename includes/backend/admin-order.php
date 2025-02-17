<?php
/*
 * admin-order.php
 *
 * Diese Datei fügt auf der Bestelldetailsseite im WooCommerce-Backend ein benutzerdefiniertes Feld hinzu,
 * in dem Du die Kundenart auswählen kannst. Das Dropdown-Feld wird in der Order-Daten-Box (unter den Feldern "Status" und "Kunde")
 * im gleichen Stil wie diese Felder dargestellt.
 *
 * Beim Speichern der Bestellung wird der ausgewählte Wert als Order‑Meta (_cth_customer_type) gespeichert.
 * Über die Order‑Meta‑Handler (in order-meta-handler.php und save-customer-type.php) wird dann zusätzlich in der Tabelle
 * wp_custom_order_data ein Eintrag (oder Update) vorgenommen.
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
    // Aktuellen Kundenart-Wert (als Option-ID) aus den Order-Meta-Daten abrufen.
    $current_customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $options = $wpdb->get_results( "SELECT * FROM $table_name" );
    ?>
    <div class="order_data_column">
        <h4><?php _e( 'Kundenart', 'text-domain' ); ?></h4>
        <p class="form-field form-field-wide">
            <select name="cth_customer_type" id="cth_customer_type" class="wc-enhanced-select">
                <option value=""><?php _e( 'Wähle eine Kundenart', 'text-domain' ); ?></option>
                <?php
                if ( $options ) {
                    foreach ( $options as $option ) {
                        $formatted_value = cth_format_surcharge_display( $option );
                        $selected = ( $current_customer_type == $option->id ) ? 'selected="selected"' : '';
                        echo '<option value="' . esc_attr( $option->id ) . '" ' . $selected . '>' . esc_html( $formatted_value ) . '</option>';
                    }
                }
                ?>
            </select>
        </p>
    </div>
    <?php
}
add_action( 'woocommerce_admin_order_data_after_order_details', 'cth_admin_order_custom_field_display' );

/**
 * Speichert den ausgewählten Kundenart-Wert, wenn die Bestellung aktualisiert wird.
 *
 * @param int $post_id
 */
function cth_admin_order_save_custom_field( $post_id ) {
    if ( isset( $_POST['cth_customer_type'] ) ) {
        update_post_meta( $post_id, '_cth_customer_type', sanitize_text_field( $_POST['cth_customer_type'] ) );
    }
}
add_action( 'save_post_shop_order', 'cth_admin_order_save_custom_field' );