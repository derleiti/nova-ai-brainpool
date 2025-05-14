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
    if (isset($_POST['nova_ai_save_ai_settings'])) {
        check_admin_referer('nova_ai_ai_settings_nonce', 'nova_ai_nonce');
        
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

    // BUGFIX: Removed the extra closing bracket here that was causing syntax error
    
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
            <?php wp_nonce_field('nova_ai_ai_settings_nonce', 'nova_ai_nonce'); ?>
            
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

