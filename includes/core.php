<?php
/**
 * Core Functions for Nova AI Brainpool Plugin
 * 
 * @package Nova_AI_Brainpool
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize plugin components
 */
add_action('init', function() {
    // Register any custom post types, taxonomies, or other WP components here
    
    // Load text domain for translations
    if (function_exists('nova_ai_load_textdomain') && !did_action('nova_ai_load_textdomain')) {
        nova_ai_load_textdomain();
    }
    
    // Initialize stats if needed
    if (!get_option('nova_ai_total_chats', false)) {
        add_option('nova_ai_total_chats', 0);
    }
    
    if (!get_option('nova_ai_today_chats', false)) {
        add_option('nova_ai_today_chats', 0);
    }
    
    $today = date('Y-m-d');
    if (!get_option('nova_ai_today_date', false)) {
        add_option('nova_ai_today_date', $today);
    } else if (get_option('nova_ai_today_date') !== $today) {
        // Reset daily stats if it's a new day
        update_option('nova_ai_today_date', $today);
        update_option('nova_ai_today_chats', 0);
    }
});

/**
 * Alternative shortcode for Nova AI Chat
 * Note: Main chat shortcode is defined in admin/settings.php
 */
if (!function_exists('nova_ai_simple_chat_shortcode')) {
    function nova_ai_simple_chat_shortcode($atts = [], $content = null) {
        // Extract attributes
        $atts = shortcode_atts(array(
            'placeholder' => __('Ask Nova AI...', 'nova-ai-brainpool'),
            'button_text' => __('Send', 'nova-ai-brainpool'),
            'width' => '100%',
        ), $atts, 'nova_ai_simple_chat');
        
        // Sanitize attributes
        $placeholder = sanitize_text_field($atts['placeholder']);
        $button_text = sanitize_text_field($atts['button_text']);
        $width = sanitize_text_field($atts['width']);
        
        ob_start();
        ?>
        <div id="nova-ai-chat-box" style="max-width: <?php echo esc_attr($width); ?>; margin: 0 auto; padding: 1rem; border: 1px solid #ccc;">
            <div id="nova-ai-chat-log" style="min-height: 200px; margin-bottom: 1rem;"></div>
            <input type="text" id="nova-ai-chat-input" placeholder="<?php echo esc_attr($placeholder); ?>" style="width: 100%; padding: 0.5rem;">
            <button id="nova-ai-chat-send" style="margin-top: 0.5rem;"><?php echo esc_html($button_text); ?></button>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('nova-ai-chat-input');
            const log = document.getElementById('nova-ai-chat-log');
            const button = document.getElementById('nova-ai-chat-send');

            button.addEventListener('click', async () => {
                const prompt = input.value.trim();
                if (!prompt) return;
                log.innerHTML += `<div><b>Du:</b> ${prompt}</div>`;
                input.value = '';

                const response = await fetch('/wp-json/nova-ai/v1/chat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: prompt, conversation: [] })
                });

                const data = await response.json();
                if (data.reply) {
                    log.innerHTML += `<div><b>Nova:</b> ${data.reply}</div>`;
                } else {
                    log.innerHTML += `<div style="color:red;">Fehler bei der Antwort.</div>`;
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
add_shortcode('nova_ai_simple_chat', 'nova_ai_simple_chat_shortcode');

/**
 * Get API response directly (for programmatic use)
 *
 * @param string $prompt The user message
 * @return string The AI response
 */
if (!function_exists('nova_ai_get_response')) {
    function nova_ai_get_response($prompt) {
        try {
            // Initialize API handler
            $api = Nova_AI_API::get_instance();
            
            // Get API settings
            $api_type = get_option('nova_ai_api_type', 'ollama');
            $model = get_option('nova_ai_model', 'zephyr');
            $api_url = get_option('nova_ai_api_url', 'http://localhost:11434/api/generate');
            $api_key = get_option('nova_ai_api_key', '');
            $system_prompt = get_option('nova_ai_system_prompt', __('I am Nova, a helpful AI assistant.', 'nova-ai-brainpool'));
            $temperature = get_option('nova_ai_temperature', 0.7);
            $max_tokens = get_option('nova_ai_max_tokens', 800);
            
            // Get response based on API type
            if ($api_type === 'ollama') {
                $response = $api->ollama_request($prompt, $model, $api_url, $system_prompt, $temperature, $max_tokens);
            } else {
                $response = $api->openai_request($prompt, $api_key, $system_prompt, $temperature, $max_tokens);
            }
            
            // Update statistics
            if (function_exists('nova_ai_update_chat_stats')) {
                nova_ai_update_chat_stats();
            }
            
            return $response;
        } catch (Exception $e) {
            if (function_exists('nova_ai_log')) {
                nova_ai_log('Error in nova_ai_get_response: ' . $e->getMessage(), 'error');
            }
            return 'Error: ' . $e->getMessage();
        }
    }
}

/**
 * Register plugin hooks during activation/deactivation
 * These are also in main plugin file but duplicated here for safety
 */
if (!has_action('register_activation_hook', 'nova_ai_activate')) {
    register_activation_hook(NOVA_AI_PLUGIN_DIR . 'nova-ai-brainpool.php', 'nova_ai_activate');
}

if (!has_action('register_deactivation_hook', 'nova_ai_deactivate')) {
    register_deactivation_hook(NOVA_AI_PLUGIN_DIR . 'nova-ai-brainpool.php', 'nova_ai_deactivate');
}
