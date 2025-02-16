<?php
ob_start(); // Output Buffering starten

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Rendert die Admin-Seite für individuelle Zuschlags-Regeln.
 *
 * Hier kann der Administrator individuelle Zuschlags-Regeln anlegen:
 * 1. Benutzerfreundlicher Name
 * 2. Zuschlagsart (fest oder prozentual)
 * 3. Zuschlagswert (numerisch – 0 wird nun als gültig akzeptiert)
 * 4. Steuerklasse (Dropdown, basierend auf WooCommerce-Steuerklassen, wobei als Label der jeweilige Steuersatz in % angezeigt wird)
 *
 * Neue Zeilen werden nur über den Button "Neue Kundenart hinzufügen" hinzugefügt.
 * Alle Felder einer Zeile müssen ausgefüllt sein, sonst wird eine Fehlermeldung angezeigt.
 * Die Eintragungen werden in der Datenbanktabelle wp_custom_tax_surcharge_handler gespeichert.
 */
function cth_render_tax_surcharge_settings_page() {

    // Prüfe, ob WooCommerce geladen ist – insbesondere WC_Tax (wird für Steuerklassen benötigt)
    if ( ! class_exists( 'WC_Tax' ) ) {
        echo '<div class="error"><p>WooCommerce ist nicht aktiv oder noch nicht geladen. Bitte aktivieren Sie WooCommerce.</p></div>';
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
    
    // Formularverarbeitung: Wenn der "Einstellungen speichern" Button gedrückt wurde
    if ( isset( $_POST['cth_save_settings'] ) && check_admin_referer( 'cth_tax_surcharge_settings_nonce' ) ) {
        // Wir holen nur die relevanten Felder; das Feld software_name wird hier nicht mehr benötigt (kann später automatisch gefüllt werden)
        $surcharge_names   = isset( $_POST['surcharge_name'] ) ? $_POST['surcharge_name'] : array();
        $surcharge_types   = isset( $_POST['surcharge_type'] ) ? $_POST['surcharge_type'] : array();
        $surcharge_values  = isset( $_POST['surcharge_value'] ) ? $_POST['surcharge_value'] : array();
        $tax_classes_input = isset( $_POST['tax_class'] ) ? $_POST['tax_class'] : array();

        $errors = array();
        $data   = array();
        $row_count = count( $surcharge_names );
        for ( $i = 0; $i < $row_count; $i++ ) {
            $name     = trim( $surcharge_names[ $i ] );
            $type     = trim( $surcharge_types[ $i ] );
            $value    = trim( $surcharge_values[ $i ] );
            $taxClass = trim( $tax_classes_input[ $i ] );
            
            // Überspringe komplett leere Zeilen (alle Felder leer)
            if ( $name === '' && $type === '' && $value === '' && $taxClass === '' ) {
                continue;
            }
            // Prüfe, ob alle Felder (außer software_name) ausgefüllt sind
            if ( $name === '' || $type === '' || $value === '' || $taxClass === '' ) {
                $errors[] = "Alle Felder in Zeile " . ( $i + 1 ) . " müssen ausgefüllt sein.";
                continue;
            }
            // Zulassen, dass der Zuschlagswert 0 ist – also prüfen wir explizit auf leeren String
            if ( $value === '' || ! is_numeric( $value ) ) {
                $errors[] = "Der Zuschlagswert in Zeile " . ( $i + 1 ) . " muss eine Zahl sein.";
                continue;
            }
            $data[] = array(
                'surcharge_name'  => $name,
                'surcharge_type'  => $type,
                'surcharge_value' => floatval( $value ),
                'tax_class'       => $taxClass,
            );
        }
        if ( empty( $errors ) ) {
            // Lösche alle bisherigen Einträge
            $wpdb->query( "TRUNCATE TABLE $table_name" );
            // Füge alle neuen Einträge ein
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
    
    // Lade bereits gespeicherte Daten als assoziatives Array
    $existing_data = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A );
    if ( ! is_array( $existing_data ) ) {
        $existing_data = json_decode( json_encode( $existing_data ), true );
    }
    
    // Hole die in WooCommerce definierten Steuerklassen:
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
                        <th><?php _e( 'Benutzerfreundlicher Name', 'custom-tax-handler' ); ?></th>
                        <th><?php _e( 'Zuschlagsart', 'custom-tax-handler' ); ?></th>
                        <th><?php _e( 'Zuschlagswert', 'custom-tax-handler' ); ?></th>
                        <th><?php _e( 'Steuerklasse (Steuersatz in %)', 'custom-tax-handler' ); ?></th>
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
                                <td><input type="text" name="surcharge_value[]" value="<?php echo esc_attr( $row['surcharge_value'] ); ?>" /></td>
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
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <!-- Eine leere Zeile (wird nur über den Button hinzugefügt) -->
                    <tr class="cth-new-row">
                        <td><input type="text" name="surcharge_name[]" value="" /></td>
                        <td>
                            <select name="surcharge_type[]">
                                <option value="">-- <?php _e( 'Auswählen', 'custom-tax-handler' ); ?> --</option>
                                <option value="fest"><?php _e( 'Fest', 'custom-tax-handler' ); ?></option>
                                <option value="prozentual"><?php _e( 'Prozentual', 'custom-tax-handler' ); ?></option>
                            </select>
                        </td>
                        <td><input type="text" name="surcharge_value[]" value="" /></td>
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
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><button type="button" id="cth-add-row" class="button"><?php _e( 'Neue Kundenart hinzufügen', 'custom-tax-handler' ); ?></button></td>
                    </tr>
                </tfoot>
            </table>
            <?php submit_button( __( 'Einstellungen speichern', 'custom-tax-handler' ), 'primary', 'cth_save_settings' ); ?>
        </form>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
         // Neue Zeile wird nur beim Klick auf den Button hinzugefügt
         $('#cth-add-row').on('click', function() {
              var newRow = '<tr class="cth-new-row">' +
                  '<td><input type="text" name="surcharge_name[]" value="" /></td>' +
                  '<td><select name="surcharge_type[]">' +
                      '<option value="">-- <?php _e( 'Auswählen', 'custom-tax-handler' ); ?> --</option>' +
                      '<option value="fest"><?php _e( 'Fest', 'custom-tax-handler' ); ?></option>' +
                      '<option value="prozentual"><?php _e( 'Prozentual', 'custom-tax-handler' ); ?></option>' +
                  '</select></td>' +
                  '<td><input type="text" name="surcharge_value[]" value="" /></td>' +
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
              newRow += '</select></td></tr>';
              $('#cth-surcharge-table tbody').append(newRow);
         });
    });
    </script>
<?php
}
ob_end_flush();