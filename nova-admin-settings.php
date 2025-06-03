<?php
/**
 * Nova AI Admin Settings
 * 
 * Settings page template and form handling
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions
if (isset($_POST['submit']) && wp_verify_nonce($_POST['nova_ai_settings_nonce'], 'nova_ai_settings')) {
    
    // AI Settings
    if (isset($_POST['nova_ai_api_key'])) {
        update_option('nova_ai_api_key', sanitize_text_field($_POST['nova_ai_api_key']));
    }
    
    if (isset($_POST['nova_ai_api_url'])) {
        update_option('nova_ai_api_url', esc_url_raw($_POST['nova_ai_api_url']));
    }
    
    if (isset($_POST['nova_ai_model'])) {
        update_option('nova_ai_model', sanitize_text_field($_POST['nova_ai_model']));
    }
    
    if (isset($_POST['nova_ai_max_tokens'])) {
        update_option('nova_ai_max_tokens', intval($_POST['nova_ai_max_tokens']));
    }
    
    if (isset($_POST['nova_ai_temperature'])) {
        update_option('nova_ai_temperature', floatval($_POST['nova_ai_temperature']));
    }
    
    if (isset($_POST['nova_ai_system_prompt'])) {
        update_option('nova_ai_system_prompt', sanitize_textarea_field($_POST['nova_ai_system_prompt']));
    }
    
    if (isset($_POST['nova_ai_active_provider'])) {
        update_option('nova_ai_active_provider', sanitize_text_field($_POST['nova_ai_active_provider']));
    }
    
    // Crawler Settings
    update_option('nova_ai_crawl_enabled', isset($_POST['nova_ai_crawl_enabled']));
    update_option('nova_ai_auto_crawl_enabled', isset($_POST['nova_ai_auto_crawl_enabled']));
    
    if (isset($_POST['nova_ai_crawl_sites'])) {
        $sites = array_map('esc_url_raw', array_filter(explode("\n", $_POST['nova_ai_crawl_sites'])));
        update_option('nova_ai_crawl_sites', json_encode($sites));
    }
    
    if (isset($_POST['nova_ai_crawl_interval'])) {
        update_option('nova_ai_crawl_interval', sanitize_text_field($_POST['nova_ai_crawl_interval']));
        
        // Reschedule auto-crawl with new interval
        wp_clear_scheduled_hook('nova_ai_auto_crawl');
        if (get_option('nova_ai_auto_crawl_enabled')) {
            wp_schedule_event(time(), $_POST['nova_ai_crawl_interval'], 'nova_ai_auto_crawl');
        }
    }
    
    if (isset($_POST['nova_ai_max_crawl_depth'])) {
        update_option('nova_ai_max_crawl_depth', intval($_POST['nova_ai_max_crawl_depth']));
    }
    
    if (isset($_POST['nova_ai_crawl_delay'])) {
        update_option('nova_ai_crawl_delay', intval($_POST['nova_ai_crawl_delay']));
    }
    
    // Image Generation Settings
    update_option('nova_ai_image_generation_enabled', isset($_POST['nova_ai_image_generation_enabled']));
    
    if (isset($_POST['nova_ai_image_api_url'])) {
        update_option('nova_ai_image_api_url', esc_url_raw($_POST['nova_ai_image_api_url']));
    }
    
    if (isset($_POST['nova_ai_max_image_size'])) {
        update_option('nova_ai_max_image_size', intval($_POST['nova_ai_max_image_size']));
    }
    
    // General Settings
    update_option('nova_ai_save_conversations', isset($_POST['nova_ai_save_conversations']));
    
    if (isset($_POST['nova_ai_conversation_retention_days'])) {
        update_option('nova_ai_conversation_retention_days', intval($_POST['nova_ai_conversation_retention_days']));
    }
    
    // NovaNet Settings
    update_option('nova_ai_novanet_enabled', isset($_POST['nova_ai_novanet_enabled']));
    update_option('nova_ai_novanet_auto_share', isset($_POST['nova_ai_novanet_auto_share']));
    
    if (isset($_POST['nova_ai_novanet_url'])) {
        update_option('nova_ai_novanet_url', esc_url_raw($_POST['nova_ai_novanet_url']));
    }
    
    if (isset($_POST['nova_ai_novanet_api_key'])) {
        update_option('nova_ai_novanet_api_key', sanitize_text_field($_POST['nova_ai_novanet_api_key']));
    }
    
    // Provider-specific API keys
    foreach ($providers as $provider_key => $provider) {
        if (isset($_POST["nova_ai_{$provider_key}_api_key"])) {
            update_option("nova_ai_{$provider_key}_api_key", sanitize_text_field($_POST["nova_ai_{$provider_key}_api_key"]));
        }
    }
    
    add_settings_error('nova_ai_messages', 'settings_updated', __('Settings saved successfully!', 'nova-ai-brainpool'), 'success');
}

// Get current values
$current_values = array(
    'api_key' => get_option('nova_ai_api_key', ''),
    'api_url' => get_option('nova_ai_api_url', 'https://ailinux.me/api/v1'),
    'model' => get_option('nova_ai_model', 'gpt-4'),
    'max_tokens' => get_option('nova_ai_max_tokens', 2048),
    'temperature' => get_option('nova_ai_temperature', 0.7),
    'system_prompt' => get_option('nova_ai_system_prompt', 'You are Nova AI, a helpful and knowledgeable assistant.'),
    'active_provider' => get_option('nova_ai_active_provider', 'ailinux'),
    'crawl_enabled' => get_option('nova_ai_crawl_enabled', true),
    'auto_crawl_enabled' => get_option('nova_ai_auto_crawl_enabled', true),
    'crawl_sites' => json_decode(get_option('nova_ai_crawl_sites', '["https://ailinux.me"]'), true),
    'crawl_interval' => get_option('nova_ai_crawl_interval', 'hourly'),
    'max_crawl_depth' => get_option('nova_ai_max_crawl_depth', 3),
    'crawl_delay' => get_option('nova_ai_crawl_delay', 1000),
    'image_generation_enabled' => get_option('nova_ai_image_generation_enabled', true),
    'image_api_url' => get_option('nova_ai_image_api_url', 'https://ailinux.me:7860'),
    'max_image_size' => get_option('nova_ai_max_image_size', 1024),
    'save_conversations' => get_option('nova_ai_save_conversations', true),
    'conversation_retention_days' => get_option('nova_ai_conversation_retention_days', 30),
    'novanet_enabled' => get_option('nova_ai_novanet_enabled', false),
    'novanet_url' => get_option('nova_ai_novanet_url', 'https://ailinux.me/novanet'),
    'novanet_api_key' => get_option('nova_ai_novanet_api_key', ''),
    'novanet_auto_share' => get_option('nova_ai_novanet_auto_share', false)
);

?>

<div class="nova-ai-admin-wrap">
    <div class="nova-ai-admin-container">
        
        <div class="nova-ai-admin-header">
            <h1 class="nova-ai-admin-title"><?php _e('Nova AI Settings', 'nova-ai-brainpool'); ?></h1>
            <p class="nova-ai-admin-subtitle"><?php _e('Configure your AI assistant, crawler, and image generation settings', 'nova-ai-brainpool'); ?></p>
        </div>

        <?php settings_errors('nova_ai_messages'); ?>

        <div class="nova-ai-tabs">
            <div class="nova-ai-tab-nav">
                <button class="nova-ai-tab-button active" data-tab="ai-settings"><?php _e('AI Settings', 'nova-ai-brainpool'); ?></button>
                <button class="nova-ai-tab-button" data-tab="crawler-settings"><?php _e('Crawler', 'nova-ai-brainpool'); ?></button>
                <button class="nova-ai-tab-button" data-tab="image-settings"><?php _e('Images', 'nova-ai-brainpool'); ?></button>
                <button class="nova-ai-tab-button" data-tab="general-settings"><?php _e('General', 'nova-ai-brainpool'); ?></button>
                <button class="nova-ai-tab-button" data-tab="novanet-settings"><?php _e('NovaNet', 'nova-ai-brainpool'); ?></button>
            </div>

            <form method="post" action="" class="nova-ai-form" data-autosave="30000">
                <?php wp_nonce_field('nova_ai_settings', 'nova_ai_settings_nonce'); ?>

                <div class="nova-ai-tab-content">
                    
                    <!-- AI Settings Tab -->
                    <div id="ai-settings" class="nova-ai-tab-panel active">
                        <h2><?php _e('AI Configuration', 'nova-ai-brainpool'); ?></h2>
                        
                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_active_provider">
                                <?php _e('AI Provider', 'nova-ai-brainpool'); ?>
                            </label>
                            <select id="nova_ai_active_provider" name="nova_ai_active_provider" class="nova-ai-form-select">
                                <?php foreach ($providers as $key => $provider): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_values['active_provider'], $key); ?>>
                                        <?php echo esc_html($provider['name']); ?> - <?php echo esc_html($provider['description']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="nova-ai-form-help"><?php _e('Choose your preferred AI provider', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <?php foreach ($providers as $provider_key => $provider): ?>
                            <div class="nova-ai-provider-settings" data-provider="<?php echo esc_attr($provider_key); ?>" style="<?php echo $current_values['active_provider'] !== $provider_key ? 'display: none;' : ''; ?>">
                                
                                <?php if ($provider['requires_api_key']): ?>
                                <div class="nova-ai-form-group">
                                    <label class="nova-ai-form-label" for="nova_ai_<?php echo $provider_key; ?>_api_key">
                                        <?php printf(__('%s API Key', 'nova-ai-brainpool'), $provider['name']); ?>
                                    </label>
                                    <input type="password" 
                                           id="nova_ai_<?php echo $provider_key; ?>_api_key" 
                                           name="nova_ai_<?php echo $provider_key; ?>_api_key" 
                                           value="<?php echo esc_attr(get_option("nova_ai_{$provider_key}_api_key", '')); ?>" 
                                           class="nova-ai-form-input" 
                                           placeholder="<?php _e('Enter your API key...', 'nova-ai-brainpool'); ?>">
                                    <p class="nova-ai-form-help"><?php printf(__('API key for %s service', 'nova-ai-brainpool'), $provider['name']); ?></p>
                                </div>
                                <?php endif; ?>

                                <div class="nova-ai-form-group">
                                    <label class="nova-ai-form-label" for="nova_ai_api_url_<?php echo $provider_key; ?>">
                                        <?php _e('API URL', 'nova-ai-brainpool'); ?>
                                    </label>
                                    <input type="url" 
                                           id="nova_ai_api_url_<?php echo $provider_key; ?>" 
                                           name="nova_ai_api_url" 
                                           value="<?php echo $provider_key === $current_values['active_provider'] ? esc_attr($current_values['api_url']) : esc_attr($provider['api_url']); ?>" 
                                           class="nova-ai-form-input" 
                                           placeholder="<?php echo esc_attr($provider['api_url']); ?>">
                                </div>

                                <div class="nova-ai-form-group">
                                    <label class="nova-ai-form-label" for="nova_ai_model_<?php echo $provider_key; ?>">
                                        <?php _e('Model', 'nova-ai-brainpool'); ?>
                                    </label>
                                    <select id="nova_ai_model_<?php echo $provider_key; ?>" name="nova_ai_model" class="nova-ai-form-select">
                                        <?php foreach ($provider['models'] as $model_key => $model_name): ?>
                                            <option value="<?php echo esc_attr($model_key); ?>" <?php selected($current_values['model'], $model_key); ?>>
                                                <?php echo esc_html($model_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            </div>
                        <?php endforeach; ?>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_max_tokens">
                                <?php _e('Max Tokens', 'nova-ai-brainpool'); ?>
                            </label>
                            <input type="number" 
                                   id="nova_ai_max_tokens" 
                                   name="nova_ai_max_tokens" 
                                   value="<?php echo esc_attr($current_values['max_tokens']); ?>" 
                                   class="nova-ai-form-input" 
                                   min="1" 
                                   max="8192" 
                                   step="1">
                            <p class="nova-ai-form-help"><?php _e('Maximum number of tokens for AI responses (1-8192)', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_temperature">
                                <?php _e('Temperature', 'nova-ai-brainpool'); ?>
                            </label>
                            <input type="number" 
                                   id="nova_ai_temperature" 
                                   name="nova_ai_temperature" 
                                   value="<?php echo esc_attr($current_values['temperature']); ?>" 
                                   class="nova-ai-form-input" 
                                   min="0" 
                                   max="2" 
                                   step="0.1">
                            <p class="nova-ai-form-help"><?php _e('Controls randomness in responses (0.0 = deterministic, 2.0 = very random)', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_system_prompt">
                                <?php _e('System Prompt', 'nova-ai-brainpool'); ?>
                            </label>
                            <textarea id="nova_ai_system_prompt" 
                                      name="nova_ai_system_prompt" 
                                      class="nova-ai-form-textarea" 
                                      rows="4" 
                                      placeholder="<?php _e('Enter system prompt...', 'nova-ai-brainpool'); ?>"><?php echo esc_textarea($current_values['system_prompt']); ?></textarea>
                            <p class="nova-ai-form-help"><?php _e('Instructions that define the AI\'s behavior and personality', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-btn-group">
                            <button type="button" class="nova-ai-btn nova-ai-btn-secondary nova-ai-test-connection">
                                <?php _e('Test Connection', 'nova-ai-brainpool'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Crawler Settings Tab -->
                    <div id="crawler-settings" class="nova-ai-tab-panel">
                        <h2><?php _e('Web Crawler Configuration', 'nova-ai-brainpool'); ?></h2>
                        
                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label">
                                <input type="checkbox" 
                                       name="nova_ai_crawl_enabled" 
                                       value="1" 
                                       <?php checked($current_values['crawl_enabled']); ?>>
                                <?php _e('Enable Web Crawling', 'nova-ai-brainpool'); ?>
                            </label>
                            <p class="nova-ai-form-help"><?php _e('Allow the AI to use crawled content to enhance responses', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label">
                                <input type="checkbox" 
                                       name="nova_ai_auto_crawl_enabled" 
                                       value="1" 
                                       <?php checked($current_values['auto_crawl_enabled']); ?>>
                                <?php _e('Enable Auto-Crawling', 'nova-ai-brainpool'); ?>
                            </label>
                            <p class="nova-ai-form-help"><?php _e('Automatically crawl configured sites at regular intervals', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_crawl_sites">
                                <?php _e('Sites to Crawl', 'nova-ai-brainpool'); ?>
                            </label>
                            <textarea id="nova_ai_crawl_sites" 
                                      name="nova_ai_crawl_sites" 
                                      class="nova-ai-form-textarea" 
                                      rows="6" 
                                      placeholder="https://ailinux.me&#10;https://example.com"><?php echo esc_textarea(implode("\n", $current_values['crawl_sites'])); ?></textarea>
                            <p class="nova-ai-form-help"><?php _e('Enter one URL per line. These sites will be crawled for content.', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_crawl_interval">
                                <?php _e('Crawl Interval', 'nova-ai-brainpool'); ?>
                            </label>
                            <select id="nova_ai_crawl_interval" name="nova_ai_crawl_interval" class="nova-ai-form-select">
                                <option value="hourly" <?php selected($current_values['crawl_interval'], 'hourly'); ?>><?php _e('Hourly', 'nova-ai-brainpool'); ?></option>
                                <option value="twicedaily" <?php selected($current_values['crawl_interval'], 'twicedaily'); ?>><?php _e('Twice Daily', 'nova-ai-brainpool'); ?></option>
                                <option value="daily" <?php selected($current_values['crawl_interval'], 'daily'); ?>><?php _e('Daily', 'nova-ai-brainpool'); ?></option>
                                <option value="weekly" <?php selected($current_values['crawl_interval'], 'weekly'); ?>><?php _e('Weekly', 'nova-ai-brainpool'); ?></option>
                            </select>
                            <p class="nova-ai-form-help"><?php _e('How often to automatically crawl the configured sites', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_max_crawl_depth">
                                <?php _e('Max Crawl Depth', 'nova-ai-brainpool'); ?>
                            </label>
                            <input type="number" 
                                   id="nova_ai_max_crawl_depth" 
                                   name="nova_ai_max_crawl_depth" 
                                   value="<?php echo esc_attr($current_values['max_crawl_depth']); ?>" 
                                   class="nova-ai-form-input" 
                                   min="1" 
                                   max="10" 
                                   step="1">
                            <p class="nova-ai-form-help"><?php _e('How many levels deep to crawl from the starting URLs (1-10)', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_crawl_delay">
                                <?php _e('Crawl Delay (ms)', 'nova-ai-brainpool'); ?>
                            </label>
                            <input type="number" 
                                   id="nova_ai_crawl_delay" 
                                   name="nova_ai_crawl_delay" 
                                   value="<?php echo esc_attr($current_values['crawl_delay']); ?>" 
                                   class="nova-ai-form-input" 
                                   min="0" 
                                   max="10000" 
                                   step="100">
                            <p class="nova-ai-form-help"><?php _e('Delay between requests in milliseconds (0-10000)', 'nova-ai-brainpool'); ?></p>
                        </div>
                    </div>

                    <!-- Image Settings Tab -->
                    <div id="image-settings" class="nova-ai-tab-panel">
                        <h2><?php _e('Image Generation Configuration', 'nova-ai-brainpool'); ?></h2>
                        
                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label">
                                <input type="checkbox" 
                                       name="nova_ai_image_generation_enabled" 
                                       value="1" 
                                       <?php checked($current_values['image_generation_enabled']); ?>>
                                <?php _e('Enable Image Generation', 'nova-ai-brainpool'); ?>
                            </label>
                            <p class="nova-ai-form-help"><?php _e('Allow users to generate images using Stable Diffusion', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_image_api_url">
                                <?php _e('Stable Diffusion API URL', 'nova-ai-brainpool'); ?>
                            </label>
                            <input type="url" 
                                   id="nova_ai_image_api_url" 
                                   name="nova_ai_image_api_url" 
                                   value="<?php echo esc_attr($current_values['image_api_url']); ?>" 
                                   class="nova-ai-form-input" 
                                   placeholder="https://ailinux.me:7860">
                            <p class="nova-ai-form-help"><?php _e('URL of your Stable Diffusion API endpoint', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_max_image_size">
                                <?php _e('Maximum Image Size', 'nova-ai-brainpool'); ?>
                            </label>
                            <select id="nova_ai_max_image_size" name="nova_ai_max_image_size" class="nova-ai-form-select">
                                <option value="512" <?php selected($current_values['max_image_size'], 512); ?>>512x512</option>
                                <option value="768" <?php selected($current_values['max_image_size'], 768); ?>>768x768</option>
                                <option value="1024" <?php selected($current_values['max_image_size'], 1024); ?>>1024x1024</option>
                                <option value="1280" <?php selected($current_values['max_image_size'], 1280); ?>>1280x1280</option>
                            </select>
                            <p class="nova-ai-form-help"><?php _e('Maximum allowed image dimensions', 'nova-ai-brainpool'); ?></p>
                        </div>
                    </div>

                    <!-- General Settings Tab -->
                    <div id="general-settings" class="nova-ai-tab-panel">
                        <h2><?php _e('General Configuration', 'nova-ai-brainpool'); ?></h2>
                        
                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label">
                                <input type="checkbox" 
                                       name="nova_ai_save_conversations" 
                                       value="1" 
                                       <?php checked($current_values['save_conversations']); ?>>
                                <?php _e('Save Conversations', 'nova-ai-brainpool'); ?>
                            </label>
                            <p class="nova-ai-form-help"><?php _e('Store chat conversations in the database for history and analytics', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_conversation_retention_days">
                                <?php _e('Conversation Retention (Days)', 'nova-ai-brainpool'); ?>
                            </label>
                            <input type="number" 
                                   id="nova_ai_conversation_retention_days" 
                                   name="nova_ai_conversation_retention_days" 
                                   value="<?php echo esc_attr($current_values['conversation_retention_days']); ?>" 
                                   class="nova-ai-form-input" 
                                   min="0" 
                                   max="365" 
                                   step="1">
                            <p class="nova-ai-form-help"><?php _e('How long to keep conversations before automatic deletion (0 = never delete)', 'nova-ai-brainpool'); ?></p>
                        </div>
                    </div>

                    <!-- NovaNet Settings Tab -->
                    <div id="novanet-settings" class="nova-ai-tab-panel">
                        <h2><?php _e('NovaNet Configuration', 'nova-ai-brainpool'); ?></h2>
                        <p><?php _e('NovaNet allows your AI to connect with other Nova AI instances for enhanced knowledge sharing and distributed processing.', 'nova-ai-brainpool'); ?></p>
                        
                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label">
                                <input type="checkbox" 
                                       name="nova_ai_novanet_enabled" 
                                       value="1" 
                                       <?php checked($current_values['novanet_enabled']); ?>>
                                <?php _e('Enable NovaNet', 'nova-ai-brainpool'); ?>
                            </label>
                            <p class="nova-ai-form-help"><?php _e('Connect to the NovaNet network for enhanced AI capabilities', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_novanet_url">
                                <?php _e('NovaNet API URL', 'nova-ai-brainpool'); ?>
                            </label>
                            <input type="url" 
                                   id="nova_ai_novanet_url" 
                                   name="nova_ai_novanet_url" 
                                   value="<?php echo esc_attr($current_values['novanet_url']); ?>" 
                                   class="nova-ai-form-input" 
                                   placeholder="https://ailinux.me/novanet">
                            <p class="nova-ai-form-help"><?php _e('URL of the NovaNet coordinator service', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label" for="nova_ai_novanet_api_key">
                                <?php _e('NovaNet API Key', 'nova-ai-brainpool'); ?>
                            </label>
                            <input type="password" 
                                   id="nova_ai_novanet_api_key" 
                                   name="nova_ai_novanet_api_key" 
                                   value="<?php echo esc_attr($current_values['novanet_api_key']); ?>" 
                                   class="nova-ai-form-input" 
                                   placeholder="<?php _e('Enter NovaNet API key...', 'nova-ai-brainpool'); ?>">
                            <p class="nova-ai-form-help"><?php _e('API key for NovaNet authentication (optional for public networks)', 'nova-ai-brainpool'); ?></p>
                        </div>

                        <div class="nova-ai-form-group">
                            <label class="nova-ai-form-label">
                                <input type="checkbox" 
                                       name="nova_ai_novanet_auto_share" 
                                       value="1" 
                                       <?php checked($current_values['novanet_auto_share']); ?>>
                                <?php _e('Auto-Share Crawled Content', 'nova-ai-brainpool'); ?>
                            </label>
                            <p class="nova-ai-form-help"><?php _e('Automatically share suitable crawled content with the NovaNet network', 'nova-ai-brainpool'); ?></p>
                        </div>
                    </div>

                </div>

                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                    <div class="nova-ai-btn-group">
                        <button type="submit" name="submit" class="nova-ai-btn nova-ai-btn-primary">
                            <?php _e('Save Settings', 'nova-ai-brainpool'); ?>
                        </button>
                        <button type="button" class="nova-ai-btn nova-ai-btn-secondary nova-ai-export-settings">
                            <?php _e('Export Settings', 'nova-ai-brainpool'); ?>
                        </button>
                        <button type="button" class="nova-ai-btn nova-ai-btn-secondary nova-ai-import-settings">
                            <?php _e('Import Settings', 'nova-ai-brainpool'); ?>
                        </button>
                    </div>
                </div>

            </form>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Provider switching
    $('#nova_ai_active_provider').on('change', function() {
        const selectedProvider = $(this).val();
        $('.nova-ai-provider-settings').hide();
        $(`.nova-ai-provider-settings[data-provider="${selectedProvider}"]`).show();
    });

    // Real-time validation for URLs
    $('input[type="url"]').on('blur', function() {
        const url = $(this).val();
        if (url && !isValidUrl(url)) {
            $(this).addClass('error');
            if (!$(this).siblings('.nova-ai-form-error').length) {
                $(this).after('<div class="nova-ai-form-error">Please enter a valid URL</div>');
            }
        } else {
            $(this).removeClass('error');
            $(this).siblings('.nova-ai-form-error').remove();
        }
    });

    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
});
</script>
