<?php
// Nova AI Brainpool Admin-Settings

// Simple: Hole bestehende Crawler-URLs
$crawler_urls = get_option('nova_ai_crawler_urls', []);
if (!is_array($crawler_urls)) $crawler_urls = [];

// Hole Trainingsdaten (als Text oder JSON)
$training_data = get_option('nova_ai_training_data', '');

// Handle POST (Save URLs, Save Data, Crawl, Export, Import)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options')) {
    if (isset($_POST['crawler_urls'])) {
        $urls = array_filter(array_map('trim', explode("\n", $_POST['crawler_urls'])));
        update_option('nova_ai_crawler_urls', $urls);
        $crawler_urls = $urls;
    }
    if (isset($_POST['training_data'])) {
        update_option('nova_ai_training_data', $_POST['training_data']);
        $training_data = $_POST['training_data'];
    }
    if (isset($_POST['crawl_now'])) {
        // Basic: Hole Text von jeder Ziel-URL (simple, nicht JS!)
        $fetched = [];
        foreach ($crawler_urls as $url) {
            $content = wp_remote_retrieve_body(wp_remote_get($url));
            $clean = wp_strip_all_tags($content);
            $fetched[] = [
                'url' => $url,
                'content' => mb_substr($clean, 0, 10000, 'UTF-8') // 10k Zeichen Max
            ];
        }
        // Trainingsdaten erweitern
        $all = [];
        if ($training_data) {
            $all = json_decode($training_data, true);
            if (!is_array($all)) $all = [];
        }
        foreach ($fetched as $site) {
            $all[] = $site;
        }
        $training_data = json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        update_option('nova_ai_training_data', $training_data);
    }
    if (isset($_POST['export_training'])) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="nova_ai_training_data.json"');
        echo $training_data;
        exit;
    }
    if (isset($_POST['import_training']) && isset($_FILES['training_file'])) {
        $import = file_get_contents($_FILES['training_file']['tmp_name']);
        update_option('nova_ai_training_data', $import);
        $training_data = $import;
    }
}

// Show Settings HTML
?>
<div class="wrap" style="max-width:900px;">
    <h1>Nova AI Brainpool – Einstellungen &amp; Trainingsdaten</h1>
    <form method="post" enctype="multipart/form-data">
        <h2>Webseiten für Crawler</h2>
        <p>Gib eine oder mehrere URLs (eine pro Zeile) an, die für den Wissensimport gecrawlt werden sollen.</p>
        <textarea name="crawler_urls" rows="6" style="width:100%;font-family:monospace;"><?php echo esc_textarea(implode("\n", $crawler_urls)); ?></textarea>
        <br>
        <button type="submit" class="button button-primary" name="save_urls">Speichern</button>
        <button type="submit" class="button" name="crawl_now">Jetzt Crawlen &amp; importieren</button>

        <hr>
        <h2>Trainingsdaten (Knowledge Base)</h2>
        <p>Hier kannst du die gesammelten Trainingsdaten einsehen, bearbeiten, exportieren oder importieren.</p>
        <textarea name="training_data" rows="12" style="width:100%;font-family:monospace;"><?php echo esc_textarea($training_data); ?></textarea>
        <br>
        <button type="submit" class="button" name="save_data">Trainingsdaten speichern</button>
        <button type="submit" class="button" name="export_training">Exportieren (JSON)</button>
        <input type="file" name="training_file" accept="application/json">
        <button type="submit" class="button" name="import_training">Importieren (JSON)</button>
    </form>
    <p><small>Fragen, Ideen? <a href="mailto:admin@derleiti.de">admin@derleiti.de</a></small></p>
</div>
