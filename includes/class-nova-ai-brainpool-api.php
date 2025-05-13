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
            wp_send_json_error('No message provided.');
        }

        // AI Power - Dummy KI Antwort (hier später API Call z.B. OpenAI, Ollama, Nova API)
        $ai_reply = $this->get_ai_response($message);

        wp_send_json_success(array(
            'reply' => $ai_reply
        ));
    }

    private function get_ai_response($message) {

        // Platzhalter KI Logik
        if (stripos($message, 'hilfe') !== false) {
            return "Nova sagt: Benötigst du Hilfe? 📖";
        }

        if (stripos($message, 'update') !== false) {
            return "Nova meldet: Alle Systeme aktuell. 🧠✅";
        }

        // Default AI Antwort
        return "Nova: Ich habe deine Nachricht erhalten: \"$message\"";
    }
}
