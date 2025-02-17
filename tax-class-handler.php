<?php
/*
 * tax-class-handler.php
 *
 * Erzeugt ein Dropdown-Menü zur Auswahl der Steuerklasse auf der Bestelldetailsseite im Adminbereich.
 * Die möglichen Steuerklassen werden dynamisch über WooCommerce (WC_Tax::get_tax_classes()) ermittelt.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_render_tax_class_dropdown( $current_tax_class = '' ) {
    // Hole alle Steuerklassen aus WooCommerce
    $tax_classes = WC_Tax::get_tax_classes();
    // Standardsteuerklasse hinzufügen
    $tax_classes = array_merge( array( '' => __( 'Standard', 'cth' ) ), $tax_classes );
    ?>
    <select name="cth_tax_class" id="cth_tax_class">
        <?php foreach ( $tax_classes as $key => $label ) : ?>
            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_tax_class, $key ); ?>>
                <?php echo esc_html( $label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}