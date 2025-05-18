<?php
/*
Plugin Name: Nova AI Brainpool
Plugin URI: https://derleiti.de
Description: KI-gestütztes Chat- und Wissenssystem für WordPress mit Anbindung an Ollama und Twitch.
Version: 1.0.0
Author: Markus Leitermann
Author URI: https://derleiti.de
License: GPLv2 or later
Text Domain: nova-ai-brainpool
*/

// Sicherheitsprüfung
if (!defined('ABSPATH')) {
    exit;
}

// Basis-Pfade definieren
define('NOVA_AI_BRAINPOOL_PATH', plugin_dir_path(__FILE__));
define('NOVA_AI_BRAINPOOL_URL', plugin_dir_url(__FILE__));
define('NOVA_AI_BRAINPOOL_VERSION', '1.0.0');

// Composer Autoloader oder eigene Loader (optional, falls vorhanden)
// require_once NOVA_AI_BRAINPOOL_PATH . 'vendor/autoload.php';

// .env laden (nur falls benötigt)
if (file_exists(NOVA_AI_BRAINPOOL_PATH . 'admin/env-loader.php')) {
    require_once NOVA_AI_BRAINPOOL_PATH . 'admin/env-loader.php';
}

// Haupt-Plugin-Funktionen laden
require_once NOVA_AI_BRAINPOOL_PATH . 'includes/core.php';
require_once NOVA_AI_BRAINPOOL_PATH . 'includes/api.php';
require_once NOVA_AI_BRAINPOOL_PATH . 'includes/chat.php';
require_once NOVA_AI_BRAINPOOL_PATH . 'includes/admin.php';
require_once NOVA_AI_BRAINPOOL_PATH . 'includes/ajax.php';
require_once NOVA_AI_BRAINPOOL_PATH . 'includes/knowledge.php';
require_once NOVA_AI_BRAINPOOL_PATH . 'includes/updater.php';
require_once NOVA_AI_BRAINPOOL_PATH . 'includes/fullsite-chat.php';

// Admin-UI laden
if (is_admin()) {
    require_once NOVA_AI_BRAINPOOL_PATH . 'admin/functions.php';
    require_once NOVA_AI_BRAINPOOL_PATH . 'admin/settings.php';
    require_once NOVA_AI_BRAINPOOL_PATH . 'admin/rest-endpoints.php';
}

// Initialisierung (optional in core.php oder init-Hook)
add_action('plugins_loaded', function () {
    do_action('nova_ai_brainpool_loaded');
});
