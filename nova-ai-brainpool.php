<?php
/*
Plugin Name: Nova AI Brainpool
Description: Cyberpunk AI Knowledgebase & Chatbot mit OS-Knowledgebase, Crawler, manueller Quellen-Eingabe und KI-Admin-Konsole.
Version: 1.0.5-bulletproof
Author: Markus Leitermann
*/

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('NOVA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOVA_AI_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Plugin-Includes laden
if (file_exists(NOVA_AI_PLUGIN_PATH . 'admin/env-loader.php')) {
    require_once(NOVA_AI_PLUGIN_PATH . 'admin/env-loader.php');
}

// Plugin initialisieren
add_action('init', 'nova_ai_brainpool_init');
function nova_ai_brainpool_init() {
    // AJAX-Handler für eingeloggte und nicht-eingeloggte Benutzer
    add_action('wp_ajax_nova_ai_chat', 'nova_ai_handle_chat_request');
    add_action('wp_ajax_nopriv_nova_ai_chat', 'nova_ai_handle_chat_request');
}

// Assets IMMER laden wenn Shortcode verwendet wird
add_action('wp_enqueue_scripts', 'nova_ai_maybe_enqueue_assets');
function nova_ai_maybe_enqueue_assets() {
    global $post;
    
    // Prüfe ob Shortcode auf der aktuellen Seite ist
    if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'nova_ai_chat') || isset($_GET['nova_ai_debug']))) {
        
        // CSS laden
        wp_enqueue_style(
            'nova-ai-chat-css',
            NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.css',
            [],
            '1.0.5'
        );
        
        // JavaScript laden
        wp_enqueue_script(
            'nova-ai-chat-js',
            NOVA_AI_PLUGIN_URL . 'assets/chat-frontend.js',
            ['jquery'],
            '1.0.5',
            true
        );
        
        // AJAX-Konfiguration für JavaScript
        wp_localize_script('nova-ai-chat-js', 'nova_ai_chat_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_ai_chat_nonce'),
            'debug' => true
        ]);
    }
}

// Frontend Chat Shortcode - BULLETPROOF VERSION
add_shortcode('nova_ai_chat', 'nova_ai_chat_shortcode');
function nova_ai_chat_shortcode($atts) {
    // Attribute mit Defaults
    $atts = shortcode_atts([
        'height' => '450px',
        'placeholder' => 'Deine Nachricht an Nova...'
    ], $atts);
    
    // Unique ID für multiple Chats auf einer Seite
    $chat_id = 'nova-ai-chat-' . wp_rand(1000, 9999);
    
    ob_start();
    ?>
    <div class="nova-ai-chat-container" id="<?php echo $chat_id; ?>-container">
        <div class="nova-ai-chatbox" style="min-height: <?php echo esc_attr($atts['height']); ?>">
            <div id="<?php echo $chat_id; ?>-messages" class="nova-ai-chat-messages">
                <div class="nova-ai-msg ai">
                    <b>Nova:</b> Hallo! Ich bin Nova, dein KI-Assistent. Wie kann ich dir helfen?
                </div>
            </div>
            <div class="nova-ai-chat-input-row">
                <textarea 
                    id="<?php echo $chat_id; ?>-input" 
                    class="nova-ai-chat-input" 
                    placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                    rows="1"></textarea>
                <button 
                    type="button" 
                    id="<?php echo $chat_id; ?>-send" 
                    class="nova-ai-chat-send-btn"
                    onclick="novaAISendMessage('<?php echo $chat_id; ?>')">
                    Senden
                </button>
            </div>
        </div>
    </div>

    <!-- INLINE JAVASCRIPT für maximale Kompatibilität -->
    <script type="text/javascript">
    (function() {
        console.log('Nova AI Chat: Inline script loading for <?php echo $chat_id; ?>');
        
        // Warte bis DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initChat);
        } else {
            initChat();
        }
        
        function initChat() {
            console.log('Nova AI Chat: Initializing chat <?php echo $chat_id; ?>');
            
            const chatId = '<?php echo $chat_id; ?>';
            const textarea = document.getElementById(chatId + '-input');
            const sendBtn = document.getElementById(chatId + '-send');
            const messages = document.getElementById(chatId + '-messages');
            
            console.log('Elements found:', {
                textarea: !!textarea,
                sendBtn: !!sendBtn,
                messages: !!messages
            });
            
            if (!textarea || !sendBtn || !messages) {
                console.error('Nova AI Chat: Elements not found for', chatId);
                return;
            }
            
            // Enter-Taste Event
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Enter key pressed');
                    novaAISendMessage(chatId);
                    return false;
                }
                
                // Auto-resize
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            });
            
            // Button-Click Event (zusätzlich zu onclick)
            sendBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Send button clicked');
                novaAISendMessage(chatId);
                return false;
            });
            
            // Focus auf Textarea
            textarea.focus();
            
            console.log('Nova AI Chat: Initialization complete for', chatId);
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}

// Globale JavaScript-Funktion für Nachrichten senden
add_action('wp_footer', 'nova_ai_add_global_js');
function nova_ai_add_global_js() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'nova_ai_chat')) {
        ?>
        <script type="text/javascript">
        // Globale Funktion für alle Nova AI Chats
        function novaAISendMessage(chatId) {
            console.log('novaAISendMessage called for', chatId);
            
            const textarea = document.getElementById(chatId + '-input');
            const sendBtn = document.getElementById(chatId + '-send');
            const messages = document.getElementById(chatId + '-messages');
            
            if (!textarea || !sendBtn || !messages) {
                console.error('Elements not found in novaAISendMessage');
                return;
            }
            
            const userMsg = textarea.value.trim();
            if (!userMsg) {
                console.log('Empty message');
                textarea.focus();
                return;
            }
            
            console.log('Sending message:', userMsg);
            
            // Button deaktivieren
            sendBtn.disabled = true;
            sendBtn.textContent = 'Sende...';
            
            // Nachricht hinzufügen
            addMessage(messages, 'Du', userMsg, 'user');
            textarea.value = '';
            textarea.style.height = 'auto';
            
            // Loading-Nachricht
            const loadingEl = addMessage(messages, 'Nova', '⌛ Denke nach...', 'ai loading');
            
            // AJAX-Konfiguration
            const ajaxUrl = window.nova_ai_chat_ajax ? window.nova_ai_chat_ajax.ajaxurl : '/wp-admin/admin-ajax.php';
            const nonce = window.nova_ai_chat_ajax ? window.nova_ai_chat_ajax.nonce : '';
            
            console.log('AJAX Config:', {
                url: ajaxUrl,
                nonce: nonce ? 'OK' : 'MISSING!'
            });
            
            if (!nonce) {
                removeMessage(loadingEl);
                addMessage(messages, 'Nova', '❌ Konfigurationsfehler. Seite neu laden.', 'ai');
                resetSendButton(sendBtn);
                return;
            }
            
            // AJAX Request
            const formData = new FormData();
            formData.append('action', 'nova_ai_chat');
            formData.append('prompt', userMsg);
            formData.append('nonce', nonce);
            
            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                removeMessage(loadingEl);
                
                if (data.success && data.data && data.data.answer) {
                    addMessage(messages, 'Nova', data.data.answer, 'ai');
                } else {
                    const error = data.data && data.data.msg ? data.data.msg : 'Unbekannter Fehler';
                    addMessage(messages, 'Nova', '❌ ' + error, 'ai');
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                removeMessage(loadingEl);
                addMessage(messages, 'Nova', '❌ Verbindungsfehler: ' + error.message, 'ai');
            })
            .finally(() => {
                resetSendButton(sendBtn);
                textarea.focus();
            });
        }
        
        function addMessage(container, sender, message, type) {
            const div = document.createElement('div');
            div.className = 'nova-ai-msg ' + type;
            div.innerHTML = '<b>' + escapeHtml(sender) + ':</b> ' + escapeHtml(message).replace(/\n/g, '<br>');
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
            return div;
        }
        
        function removeMessage(element) {
            if (element && element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }
        
        function resetSendButton(button) {
            button.disabled = false;
            button.textContent = 'Senden';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        console.log('Nova AI Chat: Global functions loaded');
        </script>
        <?php
    }
}

// AJAX-Handler für Chat-Anfragen
function nova_ai_handle_chat_request() {
    // Debug-Logging
    error_log('Nova AI Chat: AJAX request received');
    
    // Nonce-Prüfung
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nova_ai_chat_nonce')) {
        error_log('Nova AI Chat: Nonce verification failed');
        wp_send_json_error(['msg' => 'Sicherheitsfehler - Seite neu laden']);
        return;
    }
    
    $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
    if (empty($prompt)) {
        wp_send_json_error(['msg' => 'Leere Nachricht']);
        return;
    }
    
    error_log('Nova AI Chat: Processing prompt: ' . $prompt);
    
    // .env-Konfiguration laden
    $env = nova_ai_brainpool_load_env(NOVA_AI_PLUGIN_PATH . '.env');
    $ollama_url = $env['OLLAMA_URL'] ?? 'http://127.0.0.1:11434/api/chat';
    $ollama_model = $env['OLLAMA_MODEL'] ?? 'zephyr';
    
    // System-Prompt aus Admin-Einstellungen holen
    $system_prompt = get_option('zephyr_ai_prompt', 'Du bist Nova, ein freundlicher KI-Assistent.');
    
    error_log('Nova AI Chat: Using Ollama URL: ' . $ollama_url . ', Model: ' . $ollama_model);
    
    // API-Aufruf an Ollama
    $response = nova_ai_call_ollama($ollama_url, $ollama_model, $system_prompt, $prompt);
    
    if ($response['success']) {
        error_log('Nova AI Chat: Success response');
        wp_send_json_success(['answer' => $response['message']]);
    } else {
        error_log('Nova AI Chat: Error response: ' . $response['error']);
        wp_send_json_error(['msg' => $response['error']]);
    }
}

// Ollama API-Aufruf
function nova_ai_call_ollama($api_url, $model, $system_prompt, $user_prompt) {
    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => $system_prompt
            ],
            [
                'role' => 'user',
                'content' => $user_prompt
            ]
        ],
        'stream' => false
    ];
    
    $args = [
        'method' => 'POST',
        'timeout' => 30,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode($data)
    ];
    
    error_log('Nova AI Chat: Making API call to: ' . $api_url);
    
    $response = wp_remote_post($api_url, $args);
    
    if (is_wp_error($response)) {
        $error = 'Verbindungsfehler: ' . $response->get_error_message();
        error_log('Nova AI Chat: WP Error: ' . $error);
        return [
            'success' => false,
            'error' => $error
        ];
    }
    
    $body = wp_remote_retrieve_body($response);
    $status = wp_remote_retrieve_response_code($response);
    
    error_log('Nova AI Chat: API response status: ' . $status);
    
    if ($status !== 200) {
        $error = 'API-Fehler (HTTP ' . $status . ')';
        error_log('Nova AI Chat: API Error: ' . $error . ', Body: ' . $body);
        return [
            'success' => false,
            'error' => $error
        ];
    }
    
    $json = json_decode($body, true);
    
    if (!$json || !isset($json['message']['content'])) {
        error_log('Nova AI Chat: Invalid API response: ' . $body);
        return [
            'success' => false,
            'error' => 'Ungültige API-Antwort'
        ];
    }
    
    error_log('Nova AI Chat: Successful API response received');
    
    return [
        'success' => true,
        'message' => $json['message']['content']
    ];
}

// Debug-Shortcode für Tests
add_shortcode('nova_ai_debug', function() {
    if (!current_user_can('manage_options')) {
        return 'Keine Berechtigung';
    }
    
    ob_start();
    ?>
    <div style="background:#f0f0f0;padding:20px;margin:20px 0;border-radius:5px;">
        <h3>Nova AI Debug Info</h3>
        <ul>
            <li><strong>Plugin-Version:</strong> 1.0.5-bulletproof</li>
            <li><strong>WordPress AJAX URL:</strong> <?php echo admin_url('admin-ajax.php'); ?></li>
            <li><strong>Plugin-URL:</strong> <?php echo NOVA_AI_PLUGIN_URL; ?></li>
            <li><strong>JavaScript geladen:</strong> <span id="js-check">❌ Nein</span></li>
            <li><strong>AJAX-Konfiguration:</strong> <span id="ajax-check">❌ Nein</span></li>
        </ul>
        <button onclick="testNovaAI()" type="button">Test AJAX</button>
        <div id="test-result"></div>
    </div>
    <script>
    document.getElementById('js-check').innerHTML = '✅ Ja';
    if (window.nova_ai_chat_ajax) {
        document.getElementById('ajax-check').innerHTML = '✅ Ja';
    }
    
    function testNovaAI() {
        const result = document.getElementById('test-result');
        result.innerHTML = 'Teste...';
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'nova_ai_chat',
                prompt: 'Test',
                nonce: '<?php echo wp_create_nonce('nova_ai_chat_nonce'); ?>'
            })
        })
        .then(r => r.json())
        .then(data => {
            result.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        })
        .catch(e => {
            result.innerHTML = 'Fehler: ' + e.message;
        });
    }
    </script>
    <?php
    return ob_get_clean();
});

// ======================
// Admin-Bereich (vereinfacht)
// ======================

add_action('admin_menu', 'nova_ai_admin_menu');
function nova_ai_admin_menu() {
    add_menu_page(
        'Nova AI Brainpool',
        'Nova AI',
        'manage_options',
        'nova-ai-settings',
        'nova_ai_settings_page',
        'dashicons-admin-generic'
    );
}

function nova_ai_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Keine Berechtigung!');
    }
    
    $env = nova_ai_brainpool_load_env(NOVA_AI_PLUGIN_PATH . '.env');
    $env_path = NOVA_AI_PLUGIN_PATH . '.env';
    
    // System-Prompt speichern
    if (isset($_POST['save_prompt']) && wp_verify_nonce($_POST['_wpnonce'], 'nova_ai_settings')) {
        $prompt = wp_kses_post($_POST['system_prompt']);
        update_option('zephyr_ai_prompt', $prompt);
        echo '<div class="notice notice-success"><p>System-Prompt gespeichert!</p></div>';
    }
    
    $system_prompt = get_option('zephyr_ai_prompt', 'Du bist Nova, ein freundlicher KI-Assistent.');
    ?>
    <div class="wrap">
        <h1>Nova AI Brainpool - Einstellungen</h1>
        
        <h2>Shortcodes</h2>
        <p>
            <strong>Chat:</strong> <code>[nova_ai_chat]</code><br>
            <strong>Debug:</strong> <code>[nova_ai_debug]</code> (nur für Admins)
        </p>
        
        <h2>.env Status</h2>
        <p>
            <strong>Datei:</strong> 
            <?php echo file_exists($env_path) ? '<span style="color:green;">✅ Gefunden</span>' : '<span style="color:red;">❌ Fehlt</span>'; ?>
        </p>
        
        <?php if (!empty($env)): ?>
        <pre style="background:#f0f0f0;padding:10px;border-radius:5px;">
OLLAMA_URL=<?php echo esc_html($env['OLLAMA_URL'] ?? 'nicht gesetzt'); ?>
OLLAMA_MODEL=<?php echo esc_html($env['OLLAMA_MODEL'] ?? 'nicht gesetzt'); ?>
        </pre>
        <?php endif; ?>
        
        <h2>System-Prompt</h2>
        <form method="post">
            <?php wp_nonce_field('nova_ai_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="system_prompt">System-Prompt für Nova:</label>
                    </th>
                    <td>
                        <textarea id="system_prompt" name="system_prompt" rows="5" class="large-text"><?php echo esc_textarea($system_prompt); ?></textarea>
                        <p class="description">Definiert wie sich Nova verhält und antwortet.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="save_prompt" class="button button-primary" value="Speichern">
            </p>
        </form>
        
        <h2>Test & Debug</h2>
        <p>Füge <code>[nova_ai_debug]</code> in eine Seite ein um Debug-Informationen zu sehen.</p>
    </div>
    <?php
}

// .env-Loader einbinden (falls Datei fehlt)
if (!function_exists('nova_ai_brainpool_load_env')) {
    function nova_ai_brainpool_load_env($path) {
        $env = [];
        if (!file_exists($path)) return $env;
        
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos($line, '=') !== false && $line[0] !== '#') {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value, '"\'');
            }
        }
        return $env;
    }
}
?>
