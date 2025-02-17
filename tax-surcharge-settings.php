<?php
/*
 * tax-surcharge-settings.php
 *
 * Zeigt im Adminbereich eine Seite zur Verwaltung der Kundenarten, Zuschläge und Steuerklassen an.
 * Hier können Einträge (bestehend aus surcharge_name, surcharge_type, surcharge_value und tax_class) hinzugefügt, bearbeitet und gespeichert werden.
 * Die Daten werden in der Datenbanktabelle wp_custom_tax_surcharge_handler abgelegt.
 */

// Sicherstellen, dass wir im Adminbereich sind und die nötigen Funktionen geladen wurden
if ( ! is_admin() ) {
    return;
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
    return;
}

global $wpdb;
$table   = $wpdb->prefix . 'custom_tax_surcharge_handler';

// Speichern der Einstellungen
if ( isset( $_POST['cth_settings_nonce'] ) && wp_verify_nonce( $_POST['cth_settings_nonce'], 'cth_save_settings' ) ) {
    // Als Beispiel: Tabelle leeren und neue Einträge speichern
    $wpdb->query( "TRUNCATE TABLE $table" );
    
    if ( isset( $_POST['cth_entries'] ) && is_array( $_POST['cth_entries'] ) ) {
        foreach ( $_POST['cth_entries'] as $entry ) {
            $surcharge_name  = sanitize_text_field( $entry['surcharge_name'] );
            $surcharge_type  = in_array( $entry['surcharge_type'], array( 'percent', 'fixed' ) ) ? $entry['surcharge_type'] : 'percent';
            $surcharge_value = floatval( $entry['surcharge_value'] );
            $tax_class       = sanitize_text_field( $entry['tax_class'] );
            $wpdb->insert(
                $table,
                array(
                    'surcharge_name'  => $surcharge_name,
                    'surcharge_type'  => $surcharge_type,
                    'surcharge_value' => $surcharge_value,
                    'tax_class'       => $tax_class,
                ),
                array( '%s', '%s', '%f', '%s' )
            );
        }
    }
    echo '<div class="updated"><p>' . __( 'Einstellungen gespeichert.', 'cth' ) . '</p></div>';
}

$entries = $wpdb->get_results( "SELECT * FROM $table" );
?>

<div class="wrap">
    <h1><?php _e( 'Tax Surcharge Settings', 'cth' ); ?></h1>
    <form method="post" action="">
        <?php wp_nonce_field( 'cth_save_settings', 'cth_settings_nonce' ); ?>
        <table class="cth-admin-table">
            <thead>
                <tr>
                    <th><?php _e( 'Kundenart', 'cth' ); ?></th>
                    <th><?php _e( 'Zuschlagsart', 'cth' ); ?></th>
                    <th><?php _e( 'Zuschlagshöhe', 'cth' ); ?></th>
                    <th><?php _e( 'Steuerklasse', 'cth' ); ?></th>
                </tr>
            </thead>
            <tbody id="cth-settings-body">
                <?php if ( $entries ) : foreach ( $entries as $entry ) : ?>
                    <tr>
                        <td><input type="text" name="cth_entries[][surcharge_name]" value="<?php echo esc_attr( $entry->surcharge_name ); ?>"></td>
                        <td>
                            <select name="cth_entries[][surcharge_type]">
                                <option value="percent" <?php selected( $entry->surcharge_type, 'percent' ); ?>><?php _e( 'Prozentual', 'cth' ); ?></option>
                                <option value="fixed" <?php selected( $entry->surcharge_type, 'fixed' ); ?>><?php _e( 'Fester Betrag', 'cth' ); ?></option>
                            </select>
                        </td>
                        <td><input type="number" step="0.01" name="cth_entries[][surcharge_value]" value="<?php echo esc_attr( $entry->surcharge_value ); ?>"></td>
                        <td><input type="text" name="cth_entries[][tax_class]" value="<?php echo esc_attr( $entry->tax_class ); ?>"></td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr>
                        <td><input type="text" name="cth_entries[][surcharge_name]" value=""></td>
                        <td>
                            <select name="cth_entries[][surcharge_type]">
                                <option value="percent"><?php _e( 'Prozentual', 'cth' ); ?></option>
                                <option value="fixed"><?php _e( 'Fester Betrag', 'cth' ); ?></option>
                            </select>
                        </td>
                        <td><input type="number" step="0.01" name="cth_entries[][surcharge_value]" value=""></td>
                        <td><input type="text" name="cth_entries[][tax_class]" value=""></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <p>
            <button type="button" id="cth-add-row" class="button"><?php _e( 'Zeile hinzufügen', 'cth' ); ?></button>
        </p>
        <p>
            <input type="submit" value="<?php _e( 'Einstellungen speichern', 'cth' ); ?>" class="button-primary">
        </p>
    </form>
</div>
<script>
jQuery(document).ready(function($) {
    $('#cth-add-row').on('click', function() {
        var newRow = '<tr>' +
            '<td><input type="text" name="cth_entries[][surcharge_name]" value=""></td>' +
            '<td><select name="cth_entries[][surcharge_type]">' +
                '<option value="percent"><?php _e("Prozentual", "cth"); ?></option>' +
                '<option value="fixed"><?php _e("Fester Betrag", "cth"); ?></option>' +
            '</select></td>' +
            '<td><input type="number" step="0.01" name="cth_entries[][surcharge_value]" value=""></td>' +
            '<td><input type="text" name="cth_entries[][tax_class]" value=""></td>' +
        '</tr>';
        $('#cth-settings-body').append(newRow);
    });
});
</script>