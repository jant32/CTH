<?php
if (!defined('ABSPATH')) {
    exit;
}

error_log("[" . date("Y-m-d H:i:s") . "] DEBUG: surcharge-handler.php geladen");
if (!defined('ABSPATH')) {
    exit;
}

// Zuschlag nur berechnen, wenn der Benutzer auf "Warenkorb aktualisieren" klickt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    add_action('woocommerce_cart_calculate_fees', 'apply_customer_type_surcharge', 1);
}

// Funktion zur Zuschlagsberechnung
function apply_customer_type_surcharge($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (!WC()->cart) {
        return;
    }

    $customer_type = WC()->session->get('customer_type', 'verein_ssb');
    $surcharge_name = 'Kundenart-Zuschlag';

    error_log("DEBUG: Manuelle Zuschlagsberechnung gestartet für Kundenart: " . $customer_type);

    // Vorherige Zuschläge entfernen
    foreach ($cart->get_fees() as $fee_key => $fee) {
        if ($fee->name === $surcharge_name) {
            unset(WC()->cart->fees_api()->fees[$fee_key]);
            error_log("DEBUG: Entferne vorherigen Zuschlag.");
        }
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

    if ($surcharge_percentage > 0) {
        $surcharge_amount = $cart->cart_contents_total * $surcharge_percentage;
        $cart->add_fee($surcharge_name, $surcharge_amount, true);
        error_log("DEBUG: Neuer Zuschlag hinzugefügt: " . $surcharge_amount);
    }
}