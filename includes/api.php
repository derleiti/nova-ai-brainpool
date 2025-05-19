<?php
// KI-Chat Antwort verarbeiten (OpenAI oder Ollama)
function nova_ai_chat_response($user_message) {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url  = get_option('nova_ai_api_url', '');
    $api_key  = get_option('nova_ai_api_key', '');
    $model    = get_option('nova_ai_model', 'zephyr');
    $temperature = floatval(get_option('nova_ai_temperature', 0.7));

    if (empty($user_message)) {
        return __('Nova AI: Keine Eingabe übergeben.', 'nova-ai-brainpool');
    }

    if ($api_type === 'openai') {
        if (empty($api_key)) {
            return __('Nova AI: Kein API-Key für OpenAI vorhanden.', 'nova-ai-brainpool');
        }

        $api_url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => $model ?: 'gpt-3.5-turbo',
            'messages' => [['role' => 'user', 'content' => $user_message]],
            'temperature' => $temperature,
            'max_tokens' => 250
        ];

        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode($data)
        ];
    } else {
        // ollama oder custom
        $resolved_model = $model === 'custom' && defined('OLLAMA_MODEL') ? OLLAMA_MODEL : $model;
        $resolved_url   = defined('OLLAMA_CHAT_URL') ? OLLAMA_CHAT_URL : $api_url;

        if (empty($resolved_url)) {
            return __('Nova AI: Keine API-URL für lokalen Zugriff definiert.', 'nova-ai-brainpool');
        }

        $data = [
            'model' => $resolved_model,
            'messages' => [['role' => 'user', 'content' => $user_message]],
            'temperature' => $temperature
        ];

        $args = [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($data)
        ];
    }

    $response = wp_remote_post($api_url ?? $resolved_url, $args);

    if (is_wp_error($response)) {
        return 'Nova AI Error: ' . $response->get_error_message();
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($result['choices'][0]['message']['content'])) {
        return __('Nova AI: Ungültige Antwort von der API.', 'nova-ai-brainpool');
    }

    return $result['choices'][0]['message']['content'];
}
?>
