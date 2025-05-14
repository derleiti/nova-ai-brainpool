<?php
if (!defined('ABSPATH')) exit;

// Install Routine
function nova_ai_install() {
    add_option('nova_ai_version', '1.0');
}

// Uninstall Routine
function nova_ai_uninstall() {
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
        
        // Add data for JS
        wp_localize_script('nova-ai-terminal', 'nova_ai_vars', array(
            'api_url' => rest_url('nova-ai/v1/chat'),
            'nonce' => wp_create_nonce('wp_rest')
        ));
        
        return '<div id="nova-ai-chatbot" data-api-url="' . esc_url(rest_url('nova-ai/v1/chat')) . '"></div>';
    } 
    elseif ($theme === 'dark') {
        wp_enqueue_style('nova-ai-dark', plugins_url('../assets/style.css', __FILE__));
        wp_enqueue_script('nova-ai-dark', plugins_url('../assets/js/nova-ai-chat.js', __FILE__), array('jquery'), null, true);
        
        // Add data for JS
        wp_localize_script('nova-ai-dark', 'nova_ai_vars', array(
            'api_url' => rest_url('nova-ai/v1/chat'),
            'nonce' => wp_create_nonce('wp_rest')
        ));
        
        return '<div id="nova-ai-chatbox" class="nova-ai-dark">
                    <div id="nova-ai-messages"></div>
                    <div id="nova-ai-input-area">
                        <input type="text" id="nova-ai-input" placeholder="Frag Nova AI etwas...">
                        <button id="nova-ai-send">Senden</button>
                    </div>
                </div>';
    }
    else {
        // Light theme or default
        wp_enqueue_style('nova-ai-light', plugins_url('../assets/nova-ai.css', __FILE__));
        wp_enqueue_script('nova-ai-light', plugins_url('../assets/nova-ai.js', __FILE__), array('jquery'), null, true);
        
        // Add data for AJAX
        wp_localize_script('nova-ai-light', 'nova_ai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_ai_nonce')
        ));
        
        return '<div id="nova-ai-chat"></div>';
    }
}
