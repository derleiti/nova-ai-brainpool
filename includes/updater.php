<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Update Functions
 * Führt Plugin-Upgrades und Datenmigrationen durch
 */

// Automatisch bei Admin-Aufruf Updates prüfen
add_action('admin_init', 'nova_ai_check_updates');

function nova_ai_check_updates() {
    if (!defined('NOVA_AI_VERSION')) return;

    $current_version = get_option('nova_ai_version', '1.0');

    if ($current_version === NOVA_AI_VERSION) {
        return; // Keine Aktualisierung nötig
    }

    if (version_compare($current_version, '1.1', '<')) {
        nova_ai_update_to_1_1();
    }

    // Platzhalter für zukünftige Versionen
    // if (version_compare($current_version, '1.2', '<')) {
    //     nova_ai_update_to_1_2();
    // }

    update_option('nova_ai_version', NOVA_AI_VERSION);

    if (function_exists('nova_ai_log')) {
        nova_ai_log("Nova AI aktualisiert von $current_version auf " . NOVA_AI_VERSION, 'info');
    }
}

// Update auf Version 1.1 – neue Optionen, Verzeichnisse
function nova_ai_update_to_1_1() {
    if (defined('NOVA_AI_DATA_DIR')) {
        if (!file_exists(NOVA_AI_DATA_DIR . 'conversations/')) {
            wp_mkdir_p(NOVA_AI_DATA_DIR . 'conversations/');
        }
    }

    if (get_option('nova_ai_chat_welcome_message') === false) {
        add_option('nova_ai_chat_welcome_message', "Hi! I'm Nova AI. How can I help you?");
    }

    if (function_exists('nova_ai_log')) {
        nova_ai_log('Update-Routine für 1.1 abgeschlossen', 'info');
    }
}
