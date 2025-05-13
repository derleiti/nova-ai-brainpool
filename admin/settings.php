
<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_menu_page('Nova AI Settings', 'Nova AI', 'manage_options', 'nova-ai-brainpool', 'nova_ai_admin_page');
});

function nova_ai_admin_page() {
    if (isset($_POST['nova_ai_save'])) {
        update_option('nova_ai_crawl_urls', sanitize_textarea_field($_POST['nova_ai_crawl_urls']));
        echo '<div class="updated"><p>Gespeichert.</p></div>';
    }
    if (isset($_POST['nova_ai_crawl'])) {
        include_once plugin_dir_path(__FILE__) . '../includes/crawler.php';
        $result = nova_ai_run_crawler();
        echo '<div class="updated"><p>Crawl abgeschlossen. Datei: ' . esc_html($result) . '</p></div>';
    }
    $targets = get_option('nova_ai_crawl_urls', '');
    ?>
    <div class="wrap">
        <h1>Nova AI Einstellungen</h1>
        <form method="post">
            <h3>Crawler-Ziele (eine URL pro Zeile)</h3>
            <textarea name="nova_ai_crawl_urls" rows="10" cols="80"><?php echo esc_textarea($targets); ?></textarea>
            <p>
                <button type="submit" name="nova_ai_save" class="button button-primary">Speichern</button>
                <button type="submit" name="nova_ai_crawl" class="button">Crawler starten</button>
            </p>
        </form>
    </div>
    <?php
}
?>
