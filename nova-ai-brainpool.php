<?php
/*
Plugin Name: Nova AI Brainpool
Plugin URI: https://derleiti.de
Description: KI-Chat & Wissensdatenbank für WordPress mit .env-Unterstützung und Ollama-Support
Version: 1.0.1
Author: Markus Leitermann
Author URI: https://derleiti.de
License: GPLv2 or later
Text Domain: nova-ai-brainpool
*/

if (!defined('ABSPATH')) exit;

define('NOVA_AI_PATH', plugin_dir_path(__FILE__));
define('NOVA_AI_URL', plugin_dir_url(__FILE__));

require_once NOVA_AI_PATH . 'admin/env-loader.php';

if (is_admin()) {
    require_once NOVA_AI_PATH . 'admin/settings.php';
}

add_action('init', function() {
    wp_register_script('nova-ai-chat', NOVA_AI_URL . 'assets/chat-frontend.js', [], false, true);
    wp_register_style('nova-ai-chat-css', NOVA_AI_URL . 'assets/chat-frontend.css', [], false);
    add_shortcode('nova_ai_chat', 'nova_ai_chat_shortcode');
});

function nova_ai_chat_shortcode($atts = [], $content = null) {
    wp_enqueue_script('nova-ai-chat');
    wp_enqueue_style('nova-ai-chat-css');
    wp_localize_script('nova-ai-chat', 'nova_ai_chat_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php')
    ]);
    ob_start(); ?>
    <div id="nova-ai-chatbox">
        <div id="nova-ai-messages"></div>
        <textarea id="nova-ai-input" rows="4" placeholder="Frag die Nova KI... (Shift+Enter = neue Zeile)" autocomplete="off"></textarea>
        <button id="nova-ai-send">Senden</button>
    </div>
    <?php
    return ob_get_clean();
}

// --- AJAX-Handler für den KI-Chat ---
add_action('wp_ajax_nova_ai_chat', 'nova_ai_handle_chat_ajax');
add_action('wp_ajax_nopriv_nova_ai_chat', 'nova_ai_handle_chat_ajax');

function nova_ai_handle_chat_ajax() {
    $prompt = sanitize_text_field($_POST['prompt'] ?? '');
    $ollama_url = getenv('OLLAMA_URL') ?: 'http://localhost:11434/api/chat';
    $ollama_model = getenv('OLLAMA_MODEL') ?: 'zephyr'; // <--- Default ist jetzt "zephyr"

    if (!$prompt) {
        wp_send_json_error(['msg' => 'Kein Prompt empfangen.']);
    }

    $body = [
        'model' => $ollama_model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ]
    ];
    $args = [
        'body' => json_encode($body),
        'headers' => ['Content-Type' => 'application/json']
    ];
    $res = wp_remote_post($ollama_url, $args);

    if (is_wp_error($res)) {
        wp_send_json_error(['msg' => 'Konnte keine Verbindung zur KI aufbauen.']);
    }
    $json = json_decode(wp_remote_retrieve_body($res), true);

    if (isset($json['message']['content'])) {
        wp_send_json_success(['answer' => $json['message']['content']]);
    } else {
        wp_send_json_error(['msg' => 'Antwort unverständlich.']);
    }
    wp_die();
}
