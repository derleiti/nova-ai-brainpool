<?php

// Sicherheitscheck
if (!defined('ABSPATH')) {
    exit;
}

// Grundinitialisierung
function nova_ai_brainpool_init()
{
    // Pfad- und URL-Konstanten setzen, falls noch nicht gesetzt
    if (!defined('NOVA_AI_BRAINPOOL_PATH')) {
        define('NOVA_AI_BRAINPOOL_PATH', plugin_dir_path(__FILE__));
    }
    if (!defined('NOVA_AI_BRAINPOOL_URL')) {
        define('NOVA_AI_BRAINPOOL_URL', plugin_dir_url(__FILE__));
    }

    // Ressourcen laden
    add_action('wp_enqueue_scripts', 'nova_ai_brainpool_enqueue_scripts');
    add_action('admin_enqueue_scripts', 'nova_ai_brainpool_enqueue_admin_scripts');

    // Shortcodes (registriert in settings.php – Duplikat entfernt)
}

add_action('init', 'nova_ai_brainpool_init');

// Frontend Assets laden
function nova_ai_brainpool_enqueue_scripts()
{
    wp_enqueue_style('nova-ai-style', NOVA_AI_BRAINPOOL_URL . 'assets/style.css', [], '1.0.0');
    wp_enqueue_script('nova-ai-script', NOVA_AI_BRAINPOOL_URL . 'assets/nova-ai.js', ['jquery'], '1.0.0', true);
}

// Admin Assets laden
function nova_ai_brainpool_enqueue_admin_scripts()
{
    wp_enqueue_style('nova-ai-admin-style', NOVA_AI_BRAINPOOL_URL . 'assets/css/admin.css', [], '1.0.0');
    wp_enqueue_script('nova-ai-admin-script', NOVA_AI_BRAINPOOL_URL . 'assets/js/admin.js', ['jquery'], '1.0.0', true);
}

// Render-Funktion für Chat-Shortcode
function nova_ai_brainpool_render_chat()
{
    ob_start();
    include(NOVA_AI_BRAINPOOL_PATH . 'templates/chat-template.php');
    return ob_get_clean();
}
