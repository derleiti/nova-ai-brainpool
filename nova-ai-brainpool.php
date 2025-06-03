<?php
/*
Plugin Name: Nova AI Brainpool
Description: Open AI Chat & Knowledge Plugin mit modularen KI-Komponenten für WordPress.
Version: 2.0.2-safe
Author: Markus / AILinux & Nova
*/

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/includes/class-nova-ai-core.php';
require_once __DIR__ . '/includes/class-nova-ai-providers.php';
require_once __DIR__ . '/includes/class-nova-ai-stable-diffusion.php';
require_once __DIR__ . '/includes/class-nova-ai-crawler.php';
require_once __DIR__ . '/includes/class-nova-ai-novanet.php';

class NovaAI_Brainpool {
    public $modules = [];
    public $debug_log = [];

    public function __construct() {
        $this->load_modules();
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('nova_ai_chat', [$this, 'shortcode_chat']);
    }

    public function load_modules() {
        $this->modules['core'] = class_exists('NovaAI_Core');
        $this->modules['providers'] = class_exists('NovaAI_Providers');
        $this->modules['stable_diffusion'] = class_exists('NovaAI_StableDiffusion');
        $this->modules['crawler'] = class_exists('NovaAI_Crawler');
        $this->modules['novanet'] = class_exists('NovaAI_NovaNet');
    }

    public function admin_menu() {
        add_menu_page(
            'Nova AI Brainpool',
            'Nova AI',
            'manage_options',
            'nova-ai-safe',
            [$this, 'admin_page'],
            'dashicons-cloud',
            5
        );
    }

    public function admin_page() {
        ?>
        <div style="background: #181818; color: #d2ffd2; padding:2em; border-radius:10px;">
            <h2 style="margin-top:0;">🧠 Nova AI Brainpool - Production Safe</h2>
            <div style="margin-bottom:1.2em;">
                <strong>Plugin-Status:</strong><br>
                Version: <b>2.0.2-safe</b><br>
                WordPress: <b><?php echo esc_html(get_bloginfo('version')); ?></b><br>
                PHP: <b><?php echo esc_html(phpversion()); ?></b><br>
                Debug-Modus: <b>Aktiv</b>
            </div>
            <hr>
            <div style="margin-bottom:1.2em;">
                <strong>🧩 Komponenten-Status:</strong><br>
                Core-Provider: <?php echo $this->modules['core'] ? "✅ Geladen" : "❌ Fehler"; ?><br>
                Providers: <?php echo $this->modules['providers'] ? "✅ Verfügbar" : "❌ Nicht verfügbar"; ?><br>
                Stable Diffusion: <?php echo $this->modules['stable_diffusion'] ? "✅ Verfügbar" : "❌ Nicht verfügbar"; ?><br>
                Crawler: <?php echo $this->modules['crawler'] ? "✅ Verfügbar" : "❌ Nicht verfügbar"; ?><br>
                NovaNet-X: <?php echo $this->modules['novanet'] ? "✅ Verfügbar" : "❌ Nicht verfügbar"; ?><br>
            </div>
            <div style="margin-bottom:1.2em;">
                <strong>📝 Test-Shortcodes:</strong><br>
                <code>[nova_ai_chat]</code>
            </div>
            <div style="margin-bottom:1.2em;">
                <strong>🪲 Debug-Log (letzte 10 Einträge):</strong>
                <pre style="background:#111; color:#53ff53; padding:1em; max-height:180px; overflow:auto;"><?php
                $log = get_option('novaai_debug_log', []);
                if (!empty($log)) {
                    $lines = array_slice($log, -10);
                    foreach ($lines as $line) {
                        echo esc_html($line) . "\n";
                    }
                } else {
                    echo "No log entries found.";
                }
                ?></pre>
            </div>
            <small>Danke für dein Vertrauen in <a href="https://ailinux.me" style="color:#53ff53;">WordPress</a> | Nova AI Brainpool</small>
        </div>
        <?php
    }

    public function log($msg) {
        $log = get_option('novaai_debug_log', []);
        $log[] = date('[Y-m-d H:i:s] ') . $msg;
        if(count($log) > 100) { $log = array_slice($log, -100); }
        update_option('novaai_debug_log', $log);
    }

    public function shortcode_chat($atts = []) {
        return '<div id="nova-ai-chat">[Nova KI-Chat kommt hier hin]</div>';
    }
}

$GLOBALS['nova_ai_brainpool'] = new NovaAI_Brainpool();

?>
