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

jQuery(document).ready(function($) {
    // Beispiel: Funktion zum Anzeigen des Overlays (angepasst an deinen Container)
    function showLoadingOverlay() {
        var $container = $('#woocommerce-order-items');
        if (!$container.length) {
            console.log("Container #woocommerce-order-items nicht gefunden.");
            return;
        }
        if ($container.css('position') === 'static') {
            $container.css('position', 'relative');
        }
        if ($('#loading-overlay').length === 0) {
            var overlayHtml = '<div id="loading-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">' +
                              '<div class="spinner" style="width: 40px; height: 40px; border: 4px solid transparent; border-top: 4px solid #fff; border-radius: 50%; animation: spin 1s linear infinite;"></div>' +
                              '</div>';
            $container.append(overlayHtml);
            if ($('#spinner-keyframes').length === 0) {
                $('head').append('<style id="spinner-keyframes">@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } } @-webkit-keyframes spin { 0% { -webkit-transform: rotate(0deg); } 100% { -webkit-transform: rotate(360deg); } }</style>');
            }
        }
    }

    function hideLoadingOverlay() {
        $('#loading-overlay').remove();
    }

    // AJAX-Funktion, die beim Wechsel des Dropdowns aufgerufen wird
    window.updateCustomerTypeAJAX = function(orderId) {
        var customerType = $('#customer_type').val();
        showLoadingOverlay();
        $.ajax({
            type: 'POST',
            url: adminSurcharge.ajaxurl,
            data: {
                action: 'update_order_customer_type',
                order_id: orderId,
                customer_type: customerType
            },
            success: function(response) {
                hideLoadingOverlay();
                if (response.success) {
                    location.reload();
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
});