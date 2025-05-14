<?php
/*
Plugin Name: Nova AI Brainpool
Description: Minimalistischer AI Chat im Terminal-Stil – powered by AILinux
Version: 1.0.1
Author: derleiti & Nova AI
Author URI: https://ailinux.me
Text Domain: nova-ai-brainpool
License: MIT
*/

if (!defined('ABSPATH')) exit;

// Define constants
define('NOVA_AI_VERSION', '1.0.1');
define('NOVA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NOVA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOVA_AI_DATA_DIR', wp_upload_dir()['basedir'] . '/nova-ai-brainpool/');

// Create data directory if it doesn't exist
if (!file_exists(NOVA_AI_DATA_DIR)) {
    wp_mkdir_p(NOVA_AI_DATA_DIR);
    wp_mkdir_p(NOVA_AI_DATA_DIR . 'knowledge/general/');
    wp_mkdir_p(NOVA_AI_DATA_DIR . 'logs/');
}

// Include required files
require_once NOVA_AI_PLUGIN_DIR . 'includes/core.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/chat.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/knowledge.php';
require_once NOVA_AI_PLUGIN_DIR . 'admin/settings.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'nova_ai_install');
register_deactivation_hook(__FILE__, 'nova_ai_deactivate');
register_uninstall_hook(__FILE__, 'nova_ai_uninstall');

// Activation function
function nova_ai_install() {
    // Create default options if they don't exist
    if (!get_option('nova_ai_version')) {
        add_option('nova_ai_version', NOVA_AI_VERSION);
        add_option('nova_ai_api_type', 'ollama');
        add_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
        add_option('nova_ai_model', 'mistral');
        add_option('nova_ai_theme_style', 'terminal');
        
        // Set the default crawl URLs
        $default_urls = [
            'https://wiki.ubuntuusers.de/',
            'https://wiki.archlinux.org/',
            'https://ss64.com/osx/',
            'https://ss64.com/nt/',
            'https://wiki.termux.com/wiki/Main_Page',
            'https://www.freebsd.org/doc/',
            'https://man.openbsd.org/',
            'https://itsfoss.com/linux-commands/'
        ];
        add_option('nova_ai_crawl_urls', implode("\n", $default_urls));
    }
    
    // Create data directories
    if (!file_exists(NOVA_AI_DATA_DIR)) {
        wp_mkdir_p(NOVA_AI_DATA_DIR);
        wp_mkdir_p(NOVA_AI_DATA_DIR . 'knowledge/general/');
        wp_mkdir_p(NOVA_AI_DATA_DIR . 'logs/');
    }
    
    // Flush rewrite rules for REST API
    flush_rewrite_rules();
}

// Deactivation function
function nova_ai_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Uninstall function - called when plugin is deleted
function nova_ai_uninstall() {
    // Only delete options if user chooses to
    if (get_option('nova_ai_delete_data_on_uninstall', false)) {
        delete_option('nova_ai_version');
        delete_option('nova_ai_api_type');
        delete_option('nova_ai_api_url');
        delete_option('nova_ai_api_key');
        delete_option('nova_ai_model');
        delete_option('nova_ai_max_tokens');
        delete_option('nova_ai_temperature');
        delete_option('nova_ai_theme_style');
        delete_option('nova_ai_custom_css');
        delete_option('nova_ai_crawl_urls');
        delete_option('nova_ai_crawl_depth');
        delete_option('nova_ai_crawl_limit');
        delete_option('nova_ai_custom_knowledge');
        delete_option('nova_ai_system_prompt');
        delete_option('nova_ai_debug_mode');
        delete_option('nova_ai_delete_data_on_uninstall');
        
        // Optionally delete data directory
        if (file_exists(NOVA_AI_DATA_DIR)) {
            nova_ai_recursive_delete(NOVA_AI_DATA_DIR);
        }
    }
}

// Helper function to recursively delete directories
function nova_ai_recursive_delete($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            nova_ai_recursive_delete($path);
        } else {
            unlink($path);
        }
    }
    
    rmdir($dir);
}

// Enqueue scripts and styles for the frontend
add_action('wp_enqueue_scripts', 'nova_ai_enqueue_scripts');

function nova_ai_enqueue_scripts() {
    // Only enqueue if shortcode is used on the page
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'nova_ai_chat')) {
        // Get theme setting
        $theme = get_option('nova_ai_theme_style', 'terminal');
        
        // Enqueue common files
        wp_enqueue_script('jquery');
        
        // Enqueue theme-specific files
        if ($theme === 'terminal') {
            wp_enqueue_style('nova-ai-terminal', NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.css');
            wp_enqueue_script('nova-ai-terminal', NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.js', array('jquery'), NOVA_AI_VERSION, true);
        } elseif ($theme === 'dark') {
            wp_enqueue_style('nova-ai-dark', NOVA_AI_PLUGIN_URL . 'assets/style.css');
            wp_enqueue_script('nova-ai-dark', NOVA_AI_PLUGIN_URL . 'assets/js/nova-ai-chat.js', array('jquery'), NOVA_AI_VERSION, true);
        } else {
            wp_enqueue_style('nova-ai-light', NOVA_AI_PLUGIN_URL . 'assets/nova-ai.css');
            wp_enqueue_script('nova-ai-light', NOVA_AI_PLUGIN_URL . 'assets/nova-ai.js', array('jquery'), NOVA_AI_VERSION, true);
        }
        
        // Add custom CSS if available
        $custom_css = get_option('nova_ai_custom_css', '');
        if (!empty($custom_css)) {
            wp_add_inline_style($theme === 'terminal' ? 'nova-ai-terminal' : ($theme === 'dark' ? 'nova-ai-dark' : 'nova-ai-light'), $custom_css);
        }
        
        // Unify localization for all scripts
        $localization = array(
            'api_url' => rest_url('nova-ai/v1/chat'),
            'nonce' => wp_create_nonce('wp_rest')
        );
        
        wp_localize_script('nova-ai-terminal', 'nova_ai_vars', $localization);
        wp_localize_script('nova-ai-dark', 'nova_ai_vars', $localization);
        wp_localize_script('nova-ai-light', 'nova_ai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_ai_nonce')
        ));
    }
}

// Add debug logging if enabled
function nova_ai_log($message, $type = 'info') {
    if (!get_option('nova_ai_debug_mode', false)) {
        return;
    }
    
    $log_dir = NOVA_AI_DATA_DIR . 'logs/';
    $log_file = $log_dir . 'nova-ai-' . date('Y-m-d') . '.log';
    
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$type}] {$message}\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// UPDATED: includes/core.php
// Updated Shortcode implementation
function nova_ai_chat_shortcode($atts = array()) {
    // Get theme setting
    $theme = get_option('nova_ai_theme_style', 'terminal');
    
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'theme' => $theme, // Use theme from settings as default
        'placeholder' => 'Frag mich was...',
    ), $atts, 'nova_ai_chat');
    
    // Enqueue appropriate assets for the theme
    if ($atts['theme'] === 'terminal') {
        wp_enqueue_style('nova-ai-terminal', plugins_url('assets/chat-frontend.css', dirname(__FILE__)));
        wp_enqueue_script('nova-ai-terminal', plugins_url('assets/chat-frontend.js', dirname(__FILE__)), array('jquery'), NOVA_AI_VERSION, true);
        
        // Localize script with REST API endpoint
        wp_localize_script('nova-ai-terminal', 'nova_ai_vars', array(
            'api_url' => rest_url('nova-ai/v1/chat'),
            'nonce' => wp_create_nonce('wp_rest'),
            'placeholder' => $atts['placeholder']
        ));
        
        return '<div id="nova-ai-chatbot" class="nova-theme-' . esc_attr($atts['theme']) . '" data-api-url="' . esc_url(rest_url('nova-ai/v1/chat')) . '" data-placeholder="' . esc_attr($atts['placeholder']) . '"></div>';
    } 
    elseif ($atts['theme'] === 'dark') {
        wp_enqueue_style('nova-ai-dark', plugins_url('assets/style.css', dirname(__FILE__)));
        wp_enqueue_script('nova-ai-dark', plugins_url('assets/js/nova-ai-chat.js', dirname(__FILE__)), array('jquery'), NOVA_AI_VERSION, true);
        
        // Localize script with REST API endpoint
        wp_localize_script('nova-ai-dark', 'nova_ai_vars', array(
            'api_url' => rest_url('nova-ai/v1/chat'),
            'nonce' => wp_create_nonce('wp_rest'),
            'placeholder' => $atts['placeholder']
        ));
        
        // Return dark theme chat box
        return '<div id="nova-ai-chatbox">
                    <div id="nova-ai-messages"></div>
                    <div id="nova-ai-input-container">
                        <input type="text" id="nova-ai-input" placeholder="' . esc_attr($atts['placeholder']) . '">
                        <button id="nova-ai-send">Senden</button>
                    </div>
                </div>';
    } 
    else {
        // Light theme
        wp_enqueue_style('nova-ai-light', plugins_url('assets/nova-ai.css', dirname(__FILE__)));
        wp_enqueue_script('nova-ai-light', plugins_url('assets/nova-ai.js', dirname(__FILE__)), array('jquery'), NOVA_AI_VERSION, true);
        
        // Localize script with ajax endpoint
        wp_localize_script('nova-ai-light', 'nova_ai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_ai_nonce'),
            'placeholder' => $atts['placeholder']
        ));
        
        // Return light theme chat box
        return '<div id="nova-ai-chat"></div>';
    }
}

// UPDATED: includes/chat.php
// REST API Route Registration
add_action('rest_api_init', function () {
    register_rest_route('nova-ai/v1', '/chat', array(
        'methods' => 'POST',
        'callback' => 'nova_ai_chat_handler',
        'permission_callback' => '__return_true'
    ));
});

// Fixed chat handler to use selected model from options
function nova_ai_chat_handler($request) {
    $parameters = $request->get_json_params();
    $prompt = sanitize_text_field($parameters['prompt']);
    
    // Get API type and other settings
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $model = get_option('nova_ai_model', 'mistral');
    $max_tokens = get_option('nova_ai_max_tokens', 250);
    $temperature = get_option('nova_ai_temperature', 0.7);
    
    // Log request if debug is enabled
    nova_ai_log("Chat request: $prompt", 'request');
    
    if ($api_type === 'ollama') {
        // Ollama API
        $data = json_encode(array(
            'model' => $model,
            'prompt' => nova_ai_prepend_knowledge($prompt),
            'stream' => false,
            'max_tokens' => intval($max_tokens),
            'temperature' => floatval($temperature)
        ));
        
        $response = wp_remote_post($api_url, array(
            'body' => $data,
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            nova_ai_log("Error: " . $response->get_error_message(), 'error');
            return array('reply' => '[Fehler bei Verbindung: ' . $response->get_error_message() . ']');
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['response'])) {
            nova_ai_log("Response received from Ollama", 'response');
            return array('reply' => nl2br(sanitize_text_field($result['response'])));
        }
    } 
    else {
        // OpenAI API
        $api_key = get_option('nova_ai_api_key', '');
        
        if (empty($api_key)) {
            return array('reply' => '[Fehler: Kein OpenAI API-Key konfiguriert]');
        }
        
        $data = json_encode(array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array('role' => 'system', 'content' => get_option('nova_ai_system_prompt', 'You are Nova, a helpful AI assistant for AILinux users.')),
                array('role' => 'user', 'content' => $prompt)
            ),
            'temperature' => floatval($temperature),
            'max_tokens' => intval($max_tokens)
        ));
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'body' => $data,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            nova_ai_log("Error: " . $response->get_error_message(), 'error');
            return array('reply' => '[Fehler bei Verbindung: ' . $response->get_error_message() . ']');
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            nova_ai_log("Response received from OpenAI", 'response');
            return array('reply' => nl2br(sanitize_text_field($result['choices'][0]['message']['content'])));
        }
    }
    
    nova_ai_log("Error: Unexpected response format", 'error');
    return array('reply' => '[Fehler bei Antwort: Unerwartetes Antwortformat]');
}

// Fixed knowledge prepending to limit tokens
function nova_ai_prepend_knowledge($prompt) {
    $kb = nova_ai_knowledge_base();
    $inject = "";
    
    // Limit knowledge items to prevent exceeding token limits
    $kb = nova_ai_filter_relevant_knowledge($kb, $prompt, 5);
    
    foreach ($kb as $item) {
        $inject .= "Q: {$item['question']}\nA: {$item['answer']}\n\n";
    }
    
    // Add system prompt if configured
    $system_prompt = get_option('nova_ai_system_prompt', '');
    if (!empty($system_prompt)) {
        $inject = "Instructions: $system_prompt\n\n" . $inject;
    }
    
    return $inject . "Q: " . $prompt . "\nA:";
}

// AJAX Chat Handler for backward compatibility 
add_action('wp_ajax_nova_ai_chat', 'nova_ai_ajax_chat_handler');
add_action('wp_ajax_nopriv_nova_ai_chat', 'nova_ai_ajax_chat_handler');

function nova_ai_ajax_chat_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nova_ai_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    
    $message = sanitize_text_field($_POST['message'] ?? '');
    
    if (empty($message)) {
        wp_send_json_error('No message provided');
    }
    
    // Create a mock request object for the REST handler
    $request = new WP_REST_Request('POST', '/nova-ai/v1/chat');
    $request->set_param('prompt', $message);
    
    // Use the same handler for consistency
    $response = nova_ai_chat_handler($request);
    
    wp_send_json_success(array(
        'reply' => $response['reply']
    ));
}

// Make sure the shortcode is registered
add_shortcode('nova_ai_chat', 'nova_ai_chat_shortcode');
