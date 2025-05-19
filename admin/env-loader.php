<?php
// Einfache .env-Loader-Funktion
if (!function_exists('nova_ai_load_env_file')) {
    function nova_ai_load_env_file($envPath = null) {
        $envFile = $envPath ?: dirname(__DIR__) . '/.env';
        if (!file_exists($envFile)) {
            // Admin-Hinweis, wenn im Backend
            if (is_admin()) {
                add_action('admin_notices', function() use ($envFile) {
                    echo '<div class="notice notice-warning"><p><b>Nova AI Brainpool:</b> Die <code>.env</code>-Datei wurde nicht gefunden ('.$envFile.'). Bitte anlegen!</p></div>';
                });
            }
            return;
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
    nova_ai_load_env_file();
}
