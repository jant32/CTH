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

// Fügt ein Overlay in den Container mit der ID "woocommerce-order-items" ein
function showLoadingOverlay() {
    var $container = jQuery('#woocommerce-order-items');
    
    // Sicherstellen, dass der Container eine relative Positionierung hat
    if ($container.css('position') === 'static') {
        $container.css('position', 'relative');
    }
    
    // Falls das Overlay noch nicht existiert, einfügen
    if (jQuery('#loading-overlay').length === 0) {
        $container.append(
            '<div id="loading-overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; background: rgba(255,255,255,0.7); display: flex; align-items: center; justify-content: center; z-index: 1000;">' +
                '<div class="spinner" style="width:40px; height:40px; border:4px solid #ccc; border-top-color:#333; border-radius:50%; animation: spin 1s linear infinite;"></div>' +
            '</div>'
        );
        
        // Falls die Keyframes noch nicht definiert sind, in den Head einfügen
        if (jQuery('#spinner-keyframes').length === 0) {
            jQuery('head').append('<style id="spinner-keyframes">@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>');
        }
    }
}

// Entfernt das Overlay wieder
function hideLoadingOverlay() {
    jQuery('#loading-overlay').remove();
}