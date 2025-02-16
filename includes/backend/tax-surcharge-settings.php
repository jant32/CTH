<?php
ob_start(); // Output Buffering starten

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rendert die Admin-Seite für Zuschlags‑Regeln.
 *
 * Anforderungen:
 * 1. Spalte "Kundenart" (ehemals benutzerfreundlicher Name) – muss eindeutige Werte enthalten.
 * 2. Zuschlagsart: "Fest" oder "Prozentual"
 * 3. Zuschlagswert: Bei "Fest" als €-Wert (mit zwei Nachkommastellen, Anzeige mit "€"), bei "Prozentual" als Prozentwert (Anzeige mit "%" und Umrechnung in Dezimalzahl vor dem Speichern)
 * 4. Steuerklasse: Dropdown aus WooCommerce-Steuerklassen (als Label wird der Steuersatz in % angezeigt)
 * 5. Neue Zeile wird nur über den Button "Neue Kundenart hinzufügen" hinzugefügt.
 * 6. Jede Zeile besitzt ein Lösch-Symbol.
 */
function cth_render_tax_surcharge_settings_page() {

    // Prüfe, ob WooCommerce (und WC_Tax) geladen ist.
    if ( ! class_exists( 'WC_Tax' ) ) {
        echo '<div class="error"><p>WooCommerce ist nicht aktiv oder noch nicht geladen. Bitte aktivieren Sie WooCommerce.</p></div>';
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';

    // Formularverarbeitung: Beim Klick auf "Einstellungen speichern"
    if ( isset( $_POST['cth_save_settings'] ) && check_admin_referer( 'cth_tax_surcharge_settings_nonce' ) ) {
        $surcharge_names  = isset( $_POST['surcharge_name'] ) ? $_POST['surcharge_name'] : array();
        $surcharge_types  = isset( $_POST['surcharge_type'] ) ? $_POST['surcharge_type'] : array();
        $surcharge_values = isset( $_POST['surcharge_value'] ) ? $_POST['surcharge_value'] : array();
        $tax_classes_input = isset( $_POST['tax_class'] ) ? $_POST['tax_class'] : array();

        $errors = array();
        $data   = array();
        $row_count = count( $surcharge_names );
        $names_used = array(); // Für die Eindeutigkeitsprüfung
        for ( $i = 0; $i < $row_count; $i++ ) {
            $name     = trim( $surcharge_names[ $i ] );
            $type     = trim( $surcharge_types[ $i ] );
            $value    = trim( $surcharge_values[ $i ] );
            $taxClass = trim( $tax_classes_input[ $i ] );

            // Überspringe komplett leere Zeilen (alle Felder leer)
            if ( $name === '' && $type === '' && $value === '' && $taxClass === '' ) {
                continue;
            }
            // Alle Felder müssen gefüllt sein
            if ( $name === '' || $type === '' || $value === '' || $taxClass === '' ) {
                $errors[] = "Alle Spalten in Zeile " . ( $i + 1 ) . " müssen ausgefüllt sein.";
                continue;
            }
            // Eindeutigkeit prüfen: Kundenart muss einzigartig sein
            if ( in_array( strtolower( $name ), $names_used ) ) {
                $errors[] = "Die Kundenart '$name' in Zeile " . ( $i + 1 ) . " ist nicht eindeutig.";
                continue;
            } else {
                $names_used[] = strtolower( $name );
            }
            // Zuschlagswert muss numerisch sein (0 ist erlaubt)
            if ( ! is_numeric( $value ) ) {
                $errors[] = "Der Zuschlagswert in Zeile " . ( $i + 1 ) . " muss eine Zahl sein.";
                continue;
            }
            // Wenn Zuschlagsart prozentual ist, rechnen wir den eingegebenen Wert (z. B. 25) in Dezimal um (0,25)
            if ( $type === 'prozentual' ) {
                $numeric_value = floatval( $value ) / 100;
            } else {
                // Bei festem Betrag wird der Wert als Zahl (mit zwei Nachkommastellen) gespeichert
                $numeric_value = round( floatval( $value ), 2 );
            }
            $data[] = array(
                'surcharge_name'  => $name,
                'surcharge_type'  => $type,
                'surcharge_value' => $numeric_value,
                'tax_class'       => $taxClass,
            );
        }
        if ( empty( $errors ) ) {
            // Lösche alle bisherigen Einträge und speichere die neuen
            $wpdb->query( "TRUNCATE TABLE $table_name" );
            foreach ( $data as $row ) {
                $wpdb->insert(
                    $table_name,
                    $row,
                    array( '%s', '%s', '%f', '%s' )
                );
            }
            echo '<div class="updated"><p>Einstellungen wurden erfolgreich gespeichert.</p></div>';
        } else {
            foreach ( $errors as $error ) {
                echo '<div class="error"><p>' . esc_html( $error ) . '</p></div>';
            }
        }
    }

    // Hole vorhandene Einträge
    $existing_data = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A );
    if ( ! is_array( $existing_data ) ) {
        $existing_data = json_decode( json_encode( $existing_data ), true );
    }

    // Hole die in WooCommerce definierten Steuerklassen (Dropdown-Optionen)
    $additional_tax_classes = WC_Tax::get_tax_classes();
    $tax_options = array( 'standard' => __( 'Standard', 'woocommerce' ) );
    if ( ! empty( $additional_tax_classes ) ) {
        foreach ( $additional_tax_classes as $class ) {
            $tax_options[ sanitize_title( $class ) ] = $class;
        }
    }
    ?>
    <div class="wrap">
        <h1><?php _e( 'Zuschlags-Einstellungen', 'custom-tax-handler' ); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'cth_tax_surcharge_settings_nonce' ); ?>
            <table class="widefat fixed" cellspacing="0" id="cth-surcharge-table">
                <thead>
                    <tr>
                        <th><?php _e( 'Kundenart', 'custom-tax-handler' ); ?></th>
                        <th><?php _e( 'Zuschlagsart', 'custom-tax-handler' ); ?></th>
                        <th><?php _e( 'Zuschlagswert', 'custom-tax-handler' ); ?></th>
                        <th><?php _e( 'Steuerklasse (Steuersatz in %)', 'custom-tax-handler' ); ?></th>
                        <th style="width:50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $existing_data ) ) : ?>
                        <?php foreach ( $existing_data as $row ) : ?>
                            <tr>
                                <td><input type="text" name="surcharge_name[]" value="<?php echo esc_attr( $row['surcharge_name'] ); ?>" /></td>
                                <td>
                                    <select name="surcharge_type[]">
                                        <option value="fest" <?php selected( $row['surcharge_type'], 'fest' ); ?>><?php _e( 'Fest', 'custom-tax-handler' ); ?></option>
                                        <option value="prozentual" <?php selected( $row['surcharge_type'], 'prozentual' ); ?>><?php _e( 'Prozentual', 'custom-tax-handler' ); ?></option>
                                    </select>
                                </td>
                                <td>
                                    <div class="surcharge-value-wrapper" style="position:relative;">
                                        <input type="text" name="surcharge_value[]" class="surcharge-value" value="<?php 
                                            // Bei prozentual: Wert * 100 anzeigen
                                            if ( $row['surcharge_type'] === 'prozentual' ) {
                                                echo esc_attr( number_format( $row['surcharge_value'] * 100, 2 ) );
                                            } else {
                                                echo esc_attr( number_format( $row['surcharge_value'], 2 ) );
                                            }
                                        ?>" />
                                        <span class="unit" style="position:absolute; right:5px; top:0; line-height: 32px;">
                                            <?php echo ($row['surcharge_type'] === 'prozentual') ? '%' : '€'; ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <select name="tax_class[]">
                                        <?php foreach ( $tax_options as $slug => $class_name ) : ?>
                                            <?php
                                            $lookup = ( 'standard' === $slug ) ? '' : $slug;
                                            $rates = WC_Tax::get_rates_for_tax_class( $lookup );
                                            $rate = 0;
                                            if ( ! empty( $rates ) ) {
                                                $rate_data = current( $rates );
                                                $rate = is_array( $rate_data ) ? floatval( $rate_data['tax_rate'] ) : floatval( $rate_data->tax_rate );
                                            }
                                            $display_rate = number_format( $rate, 2 ) . '%';
                                            ?>
                                            <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $row['tax_class'], $slug ); ?>>
                                                <?php echo esc_html( $display_rate ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><a href="#" class="cth-delete-row" style="color:red; text-decoration:none;">&times;</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <!-- Leere Zeile -->
                    <tr class="cth-new-row">
                        <td><input type="text" name="surcharge_name[]" value="" /></td>
                        <td>
                            <select name="surcharge_type[]">
                                <option value=""><?php _e( '-- Auswahl --', 'custom-tax-handler' ); ?></option>
                                <option value="fest"><?php _e( 'Fest', 'custom-tax-handler' ); ?></option>
                                <option value="prozentual"><?php _e( 'Prozentual', 'custom-tax-handler' ); ?></option>
                            </select>
                        </td>
                        <td>
                            <div class="surcharge-value-wrapper" style="position:relative;">
                                <input type="text" name="surcharge_value[]" class="surcharge-value" value="" />
                                <span class="unit" style="position:absolute; right:5px; top:0; line-height: 32px;"></span>
                            </div>
                        </td>
                        <td>
                            <select name="tax_class[]">
                                <?php foreach ( $tax_options as $slug => $class_name ) : ?>
                                    <?php
                                    $lookup = ( 'standard' === $slug ) ? '' : $slug;
                                    $rates = WC_Tax::get_rates_for_tax_class( $lookup );
                                    $rate = 0;
                                    if ( ! empty( $rates ) ) {
                                        $rate_data = current( $rates );
                                        $rate = is_array( $rate_data ) ? floatval( $rate_data['tax_rate'] ) : floatval( $rate_data->tax_rate );
                                    }
                                    $display_rate = number_format( $rate, 2 ) . '%';
                                    ?>
                                    <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $display_rate ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><a href="#" class="cth-delete-row" style="color:red; text-decoration:none;">&times;</a></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5"><button type="button" id="cth-add-row" class="button"><?php _e( 'Neue Kundenart hinzufügen', 'custom-tax-handler' ); ?></button></td>
                    </tr>
                </tfoot>
            </table>
            <?php submit_button( __( 'Einstellungen speichern', 'custom-tax-handler' ), 'primary', 'cth_save_settings' ); ?>
        </form>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {

         // Beim Ändern der Zuschlagsart in einer Zeile wird das Einheiten-Symbol aktualisiert
         $('#cth-surcharge-table').on('change', 'select[name="surcharge_type[]"]', function() {
              var $row = $(this).closest('tr');
              var type = $(this).val();
              var $unit = $row.find('.unit');
              if ( type === 'prozentual' ) {
                   $unit.text('%');
                   // Falls bereits ein Wert vorhanden ist, multipliziere diesen (bei Bearbeitung)
                   var $input = $row.find('input.surcharge-value');
                   var val = parseFloat($input.val());
                   if (!isNaN(val)) {
                        // Falls der Wert kleiner als 1 ist, nehmen wir an, dass er schon als Dezimal vorliegt, andernfalls konvertieren
                        if (val >= 1) {
                            $input.val( number_format(val, 2) );
                        }
                   }
              } else if ( type === 'fest' ) {
                   $unit.text('€');
                   var $input = $row.find('input.surcharge-value');
                   var val = parseFloat($input.val());
                   if (!isNaN(val)) {
                        $input.val( number_format(val, 2) );
                   }
              } else {
                   $unit.text('');
              }
         });

         // Löschen einer Zeile
         $('#cth-surcharge-table').on('click', '.cth-delete-row', function(e) {
              e.preventDefault();
              $(this).closest('tr').remove();
         });

         // Neue Zeile wird nur beim Klick auf den Button hinzugefügt
         $('#cth-add-row').on('click', function() {
              var newRow = '<tr class="cth-new-row">' +
                  '<td><input type="text" name="surcharge_name[]" value="" /></td>' +
                  '<td><select name="surcharge_type[]">' +
                      '<option value="">-- <?php _e( 'Auswählen', 'custom-tax-handler' ); ?> --</option>' +
                      '<option value="fest"><?php _e( 'Fest', 'custom-tax-handler' ); ?></option>' +
                      '<option value="prozentual"><?php _e( 'Prozentual', 'custom-tax-handler' ); ?></option>' +
                  '</select></td>' +
                  '<td><div class="surcharge-value-wrapper" style="position:relative;">' +
                      '<input type="text" name="surcharge_value[]" class="surcharge-value" value="" />' +
                      '<span class="unit" style="position:absolute; right:5px; top:0; line-height: 32px;"></span>' +
                  '</div></td>' +
                  '<td><select name="tax_class[]">';
              <?php foreach ( $tax_options as $slug => $class_name ) : 
                      $lookup = ($slug === 'standard') ? '' : $slug;
                      $rates = WC_Tax::get_rates_for_tax_class($lookup);
                      $rate = 0;
                      if(!empty($rates)){
                          $rate_data = current($rates);
                          $rate = is_array($rate_data) ? floatval($rate_data['tax_rate']) : floatval($rate_data->tax_rate);
                      }
                      $display_rate = number_format($rate, 2) . '%';
              ?>
              newRow += '<option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($display_rate); ?></option>';
              <?php endforeach; ?>
              newRow += '</select></td>' +
                  '<td><a href="#" class="cth-delete-row" style="color:red; text-decoration:none;">&times;</a></td>' +
              '</tr>';
              $('#cth-surcharge-table tbody').append(newRow);
         });
    });
    </script>
<?php
}
ob_end_flush();