<?php
// If this file is called directly, abort.
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Remove all plugin options for a clean uninstall
delete_option('nova_ai_version');
delete_option('nova_ai_api_type');
delete_option('nova_ai_api_url');
delete_option('nova_ai_api_key');
delete_option('nova_ai_model');
delete_option('nova_ai_max_tokens');
delete_option('nova_ai_temperature');
delete_option('nova_ai_system_prompt');
delete_option('nova_ai_debug_mode');
delete_option('nova_ai_theme_style');
delete_option('nova_ai_custom_css');
delete_option('nova_ai_enable_fullsite_chat');
delete_option('nova_ai_chat_position');
delete_option('nova_ai_chat_welcome_message');
delete_option('nova_ai_chat_button_text');
delete_option('nova_ai_chat_placeholder');
delete_option('nova_ai_custom_knowledge');
delete_option('nova_ai_crawl_urls');
delete_option('nova_ai_crawl_depth');
delete_option('nova_ai_crawl_limit');
delete_option('nova_ai_auto_import_knowledge');
delete_option('nova_ai_total_chats');
delete_option('nova_ai_today_chats');
delete_option('nova_ai_today_date');
delete_option('nova_ai_shortcode_usage');

// Optional: Remove plugin data directories
// Note: Uncomment this if you want to remove all user data on uninstall
/*
$upload_dir = wp_upload_dir();
$plugin_data_dir = $upload_dir['basedir'] . '/nova-ai-brainpool/';
if (file_exists($plugin_data_dir)) {
    nova_ai_recursive_rmdir($plugin_data_dir);
}

// Helper function to recursively remove a directory
function nova_ai_recursive_rmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    nova_ai_recursive_rmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}
*/
