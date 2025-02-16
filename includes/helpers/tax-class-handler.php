<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Zeigt das Steuerklasse-Dropdownfeld in der Bestell-Detailansicht an.
 * Die Optionen werden dynamisch aus den in WooCommerce definierten Steuerklassen geladen.
 * Als Label wird der jeweilige Steuersatz in Prozent angezeigt.
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
    
    // Hole die in WooCommerce definierten Steuerklassen:
    $additional_tax_classes = WC_Tax::get_tax_classes();
    // Füge die Standardsteuerklasse manuell hinzu (da sie nicht in get_tax_classes() enthalten ist)
    $tax_classes = array( 'standard' => __('Standard', 'woocommerce') );
    if ( ! empty( $additional_tax_classes ) ) {
        foreach ( $additional_tax_classes as $class ) {
            $tax_classes[ sanitize_title( $class ) ] = $class;
        }
    }
    ?>
    <p class="form-field form-field-wide">
        <label for="tax_class"><?php esc_html_e( 'Steuerklasse:', 'woocommerce' ); ?></label>
        <select name="tax_class" id="tax_class" class="wc-enhanced-select" style="width: 100%;">
            <?php 
            foreach ( $tax_classes as $slug => $class_label ) :
                // Für die Standardsteuerklasse: WooCommerce speichert "standard" als leeren String intern.
                $lookup = ($slug === 'standard') ? '' : $slug;
                $rates = WC_Tax::get_rates_for_tax_class( $lookup );
                $rate_percent = 0;
                if ( ! empty( $rates ) ) {
                    $rate_data = current( $rates );
                    $rate_percent = floatval( $rate_data['tax_rate'] );
                }
                $display_rate = number_format( $rate_percent, 2 ) . '%';
            ?>
                <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $tax_class, $slug ); ?>>
                    <?php echo esc_html( $display_rate ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}