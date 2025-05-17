<?php
if (!defined('ABSPATH')) exit;

/**
 * Core functionality for Nova AI Brainpool
 * Optimized for performance and error handling
 */

/**
 * Initialize the plugin
 */
function nova_ai_init() {
    // Register necessary hooks
    add_action('wp_enqueue_scripts', 'nova_ai_register_assets');
    
    // Register AJAX handlers
    add_action('wp_ajax_nova_ai_refresh_models', 'nova_ai_ajax_refresh_models');
    add_action('wp_ajax_nova_ai_test_connection', 'nova_ai_ajax_test_connection');
    
    // Check if full-site chat is enabled
    if (get_option('nova_ai_enable_fullsite_chat', false)) {
        add_action('wp_footer', 'nova_ai_fullsite_chat');
    }
    
    // Register dynamic embed handlers
    add_action('init', 'nova_ai_register_embed_handlers');
}
add_action('init', 'nova_ai_init', 5); // Priority 5 to ensure it runs early

/**
 * Register frontend assets
 */
function nova_ai_register_assets() {
    // Only register assets, they'll be enqueued as needed
    wp_register_style('nova-ai-frontend', NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.css', [], NOVA_AI_VERSION);
    wp_register_script('nova-ai-frontend', NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.js', ['jquery'], NOVA_AI_VERSION, true);
    
    // Theme-specific styles
    $theme_style = get_option('nova_ai_theme_style', 'terminal');
    wp_register_style('nova-ai-theme-terminal', NOVA_AI_PLUGIN_URL . 'assets/css/theme-terminal.css', ['nova-ai-frontend'], NOVA_AI_VERSION);
    wp_register_style('nova-ai-theme-dark', NOVA_AI_PLUGIN_URL . 'assets/css/style-dark.css', ['nova-ai-frontend'], NOVA_AI_VERSION);
    wp_register_style('nova-ai-theme-light', NOVA_AI_PLUGIN_URL . 'assets/css/theme-light.css', ['nova-ai-frontend'], NOVA_AI_VERSION);
}

/**
 * Register embed handlers
 */
function nova_ai_register_embed_handlers() {
    // Custom oEmbed handler for chat interface
    wp_embed_register_handler('nova_ai_chat', '#\[nova_ai_chat([^\]]*)\]#i', 'nova_ai_embed_handler');
}

/**
 * oEmbed handler for more seamless integration
 */
function nova_ai_embed_handler($matches, $attr, $url, $rawattr) {
    // Extract attributes from shortcode
    $attributes = shortcode_parse_atts($matches[1]);
    
    // Generate and return the chat interface
    return nova_ai_chat_shortcode($attributes);
}

/**
 * Test connection to AI provider - improved implementation
 */
function nova_ai_test_connection() {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    
    if ($api_type === 'ollama') {
        return nova_ai_test_ollama_connection();
    } else {
        return nova_ai_test_openai_connection();
    }
}

/**
 * Test Ollama connection
 */
function nova_ai_test_ollama_connection() {
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $model = get_option('nova_ai_model', 'zephyr');
    
    // Try getting models list first (less resource intensive)
    $list_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
    
    $response = wp_remote_get($list_url, [
        'timeout' => 5,
        'httpversion' => '1.1',
        'sslverify' => apply_filters('nova_ai_ssl_verify', true)
    ]);
    
    if (is_wp_error($response)) {
        return [
            'success' => false,
            'message' => 'Connection failed: ' . $response->get_error_message()
        ];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        return [
            'success' => false,
            'message' => 'HTTP error: ' . $status_code
        ];
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    if (!$result || !isset($result['models'])) {
        // If list endpoint fails, try a minimal generation request
        $data = json_encode([
            'model' => $model,
            'prompt' => 'Say "OK"',
            'stream' => false,
            'max_tokens' => 10
        ]);
        
        $response = wp_remote_post($api_url, [
            'body' => $data,
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 5,
            'httpversion' => '1.1',
            'sslverify' => apply_filters('nova_ai_ssl_verify', true)
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['response'])) {
            return [
                'success' => true,
                'message' => 'Connection successful! Model ' . $model . ' is ready.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Connection failed: Unexpected response format'
            ];
        }
    }
    
    // Check if the requested model is available
    $available_models = array_column($result['models'], 'name');
    if (in_array($model, $available_models)) {
        return [
            'success' => true,
            'message' => 'Connection successful! Found ' . count($available_models) . ' models, including ' . $model
        ];
    } else {
        return [
            'success' => true,
            'message' => 'Connection successful but model "' . $model . '" not found. Available models: ' . implode(', ', array_slice($available_models, 0, 5)) . (count($available_models) > 5 ? '...' : '')
        ];
    }
}

/**
 * Test OpenAI connection
 */
function nova_ai_test_openai_connection() {
    $api_key = get_option('nova_ai_api_key', '');
    
    if (empty($api_key)) {
        return [
            'success' => false,
            'message' => 'OpenAI API key not configured'
        ];
    }
    
    // Make simple models list request (less tokens than generation)
    $response = wp_remote_get('https://api.openai.com/v1/models', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key
        ],
        'timeout' => 5,
        'httpversion' => '1.1',
        'sslverify' => apply_filters('nova_ai_ssl_verify', true)
    ]);
    
    if (is_wp_error($response)) {
        return [
            'success' => false,
            'message' => 'Connection failed: ' . $response->get_error_message()
        ];
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        $error_message = isset($result['error']['message']) ? $result['error']['message'] : 'HTTP error: ' . $status_code;
        
        return [
            'success' => false,
            'message' => $error_message
        ];
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    if (isset($result['data']) && is_array($result['data'])) {
        $model_count = count($result['data']);
        $available_models = array_column($result['data'], 'id');
        $gpt_models = array_filter($available_models, function($model) {
            return strpos($model, 'gpt') !== false;
        });
        
        return [
            'success' => true,
            'message' => 'Connection successful! Found ' . count($gpt_models) . ' GPT models available.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Connection successful but unexpected response format'
        ];
    }
}

/**
 * AJAX handler for testing connections
 */
function nova_ai_ajax_test_connection() {
    // Security check
    if (!check_ajax_referer('nova_ai_admin_nonce', false, false)) {
        wp_send_json_error(['message' => 'Security verification failed']);
        exit;
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
        exit;
    }
    
    // Get parameters
    $api_type = isset($_POST['api_type']) ? sanitize_text_field($_POST['api_type']) : 'ollama';
    $api_url = isset($_POST['api_url']) ? esc_url_raw($_POST['api_url']) : '';
    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
    $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
    
    // Override options temporarily for testing
    if (!empty($api_url)) {
        update_option('nova_ai_api_url', $api_url);
    }
    if (!empty($api_key)) {
        update_option('nova_ai_api_key', $api_key);
    }
    if (!empty($model)) {
        update_option('nova_ai_model', $model);
    }
    
    // Update API type
    update_option('nova_ai_api_type', $api_type);
    
    // Test connection
    $result = nova_ai_test_connection();
    
    // Send response
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

/**
 * AJAX handler for refreshing Ollama models
 */
function nova_ai_ajax_refresh_models() {
    // Security check
    if (!check_ajax_referer('nova_ai_admin_nonce', false, false)) {
        wp_send_json_error('Security verification failed');
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    // Get API URL from request or use default
    $api_url = isset($_POST['api_url']) ? esc_url_raw($_POST['api_url']) : get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    
    // Convert to list API endpoint
    $api_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
    
    // Get models from Ollama
    $response = wp_remote_get($api_url, [
        'timeout' => 10,
        'sslverify' => apply_filters('nova_ai_ssl_verify', true)
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Connection error: ' . $response->get_error_message());
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    if (!isset($result['models'])) {
        wp_send_json_error('Invalid response from Ollama API');
        return;
    }
    
    // Default friendly names for common models
    $friendly_names = [
        'zephyr' => 'Zephyr (Recommended)',
        'mistral' => 'Mistral',
        'llama2' => 'LLaMA 2',
        'llama3' => 'LLaMA 3',
        'phi' => 'Phi-2',
        'phi3' => 'Phi-3',
        'gemma' => 'Gemma',
        'neural-chat' => 'Neural Chat',
        'mixtral' => 'Mixtral 8x7B',
        'mixtral-8x22b' => 'Mixtral 8x22B',
        'codellama' => 'Code Llama',
        'stablelm' => 'Stable LM',
        'yarn' => 'YaRN',
        'orca-mini' => 'Orca Mini',
        'vicuna' => 'Vicuna',
        'yi' => 'Yi'
    ];
    
    // Extract model names
    $models = [];
    foreach ($result['models'] as $model) {
        if (isset($model['name'])) {
            $model_name = $model['name'];
            
            // Strip quantization suffix for display
            $base_model = preg_replace('/:(\d+[bk]|q\d_[KM]|\.Q\d_[KM])$/', '', $model_name);
            
            $display_name = isset($friendly_names[$base_model]) ? 
                $friendly_names[$base_model] . (($base_model !== $model_name) ? ' (' . $model_name . ')' : '') : 
                $model_name;
            
            $models[$model_name] = $display_name;
        }
    }
    
    // Sort models alphabetically
    ksort($models);
    
    wp_send_json_success([
        'models' => $models
    ]);
}

/**
 * Get theme-specific CSS with optimization
 */
function nova_ai_get_theme_css($theme) {
    static $theme_cache = [];
    
    // Use cached version if available
    if (isset($theme_cache[$theme])) {
        return $theme_cache[$theme];
    }
    
    $css = '';
    
    switch ($theme) {
        case 'terminal':
            $css = '
                :root {
                    --nova-ai-bg-color: #111;
                    --nova-ai-text-color: #0f0;
                    --nova-ai-accent-color: #0f0;
                    --nova-ai-header-bg: #000;
                    --nova-ai-input-bg: #000;
                    --nova-ai-input-text: #0f0;
                    --nova-ai-message-ai-bg: #1a1a1a;
                    --nova-ai-message-user-bg: #222;
                    --nova-ai-message-ai-text: #0f0;
                    --nova-ai-message-user-text: #0f0;
                    --nova-ai-button-bg: #0f0;
                    --nova-ai-button-text: #000;
                    --nova-ai-send-button-bg: #0f0;
                    --nova-ai-send-button-text: #000;
                    --nova-ai-scrollbar-thumb: #0f0;
                    --nova-ai-scrollbar-track: #000;
                    --nova-ai-font-family: "Courier New", monospace;
                    --nova-ai-border-color: #0f0;
                    --nova-ai-shadow-color: rgba(0, 255, 0, 0.2);
                }
                #nova-ai-fullsite-chat.nova-ai-theme-terminal .nova-ai-message-avatar {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2300ff00\'%3E%3Cpath d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-terminal .nova-ai-send-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23000000\'%3E%3Cpath d=\'M2.01 21L23 12 2.01 3 2 10l15 2-15 2z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-terminal .nova-ai-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23000000\'%3E%3Cpath d=\'M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z\'/%3E%3C/svg%3E");
                }
            ';
            break;
            
        case 'dark':
            $css = '
                :root {
                    --nova-ai-bg-color: #121212;
                    --nova-ai-text-color: #eee;
                    --nova-ai-accent-color: #00ffc8;
                    --nova-ai-header-bg: #1a1a1a;
                    --nova-ai-input-bg: #222;
                    --nova-ai-input-text: #fff;
                    --nova-ai-message-ai-bg: #292929;
                    --nova-ai-message-user-bg: #1f1f1f;
                    --nova-ai-message-ai-text: #00ffc8;
                    --nova-ai-message-user-text: #fff;
                    --nova-ai-button-bg: #00ffc8;
                    --nova-ai-button-text: #000;
                    --nova-ai-send-button-bg: #00ffc8;
                    --nova-ai-send-button-text: #000;
                    --nova-ai-scrollbar-thumb: #444;
                    --nova-ai-scrollbar-track: #222;
                    --nova-ai-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    --nova-ai-border-color: #333;
                    --nova-ai-shadow-color: rgba(0, 255, 200, 0.2);
                }
                #nova-ai-fullsite-chat.nova-ai-theme-dark .nova-ai-message-avatar {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2300ffc8\'%3E%3Cpath d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-dark .nova-ai-send-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23000000\'%3E%3Cpath d=\'M2.01 21L23 12 2.01 3 2 10l15 2-15 2z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-dark .nova-ai-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2300ffc8\'%3E%3Cpath d=\'M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z\'/%3E%3C/svg%3E");
                }
            ';
            break;
            
        case 'light':
            $css = '
                :root {
                    --nova-ai-bg-color: #f9f9f9;
                    --nova-ai-text-color: #333;
                    --nova-ai-accent-color: #008066;
                    --nova-ai-header-bg: #fff;
                    --nova-ai-input-bg: #fff;
                    --nova-ai-input-text: #333;
                    --nova-ai-message-ai-bg: #f0f0f0;
                    --nova-ai-message-user-bg: #e6e6e6;
                    --nova-ai-message-ai-text: #008066;
                    --nova-ai-message-user-text: #333;
                    --nova-ai-button-bg: #008066;
                    --nova-ai-button-text: #fff;
                    --nova-ai-send-button-bg: #008066;
                    --nova-ai-send-button-text: #fff;
                    --nova-ai-scrollbar-thumb: #ccc;
                    --nova-ai-scrollbar-track: #f0f0f0;
                    --nova-ai-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    --nova-ai-border-color: #ddd;
                    --nova-ai-shadow-color: rgba(0, 0, 0, 0.1);
                }
                #nova-ai-fullsite-chat.nova-ai-theme-light .nova-ai-message-avatar {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23008066\'%3E%3Cpath d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-light .nova-ai-send-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23ffffff\'%3E%3Cpath d=\'M2.01 21L23 12 2.01 3 2 10l15 2-15 2z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-light .nova-ai-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23ffffff\'%3E%3Cpath d=\'M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z\'/%3E%3C/svg%3E");
                }
            ';
            break;
            
        default:
            // Default to terminal theme
            $css = '/* Default theme styles */';
    }
    
    // Filter for theme customization
    $css = apply_filters('nova_ai_theme_css', $css, $theme);
    
    // Cache the result
    $theme_cache[$theme] = $css;
    
    return $css;
}

/**
 * Get connection status with detailed information
 */
function nova_ai_connection_status() {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $model = get_option('nova_ai_model', 'zephyr');
    
    if ($api_type === 'ollama') {
        // Check if URL is reachable
        $list_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
        $response = wp_remote_get($list_url, ['timeout' => 3, 'sslverify' => apply_filters('nova_ai_ssl_verify', true)]);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            
            if (isset($result['models'])) {
                $available_models = array_column($result['models'], 'name');
                
                if (in_array($model, $available_models)) {
                    return '<span class="nova-status nova-status-success">✓ Connected to Ollama - Model: ' . esc_html($model) . '</span>';
                } else {
                    return '<span class="nova-status nova-status-warning">⚠ Connected to Ollama, but model "' . esc_html($model) . '" not found</span>';
                }
            } else {
                return '<span class="nova-status nova-status-warning">⚠ Ollama connected, but models list unavailable</span>';
            }
        } else {
            $error_message = is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response);
            return '<span class="nova-status nova-status-error">✗ Not connected to Ollama (' . esc_html($error_message) . ')</span>';
        }
    } else {
        $api_key = get_option('nova_ai_api_key', '');
        
        if (empty($api_key)) {
            return '<span class="nova-status nova-status-error">✗ OpenAI API key not configured</span>';
        }
        
        // Check if API key is valid (minimal request)
        $response = wp_remote_get('https://api.openai.com/v1/models', [
            'headers' => ['Authorization' => 'Bearer ' . $api_key],
            'timeout' => 3,
            'sslverify' => apply_filters('nova_ai_ssl_verify', true)
        ]);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            return '<span class="nova-status nova-status-success">✓ OpenAI API connected</span>';
        } else {
            $error_message = is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response);
            return '<span class="nova-status nova-status-error">✗ OpenAI API connection failed (' . esc_html($error_message) . ')</span>';
        }
    }
}
