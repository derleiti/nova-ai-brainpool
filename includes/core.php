<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Core Functions
 * Common functionality used across the plugin
 */

/**
 * Initialize the plugin
 */
function nova_ai_init() {
    // Check if full-site chat is enabled
    if (get_option('nova_ai_enable_fullsite_chat', false)) {
        add_action('wp_footer', 'nova_ai_fullsite_chat');
    }
}
add_action('init', 'nova_ai_init');

/**
 * Test connection to AI provider
 */
function nova_ai_test_connection() {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    
    if ($api_type === 'ollama') {
        $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
        $model = get_option('nova_ai_model', 'zephyr');
        
        $test_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
        
        $response = wp_remote_get($test_url, array(
            'timeout' => 10,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'HTTP Error: ' . $response_code
            );
        }
        
        // Try to get model list
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['models'])) {
            // Check if selected model is available
            $model_found = false;
            foreach ($result['models'] as $available_model) {
                if (isset($available_model['name']) && $available_model['name'] === $model) {
                    $model_found = true;
                    break;
                }
            }
            
            if ($model_found) {
                return array(
                    'success' => true,
                    'message' => 'Connection successful! Model "' . $model . '" is available.'
                );
            } else {
                return array(
                    'success' => true,
                    'message' => 'Connection successful! But model "' . $model . '" was not found. Available models: ' . 
                                implode(', ', array_column($result['models'], 'name'))
                );
            }
        } else {
            return array(
                'success' => true,
                'message' => 'Connection successful! Ollama is running.'
            );
        }
    } else {
        // Test OpenAI
        $api_key = get_option('nova_ai_api_key', '');
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key is not configured.'
            );
        }
        
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $error = json_decode($body, true);
            $error_message = isset($error['error']['message']) ? $error['error']['message'] : 'HTTP Error: ' . $response_code;
            
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
        
        return array(
            'success' => true,
            'message' => 'OpenAI API connection successful!'
        );
    }
}

/**
 * Check connection status for display
 */
function nova_ai_connection_status() {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    
    if ($api_type === 'ollama') {
        $test_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
        $response = wp_remote_get($test_url, array('timeout' => 3, 'sslverify' => false));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            return '<span style="color:green;">✓ Connected to Ollama</span>';
        }
        
        return '<span style="color:red;">✗ Could not connect to Ollama</span>';
    } else {
        $api_key = get_option('nova_ai_api_key', '');
        
        if (!empty($api_key)) {
            // We don't actually test the connection here to avoid rate limits
            return '<span style="color:green;">✓ OpenAI API Key configured</span>';
        }
        
        return '<span style="color:red;">✗ OpenAI API Key missing</span>';
    }
}

/**
 * Get available Ollama models
 */
function nova_ai_get_ollama_models() {
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $api_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
    
    // Default models in case API is not available
    $models = array(
        'zephyr' => 'Zephyr (Recommended)',
        'mistral' => 'Mistral',
        'llama2' => 'LLaMA 2',
        'phi' => 'Phi-2',
        'gemma' => 'Gemma',
        'neural-chat' => 'Neural Chat'
    );
    
    // Try to get list of models from Ollama
    $response = wp_remote_get($api_url, array('timeout' => 5, 'sslverify' => false));
    
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['models']) && !empty($result['models'])) {
            $available_models = array();
            
            foreach ($result['models'] as $model) {
                if (isset($model['name'])) {
                    // Use friendly names for known models, otherwise use the raw name
                    $model_name = $model['name'];
                    $display_name = isset($models[$model_name]) ? $models[$model_name] : $model_name;
                    
                    $available_models[$model_name] = $display_name;
                }
            }
            
            // If we found models, use those instead of our default list
            if (!empty($available_models)) {
                return $available_models;
            }
        }
    }
    
    // Return default list if we couldn't get models from the API
    return $models;
}

/**
 * Get theme-specific CSS
 */
function nova_ai_get_theme_css($theme) {
    switch ($theme) {
        case 'terminal':
            return '
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
            
        case 'dark':
            return '
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
            ';
            
        case 'light':
            return '
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
            ';
            
        default:
            // Default to terminal theme
            return '
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
            ';
    }
}

/**
 * Helper for safe logging
 */
function nova_ai_log($message, $type = 'info') {
    if (!get_option('nova_ai_debug_mode', false) && $type !== 'error') {
        return;
    }
    
    $log_dir = NOVA_AI_DATA_DIR . 'logs/';
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }
    
    $log_file = $log_dir . 'nova-ai-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
