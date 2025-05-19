<?php
/*
Plugin Name: Nova AI Brainpool
Description: Einfacher AI Chatbot und Knowledge Base für WordPress – powered by Ollama/Zephyr.
Version: 1.0
Author: derleiti
*/

if (!defined('ABSPATH')) exit;

// Admin-Menu und Settings-Page einbinden
add_action('admin_menu', function () {
    add_menu_page(
        'Nova AI Brainpool',
        'Nova AI Brainpool',
        'manage_options',
        'nova-ai-brainpool',
        function () {
            include __DIR__ . '/admin/settings.php';
        },
        'dashicons-admin-generic'
    );
});

// [nova_ai_chat] Shortcode für den Chat einbinden
add_shortcode('nova_ai_chat', function ($atts) {
    ob_start();
    ?>
    <div id="nova-ai-chat-container" style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100vw;min-height:55vh;max-width:100vw;">
        <div id="nova-ai-chat-box" style="width:100%;max-width:400px;background:#181f25;color:#aef989;margin:20px auto;padding:20px;border-radius:10px;box-shadow:0 0 16px #111;display:flex;flex-direction:column;align-items:stretch;">
            <div id="nova-ai-messages" style="min-height:80px;font-family:monospace;font-size:1rem;word-break:break-word;line-height:1.35em;"></div>
            <textarea id="nova-ai-input" style="margin-top:16px;width:100%;height:70px;resize:vertical;padding:8px;background:#222;color:#aef989;font-family:monospace;" placeholder="Frag die Nova KI... (Shift+Enter = neue Zeile)"></textarea>
            <button id="nova-ai-send" style="margin-top:10px;background:#286fbe;color:#fff;padding:6px 16px;border:none;border-radius:4px;font-size:1rem;cursor:pointer;">Senden</button>
        </div>
    </div>
    <script src="<?php echo plugins_url('assets/chat-frontend.js', __FILE__); ?>"></script>
    <link rel="stylesheet" href="<?php echo plugins_url('assets/chat-frontend.css', __FILE__); ?>">
    <?php
    return ob_get_clean();
});
