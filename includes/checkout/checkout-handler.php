<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Erzeugt den HTML-Inhalt für die Zuschlagsoptionen als Radio-Buttons.
 *
 * @return string Der HTML-Output.
 */
function cth_get_custom_surcharge_options_html() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $options = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A );
    $selected_option = WC()->session->get( 'custom_surcharge', 0 );
    
    ob_start();
    if ( ! empty( $options ) ) {
        echo '<div id="cth_custom_surcharge_options">';
        echo '<p><strong>' . esc_html__( 'Kundenart', 'custom-tax-handler' ) . ' *</strong></p>';
        echo '<p class="form-row form-row-wide">';
        foreach ( $options as $option ) {
            if ( $option['surcharge_type'] === 'prozentual' ) {
                $display_value = number_format( $option['surcharge_value'] * 100, 2, ',', '' ) . '%';
            } else {
                $display_value = number_format( $option['surcharge_value'], 2, ',', '' ) . ' €';
            }
            echo '<label style="display:block; margin-bottom:5px;">';
            echo '<input type="radio" name="custom_surcharge" value="' . esc_attr( $option['id'] ) . '" ' . checked( $selected_option, $option['id'], false ) . ' onchange="cth_update_custom_surcharge(this.value);" /> ';
            echo esc_html( $option['surcharge_name'] ) . ' (' . esc_html( $display_value ) . ')';
            echo '</label>';
        }
        echo '</p>';
        echo '</div>';
    }
    return ob_get_clean();
}

/**
 * Gibt die Zuschlagsoptionen im Checkout aus.
 */
function cth_display_custom_surcharge_options_checkout( $checkout ) {
    echo cth_get_custom_surcharge_options_html();
}
add_action( 'woocommerce_review_order_before_order_total', 'cth_display_custom_surcharge_options_checkout' );

/**
 * Aktualisiert das Review-Fragment, sodass der Container
 * "cth_custom_surcharge_options" bei Checkout-Updates ersetzt wird.
 */
function cth_update_order_review_fragments( $fragments ) {
    $html = cth_get_custom_surcharge_options_html();
    $fragments['div#cth_custom_surcharge_options'] = $html;
    return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'cth_update_order_review_fragments' );

/**
 * AJAX-Handler: Speichert die ausgewählte Zuschlagsoption in der Session.
 */
function cth_set_custom_surcharge() {
    if ( isset( $_POST['custom_surcharge'] ) ) {
        WC()->session->set( 'custom_surcharge', intval( $_POST['custom_surcharge'] ) );
    }
    wp_die();
}
add_action( 'wp_ajax_set_custom_surcharge', 'cth_set_custom_surcharge' );
add_action( 'wp_ajax_nopriv_set_custom_surcharge', 'cth_set_custom_surcharge' );
?>
<script>
function cth_update_custom_surcharge(optionId) {
    jQuery.ajax({
        type: 'POST',
        url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
        data: {
            action: 'set_custom_surcharge',
            custom_surcharge: optionId
        },
        success: function() {
            jQuery(document.body).trigger('update_checkout');
        }
    });
}
</script>