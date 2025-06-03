<?php
/**
 * Nova AI Brainpool - Web Crawler
 * Crawlt Webseiten für die Wissensdatenbank
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

require_once(NOVA_AI_PLUGIN_PATH . 'includes/class-nova-ai-core.php');

class NovaAICrawler extends NovaAICore {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Wissensdatenbank durchsuchen
     */
    public function search_knowledge_base($query, $limit = 10) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_knowledge';
        
        // Prüfe ob Tabelle existiert
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return [];
        }
        
        $search_query = $wpdb->prepare(
            "SELECT * FROM $table 
             WHERE status = 'active' 
             AND (title LIKE %s OR content LIKE %s OR excerpt LIKE %s)
             ORDER BY 
               CASE 
                 WHEN title LIKE %s THEN 1
                 WHEN excerpt LIKE %s THEN 2
                 ELSE 3
               END,
               crawled_at DESC
             LIMIT %d",
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            '%' . $wpdb->esc_like($query) . '%',
            $limit
        );
        
        return $wpdb->get_results($search_query, ARRAY_A);
    }
    
    /**
     * Wissensdatenbank-Kategorien abrufen
     */
    public function get_knowledge_categories() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_knowledge';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return [];
        }
        
        $categories = $wpdb->get_col(
            "SELECT DISTINCT category FROM $table 
             WHERE category IS NOT NULL AND category != '' 
             AND status = 'active' 
             ORDER BY category"
        );
        
        return $categories;
    }
    
    /**
     * Anzahl Wissensdatenbank-Einträge
     */
    public function get_knowledge_count() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_knowledge';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return 0;
        }
        
        return $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE status = 'active'"
        );
    }
    
    /**
     * Crawling durchführen (Basis-Implementierung)
     */
    public function run_crawl() {
        $urls = get_option('nova_ai_crawler_urls', '');
        
        if (empty($urls)) {
            $this->log('No URLs configured for crawling');
            return false;
        }
        
        $url_list = array_filter(array_map('trim', explode("\n", $urls)));
        
        foreach ($url_list as $url) {
            $this->crawl_url($url);
        }
        
        update_option('nova_ai_crawler_last_run', time());
        
        return true;
    }
    
    /**
     * Einzelne URL crawlen
     */
    private function crawl_url($url) {
        $this->log("Crawling URL: {$url}");
        
        $response = $this->http_request($url, [
            'timeout' => 30
        ]);
        
        if (!$response['success']) {
            $this->log("Failed to crawl {$url}: " . $response['error'], 'error');
            return false;
        }
        
        $content = $response['body'];
        
        // Basis-HTML-Parsing
        $title = $this->extract_title($content);
        $text_content = $this->extract_text_content($content);
        $excerpt = $this->create_excerpt($text_content);
        
        // In Datenbank speichern
        $this->save_crawled_content($url, $title, $text_content, $excerpt);
        
        return true;
    }
    
    /**
     * Titel aus HTML extrahieren
     */
    private function extract_title($html) {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return trim(html_entity_decode(strip_tags($matches[1])));
        }
        
        return 'Unbekannter Titel';
    }
    
    /**
     * Textinhalt aus HTML extrahieren
     */
    private function extract_text_content($html) {
        // Entferne Scripts und Styles
        $html = preg_replace('/<script[^>]*?>.*?<\/script>/si', '', $html);
        $html = preg_replace('/<style[^>]*?>.*?<\/style>/si', '', $html);
        
        // Konvertiere zu Text
        $text = strip_tags($html);
        
        // Normalisiere Whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Excerpt erstellen
     */
    private function create_excerpt($content, $length = 200) {
        if (strlen($content) <= $length) {
            return $content;
        }
        
        return substr($content, 0, $length) . '...';
    }
    
    /**
     * Gecrawlten Inhalt speichern
     */
    private function save_crawled_content($url, $title, $content, $excerpt) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_knowledge';
        
        // Prüfe ob URL bereits existiert
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE url = %s",
            $url
        ));
        
        $data = [
            'url' => $url,
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'category' => $this->determine_category($url, $title, $content),
            'updated_at' => current_time('mysql')
        ];
        
        if ($existing) {
            // Update
            $wpdb->update($table, $data, ['id' => $existing->id]);
            $this->log("Updated existing content for {$url}");
        } else {
            // Insert
            $data['crawled_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
            $this->log("Saved new content for {$url}");
        }
    }
    
    /**
     * Kategorie basierend auf URL/Inhalt bestimmen
     */
    private function determine_category($url, $title, $content) {
        // Einfache Kategorisierung basierend auf URL-Pattern
        if (strpos($url, 'github.com') !== false) {
            return 'GitHub';
        } elseif (strpos($url, 'stackoverflow.com') !== false) {
            return 'StackOverflow';
        } elseif (strpos($url, 'reddit.com') !== false) {
            return 'Reddit';
        } elseif (strpos($url, 'medium.com') !== false) {
            return 'Medium';
        } else {
            return 'Allgemein';
        }
    }
}
?>
