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

// Knowledge Base management page - reusing the code from the previous knowledge.php
function nova_ai_knowledge_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle knowledge base operations
    if (isset($_POST['nova_ai_add_knowledge']) && check_admin_referer('nova_ai_knowledge_nonce')) {
        $question = sanitize_text_field($_POST['nova_ai_question']);
        $answer = sanitize_textarea_field($_POST['nova_ai_answer']);
        $category = sanitize_text_field($_POST['nova_ai_category'] ?? 'general');
        
        if (!empty($question) && !empty($answer)) {
            if (nova_ai_add_knowledge_item($question, $answer, $category)) {
                echo '<div class="notice notice-success is-dismissible"><p>Knowledge item added successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Error adding knowledge item.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Both question and answer are required.</p></div>';
        }
    }
    
    // Delete knowledge item
    if (isset($_GET['delete']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'nova_ai_delete_knowledge')) {
        $index = intval($_GET['delete']);
        
        if (nova_ai_delete_knowledge_item($index)) {
            echo '<div class="notice notice-success is-dismissible"><p>Knowledge item deleted successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Error deleting knowledge item.</p></div>';
        }
    }
    
    // Import JSON
    if (isset($_POST['nova_ai_import_knowledge']) && check_admin_referer('nova_ai_knowledge_nonce')) {
        if (!empty($_FILES['nova_ai_json_file']['tmp_name'])) {
            $json_data = file_get_contents($_FILES['nova_ai_json_file']['tmp_name']);
            $count = nova_ai_import_knowledge_from_json($json_data);
            
            if ($count) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $count . ' knowledge items imported successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>No valid knowledge items found in the imported file.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Please select a JSON file to import.</p></div>';
        }
    }
    
    // Import crawled content
    if (isset($_POST['nova_ai_import_crawled']) && check_admin_referer('nova_ai_knowledge_nonce')) {
        if (!empty($_POST['nova_ai_crawl_file'])) {
            $crawl_file = sanitize_text_field($_POST['nova_ai_crawl_file']);
            $crawl_path = NOVA_AI_DATA_DIR . 'knowledge/general/' . $crawl_file;
            
            $count = nova_ai_process_crawl_to_knowledge($crawl_path);
            
            if ($count) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $count . ' knowledge items created from crawled content!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Error processing crawled content.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Please select a crawl file to import.</p></div>';
        }
    }
    
    // Get current knowledge base
    $default_kb = nova_ai_default_knowledge_base();
    $custom_kb = get_option('nova_ai_custom_knowledge', array());
    
    // Get crawl files
    $crawl_files = array();
    $data_dir = NOVA_AI_DATA_DIR . 'knowledge/general/';
    
    if (file_exists($data_dir) && is_dir($data_dir)) {
        $files = glob($data_dir . 'web-*.json');
        
        if ($files) {
            foreach ($files as $file) {
                $crawl_files[] = array(
                    'file' => basename($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file)),
                    'size' => size_format(filesize($file))
                );
            }
            
            // Sort by date (newest first)
            usort($crawl_files, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="nova-kb-tabs">
            <a href="#add-kb" class="nova-kb-tab active">Add Knowledge</a>
            <a href="#import-kb" class="nova-kb-tab">Import/Export</a>
            <a href="#import-crawl" class="nova-kb-tab">Import Crawled Content</a>
            <a href="#view-kb" class="nova-kb-tab">Current Knowledge Base</a>
        </div>
        
        <div id="add-kb" class="nova-kb-content active">
            <div class="card">
                <h2>Add Knowledge Item</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('nova_ai_knowledge_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="nova_ai_question">Question</label>
                            </th>
                            <td>
                                <input type="text" name="nova_ai_question" id="nova_ai_question" class="regular-text" required>
                                <p class="description">The question that should trigger this knowledge item.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="nova_ai_answer">Answer</label>
                            </th>
                            <td>
                                <textarea name="nova_ai_answer" id="nova_ai_answer" rows="6" class="large-text" required></textarea>
                                <p class="description">The answer to provide when the question is asked.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="nova_ai_category">Category</label>
                            </th>
                            <td>
                                <select name="nova_ai_category" id="nova_ai_category">
                                    <option value="general">General</option>
                                    <option value="linux">Linux</option>
                                    <option value="ailinux">AILinux</option>
                                    <option value="programming">Programming</option>
                                    <option value="support">Support</option>
                                    <option value="faq">FAQ</option>
                                </select>
                                <p class="description">Category for organizing knowledge items.</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="nova_ai_add_knowledge" class="button-primary" value="Add Knowledge Item">
                    </p>
                </form>
            </div>
        </div>
        
        <div id="import-kb" class="nova-kb-content">
            <div class="nova-kb-flex">
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2>Import from JSON</h2>
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('nova_ai_knowledge_nonce'); ?>
                        <p>
                            <input type="file" name="nova_ai_json_file" accept=".json" required>
                        </p>
                        <p class="description">Import a JSON file with knowledge items. Must contain 'question' and 'answer' fields.</p>
                        <p class="submit">
                            <input type="submit" name="nova_ai_import_knowledge" class="button-primary" value="Import Knowledge">
                        </p>
                    </form>
                </div>
                
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h2>Export to JSON</h2>
                    <p>Download your current knowledge base as a JSON file:</p>
                    <p>
                        <a href="<?php echo esc_url(rest_url('nova-ai/v1/knowledge.json')); ?>" class="button button-primary" download="nova-ai-knowledge.json">Download All Knowledge</a>
                        <a href="<?php echo esc_url(rest_url('nova-ai/v1/knowledge.json')); ?>?include_default=false" class="button button-secondary" download="nova-ai-custom-knowledge.json">Download Custom Knowledge</a>
                    </p>
                    <p class="description">Export your knowledge base to a JSON file that can be imported later.</p>
                </div>
            </div>
        </div>
        
        <div id="import-crawl" class="nova-kb-content">
            <div class="card">
                <h2>Import Crawled Content</h2>
                <?php if (empty($crawl_files)): ?>
                    <p>No crawled content found. <a href="<?php echo admin_url('admin.php?page=nova-ai-crawler'); ?>">Run the crawler</a> to generate content.</p>
                <?php else: ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('nova_ai_knowledge_nonce'); ?>
                        <p>
                            <label for="nova_ai_crawl_file">Select a crawl file to import:</label>
                            <select name="nova_ai_crawl_file" id="nova_ai_crawl_file" required>
                                <option value="">-- Select Crawl File --</option>
                                <?php foreach ($crawl_files as $file): ?>
                                    <option value="<?php echo esc_attr($file['file']); ?>"><?php echo esc_html($file['file'] . ' (' . $file['date'] . ', ' . $file['size'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p class="description">Convert crawled web content into knowledge base items. This will create Q&A pairs based on page titles and headings.</p>
                        <p class="submit">
                            <input type="submit" name="nova_ai_import_crawled" class="button-primary" value="Import Crawled Content">
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div id="view-kb" class="nova-kb-content">
            <div class="card">
                <h2>Current Knowledge Base</h2>
                
                <div class="nova-kb-filter">
                    <input type="text" id="nova-kb-search" placeholder="Search knowledge base..." class="search-box">
                    <select id="nova-kb-category">
                        <option value="">All Categories</option>
                        <option value="default">Default Items</option>
                        <option value="custom">Custom Items</option>
                        <option value="general">General</option>
                        <option value="linux">Linux</option>
                        <option value="ailinux">AILinux</option>
                        <option value="programming">Programming</option>
                        <option value="support">Support</option>
                        <option value="faq">FAQ</option>
                        <option value="crawled">Crawled Content</option>
                    </select>
                </div>
                
                <table class="wp-list-table widefat fixed striped nova-kb-table">
                    <thead>
                        <tr>
                            <th class="column-question">Question</th>
                            <th class="column-answer">Answer</th>
                            <th class="column-category">Category</th>
                            <th class="column-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Default knowledge base items
                        foreach ($default_kb as $index => $item): 
                            $category = isset($item['category']) ? $item['category'] : 'default';
                        ?>
                        <tr data-category="<?php echo esc_attr($category); ?>" data-type="default">
                            <td class="column-question"><?php echo esc_html($item['question']); ?></td>
                            <td class="column-answer"><?php echo esc_html($item['answer']); ?></td>
                            <td class="column-category"><?php echo esc_html(ucfirst($category)); ?></td>
                            <td class="column-actions">
                                <em>Default (Cannot delete)</em>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php 
                        // Custom knowledge base items
                        foreach ($custom_kb as $index => $item): 
                            $category = isset($item['category']) ? $item['category'] : 'general';
                        ?>
                        <tr data-category="<?php echo esc_attr($category); ?>" data-type="custom">
                            <td class="column-question"><?php echo esc_html($item['question']); ?></td>
                            <td class="column-answer"><?php echo esc_html($item['answer']); ?></td>
                            <td class="column-category"><?php echo esc_html(ucfirst($category)); ?></td>
                            <td class="column-actions">
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=nova-ai-knowledge&delete=' . $index), 'nova_ai_delete_knowledge'); ?>" 
                                class="button-link-delete" onclick="return confirm('Are you sure you want to delete this item?');">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($default_kb) && empty($custom_kb)): ?>
                        <tr>
                            <td colspan="4">No knowledge items found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
            .nova-kb-tabs {
                display: flex;
                margin: 20px 0 0;
                border-bottom: 1px solid #ccc;
            }
            .nova-kb-tab {
                padding: 10px 15px;
                cursor: pointer;
                text-decoration: none;
                color: #555;
                border: 1px solid transparent;
                margin-bottom: -1px;
            }
            .nova-kb-tab.active {
                background: #fff;
                border: 1px solid #ccc;
                border-bottom-color: #fff;
                color: #000;
            }
            .nova-kb-content {
                display: none;
                margin-top: 20px;
            }
            .nova-kb-content.active {
                display: block;
            }
            .nova-kb-flex {
                display: flex;
                gap: 20px;
                flex-wrap: wrap;
            }
            .nova-kb-filter {
                display: flex;
                gap: 10px;
                margin-bottom: 15px;
            }
            .nova-kb-filter .search-box {
                flex: 1;
                min-width: 200px;
            }
            .nova-kb-table .column-question {
                width: 25%;
            }
            .nova-kb-table .column-answer {
                width: 45%;
            }
            .nova-kb-table .column-category {
                width: 15%;
            }
            .nova-kb-table .column-actions {
                width: 15%;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab navigation
            $('.nova-kb-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nova-kb-tab').removeClass('active');
                $(this).addClass('active');
                
                $('.nova-kb-content').removeClass('active');
                $(target).addClass('active');
            });
            
            // Knowledge base filtering
            $('#nova-kb-search').on('input', filterKnowledgeBase);
            $('#nova-kb-category').on('change', filterKnowledgeBase);
            
            function filterKnowledgeBase() {
                var search = $('#nova-kb-search').val().toLowerCase();
                var category = $('#nova-kb-category').val();
                
                $('.nova-kb-table tbody tr').each(function() {
                    var question = $(this).find('.column-question').text().toLowerCase();
                    var answer = $(this).find('.column-answer').text().toLowerCase();
                    var rowCategory = $(this).data('category');
                    var rowType = $(this).data('type');
                    
                    var searchMatch = question.includes(search) || answer.includes(search);
                    var categoryMatch = true;
                    
                    if (category === 'default') {
                        categoryMatch = rowType === 'default';
                    } else if (category === 'custom') {
                        categoryMatch = rowType === 'custom';
                    } else if (category !== '') {
                        categoryMatch = rowCategory === category;
                    }
                    
                    if (searchMatch && categoryMatch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
        </script>
    </div>
    <?php
}

// Web Crawler configuration page
function nova_ai_crawler_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle form submissions
    if (isset($_POST['nova_ai_save_crawler']) && check_admin_referer('nova_ai_crawler_nonce')) {
        update_option('nova_ai_crawl_urls', sanitize_textarea_field($_POST['nova_ai_crawl_urls']));
        update_option('nova_ai_crawl_depth', absint($_POST['nova_ai_crawl_depth']));
        update_option('nova_ai_crawl_limit', absint($_POST['nova_ai_crawl_limit']));
        update_option('nova_ai_auto_import_knowledge', isset($_POST['nova_ai_auto_import_knowledge']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Crawler settings saved successfully!</p></div>';
    }
    
    // Run crawler
    if (isset($_POST['nova_ai_run_crawler']) && check_admin_referer('nova_ai_crawler_nonce')) {
        // Display progress notice
        echo '<div class="notice notice-info"><p>Crawler is running... This may take a few minutes.</p></div>';
        
        // Run crawler
        require_once NOVA_AI_PLUGIN_DIR . 'includes/crawler.php';
        $result = nova_ai_run_crawler();
        
        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Crawl completed successfully! Output saved to: ' . esc_html($result) . '</p></div>';
            
            // Auto-import to knowledge base if enabled
            if (get_option('nova_ai_auto_import_knowledge', true)) {
                $count = nova_ai_process_crawl_to_knowledge($result);
                
                if ($count) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($count) . ' knowledge items created from crawled content.</p></div>';
                }
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Crawl failed. Please check the logs for details.</p></div>';
        }
    }
    
    // Get current settings
    $urls = get_option('nova_ai_crawl_urls', '');
    $depth = get_option('nova_ai_crawl_depth', 1);
    $limit = get_option('nova_ai_crawl_limit', 5000);
    $auto_import = get_option('nova_ai_auto_import_knowledge', true);
    
    // Get previous crawl results
    $data_dir = NOVA_AI_DATA_DIR . 'knowledge/general/';
    $results = array();
    
    if (file_exists($data_dir) && is_dir($data_dir)) {
        $files = glob($data_dir . 'web-*.json');
        
        if ($files) {
            foreach ($files as $file) {
                $results[] = array(
                    'file' => basename($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file)),
                    'size' => size_format(filesize($file)),
                    'path' => $file
                );
            }
            
            // Sort by date (newest first)
            usort($results, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="nova-crawler-tabs">
            <a href="#crawl-config" class="nova-crawler-tab active">Crawler Configuration</a>
            <a href="#crawl-results" class="nova-crawler-tab">Crawl Results</a>
        </div>
        
        <div id="crawl-config" class="nova-crawler-content active">
            <div class="card">
                <h2>Crawler Configuration</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('nova_ai_crawler_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="nova_ai_crawl_urls">Target URLs</label>
                            </th>
                            <td>
                                <textarea name="nova_ai_crawl_urls" id="nova_ai_crawl_urls" rows="10" class="large-text"><?php 
                                    echo esc_textarea($urls); 
                                ?></textarea>
                                <p class="description">Enter one URL per line to crawl for knowledge content.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="nova_ai_crawl_depth">Crawl Depth</label>
                            </th>
                            <td>
                                <select name="nova_ai_crawl_depth" id="nova_ai_crawl_depth">
                                    <option value="1" <?php selected($depth, 1); ?>>Level 1 (Start URLs only)</option>
                                    <option value="2" <?php selected($depth, 2); ?>>Level 2 (Start URLs + linked pages)</option>
                                    <option value="3" <?php selected($depth, 3); ?>>Level 3 (Deep crawl, slower)</option>
                                </select>
                                <p class="description">How deep should the crawler follow links? Higher values take longer and use more resources.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="nova_ai_crawl_limit">Character Limit</label>
                            </th>
                            <td>
                                <input type="number" name="nova_ai_crawl_limit" id="nova_ai_crawl_limit" min="1000" max="20000" step="1000" 
                                    value="<?php echo esc_attr($limit); ?>">
                                <p class="description">Maximum characters to extract per page (1000-20000).</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="nova_ai_auto_import_knowledge">Auto-Import to Knowledge Base</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="nova_ai_auto_import_knowledge" id="nova_ai_auto_import_knowledge" <?php checked($auto_import); ?>>
                                    Automatically import crawled content to knowledge base
                                </label>
                                <p class="description">When enabled, crawled content will be automatically converted to knowledge items after crawling.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Default Sites</th>
                            <td>
                                <button type="button" class="button" id="add-linux-sites">Add Linux Documentation Sites</button>
                                <button type="button" class="button" id="add-wp-sites">Add WordPress Documentation</button>
                                <p class="description">Click to add pre-configured sets of useful documentation sites.</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="nova_ai_save_crawler" class="button-primary" value="Save Configuration">
                        <input type="submit" name="nova_ai_run_crawler" class="button-secondary" value="Run Crawler Now">
                    </p>
                </form>
            </div>
        </div>
        
        <div id="crawl-results" class="nova-crawler-content">
            <div class="card">
                <h2>Previous Crawler Results</h2>
                <?php if (empty($results)): ?>
                    <p>No crawler results found. <a href="#crawl-config" class="switch-tab">Configure and run the crawler</a> to generate results.</p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Date</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?php echo esc_html($result['file']); ?></td>
                                <td><?php echo esc_html($result['date']); ?></td>
                                <td><?php echo esc_html($result['size']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(content_url('/uploads/nova-ai-brainpool/knowledge/general/' . $result['file'])); ?>" class="button button-small" target="_blank">View</a>
                                    <a href="<?php echo esc_url(content_url('/uploads/nova-ai-brainpool/knowledge/general/' . $result['file'])); ?>" class="button button-small" download>Download</a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=nova-ai-knowledge#import-crawl')); ?>" class="button button-small">Import to KB</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
            .nova-crawler-tabs {
                display: flex;
                margin: 20px 0 0;
                border-bottom: 1px solid #ccc;
            }
            .nova-crawler-tab {
                padding: 10px 15px;
                cursor: pointer;
                text-decoration: none;
                color: #555;
                border: 1px solid transparent;
                margin-bottom: -1px;
            }
            .nova-crawler-tab.active {
                background: #fff;
                border: 1px solid #ccc;
                border-bottom-color: #fff;
                color: #000;
            }
            .nova-crawler-content {
                display: none;
                margin-top: 20px;
            }
            .nova-crawler-content.active {
                display: block;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab navigation
            $('.nova-crawler-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nova-crawler-tab').removeClass('active');
                $(this).addClass('active');
                
                $('.nova-crawler-content').removeClass('active');
                $(target).addClass('active');
            });
            
            // Switch tab from text link
            $('.switch-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nova-crawler-tab[href="' + target + '"]').click();
            });
            
            // Add preset URLs
            $('#add-linux-sites').on('click', function() {
                var linuxSites = `https://wiki.ubuntuusers.de/
https://wiki.archlinux.org/
https://wiki.debian.org/
https://www.linux.org/
https://itsfoss.com/
https://www.kernel.org/doc/
https://ss64.com/bash/
https://www.linuxjournal.com/
https://www.tecmint.com/
https://www.howtoforge.com/`;
                
                var currentUrls = $('#nova_ai_crawl_urls').val();
                if (currentUrls.trim() !== '') {
                    currentUrls += "\n";
                }
                
                $('#nova_ai_crawl_urls').val(currentUrls + linuxSites);
            });
            
            $('#add-wp-sites').on('click', function() {
                var wpSites = `https://developer.wordpress.org/
https://wordpress.org/documentation/
https://codex.wordpress.org/
https://wordpress.org/support/
https://developer.wordpress.org/plugins/
https://developer.wordpress.org/themes/
https://wordpress.org/plugins/`;
                
                var currentUrls = $('#nova_ai_crawl_urls').val();
                if (currentUrls.trim() !== '') {
                    currentUrls += "\n";
                }
                
                $('#nova_ai_crawl_urls').val(currentUrls + wpSites);
            });
        });
        </script>
    </div>
    <?php
}

// Processing page (hidden from menu)
function nova_ai_processing_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
    
    switch ($action) {
        case 'test_connection':
            $result = nova_ai_test_connection();
            
            if ($result['success']) {
                wp_redirect(add_query_arg('message', 'connection_success', admin_url('admin.php?page=nova-ai-brainpool')));
            } else {
                wp_redirect(add_query_arg('message', 'connection_error', admin_url('admin.php?page=nova-ai-brainpool')));
            }
            exit;
            
        case 'clear_logs':
            $log_dir = NOVA_AI_DATA_DIR . 'logs/';
            
            if (file_exists($log_dir) && is_dir($log_dir)) {
                $files = glob($log_dir . '*.log');
                
                foreach ($files as $file) {
                    unlink($file);
                }
            }
            
            wp_redirect(add_query_arg('message', 'logs_cleared', admin_url('admin.php?page=nova-ai-brainpool')));
            exit;
            
        default:
            wp_redirect(admin_url('admin.php?page=nova-ai-brainpool'));
            exit;
    }
}

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

// Add admin notices based on message parameter
add_action('admin_notices', 'nova_ai_admin_notices');
function nova_ai_admin_notices() {
    if (!isset($_GET['page']) || strpos($_GET['page'], 'nova-ai') !== 0) {
        return;
    }
    
    if (isset($_GET['message'])) {
        $message = sanitize_text_field($_GET['message']);
        
        switch ($message) {
            case 'connection_success':
                echo '<div class="notice notice-success is-dismissible"><p>Connection test successful! Your AI provider is working correctly.</p></div>';
                break;
                
            case 'connection_error':
                echo '<div class="notice notice-error is-dismissible"><p>Connection test failed. Please check your AI settings.</p></div>';
                break;
                
            case 'logs_cleared':
                echo '<div class="notice notice-success is-dismissible"><p>Debug logs have been cleared.</p></div>';
                break;
        }
    }
}
