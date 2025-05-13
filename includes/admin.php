<?php
if (!defined('ABSPATH')) exit;

function nova_ai_admin_menu() {
    add_menu_page(
        'Nova AI Brainpool',
        'Nova AI',
        'manage_options',
        'nova-ai-brainpool',
        'nova_ai_admin_page',
        'dashicons-robot',
        100
    );
}
add_action('admin_menu', 'nova_ai_admin_menu');

function nova_ai_admin_page() {
    ?>
    <div class="wrap">
        <h1>Nova AI Brainpool</h1>
        <p>Version: 1.0-beta2</p>
        <p>Logs und Knowledge Base liegen unter: <code>/wp-content/uploads/nova-ai-brainpool/</code></p>
        <p>Shortcode für Chat: <code>[nova_ai_chat]</code></p>
        <p>Status: Lokal mit Ollama Mistral verbunden.</p>
    </div>
    <?php
}
?>
