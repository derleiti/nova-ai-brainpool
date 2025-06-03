<?php
/**
 * Nova AI Providers Class
 * 
 * Manages different AI providers and API configurations
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nova_AI_Providers {
    
    private $providers;
    private $active_provider;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_providers();
        $this->active_provider = get_option('nova_ai_active_provider', 'ailinux');
    }
    
    /**
     * Initialize available providers
     */
    private function init_providers() {
        $this->providers = array(
            'ailinux' => array(
                'name' => 'AI Linux',
                'description' => 'Custom AI Linux API endpoint',
                'api_url' => 'https://ailinux.me/api/v1',
                'models' => array(
                    'gpt-4' => 'GPT-4',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'claude-3' => 'Claude 3',
                    'llama-2' => 'Llama 2'
                ),
                'supports_streaming' => true,
                'supports_functions' => true,
                'requires_api_key' => true
            ),
            'openai' => array(
                'name' => 'OpenAI',
                'description' => 'Official OpenAI API',
                'api_url' => 'https://api.openai.com/v1',
                'models' => array(
                    'gpt-4' => 'GPT-4',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
                ),
                'supports_streaming' => true,
                'supports_functions' => true,
                'requires_api_key' => true
            ),
            'anthropic' => array(
                'name' => 'Anthropic',
                'description' => 'Anthropic Claude API',
                'api_url' => 'https://api.anthropic.com/v1',
                'models' => array(
                    'claude-3-opus' => 'Claude 3 Opus',
                    'claude-3-sonnet' => 'Claude 3 Sonnet',
                    'claude-3-haiku' => 'Claude 3 Haiku'
                ),
                'supports_streaming' => true,
                'supports_functions' => false,
                'requires_api_key' => true
            ),
            'local' => array(
                'name' => 'Local LLM',
                'description' => 'Local language model (Ollama/LM Studio)',
                'api_url' => 'http://localhost:11434/v1',
                'models' => array(
                    'llama2' => 'Llama 2',
                    'mistral' => 'Mistral',
                    'codellama' => 'Code Llama',
                    'neural-chat' => 'Neural Chat'
                ),
                'supports_streaming' => true,
                'supports_functions' => false,
                'requires_api_key' => false
            )
        );
        
        // Allow custom providers via filter
        $this->providers = apply_filters('nova_ai_providers', $this->providers);
    }
    
    /**
     * Get all available providers
     */
    public function get_providers() {
        return $this->providers;
    }
    
    /**
     * Get active provider
     */
    public function get_active_provider() {
        return $this->providers[$this->active_provider] ?? null;
    }
    
    /**
     * Get provider by name
     */
    public function get_provider($name) {
        return $this->providers[$name] ?? null;
    }
    
    /**
     * Set active provider
     */
    public function set_active_provider($provider_name) {
        if (isset($this->providers[$provider_name])) {
            $this->active_provider = $provider_name;
            update_option('nova_ai_active_provider', $provider_name);
            return true;
        }
        return false;
    }
    
    /**
     * Get models for provider
     */
    public function get_provider_models($provider_name = null) {
        if (!$provider_name) {
            $provider_name = $this->active_provider;
        }
        
        $provider = $this->get_provider($provider_name);
        return $provider ? $provider['models'] : array();
    }
    
    /**
     * Get API URL for provider
     */
    public function get_api_url($provider_name = null) {
        if (!$provider_name) {
            $provider_name = $this->active_provider;
        }
        
        $provider = $this->get_provider($provider_name);
        return $provider ? $provider['api_url'] : '';
    }
    
    /**
     * Check if provider supports feature
     */
    public function provider_supports($feature, $provider_name = null) {
        if (!$provider_name) {
            $provider_name = $this->active_provider;
        }
        
        $provider = $this->get_provider($provider_name);
        return $provider ? ($provider["supports_{$feature}"] ?? false) : false;
    }
    
    /**
     * Make API call using active provider
     */
    public function make_api_call($endpoint, $data, $provider_name = null) {
        if (!$provider_name) {
            $provider_name = $this->active_provider;
        }
        
        $provider = $this->get_provider($provider_name);
        if (!$provider) {
            throw new Exception('Invalid provider');
        }
        
        $api_url = rtrim($provider['api_url'], '/') . '/' . ltrim($endpoint, '/');
        
        // Prepare headers
        $headers = array(
            'Content-Type' => 'application/json'
        );
        
        // Add API key if required
        if ($provider['requires_api_key']) {
            $api_key = $this->get_provider_api_key($provider_name);
            if (empty($api_key)) {
                throw new Exception('API key required for ' . $provider['name']);
            }
            
            // Different providers use different auth headers
            switch ($provider_name) {
                case 'anthropic':
                    $headers['x-api-key'] = $api_key;
                    $headers['anthropic-version'] = '2023-06-01';
                    break;
                default:
                    $headers['Authorization'] = 'Bearer ' . $api_key;
                    break;
            }
        }
        
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 60
        );
        
        $response = wp_remote_request($api_url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            throw new Exception("API returned error: {$response_code}");
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response');
        }
        
        return $decoded_response;
    }
    
    /**
     * Get API key for provider
     */
    private function get_provider_api_key($provider_name) {
        return get_option("nova_ai_{$provider_name}_api_key", '');
    }
    
    /**
     * Test provider connection
     */
    public function test_provider_connection($provider_name) {
        try {
            $provider = $this->get_provider($provider_name);
            if (!$provider) {
                return array(
                    'success' => false,
                    'message' => 'Provider not found'
                );
            }
            
            // Simple test message
            $test_data = array(
                'model' => array_keys($provider['models'])[0] ?? 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Hello, can you respond with "Connection test successful"?'
                    )
                ),
                'max_tokens' => 50
            );
            
            // Adapt data format for different providers
            $test_data = $this->adapt_data_for_provider($test_data, $provider_name);
            
            $response = $this->make_api_call('chat/completions', $test_data, $provider_name);
            
            // Check response format
            if ($this->validate_response($response, $provider_name)) {
                return array(
                    'success' => true,
                    'message' => 'Connection successful',
                    'provider' => $provider['name']
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Invalid response format'
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Adapt data format for specific providers
     */
    private function adapt_data_for_provider($data, $provider_name) {
        switch ($provider_name) {
            case 'anthropic':
                // Anthropic uses different format
                return array(
                    'model' => $data['model'],
                    'max_tokens' => $data['max_tokens'],
                    'messages' => $data['messages']
                );
                
            case 'local':
                // Local models might need different parameters
                unset($data['max_tokens']);
                $data['max_new_tokens'] = 50;
                break;
        }
        
        return $data;
    }
    
    /**
     * Validate API response
     */
    private function validate_response($response, $provider_name) {
        switch ($provider_name) {
            case 'anthropic':
                return isset($response['content']) && is_array($response['content']);
                
            default:
                return isset($response['choices']) && is_array($response['choices']);
        }
    }
    
    /**
     * Format response for consistency
     */
    public function format_response($response, $provider_name) {
        switch ($provider_name) {
            case 'anthropic':
                if (isset($response['content'][0]['text'])) {
                    return array(
                        'choices' => array(
                            array(
                                'message' => array(
                                    'content' => $response['content'][0]['text']
                                )
                            )
                        )
                    );
                }
                break;
                
            default:
                return $response;
        }
        
        return $response;
    }
    
    /**
     * Get provider usage stats
     */
    public function get_provider_usage_stats($provider_name, $days = 30) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_messages';
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // This would require adding provider tracking to messages table
        // For now, return basic stats
        $total_messages = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE created_at >= %s",
            $start_date
        ));
        
        return array(
            'provider' => $provider_name,
            'total_messages' => intval($total_messages),
            'period_days' => $days
        );
    }
    
    /**
     * Get recommended provider based on usage
     */
    public function get_recommended_provider() {
        // Simple logic - can be enhanced
        $providers = $this->get_providers();
        
        // Prefer AI Linux as default
        if (isset($providers['ailinux'])) {
            return 'ailinux';
        }
        
        // Fallback to first available
        return array_keys($providers)[0] ?? null;
    }
    
    /**
     * Check provider health
     */
    public function check_provider_health($provider_name = null) {
        if (!$provider_name) {
            $provider_name = $this->active_provider;
        }
        
        $provider = $this->get_provider($provider_name);
        if (!$provider) {
            return array(
                'status' => 'error',
                'message' => 'Provider not found'
            );
        }
        
        // Test basic connectivity
        $test_result = $this->test_provider_connection($provider_name);
        
        if ($test_result['success']) {
            return array(
                'status' => 'healthy',
                'message' => 'Provider is responding normally',
                'provider' => $provider['name']
            );
        } else {
            return array(
                'status' => 'unhealthy',
                'message' => $test_result['message'],
                'provider' => $provider['name']
            );
        }
    }
    
    /**
     * Get provider pricing info (if available)
     */
    public function get_provider_pricing($provider_name) {
        $pricing = array(
            'openai' => array(
                'gpt-4' => array('input' => 0.03, 'output' => 0.06, 'unit' => '1K tokens'),
                'gpt-3.5-turbo' => array('input' => 0.001, 'output' => 0.002, 'unit' => '1K tokens')
            ),
            'anthropic' => array(
                'claude-3-opus' => array('input' => 0.015, 'output' => 0.075, 'unit' => '1K tokens'),
                'claude-3-sonnet' => array('input' => 0.003, 'output' => 0.015, 'unit' => '1K tokens')
            ),
            'local' => array(
                'all_models' => array('input' => 0, 'output' => 0, 'unit' => 'Free')
            ),
            'ailinux' => array(
                'all_models' => array('input' => 'Custom', 'output' => 'Custom', 'unit' => 'Contact provider')
            )
        );
        
        return $pricing[$provider_name] ?? array();
    }
    
    /**
     * Switch provider automatically on failure
     */
    public function auto_switch_provider() {
        $providers = array_keys($this->providers);
        $current_index = array_search($this->active_provider, $providers);
        
        if ($current_index === false) {
            return false;
        }
        
        // Try next provider
        $next_index = ($current_index + 1) % count($providers);
        $next_provider = $providers[$next_index];
        
        // Test connection
        $test_result = $this->test_provider_connection($next_provider);
        
        if ($test_result['success']) {
            $this->set_active_provider($next_provider);
            nova_ai_log("Switched to provider: {$next_provider}", 'info');
            return $next_provider;
        }
        
        return false;
    }
}
