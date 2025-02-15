<?php
if (!defined('ABSPATH')) {
    exit;
}

// Menüeintrag hinzufügen
add_action('admin_menu', function() {
    add_menu_page(
        'Custom Tax Handler Einstellungen',
        'Custom Tax Handler',
        'manage_options',
        'custom-tax-handler-settings',
        'cth_render_settings_page',
        'dashicons-calculator',
        90
    );
});

// Einstellungsseite rendern
function cth_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Custom Tax Handler Einstellungen</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cth_settings_group');
            do_settings_sections('custom-tax-handler-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Einstellungen registrieren
add_action('admin_init', function() {
    register_setting('cth_settings_group', 'cth_surcharge_percentage');
    add_settings_section('cth_main_settings', 'Allgemeine Einstellungen', null, 'custom-tax-handler-settings');
    
    add_settings_field(
        'cth_surcharge_percentage',
        'Zuschlag in % für Privatpersonen/Kommerzielle Nutzer',
        function() {
            $value = get_option('cth_surcharge_percentage', 10);
            echo '<input type="number" name="cth_surcharge_percentage" value="' . esc_attr($value) . '" /> %';
        },
        'custom-tax-handler-settings',
        'cth_main_settings'
    );
});