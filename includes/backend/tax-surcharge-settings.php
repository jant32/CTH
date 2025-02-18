<?php
/*
 * tax-surcharge-settings.php
 *
 * Diese Datei stellt die Admin-Seite bereit, auf der Administratoren die verschiedenen Kundenarten,
 * Zuschlagstypen (prozentual/fest) sowie den zugehörigen Steuerklassen verwalten können.
 * Zusätzlich gibt es einen neuen Abschnitt "Anpassungen", in dem Du einen benutzerdefinierten Tag
 * für den Zuschlag festlegen kannst.
 *
 * Die Kundenart-Einstellungen (Name, Zuschlagstyp, Zuschlagshöhe, Steuerklasse) werden in die Tabelle
 * wp_custom_tax_surcharge_handler gespeichert.
 *
 * Mit dem Abschnitt "Anpassungen" kannst Du einen Custom Fee Tag definieren, der überall dort verwendet wird,
 * wo bisher "[CTH]" als Marker genutzt wurde. Du gibst nur die Zeichenfolge (max. 8 Zeichen) ein – sie wird dann in eckigen Klammern gespeichert.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Formularverarbeitung für die Kundenart-Einstellungen und den Custom Fee Tag
if ( isset( $_POST['cth_settings_submit'] ) && check_admin_referer( 'cth_settings_nonce', 'cth_settings_nonce_field' ) ) {
    // Speichere neue Kundenart-Einträge (bestehender Code, falls vorhanden)
    // ... (hier der Code für das Speichern der Einträge in wp_custom_tax_surcharge_handler) ...

    // Speichere den Custom Fee Tag
    if ( isset( $_POST['cth_custom_fee_tag'] ) ) {
        // Hole nur maximal 8 Zeichen (strip_tags zum Sicherheitszweck)
        $custom_tag = substr( strip_tags( trim( $_POST['cth_custom_fee_tag'] ) ), 0, 8 );
        // Speichere den Tag in den Optionen (ohne eckige Klammern; diese fügen wir später hinzu)
        update_option( 'cth_custom_fee_tag', $custom_tag );
    }
}

// Vorhandene Einstellungen abrufen
global $wpdb;
$table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';
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

    <!-- Abschnitt "Anpassungen" für den Custom Fee Tag -->
    <h2>Anpassungen</h2>
    <form method="post">
        <?php wp_nonce_field( 'cth_settings_nonce', 'cth_settings_nonce_field' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="cth_custom_fee_tag">Custom Fee Tag</label></th>
                <td>
                    <?php 
                    $custom_fee_tag = get_option( 'cth_custom_fee_tag', 'CTH' );
                    // Zeige nur die innere Zeichenfolge an (ohne eckige Klammern)
                    ?>
                    <input name="cth_custom_fee_tag" type="text" id="cth_custom_fee_tag" value="<?php echo esc_attr( $custom_fee_tag ); ?>" class="regular-text" maxlength="8" placeholder="z.B. MYTAG">
                    <p class="description">Gib bis zu 8 Zeichen ein. Der Tag wird in eckigen Klammern angezeigt (z.B. [MYTAG]).</p>
                </td>
            </tr>
        </table>
        <?php submit_button( 'Anpassungen speichern', 'primary', 'cth_settings_submit' ); ?>
    </form>

    <h2>Vorhandene Einstellungen</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Kundenart Name</th>
                <th>Zuschlagstyp</th>
                <th>Zuschlagshöhe</th>
                <th>Steuerklasse</th>
                <th>Löschen</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $settings ) : ?>
                <?php foreach ( $settings as $setting ) : ?>
                    <tr>
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
                        <td>
                            <?php
                            $delete_url = add_query_arg( array(
                                'page'    => 'cth_tax_surcharge_settings',
                                'delete'  => $setting->id,
                                '_wpnonce'=> wp_create_nonce( 'cth_delete_setting_' . $setting->id )
                            ), admin_url( 'admin.php' ) );
                            ?>
                            <a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Eintrag wirklich löschen?');">Löschen</a>
                        </td>
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