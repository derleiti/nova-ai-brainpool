<?php
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('nova-theme-engine', plugins_url('../js/nova-theme-engine.js', __FILE__), [], null, true);
    wp_enqueue_style('nova-dark', plugins_url('../css/nova-dark.css', __FILE__));
    wp_enqueue_style('nova-light', plugins_url('../css/nova-light.css', __FILE__));
});

add_action('admin_menu', function () {
    add_options_page('Nova Theme Settings', 'Nova Theme', 'manage_options', 'nova-theme', 'nova_theme_settings_page');
});

function nova_theme_settings_page() {
    ?>
    <div class="wrap">
        <h2>Nova Theme Settings</h2>
        <p>Wähle ein Standard-Theme für dein Nova Chat Interface:</p>
        <button onclick="NovaTheme.set('theme-nova-light')">🌞 Light</button>
        <button onclick="NovaTheme.set('theme-nova-dark')">🌚 Dark</button>
        <button onclick="NovaTheme.reset()">🔁 Reset</button>
    </div>
    <?php
}
