<?php
if (!defined('ABSPATH')) {
    exit;
}

// Kundenarten mit Benutzerfreundlichen Namen
function get_all_customer_types() {
    return [
        'verein_ssb'     => 'Verein (im SSB Hannover)',
        'verein_non_ssb' => 'Verein (nicht Mitglied im SSB Hannover) | +5%',
        'privatperson'   => 'Privatperson | +10%',
        'kommerziell'    => 'Kommerzielle Nutzung | +15%',
        'none'           => 'Keine Kundenart gefunden'
    ];
}

// Holt den Namen der Kundenart anhand des Codes
function get_customer_type_label($customer_type) {
    $types = get_all_customer_types();
    return $types[$customer_type] ?? 'Unbekannt';
}