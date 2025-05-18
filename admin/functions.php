<?php
/**
 * Admin Functions for Nova AI Brainpool
 * 
 * @package Nova_AI_Brainpool
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue frontend scripts and styles
 */
function nova_ai_enqueue_scripts() {
    // Only load assets on pages where shortcode is used
    global $post;
    if (is_a($post, 'WP_Post') && (
        has_shortcode($post->post_content, 'nova_ai_chat') || 
        has_shortcode($post->post_content, 'nova_ai_search')
    )) {
        // Enqueue CSS
        wp_enqueue_style(
            'nova-ai-css',
            NOVA_AI_PLUGIN_URL . 'assets/css/nova-ai.css',
            array(),
            NOVA_AI_VERSION
        );

        // Enqueue JavaScript
        wp_enqueue_script(
            'nova-ai-chat',
            NOVA_AI_PLUGIN_URL . 'assets/js/nova-ai-chat.js',
            array('jquery'),
            NOVA_AI_VERSION,
            true
        );

        // Determine model
        $selected_model = get_option('nova_ai_model', 'zephyr');
        $resolved_model = ($selected_model === 'custom') 
            ? (defined('OLLAMA_MODEL') ? OLLAMA_MODEL : 'zephyr')
            : $selected_model;

        // Pass variables to JavaScript
        wp_localize_script('nova-ai-chat', 'nova_ai_vars', array(
            'chat_url' => defined('OLLAMA_CHAT_URL') ? OLLAMA_CHAT_URL : get_rest_url(null, 'nova-ai/v1/chat'),
            'model' => $resolved_model,
            'theme' => get_option('nova_ai_theme_style', 'terminal'),
            'placeholder' => __('Ask Nova AI something...', 'nova-ai-brainpool'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_ai_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'nova_ai_enqueue_scripts');

/**
 * Enqueue admin scripts and styles
 */
function nova_ai_enqueue_admin_scripts($hook) {
    // Only load on plugin settings pages
    if (strpos($hook, 'nova-ai') === false) {
        return;
    }
    
    // Admin CSS
    wp_enqueue_style(
        'nova-ai-admin',
        NOVA_AI_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        NOVA_AI_VERSION
    );
    
    // Admin JS
    wp_enqueue_script(
        'nova-ai-admin',
        NOVA_AI_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery'),
        NOVA_AI_VERSION,
        true
    );
    
    // Pass variables to JavaScript
    wp_localize_script('nova-ai-admin', 'nova_ai_admin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('nova_ai_admin_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'nova_ai_enqueue_admin_scripts');

/**
 * Update chat statistics
 */
function nova_ai_update_chat_stats() {
    $total_chats = get_option('nova_ai_total_chats', 0);
    $today_chats = get_option('nova_ai_today_chats', 0);
    $today_date = get_option('nova_ai_today_date', date('Y-m-d'));
    
    // Update total chats
    update_option('nova_ai_total_chats', $total_chats + 1);
    
    // Check if we need to reset daily counter
    if ($today_date !== date('Y-m-d')) {
        update_option('nova_ai_today_date', date('Y-m-d'));
        update_option('nova_ai_today_chats', 1);
    } else {
        update_option('nova_ai_today_chats', $today_chats + 1);
    }
}

/**
 * Check if user has exceeded rate limit
 */
function nova_ai_check_rate_limit($limit = 10) {
    // For logged-in users, store in user meta
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $user_requests = get_user_meta($user_id, 'nova_ai_requests', true);
        $last_request = get_user_meta($user_id, 'nova_ai_last_request', true);
        
        // Initialize if not set
        if (!$user_requests) {
            $user_requests = 0;
        }
        
        // Reset count if last request was more than 1 hour ago
        if ($last_request && (time() - $last_request) > 3600) {
            $user_requests = 0;
        }
        
        // Update counters
        $user_requests++;
        update_user_meta($user_id, 'nova_ai_requests', $user_requests);
        update_user_meta($user_id, 'nova_ai_last_request', time());
        
        return $user_requests > $limit;
    } 
    // For non-logged in users, use IP-based rate limiting with transients
    else {
        $ip_address = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        $transient_name = 'nova_ai_rate_' . md5($ip_address);
        $requests = get_transient($transient_name);
        
        if (false === $requests) {
            set_transient($transient_name, 1, 3600); // 1 hour expiration
            return false;
        }
        
        if ($requests >= $limit) {
            return true;
        }
        
        set_transient($transient_name, $requests + 1, 3600);
        return false;
    }
}
