jQuery(document).ready(function($) {
    // Overlay-Funktionen für visuelles Feedback
    function showLoadingOverlay() {
        var $container = $('#woocommerce-order-items');
        if (!$container.length) {
            console.log("Container #woocommerce-order-items nicht gefunden.");
            return;
        }
        // Stelle sicher, dass der Container relativ positioniert ist
        if ($container.css('position') === 'static') {
            $container.css('position', 'relative');
        }
        // Füge das Overlay ein, falls es noch nicht existiert
        if ($('#loading-overlay').length === 0) {
            var overlayHtml = '<div id="loading-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">' +
                              '<div class="spinner" style="width: 40px; height: 40px; border: 4px solid transparent; border-top: 4px solid #fff; border-radius: 50%; animation: spin 1s linear infinite;"></div>' +
                              '</div>';
            $container.append(overlayHtml);
            if ($('#spinner-keyframes').length === 0) {
                $('head').append(
                    '<style id="spinner-keyframes">' +
                    '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } } ' +
                    '@-webkit-keyframes spin { 0% { -webkit-transform: rotate(0deg); } 100% { -webkit-transform: rotate(360deg); } }' +
                    '</style>'
                );
            }
        }
    }
    
    function hideLoadingOverlay() {
        $('#loading-overlay').remove();
    }
    
    // Einheitliche Funktion zur Aktualisierung der Kundenart
    function updateCustomerTypeAJAX(orderId) {
        // Suche nach dem Dropdown – im Admin-Bereich wird z. B. #customer_type_dropdown verwendet,
        // im Frontend vielleicht #customer_type
        var $select = $('#customer_type');
        if (!$select.length) {
            $select = $('#customer_type_dropdown');
        }
        if ($select.length) {
            // Falls Select2 verwendet wird, schließe es
            if ($select.data('select2')) {
                $select.select2('close');
            } else {
                $select.blur();
            }
        }
        var customerType = $select.val();
        
        // Zeige den Overlay-Spinner
        showLoadingOverlay();
        
        // Baue die Daten zusammen – falls im Admin-Bereich, wird auch die Order-ID mitgesendet
        var ajaxData = {
            action: 'update_surcharge',
            customer_type: customerType
        };
        if (orderId) {
            ajaxData.order_id = orderId;
        }
        
        // Nutze adminSurcharge.ajaxurl und nonce, falls verfügbar, sonst global ajaxurl
        var ajaxUrl = (typeof adminSurcharge !== 'undefined' && adminSurcharge.ajaxurl) ? adminSurcharge.ajaxurl : ajaxurl;
        if (typeof adminSurcharge !== 'undefined' && adminSurcharge.nonce) {
            ajaxData._ajax_nonce = adminSurcharge.nonce;
        }
        
        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: ajaxData,
            success: function(response) {
                hideLoadingOverlay();
                if (response.success) {
                    // Bei Admin: Optionale Erfolgsmeldung anzeigen
                    if (orderId) {
                        alert("Zuschlag erfolgreich aktualisiert!");
                    }
                    location.reload();
                } else {
                    alert('Fehler: ' + (response.data && response.data.message ? response.data.message : 'Unbekannter Fehler'));
                }
            },
            error: function(xhr, status, error) {
                hideLoadingOverlay();
                alert('AJAX-Fehler: ' + error);
            }
        });
    }
    
    // Event-Bindings:
    // Falls das Admin-Dropdown (#customer_type_dropdown) existiert, binde den Change-Event (mit Order-ID)
    if ($('#customer_type_dropdown').length) {
        $('#customer_type_dropdown').on('change', function() {
            var orderId = $('#post_ID').val(); // Bestell-ID im Admin-Bereich
            updateCustomerTypeAJAX(orderId);
        });
    }
    // Falls das normale Dropdown (#customer_type) existiert (z.B. im Frontend), binde den Change-Event
    else if ($('#customer_type').length) {
        $('#customer_type').on('change', function() {
            updateCustomerTypeAJAX(); // Ohne Order-ID
        });
    }
});