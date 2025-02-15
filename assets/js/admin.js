function updateCustomerTypeAJAX(orderId) {
    var customerType = jQuery('#customer_type').val();

    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'update_order_customer_type',
            order_id: orderId,
            customer_type: customerType
        },
        success: function(response) {
            if (response.success) {
                location.reload(); // Falls WooCommerce es nicht direkt aktualisiert
            } else {
                alert('Fehler beim Aktualisieren der Kundenart.');
            }
        }
    });
}
jQuery(document).ready(function($) {
    $('#customer_type').change(function() {
        var order_id = woocommerce_admin_meta_boxes.post_id;
        var customer_type = $(this).val();

        $.ajax({
            type: 'POST',
            url: cth_ajax.ajax_url,
            data: {
                action: 'update_customer_type',
                order_id: order_id,
                customer_type: customer_type
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Fehler: ' + response.data);
                }
            }
        });
    });
});