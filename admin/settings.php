<?php
/**
 * Settings and Shortcodes for Nova AI Brainpool
 * 
 * @package Nova_AI_Brainpool
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the search shortcode
 */
function nova_ai_search_shortcode($atts = array(), $content = null) {
    // Extract attributes
    $atts = shortcode_atts(array(
        'theme' => get_option('nova_ai_theme_style', 'terminal'),
        'placeholder' => __('Ask a question to Zephyr...', 'nova-ai-brainpool'),
        'button_text' => __('Send', 'nova-ai-brainpool'),
        'width' => '100%',
    ), $atts, 'nova_ai_search');
    
    // Sanitize attributes
    $theme = sanitize_text_field($atts['theme']);
    $placeholder = sanitize_text_field($atts['placeholder']);
    $button_text = sanitize_text_field($atts['button_text']);
    $width = sanitize_text_field($atts['width']);
    
    // Enqueue necessary scripts
    if (!wp_script_is('nova-ai-chat', 'enqueued')) {
        wp_enqueue_script('nova-ai-chat');
        wp_enqueue_style('nova-ai-css');
    }
    
    ob_start(); 
    ?>
    <div id="nova-ai-chat-wrapper" style="max-width: <?php echo esc_attr($width); ?>;" class="nova-theme-<?php echo esc_attr($theme); ?>">
        <form id="nova-ai-chat-form">
            <input type="text" id="nova-ai-user-input" placeholder="<?php echo esc_attr($placeholder); ?>" required />
            <input type="file" id="nova-ai-image-upload" accept="image/*" aria-label="<?php esc_attr_e('Upload image', 'nova-ai-brainpool'); ?>" />
            <button type="submit"><?php echo esc_html($button_text); ?></button>
        </form>
        <div id="nova-ai-chat-output" aria-live="polite"></div>
    </div>
    <?php 
    return ob_get_clean();
}
add_shortcode('nova_ai_search', 'nova_ai_search_shortcode');

/**
 * Register the chat shortcode
 */
function nova_ai_chat_shortcode($atts = array(), $content = null) {
    // Extract attributes
    $atts = shortcode_atts(array(
        'theme' => get_option('nova_ai_theme_style', 'terminal'),
        'placeholder' => __('Chat with Nova AI...', 'nova-ai-brainpool'),
        'button_text' => __('Send', 'nova-ai-brainpool'),
        'welcome' => __('Welcome! How can I help you today?', 'nova-ai-brainpool'),
        'width' => '600px',
    ), $atts, 'nova_ai_chat');
    
    // Sanitize attributes
    $theme = sanitize_text_field($atts['theme']);
    $placeholder = sanitize_text_field($atts['placeholder']);
    $button_text = sanitize_text_field($atts['button_text']);
    $welcome = sanitize_text_field($atts['welcome']);
    $width = sanitize_text_field($atts['width']);
    
    // Create unique ID for this chat instance
    $chat_id = 'nova-ai-chat-' . wp_rand(1000, 9999);
    
    // Enqueue necessary scripts
    if (!wp_script_is('nova-ai-chat', 'enqueued')) {
        wp_enqueue_script('nova-ai-chat');
        wp_enqueue_style('nova-ai-css');
    }
    
    ob_start(); 
    ?>
    <div id="<?php echo esc_attr($chat_id); ?>" class="nova-ai-chatbot <?php echo esc_attr('nova-theme-' . $theme); ?>" data-chat-id="<?php echo esc_attr($chat_id); ?>" style="max-width: <?php echo esc_attr($width); ?>;">
        <div class="nova-ai-console-header">
            <span class="nova-ai-title"><?php esc_html_e('Nova AI Console', 'nova-ai-brainpool'); ?></span>
            <div class="nova-ai-status"><?php esc_html_e('Ready', 'nova-ai-brainpool'); ?></div>
        </div>
        <div class="nova-ai-console-output" aria-live="polite">
            <div class="ai-response"><?php echo esc_html($welcome); ?></div>
        </div>
        <div class="nova-ai-console-input-area">
            <textarea id="nova-ai-console-input-<?php echo esc_attr($chat_id); ?>" class="nova-ai-console-input" placeholder="<?php echo esc_attr($placeholder); ?>" rows="1" aria-label="<?php esc_attr_e('Message to Nova AI', 'nova-ai-brainpool'); ?>"></textarea>
            <button id="nova-ai-send-<?php echo esc_attr($chat_id); ?>" class="nova-ai-send"><?php echo esc_html($button_text); ?></button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('nova_ai_chat', 'nova_ai_chat_shortcode');

/**
 * Register admin menu
 */
function nova_ai_register_admin_menu() {
    add_options_page(
        __('Nova AI Settings', 'nova-ai-brainpool'),
        __('Nova AI', 'nova-ai-brainpool'),
        'manage_options',
        'nova-ai-settings',
        'nova_ai_render_settings_page'
    );
}
add_action('admin_menu', 'nova_ai_register_admin_menu');

/**
 * Render settings page
 */
function nova_ai_render_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Nova AI Settings', 'nova-ai-brainpool'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('nova_ai_settings_group');
            do_settings_sections('nova-ai-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register settings
 */
function nova_ai_register_settings() {
    register_setting('nova_ai_settings_group', 'nova_ai_model', 'sanitize_text_field');
    register_setting('nova_ai_settings_group', 'nova_ai_api_type', 'sanitize_text_field');
    register_setting('nova_ai_settings_group', 'nova_ai_api_url', 'sanitize_url');
    register_setting('nova_ai_settings_group', 'nova_ai_api_key', 'sanitize_text_field');
    register_setting('nova_ai_settings_group', 'nova_ai_theme_style', 'sanitize_text_field');
    register_setting('nova_ai_settings_group', 'nova_ai_enable_fullsite_chat', 'intval');
    register_setting('nova_ai_settings_group', 'nova_ai_chat_position', 'sanitize_text_field');
    register_setting('nova_ai_settings_group', 'nova_ai_system_prompt', 'sanitize_textarea_field');
    register_setting('nova_ai_settings_group', 'nova_ai_temperature', 'floatval');
    
    add_settings_section(
        'nova_ai_main_section',
        __('Model Settings', 'nova-ai-brainpool'),
        null,
        'nova-ai-settings'
    );
    
    add_settings_field(
        'nova_ai_model',
        __('Ollama Model', 'nova-ai-brainpool'),
        'nova_ai_model_field',
        'nova-ai-settings',
        'nova_ai_main_section'
    );
    
    add_settings_field(
        'nova_ai_api_type',
        __('API Type', 'nova-ai-brainpool'),
        'nova_ai_api_type_field',
        'nova-ai-settings',
        'nova_ai_main_section'
    );
    
    add_settings_field(
        'nova_ai_theme_style',
        __('Theme Style', 'nova-ai-brainpool'),
        'nova_ai_theme_field',
        'nova-ai-settings',
        'nova_ai_main_section'
    );
    
    add_settings_field(
        'nova_ai_enable_fullsite_chat',
        __('Enable Full-site Chat', 'nova-ai-brainpool'),
        'nova_ai_fullsite_chat_field',
        'nova-ai-settings',
        'nova_ai_main_section'
    );
}
add_action('admin_init', 'nova_ai_register_settings');

/**
 * Model field callback
 */
function nova_ai_model_field() {
    $value = get_option('nova_ai_model', 'zephyr');
    ?>
    <select name="nova_ai_model">
        <option value="zephyr" <?php selected($value, 'zephyr'); ?>><?php esc_html_e('Zephyr', 'nova-ai-brainpool'); ?></option>
        <option value="llava" <?php selected($value, 'llava'); ?>><?php esc_html_e('LLaVA', 'nova-ai-brainpool'); ?></option>
        <option value="mistral" <?php selected($value, 'mistral'); ?>><?php esc_html_e('Mistral', 'nova-ai-brainpool'); ?></option>
        <option value="custom" <?php selected($value, 'custom'); ?>><?php esc_html_e('Custom (.env)', 'nova-ai-brainpool'); ?></option>
    </select>
    <p class="description"><?php esc_html_e('Select the AI model to use. Choose "Custom" to use the model specified in your .env file.', 'nova-ai-brainpool'); ?></p>
    <?php
}

/**
 * API type field callback
 */
function nova_ai_api_type_field() {
    $value = get_option('nova_ai_api_type', 'ollama');
    ?>
    <select name="nova_ai_api_type">
        <option value="ollama" <?php selected($value, 'ollama'); ?>><?php esc_html_e('Ollama (Local)', 'nova-ai-brainpool'); ?></option>
        <option value="openai" <?php selected($value, 'openai'); ?>><?php esc_html_e('OpenAI', 'nova-ai-brainpool'); ?></option>
    </select>
    <p class="description"><?php esc_html_e('Select API type to use for chat and vision features.', 'nova-ai-brainpool'); ?></p>
    <?php
}

/**
 * Theme field callback
 */
function nova_ai_theme_field() {
    $value = get_option('nova_ai_theme_style', 'terminal');
    ?>
    <select name="nova_ai_theme_style">
        <option value="terminal" <?php selected($value, 'terminal'); ?>><?php esc_html_e('Terminal (Green)', 'nova-ai-brainpool'); ?></option>
        <option value="dark" <?php selected($value, 'dark'); ?>><?php esc_html_e('Dark', 'nova-ai-brainpool'); ?></option>
        <option value="light" <?php selected($value, 'light'); ?>><?php esc_html_e('Light', 'nova-ai-brainpool'); ?></option>
        <option value="auto" <?php selected($value, 'auto'); ?>><?php esc_html_e('Auto (System Preference)', 'nova-ai-brainpool'); ?></option>
    </select>
    <p class="description"><?php esc_html_e('Select theme style for the chat interface.', 'nova-ai-brainpool'); ?></p>
    <?php
}

/**
 * Full-site chat field callback
 */
function nova_ai_fullsite_chat_field() {
    $value = get_option('nova_ai_enable_fullsite_chat', 0);
    ?>
    <label>
        <input type="checkbox" name="nova_ai_enable_fullsite_chat" value="1" <?php checked($value, 1); ?> />
        <?php esc_html_e('Enable floating chat button on all pages', 'nova-ai-brainpool'); ?>
    </label>
    <p class="description"><?php esc_html_e('When enabled, a chat button will appear on all pages of your site.', 'nova-ai-brainpool'); ?></p>
    <?php
}
