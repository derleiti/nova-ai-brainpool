<?php
/**
 * Nova AI API Handler Class
 * 
 * Manages all API communication with Ollama and OpenAI
 * with optimized caching, error handling, and retries
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Nova_AI_API {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * API cache
     */
    private $cache = [];
    
    /**
     * Get an instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Initialize cache
        $this->cache = get_transient('nova_ai_api_cache');
        if (!is_array($this->cache)) {
            $this->cache = [];
        }
    }
    
    /**
     * Request handler for Ollama API
     */
    public function ollama_request($prompt, $model, $api_url, $system_prompt, $temperature, $max_tokens, $conversation = []) {
        // Sanitize and validate inputs
        $model = sanitize_text_field($model);
        $api_url = esc_url_raw($api_url);
        $system_prompt = sanitize_textarea_field($system_prompt);
        $temperature = floatval($temperature);
        $max_tokens = absint($max_tokens);
        
        // Normalize API URL
        $api_url = rtrim($api_url, '/');
        if (strpos($api_url, '/api/') === false) {
            $api_url .= '/api/generate';
        }
        
        // Check for cached response if caching is enabled
        if (apply_filters('nova_ai_enable_caching', true)) {
            $cache_key = md5($prompt . $model . $system_prompt . $temperature . $max_tokens);
            if (isset($this->cache[$cache_key])) {
                return $this->cache[$cache_key];
            }
        }
        
        // Format the request based on conversation history or single message
        if (!empty($conversation)) {
            // Format with conversation history
            $messages = [];
            
            // Add system message
            $messages[] = [
                'role' => 'system',
                'content' => $system_prompt
            ];
            
            // Add conversation history
            foreach ($conversation as $message) {
                $messages[] = [
                    'role' => $message['role'],
                    'content' => $message['content']
                ];
            }
            
            // Build request body
            $request_body = [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
                'temperature' => $temperature,
                'max_tokens' => $max_tokens
            ];
            
            // Detect if API supports chat completions format
            if (strpos($api_url, '/chat/completions') !== false || 
                strpos($api_url, '/api/chat') !== false) {
                // OpenAI-compatible chat format
            } else {
                // Fall back to Ollama generate
                $api_url = preg_replace('#/api/chat.*$#', '/api/generate', $api_url);
                
                // Convert messages to prompt for older Ollama versions
                $prompt_text = $system_prompt . "\n\n";
                foreach ($conversation as $message) {
                    $role = $message['role'] === 'assistant' ? 'Assistant' : 'User';
                    $prompt_text .= $role . ": " . $message['content'] . "\n\n";
                }
                $prompt_text .= "Assistant:";
                
                $request_body = [
                    'model' => $model,
                    'prompt' => $prompt_text,
                    'stream' => false,
                    'temperature' => $temperature,
                    'max_tokens' => $max_tokens
                ];
            }
        } else {
            // Simple prompt with system instruction
            $request_body = [
                'model' => $model,
                'prompt' => $system_prompt . "\n\nUser: " . $prompt . "\n\nAssistant:",
                'stream' => false,
                'temperature' => $temperature,
                'max_tokens' => $max_tokens
            ];
        }
        
        // Prepare request arguments
        $args = [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($request_body),
            'timeout' => 60,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'sslverify' => apply_filters('nova_ai_ssl_verify', true)
        ];
        
        // Log request for debugging
        if (get_option('nova_ai_debug_mode', false)) {
            nova_ai_log('Ollama API Request: ' . json_encode($request_body), 'debug');
        }
        
        // Make the request with retry logic
        $max_retries = apply_filters('nova_ai_max_retries', 3);
        $retry_delay = apply_filters('nova_ai_retry_delay', 1000); // in ms
        
        for ($retry = 0; $retry <= $max_retries; $retry++) {
            $response = wp_remote_post($api_url, $args);
            
            // Continue if no error
            if (!is_wp_error($response)) {
                $status_code = wp_remote_retrieve_response_code($response);
                
                // Success
                if ($status_code >= 200 && $status_code < 300) {
                    break;
                }
                
                // Don't retry client errors except for 429 (rate limit)
                if ($status_code >= 400 && $status_code < 500 && $status_code !== 429) {
                    break;
                }
            }
            
            // No more retries left
            if ($retry === $max_retries) {
                break;
            }
            
            // Exponential backoff
            $delay = $retry_delay * pow(2, $retry);
            usleep($delay * 1000);
        }
        
        // Handle response errors
        if (is_wp_error($response)) {
            nova_ai_log('Ollama API Error: ' . $response->get_error_message(), 'error');
            throw new Exception('Connection error: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            $body = wp_remote_retrieve_body($response);
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']) ? $error_data['error'] : 'HTTP Error: ' . $status_code;
            
            nova_ai_log('Ollama API Error: ' . $error_message . ' (Status: ' . $status_code . ')', 'error');
            throw new Exception('API Error: ' . $error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Handle different response formats
        $reply = '';
        if (isset($data['response'])) {
            // Standard Ollama response
            $reply = $data['response'];
        } elseif (isset($data['choices'][0]['message']['content'])) {
            // OpenAI-compatible format
            $reply = $data['choices'][0]['message']['content'];
        } elseif (isset($data['message']['content'])) {
            // Alternative chat format
            $reply = $data['message']['content'];
        } elseif (isset($data['generation'])) {
            // Some other LLM server format
            $reply = $data['generation'];
        } else {
            // Unexpected format
            nova_ai_log('Ollama API Error: Unexpected response format - ' . $body, 'error');
            throw new Exception('Unexpected response format from the AI provider.');
        }
        
        // Clean and trim response
        $reply = trim($reply);
        
        // Cache the response if caching is enabled
        if (apply_filters('nova_ai_enable_caching', true)) {
            $this->cache[$cache_key] = $reply;
            set_transient('nova_ai_api_cache', $this->cache, 12 * HOUR_IN_SECONDS); // 12 hours cache
        }
        
        return $reply;
    }
    
    /**
     * Request handler for OpenAI API
     */
    public function openai_request($prompt, $api_key, $system_prompt, $temperature, $max_tokens, $conversation = []) {
        // Sanitize and validate inputs
        $api_key = sanitize_text_field($api_key);
        $system_prompt = sanitize_textarea_field($system_prompt);
        $temperature = floatval($temperature);
        $max_tokens = absint($max_tokens);
        
        // Check for cached response if caching is enabled
        if (apply_filters('nova_ai_enable_caching', true)) {
            $cache_key = md5($prompt . $api_key . $system_prompt . $temperature . $max_tokens);
            if (isset($this->cache[$cache_key])) {
                return $this->cache[$cache_key];
            }
        }
        
        // Build the messages array
        $messages = [
            [
                'role' => 'system',
                'content' => $system_prompt
            ]
        ];
        
        // Add conversation history if available
        if (!empty($conversation)) {
            foreach ($conversation as $message) {
                $messages[] = [
                    'role' => $message['role'],
                    'content' => $message['content']
                ];
            }
        } else {
            // Just add the current message
            $messages[] = [
                'role' => 'user',
                'content' => $prompt
            ];
        }
        
        // Determine model based on plugin settings
        $model = 'gpt-3.5-turbo'; // Default model
        $openai_model = get_option('nova_ai_openai_model', '');
        if (!empty($openai_model)) {
            $model = $openai_model;
        }
        
        // Build request body
        $request_body = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        ];
        
        // Prepare request arguments
        $args = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode($request_body),
            'timeout' => 60,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'sslverify' => apply_filters('nova_ai_ssl_verify', true)
        ];
        
        // Log request for debugging
        if (get_option('nova_ai_debug_mode', false)) {
            nova_ai_log('OpenAI API Request: ' . json_encode($request_body), 'debug');
        }
        
        // Make the request with retry logic
        $max_retries = apply_filters('nova_ai_max_retries', 3);
        $retry_delay = apply_filters('nova_ai_retry_delay', 1000); // in ms
        
        for ($retry = 0; $retry <= $max_retries; $retry++) {
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);
            
            // Continue if no error
            if (!is_wp_error($response)) {
                $status_code = wp_remote_retrieve_response_code($response);
                
                // Success
                if ($status_code >= 200 && $status_code < 300) {
                    break;
                }
                
                // Rate limit - wait longer
                if ($status_code === 429) {
                    // Try to get retry-after header
                    $retry_after = wp_remote_retrieve_header($response, 'retry-after');
                    if ($retry_after) {
                        usleep(intval($retry_after) * 1000000); // Convert to microseconds
                        continue;
                    }
                }
                
                // Don't retry other client errors
                if ($status_code >= 400 && $status_code < 500 && $status_code !== 429) {
                    break;
                }
            }
            
            // No more retries left
            if ($retry === $max_retries) {
                break;
            }
            
            // Exponential backoff
            $delay = $retry_delay * pow(2, $retry);
            usleep($delay * 1000);
        }
        
        // Handle response errors
        if (is_wp_error($response)) {
            nova_ai_log('OpenAI API Error: ' . $response->get_error_message(), 'error');
            throw new Exception('Connection error: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code < 200 || $status_code >= 300) {
            $body = wp_remote_retrieve_body($response);
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'HTTP Error: ' . $status_code;
            
            nova_ai_log('OpenAI API Error: ' . $error_message . ' (Status: ' . $status_code . ')', 'error');
            throw new Exception('API Error: ' . $error_message);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Check if we have a valid response
        if (!isset($data['choices'][0]['message']['content'])) {
            nova_ai_log('OpenAI API Error: Unexpected response format - ' . $body, 'error');
            throw new Exception('Unexpected response format from the AI provider.');
        }
        
        // Extract and clean the response
        $reply = trim($data['choices'][0]['message']['content']);
        
        // Cache the response if caching is enabled
        if (apply_filters('nova_ai_enable_caching', true)) {
            $this->cache[$cache_key] = $reply;
            set_transient('nova_ai_api_cache', $this->cache, 12 * HOUR_IN_SECONDS); // 12 hours cache
        }
        
        return $reply;
    }
    
    /**
     * Clear API cache
     */
    public function clear_cache() {
        $this->cache = [];
        delete_transient('nova_ai_api_cache');
    }
}

// Initialize API handler
function nova_ai_get_api() {
    return Nova_AI_API::get_instance();
}
