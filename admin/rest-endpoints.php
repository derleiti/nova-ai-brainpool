<?php
/**
 * REST API Endpoints for Nova AI Brainpool
 * 
 * @package Nova_AI_Brainpool
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API endpoints
 */
function nova_ai_register_rest_endpoints() {
    register_rest_route('nova-ai/v1', '/vision', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_handle_vision',
        'permission_callback' => function() {
            // Check for valid nonce
            $request = new WP_REST_Request('POST');
            $headers = $request->get_headers();
            
            if (isset($headers['x_wp_nonce'][0])) {
                return wp_verify_nonce($headers['x_wp_nonce'][0], 'wp_rest');
            }
            
            // Allow anonymous access only if enabled in settings
            return (bool) get_option('nova_ai_allow_anonymous', true);
        },
        'args' => array(
            'prompt' => array(
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param) {
                    return is_string($param) && !empty($param);
                }
            ),
            'image' => array(
                'validate_callback' => function($param) {
                    return is_string($param) && !empty($param);
                }
            )
        )
    ));
}
add_action('rest_api_init', 'nova_ai_register_rest_endpoints');

/**
 * Handle vision API requests
 */
function nova_ai_handle_vision($request) {
    try {
        $params = $request->get_json_params();
        $prompt = isset($params['prompt']) ? sanitize_text_field($params['prompt']) : __('Describe this image', 'nova-ai-brainpool');
        $image = isset($params['image']) ? sanitize_text_field($params['image']) : null;

        if (!$image) {
            return new WP_REST_Response(array('error' => __('No image provided.', 'nova-ai-brainpool')), 400);
        }
        
        // Validate base64 image
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $image)) {
            return new WP_REST_Response(array('error' => __('Invalid image format.', 'nova-ai-brainpool')), 400);
        }

        // Check for rate limiting
        if (function_exists('nova_ai_check_rate_limit') && nova_ai_check_rate_limit(5)) {
            return new WP_REST_Response(array('error' => __('Rate limit exceeded. Please try again later.', 'nova-ai-brainpool')), 429);
        }

        $payload = array(
            'model' => defined('OLLAMA_MODEL') ? OLLAMA_MODEL : 'llava',
            'prompt' => $prompt,
            'image' => $image
        );

        $response = wp_remote_post(defined('OLLAMA_CHAT_URL') ? OLLAMA_CHAT_URL : get_option('nova_ai_api_url'), array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($payload),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return new WP_REST_Response(array('error' => $response->get_error_message()), 500);
        }

        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return new WP_REST_Response(array('error' => __('API error: ', 'nova-ai-brainpool') . $status_code), $status_code);
        }
        
        // Update stats
        if (function_exists('nova_ai_update_chat_stats')) {
            nova_ai_update_chat_stats();
        }
        
        return rest_ensure_response(json_decode($body, true));
    } catch (Exception $e) {
        nova_ai_log('Vision API Error: ' . $e->getMessage(), 'error');
        return new WP_REST_Response(array('error' => $e->getMessage()), 500);
    }
}
