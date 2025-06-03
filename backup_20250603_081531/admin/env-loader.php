<?php
/**
 * Nova AI Environment Variable Loader
 * 
 * Loads environment variables from .env file
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nova_AI_Env_Loader {
    
    private static $loaded = false;
    
    /**
     * Load environment variables from .env file
     */
    public static function load($file_path = null) {
        if (self::$loaded) {
            return;
        }
        
        if (!$file_path) {
            $file_path = NOVA_AI_PLUGIN_PATH . '.env';
        }
        
        if (!file_exists($file_path)) {
            return;
        }
        
        $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes from value
                $value = self::removeQuotes($value);
                
                // Set environment variable if not already set
                if (!getenv($key) && !isset($_ENV[$key])) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
        
        // Auto-populate WordPress options from env variables
        self::populateOptions();
    }
    
    /**
     * Remove quotes from value
     */
    private static function removeQuotes($value) {
        $value = trim($value);
        
        // Remove surrounding quotes
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        
        return $value;
    }
    
    /**
     * Populate WordPress options from environment variables
     */
    private static function populateOptions() {
        $env_mappings = array(
            'NOVA_AI_API_KEY' => 'nova_ai_api_key',
            'NOVA_AI_API_URL' => 'nova_ai_api_url',
            'NOVA_AI_MODEL' => 'nova_ai_model',
            'NOVA_AI_MAX_TOKENS' => 'nova_ai_max_tokens',
            'NOVA_AI_TEMPERATURE' => 'nova_ai_temperature',
            'NOVA_AI_SYSTEM_PROMPT' => 'nova_ai_system_prompt',
            'NOVA_AI_ACTIVE_PROVIDER' => 'nova_ai_active_provider',
            
            // Provider API Keys
            'NOVA_AI_OPENAI_API_KEY' => 'nova_ai_openai_api_key',
            'NOVA_AI_ANTHROPIC_API_KEY' => 'nova_ai_anthropic_api_key',
            'NOVA_AI_AILINUX_API_KEY' => 'nova_ai_ailinux_api_key',
            
            // Crawler settings
            'NOVA_AI_CRAWL_ENABLED' => 'nova_ai_crawl_enabled',
            'NOVA_AI_AUTO_CRAWL_ENABLED' => 'nova_ai_auto_crawl_enabled',
            'NOVA_AI_CRAWL_SITES' => 'nova_ai_crawl_sites',
            'NOVA_AI_CRAWL_INTERVAL' => 'nova_ai_crawl_interval',
            'NOVA_AI_MAX_CRAWL_DEPTH' => 'nova_ai_max_crawl_depth',
            'NOVA_AI_CRAWL_DELAY' => 'nova_ai_crawl_delay',
            
            // Image generation
            'NOVA_AI_IMAGE_GENERATION_ENABLED' => 'nova_ai_image_generation_enabled',
            'NOVA_AI_IMAGE_API_URL' => 'nova_ai_image_api_url',
            'NOVA_AI_MAX_IMAGE_SIZE' => 'nova_ai_max_image_size',
            
            // General settings
            'NOVA_AI_SAVE_CONVERSATIONS' => 'nova_ai_save_conversations',
            'NOVA_AI_CONVERSATION_RETENTION_DAYS' => 'nova_ai_conversation_retention_days',
            
            // NovaNet
            'NOVA_AI_NOVANET_ENABLED' => 'nova_ai_novanet_enabled',
            'NOVA_AI_NOVANET_URL' => 'nova_ai_novanet_url',
            'NOVA_AI_NOVANET_API_KEY' => 'nova_ai_novanet_api_key',
            'NOVA_AI_NOVANET_AUTO_SHARE' => 'nova_ai_novanet_auto_share'
        );
        
        foreach ($env_mappings as $env_key => $option_key) {
            $env_value = getenv($env_key);
            
            if ($env_value !== false) {
                // Convert string values to appropriate types
                $option_value = self::convertValue($env_value);
                
                // Only update if option doesn't exist or if it's empty
                $current_value = get_option($option_key);
                if ($current_value === false || empty($current_value)) {
                    update_option($option_key, $option_value);
                }
            }
        }
    }
    
    /**
     * Convert string values to appropriate types
     */
    private static function convertValue($value) {
        // Boolean conversion
        if (in_array(strtolower($value), array('true', '1', 'yes', 'on'))) {
            return true;
        }
        
        if (in_array(strtolower($value), array('false', '0', 'no', 'off', ''))) {
            return false;
        }
        
        // Numeric conversion
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? floatval($value) : intval($value);
        }
        
        // JSON array conversion
        if (substr($value, 0, 1) === '[' && substr($value, -1) === ']') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return $value;
    }
    
    /**
     * Get environment variable with fallback
     */
    public static function get($key, $default = null) {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
    
    /**
     * Check if environment variable exists
     */
    public static function has($key) {
        return getenv($key) !== false;
    }
    
    /**
     * Generate example .env file
     */
    public static function generateExample() {
        $example_content = '# Nova AI Brainpool Environment Configuration
# Copy this file to .env and configure your settings

# ==============================================
# AI Provider Settings
# ==============================================

# Active AI Provider (ailinux, openai, anthropic, local)
NOVA_AI_ACTIVE_PROVIDER=ailinux

# AI Linux API Configuration
NOVA_AI_API_URL=https://ailinux.me/api/v1
NOVA_AI_API_KEY=your_ailinux_api_key_here
NOVA_AI_MODEL=gpt-4

# OpenAI API Configuration
NOVA_AI_OPENAI_API_KEY=your_openai_api_key_here

# Anthropic API Configuration
NOVA_AI_ANTHROPIC_API_KEY=your_anthropic_api_key_here

# AI Model Parameters
NOVA_AI_MAX_TOKENS=2048
NOVA_AI_TEMPERATURE=0.7
NOVA_AI_SYSTEM_PROMPT="You are Nova AI, a helpful and knowledgeable assistant."

# ==============================================
# Web Crawler Settings
# ==============================================

# Enable/Disable Crawler
NOVA_AI_CRAWL_ENABLED=true
NOVA_AI_AUTO_CRAWL_ENABLED=true

# Sites to crawl (JSON array format)
NOVA_AI_CRAWL_SITES=["https://ailinux.me", "https://ailinux.me/blog", "https://ailinux.me/docs"]

# Crawler Configuration
NOVA_AI_CRAWL_INTERVAL=hourly
NOVA_AI_MAX_CRAWL_DEPTH=3
NOVA_AI_CRAWL_DELAY=1000

# ==============================================
# Image Generation Settings
# ==============================================

# Enable/Disable Image Generation
NOVA_AI_IMAGE_GENERATION_ENABLED=true

# Stable Diffusion API URL
NOVA_AI_IMAGE_API_URL=https://ailinux.me:7860

# Maximum Image Size
NOVA_AI_MAX_IMAGE_SIZE=1024

# ==============================================
# General Settings
# ==============================================

# Conversation Management
NOVA_AI_SAVE_CONVERSATIONS=true
NOVA_AI_CONVERSATION_RETENTION_DAYS=30

# ==============================================
# NovaNet Network Settings
# ==============================================

# Enable/Disable NovaNet
NOVA_AI_NOVANET_ENABLED=false

# NovaNet Configuration
NOVA_AI_NOVANET_URL=https://ailinux.me/novanet
NOVA_AI_NOVANET_API_KEY=your_novanet_api_key_here
NOVA_AI_NOVANET_AUTO_SHARE=false

# ==============================================
# Development & Debug Settings
# ==============================================

# WordPress Debug Mode
WP_DEBUG=false
WP_DEBUG_LOG=false
WP_DEBUG_DISPLAY=false

# Nova AI Debug Mode
NOVA_AI_DEBUG=false
NOVA_AI_LOG_LEVEL=info

# Cache Settings
NOVA_AI_CACHE_ENABLED=true
NOVA_AI_CACHE_TTL=3600

# Rate Limiting
NOVA_AI_RATE_LIMIT_ENABLED=true
NOVA_AI_RATE_LIMIT_REQUESTS=100
NOVA_AI_RATE_LIMIT_WINDOW=3600

# ==============================================
# Security Settings
# ==============================================

# API Security
NOVA_AI_API_TIMEOUT=60
NOVA_AI_MAX_REQUEST_SIZE=10485760

# User Permissions
NOVA_AI_REQUIRE_LOGIN=false
NOVA_AI_ADMIN_ONLY=false
NOVA_AI_ALLOWED_ROLES=["administrator", "editor"]

# ==============================================
# Performance Settings
# ==============================================

# Memory and Resource Limits
NOVA_AI_MEMORY_LIMIT=256M
NOVA_AI_EXECUTION_TIME=300
NOVA_AI_MAX_CONCURRENT_REQUESTS=5

# Database Optimization
NOVA_AI_DB_CLEANUP_ENABLED=true
NOVA_AI_DB_CLEANUP_INTERVAL=daily
';

        return $example_content;
    }
    
    /**
     * Create example .env file
     */
    public static function createExampleFile($file_path = null) {
        if (!$file_path) {
            $file_path = NOVA_AI_PLUGIN_PATH . '.env.example';
        }
        
        $content = self::generateExample();
        
        return file_put_contents($file_path, $content) !== false;
    }
    
    /**
     * Validate environment configuration
     */
    public static function validate() {
        $errors = array();
        $warnings = array();
        
        // Check required settings
        $required_settings = array(
            'NOVA_AI_API_URL' => 'API URL is required',
            'NOVA_AI_ACTIVE_PROVIDER' => 'Active provider must be specified'
        );
        
        foreach ($required_settings as $key => $message) {
            if (!self::has($key) || empty(self::get($key))) {
                $errors[] = $message;
            }
        }
        
        // Check provider-specific requirements
        $provider = self::get('NOVA_AI_ACTIVE_PROVIDER');
        if ($provider && in_array($provider, array('openai', 'anthropic', 'ailinux'))) {
            $api_key_var = 'NOVA_AI_' . strtoupper($provider) . '_API_KEY';
            if (!self::has($api_key_var) || empty(self::get($api_key_var))) {
                $errors[] = "API key required for {$provider} provider";
            }
        }
        
        // Check URL formats
        $url_settings = array(
            'NOVA_AI_API_URL',
            'NOVA_AI_IMAGE_API_URL',
            'NOVA_AI_NOVANET_URL'
        );
        
        foreach ($url_settings as $key) {
            $url = self::get($key);
            if ($url && !filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = "{$key} must be a valid URL";
            }
        }
        
        // Check numeric ranges
        $numeric_settings = array(
            'NOVA_AI_MAX_TOKENS' => array('min' => 1, 'max' => 8192),
            'NOVA_AI_TEMPERATURE' => array('min' => 0, 'max' => 2),
            'NOVA_AI_MAX_CRAWL_DEPTH' => array('min' => 1, 'max' => 10),
            'NOVA_AI_CRAWL_DELAY' => array('min' => 0, 'max' => 10000),
            'NOVA_AI_MAX_IMAGE_SIZE' => array('min' => 128, 'max' => 2048),
            'NOVA_AI_CONVERSATION_RETENTION_DAYS' => array('min' => 0, 'max' => 365)
        );
        
        foreach ($numeric_settings as $key => $range) {
            $value = self::get($key);
            if ($value !== null && is_numeric($value)) {
                $value = floatval($value);
                if ($value < $range['min'] || $value > $range['max']) {
                    $errors[] = "{$key} must be between {$range['min']} and {$range['max']}";
                }
            }
        }
        
        // Check crawl sites format
        $crawl_sites = self::get('NOVA_AI_CRAWL_SITES');
        if ($crawl_sites) {
            $sites = json_decode($crawl_sites, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'NOVA_AI_CRAWL_SITES must be valid JSON array';
            } elseif (!is_array($sites)) {
                $errors[] = 'NOVA_AI_CRAWL_SITES must be an array';
            } else {
                foreach ($sites as $site) {
                    if (!filter_var($site, FILTER_VALIDATE_URL)) {
                        $warnings[] = "Invalid URL in crawl sites: {$site}";
                    }
                }
            }
        }
        
        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        );
    }
    
    /**
     * Get configuration summary
     */
    public static function getSummary() {
        return array(
            'loaded' => self::$loaded,
            'provider' => self::get('NOVA_AI_ACTIVE_PROVIDER', 'not set'),
            'api_url' => self::get('NOVA_AI_API_URL', 'not set'),
            'crawl_enabled' => self::get('NOVA_AI_CRAWL_ENABLED', false),
            'image_enabled' => self::get('NOVA_AI_IMAGE_GENERATION_ENABLED', false),
            'novanet_enabled' => self::get('NOVA_AI_NOVANET_ENABLED', false),
            'validation' => self::validate()
        );
    }
}

// Auto-load if .env file exists
Nova_AI_Env_Loader::load();
