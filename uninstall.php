<?php
/**
 * Uninstall procedure for Nova AI Brainpool
 * 
 * @package Nova_AI_Brainpool
 */

// If uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Remove all plugin options
function nova_ai_cleanup_options() {
    $options = array(
        'nova_ai_version',
        'nova_ai_api_type',
        'nova_ai_api_url',
        'nova_ai_api_key',
        'nova_ai_model',
        'nova_ai_max_tokens',
        'nova_ai_temperature',
        'nova_ai_system_prompt',
        'nova_ai_debug_mode',
        'nova_ai_theme_style',
        'nova_ai_custom_css',
        'nova_ai_enable_fullsite_chat',
        'nova_ai_chat_position',
        'nova_ai_chat_welcome_message',
        'nova_ai_chat_button_text',
        'nova_ai_chat_placeholder',
        'nova_ai_custom_knowledge',
        'nova_ai_crawl_urls',
        'nova_ai_crawl_depth',
        'nova_ai_crawl_limit',
        'nova_ai_auto_import_knowledge',
        'nova_ai_total_chats',
        'nova_ai_today_chats',
        'nova_ai_today_date',
        'nova_ai_shortcode_usage'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Remove transients
    delete_transient('nova_ai_api_cache');
    delete_transient('nova_ai_knowledge_export');
    
    // Clean up user meta for all users
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'nova_ai_%'");
}

// Optional data cleanup - uncomment to enable complete removal
// Define a function to recursively delete directories
function nova_ai_recursive_rmdir($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                $path = $dir . "/" . $file;
                if (is_dir($path)) {
                    nova_ai_recursive_rmdir($path);
                } else {
                    unlink($path);
                }
            }
        }
        rmdir($dir);
        return true;
    }
    return false;
}

// Perform option cleanup
nova_ai_cleanup_options();

// Uncomment to remove data directory
/*
$upload_dir = wp_upload_dir();
$plugin_data_dir = $upload_dir['basedir'] . '/nova-ai-brainpool/';
if (file_exists($plugin_data_dir)) {
    nova_ai_recursive_rmdir($plugin_data_dir);
}
*/
