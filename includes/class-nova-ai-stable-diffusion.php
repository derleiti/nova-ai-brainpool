<?php
/**
 * Nova AI Brainpool - Stable Diffusion Integration
 * Bildgenerierung mit Stable Diffusion API
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class Nova_AI_Stable_Diffusion {
    
    /**
     * Generiert ein Bild basierend auf dem Prompt
     * 
     * @param string $prompt Der Bildprompt
     * @return string|false Relativer Pfad zum generierten Bild oder false bei Fehler
     */
    public static function generate_image($prompt) {
        // Sicherheitsprüfung für Prompt
        $prompt = sanitize_text_field($prompt);
        
        // API-Konfiguration
        $api_url = get_option('nova_ai_stable_diffusion_url', 'http://localhost:7860/sdapi/v1/txt2img');
        
        // Payload vorbereiten
        $payload = json_encode([
            "prompt" => $prompt,
            "negative_prompt" => "nsfw, nude, violence, gore, ugly, deformed, blurry",
            "steps" => 30,
            "cfg_scale" => 7.5,
            "width" => 1920,
            "height" => 1080,
            "sampler_name" => "Euler a",
            "batch_size" => 1,
            "n_iter" => 1
        ]);

        // WordPress HTTP API verwenden statt curl
        $response = wp_remote_post($api_url, [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => $payload
        ]);

        // Fehlerbehandlung
        if (is_wp_error($response)) {
            error_log('Nova AI Stable Diffusion Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('Nova AI Stable Diffusion Error: HTTP ' . $response_code);
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (!isset($result['images'][0])) {
            error_log('Nova AI Stable Diffusion Error: No image in response');
            return false;
        }

        // Bild decodieren
        $image_data = base64_decode($result['images'][0]);
        if (!$image_data) {
            error_log('Nova AI Stable Diffusion Error: Failed to decode image');
            return false;
        }

        // Upload-Verzeichnis verwenden
        $upload_dir = wp_upload_dir();
        $nova_images_dir = $upload_dir['basedir'] . '/nova-ai-images';
        $nova_images_url = $upload_dir['baseurl'] . '/nova-ai-images';
        
        // Verzeichnis erstellen falls nicht vorhanden
        if (!file_exists($nova_images_dir)) {
            wp_mkdir_p($nova_images_dir);
        }

        // Dateiname generieren
        $filename = 'nova-ai-' . time() . '-' . wp_rand(1000, 9999) . '.png';
        $filepath = $nova_images_dir . '/' . $filename;

        // Bild speichern
        $saved = file_put_contents($filepath, $image_data);
        if (!$saved) {
            error_log('Nova AI Stable Diffusion Error: Failed to save image');
            return false;
        }

        // URL zurückgeben
        return $nova_images_url . '/' . $filename;
    }

    /**
     * Prüft ob Stable Diffusion verfügbar ist
     * 
     * @return bool
     */
    public static function is_available() {
        $api_url = get_option('nova_ai_stable_diffusion_url', 'http://localhost:7860/sdapi/v1/txt2img');
        
        // Versuche die API zu erreichen
        $response = wp_remote_get(str_replace('/txt2img', '/options', $api_url), [
            'timeout' => 5
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code === 200;
    }

    /**
     * Extrahiert den Bildprompt aus einer Nachricht
     * 
     * @param string $message Die Nachricht
     * @return string|false Der extrahierte Prompt oder false
     */
    public static function extract_image_prompt($message) {
        // Prüfe auf #image Tag
        if (stripos($message, '#image') === 0) {
            return trim(str_ireplace('#image', '', $message));
        }

        // Prüfe auf andere Bildanfragen
        $image_keywords = [
            'generiere ein bild',
            'erstelle ein bild',
            'zeige mir ein bild',
            'male mir',
            'zeichne mir',
            'generate an image',
            'create an image',
            'show me an image',
            'draw me',
            'paint me'
        ];

        $message_lower = strtolower($message);
        foreach ($image_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                // Extrahiere den Teil nach dem Keyword
                $parts = explode($keyword, $message_lower);
                if (isset($parts[1])) {
                    return trim($parts[1]);
                }
            }
        }

        return false;
    }
}
?>
