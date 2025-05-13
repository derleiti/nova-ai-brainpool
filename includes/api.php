<?php
// Chat API Call
function nova_ai_chat_response($user_message) {
    $api_key = get_option('nova_ai_api_key', '');
    $api_url = 'https://api.openai.com/v1/chat/completions';

    if (empty($api_key)) {
        return 'Nova AI: Kein API Key hinterlegt!';
    }

    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'user', 'content' => $user_message]
        ],
        'temperature' => 0.7,
        'max_tokens' => 250
    ];

    $args = [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode($data)
    ];

    $response = wp_remote_post($api_url, $args);

    if (is_wp_error($response)) {
        return 'Nova AI Error: ' . $response->get_error_message();
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    return $result['choices'][0]['message']['content'] ?? 'Nova AI: Keine Antwort erhalten.';
}
?>
