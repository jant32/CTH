<?php
if (!defined('ABSPATH')) {
    exit;
}

// Kundenart im Checkout anzeigen
add_action('woocommerce_before_checkout_billing_form', function($checkout) {
    $selected_type = WC()->session->get('customer_type', 'verein_ssb');

    echo '<p><strong>Kundenart *</strong></p>';
    ?>
    <p class="form-row form-row-wide">
        <input type="radio" name="customer_type" value="verein_ssb" <?php checked($selected_type, 'verein_ssb'); ?> onchange="updateCustomerType(this.value);">
        <label>Verein (im SSB Hannover)</label>
        <br>
        <input type="radio" name="customer_type" value="verein_non_ssb" <?php checked($selected_type, 'verein_non_ssb'); ?> onchange="updateCustomerType(this.value);">
        <label>Verein (nicht Mitglied im SSB Hannover) | +5%</label>
        <br>
        <input type="radio" name="customer_type" value="privatperson" <?php checked($selected_type, 'privatperson'); ?> onchange="updateCustomerType(this.value);">
        <label>Privatperson | +10%</label>
        <br>
        <input type="radio" name="customer_type" value="kommerziell" <?php checked($selected_type, 'kommerziell'); ?> onchange="updateCustomerType(this.value);">
        <label>Kommerzielle Nutzung | +15%</label>
    </p>

    <script>
        function updateCustomerType(type) {
            jQuery.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                data: {
                    action: 'set_customer_type',
                    customer_type: type
                },
                success: function() {
                    jQuery(document.body).trigger('update_checkout');
                }
            });
        }
    </script>
    <?php
});

// AJAX-Handler für Kundenart speichern
add_action('wp_ajax_set_customer_type', 'set_customer_type');
add_action('wp_ajax_nopriv_set_customer_type', 'set_customer_type');
function set_customer_type() {
    if (isset($_POST['customer_type'])) {
        WC()->session->set('customer_type', sanitize_text_field($_POST['customer_type']));
    }
    wp_die();
}