<?php
/**
 * Nova AI Brainpool - Provider Management
 * Verwaltung verschiedener KI-APIs (Ollama, OpenAI, etc.)
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

require_once(NOVA_AI_PLUGIN_PATH . 'includes/class-nova-ai-core.php');

class NovaAIProviders extends NovaAICore {
    
    private $providers;
    private $active_provider;
    
    public function __construct() {
        parent::__construct();
        $this->load_providers();
    }
    
    /**
     * Provider-Konfigurationen laden
     */
    private function load_providers() {
        $this->providers = get_option('nova_ai_providers', $this->get_default_providers());
        $this->active_provider = get_option('nova_ai_active_provider', 'ollama');
    }
    
    /**
     * Standard-Provider
     */
    private function get_default_providers() {
        return [
            'ollama' => [
                'name' => 'Ollama (Lokal)',
                'endpoint' => 'http://127.0.0.1:11434/api/chat',
                'models' => ['mixtral', 'mistral', 'llama2', 'codellama'],
                'default_model' => 'mixtral',
                'type' => 'ollama'
            ],
            'openai' => [
                'name' => 'OpenAI',
                'endpoint' => 'https://api.openai.com/v1/chat/completions',
                'models' => ['gpt-4', 'gpt-3.5-turbo', 'gpt-4-turbo'],
                'default_model' => 'gpt-3.5-turbo',
                'type' => 'openai',
                'api_key' => ''
            ]
        ];
    }
    
    /**
     * Verfügbare Provider abrufen
     */
    public function get_available_providers() {
        return $this->providers;
    }
    
    /**
     * Aktiven Provider abrufen
     */
    public function get_active_provider() {
        return $this->active_provider;
    }
    
    /**
     * KI-Anfrage senden
     */
    public function send_request($provider_key = '', $model = '', $prompt = '') {
        if (empty($provider_key)) {
            $provider_key = $this->active_provider;
        }
        
        $provider = $this->providers[$provider_key] ?? null;
        if (!$provider) {
            return [
                'success' => false,
                'error' => "Provider '{$provider_key}' nicht gefunden"
            ];
        }
        
        if (empty($model)) {
            $model = $provider['default_model'];
        }
        
        $this->log("Sending request to {$provider_key}/{$model}: " . substr($prompt, 0, 50) . '...');
        
        switch ($provider['type']) {
            case 'ollama':
                return $this->send_ollama_request($provider, $model, $prompt);
            case 'openai':
                return $this->send_openai_request($provider, $model, $prompt);
            default:
                return $this->send_custom_request($provider, $model, $prompt);
        }
    }
    
    /**
     * Ollama-Request
     */
    private function send_ollama_request($provider, $model, $prompt) {
        $system_prompt = get_option('nova_ai_system_prompt', 
            'Du bist Nova, die KI-Admin für das AILinux-Projekt.'
        );
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_prompt
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'stream' => false
        ];
        
        $response = $this->http_request($provider['endpoint'], [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);
        
        if (!$response['success']) {
            return $response;
        }
        
        $json_response = $this->parse_json_response($response);
        if (!$json_response['success']) {
            return $json_response;
        }
        
        $data = $json_response['data'];
        
        if (!isset($data['message']['content'])) {
            return [
                'success' => false,
                'error' => 'Invalid Ollama response format'
            ];
        }
        
        return [
            'success' => true,
            'message' => $data['message']['content'],
            'model' => $model,
            'provider' => 'ollama',
            'tokens_used' => $data['eval_count'] ?? 0
        ];
    }
    
    /**
     * OpenAI-Request
     */
    private function send_openai_request($provider, $model, $prompt) {
        $api_key = $provider['api_key'] ?? '';
        if (empty($api_key)) {
            return [
                'success' => false,
                'error' => 'OpenAI API Key fehlt'
            ];
        }
        
        $system_prompt = get_option('nova_ai_system_prompt', 
            'Du bist Nova, die KI-Admin für das AILinux-Projekt.'
        );
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_prompt
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.7
        ];
        
        $response = $this->http_request($provider['endpoint'], [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode($data)
        ]);
        
        if (!$response['success']) {
            return $response;
        }
        
        $json_response = $this->parse_json_response($response);
        if (!$json_response['success']) {
            return $json_response;
        }
        
        $data = $json_response['data'];
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return [
                'success' => false,
                'error' => 'Invalid OpenAI response format'
            ];
        }
        
        return [
            'success' => true,
            'message' => $data['choices'][0]['message']['content'],
            'model' => $model,
            'provider' => 'openai',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0
        ];
    }
    
    /**
     * Custom API Request
     */
    private function send_custom_request($provider, $model, $prompt) {
        // Implementierung für Custom APIs
        return [
            'success' => false,
            'error' => 'Custom API not implemented yet'
        ];
    }
    
    /**
     * Provider-Verbindung testen
     */
    public function test_connection($provider_key) {
        $provider = $this->providers[$provider_key] ?? null;
        if (!$provider) {
            return false;
        }
        
        // Einfacher Test mit kurzem Prompt
        $result = $this->send_request($provider_key, '', 'Test');
        return $result['success'];
    }
}
?>
