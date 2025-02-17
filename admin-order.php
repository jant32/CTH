<?php
/*
 * admin-order.php
 *
 * Fügt auf der Bestelldetailsseite im Adminbereich zwei Dropdown-Menüs hinzu:
 *  - Eines zur Auswahl der Kundenart (dynamisch aus der DB geladen).
 *  - Eines zur Auswahl der Steuerklasse (dynamisch über WooCommerce ermittelt).
 * Beim Speichern der Bestellung werden diese Werte als Order-Meta gespeichert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_admin_order_data_after_order_details', 'cth_render_order_meta_fields' );
function cth_render_order_meta_fields( $order ) {
    $current_customer_type = get_post_meta( $order->get_id(), '_cth_customer_type', true );
    $current_tax_class     = get_post_meta( $order->get_id(), '_cth_tax_class', true );
    $customer_types        = cth_get_all_customer_types(); // Funktion aus helpers.php

    ?>
    <div class="cth-order-meta">
        <p>
            <label for="cth_customer_type"><?php _e( 'Kundenart:', 'cth' ); ?></label>
            <select name="cth_customer_type" id="cth_customer_type">
                <?php foreach ( $customer_types as $type ) : 
                    $display = $type->surcharge_name . ' | ' . ( ( $type->surcharge_type == 'percent' ) ? $type->surcharge_value . '%' : '+' . number_format( $type->surcharge_value, 2, ',', '.' ) . '€' );
                    ?>
                    <option value="<?php echo esc_attr( $type->surcharge_name ); ?>" <?php selected( $current_customer_type, $type->surcharge_name ); ?>>
                        <?php echo esc_html( $display ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="cth_tax_class"><?php _e( 'Steuerklasse:', 'cth' ); ?></label>
            <?php
            // Funktion aus tax-class-handler.php
            cth_render_tax_class_dropdown( $current_tax_class );
            ?>
        </p>
    </div>
    <?php
}