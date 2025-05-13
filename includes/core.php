<?php
if (!defined('ABSPATH')) exit;

// Install Routine - Removed duplicate function that was also in main plugin file

// Uninstall Routine
function nova_ai_uninstall_cleanup() {
    delete_option('nova_ai_version');
}

// Initialisierung
add_action('init', 'nova_ai_init');

function nova_ai_init() {
    add_shortcode('nova_ai_chat', 'nova_ai_chat_shortcode');
}

// Shortcode Output
function nova_ai_chat_shortcode() {
    // Get theme setting
    $theme = get_option('nova_ai_theme_style', 'terminal');
    
    // Enqueue theme-specific files
    if ($theme === 'terminal') {
        wp_enqueue_style('nova-ai-terminal', plugins_url('../assets/chat-frontend.css', __FILE__));
        wp_enqueue_script('nova-ai-terminal', plugins_url('../assets/chat-frontend.js', __FILE__), array('jquery'), null, true);
    } elseif ($theme === 'dark') {
        wp_enqueue_style('nova-ai-dark', plugins_url('../assets/style.css', __FILE__));
        wp_enqueue_script('nova-ai-dark', plugins_url('../assets/js/nova-ai-chat.js', __FILE__), array('jquery'), null, true);
    } else {
        wp_enqueue_style('nova-ai-light', plugins_url('../assets/nova-ai.css', __FILE__));
        wp_enqueue_script('nova-ai-light', plugins_url('../assets/nova-ai.js', __FILE__), array('jquery'), null, true);
    }
    
    // Add custom CSS if available
    $custom_css = get_option('nova_ai_custom_css', '');
    if (!empty($custom_css)) {
        wp_add_inline_style($theme === 'terminal' ? 'nova-ai-terminal' : ($theme === 'dark' ? 'nova-ai-dark' : 'nova-ai-light'), $custom_css);
    }
    
    // Add data for JS
    wp_localize_script($theme === 'terminal' ? 'nova-ai-terminal' : ($theme === 'dark' ? 'nova-ai-dark' : 'nova-ai-light'), 'nova_ai_vars', array(
        'api_url' => rest_url('nova-ai/v1/chat'),
        'nonce' => wp_create_nonce('wp_rest')
    ));
    
    return '<div id="nova-ai-chatbot" data-api-url="' . esc_url(rest_url('nova-ai/v1/chat')) . '"></div>';
}
