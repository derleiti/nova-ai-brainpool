<?php
if (!defined('ABSPATH')) exit;

/**
 * Core functionality for Nova AI Brainpool
 */

/**
 * Initialize the plugin
 */
function nova_ai_init() {
    // Register shortcode
    add_shortcode('nova_ai_chat', 'nova_ai_chat_shortcode');
    
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
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $model = get_option('nova_ai_model', 'mistral');
    
    try {
        if ($api_type === 'ollama') {
            // Ollama test
            $data = json_encode([
                'model' => $model,
                'prompt' => 'Say "Nova AI connection test successful"',
                'stream' => false
            ]);
            
            $response = wp_remote_post($api_url, [
                'body' => $data,
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 10,
            ]);
            
            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => $response->get_error_message()
                ];
            }
            
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            
            if (isset($result['response'])) {
                return [
                    'success' => true,
                    'message' => $result['response']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Unexpected response format from Ollama API'
                ];
            }
        } else {
            // OpenAI-compatible API test
            $api_key = get_option('nova_ai_api_key', '');
            
            if (empty($api_key)) {
                return [
                    'success' => false,
                    'message' => 'OpenAI API key not configured'
                ];
            }
            
            $data = json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Say "Nova AI connection test successful"']
                ],
                'max_tokens' => 50
            ]);
            
            $response = wp_remote_post($api_url, [
                'body' => $data,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key
                ],
                'timeout' => 10,
            ]);
            
            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'message' => $response->get_error_message()
                ];
            }
            
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            
            if (isset($result['choices'][0]['message']['content'])) {
                return [
                    'success' => true,
                    'message' => $result['choices'][0]['message']['content']
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Unexpected response format from OpenAI API'
                ];
            }
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Check if the connection to the AI provider is working
 */
function nova_ai_connection_status() {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    
    if ($api_type === 'ollama') {
        // For Ollama, check if the API is reachable
        $list_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
        
        $response = wp_remote_get($list_url, [
            'timeout' => 3,
        ]);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            return '<span style="color:green;">✓ Connected to Ollama</span>';
        }
    } else {
        // For OpenAI, check if an API key is configured
        $api_key = get_option('nova_ai_api_key', '');
        
        if (!empty($api_key)) {
            return '<span style="color:green;">✓ OpenAI API Key configured</span>';
        }
    }
    
    return '<span style="color:red;">✗ Not connected</span>';
}

/**
 * Get available Ollama models
 */
function nova_ai_get_ollama_models() {
    // Default models in case we can't reach the API
    $default_models = [
        'zephyr' => 'Zephyr (Recommended)',
        'mistral' => 'Mistral',
        'llama2' => 'LLaMA 2',
        'phi' => 'Phi-2',
        'gemma' => 'Gemma',
        'neural-chat' => 'Neural Chat'
    ];
    
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $list_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
    
    // Try to get the list of models from Ollama
    $response = wp_remote_get($list_url, [
        'timeout' => 5,
    ]);
    
    if (is_wp_error($response)) {
        return $default_models;
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    if (!isset($result['models']) || !is_array($result['models'])) {
        return $default_models;
    }
    
    // Process the models list
    $models = [];
    
    foreach ($result['models'] as $model) {
        if (isset($model['name'])) {
            $name = $model['name'];
            $display_name = isset($default_models[$name]) ? $default_models[$name] : $name;
            $models[$name] = $display_name;
        }
    }
    
    return !empty($models) ? $models : $default_models;
}

/**
 * AJAX handler for refreshing models
 */
add_action('wp_ajax_nova_ai_refresh_models', 'nova_ai_ajax_refresh_models');
function nova_ai_ajax_refresh_models() {
    // Verify nonce
    check_ajax_referer('nova_ai_refresh_models');
    
    // Get API URL from request or use default
    $api_url = isset($_POST['api_url']) ? esc_url_raw($_POST['api_url']) : get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    
    // Convert to list API endpoint
    $api_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
    
    // Try to get models from Ollama
    $response = wp_remote_get($api_url, [
        'timeout' => 10,
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Connection error: ' . $response->get_error_message());
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    if (!isset($result['models']) || !is_array($result['models'])) {
        wp_send_json_error('Invalid response from Ollama API');
        return;
    }
    
    // Friendly names for common models
    $friendly_names = [
        'zephyr' => 'Zephyr (Recommended)',
        'mistral' => 'Mistral',
        'llama2' => 'LLaMA 2',
        'phi' => 'Phi-2',
        'gemma' => 'Gemma',
        'neural-chat' => 'Neural Chat',
        'mixtral' => 'Mixtral 8x7B',
        'codellama' => 'Code Llama',
        'stablelm' => 'Stable LM',
        'yarn' => 'YaRN'
    ];
    
    // Process models
    $models = [];
    
    foreach ($result['models'] as $model) {
        if (isset($model['name'])) {
            $name = $model['name'];
            $display_name = isset($friendly_names[$name]) ? $friendly_names[$name] : $name;
            $models[$name] = $display_name;
        }
    }
    
    wp_send_json_success([
        'models' => $models
    ]);
}

/**
 * Full-site chat interface
 */
function nova_ai_fullsite_chat() {
    // Get chat settings
    $theme_style = get_option('nova_ai_theme_style', 'terminal');
    $chat_position = get_option('nova_ai_chat_position', 'bottom-right');
    $welcome_message = get_option('nova_ai_chat_welcome_message', 'Hi! I\'m Nova AI. How can I help you?');
    $button_text = get_option('nova_ai_chat_button_text', 'Chat with Nova AI');
    $placeholder = get_option('nova_ai_chat_placeholder', 'Type your message...');
    
    // Theme and position classes
    $theme_class = 'nova-ai-theme-' . $theme_style;
    $position_class = 'nova-ai-position-' . $chat_position;
    
    // Output chat HTML
    ?>
    <div id="nova-ai-fullsite-chat" class="<?php echo esc_attr($theme_class . ' ' . $position_class); ?>">
        <div class="nova-ai-chat-button">
            <span class="nova-ai-button-text"><?php echo esc_html($button_text); ?></span>
            <span class="nova-ai-icon"></span>
        </div>
        
        <div class="nova-ai-chat-container">
            <div class="nova-ai-chat-header">
                <div class="nova-ai-header-title">Nova AI</div>
                <div class="nova-ai-header-controls">
                    <button class="nova-ai-minimize" title="Minimize">–</button>
                    <button class="nova-ai-close" title="Close">×</button>
                </div>
            </div>
            
            <div class="nova-ai-chat-messages">
                <div class="nova-ai-message nova-ai-message-ai">
                    <div class="nova-ai-message-avatar"></div>
                    <div class="nova-ai-message-content"><?php echo esc_html($welcome_message); ?></div>
                </div>
            </div>
            
            <div class="nova-ai-chat-input-container">
                <textarea class="nova-ai-chat-input" placeholder="<?php echo esc_attr($placeholder); ?>" rows="1"></textarea>
                <button class="nova-ai-chat-send" title="Send message" disabled>
                    <span class="nova-ai-send-icon"></span>
                </button>
            </div>
        </div>
    </div>
    <?php
    
    // Enqueue styles and scripts
    wp_enqueue_style('nova-ai-fullsite-chat', NOVA_AI_PLUGIN_URL . 'assets/css/fullsite-chat.css', [], NOVA_AI_VERSION);
    wp_enqueue_script('nova-ai-fullsite-chat', NOVA_AI_PLUGIN_URL . 'assets/js/fullsite-chat.js', ['jquery'], NOVA_AI_VERSION, true);
    
    // Add theme-specific CSS
    $theme_css = nova_ai_get_theme_css($theme_style);
    wp_add_inline_style('nova-ai-fullsite-chat', $theme_css);
    
    // Add custom CSS if available
    $custom_css = get_option('nova_ai_custom_css', '');
    if (!empty($custom_css)) {
        wp_add_inline_style('nova-ai-fullsite-chat', $custom_css);
    }
    
    // Pass data to JavaScript
    wp_localize_script('nova-ai-fullsite-chat', 'nova_ai_chat_settings', [
        'api_url' => rest_url('nova-ai/v1/chat'),
        'nonce' => wp_create_nonce('wp_rest'),
        'welcome_message' => $welcome_message,
        'placeholder' => $placeholder,
        'theme' => $theme_style,
        'position' => $chat_position
    ]);
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
                #nova-ai-fullsite-chat.nova-ai-theme-dark .nova-ai-message-avatar {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2300ffc8\'%3E%3Cpath d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-dark .nova-ai-send-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23000000\'%3E%3Cpath d=\'M2.01 21L23 12 2.01 3 2 10l15 2-15 2z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-dark .nova-ai-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23000000\'%3E%3Cpath d=\'M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z\'/%3E%3C/svg%3E");
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
 * Add admin notices based on message parameter
 */
add_action('admin_notices', 'nova_ai_admin_notices');
function nova_ai_admin_notices() {
    if (!isset($_GET['page']) || strpos($_GET['page'], 'nova-ai') !== 0) {
        return;
    }
    
    if (isset($_GET['message'])) {
        $message = sanitize_text_field($_GET['message']);
        
        switch ($message) {
            case 'connection_success':
                echo '<div class="notice notice-success is-dismissible"><p>Connection test successful! Your AI provider is working correctly.</p></div>';
                break;
                
            case 'connection_error':
                echo '<div class="notice notice-error is-dismissible"><p>Connection test failed. Please check your AI settings.</p></div>';
                break;
                
            case 'logs_cleared':
                echo '<div class="notice notice-success is-dismissible"><p>Debug logs have been cleared.</p></div>';
                break;
                
            case 'settings_saved':
                echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
                break;
        }
    }
}
