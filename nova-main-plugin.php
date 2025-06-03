<?php
/**
 * Plugin Name: Nova AI Brainpool
 * Plugin URI: https://ailinux.me
 * Description: Advanced AI-powered chat plugin with crawling, auto-crawling, and image generation capabilities
 * Version: 2.0.0
 * Author: Nova AI Team
 * Text Domain: nova-ai-brainpool
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NOVA_AI_VERSION', '2.0.0');
define('NOVA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOVA_AI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('NOVA_AI_PLUGIN_FILE', __FILE__);

// Load environment variables if .env file exists
if (file_exists(NOVA_AI_PLUGIN_PATH . '.env')) {
    require_once NOVA_AI_PLUGIN_PATH . 'admin/env-loader.php';
}

// Include required files
require_once NOVA_AI_PLUGIN_PATH . 'includes/class-nova-ai-core.php';
require_once NOVA_AI_PLUGIN_PATH . 'includes/class-nova-ai-crawler.php';
require_once NOVA_AI_PLUGIN_PATH . 'includes/class-nova-ai-providers.php';
require_once NOVA_AI_PLUGIN_PATH . 'includes/class-nova-ai-stable-diffusion.php';
require_once NOVA_AI_PLUGIN_PATH . 'includes/class-nova-ai-novanet.php';

// Include admin files
if (is_admin()) {
    require_once NOVA_AI_PLUGIN_PATH . 'admin/class-nova-ai-admin-console.php';
    require_once NOVA_AI_PLUGIN_PATH . 'admin/settings.php';
}

/**
 * Main Nova AI Brainpool class
 */
class Nova_AI_Brainpool {
    
    private static $instance = null;
    private $core;
    private $crawler;
    private $providers;
    private $stable_diffusion;
    private $novanet;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Initialize core components
        $this->core = new Nova_AI_Core();
        $this->crawler = new Nova_AI_Crawler();
        $this->providers = new Nova_AI_Providers();
        $this->stable_diffusion = new Nova_AI_Stable_Diffusion();
        $this->novanet = new Nova_AI_NovaNet();
        
        // WordPress hooks
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_nova_ai_chat', array($this, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_nova_ai_chat', array($this, 'handle_chat_request'));
        add_action('wp_ajax_nova_ai_generate_image', array($this, 'handle_image_generation'));
        add_action('wp_ajax_nopriv_nova_ai_generate_image', array($this, 'handle_image_generation'));
        add_action('wp_ajax_nova_ai_crawl_url', array($this, 'handle_crawl_request'));
        add_action('wp_ajax_nova_ai_get_crawl_status', array($this, 'handle_crawl_status'));
        
        // Shortcodes
        add_shortcode('nova_ai_chat', array($this, 'chat_shortcode'));
        add_shortcode('nova_ai_image_generator', array($this, 'image_generator_shortcode'));
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Auto-crawling cron
        add_action('nova_ai_auto_crawl', array($this->crawler, 'run_auto_crawl'));
        
        // Schedule auto-crawling if not already scheduled
        if (!wp_next_scheduled('nova_ai_auto_crawl')) {
            $interval = get_option('nova_ai_crawl_interval', 'hourly');
            wp_schedule_event(time(), $interval, 'nova_ai_auto_crawl');
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('nova-ai-brainpool', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('nova-ai-chat-frontend', NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.css', array(), NOVA_AI_VERSION);
        wp_enqueue_style('nova-ai-extended', NOVA_AI_PLUGIN_URL . 'assets/nova-ai-extended.css', array(), NOVA_AI_VERSION);
        
        wp_enqueue_script('nova-ai-chat-frontend', NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.js', array('jquery'), NOVA_AI_VERSION, true);
        wp_enqueue_script('nova-ai-extended', NOVA_AI_PLUGIN_URL . 'assets/nova-ai-extended.js', array('jquery'), NOVA_AI_VERSION, true);
        
        // Localize script
        wp_localize_script('nova-ai-chat-frontend', 'nova_ai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_ai_nonce'),
            'strings' => array(
                'thinking' => __('Thinking...', 'nova-ai-brainpool'),
                'error' => __('An error occurred. Please try again.', 'nova-ai-brainpool'),
                'generating_image' => __('Generating image...', 'nova-ai-brainpool'),
                'crawling' => __('Crawling content...', 'nova-ai-brainpool')
            )
        ));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'nova-ai') !== false) {
            wp_enqueue_style('nova-ai-admin', NOVA_AI_PLUGIN_URL . 'assets/nova-ai-extended.css', array(), NOVA_AI_VERSION);
            wp_enqueue_script('nova-ai-admin', NOVA_AI_PLUGIN_URL . 'assets/nova-ai-extended.js', array('jquery'), NOVA_AI_VERSION, true);
            
            wp_localize_script('nova-ai-admin', 'nova_ai_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nova_ai_admin_nonce')
            ));
        }
    }
    
    /**
     * Handle chat requests
     */
    public function handle_chat_request() {
        check_ajax_referer('nova_ai_nonce', 'nonce');
        
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        $use_crawled_data = isset($_POST['use_crawled_data']) ? (bool) $_POST['use_crawled_data'] : true;
        
        if (empty($message)) {
            wp_send_json_error(__('Message cannot be empty', 'nova-ai-brainpool'));
        }
        
        try {
            $response = $this->core->process_chat_message($message, $conversation_id, $use_crawled_data);
            wp_send_json_success($response);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Handle image generation requests
     */
    public function handle_image_generation() {
        check_ajax_referer('nova_ai_nonce', 'nonce');
        
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $style = sanitize_text_field($_POST['style'] ?? 'realistic');
        $width = intval($_POST['width'] ?? 512);
        $height = intval($_POST['height'] ?? 512);
        
        if (empty($prompt)) {
            wp_send_json_error(__('Prompt cannot be empty', 'nova-ai-brainpool'));
        }
        
        try {
            $image_url = $this->stable_diffusion->generate_image($prompt, $style, $width, $height);
            wp_send_json_success(array('image_url' => $image_url));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Handle manual crawl requests
     */
    public function handle_crawl_request() {
        check_ajax_referer('nova_ai_nonce', 'nonce');
        
        $url = esc_url_raw($_POST['url'] ?? '');
        
        if (empty($url)) {
            wp_send_json_error(__('URL cannot be empty', 'nova-ai-brainpool'));
        }
        
        try {
            $result = $this->crawler->crawl_single_url($url);
            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Handle crawl status requests
     */
    public function handle_crawl_status() {
        check_ajax_referer('nova_ai_nonce', 'nonce');
        
        $status = $this->crawler->get_crawl_status();
        wp_send_json_success($status);
    }
    
    /**
     * Chat shortcode
     */
    public function chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'default',
            'height' => '500px',
            'show_image_generator' => 'true',
            'show_crawler' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div class="nova-ai-chat-container" data-theme="<?php echo esc_attr($atts['theme']); ?>" style="height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="nova-ai-chat-header">
                <h3><?php _e('Nova AI Assistant', 'nova-ai-brainpool'); ?></h3>
                <div class="nova-ai-chat-controls">
                    <?php if ($atts['show_crawler'] === 'true'): ?>
                    <button class="nova-ai-crawler-toggle" title="<?php _e('Toggle Crawler', 'nova-ai-brainpool'); ?>">
                        <span class="dashicons dashicons-search"></span>
                    </button>
                    <?php endif; ?>
                    <?php if ($atts['show_image_generator'] === 'true'): ?>
                    <button class="nova-ai-image-toggle" title="<?php _e('Toggle Image Generator', 'nova-ai-brainpool'); ?>">
                        <span class="dashicons dashicons-format-image"></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="nova-ai-chat-messages" id="nova-ai-messages"></div>
            
            <?php if ($atts['show_crawler'] === 'true'): ?>
            <div class="nova-ai-crawler-panel" style="display: none;">
                <div class="nova-ai-crawler-controls">
                    <input type="url" placeholder="<?php _e('Enter URL to crawl...', 'nova-ai-brainpool'); ?>" class="nova-ai-crawl-url">
                    <button class="nova-ai-crawl-btn"><?php _e('Crawl', 'nova-ai-brainpool'); ?></button>
                </div>
                <div class="nova-ai-crawl-status"></div>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_image_generator'] === 'true'): ?>
            <div class="nova-ai-image-panel" style="display: none;">
                <div class="nova-ai-image-controls">
                    <input type="text" placeholder="<?php _e('Describe the image you want...', 'nova-ai-brainpool'); ?>" class="nova-ai-image-prompt">
                    <select class="nova-ai-image-style">
                        <option value="realistic"><?php _e('Realistic', 'nova-ai-brainpool'); ?></option>
                        <option value="artistic"><?php _e('Artistic', 'nova-ai-brainpool'); ?></option>
                        <option value="anime"><?php _e('Anime', 'nova-ai-brainpool'); ?></option>
                        <option value="cartoon"><?php _e('Cartoon', 'nova-ai-brainpool'); ?></option>
                    </select>
                    <button class="nova-ai-generate-btn"><?php _e('Generate', 'nova-ai-brainpool'); ?></button>
                </div>
                <div class="nova-ai-image-result"></div>
            </div>
            <?php endif; ?>
            
            <div class="nova-ai-chat-input">
                <textarea placeholder="<?php _e('Type your message...', 'nova-ai-brainpool'); ?>" class="nova-ai-message-input"></textarea>
                <button class="nova-ai-send-btn"><?php _e('Send', 'nova-ai-brainpool'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Image generator shortcode
     */
    public function image_generator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'default'
        ), $atts);
        
        ob_start();
        ?>
        <div class="nova-ai-image-generator" data-theme="<?php echo esc_attr($atts['theme']); ?>">
            <div class="nova-ai-image-controls">
                <textarea placeholder="<?php _e('Describe the image you want to generate...', 'nova-ai-brainpool'); ?>" class="nova-ai-image-prompt"></textarea>
                <div class="nova-ai-image-settings">
                    <select class="nova-ai-image-style">
                        <option value="realistic"><?php _e('Realistic', 'nova-ai-brainpool'); ?></option>
                        <option value="artistic"><?php _e('Artistic', 'nova-ai-brainpool'); ?></option>
                        <option value="anime"><?php _e('Anime', 'nova-ai-brainpool'); ?></option>
                        <option value="cartoon"><?php _e('Cartoon', 'nova-ai-brainpool'); ?></option>
                    </select>
                    <select class="nova-ai-image-size">
                        <option value="512x512">512x512</option>
                        <option value="768x768">768x768</option>
                        <option value="1024x1024">1024x1024</option>
                        <option value="512x768">512x768</option>
                        <option value="768x512">768x512</option>
                    </select>
                </div>
                <button class="nova-ai-generate-btn"><?php _e('Generate Image', 'nova-ai-brainpool'); ?></button>
            </div>
            <div class="nova-ai-image-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule auto-crawling
        if (!wp_next_scheduled('nova_ai_auto_crawl')) {
            wp_schedule_event(time(), 'hourly', 'nova_ai_auto_crawl');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('nova_ai_auto_crawl');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Chat conversations table
        $table_conversations = $wpdb->prefix . 'nova_ai_conversations';
        $sql_conversations = "CREATE TABLE $table_conversations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id varchar(255) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            title varchar(500) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY conversation_id (conversation_id),
            KEY user_id (user_id),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        // Chat messages table
        $table_messages = $wpdb->prefix . 'nova_ai_messages';
        $sql_messages = "CREATE TABLE $table_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id varchar(255) NOT NULL,
            role enum('user','assistant','system') NOT NULL,
            content longtext NOT NULL,
            metadata json DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY role (role),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Crawled content table
        $table_crawled = $wpdb->prefix . 'nova_ai_crawled_content';
        $sql_crawled = "CREATE TABLE $table_crawled (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url varchar(2048) NOT NULL,
            title varchar(500) DEFAULT NULL,
            content longtext DEFAULT NULL,
            metadata json DEFAULT NULL,
            content_hash varchar(64) DEFAULT NULL,
            crawled_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('pending','crawled','error') DEFAULT 'pending',
            PRIMARY KEY (id),
            UNIQUE KEY url (url(191)),
            KEY content_hash (content_hash),
            KEY status (status),
            KEY crawled_at (crawled_at),
            FULLTEXT KEY content_search (title, content)
        ) $charset_collate;";
        
        // Generated images table
        $table_images = $wpdb->prefix . 'nova_ai_generated_images';
        $sql_images = "CREATE TABLE $table_images (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            prompt text NOT NULL,
            style varchar(50) DEFAULT 'realistic',
            width int(11) DEFAULT 512,
            height int(11) DEFAULT 512,
            image_url varchar(2048) DEFAULT NULL,
            local_path varchar(500) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY created_at (created_at),
            FULLTEXT KEY prompt_search (prompt)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_conversations);
        dbDelta($sql_messages);
        dbDelta($sql_crawled);
        dbDelta($sql_images);
    }
    
    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'nova_ai_api_key' => '',
            'nova_ai_api_url' => 'https://ailinux.me/api/v1',
            'nova_ai_image_api_url' => 'https://ailinux.me:7860',
            'nova_ai_model' => 'gpt-4',
            'nova_ai_max_tokens' => 2048,
            'nova_ai_temperature' => 0.7,
            'nova_ai_system_prompt' => 'You are Nova AI, a helpful and knowledgeable assistant.',
            'nova_ai_crawl_enabled' => true,
            'nova_ai_crawl_interval' => 'hourly',
            'nova_ai_crawl_sites' => json_encode(array(
                'https://ailinux.me',
                'https://ailinux.me/blog',
                'https://ailinux.me/docs'
            )),
            'nova_ai_auto_crawl_enabled' => true,
            'nova_ai_max_crawl_depth' => 3,
            'nova_ai_crawl_delay' => 1000,
            'nova_ai_image_generation_enabled' => true,
            'nova_ai_max_image_size' => 1024,
            'nova_ai_save_conversations' => true,
            'nova_ai_conversation_retention_days' => 30
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
}

// Initialize the plugin
function nova_ai_brainpool_init() {
    return Nova_AI_Brainpool::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'nova_ai_brainpool_init');

// Utility functions
function nova_ai_get_conversation_id() {
    return wp_create_nonce('nova_ai_conversation_' . get_current_user_id() . '_' . session_id());
}

function nova_ai_get_session_id() {
    if (!session_id()) {
        session_start();
    }
    return session_id();
}

function nova_ai_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Nova AI {$level}] " . $message);
    }
}
