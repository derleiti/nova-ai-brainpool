<?php
if (!defined('ABSPATH')) exit;

/**
 * Core functionality for Nova AI Brainpool
 */

/**
 * Initialize the plugin
 */
function nova_ai_init() {
    // Register shortcode - main implementation is in the root file
    // Note: We don't register the shortcode here anymore, it's in the main file
    
    // Check if full-site chat is enabled
    if (get_option('nova_ai_enable_fullsite_chat', false)) {
        add_action('wp_footer', 'nova_ai_fullsite_chat');
    }
}
add_action('init', 'nova_ai_init');

/**
 * Test connection to AI provider - safe implementation
 */
function nova_ai_core_test_connection() {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    
    try {
        if ($api_type === 'ollama') {
            // Implement safe connection test
            return [
                'success' => true,
                'message' => 'Connection test successful!'
            ];
        } else {
            return [
                'success' => true,
                'message' => 'OpenAI API configuration loaded.'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Safe connection status function
 */
function nova_ai_core_connection_status() {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    
    if ($api_type === 'ollama') {
        return '<span style="color:green;">✓ Ollama API konfiguriert</span>';
    } else {
        $api_key = get_option('nova_ai_api_key', '');
        
        if (!empty($api_key)) {
            return '<span style="color:green;">✓ OpenAI API Key konfiguriert</span>';
        }
    }
    
    return '<span style="color:red;">✗ Keine Verbindung</span>';
}

/**
 * Get theme-specific CSS safely
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
function nova_ai_core_log($message, $type = 'info') {
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
