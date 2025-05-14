<?php
/**
 * Nova AI Full-Site Chat Interface
 * 
 * This file handles the frontend display of the Nova AI chat interface
 * that appears on all pages of the website when enabled.
 */

if (!defined('ABSPATH')) exit;

// Add full-site chat to the footer if enabled
if (!function_exists('nova_ai_fullsite_chat')) {
    function nova_ai_fullsite_chat() {
        // Check if full-site chat is enabled
        if (!get_option('nova_ai_enable_fullsite_chat', false)) {
            return;
        }
        
        // Get chat settings
        $theme_style = get_option('nova_ai_theme_style', 'terminal');
        $chat_position = get_option('nova_ai_chat_position', 'bottom-right');
        $welcome_message = get_option('nova_ai_chat_welcome_message', 'Hi! I\'m Nova AI. How can I help you?');
        $button_text = get_option('nova_ai_chat_button_text', 'Chat with Nova AI');
        $placeholder = get_option('nova_ai_chat_placeholder', 'Type your message...');
        
        // Determine theme class
        $theme_class = '';
        switch ($theme_style) {
            case 'terminal':
                $theme_class = 'nova-ai-theme-terminal';
                break;
            case 'dark':
                $theme_class = 'nova-ai-theme-dark';
                break;
            case 'light':
                $theme_class = 'nova-ai-theme-light';
                break;
            default:
                $theme_class = 'nova-ai-theme-terminal';
        }
        
        // Position class
        $position_class = 'nova-ai-position-' . $chat_position;
        
        // Output chat HTML
        ?>
        <div id="nova-ai-fullsite-chat" class="<?php echo esc_attr($theme_class . ' ' . $position_class); ?>">
            <div class="nova-ai-chat-button">
                <span class="nova-ai-button-text"><?php echo esc_html($button_text); ?></span>
                <span class="nova-ai-icon"></span>
            </div>
            
            <div class="nova-ai-chat-container">
                <div class="nova-ai-chat-header">
                    <div class="nova-ai-header-title">Nova AI</div>
                    <div class="nova-ai-header-controls">
                        <button class="nova-ai-minimize" title="Minimize">–</button>
                        <button class="nova-ai-close" title="Close">×</button>
                    </div>
                </div>
                
                <div class="nova-ai-chat-messages">
                    <div class="nova-ai-message nova-ai-message-ai">
                        <div class="nova-ai-message-avatar"></div>
                        <div class="nova-ai-message-content"><?php echo esc_html($welcome_message); ?></div>
                    </div>
                </div>
                
                <div class="nova-ai-chat-input-container">
                    <textarea class="nova-ai-chat-input" placeholder="<?php echo esc_attr($placeholder); ?>" rows="1"></textarea>
                    <button class="nova-ai-chat-send" title="Send message" disabled>
                        <span class="nova-ai-send-icon"></span>
                    </button>
                </div>
            </div>
        </div>
        <?php
        
        // Enqueue scripts and styles
        wp_enqueue_style('nova-ai-fullsite-chat', NOVA_AI_PLUGIN_URL . 'assets/css/fullsite-chat.css', array(), NOVA_AI_VERSION);
        wp_enqueue_script('nova-ai-fullsite-chat', NOVA_AI_PLUGIN_URL . 'assets/js/fullsite-chat.js', array('jquery'), NOVA_AI_VERSION, true);
        
        // Add theme-specific styles
        if (function_exists('nova_ai_get_theme_css')) {
            $theme_css = nova_ai_get_theme_css($theme_style);
            wp_add_inline_style('nova-ai-fullsite-chat', $theme_css);
        }
        
        // Add custom CSS if available
        $custom_css = get_option('nova_ai_custom_css', '');
        if (!empty($custom_css)) {
            wp_add_inline_style('nova-ai-fullsite-chat', $custom_css);
        }
        
        // Localize script with settings
        wp_localize_script('nova-ai-fullsite-chat', 'nova_ai_chat_settings', array(
            'api_url' => rest_url('nova-ai/v1/chat'),
            'nonce' => wp_create_nonce('wp_rest'),
            'placeholder' => $placeholder,
            'welcome_message' => $welcome_message,
            'theme' => $theme_style,
            'position' => $chat_position
        ));
    }
}
