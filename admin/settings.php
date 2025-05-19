<?php
// admin/settings.php – Nova AI Brainpool

if (!current_user_can('manage_options')) {
    wp_die('Keine Berechtigung!');
}

$env = [];
$env_path = dirname(__DIR__) . '/.env';
if (file_exists($env_path)) {
    foreach (file($env_path) as $line) {
        if (preg_match('/^\s*([\w_]+)\s*=\s*(.*)$/', $line, $m))
            $env[$m[1]] = trim($m[2]);
    }
}

// Speicherpfade
$crawler_file = dirname(__DIR__) . '/crawler-urls.txt';
$kb_file = dirname(__DIR__) . '/knowledge-base.json';

// Crawler-URLs speichern
if (isset($_POST['crawl_submit']) && current_user_can('manage_options')) {
    $urls = trim(str_replace("\r", '', $_POST['crawler_urls'] ?? ''));
    file_put_contents($crawler_file, $urls);
    $crawler_msg = "Crawler-URLs gespeichert.";
}
// JSON importieren
if (isset($_POST['json_import']) && current_user_can('manage_options') && !empty($_FILES['json_upload']['tmp_name'])) {
    $json = file_get_contents($_FILES['json_upload']['tmp_name']);
    json_decode($json); // Validate
    if (json_last_error() === JSON_ERROR_NONE) {
        file_put_contents($kb_file, $json);
        $kb_msg = "Trainingsdaten importiert.";
    } else {
        $kb_msg = "Fehler: Ungültige JSON-Datei!";
    }
}
// JSON als Textfeld speichern
if (isset($_POST['json_text_save']) && current_user_can('manage_options')) {
    $json = trim($_POST['kb_json'] ?? '');
    json_decode($json); // Validate
    if ($json && json_last_error() === JSON_ERROR_NONE) {
        file_put_contents($kb_file, $json);
        $kb_msg = "Trainingsdaten gespeichert.";
    } else {
        $kb_msg = "Fehler: Ungültiges JSON!";
    }
}
// JSON exportieren
$kb_json = file_exists($kb_file) ? file_get_contents($kb_file) : '';
$crawler_urls = file_exists($crawler_file) ? file_get_contents($crawler_file) : '';
?>

<div class="wrap">
    <h2>Nova AI Brainpool – Einstellungen & Trainingsdaten</h2>
    <p>
        Shortcode für Chat: <code>[nova_ai_chat]</code>
        <br>
        <strong>.env Status:</strong>
        <?php echo file_exists($env_path) ? '<span style="color:#5fa;">Gefunden!</span>' : '<span style="color:#f44;">Fehlt!</span>'; ?>
    </p>
    <pre style="background:#23272c;color:#b8e994;padding:10px;border-radius:6px;max-width:640px;">
OLLAMA_URL=<?php echo esc_html($env['OLLAMA_URL'] ?? ''); ?>

OLLAMA_MODEL=<?php echo esc_html($env['OLLAMA_MODEL'] ?? ''); ?>
    </pre>

    <!-- Crawler URLs -->
    <h3>Crawler-URLs (jede Zeile eine Seite)</h3>
    <?php if (!empty($crawler_msg)) echo "<p style='color:green;'>$crawler_msg</p>"; ?>
    <form method="post">
        <textarea style="width:100%;max-width:640px;" rows="3" name="crawler_urls" placeholder="https://deine-seite.de/page"><?php echo esc_textarea($crawler_urls); ?></textarea><br>
        <button type="submit" name="crawl_submit">Speichern</button>
    </form>

    <!-- Knowledge Base Import/Export -->
    <h3>Trainingsdaten (Knowledge Base)</h3>
    <?php if (!empty($kb_msg)) echo "<p style='color:green;'>$kb_msg</p>"; ?>
    <form method="post" enctype="multipart/form-data" style="margin-bottom:18px;">
        <input type="file" name="json_upload" accept=".json">
        <button type="submit" name="json_import">Importieren (JSON-Datei)</button>
    </form>
    <form method="post">
        <textarea name="kb_json" rows="7" style="width:100%;max-width:640px;" placeholder="{ &quot;beispiel&quot;: &quot;dein Wissen&quot; }"><?php echo esc_textarea($kb_json); ?></textarea><br>
        <button type="submit" name="json_text_save">Speichern</button>
        <a href="<?php echo plugins_url('../knowledge-base.json', __FILE__); ?>" download style="margin-left:16px;">Export als JSON</a>
    </form>

    <p style="font-size:0.9em;color:#666;margin-top:24px;">
        Feedback &amp; Fragen: <a href="mailto:admin@derleiti.de">admin@derleiti.de</a>
    </p>
</div>
