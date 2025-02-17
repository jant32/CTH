<?php
/*
 * admin-order.php
 *
 * Diese Datei fügt auf der Bestelldetailsseite im WooCommerce-Backend zwei Dropdown-Felder ein:
 * - "Kundenart" (mit dynamisch geladenen Optionen aus wp_custom_tax_surcharge_handler)
 * - "Steuerklasse" (dynamisch ermittelt aus den WooCommerce-Steuerklassen)
 *
 * Funktionen:
 * - cth_render_order_meta_box(): Rendert die Dropdowns.
 * - cth_save_order_meta_box_data(): Speichert die ausgewählten Werte, wenn die Bestellung aktualisiert wird.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_render_order_meta_box( $post ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';

    // Dynamisch verfügbare Kundenarten abrufen.
    $options = $wpdb->get_results( "SELECT * FROM $table_name" );

    // Aktuelle Bestellung: Meta-Daten abrufen.
    $order_id = $post->ID;
    $current_customer_type = get_post_meta( $order_id, '_cth_customer_type', true );
    $current_tax_class = get_post_meta( $order_id, '_cth_tax_class', true );
    ?>
    <div class="cth-order-meta">
        <p>
            <label for="cth_customer_type"><strong>Kundenart:</strong></label>
            <select name="cth_customer_type" id="cth_customer_type">
                <?php foreach ( $options as $option ) : 
                    $formatted_value = cth_format_surcharge_display( $option );
                    $selected = ( $current_customer_type == $option->id ) ? 'selected' : '';
                    ?>
                    <option value="<?php echo esc_attr( $option->id ); ?>" <?php echo $selected; ?>>
                        <?php echo esc_html( $formatted_value ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="cth_tax_class"><strong>Steuerklasse:</strong></label>
            <?php cth_render_tax_class_dropdown( $current_tax_class ); ?>
        </p>
    </div>
    <?php
}
add_action( 'add_meta_boxes', 'cth_add_order_meta_box' );
function cth_add_order_meta_box() {
    add_meta_box( 'cth_order_meta', 'Custom Tax & Surcharge', 'cth_render_order_meta_box', 'shop_order', 'side', 'default' );
}

function cth_save_order_meta_box_data( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( isset( $_POST['cth_customer_type'] ) ) {
        update_post_meta( $post_id, '_cth_customer_type', sanitize_text_field( $_POST['cth_customer_type'] ) );
    }
    if ( isset( $_POST['cth_tax_class'] ) ) {
        update_post_meta( $post_id, '_cth_tax_class', sanitize_text_field( $_POST['cth_tax_class'] ) );
    }
}
add_action( 'save_post_shop_order', 'cth_save_order_meta_box_data' );