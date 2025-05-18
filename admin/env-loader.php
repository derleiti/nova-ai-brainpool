<?php
/**
 * .env File Loader
 * 
 * @package Nova_AI_Brainpool
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load environment variables from .env file
 */
function nova_ai_load_env_file() {
    $env_file = NOVA_AI_PLUGIN_DIR . '.env';

    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments and invalid lines
            if (strpos(trim($line), '#') === 0 || !str_contains($line, '=')) {
                continue;
            }
            
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Set environment variable
            putenv("{$name}={$value}");

            // Define constant if not already defined
            if (!defined($name)) {
                define($name, $value);
            }
        }
        
        if (function_exists('nova_ai_log')) {
            nova_ai_log('.env file loaded successfully', 'debug');
        }
    } else {
        if (function_exists('nova_ai_log')) {
            nova_ai_log('.env file not found at: ' . $env_file, 'debug');
        }
    }
}

// Load environment variables
nova_ai_load_env_file();
