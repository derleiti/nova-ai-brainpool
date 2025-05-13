<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . '/knowledge.php';

// REST API Route Registrieren
add_action('rest_api_init', function () {
    register_rest_route('nova-ai/v1', '/chat', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_chat_handler',
        'permission_callback' => '__return_true'
    ));
});

// Haupt-Handler
function nova_ai_chat_handler($request) {
    $parameters = $request->get_json_params();
    $prompt = sanitize_text_field($parameters['prompt']);

    $data = json_encode(array(
        'model' => 'mistral',
        'prompt' => nova_ai_prepend_knowledge($prompt),
        'stream' => false
    ));

    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');

    $response = wp_remote_post($api_url, array(
        'body' => $data,
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 20,
    ));

    if (is_wp_error($response)) {
        return array('reply' => '[Fehler bei Verbindung]');
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (isset($result['response'])) {
        return array('reply' => sanitize_text_field($result['response']));
    }

    return array('reply' => '[Fehler bei Antwort]');
}

// Prompt erweitern mit Knowledge
function nova_ai_prepend_knowledge($prompt) {
    $kb = nova_ai_knowledge_base();
    $inject = "";
    foreach ($kb as $item) {
        $inject .= "Q: {$item['question']}\nA: {$item['answer']}\n\n";
    }
    return $inject . "Q: " . $prompt . "\nA:";
}
