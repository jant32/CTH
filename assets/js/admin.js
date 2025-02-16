jQuery(document).ready(function($) {

    // Overlay-Funktionen
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
        // Versuche, das Dropdown-Element zu finden:
        var $select = $('#customer_type');
        if (!$select.length) {
            $select = $('#customer_type_dropdown');
        }
        if (!$select.length) {
            console.log("Kein Dropdown mit der ID #customer_type oder #customer_type_dropdown gefunden.");
            return;
        }
        // Falls Select2 verwendet wird, schließe das Dropdown
        if ($select.data('select2')) {
            $select.select2('close');
        } else {
            $select.blur();
        }
    
        var customerType = $select.val();
        showLoadingOverlay();
    
        var ajaxData = {
            action: 'update_surcharge',
            customer_type: customerType
        };
        if (orderId) {
            ajaxData.order_id = orderId;
        }
    
        // Verwende adminSurcharge.ajaxurl falls definiert, sonst global ajaxurl
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
                    // Erfolg: Seite neu laden (oder UI anderweitig aktualisieren)
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
    
    // Delegiertes Binding für beide mögliche IDs
    $(document).on('change', '#customer_type, #customer_type_dropdown', function() {
        // Im Admin-Bereich: Order-ID aus #post_ID (sofern vorhanden) übergeben
        var orderId = $('#post_ID').val() || null;
        updateCustomerTypeAJAX(orderId);
    });
    
    // Zusätzlich, falls Select2 verwendet wird, binde auch den "select2:select" Event
    $(document).on('select2:select', '#customer_type, #customer_type_dropdown', function(e) {
        var orderId = $('#post_ID').val() || null;
        updateCustomerTypeAJAX(orderId);
    });
});