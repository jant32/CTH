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
                nonce: adminSurcharge.nonce,
            },
            success: function (response) {
                if (response.success) {
                    alert("Zuschlag erfolgreich aktualisiert!");
                    location.reload();
                } else {
                    alert("Fehler: " + response.data.message);
                }
            },
            error: function () {
                alert("Ein Fehler ist aufgetreten.");
            }
        });
    });
});