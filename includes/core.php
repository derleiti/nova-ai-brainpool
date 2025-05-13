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
    wp_enqueue_style('nova-ai-style', plugins_url('../assets/chat-frontend.css', __FILE__));
    wp_enqueue_script('nova-ai-script', plugins_url('../assets/chat-frontend.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('nova-ai-script', 'nova_ai_vars', array(
        'api_url' => site_url('/wp-json/nova-ai/v1/chat')
    ));

    return '<div id="nova-ai-chatbot" data-api-url="' . esc_url(site_url('/wp-json/nova-ai/v1/chat')) . '"></div>';
}
