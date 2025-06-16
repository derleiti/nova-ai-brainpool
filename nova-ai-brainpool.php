<?php
/*
Plugin Name: Nova AI Brainpool (Production Safe)
Description: Sichere Production-Version mit Error-Handling
Version: 2.0.2-safe
Author: Nova AI - AILinux Project
*/

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('NOVA_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NOVA_AI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('NOVA_AI_VERSION', '2.0.2-safe');

/**
 * Nova AI Brainpool - Production Safe Class
 */
class NovaAIBrainpoolSafe {
    
    private $providers = null;
    private $stable_diffusion = null;
    private $crawler = null;
    private $novanet = null;
    private $debug_mode = false;
    
    public function __construct() {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->log('Plugin wird initialisiert');
        
        try {
            $this->init();
        } catch (Exception $e) {
            $this->log('Fehler bei Initialisierung: ' . $e->getMessage(), 'error');
        } catch (Error $e) {
            $this->log('Fatal Error bei Initialisierung: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Plugin sicher initialisieren
     */
    private function init() {
        // Basis-Hooks immer registrieren
        add_action('init', array($this, 'register_hooks'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX-Handler
        add_action('wp_ajax_nova_ai_chat', array($this, 'handle_chat_request'));
        add_action('wp_ajax_nopriv_nova_ai_chat', array($this, 'handle_chat_request'));
        
        // Shortcodes
        add_shortcode('nova_ai_chat', array($this, 'chat_shortcode'));
        
        // Admin-Interface nur im Backend
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
        
        // Plugin-Hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Core-Klassen laden (optional)
        $this->load_core_classes();
        
        $this->log('Plugin erfolgreich initialisiert');
    }
    
    /**
     * Core-Klassen sicher laden
     */
    private function load_core_classes() {
        $include_files = array(
            'includes/class-nova-ai-core.php',
            'includes/class-nova-ai-providers.php',
            'includes/class-nova-ai-stable-diffusion.php',
            'includes/class-nova-ai-crawler.php',
            'includes/class-nova-ai-novanet.php'
        );
        
        foreach ($include_files as $file) {
            $file_path = NOVA_AI_PLUGIN_PATH . $file;
            
            if (file_exists($file_path)) {
                try {
                    require_once($file_path);
                    $this->log("Geladen: {$file}");
                } catch (Exception $e) {
                    $this->log("Fehler beim Laden von {$file}: " . $e->getMessage(), 'error');
                } catch (Error $e) {
                    $this->log("Fatal Error beim Laden von {$file}: " . $e->getMessage(), 'error');
                }
            } else {
                $this->log("Datei nicht gefunden: {$file}", 'warning');
            }
        }
        
        // Klassen initialisieren falls verfügbar
        $this->init_core_classes();
    }
    
    /**
     * Core-Klassen initialisieren
     */
    private function init_core_classes() {
        try {
            if (class_exists('NovaAIProviders')) {
                $this->providers = new NovaAIProviders();
                $this->log('NovaAIProviders initialisiert');
            }
            
            if (class_exists('NovaAIStableDiffusion')) {
                $this->stable_diffusion = new NovaAIStableDiffusion();
                $this->log('NovaAIStableDiffusion initialisiert');
            }
            
            if (class_exists('NovaAICrawler')) {
                $this->crawler = new NovaAICrawler();
                $this->log('NovaAICrawler initialisiert');
            }
            
            if (class_exists('NovaAINovaNet')) {
                $this->novanet = new NovaAINovaNet();
                $this->log('NovaAINovaNet initialisiert');
            }
        } catch (Exception $e) {
            $this->log('Fehler bei Klassen-Initialisierung: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * WordPress-Hooks registrieren
     */
    public function register_hooks() {
        // NovaNet-Hooks nur wenn verfügbar
        if ($this->novanet) {
            add_action('wp_ajax_novanet_sync', array($this->novanet, 'handle_sync_request'));
            add_action('wp_ajax_nopriv_novanet_sync', array($this->novanet, 'handle_sync_request'));
        }
    }
    
    /**
     * Assets laden
     */
    public function enqueue_assets() {
        global $post;
        
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'nova_ai_chat')) {
            return;
        }
        
        // CSS laden (mit Fallback)
        $css_file = 'assets/nova-ai-extended.css';
        if (!file_exists(NOVA_AI_PLUGIN_PATH . $css_file)) {
            $css_file = 'assets/chat-frontend.css';
        }
        
        if (file_exists(NOVA_AI_PLUGIN_PATH . $css_file)) {
            wp_enqueue_style(
                'nova-ai-css',
                NOVA_AI_PLUGIN_URL . $css_file,
                array(),
                NOVA_AI_VERSION
            );
        } else {
            // Inline CSS als Fallback
            wp_add_inline_style('wp-admin', $this->get_fallback_css());
        }
        
        // JavaScript laden (mit Fallback)
        $js_file = 'assets/nova-ai-extended.js';
        if (!file_exists(NOVA_AI_PLUGIN_PATH . $js_file)) {
            $js_file = 'assets/chat-frontend.js';
        }
        
        if (file_exists(NOVA_AI_PLUGIN_PATH . $js_file)) {
            wp_enqueue_script(
                'nova-ai-js',
                NOVA_AI_PLUGIN_URL . $js_file,
                array('jquery'),
                NOVA_AI_VERSION,
                true
            );
        } else {
            // jQuery für Fallback
            wp_enqueue_script('jquery');
        }
        
        // AJAX-Konfiguration
        wp_localize_script('jquery', 'nova_ai_config', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nova_ai_nonce'),
            'version' => NOVA_AI_VERSION,
            'providers' => $this->providers ? $this->get_safe_providers() : array(),
            'debug' => $this->debug_mode
        ));
    }
    
    /**
     * Chat-Shortcode mit Error-Handling
     */
    public function chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '500px',
            'placeholder' => 'Frage Nova etwas...',
            'theme' => 'cyberpunk'
        ), $atts);
        
        $chat_id = 'nova-ai-chat-' . wp_rand(1000, 9999);
        
        ob_start();
        ?>
        <div class="nova-ai-container nova-theme-<?php echo esc_attr($atts['theme']); ?>" id="<?php echo $chat_id; ?>-container">
            <div class="nova-ai-chatbox" style="min-height: <?php echo esc_attr($atts['height']); ?>; background: #1a1a1a; color: #00ff41; border-radius: 8px; overflow: hidden;">
                
                <div id="<?php echo $chat_id; ?>-messages" class="nova-ai-messages" style="padding: 20px; min-height: 300px; overflow-y: auto; font-family: monospace;">
                    <div class="nova-ai-msg ai">
                        <p><strong>🤖 Nova:</strong> Hallo! Ich bin Nova, deine KI-Admin für das AILinux-Projekt.</p>
                        <p><strong>Status:</strong> 
                        <?php if ($this->providers): ?>
                            ✅ Multi-Provider aktiv
                        <?php else: ?>
                            ⚡ Legacy-Modus (Ollama)
                        <?php endif; ?>
                        </p>
                        <p><strong>Version:</strong> <?php echo NOVA_AI_VERSION; ?></p>
                    </div>
                </div>
                
                <div class="nova-ai-input-area" style="background: #2a2a2a; padding: 15px; display: flex; gap: 10px;">
                    <textarea 
                        id="<?php echo $chat_id; ?>-input" 
                        class="nova-ai-input" 
                        placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                        style="flex: 1; background: #333; color: #00ff41; border: 1px solid #555; border-radius: 4px; padding: 10px; resize: none; min-height: 40px;"
                        rows="1"></textarea>
                    
                    <button 
                        type="button" 
                        class="nova-send-btn"
                        style="background: #00ff41; color: #000; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;"
                        onclick="novaSafeSendMessage('<?php echo $chat_id; ?>')">
                        Senden
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        // Sichere globale Funktion
        function novaSafeSendMessage(chatId) {
            var input = document.getElementById(chatId + '-input');
            var messages = document.getElementById(chatId + '-messages');
            
            if (!input || !messages) {
                console.error('Nova AI: Elemente nicht gefunden');
                return;
            }
            
            var message = input.value.trim();
            if (!message) {
                input.focus();
                return;
            }
            
            // User-Nachricht hinzufügen
            messages.innerHTML += '<div class="nova-ai-msg user"><p><strong>👤 Du:</strong> ' + 
                message.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</p></div>';
            input.value = '';
            
            // Loading-Nachricht
            var loadingId = 'loading-' + Date.now();
            messages.innerHTML += '<div id="' + loadingId + '" class="nova-ai-msg ai loading"><p><strong>🤖 Nova:</strong> ⌛ Denke nach...</p></div>';
            messages.scrollTop = messages.scrollHeight;
            
            // AJAX-Request
            jQuery.post(nova_ai_config.ajaxurl, {
                action: 'nova_ai_chat',
                prompt: message,
                nonce: nova_ai_config.nonce
            })
            .done(function(response) {
                console.log('Nova AI Response:', response);
                document.getElementById(loadingId).remove();
                
                if (response.success && response.data && response.data.answer) {
                    messages.innerHTML += '<div class="nova-ai-msg ai"><p><strong>🤖 Nova:</strong> ' + 
                        response.data.answer.replace(/\n/g, '<br>') + '</p></div>';
                } else {
                    var error = response.data && response.data.msg ? response.data.msg : 'Unbekannter Fehler';
                    messages.innerHTML += '<div class="nova-ai-msg ai error"><p><strong>❌ Nova:</strong> ' + error + '</p></div>';
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Nova AI AJAX Error:', xhr, status, error);
                document.getElementById(loadingId).remove();
                messages.innerHTML += '<div class="nova-ai-msg ai error"><p><strong>❌ Nova:</strong> Verbindungsfehler: ' + error + '</p></div>';
            })
            .always(function() {
                messages.scrollTop = messages.scrollHeight;
                input.focus();
            });
        }
        
        // Enter-Taste Event
        jQuery(document).ready(function($) {
            $('#<?php echo $chat_id; ?>-input').keydown(function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    novaSafeSendMessage('<?php echo $chat_id; ?>');
                }
                
                // Auto-resize
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 150) + 'px';
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Chat-Anfrage behandeln
     */
    public function handle_chat_request() {
        $this->log('AJAX Chat-Request empfangen');
        
        // Nonce-Prüfung
        if (!wp_verify_nonce($_POST['nonce'], 'nova_ai_nonce')) {
            $this->log('Nonce-Validierung fehlgeschlagen', 'error');
            wp_send_json_error(array('msg' => 'Sicherheitsfehler'));
            return;
        }
        
        $prompt = sanitize_textarea_field($_POST['prompt']);
        if (empty($prompt)) {
            wp_send_json_error(array('msg' => 'Leere Nachricht'));
            return;
        }
        
        $this->log("Processing prompt: " . substr($prompt, 0, 50) . '...');
        
        // Verwende Provider-System falls verfügbar
        if ($this->providers) {
            try {
                $result = $this->providers->send_request('', '', $prompt);
                
                if ($result['success']) {
                    wp_send_json_success(array(
                        'answer' => $result['message'],
                        'provider' => $result['provider'],
                        'model' => $result['model']
                    ));
                    return;
                }
            } catch (Exception $e) {
                $this->log('Provider-Fehler: ' . $e->getMessage(), 'error');
            }
        }
        
        // Fallback auf Legacy-System
        $result = $this->legacy_chat_request($prompt);
        
        if ($result['success']) {
            wp_send_json_success(array('answer' => $result['message']));
        } else {
            wp_send_json_error(array('msg' => $result['error']));
        }
    }
    
    /**
     * Legacy Chat-System
     */
    private function legacy_chat_request($prompt) {
        // .env laden
        $env = $this->load_env_config();
        $ollama_url = $env['OLLAMA_URL'];
        $ollama_model = $env['OLLAMA_MODEL'];
        $system_prompt = get_option('nova_ai_system_prompt', 'Du bist Nova, die KI-Admin für das AILinux-Projekt.');
        
        return $this->call_ollama($ollama_url, $ollama_model, $system_prompt, $prompt);
    }
    
    /**
     * .env-Konfiguration laden
     */
    private function load_env_config() {
        $defaults = array(
            'OLLAMA_URL' => 'http://127.0.0.1:11434/api/chat',
            'OLLAMA_MODEL' => 'mixtral'
        );
        
        $env_file = NOVA_AI_PLUGIN_PATH . '.env';
        if (!file_exists($env_file)) {
            return $defaults;
        }
        
        $env = $defaults;
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && $line[0] !== '#') {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value, '"\'');
            }
        }
        
        return $env;
    }
    
    /**
     * Ollama API-Aufruf
     */
    private function call_ollama($api_url, $model, $system_prompt, $user_prompt) {
        $data = array(
            'model' => $model,
            'messages' => array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $user_prompt)
            ),
            'stream' => false
        );
        
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($data)
        );
        
        $this->log("Ollama Request zu: {$api_url}");
        
        $response = wp_remote_post($api_url, $args);
        
        if (is_wp_error($response)) {
            $error = 'Verbindungsfehler: ' . $response->get_error_message();
            $this->log($error, 'error');
            return array('success' => false, 'error' => $error);
        }
        
        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status !== 200) {
            $error = "API-Fehler (HTTP {$status})";
            $this->log($error, 'error');
            return array('success' => false, 'error' => $error);
        }
        
        $json = json_decode($body, true);
        
        if (!$json || !isset($json['message']['content'])) {
            $error = 'Ungültige API-Antwort';
            $this->log($error . ': ' . $body, 'error');
            return array('success' => false, 'error' => $error);
        }
        
        $this->log('Ollama-Antwort erfolgreich erhalten');
        return array('success' => true, 'message' => $json['message']['content']);
    }
    
    /**
     * Admin-Menü
     */
    public function add_admin_menu() {
        add_menu_page(
            'Nova AI Brainpool',
            'Nova AI',
            'manage_options',
            'nova-ai-safe',
            array($this, 'admin_page'),
            'dashicons-admin-generic'
        );
    }
    
    /**
     * Admin-Seite
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>🤖 Nova AI Brainpool - Production Safe</h1>
            
            <div style="background: #1a1a1a; color: #00ff41; padding: 20px; border-radius: 8px; font-family: monospace; margin: 20px 0;">
                <h3>✅ Plugin-Status</h3>
                <table style="color: #00ff41;">
                    <tr><td><strong>Version:</strong></td><td><?php echo NOVA_AI_VERSION; ?></td></tr>
                    <tr><td><strong>WordPress:</strong></td><td><?php echo get_bloginfo('version'); ?></td></tr>
                    <tr><td><strong>PHP:</strong></td><td><?php echo PHP_VERSION; ?></td></tr>
                    <tr><td><strong>Debug-Modus:</strong></td><td><?php echo $this->debug_mode ? 'Aktiv' : 'Inaktiv'; ?></td></tr>
                </table>
                
                <h4>🔌 Komponenten-Status:</h4>
                <ul>
                    <li>Core-Providers: <?php echo $this->providers ? '✅ Geladen' : '⚠️ Legacy-Modus'; ?></li>
                    <li>Stable Diffusion: <?php echo $this->stable_diffusion ? '✅ Verfügbar' : '❌ Nicht verfügbar'; ?></li>
                    <li>Crawler: <?php echo $this->crawler ? '✅ Verfügbar' : '❌ Nicht verfügbar'; ?></li>
                    <li>NovaNet: <?php echo $this->novanet ? '✅ Verfügbar' : '❌ Nicht verfügbar'; ?></li>
                </ul>
                
                <h4>🧪 Test-Shortcode:</h4>
                <code>[nova_ai_chat]</code>
                
                <?php if ($this->debug_mode): ?>
                <h4>🔍 Debug-Log (letzte 10 Einträge):</h4>
                <div style="background: #000; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto;">
                    <?php echo $this->get_debug_log_preview(); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Plugin-Aktivierung
     */
    public function activate() {
        $this->log('Plugin wird aktiviert');
        
        // Standard-Optionen setzen
        add_option('nova_ai_system_prompt', 'Du bist Nova, die KI-Admin für das AILinux-Projekt.');
        add_option('nova_ai_activated_version', NOVA_AI_VERSION);
        add_option('nova_ai_activated_time', time());
        
        // .env-Datei erstellen falls nicht vorhanden
        $env_file = NOVA_AI_PLUGIN_PATH . '.env';
        if (!file_exists($env_file)) {
            $env_content = "# Nova AI Brainpool - Production Safe Configuration\n";
            $env_content .= "OLLAMA_URL=http://127.0.0.1:11434/api/chat\n";
            $env_content .= "OLLAMA_MODEL=mixtral\n";
            
            file_put_contents($env_file, $env_content);
            if (file_exists($env_file)) {
                chmod($env_file, 0600);
                $this->log('.env-Datei erstellt');
            }
        }
        
        $this->log('Plugin erfolgreich aktiviert');
    }
    
    /**
     * Plugin-Deaktivierung
     */
    public function deactivate() {
        $this->log('Plugin wird deaktiviert');
    }
    
    /**
     * Sichere Provider-Liste für Frontend
     */
    private function get_safe_providers() {
        if (!$this->providers) {
            return array();
        }
        
        try {
            return $this->providers->get_available_providers();
        } catch (Exception $e) {
            $this->log('Fehler beim Abrufen der Provider: ' . $e->getMessage(), 'error');
            return array();
        }
    }
    
    /**
     * Fallback CSS
     */
    private function get_fallback_css() {
        return '.nova-ai-container { background: #1a1a1a; color: #00ff41; border-radius: 8px; overflow: hidden; }
                .nova-ai-messages { padding: 20px; min-height: 300px; overflow-y: auto; font-family: monospace; }
                .nova-ai-input-area { background: #2a2a2a; padding: 15px; display: flex; gap: 10px; }
                .nova-ai-input { flex: 1; background: #333; color: #00ff41; border: 1px solid #555; border-radius: 4px; padding: 10px; }
                .nova-send-btn { background: #00ff41; color: #000; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }';
    }
    
    /**
     * Debug-Log-Vorschau
     */
    private function get_debug_log_preview() {
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (!file_exists($log_file)) {
            return 'Keine Debug-Logs verfügbar';
        }
        
        $lines = file($log_file);
        $nova_lines = array_filter($lines, function($line) {
            return strpos($line, 'Nova AI') !== false;
        });
        
        $recent_lines = array_slice($nova_lines, -10);
        return implode('<br>', array_map('esc_html', $recent_lines));
    }
    
    /**
     * Logging-Funktion
     */
    private function log($message, $level = 'info') {
        if ($this->debug_mode || $level === 'error') {
            error_log("Nova AI [{$level}]: {$message}");
        }
    }
}

// Plugin sicher starten
try {
    new NovaAIBrainpoolSafe();
} catch (Exception $e) {
    error_log('Nova AI: Kritischer Fehler beim Starten: ' . $e->getMessage());
} catch (Error $e) {
    error_log('Nova AI: Fatal Error beim Starten: ' . $e->getMessage());
}
?>
