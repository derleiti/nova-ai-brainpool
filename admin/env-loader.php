<?php
// Lädt .env als Array
function nova_ai_brainpool_load_env($path) {
    $env = [];
    if (!file_exists($path)) return $env;
    foreach (file($path) as $line) {
        if (preg_match('/^\s*([\w_]+)\s*=\s*(.*)$/', $line, $m))
            $env[$m[1]] = trim($m[2]);
    }
    return $env;
}
