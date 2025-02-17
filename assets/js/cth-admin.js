/* cth-admin.js */
/*
 * Custom Tax & Surcharge Handler – Admin & Frontend JS
 * Diese Datei enthält JS-Code, der sowohl im Frontend (für die Kundenart-Radio-Buttons)
 * als auch im Admin-Bereich (für das Dropdown in der Bestelldetailsseite) verwendet wird.
 */

jQuery(document).ready(function($) {
    // Frontend: Bei Änderung der Kundenart-Radio-Buttons, speichere Auswahl via AJAX.
    if ($('input[name="cth_customer_type"]').length) {
        $('input[name="cth_customer_type"]').on('change', function() {
            console.log('Frontend: Kundenart geändert.');
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
                        console.log('Frontend AJAX:', response.data.message);
                        // Optional: Neuberechnung des Warenkorbs anstoßen.
                        $('body').trigger('update_checkout');
                    } else {
                        console.log('Frontend AJAX Fehler:', response.data.message);
                    }
                }
            });
        });
    }
    
    // Admin: Überprüfe, ob das Dropdown-Feld für die Kundenart vorhanden ist.
    if ($('#cth_customer_type').length) {
        console.log('Admin: Kundenart-Dropdown gefunden.');
        $('#cth_customer_type').on('change', function() {
            console.log('Admin: Dropdown hat sich geändert.');
            var newCustomerType = $(this).val();
            var orderId = $(this).data('order-id');
            console.log('Order ID:', orderId, 'neuer Kundenart-Wert:', newCustomerType);
            if (!orderId) {
                alert('Order ID fehlt.');
                return;
            }
            $.ajax({
                url: ajaxurl, // In Admin-Bereichen ist ajaxurl standardmäßig definiert.
                type: 'POST',
                data: {
                    action: 'cth_update_order_fee',
                    order_id: orderId,
                    customer_type: newCustomerType
                },
                success: function(response) {
                    console.log('Admin AJAX-Erfolg:', response);
                    if(response.success) {
                        alert('Die Berechnung wurde aktualisiert.');
                        // Option: Seite neu laden, um die aktualisierten Totals zu sehen.
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
    
    // Admin: Aktualisiere das Zuschlagssymbol basierend auf der Auswahl im Zuschlagstyp-Dropdown.
    if ($('#surcharge_type').length) {
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
        updateSurchargeSign();
        $('#surcharge_type').on('change', function() {
            updateSurchargeSign();
        });
    }
});