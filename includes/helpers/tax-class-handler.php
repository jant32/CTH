<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Zeigt das Steuerklasse-Dropdownfeld in der Bestell-Detailansicht an.
 * Die Optionen werden dynamisch aus den WooCommerce Steuerklassen geladen.
 */
add_action('woocommerce_admin_order_data_after_order_details', 'cth_display_tax_class_field', 25);
function cth_display_tax_class_field( $order ) {
    global $wpdb;
    $order_id = $order->get_id();
    
    // Hole den aktuellen Steuerklassen-Wert aus eurer Custom-Tabelle
    $tax_class = $wpdb->get_var( $wpdb->prepare(
        "SELECT tax_class FROM {$wpdb->prefix}custom_order_data WHERE order_id = %d",
        $order_id
    ) );
    if ( ! $tax_class ) {
        $tax_class = 'standard'; // Standardwert
    }
    
    // Hole zusätzliche Steuerklassen aus WooCommerce
    $additional_tax_classes = WC_Tax::get_tax_classes();
    // Standardsteuerklasse manuell hinzufügen (da sie nicht in get_tax_classes() enthalten ist)
    $tax_classes = array( 'standard' => __('Standard', 'woocommerce') );
    if ( !empty( $additional_tax_classes ) ) {
        foreach ( $additional_tax_classes as $class ) {
            // Nutze sanitized slug als Wert; der Label kann direkt angezeigt werden
            $tax_classes[ sanitize_title( $class ) ] = $class;
        }
    }
    ?>
    <p class="form-field form-field-wide">
        <label for="tax_class"><?php esc_html_e( 'Steuerklasse:', 'woocommerce' ); ?></label>
        <select name="tax_class" id="tax_class" class="wc-enhanced-select" style="width: 100%;">
            <?php foreach ( $tax_classes as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $tax_class, $value ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}