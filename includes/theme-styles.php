<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Theme Styles Manager
 * 
 * Handles loading and registering theme-specific styles
 */

// Register and enqueue theme styles
add_action('wp_enqueue_scripts', 'nova_ai_register_theme_styles');
function nova_ai_register_theme_styles() {
    // Get selected theme
    $theme = get_option('nova_ai_theme_style', 'terminal');
    
    // Base style
    wp_enqueue_style('nova-ai-style', NOVA_AI_PLUGIN_URL . 'assets/css/nova-ai.css', [], NOVA_AI_VERSION);
    
    // Additional themes
    if ($theme === 'dark') {
        wp_enqueue_style('nova-ai-style-dark', NOVA_AI_PLUGIN_URL . 'assets/css/style-dark.css', ['nova-ai-style'], NOVA_AI_VERSION);
    } elseif ($theme === 'terminal') {
        wp_enqueue_style('nova-ai-style-terminal', NOVA_AI_PLUGIN_URL . 'assets/css/style-terminal.css', ['nova-ai-style'], NOVA_AI_VERSION);
    } elseif ($theme === 'light') {
        wp_enqueue_style('nova-ai-style-light', NOVA_AI_PLUGIN_URL . 'assets/css/style-light.css', ['nova-ai-style'], NOVA_AI_VERSION);
    }
    
    // Add JavaScript for theme handling
    wp_enqueue_script('nova-ai-script', NOVA_AI_PLUGIN_URL . 'assets/js/nova-ai.js', ['jquery'], NOVA_AI_VERSION, true);
    wp_localize_script('nova-ai-script', 'nova_ai_settings', [
        'theme' => $theme
    ]);
}
