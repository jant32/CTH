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
    <style>
        /* Stellt sicher, dass das Dropdown genau die gleiche Breite wie das Kundenfeld hat */
        #customer_type {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
        }
        .order_data_column .form-field label {
            font-weight: normal;
            display: block;
            margin-bottom: 5px;
            margin-top: 10px;
        }
    </style>
    <script type="text/javascript">
        function updateCustomerTypeAJAX(orderId) {
            var customerType = jQuery('#customer_type').val();
            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'update_order_customer_type',
                    order_id: orderId,
                    customer_type: customerType
                    // Optional: nonce: 'hier_wenn_gewünscht'
                },
                success: function(response) {
                    if(response.success){
                        // Seite aktualisieren, damit die neuen Werte (u.a. Totals) angezeigt werden
                        location.reload();
                    } else {
                        alert('Fehler beim Aktualisieren der Kundenart.');
                    }
                },
                error: function() {
                    alert('AJAX-Fehler beim Aktualisieren der Kundenart.');
                }
            });
        }
    </script>
    <?php
});