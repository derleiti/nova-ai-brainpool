<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Theming Functions
 * 
 * Provides theme-related functionality for the Nova AI plugin
 */

/**
 * Enqueue theme styles and scripts
 */
function nova_ai_enqueue_theme_assets() {
    $theme_style = get_option('nova_ai_theme_style', 'terminal');
    
    // Base styles
    wp_enqueue_style('nova-ai-base', NOVA_AI_PLUGIN_URL . 'assets/css/nova-ai.css', [], NOVA_AI_VERSION);
    
    // Theme-specific styles
    switch ($theme_style) {
        case 'dark':
            wp_enqueue_style('nova-ai-dark', NOVA_AI_PLUGIN_URL . 'assets/css/style-dark.css', ['nova-ai-base'], NOVA_AI_VERSION);
            break;
        case 'light':
            wp_enqueue_style('nova-ai-light', NOVA_AI_PLUGIN_URL . 'assets/css/style-light.css', ['nova-ai-base'], NOVA_AI_VERSION);
            break;
        case 'terminal':
        default:
            wp_enqueue_style('nova-ai-terminal', NOVA_AI_PLUGIN_URL . 'assets/css/style-terminal.css', ['nova-ai-base'], NOVA_AI_VERSION);
            break;
    }
    
    // Theme script
    wp_enqueue_script('nova-ai-theme', NOVA_AI_PLUGIN_URL . 'assets/js/nova-ai.js', ['jquery'], NOVA_AI_VERSION, true);
    wp_localize_script('nova-ai-theme', 'nova_ai_settings', [
        'theme' => $theme_style
    ]);
    
    // Custom CSS if available
    $custom_css = get_option('nova_ai_custom_css', '');
    if (!empty($custom_css)) {
        wp_add_inline_style('nova-ai-base', $custom_css);
    }
}
add_action('wp_enqueue_scripts', 'nova_ai_enqueue_theme_assets');
