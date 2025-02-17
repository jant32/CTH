<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Berechnet und wendet den Kundenart-Zuschlag auf den Warenkorb an.
 *
 * @param WC_Cart $cart         Die Warenkorb-Instanz.
 * @param string  $customer_type Der Kundenart-Wert (z. B. 'verein_non_ssb', 'privatperson', 'kommerziell').
 */
function apply_customer_surcharge( $cart, $customer_type ) {
    $surcharge_name = 'Kundenart-Zuschlag';

    // Entferne vorhandene Zuschlag-Fees (falls bereits vorhanden)
    foreach ( $cart->get_fees() as $fee_key => $fee ) {
        if ( $fee->name === $surcharge_name ) {
            unset( WC()->cart->fees_api()->fees[ $fee_key ] );
        }
    }

    // Bestimme den Zuschlagsprozentsatz anhand der Kundenart
    $surcharge_percentage = 0;
    switch ( $customer_type ) {
        case 'verein_non_ssb':
            $surcharge_percentage = 0.05;
            break;
        case 'privatperson':
            $surcharge_percentage = 0.10;
            break;
        case 'kommerziell':
            $surcharge_percentage = 0.15;
            break;
        default:
            $surcharge_percentage = 0;
            break;
    }

    // Falls ein Prozentsatz definiert ist, berechne und füge den Zuschlag hinzu
    if ( $surcharge_percentage > 0 ) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        $cart->add_fee( $surcharge_name, $surcharge_amount, true );
        error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlag im Warenkorb hinzugefügt: " . $surcharge_amount . " EUR" );
    }
}

/**
 * Hook-Funktion, die beim Neuberechnen des Warenkorbs aufgerufen wird.
 * Liest den Kundenart-Wert aus der Session (Standard: 'verein_ssb') und
 * wendet den Zuschlag über die Funktion apply_customer_surcharge() an.
 *
 * @param WC_Cart $cart Die aktuelle Warenkorb-Instanz.
 */
function apply_customer_surcharge_to_cart( $cart ) {
    if ( ! $cart ) {
        return;
    }
    // Kundenart aus der Session (Standard: 'verein_ssb')
    $customer_type = WC()->session->get( 'customer_type', 'verein_ssb' );
    error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlagsberechnung im Warenkorb gestartet für Kundenart: " . $customer_type );
    apply_customer_surcharge( $cart, $customer_type );
    error_log( "[" . date("Y-m-d H:i:s") . "] DEBUG: Endgültige Gebühren im Warenkorb: " . print_r( $cart->get_fees(), true ) );
}

add_action('woocommerce_admin_order_data_after_order_details', 'cth_inline_customer_type_script');
function cth_inline_customer_type_script($order) {
    // Gib das Dropdown aus (falls noch nicht vorhanden) oder gehe davon aus, dass es bereits im Markup ist.
    // Hier gehen wir davon aus, dass das Dropdown bereits über euer bestehendes Markup ausgegeben wird.
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Falls das inline onchange-Attribut vorhanden ist, entfernen wir es, damit unser Binding greift:
        $('#customer_type').removeAttr('onchange');
        
        // Binde das Change-Event direkt an das Dropdown (das von WooCommerce als "wc-enhanced-select" initialisiert wird)
        $('#customer_type').on('change', function() {
            var customerType = $(this).val();
            var orderId = $('#post_ID').val(); // Im Admin-Bereich die Bestell-ID
            $.ajax({
                type: 'POST',
                url: ajaxurl,  // ajaxurl ist im Admin global verfügbar
                data: {
                    action: 'update_surcharge',
                    order_id: orderId,
                    customer_type: customerType
                },
                success: function(response) {
                    if (response.success) {
                        // Zum Beispiel: Seite neu laden, um die aktualisierten Daten anzuzeigen
                        location.reload();
                    } else {
                        alert('Fehler: ' + (response.data && response.data.message ? response.data.message : 'Unbekannter Fehler'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX-Fehler: ' + error);
                }
            });
        });
        
        // Falls Select2 aktiv ist (wie bei wc-enhanced-select), binde auch den select2:select-Event:
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
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Fehler: ' + (response.data && response.data.message ? response.data.message : 'Unbekannter Fehler'));
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

add_action( 'woocommerce_cart_calculate_fees', 'apply_customer_surcharge_to_cart', 20 );