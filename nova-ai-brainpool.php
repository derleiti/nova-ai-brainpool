<?php
/*
Plugin Name: Nova AI Brainpool
Description: Minimalistischer AI Chat im Terminal-Stil – powered by AILinux
Version: 1.2
Author: derleiti & Nova AI
Author URI: https://ailinux.me
Text Domain: nova-ai-brainpool
License: MIT
*/

if (!defined('ABSPATH')) exit;

// Define constants
define('NOVA_AI_VERSION', '1.2');
define('NOVA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NOVA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOVA_AI_DATA_DIR', wp_upload_dir()['basedir'] . '/nova-ai-brainpool/');
define('NOVA_AI_MIN_WP_VERSION', '5.0');
define('NOVA_AI_MIN_PHP_VERSION', '7.2');

/**
 * Plugin activation function
 * Checks requirements and creates necessary options and directories
 */
function nova_ai_activate() {
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, NOVA_AI_MIN_WP_VERSION, '<')) {
        deactivate_plugins(basename(__FILE__));
        wp_die(sprintf(
            'Nova AI requires WordPress version %s or higher. You are running version %s. Please upgrade WordPress or use an earlier version of the plugin.',
            NOVA_AI_MIN_WP_VERSION,
            $wp_version
        ));
    }

    // Check PHP version
    if (version_compare(PHP_VERSION, NOVA_AI_MIN_PHP_VERSION, '<')) {
        deactivate_plugins(basename(__FILE__));
        wp_die(sprintf(
            'Nova AI requires PHP version %s or higher. You are running version %s. Please upgrade PHP or use an earlier version of the plugin.',
            NOVA_AI_MIN_PHP_VERSION,
            PHP_VERSION
        ));
    }
    
    // Create data directories
    if (!file_exists(NOVA_AI_DATA_DIR)) {
        if (!wp_mkdir_p(NOVA_AI_DATA_DIR)) {
            // Log directory creation error
            error_log('Nova AI: Failed to create data directory: ' . NOVA_AI_DATA_DIR);
        } else {
            // Create subdirectories with error checking
            $subdirs = [
                'knowledge/general/',
                'logs/',
                'conversations/',
                'temp/'
            ];
            
            foreach ($subdirs as $subdir) {
                $full_path = NOVA_AI_DATA_DIR . $subdir;
                if (!wp_mkdir_p($full_path)) {
                    error_log('Nova AI: Failed to create directory: ' . $full_path);
                }
            }
            
            // Create .htaccess to protect sensitive data
            $htaccess_content = "# Prevent direct access to files\n" .
                                "<FilesMatch \"\\.(json|log)$\">\n" .
                                "Order Allow,Deny\n" .
                                "Deny from all\n" .
                                "</FilesMatch>";
            @file_put_contents(NOVA_AI_DATA_DIR . '.htaccess', $htaccess_content);
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
    
    // Initialize usage statistics
    add_option('nova_ai_total_chats', 0);
    add_option('nova_ai_today_chats', 0);
    add_option('nova_ai_today_date', date('Y-m-d'));
    add_option('nova_ai_shortcode_usage', 0);
    
    // Flush rewrite rules for REST API
    flush_rewrite_rules();
    
    // Log activation
    nova_ai_log('Plugin activated - version ' . NOVA_AI_VERSION, 'info');
}
register_activation_hook(__FILE__, 'nova_ai_activate');

/**
 * Plugin deactivation function
 */
function nova_ai_deactivate() {
    // Log deactivation
    nova_ai_log('Plugin deactivated', 'info');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'nova_ai_deactivate');

/**
 * Check system requirements and display admin notice if not met
 */
function nova_ai_check_requirements() {
    // Skip on activation as it's handled there
    if (isset($_GET['action']) && $_GET['action'] === 'activate') {
        return;
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, NOVA_AI_MIN_PHP_VERSION, '<')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>';
            echo sprintf(
                'Nova AI requires PHP version %s or higher. You are running version %s. The plugin may not function correctly.',
                NOVA_AI_MIN_PHP_VERSION,
                PHP_VERSION
            );
            echo '</p></div>';
        });
    }
    
    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, NOVA_AI_MIN_WP_VERSION, '<')) {
        add_action('admin_notices', function() use ($wp_version) {
            echo '<div class="error"><p>';
            echo sprintf(
                'Nova AI requires WordPress version %s or higher. You are running version %s. The plugin may not function correctly.',
                NOVA_AI_MIN_WP_VERSION,
                $wp_version
            );
            echo '</p></div>';
        });
    }
    
    // Check data directory writable
    if (!is_writable(NOVA_AI_DATA_DIR) && is_dir(NOVA_AI_DATA_DIR)) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>';
            echo sprintf(
                'Nova AI data directory is not writable: %s. Please check permissions.',
                NOVA_AI_DATA_DIR
            );
            echo '</p></div>';
        });
    }
}
add_action('admin_init', 'nova_ai_check_requirements');

/**
 * Load plugin dependencies with improved error handling
 */
function nova_ai_load_dependencies() {
    $required_files = [
        'knowledge' => 'includes/knowledge.php',
        'core' => 'includes/core.php',
        'chat' => 'includes/chat.php',
        'updater' => 'includes/updater.php'
    ];
    
    $admin_files = [
        'settings' => 'admin/settings.php',
        'functions' => 'admin/functions.php'
    ];
    
    $optional_files = [
        'fullsite_chat' => 'includes/fullsite-chat.php'
    ];
    
    $missing_files = [];
    
    // Load required files
    foreach ($required_files as $name => $file) {
        $full_path = NOVA_AI_PLUGIN_DIR . $file;
        if (file_exists($full_path)) {
            include_once $full_path;
        } else {
            $missing_files[] = $file;
        }
    }
    
    // Load admin files only in admin
    if (is_admin()) {
        foreach ($admin_files as $name => $file) {
            $full_path = NOVA_AI_PLUGIN_DIR . $file;
            if (file_exists($full_path)) {
                include_once $full_path;
            } else {
                $missing_files[] = $file;
            }
        }
    }
    
    // Load optional files
    foreach ($optional_files as $name => $file) {
        // Special handling for fullsite-chat
        if ($name === 'fullsite_chat' && get_option('nova_ai_enable_fullsite_chat', false)) {
            $full_path = NOVA_AI_PLUGIN_DIR . $file;
            if (file_exists($full_path)) {
                include_once $full_path;
            }
        }
    }
    
    // Log and notify admin if any required files are missing
    if (!empty($missing_files) && is_admin()) {
        $missing_files_str = implode(', ', $missing_files);
        nova_ai_log('Missing required files: ' . $missing_files_str, 'error');
        
        add_action('admin_notices', function() use ($missing_files_str) {
            echo '<div class="error"><p>';
            echo 'Nova AI plugin is missing required files: ' . esc_html($missing_files_str);
            echo '</p></div>';
        });
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
 * Enhanced shortcode implementation with analytics and error handling
 */
function nova_ai_chat_shortcode($atts = []) {
    // Parse attributes
    $attributes = shortcode_atts([
        'theme' => get_option('nova_ai_theme_style', 'terminal'),
        'placeholder' => '> Frag mich was...',
        'width' => '700px',
        'height' => '400px',
        'welcome' => '',
    ], $atts);
    
    // Record shortcode usage for analytics
    $usage_count = get_option('nova_ai_shortcode_usage', 0);
    update_option('nova_ai_shortcode_usage', $usage_count + 1);
    
    // Check if required files exist
    $css_file = NOVA_AI_PLUGIN_DIR . 'assets/chat-frontend.css';
    $js_file = NOVA_AI_PLUGIN_DIR . 'assets/chat-frontend.js';
    
    if (!file_exists($css_file) || !file_exists($js_file)) {
        nova_ai_log('Missing required assets for shortcode: CSS or JS file not found', 'error');
        return '<div class="nova-ai-error">Error: Chat interface could not be loaded. Please contact the administrator.</div>';
    }
    
    try {
        // Enqueue necessary styles and scripts
        wp_enqueue_style('nova-ai-style', NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.css', [], NOVA_AI_VERSION);
        wp_enqueue_script('nova-ai-script', NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.js', ['jquery'], NOVA_AI_VERSION, true);
        
        // Pass data to JavaScript
        wp_localize_script('nova-ai-script', 'nova_ai_vars', [
            'api_url' => rest_url('nova-ai/v1/chat'),
            'nonce' => wp_create_nonce('wp_rest'),
            'theme' => $attributes['theme'],
            'conversation_id' => uniqid('nova_'),
            'placeholder' => $attributes['placeholder'],
            'welcome' => $attributes['welcome']
        ]);
        
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
    } catch (Exception $e) {
        nova_ai_log('Error in shortcode: ' . $e->getMessage(), 'error');
        return '<div class="nova-ai-error">Error: Chat interface could not be loaded. Please try refreshing the page.</div>';
    }
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
 * Enhanced logging function with rotation and size limits
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
    
    // Check file size and rotate if necessary (max 1MB)
    if (file_exists($log_file) && filesize($log_file) > 1048576) { // 1MB
        $backup_file = $log_file . '.1';
        if (file_exists($backup_file)) {
            unlink($backup_file);
        }
        rename($log_file, $backup_file);
    }
    
    // Write log entry
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    
    // Log severe errors to WordPress error log as well
    if ($type === 'error') {
        error_log('Nova AI: ' . $message);
    }
    
    // Clean up old logs (keep only last 7 days)
    $files = glob($log_dir . 'nova-ai-*.log*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 7 * 24 * 60 * 60) { // 7 days
                unlink($file);
            }
        }
    }
}

/**
 * Register backend scripts and styles for admin pages
 */
function nova_ai_admin_scripts($hook) {
    // Only load on plugin pages
    if (strpos($hook, 'nova-ai') === false) {
        return;
    }
    
    wp_enqueue_style('nova-ai-admin-style', NOVA_AI_PLUGIN_URL . 'assets/css/admin.css', [], NOVA_AI_VERSION);
    wp_enqueue_script('nova-ai-admin-script', NOVA_AI_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], NOVA_AI_VERSION, true);
    
    // Pass data to admin script
    wp_localize_script('nova-ai-admin-script', 'nova_ai_admin', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('nova_ai_admin_nonce'),
        'version' => NOVA_AI_VERSION
    ]);
}
add_action('admin_enqueue_scripts', 'nova_ai_admin_scripts');

/**
 * Force usage tracking to clean up on version update
 */
function nova_ai_check_version() {
    $stored_version = get_option('nova_ai_version', '1.0');
    
    if (version_compare($stored_version, NOVA_AI_VERSION, '<')) {
        // Reset chat statistics each day
        $today = date('Y-m-d');
        $last_date = get_option('nova_ai_today_date', '');
        
        if ($last_date !== $today) {
            update_option('nova_ai_today_chats', 0);
            update_option('nova_ai_today_date', $today);
        }
        
        // Update version
        update_option('nova_ai_version', NOVA_AI_VERSION);
        
        // Log update
        nova_ai_log('Plugin updated from ' . $stored_version . ' to ' . NOVA_AI_VERSION, 'info');
    }
}
add_action('plugins_loaded', 'nova_ai_check_version');
