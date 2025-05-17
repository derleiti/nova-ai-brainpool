<?php
if (!defined('ABSPATH')) exit;

/**
 * Chat API functionality for Nova AI Brainpool
 */

// Register REST API routes
add_action('rest_api_init', function() {
    // Main chat endpoint
    register_rest_route('nova-ai/v1', '/chat', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_chat_handler',
        'permission_callback' => '__return_true'
    ));
    
    // Stats tracking endpoint
    register_rest_route('nova-ai/v1', '/chat/stats', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_chat_stats_handler',
        'permission_callback' => '__return_true'
    ));
});

/**
 * Handle chat requests via REST API
 */
function nova_ai_chat_handler($request) {
    try {
        // Get and validate parameters
        $parameters = $request->get_json_params();
        $prompt = isset($parameters['prompt']) ? sanitize_text_field($parameters['prompt']) : '';
        $conversation = isset($parameters['conversation']) ? $parameters['conversation'] : [];
        
        if (empty($prompt)) {
            return new WP_Error('empty_prompt', 'Bitte gib eine Nachricht ein.', ['status' => 400]);
        }
        
        // Rate limiting
        $rate_limit = apply_filters('nova_ai_rate_limit', 10); // 10 requests per minute by default
        if (nova_ai_check_rate_limit($rate_limit)) {
            return new WP_Error('rate_limit', 'Rate-Limit erreicht. Bitte warte einen Moment, bevor du weitere Nachrichten sendest.', ['status' => 429]);
        }
        
        // Get AI settings
        $api_type = get_option('nova_ai_api_type', 'ollama');
        $model = get_option('nova_ai_model', 'zephyr');
        $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
        $api_key = get_option('nova_ai_api_key', '');
        $system_prompt = get_option('nova_ai_system_prompt', 'Du bist Nova, ein hilfreicher KI Assistent für AILinux Nutzer.');
        $temperature = get_option('nova_ai_temperature', 0.7);
        $max_tokens = get_option('nova_ai_max_tokens', 800);
        
        // Add knowledge base content if available
        $relevant_knowledge = '';
        if (function_exists('nova_ai_get_relevant_knowledge')) {
            $relevant_knowledge = nova_ai_get_relevant_knowledge($prompt);
            if (!empty($relevant_knowledge)) {
                $system_prompt .= "\n\n" . $relevant_knowledge;
            }
        }
        
        // Call the appropriate API based on provider
        if ($api_type === 'ollama') {
            $response = nova_ai_ollama_request($prompt, $model, $api_url, $system_prompt, $temperature, $max_tokens, $conversation);
        } else {
            $response = nova_ai_openai_request($prompt, $api_key, $system_prompt, $temperature, $max_tokens, $conversation);
        }
        
        // Update stats
        nova_ai_update_chat_stats();
        
        // Return response
        return [
            'reply' => $response
        ];
    } catch (Exception $e) {
        nova_ai_log('API Error: ' . $e->getMessage(), 'error');
        return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
    }
}

/**
 * Make request to Ollama API
 */
function nova_ai_ollama_request($prompt, $model, $api_url, $system_prompt, $temperature, $max_tokens, $conversation = []) {
    // Format the request based on conversation history or single message
    if (!empty($conversation)) {
        // Format with conversation history
        $messages = [];
        
        // Add system message
        $messages[] = [
            'role' => 'system',
            'content' => $system_prompt
        ];
        
        // Add conversation history
        foreach ($conversation as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }
        
        // Build request body
        $request_body = [
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
            'temperature' => (float) $temperature,
            'max_tokens' => (int) $max_tokens
        ];
    } else {
        // Simple prompt with system instruction
        $request_body = [
            'model' => $model,
            'prompt' => $system_prompt . "\n\nUser: " . $prompt . "\n\nAssistant:",
            'stream' => false,
            'temperature' => (float) $temperature,
            'max_tokens' => (int) $max_tokens
        ];
    }
    
    // Make the request to Ollama
    $response = wp_remote_post($api_url, [
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($request_body),
        'timeout' => 60
    ]);
    
    // Handle response
    if (is_wp_error($response)) {
        nova_ai_log('Ollama API Error: ' . $response->get_error_message(), 'error');
        throw new Exception('Fehler bei der Verbindung zum Ollama-Server: ' . $response->get_error_message());
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    // Check for errors in the API response
    if (isset($data['error'])) {
        nova_ai_log('Ollama API Error: ' . $data['error'], 'error');
        throw new Exception('Ollama API Fehler: ' . $data['error']);
    }
    
    // Check if we have a valid response
    if (!isset($data['response']) && !isset($data['message']['content'])) {
        nova_ai_log('Ollama API Error: Unexpected response format', 'error');
        throw new Exception('Unerwartetes Antwortformat vom Ollama-Server.');
    }
    
    // Return the response text
    if (isset($data['response'])) {
        return $data['response'];
    } else {
        return $data['message']['content'];
    }
}

/**
 * Make request to OpenAI API
 */
function nova_ai_openai_request($prompt, $api_key, $system_prompt, $temperature, $max_tokens, $conversation = []) {
    // Build the messages array
    $messages = [
        [
            'role' => 'system',
            'content' => $system_prompt
        ]
    ];
    
    // Add conversation history if available
    if (!empty($conversation)) {
        foreach ($conversation as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }
    } else {
        // Just add the current message
        $messages[] = [
            'role' => 'user',
            'content' => $prompt
        ];
    }
    
    // Build request body
    $request_body = [
        'model' => 'gpt-3.5-turbo', // or other model as needed
        'messages' => $messages,
        'temperature' => (float) $temperature,
        'max_tokens' => (int) $max_tokens
    ];
    
    // Make the request to OpenAI
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'body' => json_encode($request_body),
        'timeout' => 60
    ]);
    
    // Handle response
    if (is_wp_error($response)) {
        nova_ai_log('OpenAI API Error: ' . $response->get_error_message(), 'error');
        throw new Exception('Fehler bei der Verbindung zum OpenAI-Server: ' . $response->get_error_message());
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    // Check for errors in the API response
    if (isset($data['error'])) {
        nova_ai_log('OpenAI API Error: ' . $data['error']['message'], 'error');
        throw new Exception('OpenAI API Fehler: ' . $data['error']['message']);
    }
    
    // Check if we have a valid response
    if (!isset($data['choices'][0]['message']['content'])) {
        nova_ai_log('OpenAI API Error: Unexpected response format', 'error');
        throw new Exception('Unerwartetes Antwortformat vom OpenAI-Server.');
    }
    
    // Return the response text
    return $data['choices'][0]['message']['content'];
}

/**
 * Track chat statistics
 */
function nova_ai_chat_stats_handler() {
    nova_ai_update_chat_stats();
    return ['success' => true];
}

/**
 * Update chat statistics
 */
function nova_ai_update_chat_stats() {
    // Total chats
    $total_chats = get_option('nova_ai_total_chats', 0);
    update_option('nova_ai_total_chats', $total_chats + 1);
    
    // Today's chats
    $today_date = get_option('nova_ai_today_date', '');
    $today = date('Y-m-d');
    
    if ($today_date !== $today) {
        // New day, reset counter
        update_option('nova_ai_today_date', $today);
        update_option('nova_ai_today_chats', 1);
    } else {
        $today_chats = get_option('nova_ai_today_chats', 0);
        update_option('nova_ai_today_chats', $today_chats + 1);
    }
    
    return true;
}

/**
 * Rate limiting function
 */
function nova_ai_check_rate_limit($limit = 10) {
    // Get IP address
    $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR'];
    $transient_name = 'nova_ai_rate_' . md5($ip);
    
    // Get current rate data
    $rate_data = get_transient($transient_name);
    
    // First request in this time window
    if (false === $rate_data) {
        set_transient($transient_name, ['count' => 1, 'time' => time()], 60); // 1 minute window
        return false;
    }
    
    // Reset for new time window
    if (time() - $rate_data['time'] > 60) {
        set_transient($transient_name, ['count' => 1, 'time' => time()], 60);
        return false;
    }
    
    // Check against limit
    if ($rate_data['count'] >= $limit) {
        return true; // Limit reached
    }
    
    // Increment counter
    $rate_data['count']++;
    set_transient($transient_name, $rate_data, 60);
    
    return false;
}
