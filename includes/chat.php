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
    // Get and validate parameters
    $parameters = $request->get_json_params();
    $prompt = isset($parameters['prompt']) ? sanitize_text_field($parameters['prompt']) : '';
    $conversation = isset($parameters['conversation']) ? $parameters['conversation'] : [];
    
    if (empty($prompt)) {
        return new WP_Error('empty_prompt', 'Please provide a message.', ['status' => 400]);
    }
    
    // Rate limiting
    $rate_limit = apply_filters('nova_ai_rate_limit', 10); // 10 requests per minute by default
    if (nova_ai_check_rate_limit($rate_limit)) {
        return new WP_Error('rate_limit', 'Rate limit reached. Please wait before sending more messages.', ['status' => 429]);
    }
    
    // Get AI settings
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $model = get_option('nova_ai_model', 'mistral');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $system_prompt = get_option('nova_ai_system_prompt', 'Du bist Nova, ein hilfreicher KI Assistent für AILinux Nutzer.');
    $temperature = get_option('nova_ai_temperature', 0.7);
    $max_tokens = get_option('nova_ai_max_tokens', 800);
    
    // Add knowledge base content if available
    if (function_exists('nova_ai_knowledge_base')) {
        $relevant_knowledge = nova_ai_get_relevant_knowledge($prompt);
        if (!empty($relevant_knowledge)) {
            $system_prompt .= "\n\n" . $relevant_knowledge;
        }
    }
    
    // Make API call based on provider type
    try {
        if ($api_type === 'ollama') {
            $response = nova_ai_call_ollama_api($api_url, $model, $system_prompt, $prompt, $conversation, $temperature, $max_tokens);
        } else {
            $api_key = get_option('nova_ai_api_key', '');
            if (empty($api_key)) {
                return new WP_Error('missing_api_key', 'API key is required for OpenAI API.', ['status' => 400]);
            }
            $response = nova_ai_call_openai_api($api_url, $api_key, $model, $system_prompt, $prompt, $conversation, $temperature, $max_tokens);
        }
    } catch (Exception $e) {
        nova_ai_log("API Error: {$e->getMessage()}", 'error');
        return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
    }
    
    // Update chat statistics
    nova_ai_update_chat_stats();
    
    // Return the response
    return ['reply' => $response];
}

/**
 * Call Ollama API
 */
function nova_ai_call_ollama_api($api_url, $model, $system_prompt, $prompt, $conversation, $temperature, $max_tokens) {
    // Convert conversation history to Ollama format if provided
    $enhanced_prompt = $system_prompt . "\n\n";
    
    // Add conversation context if available (limit to last 5 messages)
    if (!empty($conversation)) {
        $limited_conversation = array_slice($conversation, -5);
        foreach ($limited_conversation as $message) {
            if ($message['role'] === 'user') {
                $enhanced_prompt .= "Human: " . $message['content'] . "\n";
            } else {
                $enhanced_prompt .= "Nova: " . $message['content'] . "\n";
            }
        }
    }
    
    // Add current prompt
    $enhanced_prompt .= "Human: " . $prompt . "\nNova:";
    
    // Prepare API request
    $data = json_encode([
        'model' => $model,
        'prompt' => $enhanced_prompt,
        'stream' => false,
        'temperature' => floatval($temperature),
        'max_tokens' => intval($max_tokens)
    ]);
    
    // Make API call
    $response = wp_remote_post($api_url, [
        'body' => $data,
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 30,
    ]);
    
    // Handle response
    if (is_wp_error($response)) {
        throw new Exception($response->get_error_message());
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        throw new Exception("API returned status code {$status_code}");
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    if (!is_array($result) || !isset($result['response'])) {
        throw new Exception("Unexpected response format from Ollama API");
    }
    
    return $result['response'];
}

/**
 * Call OpenAI-compatible API
 */
function nova_ai_call_openai_api($api_url, $api_key, $model, $system_prompt, $prompt, $conversation, $temperature, $max_tokens) {
    // Prepare messages array
    $messages = [['role' => 'system', 'content' => $system_prompt]];
    
    // Add conversation context if available
    if (!empty($conversation)) {
        foreach ($conversation as $message) {
            $messages[] = [
                'role' => ($message['role'] === 'user') ? 'user' : 'assistant',
                'content' => $message['content']
            ];
        }
    }
    
    // Add current prompt
    $messages[] = ['role' => 'user', 'content' => $prompt];
    
    // Prepare API request
    $data = json_encode([
        'model' => $model,
        'messages' => $messages,
        'temperature' => floatval($temperature),
        'max_tokens' => intval($max_tokens)
    ]);
    
    // Make API call
    $response = wp_remote_post($api_url, [
        'body' => $data,
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ],
        'timeout' => 30,
    ]);
    
    // Handle response
    if (is_wp_error($response)) {
        throw new Exception($response->get_error_message());
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        throw new Exception("API returned status code {$status_code}");
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    if (!is_array($result) || !isset($result['choices'][0]['message']['content'])) {
        throw new Exception("Unexpected response format from OpenAI API");
    }
    
    return $result['choices'][0]['message']['content'];
}

/**
 * Get relevant knowledge items for the current prompt
 */
function nova_ai_get_relevant_knowledge($prompt) {
    if (!function_exists('nova_ai_knowledge_base') || !function_exists('nova_ai_filter_relevant_knowledge')) {
        return '';
    }
    
    $knowledge_base = nova_ai_knowledge_base();
    if (empty($knowledge_base)) {
        return '';
    }
    
    $relevant_items = nova_ai_filter_relevant_knowledge($knowledge_base, $prompt, 3);
    if (empty($relevant_items)) {
        return '';
    }
    
    $knowledge_text = "Here is some relevant information to help you answer:\n\n";
    foreach ($relevant_items as $item) {
        $knowledge_text .= "Q: {$item['question']}\nA: {$item['answer']}\n\n";
    }
    
    return $knowledge_text;
}

/**
 * Check if the user has reached the rate limit
 */
function nova_ai_check_rate_limit($limit = 10) {
    // Get IP address (considering potential proxies)
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
}
