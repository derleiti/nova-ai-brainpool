<?php
/**
 * Nova AI Brainpool - Core-Klasse
 * Basis-Funktionalität für alle Components
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class NovaAICore {
    
    protected $plugin_path;
    protected $plugin_url;
    protected $version;
    
    public function __construct() {
        $this->plugin_path = NOVA_AI_PLUGIN_PATH;
        $this->plugin_url = NOVA_AI_PLUGIN_URL;
        $this->version = NOVA_AI_VERSION;
    }
    
    /**
     * Log-Nachricht erstellen
     */
    protected function log($message, $level = 'info') {
        if (WP_DEBUG) {
            error_log("Nova AI [{$level}]: {$message}");
        }
    }
    
    /**
     * .env-Datei laden
     */
    protected function load_env() {
        if (function_exists('nova_ai_brainpool_load_env')) {
            return nova_ai_brainpool_load_env($this->plugin_path . '.env');
        }
        
        // Fallback
        $env_file = $this->plugin_path . '.env';
        $env = [];
        
        if (file_exists($env_file)) {
            $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && $line[0] !== '#') {
                    list($key, $value) = explode('=', $line, 2);
                    $env[trim($key)] = trim($value, '"\'');
                }
            }
        }
        
        return $env;
    }
    
    /**
     * HTTP-Request senden
     */
    protected function http_request($url, $args = []) {
        $defaults = [
            'timeout' => 30,
            'method' => 'GET',
            'headers' => [
                'User-Agent' => 'Nova AI Brainpool/' . $this->version
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $this->log("HTTP Request: {$args['method']} {$url}");
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $this->log("HTTP Error: " . $response->get_error_message(), 'error');
            return [
                'success' => false,
                'error' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $this->log("HTTP Response: {$status_code}");
        
        return [
            'success' => ($status_code >= 200 && $status_code < 300),
            'status_code' => $status_code,
            'body' => $body,
            'headers' => wp_remote_retrieve_headers($response)
        ];
    }
    
    /**
     * JSON-Response verarbeiten
     */
    protected function parse_json_response($response) {
        if (!$response['success']) {
            return $response;
        }
        
        $data = json_decode($response['body'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response: ' . json_last_error_msg()
            ];
        }
        
        return [
            'success' => true,
            'data' => $data,
            'status_code' => $response['status_code']
        ];
    }
    
    /**
     * Sichere Datenverarbeitung
     */
    protected function sanitize_data($data, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($data);
            case 'url':
                return esc_url_raw($data);
            case 'textarea':
                return sanitize_textarea_field($data);
            case 'html':
                return wp_kses_post($data);
            default:
                return sanitize_text_field($data);
        }
    }
    
    /**
     * Cache-Funktionen
     */
    protected function get_cache($key, $default = null) {
        return get_transient("nova_ai_{$key}") ?: $default;
    }
    
    protected function set_cache($key, $value, $expiration = 3600) {
        return set_transient("nova_ai_{$key}", $value, $expiration);
    }
    
    protected function delete_cache($key) {
        return delete_transient("nova_ai_{$key}");
    }
}
?>
