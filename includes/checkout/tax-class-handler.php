<?php
/*
 * tax-class-handler.php
 *
 * Diese Datei stellt Funktionen bereit, um im Bestell-Backend ein Dropdown zur Auswahl der Steuerklasse anzuzeigen.
 * Dabei werden die verfügbaren WooCommerce-Steuerklassen dynamisch abgerufen.
 *
 * Funktionen:
 * - cth_render_tax_class_dropdown(): Gibt das Dropdown zur Auswahl der Steuerklasse aus.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_render_tax_class_dropdown( $current_tax_class ) {
    $tax_classes = WC_Tax::get_tax_classes();
    array_unshift( $tax_classes, 'standard-rate' );
    echo '<select name="cth_tax_class" id="cth_tax_class">';
    foreach ( $tax_classes as $tax_class ) {
        $selected = ( $current_tax_class == $tax_class ) ? 'selected' : '';
        echo '<option value="' . esc_attr( $tax_class ) . '" ' . $selected . '>' . esc_html( $tax_class ) . '</option>';
    }
    echo '</select>';
}