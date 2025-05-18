<?php
if (!defined('ABSPATH')) {
    exit;
}

// === Shortcode: [nova_ai_search] ===
add_shortcode('nova_ai_search', function () {
    ob_start(); ?>
    <div id="nova-ai-chat-wrapper">
        <form id="nova-ai-chat-form">
            <input type="text" id="nova-ai-user-input" placeholder="Stell eine Frage an Zephyr ..." required />
            <input type="file" id="nova-ai-image-upload" accept="image/*" />
            <button type="submit">Senden</button>
        </form>
        <div id="nova-ai-chat-output"></div>
    </div>
<?php return ob_get_clean();
});

// === Admin-Menü ===
add_action('admin_menu', function () {
    add_options_page(
        'Nova AI Einstellungen',
        'Nova AI',
        'manage_options',
        'nova-ai-settings',
        'nova_ai_render_settings_page'
    );
});

function nova_ai_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Nova AI Einstellungen</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('nova_ai_settings_group');
            do_settings_sections('nova-ai-settings');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

// === Settings ===
add_action('admin_init', function () {
    register_setting('nova_ai_settings_group', 'nova_ai_model');

    add_settings_section(
        'nova_ai_main_section',
        'Modelleinstellungen',
        null,
        'nova-ai-settings'
    );

    add_settings_field(
        'nova_ai_model',
        'Ollama Modell',
        'nova_ai_model_field',
        'nova-ai-settings',
        'nova_ai_main_section'
    );
});

function nova_ai_model_field() {
    $value = get_option('nova_ai_model', 'zephyr'); ?>
    <select name="nova_ai_model">
        <option value="zephyr" <?php selected($value, 'zephyr'); ?>>Zephyr</option>
        <option value="llava" <?php selected($value, 'llava'); ?>>LLaVA</option>
        <option value="mistral" <?php selected($value, 'mistral'); ?>>Mistral</option>
        <option value="custom" <?php selected($value, 'custom'); ?>>Benutzerdefiniert (.env)</option>
    </select>
<?php }
