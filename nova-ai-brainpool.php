<?php
/*
Plugin Name: Nova AI Brainpool
Description: Minimaler KI-Chat für WordPress mit Ollama-Backend (z.B. Zephyr, Mistral, LLaVA). Nutzt .env Datei im Plugin-Ordner. Shortcode: [nova_ai_chat]
Version: 1.1.0
Author: derleiti
*/

define('NOVA_AI_BP_PATH', plugin_dir_path(__FILE__));

// === ENV loader (Helfer) ===
function nova_ai_brainpool_env() {
    static $env = null;
    if ($env !== null) return $env;
    $env = [];
    $env_path = NOVA_AI_BP_PATH . '.env';
    if (file_exists($env_path)) {
        foreach (file($env_path) as $line) {
            if (preg_match('/^\s*([\w_]+)\s*=\s*(.*)$/', $line, $m))
                $env[$m[1]] = trim($m[2]);
        }
    }
    return $env;
}

// === Scripte und Styles laden ===
add_action('wp_enqueue_scripts', function() {
    wp_register_style('nova-ai-brainpool', plugins_url('assets/chat-frontend.css', __FILE__));
    wp_register_script('nova-ai-brainpool', plugins_url('assets/chat-frontend.js', __FILE__), [], false, true);
});

add_shortcode('nova_ai_chat', function() {
    wp_enqueue_style('nova-ai-brainpool');
    wp_enqueue_script('nova-ai-brainpool');
    ?>
    <div class="nova-ai-chat-container">
        <div class="nova-ai-chatbox" id="nova-ai-chatbox">
            <div class="nova-ai-chat-messages" id="nova-ai-messages"></div>
            <div class="nova-ai-chat-input-row">
                <textarea id="nova-ai-input" class="nova-ai-chat-input" rows="2" placeholder="Frag die Nova KI... (Shift+Enter = neue Zeile)"></textarea>
                <button id="nova-ai-send" class="nova-ai-chat-send-btn">Senden</button>
            </div>
        </div>
    </div>
    <script>
    window.nova_ai_chat_ajax = {
        ajaxurl: "<?php echo admin_url('admin-ajax.php'); ?>"
    };
    </script>
    <?php
});

// === AJAX Handler mit Knowledge Base Kontext ===
add_action('wp_ajax_nopriv_nova_ai_chat', 'nova_ai_brainpool_chat_ajax');
add_action('wp_ajax_nova_ai_chat', 'nova_ai_brainpool_chat_ajax');
function nova_ai_brainpool_chat_ajax() {
    $prompt = trim($_POST['prompt'] ?? '');
    if (!$prompt) {
        wp_send_json_success(['answer' => 'Bitte frage etwas.']);
    }

    $env = nova_ai_brainpool_env();
    $ollama_url = $env['OLLAMA_URL'] ?? 'http://127.0.0.1:11434/api/chat';
    $model = $env['OLLAMA_MODEL'] ?? 'zephyr';

    // ==== Knowledge Base laden ====
    $kb_file = dirname(__FILE__) . '/knowledge-base.json';
    $kb_context = '';
    if (file_exists($kb_file)) {
        $kb_json = file_get_contents($kb_file);
        $kb_arr = json_decode($kb_json, true);
        if (is_array($kb_arr)) {
            foreach ($kb_arr as $item) {
                // Flexibel: Frage & Antwort oder nur Texte
                if (isset($item['frage']) && isset($item['antwort'])) {
                    $kb_context .= "Frage: " . $item['frage'] . " Antwort: " . $item['antwort'] . "\n";
                } elseif (is_string($item)) {
                    $kb_context .= "- " . $item . "\n";
                } elseif (is_array($item)) {
                    $kb_context .= "- " . implode(' ', $item) . "\n";
                }
            }
        }
    }

    // ==== Prompt bauen ====
    $final_prompt = "Nutze dieses Wissen falls hilfreich:\n" .
        $kb_context .
        "\nUser: $prompt\nAntwort:";

    // ==== Ollama API Call ====
    $req = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => "Du bist ein hilfreicher Assistent mit Spezialwissen aus der Knowledge Base."],
            ['role' => 'user', 'content' => $final_prompt]
        ]
    ];
    $args = [
        'body' => json_encode($req),
        'headers' => [ 'Content-Type' => 'application/json' ],
        'timeout' => 28,
    ];

    $response = wp_remote_post($ollama_url, $args);
    $msg = '';
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        // Streaming-API: mehrere JSON-Zeilen – alle Content-Chunks zusammenfügen
        $lines = explode("\n", $body);
        foreach ($lines as $line) {
            $json = json_decode($line, true);
            if (isset($json['message']['content'])) {
                $msg .= $json['message']['content'];
            }
        }
        $msg = trim($msg);
    } else {
        $msg = "Fehler: " . $response->get_error_message();
    }

    if (!$msg) $msg = 'Keine Antwort erhalten.';
    wp_send_json_success(['answer' => $msg]);
}

// --- Admin Settings: .env Info & KB Link ---
add_action('admin_menu', function() {
    add_menu_page('Nova AI Brainpool', 'Nova AI Brainpool', 'manage_options', 'nova-ai-brainpool', function() {
        $env = nova_ai_brainpool_env();
        ?>
        <div class="wrap">
            <h2>Nova AI Brainpool – Einstellungen & Info</h2>
            <p>Shortcode: <code>[nova_ai_chat]</code></p>
            <pre style="background:#222;color:#b8e994;padding:12px;border-radius:6px;max-width:780px;">
.env gefunden!
OLLAMA_URL=<?php echo esc_html($env['OLLAMA_URL'] ?? ''); ?>

OLLAMA_MODEL=<?php echo esc_html($env['OLLAMA_MODEL'] ?? ''); ?>
            </pre>
            <a href="<?php echo plugins_url('../knowledge-base.json', __FILE__); ?>" download>Knowledge Base (Export/Import JSON)</a>
            <small>Fragen? <a href="mailto:admin@derleiti.de">admin@derleiti.de</a></small>
        </div>
        <?php
    }, 'dashicons-format-chat');
});
