<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . '/knowledge.php';

add_action('rest_api_init', function () {
    register_rest_route('nova-ai/v1', '/knowledge.json', array(
        'methods' => 'GET',
        'callback' => 'nova_ai_export_knowledge',
        'permission_callback' => '__return_true'
    ));
});

function nova_ai_export_knowledge() {
    $cache_key = 'nova_ai_knowledge_export';
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return [
            'status' => 'cached',
            'items' => $cached
        ];
    }

    $knowledge = nova_ai_knowledge_base();
    if (empty($knowledge)) {
        return new WP_Error('no_knowledge', 'Keine Wissensdatenbank verfügbar.', ['status' => 404]);
    }

    set_transient($cache_key, $knowledge, 12 * HOUR_IN_SECONDS);

    if (function_exists('nova_ai_log')) {
        nova_ai_log('Knowledge export via REST ausgeliefert', 'info');
    }

    return [
        'status' => 'fresh',
        'items' => $knowledge
    ];
}
