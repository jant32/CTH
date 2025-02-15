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
jQuery(document).ready(function ($) {
    $('#customer_type_dropdown').change(function () {
        var customerType = $(this).val();
        var orderId = $('#post_ID').val(); // WooCommerce Bestell-ID abrufen

        $.ajax({
            type: "POST",
            url: adminSurcharge.ajaxurl,
            data: {
                action: "admin_update_surcharge",
                order_id: orderId,
                customer_type: customerType,
                _ajax_nonce: adminSurcharge.nonce,
            },
            success: function (response) {
                if (response.success) {
                    alert("Zuschlag erfolgreich aktualisiert!");
                    location.reload();
                } else {
                    alert("Fehler: " + response.data.message);
                }
            },
        });
    });
});