<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Brainpool Admin Panel
 * 
 * Consolidated admin panel that handles all plugin settings, knowledge base management,
 * crawler configuration, and theme settings in one centralized file.
 */

// Register admin menu
add_action('admin_menu', 'nova_ai_admin_menu');
function nova_ai_admin_menu() {
    add_menu_page(
        'Nova AI Brainpool',
        'Nova AI',
        'manage_options',
        'nova-ai-brainpool',
        'nova_ai_admin_page',
        'dashicons-robot',
        100
    );
    
    // Add submenu pages
    add_submenu_page(
        'nova-ai-brainpool',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'nova-ai-brainpool',
        'nova_ai_admin_page'
    );
    
    add_submenu_page(
        'nova-ai-brainpool',
        'AI Settings',
        'AI Settings',
        'manage_options',
        'nova-ai-ai-settings',
        'nova_ai_ai_settings_page'
    );
    
    add_submenu_page(
        'nova-ai-brainpool',
        'Knowledge Base',
        'Knowledge Base',
        'manage_options',
        'nova-ai-knowledge',
        'nova_ai_knowledge_page'
    );
    
    add_submenu_page(
        'nova-ai-brainpool',
        'Web Crawler',
        'Web Crawler',
        'manage_options',
        'nova-ai-crawler',
        'nova_ai_crawler_page'
    );
    
    add_submenu_page(
        'nova-ai-brainpool',
        'Chat Interface',
        'Chat Interface',
        'manage_options',
        'nova-ai-chat-settings',
        'nova_ai_chat_settings_page'
    );
    
    // Hidden submenu for processing AJAX
    add_submenu_page(
        null,
        'Processing',
        'Processing',
        'manage_options',
        'nova-ai-processing',
        'nova_ai_processing_page'
    );
}

// Register settings
add_action('admin_init', 'nova_ai_register_settings');
function nova_ai_register_settings() {
    // AI Settings
    register_setting('nova_ai_ai_settings', 'nova_ai_api_type', [
        'type' => 'string',
        'default' => 'ollama',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_api_url', [
        'type' => 'string',
        'default' => 'http://host.docker.internal:11434/api/generate',
        'sanitize_callback' => 'esc_url_raw'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_api_key', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_model', [
        'type' => 'string',
        'default' => 'zephyr',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_max_tokens', [
        'type' => 'integer',
        'default' => 750,
        'sanitize_callback' => 'absint'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_temperature', [
        'type' => 'number',
        'default' => 0.7,
        'sanitize_callback' => 'floatval'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_system_prompt', [
        'type' => 'string',
        'default' => 'You are Nova, a helpful AI assistant for AILinux users.',
        'sanitize_callback' => 'sanitize_textarea_field'
    ]);
    register_setting('nova_ai_ai_settings', 'nova_ai_debug_mode', [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    
    // Chat Interface Settings
    register_setting('nova_ai_chat_settings', 'nova_ai_theme_style', [
        'type' => 'string',
        'default' => 'terminal',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_custom_css', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_textarea_field'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_enable_fullsite_chat', [
        'type' => 'boolean',
        'default' => false,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_chat_position', [
        'type' => 'string',
        'default' => 'bottom-right',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_chat_welcome_message', [
        'type' => 'string',
        'default' => 'Hi! I\'m Nova AI. How can I help you?',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_chat_button_text', [
        'type' => 'string',
        'default' => 'Chat with Nova AI',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    register_setting('nova_ai_chat_settings', 'nova_ai_chat_placeholder', [
        'type' => 'string',
        'default' => 'Type your message...',
        'sanitize_callback' => 'sanitize_text_field'
    ]);
    
    // Crawler settings
    register_setting('nova_ai_crawler', 'nova_ai_crawl_urls', [
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_textarea_field'
    ]);
    register_setting('nova_ai_crawler', 'nova_ai_crawl_depth', [
        'type' => 'integer',
        'default' => 1,
        'sanitize_callback' => 'absint'
    ]);
    register_setting('nova_ai_crawler', 'nova_ai_crawl_limit', [
        'type' => 'integer',
        'default' => 5000,
        'sanitize_callback' => 'absint'
    ]);
    register_setting('nova_ai_crawler', 'nova_ai_auto_import_knowledge', [
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ]);
}

// Main dashboard page
function nova_ai_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get usage statistics
    $usage_stats = [
        'total_chats' => intval(get_option('nova_ai_total_chats', 0)),
        'today_chats' => intval(get_option('nova_ai_today_chats', 0)),
        'today_date' => get_option('nova_ai_today_date', date('Y-m-d')),
        'knowledge_items' => count(nova_ai_knowledge_base()),
        'custom_knowledge_items' => count(get_option('nova_ai_custom_knowledge', [])),
    ];
    
    // Reset today's stats if date changed
    if ($usage_stats['today_date'] !== date('Y-m-d')) {
        update_option('nova_ai_today_chats', 0);
        update_option('nova_ai_today_date', date('Y-m-d'));
        $usage_stats['today_chats'] = 0;
        $usage_stats['today_date'] = date('Y-m-d');
    }
    
    // Get system status
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $connection_status = nova_ai_connection_status();
    $model = get_option('nova_ai_model', 'zephyr');
    $chat_enabled = get_option('nova_ai_enable_fullsite_chat', false);
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="nova-admin-dashboard">
            <div class="nova-admin-card">
                <h2><span class="dashicons dashicons-dashboard"></span> System Status</h2>
                <table class="widefat">
                    <tr>
                        <th>Version</th>
                        <td><?php echo esc_html(NOVA_AI_VERSION); ?></td>
                    </tr>
                    <tr>
                        <th>Connection</th>
                        <td><?php echo $connection_status; ?></td>
                    </tr>
                    <tr>
                        <th>AI Provider</th>
                        <td><?php echo esc_html(ucfirst($api_type)); ?></td>
                    </tr>
                    <tr>
                        <th>Model</th>
                        <td><?php echo esc_html($model); ?></td>
                    </tr>
                    <tr>
                        <th>Full-Site Chat</th>
                        <td><?php echo $chat_enabled ? '<span style="color:green;">Enabled</span>' : '<span style="color:gray;">Disabled</span>'; ?></td>
                    </tr>
                    <tr>
                        <th>Data Directory</th>
                        <td><code><?php echo esc_html(NOVA_AI_DATA_DIR); ?></code></td>
                    </tr>
                </table>
                <p class="submit">
                    <a href="<?php echo admin_url('admin.php?page=nova-ai-ai-settings'); ?>" class="button-primary">AI Settings</a>
                    <a href="<?php echo admin_url('admin.php?page=nova-ai-chat-settings'); ?>" class="button-secondary">Chat Settings</a>
                </p>
            </div>
            
            <div class="nova-admin-card">
                <h2><span class="dashicons dashicons-chart-bar"></span> Usage Statistics</h2>
                <table class="widefat">
                    <tr>
                        <th>Total Chats</th>
                        <td><?php echo esc_html($usage_stats['total_chats']); ?></td>
                    </tr>
                    <tr>
                        <th>Today's Chats</th>
                        <td><?php echo esc_html($usage_stats['today_chats']); ?></td>
                    </tr>
                    <tr>
                        <th>Knowledge Base Items</th>
                        <td><?php echo esc_html($usage_stats['knowledge_items']); ?></td>
                    </tr>
                    <tr>
                        <th>Custom Knowledge Items</th>
                        <td><?php echo esc_html($usage_stats['custom_knowledge_items']); ?></td>
                    </tr>
                </table>
                <p class="submit">
                    <a href="<?php echo admin_url('admin.php?page=nova-ai-knowledge'); ?>" class="button-primary">Manage Knowledge Base</a>
                </p>
            </div>
            
            <div class="nova-admin-card">
                <h2><span class="dashicons dashicons-admin-tools"></span> Quick Tools</h2>
                <div class="nova-admin-tools">
                    <a href="<?php echo admin_url('admin.php?page=nova-ai-crawler'); ?>" class="nova-admin-tool">
                        <span class="dashicons dashicons-admin-site"></span>
                        <span>Web Crawler</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=nova-ai-processing&action=test_connection'); ?>" class="nova-admin-tool">
                        <span class="dashicons dashicons-controls-play"></span>
                        <span>Test Connection</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=nova-ai-knowledge'); ?>" class="nova-admin-tool">
                        <span class="dashicons dashicons-database"></span>
                        <span>Knowledge Base</span>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=nova-ai-processing&action=clear_logs'); ?>" class="nova-admin-tool">
                        <span class="dashicons dashicons-trash"></span>
                        <span>Clear Logs</span>
                    </a>
                </div>
                <div class="nova-admin-shortcode">
                    <h4>Shortcode</h4>
                    <code>[nova_ai_chat]</code>
                    <p class="description">Add this shortcode to any page or post to display the chat interface.</p>
                </div>
            </div>
        </div>
        
        <style>
            .nova-admin-dashboard {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }
            .nova-admin-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 15px;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
            }
            .nova-admin-card h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
                display: flex;
                align-items: center;
            }
            .nova-admin-card h2 .dashicons {
                margin-right: 7px;
            }
            .nova-admin-tools {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 10px;
                margin-bottom: 15px;
            }
            .nova-admin-tool {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-decoration: none;
                padding: 15px 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                transition: all 0.2s;
                background: #f9f9f9;
                color: #555;
            }
            .nova-admin-tool:hover {
                background: #f0f0f0;
                border-color: #999;
                color: #000;
            }
            .nova-admin-tool .dashicons {
                font-size: 30px;
                width: 30px;
                height: 30px;
                margin-bottom: 5px;
            }
            .nova-admin-shortcode {
                background: #f9f9f9;
                padding: 10px 15px;
                border-radius: 4px;
                border-left: 4px solid #00a0d2;
            }
            .nova-admin-shortcode h4 {
                margin: 0 0 5px 0;
            }
            .nova-admin-shortcode code {
                font-size: 14px;
                padding: 3px 5px;
                background: #f0f0f0;
                border: 1px solid #ddd;
            }
        </style>
    </div>
    <?php
}

// AI Settings Page
function nova_ai_ai_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle form submissions
    if (isset($_POST['nova_ai_save_ai_settings']) && check_admin_referer('nova_ai_ai_settings_nonce')) {
        // Save settings
        update_option('nova_ai_api_type', sanitize_text_field($_POST['nova_ai_api_type']));
        update_option('nova_ai_api_url', esc_url_raw($_POST['nova_ai_api_url']));
        update_option('nova_ai_api_key', sanitize_text_field($_POST['nova_ai_api_key']));
        update_option('nova_ai_model', sanitize_text_field($_POST['nova_ai_model']));
        update_option('nova_ai_max_tokens', absint($_POST['nova_ai_max_tokens']));
        update_option('nova_ai_temperature', floatval($_POST['nova_ai_temperature']));
        update_option('nova_ai_system_prompt', sanitize_textarea_field($_POST['nova_ai_system_prompt']));
        update_option('nova_ai_debug_mode', isset($_POST['nova_ai_debug_mode']));
        
        echo '<div class="notice notice-success is-dismissible"><p>AI settings saved successfully!</p></div>';
    }
    
    // Test connection
    if (isset($_POST['nova_ai_test_connection']) && check_admin_referer('nova_ai_ai_settings_nonce')) {
        $result = nova_ai_test_connection();
        
        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
    
    // Get current settings
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $api_key = get_option('nova_ai_api_key', '');
    $model = get_option('nova_ai_model', 'zephyr');
    $max_tokens = get_option('nova_ai_max_tokens', 750);
    $temperature = get_option('nova_ai_temperature', 0.7);
    $system_prompt = get_option('nova_ai_system_prompt', 'You are Nova, a helpful AI assistant for AILinux users.');
    $debug_mode = get_option('nova_ai_debug_mode', false);
    
    // Get Ollama models
    $ollama_models = nova_ai_get_ollama_models();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('nova_ai_ai_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="nova_ai_api_type">AI Provider</label>
                    </th>
                    <td>
                        <select name="nova_ai_api_type" id="nova_ai_api_type">
                            <option value="ollama" <?php selected($api_type, 'ollama'); ?>>Ollama (Local LLM)</option>
                            <option value="openai" <?php selected($api_type, 'openai'); ?>>OpenAI API</option>
                        </select>
                        <p class="description">Choose which AI provider to use for chat responses.</p>
                    </td>
                </tr>
                
                <tr class="api-ollama">
                    <th scope="row">
                        <label for="nova_ai_api_url">Ollama API URL</label>
                    </th>
                    <td>
                        <input type="url" name="nova_ai_api_url" id="nova_ai_api_url" class="regular-text" 
                            value="<?php echo esc_attr($api_url); ?>">
                        <p class="description">URL to the Ollama API endpoint (e.g., http://localhost:11434/api/generate)</p>
                    </td>
                </tr>
                
                <tr class="api-ollama">
                    <th scope="row">
                        <label for="nova_ai_model">Ollama Model</label>
                    </th>
                    <td>
                        <select name="nova_ai_model" id="nova_ai_model" class="regular-text">
                            <?php foreach ($ollama_models as $model_id => $model_name): ?>
                                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($model, $model_id); ?>><?php echo esc_html($model_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="refresh-models" class="button-secondary">Refresh Models</button>
                        <div id="model-status"></div>
                        <p class="description">Select an AI model to use with Ollama.</p>
                        <div class="model-info" style="margin-top: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px; border-left: 4px solid #00a0d2;">
                            <strong>Recommended Models:</strong>
                            <ul style="margin-top: 5px;">
                                <li><strong>zephyr</strong> - Best balance of performance and quality</li>
                                <li><strong>llama2</strong> - Good for general purpose usage</li>
                                <li><strong>mistral</strong> - Excellent performance on diverse tasks</li>
                                <li><strong>neural-chat</strong> - Optimized for conversation</li>
                            </ul>
                            <p>Not listed? Run <code>ollama pull model_name</code> on your server to download a new model.</p>
                        </div>
                    </td>
                </tr>
                
                <tr class="api-openai">
                    <th scope="row">
                        <label for="nova_ai_api_key">OpenAI API Key</label>
                    </th>
                    <td>
                        <input type="password" name="nova_ai_api_key" id="nova_ai_api_key" class="regular-text" 
                            value="<?php echo esc_attr($api_key); ?>">
                        <p class="description">Your OpenAI API key</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova_ai_max_tokens">Max Tokens</label>
                    </th>
                    <td>
                        <input type="number" name="nova_ai_max_tokens" id="nova_ai_max_tokens" min="100" max="4000" step="50" 
                            value="<?php echo esc_attr($max_tokens); ?>">
                        <p class="description">Maximum number of tokens (words) for the AI response. Higher values allow longer responses but use more resources.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova_ai_temperature">Temperature</label>
                    </th>
                    <td>
                        <input type="range" name="nova_ai_temperature" id="nova_ai_temperature" min="0" max="1" step="0.1" 
                            value="<?php echo esc_attr($temperature); ?>">
                        <span id="temperature-value"><?php echo esc_html($temperature); ?></span>
                        <p class="description">Controls randomness: 0 = deterministic, 1 = creative</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova_ai_system_prompt">System Prompt</label>
                    </th>
                    <td>
                        <textarea name="nova_ai_system_prompt" id="nova_ai_system_prompt" rows="5" class="large-text"><?php 
                            echo esc_textarea($system_prompt); 
                        ?></textarea>
                        <p class="description">Custom system prompt for the AI model. This sets the AI's behavior and persona.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova_ai_debug_mode">Debug Mode</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="nova_ai_debug_mode" id="nova_ai_debug_mode" <?php checked($debug_mode); ?>>
                            Enable debug logging
                        </label>
                        <p class="description">Log API requests and responses for troubleshooting. Logs are stored in: <code><?php echo esc_html(NOVA_AI_DATA_DIR . 'logs/'); ?></code></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="nova_ai_save_ai_settings" class="button-primary" value="Save Settings">
                <input type="submit" name="nova_ai_test_connection" class="button-secondary" value="Test Connection">
            </p>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Show/hide API settings based on selection
        function toggleApiSettings() {
            var apiType = $('#nova_ai_api_type').val();
            
            if (apiType === 'ollama') {
                $('.api-ollama').show();
                $('.api-openai').hide();
            } else {
                $('.api-ollama').hide();
                $('.api-openai').show();
            }
        }
        
        toggleApiSettings();
        $('#nova_ai_api_type').on('change', toggleApiSettings);
        
        // Update temperature value display
        $('#nova_ai_temperature').on('input', function() {
            $('#temperature-value').text($(this).val());
        });
        
        // Refresh Ollama models
        $('#refresh-models').on('click', function() {
            var statusDiv = $('#model-status');
            var apiUrl = $('#nova_ai_api_url').val();
            
            statusDiv.html('<p><em>Checking available models...</em></p>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'nova_ai_refresh_models',
                    api_url: apiUrl,
                    _wpnonce: '<?php echo wp_create_nonce('nova_ai_refresh_models'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        var modelSelect = $('#nova_ai_model');
                        var currentValue = modelSelect.val();
                        
                        modelSelect.empty();
                        
                        $.each(response.data.models, function(id, name) {
                            var selected = (id === currentValue) ? 'selected' : '';
                            modelSelect.append('<option value="' + id + '" ' + selected + '>' + name + '</option>');
                        });
                        
                        statusDiv.html('<p style="color:green;">✓ Found ' + Object.keys(response.data.models).length + ' models</p>');
                    } else {
                        statusDiv.html('<p style="color:red;">✗ Error: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    statusDiv.html('<p style="color:red;">✗ Connection error</p>');
                }
            });
        });
    });
    </script>
    <?php
}

// Chat Interface Settings Page
function nova_ai_chat_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle form submissions
    if (isset($_POST['nova_ai_save_chat_settings']) && check_admin_referer('nova_ai_chat_settings_nonce')) {
        update_option('nova_ai_theme_style', sanitize_text_field($_POST['nova_ai_theme_style']));
        update_option('nova_ai_custom_css', sanitize_textarea_field($_POST['nova_ai_custom_css']));
        update_option('nova_ai_enable_fullsite_chat', isset($_POST['nova_ai_enable_fullsite_chat']));
        update_option('nova_ai_chat_position', sanitize_text_field($_POST['nova_ai_chat_position']));
        update_option('nova_ai_chat_welcome_message', sanitize_text_field($_POST['nova_ai_chat_welcome_message']));
        update_option('nova_ai_chat_button_text', sanitize_text_field($_POST['nova_ai_chat_button_text']));
        update_option('nova_ai_chat_placeholder', sanitize_text_field($_POST['nova_ai_chat_placeholder']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Chat settings saved successfully!</p></div>';
    }
    
    // Get current settings
    $theme_style = get_option('nova_ai_theme_style', 'terminal');
    $custom_css = get_option('nova_ai_custom_css', '');
    $enable_fullsite_chat = get_option('nova_ai_enable_fullsite_chat', false);
    $chat_position = get_option('nova_ai_chat_position', 'bottom-right');
    $welcome_message = get_option('nova_ai_chat_welcome_message', 'Hi! I\'m Nova AI. How can I help you?');
    $button_text = get_option('nova_ai_chat_button_text', 'Chat with Nova AI');
    $placeholder = get_option('nova_ai_chat_placeholder', 'Type your message...');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('nova_ai_chat_settings_nonce'); ?>
            
            <h2 class="title">Chat Appearance</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="nova_ai_theme_style">Chat Theme Style</label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="nova_ai_theme_style" value="terminal" <?php checked($theme_style, 'terminal'); ?>>
                                Terminal (Green on Black)
                                <div class="theme-preview terminal-preview">
                                    <div style="background:#000; color:#0f0; font-family:monospace; padding:10px; border-radius:5px; max-width:300px;">
                                        <div>> What is AILinux?</div>
                                        <div>AILinux is an independent, open-source Linux Distribution created by Markus Leitermann...</div>
                                    </div>
                                </div>
                            </label>
                            <br><br>
                            <label>
                                <input type="radio" name="nova_ai_theme_style" value="dark" <?php checked($theme_style, 'dark'); ?>>
                                Dark Modern
                                <div class="theme-preview dark-preview">
                                    <div style="background:#121212; color:#eee; font-family:sans-serif; padding:10px; border-radius:5px; max-width:300px;">
                                        <div style="text-align:right; background:#1f1f1f; padding:5px; border-radius:4px; margin:5px 0;">What is AILinux?</div>
                                        <div style="background:#292929; color:#00ffc8; padding:5px; border-radius:4px;">AILinux is an independent, open-source Linux Distribution created by Markus Leitermann...</div>
                                    </div>
                                </div>
                            </label>
                            <br><br>
                            <label>
                                <input type="radio" name="nova_ai_theme_style" value="light" <?php checked($theme_style, 'light'); ?>>
                                Light Modern
                                <div class="theme-preview light-preview">
                                    <div style="background:#f9f9f9; color:#333; font-family:sans-serif; padding:10px; border-radius:5px; max-width:300px;">
                                        <div style="text-align:right; background:#e6e6e6; padding:5px; border-radius:4px; margin:5px 0;">What is AILinux?</div>
                                        <div style="background:#f0f0f0; color:#008066; padding:5px; border-radius:4px;">AILinux is an independent, open-source Linux Distribution created by Markus Leitermann...</div>
                                    </div>
                                </div>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova_ai_custom_css">Custom CSS</label>
                    </th>
                    <td>
                        <textarea name="nova_ai_custom_css" id="nova_ai_custom_css" rows="8" cols="50" class="large-text code"><?php 
                            echo esc_textarea($custom_css); 
                        ?></textarea>
                        <p class="description">Add custom CSS to override the default styles.</p>
                    </td>
                </tr>
            </table>
            
            <h2 class="title">Full-Site Chat</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="nova_ai_enable_fullsite_chat">Enable Full-Site Chat</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="nova_ai_enable_fullsite_chat" id="nova_ai_enable_fullsite_chat" <?php checked($enable_fullsite_chat); ?>>
                            Display chat on all site pages
                        </label>
                        <p class="description">When enabled, a chat button will appear on all pages of your site.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova_ai_chat_position">Chat Position</label>
                    </th>
                    <td>
                        <select name="nova_ai_chat_position" id="nova_ai_chat_position">
                            <option value="bottom-right" <?php selected($chat_position, 'bottom-right'); ?>>Bottom Right</option>
                            <option value="bottom-left" <?php selected($chat_position, 'bottom-left'); ?>>Bottom Left</option>
                            <option value="top-right" <?php selected($chat_position, 'top-right'); ?>>Top Right</option>
                            <option value="top-left" <?php selected($chat_position, 'top-left'); ?>>Top Left</option>
                        </select>
                        <p class="description">Where should the chat widget appear on the page?</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova_ai_chat_welcome_message">Welcome Message</label>
                    </th>
                    <td>
                        <input type="text" name="nova_ai_chat_welcome_message" id="nova_ai_chat_welcome_message" class="regular-text" 
                            value="<?php echo esc_attr($welcome_message); ?>">
                        <p class="description">First message displayed when the chat is opened.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova_ai_chat_button_text">Button Text</label>
                    </th>
                    <td>
                        <input type="text" name="nova_ai_chat_button_text" id="nova_ai_chat_button_text" class="regular-text" 
                            value="<?php echo esc_attr($button_text); ?>">
                        <p class="description">Text displayed on the chat button.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="nova_ai_chat_placeholder">Input Placeholder</label>
                    </th>
                    <td>
                        <input type="text" name="nova_ai_chat_placeholder" id="nova_ai_chat_placeholder" class="regular-text" 
                            value="<?php echo esc_attr($placeholder); ?>">
                        <p class="description">Placeholder text for the chat input field.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="nova_ai_save_chat_settings" class="button-primary" value="Save Settings">
            </p>
        </form>
        
        <style>
            .theme-preview {
                margin-top: 10px;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                display: inline-block;
            }
            h2.title {
                font-size: 1.3em;
                margin: 1.5em 0 1em;
                padding-bottom: 5px;
                border-bottom: 1px solid #ddd;
            }
        </style>
    </div>
    <?php
}

// Helper functions, knowledge page, crawler page, etc. go here
// These have been omitted for brevity but would be included in the full code

// Test connection to AI provider
function nova_ai_test_connection() {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    
    if ($api_type === 'ollama') {
        $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
        $model = get_option('nova_ai_model', 'zephyr');
        
        $data = json_encode(array(
            'model' => $model,
            'prompt' => 'Say "Nova AI connection test successful"',
            'stream' => false
        ));
        
        $response = wp_remote_post($api_url, array(
            'body' => $data,
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 10,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['response'])) {
            return array(
                'success' => true,
                'message' => 'Connection successful! Response: ' . $result['response']
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Connection failed: Unexpected response format'
            );
        }
    } else {
        $api_key = get_option('nova_ai_api_key', '');
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key not configured'
            );
        }
        
        $data = json_encode(array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array('role' => 'user', 'content' => 'Say "Nova AI connection test successful"')
            ),
            'max_tokens' => 50
        ));
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'body' => $data,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'timeout' => 10,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'message' => 'Connection successful! Response: ' . $result['choices'][0]['message']['content']
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Connection failed: Unexpected response format'
            );
        }
    }
}

// Get available Ollama models
function nova_ai_get_ollama_models() {
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    $api_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
    
    $models = array(
        'zephyr' => 'Zephyr (Recommended)',
        'mistral' => 'Mistral',
        'llama2' => 'LLaMA 2',
        'phi' => 'Phi-2',
        'gemma' => 'Gemma',
        'neural-chat' => 'Neural Chat'
    );
    
    // Try to get list of models from Ollama
    $response = wp_remote_get($api_url, array('timeout' => 5));
    
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['models'])) {
            $available_models = array();
            
            foreach ($result['models'] as $model) {
                if (isset($model['name'])) {
                    // Use friendly names for known models, otherwise use the raw name
                    $model_name = $model['name'];
                    $display_name = isset($models[$model_name]) ? $models[$model_name] : $model_name;
                    
                    $available_models[$model_name] = $display_name;
                }
            }
            
            // If we found models, use those instead of our default list
            if (!empty($available_models)) {
                return $available_models;
            }
        }
    }
    
    // Return default list if we couldn't get models from the API
    return $models;
}

// AJAX handler for refreshing models
add_action('wp_ajax_nova_ai_refresh_models', 'nova_ai_ajax_refresh_models');
function nova_ai_ajax_refresh_models() {
    // Check nonce
    check_ajax_referer('nova_ai_refresh_models');
    
    // Get API URL from request or use default
    $api_url = isset($_POST['api_url']) ? esc_url_raw($_POST['api_url']) : get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    
    // Convert to list API endpoint
    $api_url = preg_replace('/\/api\/generate$/', '/api/list', $api_url);
    
    // Get models from Ollama
    $response = wp_remote_get($api_url, array('timeout' => 10));
    
    if (is_wp_error($response)) {
        wp_send_json_error('Connection error: ' . $response->get_error_message());
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    if (!isset($result['models'])) {
        wp_send_json_error('Invalid response from Ollama API');
        return;
    }
    
    // Default friendly names for common models
    $friendly_names = array(
        'zephyr' => 'Zephyr (Recommended)',
        'mistral' => 'Mistral',
        'llama2' => 'LLaMA 2',
        'phi' => 'Phi-2',
        'gemma' => 'Gemma',
        'neural-chat' => 'Neural Chat',
        'mixtral' => 'Mixtral 8x7B',
        'codellama' => 'Code Llama',
        'stablelm' => 'Stable LM',
        'yarn' => 'YaRN'
    );
    
    // Extract model names
    $models = array();
    foreach ($result['models'] as $model) {
        if (isset($model['name'])) {
            $model_name = $model['name'];
            $display_name = isset($friendly_names[$model_name]) ? $friendly_names[$model_name] : $model_name;
            
            $models[$model_name] = $display_name;
        }
    }
    
    wp_send_json_success(array(
        'models' => $models
    ));
}

// Helper function to check connection status
function nova_ai_connection_status() {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    
    if ($api_type === 'ollama') {
        $response = wp_remote_get(preg_replace('/\/api\/generate$/', '/api/list', $api_url), array('timeout' => 3));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            return '<span style="color:green;">✓ Connected to Ollama</span>';
        }
    } else {
        $api_key = get_option('nova_ai_api_key', '');
        
        if (!empty($api_key)) {
            return '<span style="color:green;">✓ OpenAI API Key configured</span>';
        }
    }
    
    return '<span style="color:red;">✗ Not connected</span>';
}
