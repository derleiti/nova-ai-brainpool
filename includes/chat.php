<?php
if (!defined('ABSPATH')) exit;

/**
 * Chat API functionality for Nova AI Brainpool
 */

add_action('rest_api_init', function () {
    register_rest_route('nova-ai/v1', '/chat', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_chat_handler',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('nova-ai/v1', '/chat/stats', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_chat_stats_handler',
        'permission_callback' => '__return_true'
    ));
});

function nova_ai_chat_handler($request) {
    try {
        $parameters = $request->get_json_params();
        $prompt = isset($parameters['prompt']) ? sanitize_text_field($parameters['prompt']) : '';
        $conversation = isset($parameters['conversation']) ? $parameters['conversation'] : [];

        if (empty($prompt)) {
            return new WP_Error('empty_prompt', 'Bitte gib eine Nachricht ein.', ['status' => 400]);
        }

        $rate_limit = apply_filters('nova_ai_rate_limit', 10);
        if (function_exists('nova_ai_check_rate_limit') && nova_ai_check_rate_limit($rate_limit)) {
            return new WP_Error('rate_limit', 'Rate-Limit erreicht. Bitte warte einen Moment.', ['status' => 429]);
        }

        $api_type = get_option('nova_ai_api_type', 'ollama');
        $model = get_option('nova_ai_model', 'zephyr');
        $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
        $api_key = get_option('nova_ai_api_key', '');
        $system_prompt = get_option('nova_ai_system_prompt', 'Du bist Nova, ein hilfreicher KI Assistent für AILinux Nutzer.');
        $temperature = get_option('nova_ai_temperature', 0.7);
        $max_tokens = get_option('nova_ai_max_tokens', 800);

        $relevant_knowledge = '';
        if (function_exists('nova_ai_get_relevant_knowledge')) {
            $relevant_knowledge = nova_ai_get_relevant_knowledge($prompt);
            if (!empty($relevant_knowledge)) {
                $system_prompt .= "\n\n" . $relevant_knowledge;
            }
        }

        $api = Nova_AI_API::get_instance();

        if ($api_type === 'ollama') {
            $response = $api->ollama_request($prompt, $model, $api_url, $system_prompt, $temperature, $max_tokens, $conversation);
        } else {
            $response = $api->openai_request($prompt, $api_key, $system_prompt, $temperature, $max_tokens, $conversation);
        }

        if (function_exists('nova_ai_update_chat_stats')) {
            nova_ai_update_chat_stats();
        }

        return ['reply' => $response];
    } catch (Exception $e) {
        if (function_exists('nova_ai_log')) {
            nova_ai_log('API Error: ' . $e->getMessage(), 'error');
        }
        return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
    }
}
