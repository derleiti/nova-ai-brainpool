<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Brainpool Chat Processor
 * Handles communication with the AI model (Ollama/OpenAI)
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
        if (function_exists('nova_ai_check_rate_limit') && nova_ai_check_rate_limit(10)) {
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
        if (function_exists('nova_ai_get_relevant_knowledge')) {
            $relevant_knowledge = nova_ai_get_relevant_knowledge($prompt);
            if (!empty($relevant_knowledge)) {
                $system_prompt .= "\n\nHier sind einige relevante Informationen zu AILinux: " . $relevant_knowledge;
            }
        }
        
        // Process with the selected API
        $response = '';
        
        if ($api_type === 'ollama') {
            $response = nova_ai_process_ollama($prompt, $system_prompt, $model, $api_url, $temperature, $max_tokens);
        } else {
            $response = nova_ai_process_openai($prompt, $system_prompt, $api_key, $temperature, $max_tokens);
        }
        
        // Update stats
        nova_ai_update_chat_stats();
        
        // Return response
        return [
            'reply' => $response
        ];
    } catch (Exception $e) {
        if (function_exists('nova_ai_log')) {
            nova_ai_log('API Error: ' . $e->getMessage(), 'error');
        }
        return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
    }
}

/**
 * Process request with Ollama
 */
function nova_ai_process_ollama($prompt, $system_prompt, $model, $api_url, $temperature, $max_tokens) {
    // Prepare request data
    $request_data = array(
        'model' => $model,
        'prompt' => $prompt,
        'system' => $system_prompt,
        'stream' => false,
        'options' => array(
            'temperature' => (float)$temperature,
            'num_predict' => (int)$max_tokens
        )
    );
    
    // Log request if debug mode enabled
    if (get_option('nova_ai_debug_mode', false)) {
        nova_ai_log('Ollama Request: ' . json_encode($request_data, JSON_PRETTY_PRINT), 'debug');
    }
    
    // Make the API request
    $response = wp_remote_post($api_url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($request_data),
        'timeout' => 60,
        'sslverify' => false
    ));
    
    // Handle errors
    if (is_wp_error($response)) {
        nova_ai_log('Ollama API Error: ' . $response->get_error_message(), 'error');
        throw new Exception('Verbindungsfehler: ' . $response->get_error_message());
    }
    
    // Check the response code
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        nova_ai_log('Ollama API HTTP Error: ' . $response_code . ' - ' . $body, 'error');
        throw new Exception('HTTP-Fehler: ' . $response_code);
    }
    
    // Get the response body
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    // Log response if debug mode enabled
    if (get_option('nova_ai_debug_mode', false)) {
        nova_ai_log('Ollama Response: ' . $body, 'debug');
    }
    
    // Extract the response text
    if (isset($result['response'])) {
        return $result['response'];
    } else {
        nova_ai_log('Ollama API Invalid Response: ' . $body, 'error');
        throw new Exception('Ungültige Antwort vom Ollama API Server');
    }
}

/**
 * Process request with OpenAI
 */
function nova_ai_process_openai($prompt, $system_prompt, $api_key, $temperature, $max_tokens) {
    // Check for API key
    if (empty($api_key)) {
        throw new Exception('OpenAI API-Schlüssel fehlt. Bitte in den Einstellungen konfigurieren.');
    }
    
    // Prepare request data
    $request_data = array(
        'model' => 'gpt-3.5-turbo',
        'messages' => array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $prompt)
        ),
        'temperature' => (float)$temperature,
        'max_tokens' => (int)$max_tokens
    );
    
    // Log request if debug mode enabled
    if (get_option('nova_ai_debug_mode', false)) {
        nova_ai_log('OpenAI Request: ' . json_encode($request_data, JSON_PRETTY_PRINT), 'debug');
    }
    
    // Make the API request
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'body' => json_encode($request_data),
        'timeout' => 60
    ));
    
    // Handle errors
    if (is_wp_error($response)) {
        nova_ai_log('OpenAI API Error: ' . $response->get_error_message(), 'error');
        throw new Exception('Verbindungsfehler: ' . $response->get_error_message());
    }
    
    // Check the response code
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        nova_ai_log('OpenAI API HTTP Error: ' . $response_code . ' - ' . $body, 'error');
        throw new Exception('HTTP-Fehler: ' . $response_code);
    }
    
    // Get the response body
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    // Log response if debug mode enabled
    if (get_option('nova_ai_debug_mode', false)) {
        nova_ai_log('OpenAI Response: ' . $body, 'debug');
    }
    
    // Extract the response text
    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    } else {
        nova_ai_log('OpenAI API Invalid Response: ' . $body, 'error');
        throw new Exception('Ungültige Antwort vom OpenAI API Server');
    }
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
