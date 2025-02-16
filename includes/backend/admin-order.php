<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . '../helpers/helpers.php';

// Kundenart-Dropdown in der Bestell-Detailansicht
add_action( 'woocommerce_admin_order_data_after_order_details', function( $order ) {
    global $wpdb;
    $order_id = $order->get_id();
    $customer_type = $wpdb->get_var( $wpdb->prepare(
        "SELECT customer_type FROM {$wpdb->prefix}custom_order_data WHERE order_id = %d",
        $order_id
    ) );
    if ( ! $customer_type ) {
        $customer_type = 'none';
    }
    ?>
    <p class="form-field form-field-wide">
        <label for="customer_type"><?php esc_html_e( 'Kundenart:', 'woocommerce' ); ?></label>
        <select name="customer_type" id="customer_type" class="wc-enhanced-select" style="width: 100%;">
            <?php foreach ( get_all_customer_types() as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $customer_type, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Zerstöre die Select2-Initialisierung und entferne alle Event-Handler vom Kundenart-Dropdown
        var $dropdown = $('#customer_type');
        if ($dropdown.data('select2')) {
            $dropdown.select2('destroy');
        }
        $dropdown.off();
    });
    </script>
    <?php
});