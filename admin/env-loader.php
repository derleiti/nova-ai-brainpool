<?php
// Sicherheitsprüfung
if (!defined('ABSPATH')) {
    exit;
}

// === .env laden ===
$env_file = plugin_dir_path(__FILE__) . '../.env';

if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || !str_contains($line, '=')) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Setzen als Umgebungsvariable
        putenv("$name=$value");

        // Falls nicht definiert, als PHP-Konstante setzen
        if (!defined($name)) {
            define($name, $value);
        }
    }
} else {
    error_log("[Nova AI] ⚠️ .env Datei nicht gefunden unter: $env_file");
}
