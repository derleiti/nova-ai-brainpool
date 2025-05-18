<?php
// Sicherheitsprüfung
if (!defined('ABSPATH')) {
    exit;
}

// === Scripts & Styles ===
add_action('wp_enqueue_scripts', function () {
    // CSS laden
    wp_enqueue_style(
        'nova-ai-css',
        plugin_dir_url(__DIR__) . 'assets/css/nova-ai.css'
    );

    // JavaScript laden
    wp_enqueue_script(
        'nova-ai-chat',
        plugin_dir_url(__DIR__) . 'assets/js/nova-ai-chat.js',
        ['jquery'],
        null,
        true
    );

    // Modellwahl: Option oder .env (bei "custom")
    $selected_model = get_option('nova_ai_model', 'zephyr');
    $resolved_model = ($selected_model === 'custom')
        ? (defined('OLLAMA_MODEL') ? OLLAMA_MODEL : 'zephyr')
        : $selected_model;

    // Übergabe an JavaScript
    wp_localize_script('nova-ai-chat', 'nova_ai_vars', [
        'chat_url' => defined('OLLAMA_CHAT_URL') ? OLLAMA_CHAT_URL : '',
        'model' => $resolved_model
    ]);
});
