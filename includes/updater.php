<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Update Functions
 * 
 * Handles plugin updates and database migrations
 */

/**
 * Check for plugin updates and perform necessary migrations
 */
function nova_ai_check_updates() {
    $current_version = get_option('nova_ai_version', '1.0');
    
    // If versions match, no update needed
    if ($current_version === NOVA_AI_VERSION) {
        return;
    }
    
    // Version-specific updates
    if (version_compare($current_version, '1.1', '<')) {
        nova_ai_update_to_1_1();
    }
    
    // Update version number
    update_option('nova_ai_version', NOVA_AI_VERSION);
}
add_action('admin_init', 'nova_ai_check_updates');

/**
 * Update tasks for version 1.1
 */
function nova_ai_update_to_1_1() {
    // Create directories if they don't exist
    if (!file_exists(NOVA_AI_DATA_DIR . 'conversations/')) {
        wp_mkdir_p(NOVA_AI_DATA_DIR . 'conversations/');
    }
    
    // Add new options
    if (get_option('nova_ai_chat_welcome_message') === false) {
        add_option('nova_ai_chat_welcome_message', "Hi! I'm Nova AI. How can I help you?");
    }
    
    // Log the update
    nova_ai_log('Updated plugin to version 1.1', 'info');
}
