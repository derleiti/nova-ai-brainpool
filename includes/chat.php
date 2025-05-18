<?php
/**
 * Chat API functionality for Nova AI Brainpool
 * 
 * @package Nova_AI_Brainpool
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register chat endpoints
 */
function nova_ai_register_chat_endpoints() {
    register_rest_route('nova-ai/v1', '/chat', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_chat_handler',
        'permission_callback' => function() {
            // Allow anonymous access if enabled in settings
            return (bool) get_option('nova_ai_allow_anonymous', true);
        },
        'args' => array(
            'prompt' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'conversation' => array(
                'default' => array()
            )
        )
    ));

    register_rest_route('nova-ai/v1', '/chat/stats', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_chat_stats_handler',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'nova_ai_register_chat_endpoints');

/**
 * Chat API handler
 */
function nova_ai_chat_handler($request) {
    try {
        $parameters = $request->get_json_params();
        $prompt = isset($parameters['prompt']) ? sanitize_text_field($parameters['prompt']) : '';
        $conversation = isset($parameters['conversation']) ? $parameters['conversation'] : array();

        // Validate prompt
        if (empty($prompt)) {
            return new WP_Error('empty_prompt', __('Please enter a message.', 'nova-ai-brainpool'), array('status' => 400));
        }

        // Check rate limiting
        if (function_exists('nova_ai_check_rate_limit') && nova_ai_check_rate_limit(10)) {
            return new WP_Error('rate_limit', __('Rate limit reached. Please wait a moment.', 'nova-ai-brainpool'), array('status' => 429));
        }

        // Get API settings
        $api_type = get_option('nova_ai_api_type', 'ollama');
        $model = get_option('nova_ai_model', 'zephyr');
        $api_url = get_option('nova_ai_api_url', 'http://localhost:11434/api/generate');
        $api_key = get_option('nova_ai_api_key', '');
        $system_prompt = get_option('nova_ai_system_prompt', __('I am Nova, a helpful AI assistant for AILinux users.', 'nova-ai-brainpool'));
        $temperature = get_option('nova_ai_temperature', 0.7);
        $max_tokens = get_option('nova_ai_max_tokens', 800);

        // Get relevant knowledge if available
        $relevant_knowledge = '';
        if (function_exists('nova_ai_get_relevant_knowledge')) {
            $relevant_knowledge = nova_ai_get_relevant_knowledge($prompt);
            if (!empty($relevant_knowledge)) {
                $system_prompt .= "\n\n" . $relevant_knowledge;
            }
        }

        // Initialize API handler
        $api = Nova_AI_API::get_instance();

        // Make API request based on type
        if ($api_type === 'ollama') {
            $response = $api->ollama_request($prompt, $model, $api_url, $system_prompt, $temperature, $max_tokens, $conversation);
        } else {
            $response = $api->openai_request($prompt, $api_key, $system_prompt, $temperature, $max_tokens, $conversation);
        }

        // Update statistics
        if (function_exists('nova_ai_update_chat_stats')) {
            nova_ai_update_chat_stats();
        }

        // Log success
        if (function_exists('nova_ai_log')) {
            nova_ai_log('Chat API request successful', 'info');
        }

        return array('reply' => $response);
    } catch (Exception $e) {
        // Log error
        if (function_exists('nova_ai_log')) {
            nova_ai_log('Chat API Error: ' . $e->getMessage(), 'error');
        }
        
        return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
    }
}

/**
 * Handle chat stats tracking
 */
function nova_ai_chat_stats_handler() {
    try {
        // Update chat statistics
        if (function_exists('nova_ai_update_chat_stats')) {
            nova_ai_update_chat_stats();
        }
        
        return array(
            'success' => true,
            'total' => get_option('nova_ai_total_chats', 0),
            'today' => get_option('nova_ai_today_chats', 0)
        );
    } catch (Exception $e) {
        return new WP_Error('stats_error', $e->getMessage(), array('status' => 500));
    }
}
