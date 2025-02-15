<?php
if (!defined('ABSPATH')) {
    exit;
}

error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: surcharge-handler.php geladen");

// Prüfen, ob der Hook mehrfach registriert wird
global $wp_filter;
if (isset($wp_filter['woocommerce_cart_calculate_fees'])) {
    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Hook 'woocommerce_cart_calculate_fees' ist registriert: " . count($wp_filter['woocommerce_cart_calculate_fees']->callbacks) . " Mal.");
}

// Doppeltes Registrieren der Funktion verhindern
remove_action('woocommerce_cart_calculate_fees', 'apply_customer_type_surcharge');
add_action('woocommerce_cart_calculate_fees', 'apply_customer_type_surcharge', 20);

// Funktion zum Anwenden des Zuschlags
function apply_customer_type_surcharge() {
    static $executed = false; // Schutzmechanismus gegen doppelte Ausführung

    if ($executed) {
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlagsberechnung übersprungen, weil sie bereits ausgeführt wurde.");
        return;
    }
    $executed = true; // Setzen, damit die Funktion nicht doppelt läuft

    if (is_admin() && !defined('DOING_AJAX')) {
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlagsberechnung im Backend übersprungen.");
        return;
    }

    // Sicherstellen, dass WooCommerce geladen ist
    if (!WC()->cart) {
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: WooCommerce-Warenkorb nicht geladen.");
        return;
    }

    $cart = WC()->cart;
    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $surcharge_name = 'Kundenart-Zuschlag';

    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlagsberechnung gestartet für Kundenart: " . $customer_type);

    // Vorhandene Zuschläge entfernen
    $existing_fees = $cart->get_fees();
    $found_existing = false;

    foreach ($existing_fees as $fee_key => $fee) {
        if ($fee->name === $surcharge_name) {
            error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Entferne alten Zuschlag (Key: $fee_key).");
            unset(WC()->cart->fees_api()->fees[$fee_key]);
            $found_existing = true;
        }
    }

    if ($found_existing) {
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Alter Zuschlag entfernt, bevor neuer hinzugefügt wird.");
    }

    // Bestimmen des Zuschlags
    $surcharge_percentage = 0;
    switch ($customer_type) {
        case 'verein_non_ssb':
            $surcharge_percentage = 0.05;
            break;
        case 'privatperson':
            $surcharge_percentage = 0.10;
            break;
        case 'kommerziell':
            $surcharge_percentage = 0.15;
            break;
    }

    // Zuschlag nur hinzufügen, wenn Prozentsatz größer als 0
    if ($surcharge_percentage > 0) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;

        // Prüfen, ob der Zuschlag nicht bereits existiert
        foreach ($cart->get_fees() as $fee) {
            if ($fee->name === $surcharge_name) {
                error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Zuschlag existiert bereits, wird nicht erneut hinzugefügt.");
                return;
            }
        }

        // Debugging: Stacktrace für das Hinzufügen eines Zuschlags
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Neuer Zuschlag hinzugefügt: " . $surcharge_amount . " EUR");
        error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Stacktrace für Zuschlag hinzufügen: " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), true));

        $cart->add_fee($surcharge_name, $surcharge_amount, true);
    }

    // Debugging: Alle aktuellen Gebühren loggen
    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Aktuelle Gebühren nach Berechnung: " . print_r($cart->get_fees(), true));
}

// Nach der Berechnung prüfen, ob der Zuschlag bestehen bleibt
add_action('woocommerce_after_calculate_totals', function() {
    error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: Endgültige Gebühren im Warenkorb: " . print_r(WC()->cart->get_fees(), true));
}, 10);