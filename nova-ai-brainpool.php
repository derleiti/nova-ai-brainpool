<?php
/*
Plugin Name: Nova AI Brainpool
Description: Minimalistischer AI Chat im Terminal-Stil – powered by AILinux
Version: 1.0
Author: derleiti & Nova AI
*/

if (!defined('ABSPATH')) exit;

// Includes
require_once plugin_dir_path(__FILE__) . 'includes/core.php';
require_once plugin_dir_path(__FILE__) . 'includes/chat.php';

// Admin Menü (optional, wenn settings.php vorhanden ist)
if (is_admin() && file_exists(plugin_dir_path(__FILE__) . 'admin/settings.php')) {
    require_once plugin_dir_path(__FILE__) . 'admin/settings.php';
}

// Aktivierung / Deaktivierung
register_activation_hook(__FILE__, 'nova_ai_install');
register_uninstall_hook(__FILE__, 'nova_ai_uninstall');
