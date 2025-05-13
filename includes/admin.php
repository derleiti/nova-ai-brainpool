<?php
if (!defined('ABSPATH')) exit;

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
        'Settings',
        'Settings',
        'manage_options',
        'nova-ai-brainpool',
        'nova_ai_admin_page'
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
        'Theme Settings',
        'Theme Settings',
        'manage_options',
        'nova-ai-theme',
        'nova_ai_theme_page'
    );
}

// Register settings
add_action('admin_init', 'nova_ai_register_settings');
function nova_ai_register_settings() {
    // AI Settings
    register_setting('nova_ai_settings', 'nova_ai_api_type', array(
        'type' => 'string',
        'default' => 'ollama',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    register_setting('nova_ai_settings', 'nova_ai_api_url', array(
        'type' => 'string',
        'default' => 'http://host.docker.internal:11434/api/generate',
        'sanitize_callback' => 'esc_url_raw'
    ));
    register_setting('nova_ai_settings', 'nova_ai_api_key', array(
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    register_setting('nova_ai_settings', 'nova_ai_model', array(
        'type' => 'string',
        'default' => 'mistral',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    register_setting('nova_ai_settings', 'nova_ai_max_tokens', array(
        'type' => 'integer',
        'default' => 250,
        'sanitize_callback' => 'absint'
    ));
    register_setting('nova_ai_settings', 'nova_ai_temperature', array(
        'type' => 'number',
        'default' => 0.7,
        'sanitize_callback' => 'floatval'
    ));
    
    // Theme settings
    register_setting('nova_ai_theme', 'nova_ai_theme_style', array(
        'type' => 'string',
        'default' => 'terminal',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    
    // Crawler settings
    register_setting('nova_ai_crawler', 'nova_ai_crawl_urls', array(
        'type' => 'string',
        'default' => '',
        'sanitize_callback' => 'sanitize_textarea_field'
    ));
    register_setting('nova_ai_crawler', 'nova_ai_crawl_depth', array(
        'type' => 'integer',
        'default' => 1,
        'sanitize_callback' => 'absint'
    ));
    register_setting('nova_ai_crawler', 'nova_ai_crawl_limit', array(
        'type' => 'integer',
        'default' => 5000,
        'sanitize_callback' => 'absint'
    ));
}

// Main settings page
function nova_ai_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    
    // Handle form submissions
    if (isset($_POST['nova_ai_save_settings']) && check_admin_referer('nova_ai_settings_nonce')) {
        // Save settings
        update_option('nova_ai_api_type', sanitize_text_field($_POST['nova_ai_api_type']));
        update_option('nova_ai_api_url', esc_url_raw($_POST['nova_ai_api_url']));
        update_option('nova_ai_api_key', sanitize_text_field($_POST['nova_ai_api_key']));
        update_option('nova_ai_model', sanitize_text_field($_POST['nova_ai_model']));
        update_option('nova_ai_max_tokens', absint($_POST['nova_ai_max_tokens']));
        update_option('nova_ai_temperature', floatval($_POST['nova_ai_temperature']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
    }
    
    // Test connection
    if (isset($_POST['nova_ai_test_connection']) && check_admin_referer('nova_ai_settings_nonce')) {
        $api_type = get_option('nova_ai_api_type', 'ollama');
        $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
        
        if ($api_type === 'ollama') {
            $data = json_encode(array(
                'model' => get_option('nova_ai_model', 'mistral'),
                'prompt' => 'Say "Nova AI connection test successful"',
                'stream' => false
            ));
            
            $response = wp_remote_post($api_url, array(
                'body' => $data,
                'headers' => array('Content-Type' => 'application/json'),
                'timeout' => 10,
            ));
        } else {
            // OpenAI API
            $api_key = get_option('nova_ai_api_key', '');
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
        }
        
        if (is_wp_error($response)) {
            echo '<div class="notice notice-error is-dismissible"><p>Connection failed: ' . esc_html($response->get_error_message()) . '</p></div>';
        } else {
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            
            if ($api_type === 'ollama' && isset($result['response'])) {
                echo '<div class="notice notice-success is-dismissible"><p>Connection successful! Response: ' . esc_html($result['response']) . '</p></div>';
            } elseif ($api_type === 'openai' && isset($result['choices'][0]['message']['content'])) {
                echo '<div class="notice notice-success is-dismissible"><p>Connection successful! Response: ' . esc_html($result['choices'][0]['message']['content']) . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Connection failed: Unexpected response format</p></div>';
            }
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=nova-ai-brainpool&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">General</a>
            <a href="?page=nova-ai-brainpool&tab=ai" class="nav-tab <?php echo $active_tab === 'ai' ? 'nav-tab-active' : ''; ?>">AI Settings</a>
            <a href="?page=nova-ai-brainpool&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">Advanced</a>
        </h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('nova_ai_settings_nonce'); ?>
            
            <?php if ($active_tab === 'general'): ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nova_ai_plugin_info">Plugin Information</label>
                        </th>
                        <td>
                            <p><strong>Version:</strong> 1.0-beta2</p>
                            <p><strong>Status:</strong> <?php echo nova_ai_connection_status(); ?></p>
                            <p><strong>Shortcode:</strong> <code>[nova_ai_chat]</code></p>
                            <p><strong>Data Directory:</strong> <code><?php echo esc_html(wp_upload_dir()['basedir'] . '/nova-ai-brainpool/'); ?></code></p>
                        </td>
                    </tr>
                </table>
            <?php elseif ($active_tab === 'ai'): ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nova_ai_api_type">AI Provider</label>
                        </th>
                        <td>
                            <select name="nova_ai_api_type" id="nova_ai_api_type">
                                <option value="ollama" <?php selected(get_option('nova_ai_api_type', 'ollama'), 'ollama'); ?>>Ollama (Local)</option>
                                <option value="openai" <?php selected(get_option('nova_ai_api_type', 'ollama'), 'openai'); ?>>OpenAI API</option>
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
                                value="<?php echo esc_attr(get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate')); ?>">
                            <p class="description">URL to the Ollama API endpoint (e.g., http://localhost:11434/api/generate)</p>
                        </td>
                    </tr>
                    
                    <tr class="api-ollama">
                        <th scope="row">
                            <label for="nova_ai_model">Ollama Model</label>
                        </th>
                        <td>
                            <input type="text" name="nova_ai_model" id="nova_ai_model" class="regular-text" 
                                value="<?php echo esc_attr(get_option('nova_ai_model', 'mistral')); ?>">
                            <p class="description">Model name to use with Ollama (e.g., mistral, llama2, etc.)</p>
                        </td>
                    </tr>
                    
                    <tr class="api-openai">
                        <th scope="row">
                            <label for="nova_ai_api_key">OpenAI API Key</label>
                        </th>
                        <td>
                            <input type="password" name="nova_ai_api_key" id="nova_ai_api_key" class="regular-text" 
                                value="<?php echo esc_attr(get_option('nova_ai_api_key', '')); ?>">
                            <p class="description">Your OpenAI API key</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="nova_ai_max_tokens">Max Tokens</label>
                        </th>
                        <td>
                            <input type="number" name="nova_ai_max_tokens" id="nova_ai_max_tokens" min="50" max="2000" step="10" 
                                value="<?php echo esc_attr(get_option('nova_ai_max_tokens', 250)); ?>">
                            <p class="description">Maximum number of tokens (words) for the AI response</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="nova_ai_temperature">Temperature</label>
                        </th>
                        <td>
                            <input type="range" name="nova_ai_temperature" id="nova_ai_temperature" min="0" max="1" step="0.1" 
                                value="<?php echo esc_attr(get_option('nova_ai_temperature', 0.7)); ?>">
                            <span id="temperature-value"><?php echo esc_html(get_option('nova_ai_temperature', 0.7)); ?></span>
                            <p class="description">Controls randomness: 0 = deterministic, 1 = creative</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="nova_ai_save_settings" class="button-primary" value="Save Settings">
                    <input type="submit" name="nova_ai_test_connection" class="button-secondary" value="Test Connection">
                </p>
            <?php elseif ($active_tab === 'advanced'): ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nova_ai_debug_mode">Debug Mode</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="nova_ai_debug_mode" id="nova_ai_debug_mode" 
                                    <?php checked(get_option('nova_ai_debug_mode', false), true); ?>>
                                Enable debug logging
                            </label>
                            <p class="description">Log API requests and responses for troubleshooting</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="nova_ai_system_prompt">System Prompt</label>
                        </th>
                        <td>
                            <textarea name="nova_ai_system_prompt" id="nova_ai_system_prompt" rows="5" cols="50" class="large-text"><?php 
                                echo esc_textarea(get_option('nova_ai_system_prompt', 'You are Nova, a helpful AI assistant for AILinux users.')); 
                            ?></textarea>
                            <p class="description">Custom system prompt for the AI model</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="nova_ai_save_settings" class="button-primary" value="Save Advanced Settings">
                </p>
            <?php endif; ?>
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
    });
    </script>
    <?php
}

// Knowledge Base management page
function nova_ai_knowledge_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle knowledge base operations
    if (isset($_POST['nova_ai_add_knowledge']) && check_admin_referer('nova_ai_knowledge_nonce')) {
        $question = sanitize_text_field($_POST['nova_ai_question']);
        $answer = sanitize_textarea_field($_POST['nova_ai_answer']);
        
        if (!empty($question) && !empty($answer)) {
            $knowledge_base = get_option('nova_ai_custom_knowledge', array());
            $knowledge_base[] = array(
                'question' => $question,
                'answer' => $answer
            );
            update_option('nova_ai_custom_knowledge', $knowledge_base);
            echo '<div class="notice notice-success is-dismissible"><p>Knowledge item added successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Both question and answer are required.</p></div>';
        }
    }
    
    // Delete knowledge item
    if (isset($_GET['delete']) && check_admin_referer('nova_ai_delete_knowledge')) {
        $index = intval($_GET['delete']);
        $knowledge_base = get_option('nova_ai_custom_knowledge', array());
        
        if (isset($knowledge_base[$index])) {
            unset($knowledge_base[$index]);
            $knowledge_base = array_values($knowledge_base); // Reindex array
            update_option('nova_ai_custom_knowledge', $knowledge_base);
            echo '<div class="notice notice-success is-dismissible"><p>Knowledge item deleted successfully!</p></div>';
        }
    }
    
    // Import JSON
    if (isset($_POST['nova_ai_import_knowledge']) && check_admin_referer('nova_ai_knowledge_nonce')) {
        if (!empty($_FILES['nova_ai_json_file']['tmp_name'])) {
            $json_data = file_get_contents($_FILES['nova_ai_json_file']['tmp_name']);
            $imported_data = json_decode($json_data, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($imported_data)) {
                $valid_items = array();
                
                foreach ($imported_data as $item) {
                    if (isset($item['question']) && isset($item['answer']) && 
                        !empty($item['question']) && !empty($item['answer'])) {
                        $valid_items[] = array(
                            'question' => sanitize_text_field($item['question']),
                            'answer' => sanitize_textarea_field($item['answer'])
                        );
                    }
                }
                
                if (!empty($valid_items)) {
                    $current_knowledge = get_option('nova_ai_custom_knowledge', array());
                    $merged_knowledge = array_merge($current_knowledge, $valid_items);
                    update_option('nova_ai_custom_knowledge', $merged_knowledge);
                    echo '<div class="notice notice-success is-dismissible"><p>' . count($valid_items) . ' knowledge items imported successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>No valid knowledge items found in the imported file.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Invalid JSON format. Please check your file.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Please select a JSON file to import.</p></div>';
        }
    }
    
    // Get current knowledge base
    $knowledge_base = array_merge(
        // Default knowledge items (always present)
        require_once plugin_dir_path(__FILE__) . '../includes/knowledge.php',
        // Custom knowledge items
        get_option('nova_ai_custom_knowledge', array())
    );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
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
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="nova_ai_answer">Answer</label>
                        </th>
                        <td>
                            <textarea name="nova_ai_answer" id="nova_ai_answer" rows="4" class="large-text" required></textarea>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="nova_ai_add_knowledge" class="button-primary" value="Add Knowledge Item">
                </p>
            </form>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Import/Export Knowledge Base</h2>
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <h3>Import from JSON</h3>
                    <form method="post" action="" enctype="multipart/form-data">
                        <?php wp_nonce_field('nova_ai_knowledge_nonce'); ?>
                        <p>
                            <input type="file" name="nova_ai_json_file" accept=".json">
                        </p>
                        <p class="submit">
                            <input type="submit" name="nova_ai_import_knowledge" class="button-secondary" value="Import Knowledge">
                        </p>
                    </form>
                </div>
                
                <div style="flex: 1;">
                    <h3>Export to JSON</h3>
                    <p>Download your current knowledge base as a JSON file:</p>
                    <p>
                        <a href="<?php echo esc_url(rest_url('nova-ai/v1/knowledge.json')); ?>" class="button-secondary" download="nova-ai-knowledge.json">Download Knowledge Base</a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="card" style="margin-top: 20px;">
            <h2>Current Knowledge Base</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Answer</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($knowledge_base as $index => $item): ?>
                    <tr>
                        <td><?php echo esc_html($item['question']); ?></td>
                        <td><?php echo esc_html($item['answer']); ?></td>
                        <td>
                            <?php if ($index >= count(nova_ai_knowledge_base())): // Only allow deleting custom items ?>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=nova-ai-knowledge&delete=' . $index), 'nova_ai_delete_knowledge'); ?>" 
                               class="button-link-delete" onclick="return confirm('Are you sure you want to delete this item?');">
                                Delete
                            </a>
                            <?php else: ?>
                            <em>Default (Cannot delete)</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($knowledge_base)): ?>
                    <tr>
                        <td colspan="3">No knowledge items found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// Web Crawler configuration page
function nova_ai_crawler_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle form submissions
    if (isset($_POST['nova_ai_save_urls']) && check_admin_referer('nova_ai_crawler_nonce')) {
        update_option('nova_ai_crawl_urls', sanitize_textarea_field($_POST['nova_ai_crawl_urls']));
        update_option('nova_ai_crawl_depth', absint($_POST['nova_ai_crawl_depth']));
        update_option('nova_ai_crawl_limit', absint($_POST['nova_ai_crawl_limit']));
        echo '<div class="notice notice-success is-dismissible"><p>Crawler settings saved successfully!</p></div>';
    }
    
    // Run crawler
    if (isset($_POST['nova_ai_run_crawler']) && check_admin_referer('nova_ai_crawler_nonce')) {
        // Display progress notice
        echo '<div class="notice notice-info"><p>Crawler is running... This may take a few minutes.</p></div>';
        
        // Include crawler functionality
        require_once plugin_dir_path(__FILE__) . '../includes/crawler.php';
        
        // Run crawler with improved feedback
        $result = nova_ai_run_crawler();
        
        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Crawl completed successfully! Output saved to: ' . esc_html($result) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Crawl failed. Please check the logs for details.</p></div>';
        }
    }
    
    // Get current settings
    $urls = get_option('nova_ai_crawl_urls', '');
    $depth = get_option('nova_ai_crawl_depth', 1);
    $limit = get_option('nova_ai_crawl_limit', 5000);
    
    // Get previous crawl results
    $data_dir = WP_CONTENT_DIR . '/uploads/nova-ai-brainpool/knowledge/';
    $results = array();
    
    if (file_exists($data_dir) && is_dir($data_dir)) {
        $files = glob($data_dir . 'general/web-*.json');
        
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
                            <textarea name="nova_ai_crawl_urls" id="nova_ai_crawl_urls" rows="10" cols="50" class="large-text"><?php 
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
                            <input type="number" name="nova_ai_crawl_depth" id="nova_ai_crawl_depth" min="1" max="3" step="1" 
                                value="<?php echo esc_attr($depth); ?>">
                            <p class="description">How many levels deep to crawl (1-3). Higher values take longer.</p>
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
                </table>
                <p class="submit">
                    <input type="submit" name="nova_ai_save_urls" class="button-primary" value="Save Configuration">
                    <input type="submit" name="nova_ai_run_crawler" class="button-secondary" value="Run Crawler Now">
                </p>
            </form>
        </div>
        
        <?php if (!empty($results)): ?>
        <div class="card" style="margin-top: 20px;">
            <h2>Previous Crawler Results</h2>
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
                            <a href="<?php echo esc_url(content_url('/uploads/nova-ai-brainpool/knowledge/general/' . $result['file'])); ?>" class="button-secondary" target="_blank">View</a>
                            <a href="<?php echo esc_url(content_url('/uploads/nova-ai-brainpool/knowledge/general/' . $result['file'])); ?>" class="button-secondary" download>Download</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// Theme Settings page
function nova_ai_theme_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle theme settings
    if (isset($_POST['nova_ai_save_theme']) && check_admin_referer('nova_ai_theme_nonce')) {
        update_option('nova_ai_theme_style', sanitize_text_field($_POST['nova_ai_theme_style']));
        echo '<div class="notice notice-success is-dismissible"><p>Theme settings saved successfully!</p></div>';
    }
    
    // Get current theme settings
    $theme_style = get_option('nova_ai_theme_style', 'terminal');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('nova_ai_theme_nonce'); ?>
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
                            echo esc_textarea(get_option('nova_ai_custom_css', '')); 
                        ?></textarea>
                        <p class="description">Add custom CSS to override the default styles.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="nova_ai_save_theme" class="button-primary" value="Save Theme Settings">
            </p>
        </form>
    </div>
    
    <style>
    .theme-preview {
        margin-top: 10px;
        margin-bottom: 15px;
    }
    </style>
    <?php
}

// Helper function to check connection status
function nova_ai_connection_status() {
    $api_type = get_option('nova_ai_api_type', 'ollama');
    $api_url = get_option('nova_ai_api_url', 'http://host.docker.internal:11434/api/generate');
    
    if ($api_type === 'ollama') {
        $response = wp_remote_get($api_url, array('timeout' => 5));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) !== 404) {
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
