<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Zeigt im Checkout dynamisch die Zuschlagsoptionen als Radio-Buttons an,
 * basierend auf den Einträgen in der Datenbanktabelle wp_custom_tax_surcharge_handler.
 */
function cth_display_custom_surcharge_options_checkout( $checkout ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
    $options = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A );
    
    // Lade den aktuell ausgewählten Wert aus der Session (als ID, Standard: 0)
    $selected_option = WC()->session->get( 'custom_surcharge', 0 );
    
    if ( ! empty( $options ) ) {
        echo '<p><strong>' . esc_html__( 'Kundenart', 'custom-tax-handler' ) . ' *</strong></p>';
        echo '<p class="form-row form-row-wide">';
        foreach ( $options as $option ) {
            // Bestimme den anzuzeigenden Zuschlagswert:
            if ( $option['surcharge_type'] === 'prozentual' ) {
                // Der in der DB gespeicherte Wert ist ein Dezimalwert, z. B. 0.25 → 25,00%
                $display_value = number_format( $option['surcharge_value'] * 100, 2, ',', '' ) . '%';
            } else {
                // Fest: als Währungswert mit zwei Nachkommastellen und €-Symbol
                $display_value = number_format( $option['surcharge_value'], 2, ',', '' ) . ' €';
            }
            echo '<label style="display:block; margin-bottom:5px;">';
            echo '<input type="radio" name="custom_surcharge" value="' . esc_attr( $option['id'] ) . '" ' . checked( $selected_option, $option['id'], false ) . ' onchange="updateCustomSurcharge(this.value);" /> ';
            echo esc_html( $option['surcharge_name'] ) . ' (' . esc_html( $display_value ) . ')';
            echo '</label>';
        }
        echo '</p>';
        ?>
        <script>
            function updateCustomSurcharge(optionId) {
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
        <?php
    }
}
add_action( 'woocommerce_review_order_before_order_total', 'cth_display_custom_surcharge_options_checkout' );

/**
 * AJAX-Handler: Speichert die ausgewählte Zuschlagsoption in der Session.
 */
add_action( 'wp_ajax_set_custom_surcharge', 'cth_set_custom_surcharge' );
add_action( 'wp_ajax_nopriv_set_custom_surcharge', 'cth_set_custom_surcharge' );
function cth_set_custom_surcharge() {
    if ( isset( $_POST['custom_surcharge'] ) ) {
        WC()->session->set( 'custom_surcharge', intval( $_POST['custom_surcharge'] ) );
    }
    wp_die();
}