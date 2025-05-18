<?php
/**
 * Nova AI Full-Site Chat Interface - Optimized
 * 
 * Enhanced version with improved performance, compatibility, and accessibility
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Check if full-site chat should be added
 */
function nova_ai_should_add_fullsite_chat() {
    // Skip if not enabled
    if (!get_option('nova_ai_enable_fullsite_chat', false)) {
        return false;
    }
    
    // Skip on admin pages
    if (is_admin()) {
        return false;
    }
    
    // Skip on login/register pages
    if (in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'])) {
        return false;
    }
    
    // Check for page exclusions
    $excluded_pages = apply_filters('nova_ai_excluded_pages', []);
    if (!empty($excluded_pages)) {
        global $post;
        if (isset($post) && in_array($post->ID, $excluded_pages)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Add full-site chat to the footer if enabled
 */
function nova_ai_add_fullsite_chat() {
    // Check if chat should be added
    if (!nova_ai_should_add_fullsite_chat()) {
        return;
    }
    
    // Get chat settings
    $theme_style = get_option('nova_ai_theme_style', 'terminal');
    $chat_position = get_option('nova_ai_chat_position', 'bottom-right');
    $welcome_message = get_option('nova_ai_chat_welcome_message', 'Hi! I\'m Nova AI. How can I help you?');
    $button_text = get_option('nova_ai_chat_button_text', 'Chat with Nova AI');
    $placeholder = get_option('nova_ai_chat_placeholder', 'Type your message...');
    
    // Allow theme override with auto detection
    if ($theme_style === 'auto') {
        $theme_style = 'dark'; // Default to dark, will be changed by JS
    }
    
    // Determine theme class
    $theme_class = 'nova-ai-theme-' . $theme_style;
    
    // Position class
    $position_class = 'nova-ai-position-' . $chat_position;
    
    // Generate unique ID for accessibility
    $unique_id = 'nova-ai-' . wp_rand(10000, 99999);
    
    // Output chat HTML with accessibility and performance improvements
    ?>
    <div id="nova-ai-fullsite-chat" class="<?php echo esc_attr($theme_class . ' ' . $position_class); ?>" role="region" aria-label="Nova AI Chat Interface">
        <button class="nova-ai-chat-button" aria-label="Open Chat" aria-expanded="false" aria-controls="<?php echo esc_attr($unique_id . '-container'); ?>">
            <span class="nova-ai-button-text"><?php echo esc_html($button_text); ?></span>
            <span class="nova-ai-icon" aria-hidden="true"></span>
        </button>
        
        <div id="<?php echo esc_attr($unique_id . '-container'); ?>" class="nova-ai-chat-container" aria-hidden="true">
            <div class="nova-ai-chat-header" role="banner">
                <div class="nova-ai-header-title" id="<?php echo esc_attr($unique_id . '-title'); ?>">Nova AI</div>
                <div class="nova-ai-header-controls">
                    <button class="nova-ai-minimize" aria-label="Minimize chat" title="Minimize">–</button>
                    <button class="nova-ai-close" aria-label="Close chat" title="Close">×</button>
                </div>
            </div>
            
            <div id="<?php echo esc_attr($unique_id . '-messages'); ?>" class="nova-ai-chat-messages" role="log" aria-live="polite" aria-relevant="additions">
                <div class="nova-ai-message nova-ai-message-ai">
                    <div class="nova-ai-message-avatar" aria-hidden="true"></div>
                    <div class="nova-ai-message-content"><?php echo esc_html($welcome_message); ?></div>
                </div>
            </div>
            
            <div class="nova-ai-chat-input-container">
                <textarea 
                    class="nova-ai-chat-input" 
                    id="<?php echo esc_attr($unique_id . '-input'); ?>"
                    placeholder="<?php echo esc_attr($placeholder); ?>" 
                    rows="1"
                    aria-label="Message to Nova AI"
                    aria-multiline="true"
                    aria-controls="<?php echo esc_attr($unique_id . '-messages'); ?>"
                ></textarea>
                <button 
                    class="nova-ai-chat-send" 
                    aria-label="Send message" 
                    title="Send message" 
                    disabled
                >
                    <span class="nova-ai-send-icon" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
    <?php
    
    // Enqueue scripts and styles with optimization
    nova_ai_enqueue_fullsite_chat_assets($theme_style);
}

/**
 * Enqueue assets for fullsite chat with performance optimization
 */
function nova_ai_enqueue_fullsite_chat_assets($theme_style) {
    // Enable asset minification
    $min = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
    
    // Use WordPress's built-in jQuery
    wp_enqueue_script('jquery');
    
    // Base styles with versioning
    wp_enqueue_style(
        'nova-ai-fullsite-chat', 
        NOVA_AI_PLUGIN_URL . 'assets/css/fullsite-chat' . $min . '.css', 
        [], 
        NOVA_AI_VERSION
    );
    
    // Add theme-specific styles
    $inline_css = nova_ai_get_theme_css($theme_style);
    wp_add_inline_style('nova-ai-fullsite-chat', $inline_css);
    
    // Add custom CSS if available
    $custom_css = get_option('nova_ai_custom_css', '');
    if (!empty($custom_css)) {
        wp_add_inline_style('nova-ai-fullsite-chat', $custom_css);
    }
    
    // JavaScript with deferred loading
    wp_enqueue_script(
        'nova-ai-fullsite-chat', 
        NOVA_AI_PLUGIN_URL . 'assets/js/fullsite-chat' . $min . '.js', 
        ['jquery'], 
        NOVA_AI_VERSION, 
        true
    );
    
    // Add offline handler and accessibility scripts
    wp_enqueue_script(
        'nova-ai-offline-handler', 
        NOVA_AI_PLUGIN_URL . 'assets/js/offline-handler' . $min . '.js', 
        ['jquery', 'nova-ai-fullsite-chat'], 
        NOVA_AI_VERSION, 
        true
    );
    
    wp_enqueue_script(
        'nova-ai-accessibility', 
        NOVA_AI_PLUGIN_URL . 'assets/js/accessibility' . $min . '.js', 
        ['jquery', 'nova-ai-fullsite-chat'], 
        NOVA_AI_VERSION, 
        true
    );
    
    // Localize script with settings
    wp_localize_script('nova-ai-fullsite-chat', 'nova_ai_chat_settings', [
        'api_url' => rest_url('nova-ai/v1/chat'),
        'nonce' => wp_create_nonce('wp_rest'),
        'placeholder' => get_option('nova_ai_chat_placeholder', 'Type your message...'),
        'welcome_message' => get_option('nova_ai_chat_welcome_message', 'Hi! I\'m Nova AI. How can I help you?'),
        'theme' => $theme_style,
        'position' => get_option('nova_ai_chat_position', 'bottom-right'),
        'conversation_id' => uniqid('chat_'),
        'auto_open' => apply_filters('nova_ai_auto_open_chat', false),
        'debug_mode' => get_option('nova_ai_debug_mode', false),
        'emoji_support' => true,
        'typing_delay' => apply_filters('nova_ai_typing_delay', true),
        'version' => NOVA_AI_VERSION
    ]);
}

// Add to wp_footer with high priority to ensure it's at the bottom of the page
add_action('wp_footer', 'nova_ai_add_fullsite_chat', 999);
