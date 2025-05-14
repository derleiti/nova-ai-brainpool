<?php
/*
Plugin Name: Nova AI Brainpool
Description: Minimalistischer AI Chat im Terminal-Stil – powered by AILinux
Version: 1.1
Author: derleiti & Nova AI
Author URI: https://ailinux.me
Text Domain: nova-ai-brainpool
License: MIT
*/

if (!defined('ABSPATH')) exit;

// Define constants
define('NOVA_AI_VERSION', '1.1');
define('NOVA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NOVA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOVA_AI_DATA_DIR', wp_upload_dir()['basedir'] . '/nova-ai-brainpool/');

// Activation function - creates necessary options and directories
function nova_ai_install() {
    // Core options
    add_option('nova_ai_version', NOVA_AI_VERSION);
    add_option('nova_ai_api_type', 'ollama');
    add_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    add_option('nova_ai_model', 'mistral');
    add_option('nova_ai_theme_style', 'terminal');
    add_option('nova_ai_system_prompt', 'Du bist Nova, ein hilfreicher KI Assistent für AILinux Nutzer.');
    add_option('nova_ai_temperature', 0.7);
    add_option('nova_ai_max_tokens', 800);
    
    // Create data directories
    if (!file_exists(NOVA_AI_DATA_DIR)) {
        wp_mkdir_p(NOVA_AI_DATA_DIR);
        wp_mkdir_p(NOVA_AI_DATA_DIR . 'knowledge/');
        wp_mkdir_p(NOVA_AI_DATA_DIR . 'logs/');
        wp_mkdir_p(NOVA_AI_DATA_DIR . 'conversations/');
    }
    
    // Flush rewrite rules for REST API
    flush_rewrite_rules();
}

// Deactivation function
function nova_ai_deactivate() {
    flush_rewrite_rules();
}

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'nova_ai_install');
register_deactivation_hook(__FILE__, 'nova_ai_deactivate');

// Enhanced shortcode implementation with analytics
function nova_ai_chat_shortcode($atts = []) {
    // Parse attributes
    $attributes = shortcode_atts([
        'theme' => get_option('nova_ai_theme_style', 'terminal'),
        'placeholder' => '> Frag mich was...',
        'width' => '700px',
        'height' => '400px',
    ], $atts);
    
    // Record shortcode usage for analytics
    $usage_count = get_option('nova_ai_shortcode_usage', 0);
    update_option('nova_ai_shortcode_usage', $usage_count + 1);
    
    // Enqueue necessary styles and scripts
    wp_enqueue_style('nova-ai-style', NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.css');
    wp_enqueue_script('nova-ai-script', NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.js', array('jquery'), NOVA_AI_VERSION, true);
    
    // Pass data to JavaScript
    wp_localize_script('nova-ai-script', 'nova_ai_vars', array(
        'api_url' => rest_url('nova-ai/v1/chat'),
        'nonce' => wp_create_nonce('wp_rest'),
        'theme' => $attributes['theme'],
        'conversation_id' => uniqid('nova_'),
        'placeholder' => $attributes['placeholder']
    ));
    
    // Add inline CSS for custom dimensions
    $custom_css = "
        #nova-ai-chatbot {
            max-width: {$attributes['width']};
        }
        .nova-ai-console-output {
            max-height: {$attributes['height']};
        }
    ";
    wp_add_inline_style('nova-ai-style', $custom_css);
    
    // Return the chat container
    return '<div id="nova-ai-chatbot" data-api-url="' . esc_url(rest_url('nova-ai/v1/chat')) . '" class="nova-theme-' . esc_attr($attributes['theme']) . '"></div>';
}
add_shortcode('nova_ai_chat', 'nova_ai_chat_shortcode');

// Register REST API routes with improved security
add_action('rest_api_init', function() {
    // Main chat endpoint
    register_rest_route('nova-ai/v1', '/chat', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_chat_handler',
        'permission_callback' => function() {
            return true; // Public endpoint, but we'll implement rate limiting
        }
    ));
    
    // Admin-only endpoints
    register_rest_route('nova-ai/v1', '/test-connection', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_test_connection',
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
});

// Enhanced chat handler with error handling and logging
function nova_ai_chat_handler($request) {
    // Get and validate parameters
    $parameters = $request->get_json_params();
    $prompt = isset($parameters['prompt']) ? sanitize_text_field($parameters['prompt']) : '';
    $conversation_id = isset($parameters['conversation_id']) ? sanitize_text_field($parameters['conversation_id']) : '';
    
    if (empty($prompt)) {
        return array('reply' => 'Bitte gib eine Nachricht ein.');
    }
    
    // Rate limiting
    $rate_limit = apply_filters('nova_ai_rate_limit', 10); // 10 requests per minute by default
    if (nova_ai_check_rate_limit($rate_limit)) {
        return array('reply' => 'Rate limit erreicht. Bitte warte einen Moment bevor du weitere Nachrichten sendest.');
    }
    
    // Prepare API request
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $model = get_option('nova_ai_model', 'mistral');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $system_prompt = get_option('nova_ai_system_prompt', 'Du bist Nova, ein hilfreicher KI Assistent für AILinux Nutzer.');
    $temperature = get_option('nova_ai_temperature', 0.7);
    $max_tokens = get_option('nova_ai_max_tokens', 800);
    
    // Store the conversation history
    $conversation_history = [];
    if (!empty($conversation_id)) {
        $history_file = NOVA_AI_DATA_DIR . 'conversations/' . sanitize_file_name($conversation_id) . '.json';
        if (file_exists($history_file)) {
            $history_json = file_get_contents($history_file);
            $conversation_history = json_decode($history_json, true) ?: [];
        }
        
        // Add the new message to history
        $conversation_history[] = [
            'role' => 'user',
            'content' => $prompt,
            'timestamp' => time()
        ];
    }
    
    // Enhanced prompt with system instructions and conversation context
    $enhanced_prompt = $system_prompt . "\n\n";
    
    // Add conversation history context if available
    if (!empty($conversation_history)) {
        foreach ($conversation_history as $index => $message) {
            // Only include the last 5 messages to avoid excessive context
            if ($index >= count($conversation_history) - 5) {
                if ($message['role'] === 'user') {
                    $enhanced_prompt .= "Mensch: " . $message['content'] . "\n";
                } else {
                    $enhanced_prompt .= "Nova: " . $message['content'] . "\n";
                }
            }
        }
    }
    
    $enhanced_prompt .= "Mensch: " . $prompt . "\nNova:";
    
    // Make the API request based on provider type
    if ($api_type === 'ollama') {
        $data = json_encode([
            'model' => $model,
            'prompt' => $enhanced_prompt,
            'stream' => false,
            'temperature' => floatval($temperature),
            'max_tokens' => intval($max_tokens)
        ]);
        
        $response = wp_remote_post($api_url, [
            'body' => $data,
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30,
        ]);
    } else {
        // OpenAI-compatible API format
        $messages = [['role' => 'system', 'content' => $system_prompt]];
        
        // Add conversation history
        if (!empty($conversation_history)) {
            foreach ($conversation_history as $message) {
                $messages[] = [
                    'role' => ($message['role'] === 'user') ? 'user' : 'assistant',
                    'content' => $message['content']
                ];
            }
        }
        
        // Add the current message
        $messages[] = ['role' => 'user', 'content' => $prompt];
        
        $data = json_encode([
            'model' => $model,
            'messages' => $messages,
            'temperature' => floatval($temperature),
            'max_tokens' => intval($max_tokens)
        ]);
        
        $api_key = get_option('nova_ai_api_key', '');
        $response = wp_remote_post($api_url, [
            'body' => $data,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'timeout' => 30,
        ]);
    }
    
    // Process the response
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        nova_ai_log("API Error: {$error_message}", 'error');
        return ['reply' => "Fehler bei der Verbindung zum AI-Server: {$error_message}"];
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    // Extract the AI's reply based on API type
    if ($api_type === 'ollama' && isset($result['response'])) {
        $ai_reply = $result['response'];
    } elseif (isset($result['choices'][0]['message']['content'])) {
        // OpenAI-compatible format
        $ai_reply = $result['choices'][0]['message']['content'];
    } else {
        nova_ai_log("Unexpected API response: " . print_r($result, true), 'error');
        return ['reply' => 'Der AI-Server hat in einem unerwarteten Format geantwortet. Bitte überprüfe die Einstellungen.'];
    }
    
    // Store the AI's reply in conversation history
    if (!empty($conversation_id)) {
        $conversation_history[] = [
            'role' => 'assistant',
            'content' => $ai_reply,
            'timestamp' => time()
        ];
        
        // Save updated history
        $history_file = NOVA_AI_DATA_DIR . 'conversations/' . sanitize_file_name($conversation_id) . '.json';
        file_put_contents($history_file, json_encode($conversation_history, JSON_PRETTY_PRINT));
    }
    
    // Log successful interaction
    nova_ai_log("Chat: " . substr($prompt, 0, 100) . "... → " . substr($ai_reply, 0, 100) . "...");
    
    return ['reply' => $ai_reply];
}

// Rate limiting function
function nova_ai_check_rate_limit($limit = 10) {
    // Get the visitor's IP address
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_name = 'nova_ai_rate_' . md5($ip);
    
    // Get current count and time
    $rate_data = get_transient($transient_name);
    
    if (false === $rate_data) {
        // First request in the time window
        set_transient($transient_name, ['count' => 1, 'time' => time()], 60); // 1 minute window
        return false;
    }
    
    // Check if we're within the same minute
    if (time() - $rate_data['time'] > 60) {
        // Reset counter for a new minute
        set_transient($transient_name, ['count' => 1, 'time' => time()], 60);
        return false;
    }
    
    // We're in the same minute, check against limit
    if ($rate_data['count'] >= $limit) {
        return true; // Limit reached
    }
    
    // Increment the counter
    $rate_data['count']++;
    set_transient($transient_name, $rate_data, 60);
    
    return false;
}

// Test connection endpoint for admin settings
function nova_ai_test_connection($request) {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $model = get_option('nova_ai_model', 'mistral');
    
    if ($api_type === 'ollama') {
        $data = json_encode([
            'model' => $model,
            'prompt' => 'Say "Nova AI connection test successful"',
            'stream' => false
        ]);
        
        $response = wp_remote_post($api_url, [
            'body' => $data,
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 5,
        ]);
    } else {
        // OpenAI-compatible API
        $api_key = get_option('nova_ai_api_key', '');
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
            'timeout' => 5,
        ]);
    }
    
    if (is_wp_error($response)) {
        return [
            'success' => false,
            'message' => $response->get_error_message()
        ];
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    if ($api_type === 'ollama' && isset($result['response'])) {
        return [
            'success' => true,
            'message' => $result['response']
        ];
    } elseif (isset($result['choices'][0]['message']['content'])) {
        return [
            'success' => true,
            'message' => $result['choices'][0]['message']['content']
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Unexpected response format from API',
            'details' => wp_remote_retrieve_response_code($response) . ': ' . substr(wp_remote_retrieve_body($response), 0, 100)
        ];
    }
}

// Enhanced admin menu with sub-pages
add_action('admin_menu', 'nova_ai_admin_menu');
function nova_ai_admin_menu() {
    add_menu_page(
        'Nova AI Brainpool',
        'Nova AI',
        'manage_options',
        'nova-ai-brainpool',
        'nova_ai_settings_page',
        'dashicons-robot',
        100
    );
    
    add_submenu_page(
        'nova-ai-brainpool',
        'Settings',
        'Settings',
        'manage_options',
        'nova-ai-brainpool',
        'nova_ai_settings_page'
    );
    
    add_submenu_page(
        'nova-ai-brainpool',
        'Theme Settings',
        'Theme',
        'manage_options',
        'nova-ai-theme',
        'nova_ai_theme_page'
    );
    
    add_submenu_page(
        'nova-ai-brainpool',
        'Analytics',
        'Analytics',
        'manage_options',
        'nova-ai-analytics',
        'nova_ai_analytics_page'
    );
    
    add_submenu_page(
        'nova-ai-brainpool',
        'About',
        'About',
        'manage_options',
        'nova-ai-about',
        'nova_ai_about_page'
    );
}

// Enhanced settings page with tabs and additional options
function nova_ai_settings_page() {
    if (!current_user_can('manage_options')) return;
    
    // Process form submission
    if (isset($_POST['nova_ai_save_settings']) && isset($_POST['nova_ai_nonce']) && wp_verify_nonce($_POST['nova_ai_nonce'], 'nova_ai_settings')) {
        update_option('nova_ai_api_type', sanitize_text_field($_POST['nova_ai_api_type']));
        update_option('nova_ai_api_url', esc_url_raw($_POST['nova_ai_api_url']));
        update_option('nova_ai_model', sanitize_text_field($_POST['nova_ai_model']));
        update_option('nova_ai_api_key', sanitize_text_field($_POST['nova_ai_api_key']));
        update_option('nova_ai_system_prompt', sanitize_textarea_field($_POST['nova_ai_system_prompt']));
        update_option('nova_ai_temperature', floatval($_POST['nova_ai_temperature']));
        update_option('nova_ai_max_tokens', intval($_POST['nova_ai_max_tokens']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    
    // Get current settings
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $model = get_option('nova_ai_model', 'mistral');
    $api_key = get_option('nova_ai_api_key', '');
    $system_prompt = get_option('nova_ai_system_prompt', 'Du bist Nova, ein hilfreicher KI Assistent für AILinux Nutzer.');
    $temperature = get_option('nova_ai_temperature', 0.7);
    $max_tokens = get_option('nova_ai_max_tokens', 800);
    
    // Enqueue admin JS
    wp_enqueue_script('nova-ai-admin', NOVA_AI_PLUGIN_URL . 'assets/admin.js', ['jquery'], NOVA_AI_VERSION, true);
    wp_localize_script('nova-ai-admin', 'nova_ai_admin', [
        'api_url' => rest_url('nova-ai/v1/test-connection'),
        'nonce' => wp_create_nonce('wp_rest')
    ]);
    
    ?>
    <div class="wrap">
        <h1>Nova AI Settings</h1>
        
        <div class="nova-ai-header-info">
            <p>Nova AI Brainpool Version: <?php echo NOVA_AI_VERSION; ?></p>
            <p>Use shortcode <code>[nova_ai_chat]</code> to display the chat interface</p>
        </div>
        
        <form method="post" id="nova-ai-settings-form">
            <?php wp_nonce_field('nova_ai_settings', 'nova_ai_nonce'); ?>
            
            <h2 class="title">AI Provider Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">AI Provider</th>
                    <td>
                        <select name="nova_ai_api_type" id="nova_ai_api_type">
                            <option value="ollama" <?php selected($api_type, 'ollama'); ?>>Ollama (Local AI)</option>
                            <option value="openai" <?php selected($api_type, 'openai'); ?>>OpenAI Compatible</option>
                        </select>
                        <p class="description">Select your AI provider</p>
                    </td>
                </tr>
                
                <tr class="api-ollama">
                    <th scope="row">Ollama API URL</th>
                    <td>
                        <input type="url" name="nova_ai_api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text">
                        <p class="description">URL to Ollama API endpoint (e.g., http://localhost:11434/api/generate)</p>
                    </td>
                </tr>
                
                <tr class="api-openai" style="display:none;">
                    <th scope="row">API URL</th>
                    <td>
                        <input type="url" name="nova_ai_api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text">
                        <p class="description">URL to OpenAI-compatible API (e.g., https://api.openai.com/v1/chat/completions)</p>
                    </td>
                </tr>
                
                <tr class="api-openai" style="display:none;">
                    <th scope="row">API Key</th>
                    <td>
                        <input type="password" name="nova_ai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                        <p class="description">Your API key for authentication</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Model Name</th>
                    <td>
                        <input type="text" name="nova_ai_model" value="<?php echo esc_attr($model); ?>" class="regular-text">
                        <p class="description">Name of the model to use (e.g., mistral, llama2, gpt-3.5-turbo)</p>
                    </td>
                </tr>
            </table>
            
            <h2 class="title">AI Behavior Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">System Prompt</th>
                    <td>
                        <textarea name="nova_ai_system_prompt" rows="4" class="large-text"><?php echo esc_textarea($system_prompt); ?></textarea>
                        <p class="description">Instructions that define how Nova AI behaves</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Temperature</th>
                    <td>
                        <input type="range" name="nova_ai_temperature" min="0" max="1" step="0.1" value="<?php echo esc_attr($temperature); ?>" id="temperature-slider">
                        <span id="temperature-value"><?php echo esc_html($temperature); ?></span>
                        <p class="description">Controls randomness: 0 = deterministic, 1 = creative</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Max Tokens</th>
                    <td>
                        <input type="number" name="nova_ai_max_tokens" value="<?php echo esc_attr($max_tokens); ?>" min="100" max="4000" class="small-text">
                        <p class="description">Maximum length of AI responses</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="nova_ai_save_settings" class="button button-primary" value="Save Settings">
                <button type="button" id="test-connection" class="button button-secondary">Test Connection</button>
            </p>
        </form>
        
        <div id="connection-test-results" style="display:none; margin-top:20px; padding:15px; border:1px solid #ccc; border-radius:5px;">
            <h3>Connection Test Results</h3>
            <div id="test-results-content"></div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Toggle API fields based on provider selection
        $('#nova_ai_api_type').on('change', function() {
            if ($(this).val() === 'ollama') {
                $('.api-ollama').show();
                $('.api-openai').hide();
            } else {
                $('.api-ollama').hide();
                $('.api-openai').show();
            }
        }).trigger('change');
        
        // Update temperature value display
        $('#temperature-slider').on('input', function() {
            $('#temperature-value').text($(this).val());
        });
    });
    </script>
    <?php
}

// Theme settings page
function nova_ai_theme_page() {
    if (!current_user_can('manage_options')) return;
    
    // Process form submission
    if (isset($_POST['nova_ai_save_theme']) && isset($_POST['nova_ai_theme_nonce']) && wp_verify_nonce($_POST['nova_ai_theme_nonce'], 'nova_ai_theme')) {
        update_option('nova_ai_theme_style', sanitize_text_field($_POST['nova_ai_theme_style']));
        update_option('nova_ai_custom_css', wp_kses_post($_POST['nova_ai_custom_css']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Theme settings saved successfully!</p></div>';
    }
    
    // Get current settings
    $theme_style = get_option('nova_ai_theme_style', 'terminal');
    $custom_css = get_option('nova_ai_custom_css', '');
    
    ?>
    <div class="wrap">
        <h1>Nova AI Theme Settings</h1>
        
        <form method="post">
            <?php wp_nonce_field('nova_ai_theme', 'nova_ai_theme_nonce'); ?>
            
            <h2 class="title">Theme Selection</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Theme Style</th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="nova_ai_theme_style" value="terminal" <?php checked($theme_style, 'terminal'); ?>>
                                Terminal (Green on Black)
                                <div style="background:#000; color:#0f0; font-family:monospace; padding:10px; border-radius:5px; margin:10px 0; max-width:300px;">
                                    <div>> What is AILinux?</div>
                                    <div>AILinux is an independent Linux Distribution optimized for AI and gaming workloads.</div>
                                </div>
                            </label>
                            <br><br>
                            
                            <label>
                                <input type="radio" name="nova_ai_theme_style" value="dark" <?php checked($theme_style, 'dark'); ?>>
                                Dark Modern
                                <div style="background:#121212; color:#eee; font-family:sans-serif; padding:10px; border-radius:5px; margin:10px 0; max-width:300px;">
                                    <div style="text-align:right; background:#1f1f1f; padding:5px; border-radius:4px; margin:5px 0;">What is AILinux?</div>
                                    <div style="background:#292929; color:#00ffc8; padding:5px; border-radius:4px;">AILinux is an independent Linux Distribution optimized for AI and gaming workloads.</div>
                                </div>
                            </label>
                            <br><br>
                            
                            <label>
                                <input type="radio" name="nova_ai_theme_style" value="light" <?php checked($theme_style, 'light'); ?>>
                                Light Modern
                                <div style="background:#f9f9f9; color:#333; font-family:sans-serif; padding:10px; border-radius:5px; margin:10px 0; max-width:300px;">
                                    <div style="text-align:right; background:#e6e6e6; padding:5px; border-radius:4px; margin:5px 0;">What is AILinux?</div>
                                    <div style="background:#f0f0f0; color:#008066; padding:5px; border-radius:4px;">AILinux is an independent Linux Distribution optimized for AI and gaming workloads.</div>
                                </div>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Custom CSS</th>
                    <td>
                        <textarea name="nova_ai_custom_css" rows="10" class="large-text code"><?php echo esc_textarea($custom_css); ?></textarea>
                        <p class="description">Add custom CSS styles to override the default theme</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="nova_ai_save_theme" class="button button-primary" value="Save Theme Settings">
            </p>
        </form>
    </div>
    <?php
}

// Simple analytics page
function nova_ai_analytics_page() {
    if (!current_user_can('manage_options')) return;
    
    // Get usage statistics
    $shortcode_usage = get_option('nova_ai_shortcode_usage', 0);
    $conversation_dir = NOVA_AI_DATA_DIR . 'conversations/';
    $conversation_count = 0;
    $message_count = 0;
    
    if (file_exists($conversation_dir)) {
        $files = glob($conversation_dir . '*.json');
        $conversation_count = count($files);
        
        foreach ($files as $file) {
            $conversation = json_decode(file_get_contents($file), true);
            $message_count += count($conversation);
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Nova AI Analytics</h1>
        
        <div class="card" style="max-width:800px;">
            <h2>Usage Statistics</h2>
            
            <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
                <div style="flex:1; text-align:center; padding:20px; background:#f9f9f9; border-radius:5px; margin-right:10px;">
                    <h3 style="margin-top:0;"><?php echo esc_html($shortcode_usage); ?></h3>
                    <p>Shortcode Usages</p>
                </div>
                
                <div style="flex:1; text-align:center; padding:20px; background:#f9f9f9; border-radius:5px; margin-right:10px;">
                    <h3 style="margin-top:0;"><?php echo esc_html($conversation_count); ?></h3>
                    <p>Conversations</p>
                </div>
                
                <div style="flex:1; text-align:center; padding:20px; background:#f9f9f9; border-radius:5px;">
                    <h3 style="margin-top:0;"><?php echo esc_html($message_count); ?></h3>
                    <p>Total Messages</p>
                </div>
            </div>
            
            <p>These statistics help you understand how your users are interacting with Nova AI.</p>
        </div>
    </div>
    <?php
}

// About page
function nova_ai_about_page() {
    ?>
    <div class="wrap">
        <h1>About Nova AI Brainpool</h1>
        
        <div class="card" style="max-width:800px; margin-bottom:20px;">
            <h2>About This Plugin</h2>
            <p>Nova AI Brainpool is a minimalist AI chat interface in a terminal style, powered by AILinux. It allows you to integrate a powerful AI assistant into your WordPress site that can connect to local AI models via Ollama or cloud-based AI services.</p>
            
            <p><strong>Version:</strong> <?php echo NOVA_AI_VERSION; ?></p>
            <p><strong>Author:</strong> <a href="https://ailinux.me" target="_blank">derleiti & Nova AI</a></p>
            <p><strong>License:</strong> MIT</p>
        </div>
        
        <div class="card" style="max-width:800px;">
            <h2>How to Use</h2>
            <ol>
                <li>Configure the AI provider in the Settings tab</li>
                <li>Add the shortcode <code>[nova_ai_chat]</code> to any page or post</li>
                <li>Customize the theme in the Theme Settings tab</li>
            </ol>
            
            <h3>Shortcode Options</h3>
            <p>You can customize the chat interface with these shortcode attributes:</p>
            <pre>[nova_ai_chat theme="terminal" placeholder="Ask me anything..." width="800px" height="500px"]</pre>
        </div>
    </div>
    <?php
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'nova_ai_plugin_links');
function nova_ai_plugin_links($links) {
    $settings_link = '<a href="admin.php?page=nova-ai-brainpool">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Helper function for logging
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

// Create the admin.js file for the settings page
function nova_ai_create_admin_js() {
    $admin_js_path = NOVA_AI_PLUGIN_DIR . 'assets/admin.js';
    
    if (!file_exists($admin_js_path)) {
        $js_content = <<<'EOT'
jQuery(document).ready(function($) {
    // Test connection button
    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var resultArea = $('#connection-test-results');
        var resultContent = $('#test-results-content');
        
        // Update button state
        button.prop('disabled', true).text('Testing...');
        
        // Get form data
        var apiType = $('#nova_ai_api_type').val();
        var apiUrl = $('input[name="nova_ai_api_url"]').val();
        var model = $('input[name="nova_ai_model"]').val();
        var apiKey = $('input[name="nova_ai_api_key"]').val();
        
        // Send test request
        $.ajax({
            url: nova_ai_admin.api_url,
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nova_ai_admin.nonce);
            },
            data: JSON.stringify({
                api_type: apiType,
                api_url: apiUrl,
                model: model,
                api_key: apiKey
            }),
            contentType: 'application/json',
            success: function(response) {
                resultArea.show();
                
                if (response.success) {
                    resultContent.html('<div class="notice notice-success"><p><strong>Connection successful!</strong></p><p>Response: ' + response.message + '</p></div>');
                } else {
                    resultContent.html('<div class="notice notice-error"><p><strong>Connection failed:</strong> ' + response.message + '</p>' + 
                        (response.details ? '<p>Details: ' + response.details + '</p>' : '') + '</div>');
                }
            },
            error: function(xhr) {
                resultArea.show();
                resultContent.html('<div class="notice notice-error"><p><strong>Test request failed:</strong> ' + 
                    (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error') + '</p></div>');
            },
            complete: function() {
                button.prop('disabled', false).text('Test Connection');
            }
        });
    });
    
    // Update temperature display
    $('#temperature-slider').on('input', function() {
        $('#temperature-value').text($(this).val());
    });
});
EOT;

        file_put_contents($admin_js_path, $js_content);
    }
}
// Create admin JS file when needed
add_action('admin_init', 'nova_ai_create_admin_js');
