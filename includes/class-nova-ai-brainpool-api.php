<?php
/**
 * Nova AI Brainpool API Handler
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_AI_Brainpool_API {

    public function __construct() {
        add_action('wp_ajax_nova_ai_chat', array($this, 'handle_ai_chat'));
        add_action('wp_ajax_nopriv_nova_ai_chat', array($this, 'handle_ai_chat'));
    }

    public function handle_ai_chat() {
        $message = sanitize_text_field($_POST['message'] ?? '');

        if (empty($message)) {
            wp_send_json_error(__('Keine Nachricht übergeben.', 'nova-ai-brainpool'));
        }

        if (!function_exists('nova_ai_chat_response')) {
            wp_send_json_error(__('KI-Funktion nicht verfügbar.', 'nova-ai-brainpool'));
        }

        try {
            $ai_reply = nova_ai_chat_response($message);

            wp_send_json_success(array(
                'reply' => $ai_reply
            ));
        } catch (Exception $e) {
            wp_send_json_error(__('Fehler bei der KI-Antwort: ', 'nova-ai-brainpool') . $e->getMessage());
        }
    }
}
