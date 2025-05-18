<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin Interface for Nova AI
 */

// Admin-Menü hinzufügen
add_action('admin_menu', function () {
    add_menu_page(
        'Nova AI',
        'Nova AI',
        'manage_options',
        'nova-ai-brainpool',
        'nova_ai_settings_page',
        'dashicons-robot',
        100
    );

    add_submenu_page(
        'nova-ai-brainpool',
        'Chat Interface',
        'Chat Interface',
        'manage_options',
        'nova-ai-brainpool-chat',
        'nova_ai_chat_settings_page'
    );
});

// Haupt-Einstellungsseite
function nova_ai_settings_page() {
    ?>
    <div class="wrap">
        <h1>Nova AI Einstellungen</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('nova_ai_settings_group');
            do_settings_sections('nova-ai-brainpool');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Einstellungen registrieren
add_action('admin_init', function () {
    register_setting('nova_ai_settings_group', 'nova_ai_api_type');
    register_setting('nova_ai_settings_group', 'nova_ai_api_url');
    register_setting('nova_ai_settings_group', 'nova_ai_api_key');
    register_setting('nova_ai_settings_group', 'nova_ai_model');
    register_setting('nova_ai_settings_group', 'nova_ai_system_prompt');
    register_setting('nova_ai_settings_group', 'nova_ai_temperature');
    register_setting('nova_ai_settings_group', 'nova_ai_max_tokens');

    add_settings_section(
        'nova_ai_main_section',
        'Grundeinstellungen',
        null,
        'nova-ai-brainpool'
    );

    add_settings_field('nova_ai_api_type', 'API Typ', function () {
        $value = get_option('nova_ai_api_type', 'ollama');
        echo '<select name="nova_ai_api_type">
                <option value="ollama" ' . selected($value, 'ollama', false) . '>Ollama</option>
                <option value="openai" ' . selected($value, 'openai', false) . '>OpenAI</option>
              </select>';
    }, 'nova-ai-brainpool', 'nova_ai_main_section');

    add_settings_field('nova_ai_api_url', 'API URL', function () {
        $value = esc_attr(get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate'));
        echo '<input type="text" name="nova_ai_api_url" value="' . $value . '" size="50">';
    }, 'nova-ai-brainpool', 'nova_ai_main_section');

    add_settings_field('nova_ai_api_key', 'API Key (nur OpenAI)', function () {
        $value = esc_attr(get_option('nova_ai_api_key', ''));
        echo '<input type="password" name="nova_ai_api_key" value="' . $value . '" size="50">';
    }, 'nova-ai-brainpool', 'nova_ai_main_section');

    add_settings_field('nova_ai_model', 'Modellname', function () {
        $value = esc_attr(get_option('nova_ai_model', 'zephyr'));
        echo '<input type="text" name="nova_ai_model" value="' . $value . '" size="30">';
    }, 'nova-ai-brainpool', 'nova_ai_main_section');

    add_settings_field('nova_ai_system_prompt', 'System Prompt', function () {
        $value = esc_attr(get_option('nova_ai_system_prompt', 'Du bist Nova, ein hilfreicher KI Assistent für AILinux Nutzer.'));
        echo '<textarea name="nova_ai_system_prompt" rows="4" cols="70">' . $value . '</textarea>';
    }, 'nova-ai-brainpool', 'nova_ai_main_section');

    add_settings_field('nova_ai_temperature', 'Temperature', function () {
        $value = esc_attr(get_option('nova_ai_temperature', 0.7));
        echo '<input type="number" step="0.1" min="0" max="2" name="nova_ai_temperature" value="' . $value . '">';
    }, 'nova-ai-brainpool', 'nova_ai_main_section');

    add_settings_field('nova_ai_max_tokens', 'Max Tokens', function () {
        $value = esc_attr(get_option('nova_ai_max_tokens', 800));
        echo '<input type="number" name="nova_ai_max_tokens" value="' . $value . '">';
    }, 'nova-ai-brainpool', 'nova_ai_main_section');
});

// Chat Interface Settings Page
function nova_ai_chat_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>Nova AI Chat Interface</h1>';
    echo '<p>Diese Seite zeigt dir die aktuelle Chat-Statusanzeige oder Diagnoseinformationen.</p>';
    echo '<div id="nova-ai-chat-status">Lade Status...</div>';
    echo '</div>';

    ?>
    <script>
    jQuery(document).ready(function ($) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'nova_ai_check_chat_status'
            },
            success: function (response) {
                let statusDiv = $('#nova-ai-chat-status');
                if (response.success) {
                    statusDiv.html('<p style="color:green;">✓ ' + response.data.message + '</p>');
                } else {
                    statusDiv.html('<p style="color:red;">✗ Fehler: ' + response.data.message + '</p>');
                }
            },
            error: function () {
                $('#nova-ai-chat-status').html('<p style="color:red;">✗ AJAX-Fehler beim Abruf des Chat-Status</p>');
            }
        });
    });
    </script>
    <?php
}
