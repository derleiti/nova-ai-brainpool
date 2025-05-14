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

/**
 * Plugin activation function
 * Creates necessary options and directories
 */
function nova_ai_activate() {
    // Create data directories
    if (!file_exists(NOVA_AI_DATA_DIR)) {
        if (!wp_mkdir_p(NOVA_AI_DATA_DIR)) {
            // Log directory creation error
            error_log('Nova AI: Failed to create data directory: ' . NOVA_AI_DATA_DIR);
        } else {
            wp_mkdir_p(NOVA_AI_DATA_DIR . 'knowledge/general/');
            wp_mkdir_p(NOVA_AI_DATA_DIR . 'logs/');
            wp_mkdir_p(NOVA_AI_DATA_DIR . 'conversations/');
        }
    }
    
    // Core options with defaults (only if not already set)
    update_option('nova_ai_version', NOVA_AI_VERSION);
    
    // AI Provider settings
    add_option('nova_ai_api_type', 'ollama');
    add_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    add_option('nova_ai_model', 'mistral');
    add_option('nova_ai_system_prompt', 'Du bist Nova, ein hilfreicher KI Assistent für AILinux Nutzer.');
    add_option('nova_ai_temperature', 0.7);
    add_option('nova_ai_max_tokens', 800);
    
    // Chat Interface settings
    add_option('nova_ai_theme_style', 'terminal');
    add_option('nova_ai_enable_fullsite_chat', false);
    add_option('nova_ai_chat_position', 'bottom-right');
    add_option('nova_ai_chat_welcome_message', 'Hi! I\'m Nova AI. How can I help you?');
    add_option('nova_ai_chat_button_text', 'Chat with Nova AI');
    add_option('nova_ai_chat_placeholder', 'Type your message...');
    
    // Flush rewrite rules for REST API
    flush_rewrite_rules();
}
// BUGFIX: Register the activation hook
register_activation_hook(__FILE__, 'nova_ai_activate');

/**
 * Plugin deactivation function
 */
function nova_ai_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'nova_ai_deactivate');

/**
 * Load plugin dependencies
 */
function nova_ai_load_dependencies() {
    // Core functionality
    require_once NOVA_AI_PLUGIN_DIR . 'includes/core.php';
    require_once NOVA_AI_PLUGIN_DIR . 'includes/knowledge.php';
    require_once NOVA_AI_PLUGIN_DIR . 'includes/theme-styles.php';
    require_once NOVA_AI_PLUGIN_DIR . 'includes/updater.php';
    
    // Admin functionality (only load in admin)
    if (is_admin()) {
        require_once NOVA_AI_PLUGIN_DIR . 'admin/settings.php';
        require_once NOVA_AI_PLUGIN_DIR . 'admin/functions.php';
    }
    
    // Public-facing functionality
    require_once NOVA_AI_PLUGIN_DIR . 'includes/chat.php';
    
    // Enable full-site chat if activated
    if (get_option('nova_ai_enable_fullsite_chat', false)) {
        require_once NOVA_AI_PLUGIN_DIR . 'includes/fullsite-chat.php';
    }
}
add_action('plugins_loaded', 'nova_ai_load_dependencies');

/**
 * Register all plugin settings
 */
function nova_ai_register_settings() {
    // AI Provider settings
    register_setting('nova_ai_ai_settings', 'nova_ai_api_type', [
        'type' => 'string',
        'default' => 'ollama',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_api_url', [
        'type' => 'string',
        'default' => 'http://host.docker.internal:11434/api/generate',
        'sanitize_callback' => 'esc_url_raw'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_api_key', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_model', [
        'type' => 'string',
        'default' => 'mistral',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_max_tokens', [
        'type' => 'integer',
        'default' => 750,
        'sanitize_callback' => 'absint'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_temperature', [
        'type' => 'number',
        'default' => 0.7,
        'sanitize_callback' => 'floatval'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_system_prompt', [
        'type' => 'string',
        'default' => 'You are Nova, a helpful AI assistant for AILinux users.',
        'sanitize_callback' => 'sanitize_textarea_field'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_debug_mode', [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    
    // Chat Interface Settings
    register_setting('nova_ai_chat_settings', 'nova_ai_theme_style', [
        'type' => 'string',
        'default' => 'terminal',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_custom_css', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_textarea_field'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_enable_fullsite_chat', [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_chat_position', [
        'type' => 'string',
        'default' => 'bottom-right',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_chat_welcome_message', [
        'type' => 'string',
        'default' => 'Hi! I\'m Nova AI. How can I help you?',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_chat_button_text', [
        'type' => 'string',
        'default' => 'Chat with Nova AI',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_chat_placeholder', [
        'type' => 'string',
        'default' => 'Type your message...',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    
    // Crawler settings
    register_setting('nova_ai_crawler', 'nova_ai_crawl_urls', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_textarea_field'
    ]);
    register_setting('nova_ai_crawler', 'nova_ai_crawl_depth', [
        'type' => 'integer',
        'default' => 1,
        'sanitize_callback' => 'absint'
    ]);
    register_setting('nova_ai_crawler', 'nova_ai_crawl_limit', [
        'type' => 'integer',
        'default' => 5000,
        'sanitize_callback' => 'absint'
    ]);
    register_setting('nova_ai_crawler', 'nova_ai_auto_import_knowledge', [
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
}
add_action('admin_init', 'nova_ai_register_settings');

/**
 * Enhanced shortcode implementation with analytics
 */
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

/**
 * Add settings link on plugin page
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'nova_ai_plugin_links');
function nova_ai_plugin_links($links) {
    $settings_link = '<a href="admin.php?page=nova-ai-brainpool">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Helper function for logging
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
