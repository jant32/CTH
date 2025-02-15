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
    // Beispiel: Funktion zum Anzeigen des Overlays (angepasst an deinen Container)
    function showLoadingOverlay() {
        var $container = jQuery('#woocommerce-order-items');
        if (!$container.length) {
            console.log("Container #woocommerce-order-items nicht gefunden.");
            return;
        }
        // Container relativ positionieren, falls nötig
        if ($container.css('position') === 'static') {
            $container.css('position', 'relative');
        }
        // Overlay einfügen, falls noch nicht vorhanden
        if (jQuery('#loading-overlay').length === 0) {
            var overlayHtml = '<div id="loading-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">' +
                              '<div class="spinner" style="width: 40px; height: 40px; border: 4px solid transparent; border-top: 4px solid #fff; border-radius: 50%; animation: spin 1s linear infinite;"></div>' +
                              '</div>';
            $container.append(overlayHtml);
            // Keyframes einfügen, falls nicht schon vorhanden
            if (jQuery('#spinner-keyframes').length === 0) {
                jQuery('head').append(
                    '<style id="spinner-keyframes">' +
                    '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }' +
                    '@-webkit-keyframes spin { 0% { -webkit-transform: rotate(0deg); } 100% { -webkit-transform: rotate(360deg); } }' +
                    '</style>'
                );
            }
        }
    }
    
    function hideLoadingOverlay() {
        jQuery('#loading-overlay').remove();
    }
    
    function updateCustomerTypeAJAX(orderId) {
        var $select = jQuery('#customer_type');
    
        // Schließe das Select2-Dropdown, falls es verwendet wird
        if ($select.data('select2')) {
            $select.select2('close');
        } else {
            $select.blur();
        }
    
        // Wert aus dem Dropdown holen
        var customerType = $select.val();
        
        // Overlay anzeigen
        showLoadingOverlay();
        
        jQuery.ajax({
            type: 'POST',
            url: ajaxurl, // ajaxurl wird im Admin immer global zur Verfügung gestellt
            data: {
                action: 'update_order_customer_type',
                order_id: orderId,
                customer_type: customerType
                // Optional: nonce: 'dein_nonce'
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