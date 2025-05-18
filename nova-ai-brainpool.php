<?php
/*
Plugin Name: Nova AI Brainpool
Plugin URI: https://derleiti.de
Description: Cyberpunk AI Plugin mit Chat, Vision (LLaVA), Ollama, .env-Unterstützung & Shortcode.
Version: 19.0
Author: Markus Leitermann
Author URI: https://derleiti.de
*/

if (!defined('ABSPATH')) {
    exit;
}

// === Plugin Bootstrap ===
require_once plugin_dir_path(__FILE__) . 'admin/env-loader.php';
require_once plugin_dir_path(__FILE__) . 'admin/settings.php';
require_once plugin_dir_path(__FILE__) . 'admin/functions.php';
require_once plugin_dir_path(__FILE__) . 'admin/rest-endpoints.php';

$includes = glob(plugin_dir_path(__FILE__) . 'includes/*.php');
foreach ($includes as $file) {
    require_once $file;
}
