<?php
/**
 * Nova AI Admin Console Class
 * 
 * Handles admin interface and dashboard functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nova_AI_Admin_Console {
    
    private $core;
    private $crawler;
    private $stable_diffusion;
    private $providers;
    private $novanet;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
        
        // AJAX handlers
        add_action('wp_ajax_nova_ai_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_nova_ai_get_usage_stats', array($this, 'get_usage_stats'));
        add_action('wp_ajax_nova_ai_get_quick_stats', array($this, 'get_quick_stats'));
        add_action('wp_ajax_nova_ai_get_crawler_status', array($this, 'get_crawler_status'));
        add_action('wp_ajax_nova_ai_run_crawler', array($this, 'run_crawler'));
        add_action('wp_ajax_nova_ai_clear_cache', array($this, 'clear_cache'));
        add_action('wp_ajax_nova_ai_export_settings', array($this, 'export_settings'));
        add_action('wp_ajax_nova_ai_import_settings', array($this, 'import_settings'));
        add_action('wp_ajax_nova_ai_autosave', array($this, 'autosave_settings'));
        
        // Initialize components
        $this->core = new Nova_AI_Core();
        $this->crawler = new Nova_AI_Crawler();
        $this->stable_diffusion = new Nova_AI_Stable_Diffusion();
        $this->providers = new Nova_AI_Providers();
        $this->novanet = new Nova_AI_NovaNet();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Nova AI', 'nova-ai-brainpool'),
            __('Nova AI', 'nova-ai-brainpool'),
            'manage_options',
            'nova-ai',
            array($this, 'dashboard_page'),
            'dashicons-robot',
            30
        );
        
        // Dashboard
        add_submenu_page(
            'nova-ai',
            __('Dashboard', 'nova-ai-brainpool'),
            __('Dashboard', 'nova-ai-brainpool'),
            'manage_options',
            'nova-ai',
            array($this, 'dashboard_page')
        );
        
        // Settings
        add_submenu_page(
            'nova-ai',
            __('Settings', 'nova-ai-brainpool'),
            __('Settings', 'nova-ai-brainpool'),
            'manage_options',
            'nova-ai-settings',
            array($this, 'settings_page')
        );
        
        // Crawler
        add_submenu_page(
            'nova-ai',
            __('Crawler', 'nova-ai-brainpool'),
            __('Crawler', 'nova-ai-brainpool'),
            'manage_options',
            'nova-ai-crawler',
            array($this, 'crawler_page')
        );
        
        // Image Generator
        add_submenu_page(
            'nova-ai',
            __('Image Generator', 'nova-ai-brainpool'),
            __('Image Generator', 'nova-ai-brainpool'),
            'manage_options',
            'nova-ai-images',
            array($this, 'images_page')
        );
        
        // Conversations
        add_submenu_page(
            'nova-ai',
            __('Conversations', 'nova-ai-brainpool'),
            __('Conversations', 'nova-ai-brainpool'),
            'manage_options',
            'nova-ai-conversations',
            array($this, 'conversations_page')
        );
        
        // NovaNet
        add_submenu_page(
            'nova-ai',
            __('NovaNet', 'nova-ai-brainpool'),
            __('NovaNet', 'nova-ai-brainpool'),
            'manage_options',
            'nova-ai-novanet',
            array($this, 'novanet_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // AI Settings
        register_setting('nova_ai_settings', 'nova_ai_api_key');
        register_setting('nova_ai_settings', 'nova_ai_api_url');
        register_setting('nova_ai_settings', 'nova_ai_model');
        register_setting('nova_ai_settings', 'nova_ai_max_tokens');
        register_setting('nova_ai_settings', 'nova_ai_temperature');
        register_setting('nova_ai_settings', 'nova_ai_system_prompt');
        register_setting('nova_ai_settings', 'nova_ai_active_provider');
        
        // Crawler Settings
        register_setting('nova_ai_crawler', 'nova_ai_crawl_enabled');
        register_setting('nova_ai_crawler', 'nova_ai_crawl_sites');
        register_setting('nova_ai_crawler', 'nova_ai_auto_crawl_enabled');
        register_setting('nova_ai_crawler', 'nova_ai_crawl_interval');
        register_setting('nova_ai_crawler', 'nova_ai_max_crawl_depth');
        register_setting('nova_ai_crawler', 'nova_ai_crawl_delay');
        
        // Image Settings
        register_setting('nova_ai_images', 'nova_ai_image_generation_enabled');
        register_setting('nova_ai_images', 'nova_ai_image_api_url');
        register_setting('nova_ai_images', 'nova_ai_max_image_size');
        
        // General Settings
        register_setting('nova_ai_general', 'nova_ai_save_conversations');
        register_setting('nova_ai_general', 'nova_ai_conversation_retention_days');
        
        // NovaNet Settings
        register_setting('nova_ai_novanet', 'nova_ai_novanet_enabled');
        register_setting('nova_ai_novanet', 'nova_ai_novanet_url');
        register_setting('nova_ai_novanet', 'nova_ai_novanet_api_key');
        register_setting('nova_ai_novanet', 'nova_ai_novanet_auto_share');
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['nova_ai_action']) && wp_verify_nonce($_POST['nova_ai_nonce'], 'nova_ai_admin_action')) {
            switch ($_POST['nova_ai_action']) {
                case 'run_crawler':
                    $this->handle_run_crawler();
                    break;
                    
                case 'clear_crawled_data':
                    $this->handle_clear_crawled_data();
                    break;
                    
                case 'test_image_api':
                    $this->handle_test_image_api();
                    break;
                    
                case 'register_novanet':
                    $this->handle_register_novanet();
                    break;
                    
                case 'cleanup_old_data':
                    $this->handle_cleanup_old_data();
                    break;
            }
        }
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $stats = $this->get_dashboard_stats();
        $system_info = $this->get_system_info();
        
        include NOVA_AI_PLUGIN_PATH . 'admin/views/dashboard.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $providers = $this->providers->get_providers();
        $active_provider = $this->providers->get_active_provider();
        
        include NOVA_AI_PLUGIN_PATH . 'admin/views/settings.php';
    }
    
    /**
     * Crawler page
     */
    public function crawler_page() {
        $crawler_status = $this->crawler->get_crawl_status();
        $crawled_content = $this->crawler->get_crawled_content_list(50);
        
        include NOVA_AI_PLUGIN_PATH . 'admin/views/crawler.php';
    }
    
    /**
     * Images page
     */
    public function images_page() {
        $generated_images = $this->stable_diffusion->get_user_images(0, 50); // All users
        $generation_stats = $this->stable_diffusion->get_generation_stats();
        
        include NOVA_AI_PLUGIN_PATH . 'admin/views/images.php';
    }
    
    /**
     * Conversations page
     */
    public function conversations_page() {
        $conversations = $this->get_all_conversations(50);
        $conversation_stats = $this->core->get_usage_stats();
        
        include NOVA_AI_PLUGIN_PATH . 'admin/views/conversations.php';
    }
    
    /**
     * NovaNet page
     */
    public function novanet_page() {
        $novanet_status = $this->novanet->get_status();
        $network_stats = $this->novanet->get_network_stats();
        
        include NOVA_AI_PLUGIN_PATH . 'admin/views/novanet.php';
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Message statistics
        $messages_table = $wpdb->prefix . 'nova_ai_messages';
        $conversations_table = $wpdb->prefix . 'nova_ai_conversations';
        $images_table = $wpdb->prefix . 'nova_ai_generated_images';
        $crawled_table = $wpdb->prefix . 'nova_ai_crawled_content';
        
        // Today's stats
        $today = date('Y-m-d');
        $stats['today'] = array(
            'messages' => $wpdb->get_var("SELECT COUNT(*) FROM $messages_table WHERE DATE(created_at) = '$today'"),
            'conversations' => $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table WHERE DATE(created_at) = '$today'"),
            'images' => $wpdb->get_var("SELECT COUNT(*) FROM $images_table WHERE DATE(created_at) = '$today'"),
            'crawled_pages' => $wpdb->get_var("SELECT COUNT(*) FROM $crawled_table WHERE DATE(crawled_at) = '$today'")
        );
        
        // Total stats
        $stats['total'] = array(
            'messages' => $wpdb->get_var("SELECT COUNT(*) FROM $messages_table"),
            'conversations' => $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table"),
            'images' => $wpdb->get_var("SELECT COUNT(*) FROM $images_table"),
            'crawled_pages' => $wpdb->get_var("SELECT COUNT(*) FROM $crawled_table WHERE status = 'crawled'")
        );
        
        // Last 7 days trend
        $stats['weekly_trend'] = array();
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $stats['weekly_trend'][] = array(
                'date' => $date,
                'messages' => $wpdb->get_var("SELECT COUNT(*) FROM $messages_table WHERE DATE(created_at) = '$date'"),
                'images' => $wpdb->get_var("SELECT COUNT(*) FROM $images_table WHERE DATE(created_at) = '$date'")
            );
        }
        
        return $stats;
    }
    
    /**
     * Get system information
     */
    private function get_system_info() {
        return array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => NOVA_AI_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'curl_version' => function_exists('curl_version') ? curl_version()['version'] : 'Not available',
            'openssl_version' => OPENSSL_VERSION_TEXT,
            'database_version' => $GLOBALS['wpdb']->db_version()
        );
    }
    
    /**
     * Get all conversations
     */
    private function get_all_conversations($limit = 50) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'nova_ai_conversations';
        $messages_table = $wpdb->prefix . 'nova_ai_messages';
        
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM $messages_table m WHERE m.conversation_id = c.conversation_id) as message_count,
                    (SELECT u.display_name FROM {$wpdb->users} u WHERE u.ID = c.user_id) as user_name
             FROM $conversations_table c 
             ORDER BY c.updated_at DESC 
             LIMIT %d",
            $limit
        ), ARRAY_A);
        
        return $conversations;
    }
    
    /**
     * AJAX: Test connection
     */
    public function test_connection() {
        check_ajax_referer('nova_ai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $api_url = sanitize_url($_POST['api_url'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($api_url)) {
            wp_send_json_error('API URL is required');
        }
        
        try {
            // Temporarily set the API details for testing
            $original_url = get_option('nova_ai_api_url');
            $original_key = get_option('nova_ai_api_key');
            
            update_option('nova_ai_api_url', $api_url);
            update_option('nova_ai_api_key', $api_key);
            
            $result = $this->core->test_api_connection();
            
            // Restore original settings
            update_option('nova_ai_api_url', $original_url);
            update_option('nova_ai_api_key', $original_key);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Get usage statistics
     */
    public function get_usage_stats() {
        check_ajax_referer('nova_ai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $stats = $this->get_dashboard_stats();
        
        // Format for chart
        $chart_data = array(
            'labels' => array(),
            'messages' => array(),
            'images' => array()
        );
        
        foreach ($stats['weekly_trend'] as $day) {
            $chart_data['labels'][] = date('M d', strtotime($day['date']));
            $chart_data['messages'][] = intval($day['messages']);
            $chart_data['images'][] = intval($day['images']);
        }
        
        wp_send_json_success($chart_data);
    }
    
    /**
     * AJAX: Get quick stats
     */
    public function get_quick_stats() {
        check_ajax_referer('nova_ai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $stats = $this->get_dashboard_stats();
        wp_send_json_success($stats['total']);
    }
    
    /**
     * AJAX: Get crawler status
     */
    public function get_crawler_status() {
        check_ajax_referer('nova_ai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $status = $this->crawler->get_crawl_status();
        wp_send_json_success($status);
    }
    
    /**
     * AJAX: Run crawler
     */
    public function run_crawler() {
        check_ajax_referer('nova_ai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        try {
            // Run crawler in background
            wp_schedule_single_event(time(), 'nova_ai_auto_crawl');
            wp_send_json_success(array('message' => 'Crawler started'));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Clear cache
     */
    public function clear_cache() {
        check_ajax_referer('nova_ai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear plugin-specific caches
        delete_transient_like('nova_ai_%');
        
        wp_send_json_success(array('message' => 'Cache cleared'));
    }
    
    /**
     * AJAX: Export settings
     */
    public function export_settings() {
        check_ajax_referer('nova_ai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $settings = array();
        
        // Get all Nova AI options
        global $wpdb;
        $options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'nova_ai_%'",
            ARRAY_A
        );
        
        foreach ($options as $option) {
            $settings[$option['option_name']] = maybe_unserialize($option['option_value']);
        }
        
        $export_data = array(
            'version' => NOVA_AI_VERSION,
            'exported_at' => current_time('mysql'),
            'site_url' => site_url(),
            'settings' => $settings
        );
        
        wp_send_json_success($export_data);
    }
    
    /**
     * AJAX: Import settings
     */
    public function import_settings() {
        check_ajax_referer('nova_ai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $import_data = json_decode(stripslashes($_POST['settings']), true);
        
        if (!$import_data || !isset($import_data['settings'])) {
            wp_send_json_error(array('message' => 'Invalid import data'));
        }
        
        $imported_count = 0;
        
        foreach ($import_data['settings'] as $option_name => $option_value) {
            if (strpos($option_name, 'nova_ai_') === 0) {
                update_option($option_name, $option_value);
                $imported_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('Imported %d settings', $imported_count),
            'count' => $imported_count
        ));
    }
    
    /**
     * AJAX: Auto-save settings
     */
    public function autosave_settings() {
        check_ajax_referer('nova_ai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $form_id = sanitize_text_field($_POST['form_id'] ?? '');
        $form_data = sanitize_text_field($_POST['form_data'] ?? '');
        
        // Store auto-saved data
        set_transient("nova_ai_autosave_{$form_id}", $form_data, HOUR_IN_SECONDS);
        
        wp_send_json_success(array('message' => 'Auto-saved'));
    }
    
    /**
     * Handle run crawler action
     */
    private function handle_run_crawler() {
        try {
            wp_schedule_single_event(time(), 'nova_ai_auto_crawl');
            add_settings_error('nova_ai_messages', 'crawler_started', __('Crawler started successfully', 'nova-ai-brainpool'), 'success');
        } catch (Exception $e) {
            add_settings_error('nova_ai_messages', 'crawler_error', __('Failed to start crawler: ', 'nova-ai-brainpool') . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Handle clear crawled data action
     */
    private function handle_clear_crawled_data() {
        try {
            $this->crawler->clear_crawled_content();
            add_settings_error('nova_ai_messages', 'data_cleared', __('Crawled data cleared successfully', 'nova-ai-brainpool'), 'success');
        } catch (Exception $e) {
            add_settings_error('nova_ai_messages', 'clear_error', __('Failed to clear data: ', 'nova-ai-brainpool') . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Handle test image API action
     */
    private function handle_test_image_api() {
        try {
            $result = $this->stable_diffusion->test_api_connection();
            if ($result['success']) {
                add_settings_error('nova_ai_messages', 'api_test_success', __('Image API connection successful', 'nova-ai-brainpool'), 'success');
            } else {
                add_settings_error('nova_ai_messages', 'api_test_error', __('Image API test failed: ', 'nova-ai-brainpool') . $result['message'], 'error');
            }
        } catch (Exception $e) {
            add_settings_error('nova_ai_messages', 'api_test_error', __('Image API test failed: ', 'nova-ai-brainpool') . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Handle register NovaNet action
     */
    private function handle_register_novanet() {
        try {
            $result = $this->novanet->register_node();
            if ($result) {
                add_settings_error('nova_ai_messages', 'novanet_registered', __('Successfully registered with NovaNet', 'nova-ai-brainpool'), 'success');
            } else {
                add_settings_error('nova_ai_messages', 'novanet_error', __('Failed to register with NovaNet', 'nova-ai-brainpool'), 'error');
            }
        } catch (Exception $e) {
            add_settings_error('nova_ai_messages', 'novanet_error', __('NovaNet registration failed: ', 'nova-ai-brainpool') . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Handle cleanup old data action
     */
    private function handle_cleanup_old_data() {
        try {
            // Cleanup old conversations
            $this->core->cleanup_old_conversations();
            
            // Cleanup old images
            $this->stable_diffusion->cleanup_old_images();
            
            add_settings_error('nova_ai_messages', 'cleanup_success', __('Old data cleaned up successfully', 'nova-ai-brainpool'), 'success');
        } catch (Exception $e) {
            add_settings_error('nova_ai_messages', 'cleanup_error', __('Cleanup failed: ', 'nova-ai-brainpool') . $e->getMessage(), 'error');
        }
    }
}

// Helper function to delete transients with pattern
function delete_transient_like($pattern) {
    global $wpdb;
    
    $pattern = str_replace('_', '\\_', $pattern);
    $pattern = str_replace('%', '\\%', $pattern);
    $pattern = $wpdb->esc_like($pattern);
    
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        '_transient_' . $pattern
    ));
    
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
        '_transient_timeout_' . $pattern
    ));
}
