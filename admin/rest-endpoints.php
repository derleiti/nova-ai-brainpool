<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('nova-ai/v1', '/vision', [
        'methods' => 'POST',
        'callback' => 'nova_ai_handle_vision',
        'permission_callback' => '__return_true',
    ]);
});

function nova_ai_handle_vision($request) {
    $params = $request->get_json_params();
    $prompt = $params['prompt'] ?? 'Beschreibe das Bild';
    $image = $params['image'] ?? null;

    if (!$image) {
        return new WP_REST_Response(['error' => 'Kein Bild gesendet.'], 400);
    }

    $payload = [
        'model' => defined('OLLAMA_MODEL') ? OLLAMA_MODEL : 'llava',
        'prompt' => $prompt,
        'image' => $image
    ];

    $response = wp_remote_post(OLLAMA_CHAT_URL, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($payload),
        'timeout' => 60
    ]);

    if (is_wp_error($response)) {
        return new WP_REST_Response(['error' => $response->get_error_message()], 500);
    }

    $body = wp_remote_retrieve_body($response);
    return rest_ensure_response(json_decode($body, true));
}
