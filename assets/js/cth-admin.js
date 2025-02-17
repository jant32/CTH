/* cth-admin.js */
/*
 * Custom Tax & Surcharge Handler – Admin & Frontend JS
 * Admin: Diese Datei enthält eventuelle zukünftige adminseitige JS-Funktionalitäten.
 * Frontend: Wird verwendet, um die Auswahl der Kundenart via AJAX zu speichern.
 */

jQuery(document).ready(function($) {
    // Frontend: Bei Änderung der Kundenart-Radio-Buttons, speichere Auswahl via AJAX.
    $('input[name="cth_customer_type"]').on('change', function() {
        var customerType = $(this).val();
        $.ajax({
            url: cth_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'cth_save_customer_type',
                customer_type: customerType,
                nonce: cth_ajax_obj.nonce
            },
            success: function(response) {
                if(response.success) {
                    console.log(response.data.message);
                    // Optional: Neuberechnung des Warenkorbs anstoßen.
                    $('body').trigger('update_checkout');
                } else {
                    console.log(response.data.message);
                }
            }
        });
    });
    
    // Admin: Bei Änderung des Kundenart-Dropdowns in der Bestelldetailsseite, führe einen AJAX-Aufruf aus,
    // um den neuen Kundenart-Wert zu speichern und den Zuschlag sowie die Steuer neu zu berechnen.
    if ($('#cth_customer_type').length) {
        $('#cth_customer_type').on('change', function() {
            var newCustomerType = $(this).val();
            var orderId = $(this).data('order-id');
            if (!orderId) {
                alert('Order ID fehlt.');
                return;
            }
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'cth_update_order_fee',
                    order_id: orderId,
                    customer_type: newCustomerType
                },
                success: function(response) {
                    if(response.success) {
                        alert('Die Berechnung wurde aktualisiert.');
                        // Option: Seite neu laden, um aktualisierte Totals anzuzeigen
                        // location.reload();
                    } else {
                        alert('Fehler: ' + response.data);
                    }
                },
                error: function() {
                    alert('AJAX-Fehler.');
                }
            });
        });
    }
    
    // Admin: Update surcharge sign based on surcharge type selection.
    function updateSurchargeSign() {
        var type = $('#surcharge_type').val();
        if (type === 'percentage') {
            $('#surcharge_sign').text('%');
        } else if (type === 'fixed') {
            $('#surcharge_sign').text('€');
        } else {
            $('#surcharge_sign').text('');
        }
    }
    if ($('#surcharge_type').length) {
        updateSurchargeSign();
        $('#surcharge_type').on('change', function() {
            updateSurchargeSign();
        });
    }
});