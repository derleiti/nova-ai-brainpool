<?php
// Admin-Menü hinzufügen
add_action('admin_menu', function() {
    add_menu_page(
        'Nova AI Brainpool',
        'Nova AI Brainpool',
        'manage_options',
        'nova-ai-brainpool',
        'nova_ai_admin_settings_page',
        'dashicons-artificial-intelligence',
        100
    );
});

// Settings-Page Callback
function nova_ai_admin_settings_page() {
    echo '<div class="wrap">';
    echo '<h1>Nova AI Brainpool – Einstellungen & Info</h1>';
    echo '<p>Willkommen beim KI-Plugin. Verwende den Shortcode <code>[nova_ai_chat]</code> auf einer beliebigen Seite oder Beitrag für den Chat.</p>';

    // .env-Status prüfen
    $env_path = dirname(__DIR__) . '/.env';
    if (file_exists($env_path)) {
        echo '<p><b>.env gefunden!</b></p>';
        echo '<pre style="background:#222;color:#fff;padding:1em;border-radius:6px;">' . esc_html(file_get_contents($env_path)) . '</pre>';
    } else {
        echo '<p style="color:red;"><b>Keine .env-Datei gefunden.</b> Lege eine Datei <code>.env</code> im Plugin-Verzeichnis an!</p>';
    }

    echo '<hr>';
    echo '<p>Weitere Features folgen in Kürze. Fragen? <a href="mailto:admin@derleiti.de">admin@derleiti.de</a></p>';
    echo '</div>';
}
