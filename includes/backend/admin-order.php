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
            
            // Versuche, das Container-Element für die Bestellpositionen zu finden.
            // Zuerst #order_line_items, alternativ der gesamte Admin-Content.
            var $orderItemsContainer = jQuery('#order_line_items');
            if (!$orderItemsContainer.length) {
                $orderItemsContainer = jQuery('#post-body-content');
            }
            
            // Wenn ein Container gefunden wurde, füge ein Overlay mit Spinner ein.
            if ($orderItemsContainer.length) {
                // Stelle sicher, dass der Container relativ positioniert ist.
                if ($orderItemsContainer.css('position') === 'static') {
                    $orderItemsContainer.css('position', 'relative');
                }
                // Füge das Overlay ein, falls noch nicht vorhanden.
                if (jQuery('#surcharge-loader').length === 0) {
                    $orderItemsContainer.append(
                        '<div id="surcharge-loader" style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); z-index:9999; display:flex; align-items:center; justify-content:center;">' +
                        '<div class="spinner" style="width:40px; height:40px; border:4px solid #ccc; border-top-color:#333; border-radius:50%; animation:spin 1s linear infinite;"></div>' +
                        '</div>'
                    );
                    // Füge die Keyframes für den Spinner ein (falls nicht bereits vorhanden).
                    if (jQuery('#surcharge-spinner-css').length === 0) {
                        jQuery('head').append('<style id="surcharge-spinner-css">@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>');
                    }
                }
            }
            
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
                    // Overlay entfernen
                    jQuery('#surcharge-loader').remove();
                    if(response.success){
                        // Seite neu laden, um aktualisierte Totals anzuzeigen.
                        location.reload();
                    } else {
                        alert('Fehler beim Aktualisieren der Kundenart.');
                    }
                },
                error: function() {
                    // Overlay entfernen
                    jQuery('#surcharge-loader').remove();
                    alert('AJAX-Fehler beim Aktualisieren der Kundenart.');
                }
            });
        }
    </script>
    <?php
});