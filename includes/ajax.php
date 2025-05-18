<?php
if (!defined('ABSPATH')) exit;

// Sichere AJAX-Verarbeitung für eingeloggte & nicht eingeloggte Nutzer
add_action('wp_ajax_nova_ai_chat', 'nova_ai_chat_ajax_handler');
add_action('wp_ajax_nopriv_nova_ai_chat', 'nova_ai_chat_ajax_handler');

function nova_ai_chat_ajax_handler() {
    // Optionaler Nonce-Check für zusätzliche Sicherheit
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'nova_ai_nonce')) {
        wp_send_json_error(['error' => 'Ungültiger Sicherheits-Token']);
    }

    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    if (empty($message)) {
        wp_send_json_error(['error' => 'Leere Nachricht']);
    }

    $response = wp_remote_post(rest_url('nova-ai/v1/chat'), [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode(['prompt' => $message, 'conversation' => []]),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['error' => 'Verbindungsfehler']);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($data['reply'])) {
        wp_send_json_success(['reply' => $data['reply']]);
    } else {
        wp_send_json_error(['error' => 'Keine Antwort erhalten']);
    }
}
