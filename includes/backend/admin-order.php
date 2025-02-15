<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../helpers/helpers.php';

// Kundenart in der linken Spalte unter "Kunde" anzeigen
add_action('woocommerce_admin_order_data_after_order_details', function($order) {
    global $wpdb;

    $order_id = $order->get_id();
    $customer_type = $wpdb->get_var($wpdb->prepare(
        "SELECT customer_type FROM {$wpdb->prefix}custom_order_data WHERE order_id = %d",
        $order_id
    ));

    if (!$customer_type) {
        $customer_type = 'none';
    }
    $customer_types = get_all_customer_types();
    ?>
    <p class="form-field form-field-wide">
        <label for="customer_type"><?php esc_html_e('Kundenart:', 'woocommerce'); ?></label>
        <select name="customer_type" id="customer_type" class="wc-enhanced-select" style="width: 100%;" onchange="updateCustomerTypeAJAX(<?php echo $order_id; ?>)">
            <?php foreach ($customer_types as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($customer_type, $key); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </p>

    <script type="text/javascript">
        function updateCustomerTypeAJAX(orderId) {
            var customerType = jQuery('#customer_type').val();
            
            // Overlay anzeigen
            showLoadingOverlay();
            
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'update_order_customer_type',
                    order_id: orderId,
                    customer_type: customerType
                    // Optional: nonce: 'dein_nonce'
                },
                success: function(response) {
                    // Overlay entfernen
                    hideLoadingOverlay();
                    if(response.success){
                        location.reload(); // oder gezielte Aktualisierung der Totals
                    } else {
                        alert('Fehler beim Aktualisieren der Kundenart.');
                    }
                },
                error: function() {
                    hideLoadingOverlay();
                    alert('AJAX-Fehler beim Aktualisieren der Kundenart.');
                }
            });
        }
    </script>
    <?php
});