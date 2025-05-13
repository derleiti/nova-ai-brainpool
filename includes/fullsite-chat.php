<?php
/**
 * Nova AI Full-Site Chat Interface
 * 
 * This file handles the frontend display of the Nova AI chat interface
 * that appears on all pages of the website when enabled.
 */

if (!defined('ABSPATH')) exit;

// Add full-site chat to the footer if enabled
add_action('wp_footer', 'nova_ai_fullsite_chat');
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
                <button class="nova-ai-chat-send" title="Send message">
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
    $theme_css = nova_ai_get_theme_css($theme_style);
    wp_add_inline_style('nova-ai-fullsite-chat', $theme_css);
    
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

// Get theme-specific CSS
function nova_ai_get_theme_css($theme) {
    $css = '';
    
    switch ($theme) {
        case 'terminal':
            $css = '
                :root {
                    --nova-ai-bg-color: #111;
                    --nova-ai-text-color: #0f0;
                    --nova-ai-accent-color: #0f0;
                    --nova-ai-header-bg: #000;
                    --nova-ai-input-bg: #000;
                    --nova-ai-input-text: #0f0;
                    --nova-ai-message-ai-bg: #1a1a1a;
                    --nova-ai-message-user-bg: #222;
                    --nova-ai-message-ai-text: #0f0;
                    --nova-ai-message-user-text: #0f0;
                    --nova-ai-button-bg: #0f0;
                    --nova-ai-button-text: #000;
                    --nova-ai-send-button-bg: #0f0;
                    --nova-ai-send-button-text: #000;
                    --nova-ai-scrollbar-thumb: #0f0;
                    --nova-ai-scrollbar-track: #000;
                    --nova-ai-font-family: "Courier New", monospace;
                    --nova-ai-border-color: #0f0;
                    --nova-ai-shadow-color: rgba(0, 255, 0, 0.2);
                }
                #nova-ai-fullsite-chat.nova-ai-theme-terminal .nova-ai-message-avatar {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2300ff00\'%3E%3Cpath d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-terminal .nova-ai-send-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23000000\'%3E%3Cpath d=\'M2.01 21L23 12 2.01 3 2 10l15 2-15 2z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-terminal .nova-ai-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23000000\'%3E%3Cpath d=\'M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z\'/%3E%3C/svg%3E");
                }
            ';
            break;
        
        case 'dark':
            $css = '
                :root {
                    --nova-ai-bg-color: #121212;
                    --nova-ai-text-color: #eee;
                    --nova-ai-accent-color: #00ffc8;
                    --nova-ai-header-bg: #1a1a1a;
                    --nova-ai-input-bg: #222;
                    --nova-ai-input-text: #fff;
                    --nova-ai-message-ai-bg: #292929;
                    --nova-ai-message-user-bg: #1f1f1f;
                    --nova-ai-message-ai-text: #00ffc8;
                    --nova-ai-message-user-text: #fff;
                    --nova-ai-button-bg: #00ffc8;
                    --nova-ai-button-text: #000;
                    --nova-ai-send-button-bg: #00ffc8;
                    --nova-ai-send-button-text: #000;
                    --nova-ai-scrollbar-thumb: #444;
                    --nova-ai-scrollbar-track: #222;
                    --nova-ai-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    --nova-ai-border-color: #333;
                    --nova-ai-shadow-color: rgba(0, 255, 200, 0.2);
                }
                #nova-ai-fullsite-chat.nova-ai-theme-dark .nova-ai-message-avatar {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2300ffc8\'%3E%3Cpath d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-dark .nova-ai-send-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23000000\'%3E%3Cpath d=\'M2.01 21L23 12 2.01 3 2 10l15 2-15 2z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-dark .nova-ai-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23000000\'%3E%3Cpath d=\'M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z\'/%3E%3C/svg%3E");
                }
            ';
            break;
        
        case 'light':
            $css = '
                :root {
                    --nova-ai-bg-color: #f9f9f9;
                    --nova-ai-text-color: #333;
                    --nova-ai-accent-color: #008066;
                    --nova-ai-header-bg: #fff;
                    --nova-ai-input-bg: #fff;
                    --nova-ai-input-text: #333;
                    --nova-ai-message-ai-bg: #f0f0f0;
                    --nova-ai-message-user-bg: #e6e6e6;
                    --nova-ai-message-ai-text: #008066;
                    --nova-ai-message-user-text: #333;
                    --nova-ai-button-bg: #008066;
                    --nova-ai-button-text: #fff;
                    --nova-ai-send-button-bg: #008066;
                    --nova-ai-send-button-text: #fff;
                    --nova-ai-scrollbar-thumb: #ccc;
                    --nova-ai-scrollbar-track: #f0f0f0;
                    --nova-ai-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    --nova-ai-border-color: #ddd;
                    --nova-ai-shadow-color: rgba(0, 0, 0, 0.1);
                }
                #nova-ai-fullsite-chat.nova-ai-theme-light .nova-ai-message-avatar {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23008066\'%3E%3Cpath d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-light .nova-ai-send-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23ffffff\'%3E%3Cpath d=\'M2.01 21L23 12 2.01 3 2 10l15 2-15 2z\'/%3E%3C/svg%3E");
                }
                #nova-ai-fullsite-chat.nova-ai-theme-light .nova-ai-icon {
                    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23ffffff\'%3E%3Cpath d=\'M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z\'/%3E%3C/svg%3E");
                }
            ';
            break;
        
        default:
            // Default to terminal theme
            $css = '
                :root {
                    --nova-ai-bg-color: #111;
                    --nova-ai-text-color: #0f0;
                    --nova-ai-accent-color: #0f0;
                    --nova-ai-header-bg: #000;
                    --nova-ai-input-bg: #000;
                    --nova-ai-input-text: #0f0;
                    --nova-ai-message-ai-bg: #1a1a1a;
                    --nova-ai-message-user-bg: #222;
                    --nova-ai-message-ai-text: #0f0;
                    --nova-ai-message-user-text: #0f0;
                    --nova-ai-button-bg: #0f0;
                    --nova-ai-button-text: #000;
                    --nova-ai-send-button-bg: #0f0;
                    --nova-ai-send-button-text: #000;
                    --nova-ai-scrollbar-thumb: #0f0;
                    --nova-ai-scrollbar-track: #000;
                    --nova-ai-font-family: "Courier New", monospace;
                    --nova-ai-border-color: #0f0;
                    --nova-ai-shadow-color: rgba(0, 255, 0, 0.2);
                }
            ';
    }
    
    return $css;
}
