<?php
/**
 * Nova AI NovaNet Class
 * 
 * Handles NovaNet integration and advanced AI networking features
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nova_AI_NovaNet {
    
    private $novanet_url;
    private $api_key;
    private $enabled;
    private $node_id;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->novanet_url = get_option('nova_ai_novanet_url', 'https://ailinux.me/novanet');
        $this->api_key = get_option('nova_ai_novanet_api_key', '');
        $this->enabled = get_option('nova_ai_novanet_enabled', false);
        $this->node_id = get_option('nova_ai_novanet_node_id', $this->generate_node_id());
    }
    
    /**
     * Generate unique node ID
     */
    private function generate_node_id() {
        $node_id = 'node_' . uniqid() . '_' . substr(hash('sha256', site_url()), 0, 8);
        update_option('nova_ai_novanet_node_id', $node_id);
        return $node_id;
    }
    
    /**
     * Register node with NovaNet
     */
    public function register_node() {
        if (!$this->enabled) {
            return false;
        }
        
        $registration_data = array(
            'node_id' => $this->node_id,
            'site_url' => site_url(),
            'site_name' => get_bloginfo('name'),
            'capabilities' => $this->get_node_capabilities(),
            'version' => NOVA_AI_VERSION,
            'timestamp' => current_time('timestamp')
        );
        
        try {
            $response = $this->make_novanet_request('register', $registration_data);
            
            if ($response && isset($response['success']) && $response['success']) {
                update_option('nova_ai_novanet_registered', true);
                update_option('nova_ai_novanet_last_registration', current_time('mysql'));
                
                nova_ai_log('Node registered with NovaNet: ' . $this->node_id, 'info');
                return true;
            }
            
        } catch (Exception $e) {
            nova_ai_log('NovaNet registration failed: ' . $e->getMessage(), 'error');
        }
        
        return false;
    }
    
    /**
     * Get node capabilities
     */
    private function get_node_capabilities() {
        return array(
            'chat' => true,
            'image_generation' => get_option('nova_ai_image_generation_enabled', true),
            'crawling' => get_option('nova_ai_crawl_enabled', true),
            'knowledge_sharing' => true,
            'api_forwarding' => true,
            'load_balancing' => true
        );
    }
    
    /**
     * Share knowledge with NovaNet
     */
    public function share_knowledge($content, $metadata = array()) {
        if (!$this->enabled || !$this->is_registered()) {
            return false;
        }
        
        $knowledge_data = array(
            'node_id' => $this->node_id,
            'content' => $content,
            'metadata' => array_merge($metadata, array(
                'source_site' => site_url(),
                'timestamp' => current_time('timestamp'),
                'content_hash' => hash('sha256', $content)
            )),
            'visibility' => 'public' // or 'private', 'network'
        );
        
        try {
            $response = $this->make_novanet_request('share-knowledge', $knowledge_data);
            
            if ($response && isset($response['success']) && $response['success']) {
                nova_ai_log('Knowledge shared with NovaNet', 'info');
                return $response['knowledge_id'] ?? true;
            }
            
        } catch (Exception $e) {
            nova_ai_log('Knowledge sharing failed: ' . $e->getMessage(), 'error');
        }
        
        return false;
    }
    
    /**
     * Query NovaNet for knowledge
     */
    public function query_knowledge($query, $limit = 5) {
        if (!$this->enabled || !$this->is_registered()) {
            return array();
        }
        
        $query_data = array(
            'node_id' => $this->node_id,
            'query' => $query,
            'limit' => $limit,
            'exclude_own' => true // Don't return our own shared knowledge
        );
        
        try {
            $response = $this->make_novanet_request('query-knowledge', $query_data);
            
            if ($response && isset($response['success']) && $response['success']) {
                return $response['results'] ?? array();
            }
            
        } catch (Exception $e) {
            nova_ai_log('NovaNet knowledge query failed: ' . $e->getMessage(), 'error');
        }
        
        return array();
    }
    
    /**
     * Request AI processing from network
     */
    public function request_network_processing($messages, $preferences = array()) {
        if (!$this->enabled || !$this->is_registered()) {
            return null;
        }
        
        $request_data = array(
            'node_id' => $this->node_id,
            'messages' => $messages,
            'preferences' => array_merge(array(
                'model_type' => 'any',
                'max_tokens' => 2048,
                'temperature' => 0.7,
                'priority' => 'normal'
            ), $preferences),
            'timestamp' => current_time('timestamp')
        );
        
        try {
            $response = $this->make_novanet_request('process-request', $request_data);
            
            if ($response && isset($response['success']) && $response['success']) {
                return $response['result'] ?? null;
            }
            
        } catch (Exception $e) {
            nova_ai_log('NovaNet processing request failed: ' . $e->getMessage(), 'error');
        }
        
        return null;
    }
    
    /**
     * Get network statistics
     */
    public function get_network_stats() {
        if (!$this->enabled) {
            return array();
        }
        
        try {
            $response = $this->make_novanet_request('network-stats', array(
                'node_id' => $this->node_id
            ));
            
            if ($response && isset($response['success']) && $response['success']) {
                return $response['stats'] ?? array();
            }
            
        } catch (Exception $e) {
            nova_ai_log('Failed to get network stats: ' . $e->getMessage(), 'error');
        }
        
        return array();
    }
    
    /**
     * Contribute processing power to network
     */
    public function contribute_processing($max_requests_per_hour = 10) {
        if (!$this->enabled || !$this->is_registered()) {
            return false;
        }
        
        $contribution_data = array(
            'node_id' => $this->node_id,
            'max_requests_per_hour' => $max_requests_per_hour,
            'available_models' => $this->get_available_models(),
            'system_specs' => $this->get_system_specs()
        );
        
        try {
            $response = $this->make_novanet_request('contribute-processing', $contribution_data);
            
            if ($response && isset($response['success']) && $response['success']) {
                update_option('nova_ai_novanet_contributing', true);
                nova_ai_log('Started contributing processing power to NovaNet', 'info');
                return true;
            }
            
        } catch (Exception $e) {
            nova_ai_log('Failed to contribute processing: ' . $e->getMessage(), 'error');
        }
        
        return false;
    }
    
    /**
     * Get available models for contribution
     */
    private function get_available_models() {
        // This would integrate with the providers system
        $providers = new Nova_AI_Providers();
        $active_provider = $providers->get_active_provider();
        
        return $active_provider ? $active_provider['models'] : array();
    }
    
    /**
     * Get basic system specifications
     */
    private function get_system_specs() {
        return array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        );
    }
    
    /**
     * Handle incoming network requests
     */
    public function handle_network_request($request_data) {
        if (!$this->enabled || !$this->is_contributing()) {
            return array('error' => 'Not accepting network requests');
        }
        
        // Validate request
        if (!$this->validate_network_request($request_data)) {
            return array('error' => 'Invalid request');
        }
        
        try {
            // Process the request using local AI
            $core = new Nova_AI_Core();
            $response = $core->process_chat_message(
                $request_data['messages'][count($request_data['messages']) - 1]['content'],
                'network_' . uniqid(),
                false // Don't use crawled data for network requests
            );
            
            return array(
                'success' => true,
                'result' => $response['response'],
                'node_id' => $this->node_id,
                'timestamp' => current_time('timestamp')
            );
            
        } catch (Exception $e) {
            nova_ai_log('Network request processing failed: ' . $e->getMessage(), 'error');
            return array('error' => 'Processing failed');
        }
    }
    
    /**
     * Validate incoming network request
     */
    private function validate_network_request($request_data) {
        // Basic validation
        if (!isset($request_data['messages']) || !is_array($request_data['messages'])) {
            return false;
        }
        
        if (empty($request_data['messages'])) {
            return false;
        }
        
        // Check rate limits
        if (!$this->check_rate_limits($request_data['source_node'] ?? 'unknown')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check rate limits for network requests
     */
    private function check_rate_limits($source_node) {
        $max_requests = get_option('nova_ai_novanet_max_requests_per_hour', 10);
        $current_hour = date('Y-m-d H');
        
        $request_count = get_transient("nova_novanet_requests_{$source_node}_{$current_hour}");
        
        if ($request_count === false) {
            $request_count = 0;
        }
        
        if ($request_count >= $max_requests) {
            return false;
        }
        
        // Increment counter
        set_transient("nova_novanet_requests_{$source_node}_{$current_hour}", $request_count + 1, 3600);
        
        return true;
    }
    
    /**
     * Make request to NovaNet
     */
    private function make_novanet_request($endpoint, $data) {
        $url = rtrim($this->novanet_url, '/') . '/api/' . $endpoint;
        
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'Nova AI Brainpool/' . NOVA_AI_VERSION
        );
        
        if (!empty($this->api_key)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        }
        
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 30
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('NovaNet request failed: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            throw new Exception("NovaNet returned error: {$response_code}");
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from NovaNet');
        }
        
        return $decoded_response;
    }
    
    /**
     * Check if node is registered
     */
    public function is_registered() {
        return get_option('nova_ai_novanet_registered', false);
    }
    
    /**
     * Check if node is contributing
     */
    public function is_contributing() {
        return get_option('nova_ai_novanet_contributing', false);
    }
    
    /**
     * Sync with NovaNet
     */
    public function sync_with_network() {
        if (!$this->enabled || !$this->is_registered()) {
            return false;
        }
        
        try {
            // Get latest network configuration
            $response = $this->make_novanet_request('sync', array(
                'node_id' => $this->node_id,
                'last_sync' => get_option('nova_ai_novanet_last_sync', '')
            ));
            
            if ($response && isset($response['success']) && $response['success']) {
                // Update local configuration if needed
                if (isset($response['config'])) {
                    $this->update_local_config($response['config']);
                }
                
                update_option('nova_ai_novanet_last_sync', current_time('mysql'));
                nova_ai_log('Synced with NovaNet', 'info');
                return true;
            }
            
        } catch (Exception $e) {
            nova_ai_log('NovaNet sync failed: ' . $e->getMessage(), 'error');
        }
        
        return false;
    }
    
    /**
     * Update local configuration from network
     */
    private function update_local_config($config) {
        // Update settings based on network recommendations
        if (isset($config['recommended_settings'])) {
            foreach ($config['recommended_settings'] as $setting => $value) {
                $option_name = 'nova_ai_' . $setting;
                update_option($option_name, $value);
            }
        }
        
        // Update model recommendations
        if (isset($config['recommended_models'])) {
            update_option('nova_ai_novanet_recommended_models', $config['recommended_models']);
        }
    }
    
    /**
     * Get NovaNet status
     */
    public function get_status() {
        return array(
            'enabled' => $this->enabled,
            'registered' => $this->is_registered(),
            'contributing' => $this->is_contributing(),
            'node_id' => $this->node_id,
            'last_sync' => get_option('nova_ai_novanet_last_sync', ''),
            'network_url' => $this->novanet_url
        );
    }
    
    /**
     * Auto-share crawled content with network
     */
    public function auto_share_crawled_content() {
        if (!$this->enabled || !get_option('nova_ai_novanet_auto_share', false)) {
            return;
        }
        
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_crawled_content';
        
        // Get recently crawled content that hasn't been shared
        $content = $wpdb->get_results(
            "SELECT * FROM $table 
             WHERE status = 'crawled' 
             AND metadata NOT LIKE '%\"shared_with_novanet\":true%'
             ORDER BY crawled_at DESC 
             LIMIT 10",
            ARRAY_A
        );
        
        foreach ($content as $item) {
            $metadata = json_decode($item['metadata'], true) ?: array();
            
            // Check if content is suitable for sharing
            if ($this->is_content_shareable($item)) {
                $knowledge_id = $this->share_knowledge($item['content'], array(
                    'title' => $item['title'],
                    'url' => $item['url'],
                    'type' => 'crawled_content'
                ));
                
                if ($knowledge_id) {
                    // Mark as shared
                    $metadata['shared_with_novanet'] = true;
                    $metadata['novanet_knowledge_id'] = $knowledge_id;
                    
                    $wpdb->update(
                        $table,
                        array('metadata' => json_encode($metadata)),
                        array('id' => $item['id'])
                    );
                }
            }
        }
    }
    
    /**
     * Check if content is suitable for sharing
     */
    private function is_content_shareable($content_item) {
        // Don't share private or sensitive content
        $sensitive_patterns = array(
            'password', 'api_key', 'secret', 'private',
            'confidential', 'internal', 'login'
        );
        
        $text = strtolower($content_item['title'] . ' ' . $content_item['content']);
        
        foreach ($sensitive_patterns as $pattern) {
            if (strpos($text, $pattern) !== false) {
                return false;
            }
        }
        
        // Check content length (too short or too long might not be useful)
        $content_length = strlen($content_item['content']);
        if ($content_length < 100 || $content_length > 10000) {
            return false;
        }
        
        return true;
    }
}
