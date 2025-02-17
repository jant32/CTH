<?php
/*
 * checkout-handler.php
 *
 * Fügt auf der WooCommerce-Kassenseite ein Set von Radio-Buttons hinzu, über die der Kunde seine Kundenart auswählen kann.
 * Die Optionen werden dynamisch aus der Datenbanktabelle (wp_custom_tax_surcharge_handler) geladen.
 * Die Auswahl wird via Ajax (über ajax-handler.php) gespeichert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_review_order_before_payment', 'cth_render_checkout_customer_type' );
function cth_render_checkout_customer_type() {
    $customer_types = cth_get_all_customer_types();
    ?>
    <div id="cth-customer-type">
        <h3><?php _e( 'Kundenart wählen', 'cth' ); ?></h3>
        <?php foreach ( $customer_types as $type ) : 
            $display = $type->surcharge_name . ' | ' . ( ( $type->surcharge_type == 'percent' ) ? $type->surcharge_value . '%' : '+' . number_format( $type->surcharge_value, 2, ',', '.' ) . '€' );
            ?>
            <p>
                <input type="radio" name="cth_customer_type" value="<?php echo esc_attr( $type->surcharge_name ); ?>" id="cth_<?php echo esc_attr( $type->surcharge_name ); ?>">
                <label for="cth_<?php echo esc_attr( $type->surcharge_name ); ?>"><?php echo esc_html( $display ); ?></label>
            </p>
        <?php endforeach; ?>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('input[name="cth_customer_type"]').on('change', function(){
            var selected = $(this).val();
            $.ajax({
                url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                type: 'POST',
                data: {
                    action: 'cth_save_customer_type',
                    customer_type: selected,
                    nonce: '<?php echo wp_create_nonce("cth_ajax_nonce"); ?>'
                },
                success: function(response) {
                    if ( response.success ) {
                        // Trigger Update des Warenkorbs
                        $('body').trigger('update_checkout');
                    }
                }
            });
        });
    });
    </script>
    <?php
}