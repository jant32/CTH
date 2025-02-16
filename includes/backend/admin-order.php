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
    ?>
    <p class="form-field form-field-wide">
        <label for="customer_type"><?php esc_html_e( 'Kundenart:', 'woocommerce' ); ?></label>
        <select name="customer_type" id="customer_type" class="wc-enhanced-select" style="width: 100%;">
            <?php foreach ( get_all_customer_types() as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $customer_type, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Zerstöre Select2 auf dem Kundenart-Dropdown, damit es als normales <select> agiert
        if ($('#customer_type').data('select2')) {
            $('#customer_type').select2('destroy');
        }
        
        // Entferne eventuell vorhandenes inline onchange-Attribut
        $('#customer_type').removeAttr('onchange');
        
        // Binde das Change-Event an das Dropdown
        $(document).on('change', '#customer_type', function() {
            var orderId = $('#post_ID').val() || null;
            updateCustomerTypeAJAX(orderId);
        });
        
        // Falls Select2 verwendet wird, auch den select2:select-Event binden
        $(document).on('select2:select', '#customer_type', function(e) {
            var orderId = $('#post_ID').val() || null;
            updateCustomerTypeAJAX(orderId);
        });
        
        // Funktion, die den AJAX-Aufruf ausführt
        function updateCustomerTypeAJAX(orderId) {
            var customerType = document.getElementById('customer_type').value;
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'update_surcharge',
                    order_id: orderId,
                    customer_type: customerType
                },
                success: function(response) {
                    if (response.success) {
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
    });
    </script>
    <?php
});