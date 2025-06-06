<?php
/**
 * Nova AI Brainpool - Stable Diffusion Integration
 * Bildgenerierung mit Stable Diffusion WebUI
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

require_once(NOVA_AI_PLUGIN_PATH . 'includes/class-nova-ai-core.php');

class NovaAIStableDiffusion extends NovaAICore {
    
    private $config;
    
    public function __construct() {
        parent::__construct();
        $this->load_config();
    }
    
    /**
     * Konfiguration laden
     */
    private function load_config() {
        $this->config = get_option('nova_ai_stable_diffusion', [
            'enabled' => false,
            'endpoint' => 'http://127.0.0.1:7860',
            'resolution' => '1024x1024',
            'auto_detect' => true
        ]);
    }
    
    /**
     * Prüfe ob Stable Diffusion aktiviert ist
     */
    public function is_enabled() {
        return $this->config['enabled'] ?? false;
    }
    
    /**
     * Bild generieren
     */
    public function generate_image($prompt, $options = []) {
        if (!$this->is_enabled()) {
            return [
                'success' => false,
                'error' => 'Stable Diffusion ist nicht aktiviert'
            ];
        }
        
        // Standard-Optionen
        $defaults = [
            'resolution' => $this->config['resolution'] ?? '1024x1024',
            'steps' => 20,
            'cfg_scale' => 7.5,
            'negative_prompt' => 'blurry, bad quality, distorted',
            'sampler' => 'DPM++ 2M Karras'
        ];
        
        $options = array_merge($defaults, $options);
        
        // Auflösung parsen
        list($width, $height) = explode('x', $options['resolution']);
        
        $this->log("Generating image with prompt: " . substr($prompt, 0, 50) . '...');
        
        // SD WebUI API Request
        $data = [
            'prompt' => $prompt,
            'negative_prompt' => $options['negative_prompt'],
            'width' => intval($width),
            'height' => intval($height),
            'steps' => intval($options['steps']),
            'cfg_scale' => floatval($options['cfg_scale']),
            'sampler_name' => $options['sampler'],
            'batch_size' => 1,
            'n_iter' => 1
        ];
        
        $endpoint = rtrim($this->config['endpoint'], '/') . '/sdapi/v1/txt2img';
        
        $response = $this->http_request($endpoint, [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data),
            'timeout' => 120 // Länger Timeout für Bildgenerierung
        ]);
        
        if (!$response['success']) {
            return [
                'success' => false,
                'error' => 'Stable Diffusion API Error: ' . ($response['error'] ?? 'Unknown error')
            ];
        }
        
        $json_response = $this->parse_json_response($response);
        if (!$json_response['success']) {
            return $json_response;
        }
        
        $result = $json_response['data'];
        
        if (!isset($result['images']) || empty($result['images'])) {
            return [
                'success' => false,
                'error' => 'No images generated'
            ];
        }
        
        // Erstes Bild nehmen
        $image_data = $result['images'][0];
        
        // Bild speichern
        $saved_image = $this->save_generated_image($image_data, $prompt);
        
        if (!$saved_image['success']) {
            return $saved_image;
        }
        
        return [
            'success' => true,
            'image_url' => $saved_image['url'],
            'image_path' => $saved_image['path'],
            'prompt' => $prompt,
            'settings' => $options
        ];
    }
    
    /**
     * Generiertes Bild speichern
     */
    private function save_generated_image($base64_data, $prompt) {
        // Upload-Verzeichnis erstellen
        $upload_dir = wp_upload_dir();
        $nova_dir = $upload_dir['basedir'] . '/nova-ai-images';
        
        if (!file_exists($nova_dir)) {
            wp_mkdir_p($nova_dir);
        }
        
        // Dateiname generieren
        $filename = 'nova-ai-' . date('Y-m-d-H-i-s') . '-' . wp_rand(1000, 9999) . '.png';
        $file_path = $nova_dir . '/' . $filename;
        $file_url = $upload_dir['baseurl'] . '/nova-ai-images/' . $filename;
        
        // Base64 dekodieren und speichern
        $image_data = base64_decode($base64_data);
        
        if (!$image_data) {
            return [
                'success' => false,
                'error' => 'Failed to decode image data'
            ];
        }
        
        $saved = file_put_contents($file_path, $image_data);
        
        if (!$saved) {
            return [
                'success' => false,
                'error' => 'Failed to save image file'
            ];
        }
        
        // Metadaten speichern
        $this->save_image_metadata($filename, $prompt);
        
        $this->log("Image saved: {$filename}");
        
        return [
            'success' => true,
            'path' => $file_path,
            'url' => $file_url,
            'filename' => $filename
        ];
    }
    
    /**
     * Bild-Metadaten speichern
     */
    private function save_image_metadata($filename, $prompt) {
        $metadata = get_option('nova_ai_image_metadata', []);
        
        $metadata[$filename] = [
            'prompt' => $prompt,
            'generated_at' => time(),
            'user_id' => get_current_user_id(),
            'settings' => $this->config
        ];
        
        // Nur die letzten 100 Bilder speichern
        if (count($metadata) > 100) {
            $metadata = array_slice($metadata, -100, null, true);
        }
        
        update_option('nova_ai_image_metadata', $metadata);
    }
    
    /**
     * Stable Diffusion Verbindung testen
     */
    public function test_connection() {
        if (!$this->is_enabled()) {
            return false;
        }
        
        $endpoint = rtrim($this->config['endpoint'], '/') . '/app_id';
        
        $response = $this->http_request($endpoint, [
            'timeout' => 10
        ]);
        
        return $response['success'] && $response['status_code'] === 200;
    }
    
    /**
     * Verfügbare Modelle abrufen
     */
    public function get_available_models() {
        if (!$this->is_enabled()) {
            return [];
        }
        
        $endpoint = rtrim($this->config['endpoint'], '/') . '/sdapi/v1/sd-models';
        
        $response = $this->http_request($endpoint);
        
        if (!$response['success']) {
            return [];
        }
        
        $json_response = $this->parse_json_response($response);
        
        if (!$json_response['success']) {
            return [];
        }
        
        return $json_response['data'];
    }
    
    /**
     * Prompt-Verbesserung (optional)
     */
    public function enhance_prompt($prompt) {
        // Basis-Verbesserungen für bessere Bildqualität
        $enhancements = [
            'masterpiece',
            'best quality',
            'highly detailed',
            '8k resolution'
        ];
        
        $enhanced = $prompt . ', ' . implode(', ', $enhancements);
        
        return $enhanced;
    }
}
?>
