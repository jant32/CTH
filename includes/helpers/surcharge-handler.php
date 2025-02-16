<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Berechnet und wendet den Kundenart-Zuschlag auf den Warenkorb an,
 * basierend auf der in der Session gespeicherten Zuschlagsregel (custom_surcharge).
 *
 * Die Zuschlagsregel wird dynamisch aus der Datenbanktabelle wp_custom_tax_surcharge_handler geladen.
 *
 * @param WC_Cart $cart Die Warenkorb-Instanz.
 */
function apply_customer_surcharge( $cart ) {
    $fee_name = 'Kundenart-Zuschlag';

    // Entferne vorhandene Zuschlag-Fees (falls vorhanden)
    foreach ( $cart->get_fees() as $fee_key => $fee ) {
        if ( $fee->get_name() === $fee_name ) {
            unset( WC()->cart->fees_api()->fees[ $fee_key ] );
        }
    }

    // Dynamische Berechnung: Hole die Regel-ID aus der Session (Standard: 0, d.h. keine Regel ausgewählt)
    $rule_id = WC()->session->get( 'custom_surcharge', 0 );
    if ( $rule_id > 0 ) {
        global $wpdb;
        $table_rule = $wpdb->prefix . 'custom_tax_surcharge_handler';
        $rule = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_rule WHERE id = %d", $rule_id ), ARRAY_A );
        if ( $rule ) {
            // Berechne den Zuschlag
            if ( $rule['surcharge_type'] === 'prozentual' ) {
                $amount = $cart->cart_contents_total * floatval( $rule['surcharge_value'] );
            } else {
                $amount = floatval( $rule['surcharge_value'] );
            }
            if ( $amount > 0 ) {
                $fee = new WC_Order_Item_Fee();
                $fee->set_name( $rule['surcharge_name'] );
                $fee->set_total( $amount );
                $fee->set_tax_status( 'taxable' );
                if ( method_exists( $fee, 'set_tax_class' ) ) {
                    $fee->set_tax_class( ( $rule['tax_class'] === 'standard' ) ? '' : $rule['tax_class'] );
                }
                $cart->add_fee( $rule['surcharge_name'], $amount, true );
                error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlag hinzugefügt: " . $amount . " EUR (Regel ID: " . $rule_id . ")" );
            }
        }
    }
}

/**
 * Hook-Funktion, die beim Neuberechnen des Warenkorbs aufgerufen wird.
 * Liest die in der Session gespeicherte Zuschlagsregel aus und wendet den Zuschlag an.
 *
 * @param WC_Cart $cart Die aktuelle Warenkorb-Instanz.
 */
function apply_customer_surcharge_to_cart( $cart ) {
    if ( ! $cart ) {
        return;
    }
    error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlagsberechnung im Warenkorb gestartet." );
    apply_customer_surcharge( $cart );
    error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Endgültige Gebühren im Warenkorb: " . print_r( $cart->get_fees(), true ) );
}

add_action( 'woocommerce_cart_calculate_fees', 'apply_customer_surcharge_to_cart', 20 );

/**
 * Optional: Ein Inline-Script im Admin-Bereich, um bei Änderung des Kundenart-Dropdowns (falls vorhanden)
 * den Zuschlag neu zu berechnen.
 * (Falls ihr den alten Ansatz beibehalten möchtet – ansonsten kann dieser Block entfernt werden.)
 */
add_action('woocommerce_admin_order_data_after_order_details', 'cth_inline_customer_type_script');
function cth_inline_customer_type_script( $order ) {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#customer_type').removeAttr('onchange');
        $('#customer_type').on('change', function() {
            var customerType = $(this).val();
            var orderId = $('#post_ID').val();
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'update_surcharge',
                    order_id: orderId,
                    customer_type: customerType
                },
                success: function(response) {
                    if ( response.success ) {
                        location.reload();
                    } else {
                        alert('Fehler: ' + ( response.data && response.data.message ? response.data.message : 'Unbekannter Fehler' ));
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX-Fehler: ' + error);
                }
            });
        });
        $('#customer_type').on('select2:select', function(e) {
            var customerType = $(this).val();
            var orderId = $('#post_ID').val();
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'update_surcharge',
                    order_id: orderId,
                    customer_type: customerType
                },
                success: function(response) {
                    if ( response.success ) {
                        location.reload();
                    } else {
                        alert('Fehler: ' + ( response.data && response.data.message ? response.data.message : 'Unbekannter Fehler' ));
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX-Fehler: ' + error);
                }
            });
        });
    });
    </script>
    <?php
}