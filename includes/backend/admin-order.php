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

    <!-- CSS für Lade-Indikator (kann alternativ in eine separate CSS-Datei ausgelagert werden) -->
    <style>
        /* Lade-Indikator: Dimmt den Container und zeigt einen Spinner */
        .woocommerce_order_items.loading, #order_line_items.loading {
            opacity: 0.5;
            position: relative;
        }
        .woocommerce_order_items.loading:after, #order_line_items.loading:after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 40px;
            height: 40px;
            margin: -20px 0 0 -20px;
            border: 4px solid #ccc;
            border-top-color: #333;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            z-index: 1000;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script type="text/javascript">
        function updateCustomerTypeAJAX(orderId) {
            var customerType = jQuery('#customer_type').val();
            
            // Container, in dem die Bestellpositionen (Artikel) dargestellt werden.
            // Passen Sie ggf. den Selektor an, falls Ihre Seite einen anderen Container verwendet.
            var $orderItemsContainer = jQuery('#order_line_items');
            if (!$orderItemsContainer.length) {
                $orderItemsContainer = jQuery('.woocommerce_order_items');
            }
            
            // Lade-Indikator: Container dimmen & Spinner anzeigen
            $orderItemsContainer.addClass('loading');
            
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
                    // Lade-Indikator entfernen
                    $orderItemsContainer.removeClass('loading');
                    if(response.success){
                        // Seite aktualisieren, damit neue Werte (u.a. Totals) angezeigt werden
                        location.reload();
                    } else {
                        alert('Fehler beim Aktualisieren der Kundenart.');
                    }
                },
                error: function() {
                    $orderItemsContainer.removeClass('loading');
                    alert('AJAX-Fehler beim Aktualisieren der Kundenart.');
                }
            });
        }
    </script>
    <?php
});