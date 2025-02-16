<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . '../helpers/helpers.php';

// Kundenart in der linken Spalte unter "Kunde" anzeigen
add_action( 'woocommerce_admin_order_data_after_order_details', function( $order ) {
    global $wpdb;

    $order_id = $order->get_id();
    $customer_type = $wpdb->get_var( $wpdb->prepare(
        "SELECT customer_type FROM {$wpdb->prefix}custom_order_data WHERE order_id = %d",
        $order_id
    ) );

    if ( ! $customer_type ) {
        $customer_type = 'none';
    }
    $customer_types = get_all_customer_types();
    ?>
    <p class="form-field form-field-wide">
        <label for="customer_type"><?php esc_html_e( 'Kundenart:', 'woocommerce' ); ?></label>
        <select name="customer_type" id="customer_type" class="wc-enhanced-select" style="width: 100%;" onchange="updateCustomerTypeAJAX(<?php echo $order_id; ?>)">
            <?php foreach ( get_all_customer_types() as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $customer_type, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <script type="text/javascript">
    /**
     * Diese Funktion wird vom inline onchange-Attribut aufgerufen.
     * Sie sendet per AJAX den neuen Kundenart-Wert (und im Admin ggf. die Order-ID)
     * an unseren AJAX-Handler (Action "update_surcharge").
     */
    function updateCustomerTypeAJAX(orderId) {
        var customerType = document.getElementById('customer_type').value;
        jQuery.ajax({
            type: 'POST',
            url: ajaxurl, // ajaxurl ist im Admin global verfügbar
            data: {
                action: 'update_surcharge',
                order_id: orderId,
                customer_type: customerType
            },
            success: function(response) {
                if (response.success) {
                    // Bei Erfolg Seite neu laden, um die Änderungen zu übernehmen
                    location.reload();
                } else {
                    alert('Fehler: ' + (response.data && response.data.message ? response.data.message : 'Unbekannter Fehler'));
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX-Fehler: ' + error);
            }
        });
    }
    </script>
    <?php
});