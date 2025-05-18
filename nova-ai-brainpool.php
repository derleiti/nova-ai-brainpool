<?php
/**
 * Plugin Name: Nova AI Brainpool
 * Plugin URI: https://derleiti.de
 * Description: AI Chat Plugin with Vision (LLaVA), Ollama, .env support & shortcode integration for WordPress.
 * Version: 1.0.0
 * Author: Markus Leitermann
 * Author URI: https://derleiti.de
 * Text Domain: nova-ai-brainpool
 * Domain Path: /languages
 * License: MIT
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NOVA_AI_VERSION', '1.0.0');
define('NOVA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NOVA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOVA_AI_BASENAME', plugin_basename(__FILE__));
define('NOVA_AI_DATA_DIR', wp_upload_dir()['basedir'] . '/nova-ai-brainpool/');

// Create data directory if it doesn't exist
if (!file_exists(NOVA_AI_DATA_DIR)) {
    wp_mkdir_p(NOVA_AI_DATA_DIR);
    wp_mkdir_p(NOVA_AI_DATA_DIR . 'conversations/');
}

// Load translations
add_action('plugins_loaded', 'nova_ai_load_textdomain');
function nova_ai_load_textdomain() {
    load_plugin_textdomain('nova-ai-brainpool', false, dirname(NOVA_AI_BASENAME) . '/languages');
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'nova_ai_activate');
register_deactivation_hook(__FILE__, 'nova_ai_deactivate');

// Activation function
function nova_ai_activate() {
    // Initialize options with defaults
    add_option('nova_ai_version', NOVA_AI_VERSION);
    add_option('nova_ai_api_type', 'ollama');
    add_option('nova_ai_api_url', 'http://localhost:11434/api/generate');
    add_option('nova_ai_model', 'zephyr');
    add_option('nova_ai_system_prompt', __('I am Nova, a helpful AI assistant for AILinux users.', 'nova-ai-brainpool'));
    add_option('nova_ai_temperature', 0.7);
    add_option('nova_ai_max_tokens', 800);
    add_option('nova_ai_theme_style', 'terminal');
    
    // Create statistics
    add_option('nova_ai_total_chats', 0);
    add_option('nova_ai_today_chats', 0);
    add_option('nova_ai_today_date', date('Y-m-d'));
    
    // Flush rewrite rules for REST API
    flush_rewrite_rules();
}

// Deactivation function
function nova_ai_deactivate() {
    // Clean up transients
    delete_transient('nova_ai_api_cache');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Optional logging function
if (!function_exists('nova_ai_log')) {
    function nova_ai_log($message, $type = 'info') {
        if (!get_option('nova_ai_debug_mode', false) && $type == 'debug') {
            return;
        }
        
        $log_file = NOVA_AI_DATA_DIR . 'nova-ai-log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] [{$type}] {$message}\n";
        
        error_log($log_message, 3, $log_file);
    }
}

// Load .env file if exists (.env loader must come first)
require_once NOVA_AI_PLUGIN_DIR . 'admin/env-loader.php';

// Load admin files
require_once NOVA_AI_PLUGIN_DIR . 'admin/settings.php';
require_once NOVA_AI_PLUGIN_DIR . 'admin/functions.php';
require_once NOVA_AI_PLUGIN_DIR . 'admin/rest-endpoints.php';

// Load includes in specific order
require_once NOVA_AI_PLUGIN_DIR . 'includes/class-nova-ai-api.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/knowledge.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/chat.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/core.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/knowledge-export.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/updater.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/ajax.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/fullsite-chat.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/admin.php';
