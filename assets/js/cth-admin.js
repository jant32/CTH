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
});