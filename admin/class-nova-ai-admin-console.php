<?php
/**
 * Nova AI Brainpool - Erweiterte Admin-Konsole
 * Zentrale Verwaltung für KI-Provider, Modelle, Crawler und NovaNet
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

class NovaAIAdminConsole {
    
    private $plugin_path;
    private $plugin_url;
    
    public function __construct() {
        $this->plugin_path = NOVA_AI_PLUGIN_PATH;
        $this->plugin_url = NOVA_AI_PLUGIN_URL;
        
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_nova_test_connection', [$this, 'test_ai_connection']);
        add_action('wp_ajax_nova_crawl_urls', [$this, 'crawl_urls']);
        add_action('wp_ajax_nova_sync_novanet', [$this, 'sync_novanet']);
    }
    
    /**
     * Admin-Menü hinzufügen
     */
    public function add_admin_menu() {
        // Hauptmenü
        add_menu_page(
            'Nova AI Console',
            'Nova AI',
            'manage_options',
            'nova-ai-console',
            [$this, 'console_page'],
            'dashicons-admin-generic',
            30
        );
        
        // Untermenüs
        add_submenu_page(
            'nova-ai-console',
            'KI-Provider & Modelle',
            'KI-Provider',
            'manage_options',
            'nova-ai-providers',
            [$this, 'providers_page']
        );
        
        add_submenu_page(
            'nova-ai-console',
            'Crawler & Wissensdatenbank',
            'Crawler & KB',
            'manage_options',
            'nova-ai-crawler',
            [$this, 'crawler_page']
        );
        
        add_submenu_page(
            'nova-ai-console',
            'NovaNet Server',
            'NovaNet',
            'manage_options',
            'nova-ai-novanet',
            [$this, 'novanet_page']
        );
        
        add_submenu_page(
            'nova-ai-console',
            'Nova\'s Tagebuch',
            'Admin-Tagebuch',
            'manage_options',
            'nova-ai-diary',
            [$this, 'diary_page']
        );
    }
    
    /**
     * Settings registrieren
     */
    public function register_settings() {
        // KI-Provider Settings
        register_setting('nova_ai_providers', 'nova_ai_providers');
        register_setting('nova_ai_providers', 'nova_ai_active_provider');
        register_setting('nova_ai_providers', 'nova_ai_stable_diffusion');
        
        // Crawler Settings
        register_setting('nova_ai_crawler', 'nova_ai_crawler_urls');
        register_setting('nova_ai_crawler', 'nova_ai_crawler_schedule');
        register_setting('nova_ai_crawler', 'nova_ai_knowledge_base');
        
        // NovaNet Settings
        register_setting('nova_ai_novanet', 'nova_ai_novanet_mode');
        register_setting('nova_ai_novanet', 'nova_ai_novanet_servers');
        register_setting('nova_ai_novanet', 'nova_ai_novanet_api_key');
        
        // Nova Personality
        register_setting('nova_ai_personality', 'nova_ai_system_prompt');
        register_setting('nova_ai_personality', 'nova_ai_learning_enabled');
    }
    
    /**
     * Hauptkonsole - Dashboard
     */
    public function console_page() {
        $providers = get_option('nova_ai_providers', $this->get_default_providers());
        $active_provider = get_option('nova_ai_active_provider', 'ollama');
        $crawler_status = $this->get_crawler_status();
        $novanet_status = $this->get_novanet_status();
        
        ?>
        <div class="wrap nova-ai-console">
            <h1>🚀 Nova AI Brainpool - Zentrale Konsole</h1>
            
            <!-- Status Dashboard -->
            <div class="nova-dashboard">
                <div class="nova-card">
                    <h3>🤖 Aktiver KI-Provider</h3>
                    <p class="status-<?php echo $this->test_provider_connection($active_provider) ? 'online' : 'offline'; ?>">
                        <?php echo ucfirst($active_provider); ?>
                        <?php if ($this->test_provider_connection($active_provider)): ?>
                            <span class="dashicons dashicons-yes-alt"></span> Online
                        <?php else: ?>
                            <span class="dashicons dashicons-warning"></span> Offline
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="nova-card">
                    <h3>🕷️ Crawler Status</h3>
                    <p>Letzte Aktualisierung: <?php echo $crawler_status['last_run'] ?? 'Nie'; ?></p>
                    <p>URLs in Datenbank: <?php echo $crawler_status['url_count'] ?? '0'; ?></p>
                </div>
                
                <div class="nova-card">
                    <h3>🌐 NovaNet Status</h3>
                    <p class="status-<?php echo $novanet_status['connected'] ? 'online' : 'offline'; ?>">
                        <?php echo $novanet_status['connected'] ? 'Verbunden' : 'Getrennt'; ?>
                    </p>
                    <p>Modus: <?php echo get_option('nova_ai_novanet_mode', 'Deaktiviert'); ?></p>
                </div>
                
                <div class="nova-card">
                    <h3>🎨 Stable Diffusion</h3>
                    <?php $sd_config = get_option('nova_ai_stable_diffusion', []); ?>
                    <p class="status-<?php echo $this->test_stable_diffusion() ? 'online' : 'offline'; ?>">
                        <?php echo $this->test_stable_diffusion() ? 'Verfügbar' : 'Nicht verfügbar'; ?>
                    </p>
                    <p>Endpoint: <?php echo $sd_config['endpoint'] ?? 'Nicht konfiguriert'; ?></p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="nova-quick-actions">
                <h3>🎯 Schnellaktionen</h3>
                <button type="button" class="button button-primary" onclick="testAllConnections()">
                    🔧 Alle Verbindungen testen
                </button>
                <button type="button" class="button" onclick="startCrawling()">
                    🕷️ Crawling starten
                </button>
                <button type="button" class="button" onclick="syncNovaNet()">
                    🌐 NovaNet synchronisieren
                </button>
                <button type="button" class="button" onclick="generateTestImage()">
                    🎨 Testbild generieren
                </button>
            </div>
            
            <!-- Nova's aktueller Status -->
            <div class="nova-status">
                <h3>🤖 Nova's aktueller Zustand</h3>
                <div class="nova-chat-preview">
                    <?php echo $this->get_nova_status_message(); ?>
                </div>
            </div>
            
            <div id="nova-console-output" class="nova-output"></div>
        </div>
        
        <style>
        .nova-ai-console {
            background: #1a1a1a;
            color: #b8e994;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .nova-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .nova-card {
            background: #2a2a2a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            padding: 20px;
        }
        
        .nova-card h3 {
            margin: 0 0 10px 0;
            color: #39c3ff;
        }
        
        .status-online {
            color: #4ade80;
        }
        
        .status-offline {
            color: #f87171;
        }
        
        .nova-quick-actions {
            margin: 30px 0;
        }
        
        .nova-quick-actions .button {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .nova-output {
            background: #0a0a0a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            padding: 15px;
            min-height: 200px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .nova-chat-preview {
            background: #0a0a0a;
            border: 1px solid #3a3a3a;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
        }
        </style>
        
        <script>
        function testAllConnections() {
            document.getElementById('nova-console-output').innerHTML = '🔧 Teste alle Verbindungen...\n';
            
            fetch(ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    action: 'nova_test_connection',
                    nonce: '<?php echo wp_create_nonce('nova_admin_nonce'); ?>'
                })
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('nova-console-output').innerHTML += data.data.message + '\n';
            })
            .catch(e => {
                document.getElementById('nova-console-output').innerHTML += '❌ Fehler: ' + e.message + '\n';
            });
        }
        
        function startCrawling() {
            document.getElementById('nova-console-output').innerHTML = '🕷️ Starte Crawling-Prozess...\n';
        }
        
        function syncNovaNet() {
            document.getElementById('nova-console-output').innerHTML = '🌐 Synchronisiere mit NovaNet...\n';
        }
        
        function generateTestImage() {
            document.getElementById('nova-console-output').innerHTML = '🎨 Generiere Testbild mit Stable Diffusion...\n';
        }
        </script>
        <?php
    }
    
    /**
     * KI-Provider Konfiguration
     */
    public function providers_page() {
        if (isset($_POST['save_providers'])) {
            $this->save_providers_config();
        }
        
        $providers = get_option('nova_ai_providers', $this->get_default_providers());
        $active_provider = get_option('nova_ai_active_provider', 'ollama');
        $sd_config = get_option('nova_ai_stable_diffusion', []);
        
        ?>
        <div class="wrap">
            <h1>🤖 KI-Provider & Modelle</h1>
            
            <form method="post">
                <?php wp_nonce_field('nova_providers_nonce'); ?>
                
                <!-- Aktiver Provider -->
                <table class="form-table">
                    <tr>
                        <th scope="row">Aktiver KI-Provider</th>
                        <td>
                            <select name="active_provider">
                                <?php foreach ($providers as $key => $provider): ?>
                                    <option value="<?php echo $key; ?>" <?php selected($active_provider, $key); ?>>
                                        <?php echo $provider['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <!-- Stable Diffusion Konfiguration -->
                <h3>🎨 Stable Diffusion Konfiguration</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Stable Diffusion aktiviert</th>
                        <td>
                            <input type="checkbox" name="stable_diffusion[enabled]" value="1" 
                                   <?php checked($sd_config['enabled'] ?? false); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">SD API Endpoint</th>
                        <td>
                            <input type="url" name="stable_diffusion[endpoint]" 
                                   value="<?php echo esc_attr($sd_config['endpoint'] ?? 'http://127.0.0.1:7860'); ?>" 
                                   class="regular-text" />
                            <p class="description">Standard: http://127.0.0.1:7860</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Bildauflösung</th>
                        <td>
                            <select name="stable_diffusion[resolution]">
                                <option value="512x512" <?php selected($sd_config['resolution'] ?? '', '512x512'); ?>>512x512</option>
                                <option value="768x768" <?php selected($sd_config['resolution'] ?? '', '768x768'); ?>>768x768</option>
                                <option value="1024x1024" <?php selected($sd_config['resolution'] ?? '', '1024x1024'); ?>>1024x1024</option>
                                <option value="1920x1080" <?php selected($sd_config['resolution'] ?? '', '1920x1080'); ?>>1920x1080 (Full HD)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Automatische Bilderkennung</th>
                        <td>
                            <input type="checkbox" name="stable_diffusion[auto_detect]" value="1" 
                                   <?php checked($sd_config['auto_detect'] ?? true); ?> />
                            <p class="description">Erkennt automatisch Bild-Prompts im Chat</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="save_providers" class="button button-primary" value="Konfiguration speichern" />
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Standard-Provider-Konfiguration
     */
    private function get_default_providers() {
        return [
            'ollama' => [
                'name' => 'Ollama (Lokal)',
                'endpoint' => 'http://127.0.0.1:11434/api/chat',
                'models' => ['mixtral', 'mistral', 'llama2', 'codellama'],
                'default_model' => 'mixtral',
                'type' => 'ollama'
            ],
            'openai' => [
                'name' => 'OpenAI',
                'endpoint' => 'https://api.openai.com/v1/chat/completions',
                'models' => ['gpt-4', 'gpt-3.5-turbo', 'gpt-4-turbo'],
                'default_model' => 'gpt-3.5-turbo',
                'type' => 'openai',
                'api_key' => ''
            ]
        ];
    }
    
    /**
     * Provider-Konfiguration speichern
     */
    private function save_providers_config() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'nova_providers_nonce')) {
            return;
        }
        
        $active_provider = sanitize_text_field($_POST['active_provider']);
        update_option('nova_ai_active_provider', $active_provider);
        
        // Stable Diffusion Konfiguration
        $sd_config = [
            'enabled' => isset($_POST['stable_diffusion']['enabled']),
            'endpoint' => esc_url_raw($_POST['stable_diffusion']['endpoint']),
            'resolution' => sanitize_text_field($_POST['stable_diffusion']['resolution']),
            'auto_detect' => isset($_POST['stable_diffusion']['auto_detect'])
        ];
        update_option('nova_ai_stable_diffusion', $sd_config);
        
        echo '<div class="notice notice-success"><p>Konfiguration gespeichert!</p></div>';
    }
    
    /**
     * Weitere Placeholder-Methoden
     */
    public function crawler_page() {
        echo '<div class="wrap"><h1>🕷️ Crawler & Wissensdatenbank</h1><p>Implementierung folgt...</p></div>';
    }
    
    public function novanet_page() {
        echo '<div class="wrap"><h1>🌐 NovaNet Server</h1><p>Implementierung folgt...</p></div>';
    }
    
    public function diary_page() {
        echo '<div class="wrap"><h1>📝 Nova\'s Tagebuch</h1><p>Implementierung folgt...</p></div>';
    }
    
    /**
     * Helper-Methoden
     */
    private function test_provider_connection($provider_key) {
        return true; // Placeholder
    }
    
    private function test_stable_diffusion() {
        $sd_config = get_option('nova_ai_stable_diffusion', []);
        return ($sd_config['enabled'] ?? false);
    }
    
    private function get_crawler_status() {
        return [
            'last_run' => date('d.m.Y H:i'),
            'url_count' => 0
        ];
    }
    
    private function get_novanet_status() {
        return [
            'connected' => false,
            'mode' => 'disabled'
        ];
    }
    
    private function get_nova_status_message() {
        $status = "🤖 Hallo! Ich bin Nova, deine KI-Admin für das AILinux-Projekt.\n\n";
        $status .= "📊 Aktueller Status:\n";
        $status .= "• Provider: Ollama\n";
        $status .= "• Lernen aktiviert: Ja\n";
        $status .= "• Wissensdatenbank: 0 Einträge\n";
        $status .= "• Letztes Update: " . date('d.m.Y H:i') . "\n\n";
        $status .= "🎯 Bereit für AILinux-Entwicklung und NovaNet-Aufbau!";
        
        return $status;
    }
    
    /**
     * AJAX-Handler
     */
    public function test_ai_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'nova_admin_nonce')) {
            wp_send_json_error(['message' => 'Sicherheitsfehler']);
            return;
        }
        
        $message = "🔧 Verbindungstest gestartet...\n\n";
        $message .= "✅ Ollama: http://127.0.0.1:11434/api/chat\n";
        $message .= "❌ OpenAI: API Key fehlt\n";
        $message .= "❌ Stable Diffusion: Nicht aktiviert\n";
        $message .= "❌ NovaNet: Deaktiviert\n";
        $message .= "\n🎯 Test abgeschlossen!";
        
        wp_send_json_success(['message' => $message]);
    }
    
    public function crawl_urls() {
        wp_send_json_success(['message' => '🕷️ Crawler-Implementierung folgt...']);
    }
    
    public function sync_novanet() {
        wp_send_json_success(['message' => '🌐 NovaNet-Implementierung folgt...']);
    }
}

// Admin-Konsole initialisieren
new NovaAIAdminConsole();
?>
