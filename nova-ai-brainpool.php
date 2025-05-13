<?php
/*
Plugin Name: Nova AI Brainpool
Description: Minimalistischer AI Chat im Terminal-Stil – powered by AILinux
Version: 1.0
Author: derleiti & Nova AI
Author URI: https://ailinux.me
Text Domain: nova-ai-brainpool
License: MIT
*/

if (!defined('ABSPATH')) exit;

// Define constants
define('NOVA_AI_VERSION', '1.0');
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
require_once NOVA_AI_PLUGIN_DIR . 'includes/fullsite-chat.php';
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
        add_option('nova_ai_model', 'zephyr'); // Changed default to zephyr
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

// Check and add Zephyr model to Ollama if not already present
add_action('admin_init', 'nova_ai_check_zephyr_model');
function nova_ai_check_zephyr_model() {
    // Only run this check once
    if (get_option('nova_ai_zephyr_check_done', false)) {
        return;
    }

    $api_type = get_option('nova_ai_api_type', 'ollama');
    if ($api_type !== 'ollama') {
        return;
    }

    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/list');
    
    // Get list of models from Ollama
    $response = wp_remote_get($api_url, array('timeout' => 10));
    
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        // Check if Zephyr is in the list
        $has_zephyr = false;
        if (isset($result['models'])) {
            foreach ($result['models'] as $model) {
                if (isset($model['name']) && $model['name'] === 'zephyr') {
                    $has_zephyr = true;
                    break;
                }
            }
        }
        
        // If Zephyr is not found, add a notice
        if (!$has_zephyr) {
            add_action('admin_notices', 'nova_ai_zephyr_notice');
        }
    }
    
    // Mark check as done
    update_option('nova_ai_zephyr_check_done', true);
}

// Admin notice if Zephyr model is not found
function nova_ai_zephyr_notice() {
    ?>
    <div class="notice notice-warning is-dismissible">
        <p><strong>Nova AI Brainpool:</strong> The Zephyr model was not found in your Ollama installation. Please run <code>ollama pull zephyr</code> on your server to download the model, or select a different model in the plugin settings.</p>
    </div>
    <?php
}
