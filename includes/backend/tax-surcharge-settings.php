<?php
/*
 * tax-surcharge-settings.php
 *
 * Diese Datei stellt die Admin-Seite bereit, auf der Administratoren die verschiedenen Kundenarten, Zuschlagstypen (prozentual/fest) sowie den zugehörigen Steuerklassen verwalten können.
 *
 * Funktionen:
 * - cth_tax_surcharge_settings_page(): Rendert die Einstellungsseite inkl. Formular zur Eingabe neuer Einträge.
 * - cth_handle_settings_form_submission(): Verarbeitet die Formularübermittlung und speichert einen neuen Eintrag in die Datenbanktabelle wp_custom_tax_surcharge_handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_tax_surcharge_settings_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';

    // Formularverarbeitung.
    if ( isset( $_POST['cth_settings_submit'] ) && check_admin_referer( 'cth_settings_nonce', 'cth_settings_nonce_field' ) ) {
        cth_handle_settings_form_submission( $table_name );
    }

    // Vorhandene Einstellungen abrufen.
    $settings = $wpdb->get_results( "SELECT * FROM $table_name" );
    ?>
    <div class="wrap">
        <h1>Tax & Surcharge Settings</h1>
        <form method="post">
            <?php wp_nonce_field( 'cth_settings_nonce', 'cth_settings_nonce_field' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="surcharge_name">Kundenart Name</label></th>
                    <td><input name="surcharge_name" type="text" id="surcharge_name" value="" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="surcharge_type">Zuschlagstyp</label></th>
                    <td>
                        <select name="surcharge_type" id="surcharge_type" required>
                            <option value="percentage">Prozentual</option>
                            <option value="fixed">Fester Betrag</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="surcharge_value">Zuschlagshöhe</label></th>
                    <td><input name="surcharge_value" type="number" step="0.01" id="surcharge_value" value="" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="tax_class">Steuerklasse</label></th>
                    <td><input name="tax_class" type="text" id="tax_class" value="" class="regular-text" required></td>
                </tr>
            </table>
            <?php submit_button( 'Speichern', 'primary', 'cth_settings_submit' ); ?>
        </form>
        <h2>Bestehende Einstellungen</h2>
        <table class="wp-list-table widefat fixed striped cth-settings-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kundenart Name</th>
                    <th>Zuschlagstyp</th>
                    <th>Zuschlagshöhe</th>
                    <th>Steuerklasse</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $settings ) : ?>
                    <?php foreach ( $settings as $setting ) : ?>
                        <tr>
                            <td><?php echo esc_html( $setting->id ); ?></td>
                            <td><?php echo esc_html( $setting->surcharge_name ); ?></td>
                            <td><?php echo esc_html( ucfirst( $setting->surcharge_type ) ); ?></td>
                            <td>
                                <?php
                                if ( $setting->surcharge_type == 'percentage' ) {
                                    echo esc_html( $setting->surcharge_value ) . '%';
                                } else {
                                    echo '+' . esc_html( number_format( $setting->surcharge_value, 2 ) ) . '€';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html( $setting->tax_class ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5">Keine Einstellungen gefunden.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function cth_handle_settings_form_submission( $table_name ) {
    global $wpdb;
    $surcharge_name  = sanitize_text_field( $_POST['surcharge_name'] );
    $surcharge_type  = sanitize_text_field( $_POST['surcharge_type'] );
    $surcharge_value = floatval( $_POST['surcharge_value'] );
    $tax_class       = sanitize_text_field( $_POST['tax_class'] );

    // Neuen Datensatz einfügen.
    $wpdb->insert(
        $table_name,
        array(
            'surcharge_name'  => $surcharge_name,
            'surcharge_type'  => $surcharge_type,
            'surcharge_value' => $surcharge_value,
            'tax_class'       => $tax_class,
        ),
        array( '%s', '%s', '%f', '%s' )
    );
}