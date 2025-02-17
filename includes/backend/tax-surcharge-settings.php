<?php
/*
 * tax-surcharge-settings.php
 *
 * Diese Datei stellt Dir die Admin-Seite bereit, auf der Du verschiedene Kundenarten,
 * Zuschlagstypen (prozentual/fest) sowie die zugehörigen Steuerklassen verwalten kannst.
 *
 * Auf dieser Seite kannst Du:
 * - Neue Kundenarten hinzufügen, indem Du einen Namen, den Zuschlagstyp (Prozentual oder Fester Betrag),
 *   die Zuschlagshöhe und die zugehörige Steuerklasse aus den in WooCommerce hinterlegten Steueroptionen auswählst.
 * - Bestehende Kundenarten bearbeiten oder löschen.
 * - Die Steuerklasse wird als Steuersatz in Prozent angezeigt. Ein leerer Eintrag entspricht der Standardsteuer.
 *
 * In der unten stehenden Tabelle werden alle vorhandenen Kundenarten angezeigt.
 * In der letzten Spalte findest Du zwei Icons: einen Stift zum Bearbeiten und einen Mülleimer zum Löschen.
 * Zwischen den Icons ist ein kleiner Abstand.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function cth_tax_surcharge_settings_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_tax_surcharge_handler';

    // Handling für Löschvorgänge, wenn ein "delete"-Parameter übergeben wurde.
    if ( isset($_GET['delete']) && !empty($_GET['delete']) ) {
        $delete_id = intval($_GET['delete']);
        if ( isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'cth_delete_setting_' . $delete_id) ) {
            $wpdb->delete( $table_name, array( 'id' => $delete_id ), array( '%d' ) );
            echo '<div class="notice notice-success is-dismissible"><p>Eintrag gelöscht.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Nonce-Überprüfung fehlgeschlagen.</p></div>';
        }
    }

    // Prüfen, ob ein Eintrag bearbeitet werden soll.
    $edit_record = null;
    if ( isset($_GET['edit']) && !empty($_GET['edit']) ) {
        $edit_id = intval($_GET['edit']);
        $edit_record = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id) );
    }

    // Formularverarbeitung.
    if ( isset( $_POST['cth_settings_submit'] ) && check_admin_referer( 'cth_settings_nonce', 'cth_settings_nonce_field' ) ) {
        cth_handle_settings_form_submission( $table_name );
        // Nach dem Speichern wird das Bearbeiten beendet.
        $edit_record = null;
    }

    // Vorhandene Einstellungen abrufen.
    $settings = $wpdb->get_results( "SELECT * FROM $table_name" );
    ?>
    <div class="wrap">
        <h1>Tax & Surcharge Settings</h1>
        <p>
            Auf dieser Seite kannst Du Kundenarten definieren und verwalten. Füge neue Kundenarten hinzu, bearbeite oder lösche bestehende Einträge.
            Wähle dazu den Zuschlagstyp (Prozentual oder Fester Betrag) und gib die Zuschlagshöhe an. Außerdem kannst Du die zugehörige Steuerklasse
            aus den in WooCommerce hinterlegten Steueroptionen auswählen. Die Steuerklasse wird als Steuersatz in Prozent angezeigt – ein leerer Eintrag entspricht der Standardsteuer.
        </p>
        <form method="post">
            <?php wp_nonce_field( 'cth_settings_nonce', 'cth_settings_nonce_field' ); ?>
            <?php if ( $edit_record ) : ?>
                <h2>Bearbeite Deine bestehende Kundenart</h2>
            <?php else : ?>
                <h2>Neue Kundenart hinzufügen</h2>
            <?php endif; ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="surcharge_name">Kundenart Name</label></th>
                    <td>
                        <input name="surcharge_name" type="text" id="surcharge_name" value="<?php echo $edit_record ? esc_attr( $edit_record->surcharge_name ) : ''; ?>" class="regular-text cth-input" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="surcharge_type">Zuschlagstyp</label></th>
                    <td>
                        <select name="surcharge_type" id="surcharge_type" class="cth-input" required>
                            <option value="percentage" <?php echo ($edit_record && $edit_record->surcharge_type === 'percentage') ? 'selected' : ''; ?>>Prozentual</option>
                            <option value="fixed" <?php echo ($edit_record && $edit_record->surcharge_type === 'fixed') ? 'selected' : ''; ?>>Fester Betrag</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="surcharge_value">Zuschlagshöhe</label></th>
                    <td>
                        <input name="surcharge_value" type="number" step="0.01" id="surcharge_value" value="<?php echo $edit_record ? esc_attr( $edit_record->surcharge_value ) : ''; ?>" class="regular-text cth-input" required style="text-align: right;">
                        <span id="surcharge_sign"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tax_class">Steuerklasse</label></th>
                    <td>
                        <select name="tax_class" id="tax_class" class="cth-input">
                            <?php
                            // Abfrage der WooCommerce-Steuerklassen.
                            $tax_classes = WC_Tax::get_tax_classes();
                            // Standard-Steuerklasse (leerer String) hinzufügen.
                            $tax_classes = array_merge( array( '' ), $tax_classes );
                            global $wpdb;
                            $tax_table = $wpdb->prefix . 'woocommerce_tax_rates';
                            foreach ( $tax_classes as $tax_class ) {
                                $rate = $wpdb->get_var( $wpdb->prepare("SELECT tax_rate FROM $tax_table WHERE tax_rate_class = %s LIMIT 1", $tax_class) );
                                if ( $rate !== null ) {
                                    // tax_rate wird in der DB als z. B. 19.0000 gespeichert – wir wandeln es in 19% um.
                                    $display_text = floatval( $rate ) . '%';
                                } else {
                                    $display_text = '0%';
                                }
                                $selected = ($edit_record && $edit_record->tax_class === $tax_class) ? 'selected' : '';
                                echo '<option value="' . esc_attr( $tax_class ) . '" ' . $selected . '>' . esc_html( $display_text ) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
            <?php if ( $edit_record ) : ?>
                <input type="hidden" name="edit_id" value="<?php echo intval( $edit_record->id ); ?>">
                <?php submit_button( 'Kundenart aktualisieren', 'primary', 'cth_settings_submit' ); ?>
            <?php else : ?>
                <?php submit_button( 'Kundenart hinzufügen', 'primary', 'cth_settings_submit' ); ?>
            <?php endif; ?>
        </form>
        <h2>Vorhandene Kundenarten</h2>
        <table class="wp-list-table widefat fixed striped cth-settings-table">
            <thead>
                <tr>
                    <th>Kundenart Name</th>
                    <th>Zuschlagstyp</th>
                    <th>Zuschlagshöhe</th>
                    <th>Steuerklasse</th>
                    <th style="width: 80px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $settings ) : ?>
                    <?php foreach ( $settings as $setting ) : ?>
                        <tr>
                            <td><?php echo esc_html( $setting->surcharge_name ); ?></td>
                            <td>
                                <?php 
                                if ( $setting->surcharge_type == 'percentage' ) {
                                    echo 'Prozentual';
                                } elseif ( $setting->surcharge_type == 'fixed' ) {
                                    echo 'Fester Betrag';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ( $setting->surcharge_type == 'percentage' ) {
                                    echo esc_html( $setting->surcharge_value ) . '%';
                                } else {
                                    echo '+' . esc_html( number_format( $setting->surcharge_value, 2 ) ) . '€';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $tax_rate = $wpdb->get_var( $wpdb->prepare("SELECT tax_rate FROM $tax_table WHERE tax_rate_class = %s LIMIT 1", $setting->tax_class) );
                                if ( $tax_rate !== null ) {
                                    echo floatval( $tax_rate ) . '%';
                                } else {
                                    echo '0%';
                                }
                                ?>
                            </td>
                            <td style="text-align: center;">
                                <?php
                                $edit_url = add_query_arg( array(
                                    'page' => 'cth_tax_surcharge_settings',
                                    'edit' => $setting->id
                                ), admin_url( 'admin.php' ) );
                                $delete_url = add_query_arg( array(
                                    'page'    => 'cth_tax_surcharge_settings',
                                    'delete'  => $setting->id,
                                    '_wpnonce'=> wp_create_nonce( 'cth_delete_setting_' . $setting->id )
                                ), admin_url( 'admin.php' ) );
                                ?>
                                <a href="<?php echo esc_url( $edit_url ); ?>" title="Bearbeiten">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <span style="margin: 0 4px;"></span>
                                <a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Eintrag wirklich löschen?');" title="Löschen">
                                    <span class="dashicons dashicons-trash"></span>
                                </a>
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
    <?php
}

function cth_handle_settings_form_submission( $table_name ) {
    global $wpdb;
    $surcharge_name  = sanitize_text_field( $_POST['surcharge_name'] );
    $surcharge_type  = sanitize_text_field( $_POST['surcharge_type'] );
    $surcharge_value = floatval( $_POST['surcharge_value'] );
    // Erlaube leere Steuerklasse
    $tax_class       = isset($_POST['tax_class']) ? sanitize_text_field( $_POST['tax_class'] ) : '';

    if ( isset($_POST['edit_id']) && !empty($_POST['edit_id']) ) {
        $edit_id = intval($_POST['edit_id']);
        $wpdb->update(
            $table_name,
            array(
                'surcharge_name'  => $surcharge_name,
                'surcharge_type'  => $surcharge_type,
                'surcharge_value' => $surcharge_value,
                'tax_class'       => $tax_class,
            ),
            array( 'id' => $edit_id ),
            array( '%s', '%s', '%f', '%s' ),
            array( '%d' )
        );
        echo '<div class="notice notice-success is-dismissible"><p>Eintrag aktualisiert.</p></div>';
    } else {
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
        echo '<div class="notice notice-success is-dismissible"><p>Eintrag hinzugefügt.</p></div>';
    }
}