<?php
/**
 * .env Datei Loader für Nova AI Brainpool
 * Lädt Umgebungsvariablen aus einer .env-Datei
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lädt .env-Datei und gibt die Variablen als Array zurück
 * 
 * @param string $path Pfad zur .env-Datei
 * @return array Assoziatives Array mit Umgebungsvariablen
 */
function nova_ai_brainpool_load_env($path) {
    $env = [];
    
    // Prüfe ob Datei existiert und lesbar ist
    if (!file_exists($path) || !is_readable($path)) {
        return $env;
    }
    
    try {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($lines === false) {
            return $env;
        }
        
        foreach ($lines as $line) {
            // Kommentare und leere Zeilen überspringen
            $line = trim($line);
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // Variable parsen: KEY=value
            if (preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2];
                
                // Anführungszeichen entfernen (falls vorhanden)
                if (preg_match('/^"(.*)"$/', $value, $quoted) || preg_match("/^'(.*)'$/", $value, $quoted)) {
                    $value = $quoted[1];
                }
                
                // Escape-Sequenzen verarbeiten
                $value = str_replace(['\\n', '\\r', '\\t', '\\"', "\\'"], ["\n", "\r", "\t", '"', "'"], $value);
                
                $env[$key] = $value;
            }
        }
    } catch (Exception $e) {
        // Bei Fehlern leeres Array zurückgeben
        error_log('Nova AI Brainpool: Fehler beim Laden der .env-Datei: ' . $e->getMessage());
        return [];
    }
    
    return $env;
}

/**
 * Speichert Umgebungsvariablen in eine .env-Datei
 * 
 * @param string $path Pfad zur .env-Datei
 * @param array $env Assoziatives Array mit Umgebungsvariablen
 * @return bool True bei Erfolg, false bei Fehler
 */
function nova_ai_brainpool_save_env($path, $env) {
    if (!is_array($env)) {
        return false;
    }
    
    $content = "# Nova AI Brainpool Konfiguration\n";
    $content .= "# Generiert am " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($env as $key => $value) {
        // Key validieren
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
            continue;
        }
        
        // Wert escapen falls nötig
        if (strpos($value, ' ') !== false || strpos($value, '"') !== false || strpos($value, "'") !== false) {
            $value = '"' . str_replace('"', '\\"', $value) . '"';
        }
        
        $content .= $key . '=' . $value . "\n";
    }
    
    try {
        // Datei schreiben mit korrekten Berechtigungen
        $result = file_put_contents($path, $content, LOCK_EX);
        
        if ($result !== false && file_exists($path)) {
            // Berechtigungen setzen (nur Owner lesen/schreiben)
            chmod($path, 0600);
            return true;
        }
    } catch (Exception $e) {
        error_log('Nova AI Brainpool: Fehler beim Speichern der .env-Datei: ' . $e->getMessage());
    }
    
    return false;
}

/**
 * Erstellt eine Standard-.env-Datei falls sie nicht existiert
 * 
 * @param string $path Pfad zur .env-Datei
 * @return bool True bei Erfolg, false bei Fehler
 */
function nova_ai_brainpool_create_default_env($path) {
    if (file_exists($path)) {
        return true; // Datei existiert bereits
    }
    
    $default_env = [
        'OLLAMA_URL' => 'http://127.0.0.1:11434/api/chat',
        'OLLAMA_MODEL' => 'zephyr'
    ];
    
    return nova_ai_brainpool_save_env($path, $default_env);
}

/**
 * Validiert .env-Werte für Nova AI Brainpool
 * 
 * @param array $env Umgebungsvariablen
 * @return array Array mit 'valid' (bool) und 'errors' (array)
 */
function nova_ai_brainpool_validate_env($env) {
    $errors = [];
    $valid = true;
    
    // OLLAMA_URL prüfen
    if (empty($env['OLLAMA_URL'])) {
        $errors[] = 'OLLAMA_URL ist erforderlich';
        $valid = false;
    } elseif (!filter_var($env['OLLAMA_URL'], FILTER_VALIDATE_URL)) {
        $errors[] = 'OLLAMA_URL muss eine gültige URL sein';
        $valid = false;
    }
    
    // OLLAMA_MODEL prüfen
    if (empty($env['OLLAMA_MODEL'])) {
        $errors[] = 'OLLAMA_MODEL ist erforderlich';
        $valid = false;
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $env['OLLAMA_MODEL'])) {
        $errors[] = 'OLLAMA_MODEL darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten';
        $valid = false;
    }
    
    return [
        'valid' => $valid,
        'errors' => $errors
    ];
}

/**
 * Lädt .env-Variablen und setzt sie als WordPress-Optionen
 * (Fallback falls .env nicht verfügbar ist)
 * 
 * @param string $path Pfad zur .env-Datei
 * @return array Geladene Konfiguration
 */
function nova_ai_brainpool_get_config($path) {
    // Versuche .env zu laden
    $env = nova_ai_brainpool_load_env($path);
    
    // Falls .env nicht verfügbar, nutze WordPress-Optionen als Fallback
    $config = [
        'OLLAMA_URL' => $env['OLLAMA_URL'] ?? get_option('nova_ai_ollama_url', 'http://127.0.0.1:11434/api/chat'),
        'OLLAMA_MODEL' => $env['OLLAMA_MODEL'] ?? get_option('nova_ai_ollama_model', 'zephyr')
    ];
    
    return $config;
}
?>
