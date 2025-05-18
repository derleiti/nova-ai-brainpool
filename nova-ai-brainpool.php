<?php
/*
Plugin Name: Nova AI Brainpool
Description: Minimalistischer AI Chat im Terminal-Stil – powered by AILinux
Version: 1.2
Author: derleiti & Nova AI
Author URI: https://ailinux.me
Text Domain: nova-ai-brainpool
License: MIT
*/

if (!defined('ABSPATH')) exit;

// Define constants
define('NOVA_AI_VERSION', '1.2');
define('NOVA_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NOVA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOVA_AI_DATA_DIR', wp_upload_dir()['basedir'] . '/nova-ai-brainpool/');
define('NOVA_AI_MIN_WP_VERSION', '5.0');
define('NOVA_AI_MIN_PHP_VERSION', '7.2');

// Load required files
require_once NOVA_AI_PLUGIN_DIR . 'includes/class-nova-ai-api.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/class-nova-ai-brainpool-api.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/chat.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/admin.php';
require_once NOVA_AI_PLUGIN_DIR . 'includes/core.php'; // falls benötigt

// Load translations
function nova_ai_load_textdomain() {
    load_plugin_textdomain('nova-ai-brainpool', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'nova_ai_load_textdomain');

// Plugin Activation
function nova_ai_activate() {
    global $wp_version;

    if (version_compare($wp_version, NOVA_AI_MIN_WP_VERSION, '<')) {
        deactivate_plugins(basename(__FILE__));
        wp_die("Nova AI benötigt WordPress Version " . NOVA_AI_MIN_WP_VERSION . " oder höher.");
    }

    if (version_compare(PHP_VERSION, NOVA_AI_MIN_PHP_VERSION, '<')) {
        deactivate_plugins(basename(__FILE__));
        wp_die("Nova AI benötigt PHP Version " . NOVA_AI_MIN_PHP_VERSION . " oder höher.");
    }

    // Ordnerstruktur anlegen
    if (!file_exists(NOVA_AI_DATA_DIR)) {
        if (wp_mkdir_p(NOVA_AI_DATA_DIR)) {
            foreach (['knowledge/general/', 'logs/', 'conversations/', 'temp/'] as $dir) {
                wp_mkdir_p(NOVA_AI_DATA_DIR . $dir);
            }
            $htaccess = "# Prevent direct access to files\n<FilesMatch \"\\.(json|log)$\">\nOrder Allow,Deny\nDeny from all\n</FilesMatch>";
            if (is_writable(NOVA_AI_DATA_DIR)) {
                file_put_contents(NOVA_AI_DATA_DIR . '.htaccess', $htaccess);
            }
        }
    }

    update_option('nova_ai_version', NOVA_AI_VERSION);
}
register_activation_hook(__FILE__, 'nova_ai_activate');
