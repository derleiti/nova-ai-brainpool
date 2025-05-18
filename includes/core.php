<?php
if (!defined('ABSPATH')) exit;

/**
 * Core-Funktionen für Nova AI Brainpool Plugin
 */

// REST API prüfen (optional)
add_action('init', function () {
    // Hier könnten Initialisierungen stattfinden
});

// === Shortcode ===

add_shortcode('nova_ai_chat', 'nova_ai_chat_shortcode');

function nova_ai_chat_shortcode($atts = [], $content = null) {
    ob_start();
    ?>
    <div id="nova-ai-chat-box" style="max-width:600px;margin:0 auto;padding:1rem;border:1px solid #ccc;">
        <div id="nova-ai-chat-log" style="min-height:200px; margin-bottom:1rem;"></div>
        <input type="text" id="nova-ai-chat-input" placeholder="Frag Nova etwas..." style="width:100%;padding:0.5rem;">
        <button id="nova-ai-chat-send" style="margin-top:0.5rem;">Senden</button>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('nova-ai-chat-input');
        const log = document.getElementById('nova-ai-chat-log');
        const button = document.getElementById('nova-ai-chat-send');

        button.addEventListener('click', async () => {
            const prompt = input.value.trim();
            if (!prompt) return;
            log.innerHTML += `<div><b>Du:</b> ${prompt}</div>`;
            input.value = '';

            const response = await fetch('/wp-json/nova-ai/v1/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: prompt, conversation: [] })
            });

            const data = await response.json();
            if (data.reply) {
                log.innerHTML += `<div><b>Nova:</b> ${data.reply}</div>`;
            } else {
                log.innerHTML += `<div style="color:red;">Fehler bei der Antwort.</div>`;
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
