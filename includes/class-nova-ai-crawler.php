<?php
// includes/class-nova-ai-crawler.php

class NovaAI_Crawler {
    public function __construct() {
        // Hier könnten Einstellungen oder Cronjobs geladen werden
    }

    public function crawl_url($url) {
        // Dummy: Holt den Inhalt einer URL und gibt den Text zurück (Demo)
        $html = @file_get_contents($url);
        if ($html === false) return false;
        $text = strip_tags($html);
        return mb_substr($text, 0, 5000); // Limitiertes Beispiel
    }

    public function save_training_data($data) {
        // Dummy-Logik für Trainingsdaten speichern
        update_option('novaai_crawler_training', $data);
        return true;
    }
}
?>
