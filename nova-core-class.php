<?php
/**
 * Nova AI Core Class
 * 
 * Handles core AI functionality, chat processing, and API communication
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nova_AI_Core {
    
    private $api_key;
    private $api_url;
    private $model;
    private $max_tokens;
    private $temperature;
    private $system_prompt;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('nova_ai_api_key', '');
        $this->api_url = get_option('nova_ai_api_url', 'https://ailinux.me/api/v1');
        $this->model = get_option('nova_ai_model', 'gpt-4');
        $this->max_tokens = intval(get_option('nova_ai_max_tokens', 2048));
        $this->temperature = floatval(get_option('nova_ai_temperature', 0.7));
        $this->system_prompt = get_option('nova_ai_system_prompt', 'You are Nova AI, a helpful and knowledgeable assistant.');
    }
    
    /**
     * Process chat message
     */
    public function process_chat_message($message, $conversation_id = '', $use_crawled_data = true) {
        if (empty($conversation_id)) {
            $conversation_id = $this->generate_conversation_id();
        }
        
        // Save user message
        $this->save_message($conversation_id, 'user', $message);
        
        // Get conversation history
        $conversation_history = $this->get_conversation_history($conversation_id);
        
        // Get relevant crawled content if enabled
        $context = '';
        if ($use_crawled_data && get_option('nova_ai_crawl_enabled', true)) {
            $context = $this->get_relevant_context($message);
        }
        
        // Prepare messages for API
        $messages = $this->prepare_messages($conversation_history, $context);
        
        // Call AI API
        $response = $this->call_ai_api($messages);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            $ai_response = $response['choices'][0]['message']['content'];
            
            // Save AI response
            $this->save_message($conversation_id, 'assistant', $ai_response);
            
            // Update conversation title if it's the first exchange
            $this->update_conversation_title($conversation_id, $message);
            
            return array(
                'success' => true,
                'response' => $ai_response,
                'conversation_id' => $conversation_id,
                'context_used' => !empty($context)
            );
        }
        
        throw new Exception(__('Failed to get AI response', 'nova-ai-brainpool'));
    }
    
    /**
     * Generate unique conversation ID
     */
    private function generate_conversation_id() {
        return 'conv_' . uniqid() . '_' . time();
    }
    
    /**
     * Save message to database
     */
    private function save_message($conversation_id, $role, $content, $metadata = null) {
        global $wpdb;
        
        // Ensure conversation exists
        $this->ensure_conversation_exists($conversation_id);
        
        $table = $wpdb->prefix . 'nova_ai_messages';
        
        $result = $wpdb->insert(
            $table,
            array(
                'conversation_id' => $conversation_id,
                'role' => $role,
                'content' => $content,
                'metadata' => $metadata ? json_encode($metadata) : null,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            nova_ai_log('Failed to save message: ' . $wpdb->last_error, 'error');
        }
        
        return $result;
    }
    
    /**
     * Ensure conversation exists in database
     */
    private function ensure_conversation_exists($conversation_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_conversations';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE conversation_id = %s",
            $conversation_id
        ));
        
        if (!$exists) {
            $wpdb->insert(
                $table,
                array(
                    'conversation_id' => $conversation_id,
                    'user_id' => get_current_user_id(),
                    'session_id' => nova_ai_get_session_id(),
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%d', '%s', '%s')
            );
        }
    }
    
    /**
     * Get conversation history
     */
    private function get_conversation_history($conversation_id, $limit = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_messages';
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT role, content, metadata, created_at 
             FROM $table 
             WHERE conversation_id = %s 
             ORDER BY created_at ASC 
             LIMIT %d",
            $conversation_id,
            $limit
        ), ARRAY_A);
        
        return $messages ? $messages : array();
    }
    
    /**
     * Get relevant context from crawled content
     */
    private function get_relevant_context($query, $limit = 5) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_crawled_content';
        
        // Use fulltext search if available
        $search_results = $wpdb->get_results($wpdb->prepare(
            "SELECT title, content, url 
             FROM $table 
             WHERE status = 'crawled' 
             AND MATCH(title, content) AGAINST(%s IN NATURAL LANGUAGE MODE)
             ORDER BY MATCH(title, content) AGAINST(%s IN NATURAL LANGUAGE MODE) DESC
             LIMIT %d",
            $query,
            $query,
            $limit
        ), ARRAY_A);
        
        // Fallback to LIKE search if no fulltext results
        if (empty($search_results)) {
            $search_results = $wpdb->get_results($wpdb->prepare(
                "SELECT title, content, url 
                 FROM $table 
                 WHERE status = 'crawled' 
                 AND (title LIKE %s OR content LIKE %s)
                 ORDER BY updated_at DESC
                 LIMIT %d",
                '%' . $wpdb->esc_like($query) . '%',
                '%' . $wpdb->esc_like($query) . '%',
                $limit
            ), ARRAY_A);
        }
        
        if (empty($search_results)) {
            return '';
        }
        
        $context = "Relevant information from crawled content:\n\n";
        foreach ($search_results as $result) {
            $context .= "Source: " . $result['url'] . "\n";
            $context .= "Title: " . $result['title'] . "\n";
            $context .= "Content: " . substr(strip_tags($result['content']), 0, 500) . "...\n\n";
        }
        
        return $context;
    }
    
    /**
     * Prepare messages for API call
     */
    private function prepare_messages($conversation_history, $context = '') {
        $messages = array();
        
        // System prompt with context
        $system_content = $this->system_prompt;
        if (!empty($context)) {
            $system_content .= "\n\nAdditional Context:\n" . $context;
        }
        
        $messages[] = array(
            'role' => 'system',
            'content' => $system_content
        );
        
        // Add conversation history
        foreach ($conversation_history as $msg) {
            $messages[] = array(
                'role' => $msg['role'],
                'content' => $msg['content']
            );
        }
        
        return $messages;
    }
    
    /**
     * Call AI API
     */
    private function call_ai_api($messages) {
        if (empty($this->api_key)) {
            throw new Exception(__('API key not configured', 'nova-ai-brainpool'));
        }
        
        $endpoint = rtrim($this->api_url, '/') . '/chat/completions';
        
        $data = array(
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->max_tokens,
            'temperature' => $this->temperature,
            'stream' => false
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode($data),
            'timeout' => 60
        );
        
        $response = wp_remote_request($endpoint, $args);
        
        if (is_wp_error($response)) {
            nova_ai_log('API request failed: ' . $response->get_error_message(), 'error');
            throw new Exception(__('API request failed', 'nova-ai-brainpool'));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            nova_ai_log('API returned error: ' . $response_code . ' - ' . $response_body, 'error');
            throw new Exception(sprintf(__('API returned error: %d', 'nova-ai-brainpool'), $response_code));
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            nova_ai_log('Invalid JSON response from API', 'error');
            throw new Exception(__('Invalid API response', 'nova-ai-brainpool'));
        }
        
        return $decoded_response;
    }
    
    /**
     * Update conversation title
     */
    private function update_conversation_title($conversation_id, $first_message) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_conversations';
        
        // Check if title is already set
        $current_title = $wpdb->get_var($wpdb->prepare(
            "SELECT title FROM $table WHERE conversation_id = %s",
            $conversation_id
        ));
        
        if (empty($current_title)) {
            // Generate title from first message (max 100 chars)
            $title = substr($first_message, 0, 100);
            if (strlen($first_message) > 100) {
                $title .= '...';
            }
            
            $wpdb->update(
                $table,
                array('title' => $title),
                array('conversation_id' => $conversation_id),
                array('%s'),
                array('%s')
            );
        }
    }
    
    /**
     * Get conversation list for user
     */
    public function get_user_conversations($user_id = null, $limit = 20) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $table = $wpdb->prefix . 'nova_ai_conversations';
        
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT conversation_id, title, created_at, updated_at 
             FROM $table 
             WHERE user_id = %d 
             ORDER BY updated_at DESC 
             LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);
        
        return $conversations ? $conversations : array();
    }
    
    /**
     * Delete old conversations
     */
    public function cleanup_old_conversations() {
        $retention_days = intval(get_option('nova_ai_conversation_retention_days', 30));
        
        if ($retention_days <= 0) {
            return; // Retention disabled
        }
        
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Get old conversation IDs
        $conversations_table = $wpdb->prefix . 'nova_ai_conversations';
        $messages_table = $wpdb->prefix . 'nova_ai_messages';
        
        $old_conversations = $wpdb->get_col($wpdb->prepare(
            "SELECT conversation_id FROM $conversations_table WHERE updated_at < %s",
            $cutoff_date
        ));
        
        if (!empty($old_conversations)) {
            $conversation_list = "'" . implode("','", array_map('esc_sql', $old_conversations)) . "'";
            
            // Delete messages
            $wpdb->query("DELETE FROM $messages_table WHERE conversation_id IN ($conversation_list)");
            
            // Delete conversations
            $wpdb->query("DELETE FROM $conversations_table WHERE conversation_id IN ($conversation_list)");
            
            nova_ai_log('Cleaned up ' . count($old_conversations) . ' old conversations', 'info');
        }
    }
    
    /**
     * Get AI usage statistics
     */
    public function get_usage_stats($days = 30) {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'nova_ai_messages';
        $conversations_table = $wpdb->prefix . 'nova_ai_conversations';
        
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Total messages
        $total_messages = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $messages_table WHERE created_at >= %s",
            $start_date
        ));
        
        // User messages
        $user_messages = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $messages_table WHERE role = 'user' AND created_at >= %s",
            $start_date
        ));
        
        // AI responses
        $ai_responses = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $messages_table WHERE role = 'assistant' AND created_at >= %s",
            $start_date
        ));
        
        // Active conversations
        $active_conversations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $conversations_table WHERE updated_at >= %s",
            $start_date
        ));
        
        // Average messages per conversation
        $avg_messages = $active_conversations > 0 ? round($total_messages / $active_conversations, 2) : 0;
        
        return array(
            'total_messages' => intval($total_messages),
            'user_messages' => intval($user_messages),
            'ai_responses' => intval($ai_responses),
            'active_conversations' => intval($active_conversations),
            'avg_messages_per_conversation' => $avg_messages,
            'period_days' => $days
        );
    }
    
    /**
     * Test API connection
     */
    public function test_api_connection() {
        try {
            $messages = array(
                array(
                    'role' => 'system',
                    'content' => 'You are a helpful assistant.'
                ),
                array(
                    'role' => 'user',
                    'content' => 'Hello, can you respond with "Connection test successful"?'
                )
            );
            
            $response = $this->call_ai_api($messages);
            
            if ($response && isset($response['choices'][0]['message']['content'])) {
                return array(
                    'success' => true,
                    'message' => __('API connection successful', 'nova-ai-brainpool'),
                    'response' => $response['choices'][0]['message']['content']
                );
            }
            
            return array(
                'success' => false,
                'message' => __('Invalid API response', 'nova-ai-brainpool')
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
}
