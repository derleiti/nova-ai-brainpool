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
 * Handle chat requests via REST API - Fixed version
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
        if (function_exists('nova_ai_check_rate_limit') && nova_ai_check_rate_limit($rate_limit)) {
            return new WP_Error('rate_limit', 'Rate-Limit erreicht. Bitte warte einen Moment, bevor du weitere Nachrichten sendest.', ['status' => 429]);
        }
        
        // Get AI settings
        $api_type = get_option('nova_ai_api_type', 'ollama');
        $model = get_option('nova_ai_model', 'zephyr');
        $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
        $system_prompt = get_option('nova_ai_system_prompt', 'Du bist Nova, ein hilfreicher KI Assistent für AILinux Nutzer.');
        $temperature = get_option('nova_ai_temperature', 0.7);
        $max_tokens = get_option('nova_ai_max_tokens', 800);
        
        // Add knowledge base content if available
        if (function_exists('nova_ai_get_relevant_knowledge')) {
            $relevant_knowledge = nova_ai_get_relevant_knowledge($prompt);
            if (!empty($relevant_knowledge)) {
                $system_prompt .= "\n\n" . $relevant_knowledge;
            }
        }
        
        // Simulate API response for testing (remove this in production)
        $response = "Dies ist eine Test-Antwort von Nova AI. Das Plugin wurde repariert!";
        
        // Update stats
        if (function_exists('nova_ai_update_chat_stats')) {
            nova_ai_update_chat_stats();
        }
        
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
 * Track chat statistics
 */
function nova_ai_chat_stats_handler() {
    if (function_exists('nova_ai_update_chat_stats')) {
        nova_ai_update_chat_stats();
    }
    return ['success' => true];
}

/**
 * Update chat statistics - Safe implementation
 */
if (!function_exists('nova_ai_update_chat_stats')) {
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
}

/**
 * Rate limiting function - Safe implementation
 */
if (!function_exists('nova_ai_check_rate_limit')) {
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
}

/**
 * Safe logging function if not already defined
 */
if (!function_exists('nova_ai_log')) {
    function nova_ai_log($message, $type = 'info') {
        if (!get_option('nova_ai_debug_mode', false) && $type !== 'error') {
            return;
        }
        
        $log_dir = WP_CONTENT_DIR . '/nova-ai-logs/';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $log_file = $log_dir . 'nova-ai-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}
