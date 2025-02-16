<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prüfe, ob WooCommerce geladen ist – insbesondere die Klasse WC_Tax
if ( ! class_exists( 'WC_Tax' ) ) {
    echo '<div class="error"><p>WooCommerce ist nicht aktiv oder noch nicht geladen. Bitte überprüfen Sie Ihre WooCommerce-Installation.</p></div>';
    return;
}

global $wpdb;
$table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';

// Falls das Formular abgesendet wurde, verarbeite die Eingaben
if ( isset( $_POST['cth_save_settings'] ) ) {
    $surcharge_names   = isset( $_POST['surcharge_name'] ) ? $_POST['surcharge_name'] : array();
    $software_names    = isset( $_POST['software_name'] ) ? $_POST['software_name'] : array();
    $surcharge_types   = isset( $_POST['surcharge_type'] ) ? $_POST['surcharge_type'] : array();
    $surcharge_values  = isset( $_POST['surcharge_value'] ) ? $_POST['surcharge_value'] : array();
    $tax_classes       = isset( $_POST['tax_class'] ) ? $_POST['tax_class'] : array();

    $errors = array();
    $data   = array();
    $row_count = count( $surcharge_names );
    for ( $i = 0; $i < $row_count; $i++ ) {
        $name     = trim( $surcharge_names[ $i ] );
        $software = trim( $software_names[ $i ] );
        $type     = trim( $surcharge_types[ $i ] );
        $value    = trim( $surcharge_values[ $i ] );
        $taxClass = trim( $tax_classes[ $i ] );

        // Überspringe komplett leere Zeilen
        if ( empty( $name ) && empty( $software ) && empty( $type ) && empty( $value ) && empty( $taxClass ) ) {
            continue;
        }
        // Alle Felder müssen ausgefüllt sein
        if ( empty( $name ) || empty( $software ) || empty( $type ) || empty( $value ) || empty( $taxClass ) ) {
            $errors[] = "Alle Felder müssen in jeder Zeile ausgefüllt sein (Zeile " . ( $i + 1 ) . ").";
            continue;
        }
        if ( ! is_numeric( $value ) ) {
            $errors[] = "Der Zuschlagswert in Zeile " . ( $i + 1 ) . " muss eine Zahl sein.";
            continue;
        }
        $data[] = array(
            'surcharge_name'  => $name,
            'software_name'   => $software,
            'surcharge_type'  => $type,
            'surcharge_value' => floatval( $value ),
            'tax_class'       => $taxClass,
        );
    }
    if ( empty( $errors ) ) {
        // Lösche alle bisherigen Einträge
        $wpdb->query( "TRUNCATE TABLE $table_name" );
        // Füge die neuen Einträge ein
        foreach ( $data as $row ) {
            $wpdb->insert(
                $table_name,
                $row,
                array( '%s', '%s', '%s', '%f', '%s' )
            );
        }
        echo '<div class="updated"><p>Einstellungen wurden erfolgreich gespeichert.</p></div>';
    } else {
        foreach ( $errors as $error ) {
            echo '<div class="error"><p>' . esc_html( $error ) . '</p></div>';
        }
    }
}

// Lade bestehende Einstellungen
$existing_data = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A );

// Hole die in WooCommerce definierten Steuerklassen
$additional_tax_classes = WC_Tax::get_tax_classes();
$tax_classes = array( 'standard' => __( 'Standard', 'woocommerce' ) );
if ( ! empty( $additional_tax_classes ) ) {
    foreach ( $additional_tax_classes as $class ) {
        $tax_classes[ sanitize_title( $class ) ] = $class;
    }
}
?>
<div class="wrap">
    <h1>Steuereinstellungen für Zuschläge</h1>
    <form method="post" action="">
        <table class="widefat fixed" cellspacing="0" id="cth-surcharge-table">
            <thead>
                <tr>
                    <th>Benutzerfreundlicher Name</th>
                    <th>Software Name</th>
                    <th>Zuschlagsart</th>
                    <th>Zuschlagswert</th>
                    <th>Steuerklasse (Steuersatz in %)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $existing_data ) ) : ?>
                    <?php foreach ( $existing_data as $row ) : ?>
                        <tr>
                            <td><input type="text" name="surcharge_name[]" value="<?php echo esc_attr( $row['surcharge_name'] ); ?>" /></td>
                            <td><input type="text" name="software_name[]" value="<?php echo esc_attr( $row['software_name'] ); ?>" readonly /></td>
                            <td>
                                <select name="surcharge_type[]">
                                    <option value="fest" <?php selected( $row['surcharge_type'], 'fest' ); ?>>Fest</option>
                                    <option value="prozentual" <?php selected( $row['surcharge_type'], 'prozentual' ); ?>>Prozentual</option>
                                </select>
                            </td>
                            <td><input type="text" name="surcharge_value[]" value="<?php echo esc_attr( $row['surcharge_value'] ); ?>" /></td>
                            <td>
                                <select name="tax_class[]">
                                    <?php foreach ( $tax_classes as $slug => $label ) : ?>
                                        <?php
                                        // Für "standard" wird WooCommerce intern als leerer String genutzt.
                                        $lookup = ( 'standard' === $slug ) ? '' : $slug;
                                        $rates = WC_Tax::get_rates_for_tax_class( $lookup );
                                        $rate_percent = 0;
                                        if ( ! empty( $rates ) ) {
                                            $rate_data = current( $rates );
                                            $rate_percent = floatval( $rate_data['tax_rate'] );
                                        }
                                        $display_rate = number_format( $rate_percent, 2 ) . '%';
                                        ?>
                                        <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $row['tax_class'], $slug ); ?>>
                                            <?php echo esc_html( $display_rate ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <!-- Füge eine leere Zeile hinzu -->
                <tr class="cth-new-row">
                    <td><input type="text" name="surcharge_name[]" value="" /></td>
                    <td><input type="text" name="software_name[]" value="" readonly /></td>
                    <td>
                        <select name="surcharge_type[]">
                            <option value="">-- Auswahl --</option>
                            <option value="fest">Fest</option>
                            <option value="prozentual">Prozentual</option>
                        </select>
                    </td>
                    <td><input type="text" name="surcharge_value[]" value="" /></td>
                    <td>
                        <select name="tax_class[]">
                            <?php foreach ( $tax_classes as $slug => $label ) : ?>
                                <?php
                                $lookup = ( 'standard' === $slug ) ? '' : $slug;
                                $rates = WC_Tax::get_rates_for_tax_class( $lookup );
                                $rate_percent = 0;
                                if ( ! empty( $rates ) ) {
                                    $rate_data = current( $rates );
                                    $rate_percent = floatval( $rate_data['tax_rate'] );
                                }
                                $display_rate = number_format( $rate_percent, 2 ) . '%';
                                ?>
                                <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $display_rate ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5"><button type="button" id="cth-add-row" class="button">Neue Zeile hinzufügen</button></td>
                </tr>
            </tfoot>
        </table>
        <?php submit_button( 'Einstellungen speichern', 'primary', 'cth_save_settings' ); ?>
    </form>
</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Dynamisch neue Zeilen hinzufügen, wenn alle Felder einer Zeile ausgefüllt sind
    function addNewRowIfNeeded() {
        var addRow = true;
        $('#cth-surcharge-table tbody tr').each(function() {
            var filled = true;
            $(this).find('input, select').each(function() {
                if ($(this).val() === '') {
                    filled = false;
                    return false;
                }
            });
            if (!filled) {
                addRow = false;
                return false;
            }
        });
        if (addRow) {
            var newRow = '<tr class="cth-new-row">' +
                '<td><input type="text" name="surcharge_name[]" value="" /></td>' +
                '<td><input type="text" name="software_name[]" value="" readonly /></td>' +
                '<td><select name="surcharge_type[]">' +
                    '<option value="">-- Auswahl --</option>' +
                    '<option value="fest">Fest</option>' +
                    '<option value="prozentual">Prozentual</option>' +
                '</select></td>' +
                '<td><input type="text" name="surcharge_value[]" value="" /></td>' +
                '<td><select name="tax_class[]">';
            <?php foreach ( $tax_classes as $slug => $label ) : ?>
                newRow += '<option value="<?php echo esc_attr( $slug ); ?>"><?php 
                    $lookup = ($slug === 'standard') ? '' : $slug;
                    $rates = WC_Tax::get_rates_for_tax_class( $lookup );
                    $rate_percent = 0;
                    if (!empty($rates)) {
                        $rate_data = current($rates);
                        $rate_percent = floatval($rate_data['tax_rate']);
                    }
                    echo number_format($rate_percent, 2) . "%";
                ?></option>';
            <?php endforeach; ?>
            newRow += '</select></td></tr>';
            $('#cth-surcharge-table tbody').append(newRow);
        }
    }
    
    $('#cth-surcharge-table').on('input change', 'tr', function() {
        addNewRowIfNeeded();
    });
    
    $('#cth-add-row').on('click', function() {
        addNewRowIfNeeded();
    });
});
</script>