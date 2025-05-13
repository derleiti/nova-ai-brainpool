<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . '/knowledge.php';

// API Endpoint für Knowledge-Export
add_action('rest_api_init', function () {
    register_rest_route('nova-ai/v1', '/knowledge.json', array(
        'methods' => 'GET',
        'callback' => 'nova_ai_export_knowledge',
        'permission_callback' => '__return_true'
    ));
});

function nova_ai_export_knowledge() {
    return nova_ai_knowledge_base();
}
