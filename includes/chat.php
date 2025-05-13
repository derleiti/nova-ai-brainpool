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
    // Get request parameters
    $parameters = $request->get_json_params();
    if (empty($parameters)) {
        $parameters = $request->get_params();
    }
    
    $prompt = isset($parameters['prompt']) ? sanitize_text_field($parameters['prompt']) : '';
    if (empty($prompt)) {
        $prompt = isset($parameters['message']) ? sanitize_text_field($parameters['message']) : '';
    }
    
    if (empty($prompt)) {
        return array('reply' => 'Error: No message provided.');
    }
    
    // Log the request if debug is enabled
    if (function_exists('nova_ai_log')) {
        nova_ai_log('Chat request received: ' . $prompt);
    }
    
    // Get AI provider type
    $provider = get_option('nova_ai_api_type', 'ollama');
    
    if ($provider === 'ollama') {
        return nova_ai_ollama_chat($prompt);
    } else {
        return nova_ai_openai_chat($prompt);
    }
}

// Ollama Integration
function nova_ai_ollama_chat($prompt) {
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $model = get_option('nova_ai_model', 'zephyr'); // Default to zephyr
    $system_prompt = get_option('nova_ai_system_prompt', 'You are Nova, a helpful AI assistant for AILinux users.');
    $temperature = floatval(get_option('nova_ai_temperature', 0.7));
    $max_tokens = intval(get_option('nova_ai_max_tokens', 250));
    
    // Add knowledge base context to the prompt
    $full_prompt = nova_ai_prepend_knowledge($prompt);
    
    // Prepare the request body for Ollama
    // Note: Different models might need different formats
    $request_body = array(
        'model' => $model,
        'prompt' => $full_prompt,
        'system' => $system_prompt,
        'temperature' => $temperature,
        'max_tokens' => $max_tokens,
        'stream' => false
    );
    
    // Log the actual request if debug is enabled
    if (function_exists('nova_ai_log')) {
        nova_ai_log('Ollama request: ' . json_encode($request_body));
    }
    
    // Send request to Ollama
    $response = wp_remote_post($api_url, array(
        'body' => json_encode($request_body),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 30, // Longer timeout for model responses
    ));
    
    // Check for errors
    if (is_wp_error($response)) {
        if (function_exists('nova_ai_log')) {
            nova_ai_log('Ollama error: ' . $response->get_error_message(), 'error');
        }
        return array('reply' => 'Error connecting to Ollama: ' . esc_html($response->get_error_message()));
    }
    
    // Get the response
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    // Log the response if debug is enabled
    if (function_exists('nova_ai_log')) {
        nova_ai_log('Ollama response: ' . $body);
    }
    
    // Check for Ollama API response format
    if (isset($result['response'])) {
        // Standard Ollama API format
        return array('reply' => $result['response']);
    } elseif (isset($result['message'])) {
        // Some models return a message object
        return array('reply' => $result['message']['content']);
    } else {
        // Unknown response format
        return array('reply' => 'Error: Unexpected response format from Ollama.');
    }
}

// OpenAI Integration
function nova_ai_openai_chat($prompt) {
    $api_key = get_option('nova_ai_api_key', '');
    if (empty($api_key)) {
        return array('reply' => 'Error: OpenAI API key not configured.');
    }
    
    $api_url = 'https://api.openai.com/v1/chat/completions';
    $model = get_option('nova_ai_openai_model', 'gpt-3.5-turbo');
    $system_prompt = get_option('nova_ai_system_prompt', 'You are Nova, a helpful AI assistant for AILinux users.');
    $temperature = floatval(get_option('nova_ai_temperature', 0.7));
    $max_tokens = intval(get_option('nova_ai_max_tokens', 250));
    
    // Prepare messages array with system prompt and user prompt
    $messages = array(
        array('role' => 'system', 'content' => $system_prompt)
    );
    
    // Add knowledge base context
    $kb = nova_ai_filter_relevant_knowledge(nova_ai_knowledge_base(), $prompt, 5);
    if (!empty($kb)) {
        $context = "Here is some relevant information:\n\n";
        foreach ($kb as $item) {
            $context .= "Q: {$item['question']}\nA: {$item['answer']}\n\n";
        }
        $messages[] = array('role' => 'system', 'content' => $context);
    }
    
    // Add the user's message
    $messages[] = array('role' => 'user', 'content' => $prompt);
    
    // Prepare the request
    $request_body = array(
        'model' => $model,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => $max_tokens
    );
    
    // Send request to OpenAI
    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'body' => json_encode($request_body),
        'timeout' => 30
    ));
    
    // Check for errors
    if (is_wp_error($response)) {
        return array('reply' => 'Error connecting to OpenAI API: ' . esc_html($response->get_error_message()));
    }
    
    // Get the response
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    // Check if we have a valid response
    if (isset($result['choices'][0]['message']['content'])) {
        return array('reply' => $result['choices'][0]['message']['content']);
    } else {
        return array('reply' => 'Error: Unexpected response from OpenAI API.');
    }
}

// Prompt erweitern mit Knowledge (keeps original function name for compatibility)
function nova_ai_prepend_knowledge($prompt) {
    // Use relevance filtering instead of adding all knowledge items
    $kb = nova_ai_filter_relevant_knowledge(nova_ai_knowledge_base(), $prompt, 8);
    $inject = "";
    
    foreach ($kb as $item) {
        $inject .= "Q: {$item['question']}\nA: {$item['answer']}\n\n";
    }
    
    return $inject . "Q: " . $prompt . "\nA:";
}
