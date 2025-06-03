<?php
/**
 * Nova AI Crawler Class
 * 
 * Handles web crawling, auto-crawling, and content extraction
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nova_AI_Crawler {
    
    private $max_depth;
    private $delay;
    private $user_agent;
    private $crawl_sites;
    private $auto_crawl_enabled;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->max_depth = intval(get_option('nova_ai_max_crawl_depth', 3));
        $this->delay = intval(get_option('nova_ai_crawl_delay', 1000)); // milliseconds
        $this->user_agent = 'Nova AI Crawler/1.0 (+https://ailinux.me)';
        $this->auto_crawl_enabled = get_option('nova_ai_auto_crawl_enabled', true);
        
        // Get crawl sites from options
        $sites_json = get_option('nova_ai_crawl_sites', '["https://ailinux.me"]');
        $this->crawl_sites = json_decode($sites_json, true);
        
        if (!is_array($this->crawl_sites)) {
            $this->crawl_sites = array('https://ailinux.me');
        }
    }
    
    /**
     * Run auto-crawl for all configured sites
     */
    public function run_auto_crawl() {
        if (!$this->auto_crawl_enabled) {
            nova_ai_log('Auto-crawl is disabled', 'info');
            return;
        }
        
        nova_ai_log('Starting auto-crawl for ' . count($this->crawl_sites) . ' sites', 'info');
        
        foreach ($this->crawl_sites as $site_url) {
            try {
                $this->crawl_site($site_url, 1); // Start with depth 1
                
                // Respect delay between sites
                if ($this->delay > 0) {
                    usleep($this->delay * 1000); // Convert to microseconds
                }
                
            } catch (Exception $e) {
                nova_ai_log('Auto-crawl failed for ' . $site_url . ': ' . $e->getMessage(), 'error');
            }
        }
        
        nova_ai_log('Auto-crawl completed', 'info');
    }
    
    /**
     * Crawl a single URL
     */
    public function crawl_single_url($url) {
        if (!$this->is_url_allowed($url)) {
            throw new Exception(__('URL not allowed for crawling', 'nova-ai-brainpool'));
        }
        
        $result = $this->fetch_and_store_content($url);
        
        return array(
            'success' => true,
            'url' => $url,
            'title' => $result['title'],
            'content_length' => strlen($result['content']),
            'status' => $result['status']
        );
    }
    
    /**
     * Crawl an entire site
     */
    public function crawl_site($base_url, $current_depth = 1) {
        if ($current_depth > $this->max_depth) {
            return;
        }
        
        // Get or create crawl queue
        $queue = $this->get_crawl_queue($base_url);
        
        foreach ($queue as $url) {
            try {
                // Check if already crawled recently
                if ($this->is_recently_crawled($url)) {
                    continue;
                }
                
                // Fetch and store content
                $result = $this->fetch_and_store_content($url);
                
                // Extract and queue new URLs if not at max depth
                if ($current_depth < $this->max_depth && $result['status'] === 'crawled') {
                    $new_urls = $this->extract_urls($result['content'], $base_url);
                    $this->add_to_crawl_queue($new_urls, $base_url);
                }
                
                // Respect delay
                if ($this->delay > 0) {
                    usleep($this->delay * 1000);
                }
                
            } catch (Exception $e) {
                nova_ai_log('Crawl failed for ' . $url . ': ' . $e->getMessage(), 'error');
                $this->mark_url_error($url, $e->getMessage());
            }
        }
        
        // Recurse to next depth
        if ($current_depth < $this->max_depth) {
            $this->crawl_site($base_url, $current_depth + 1);
        }
    }
    
    /**
     * Fetch and store content from URL
     */
    private function fetch_and_store_content($url) {
        // Check if URL exists in database
        $existing = $this->get_crawled_content($url);
        
        // Fetch content
        $content_data = $this->fetch_url_content($url);
        
        // Calculate content hash
        $content_hash = hash('sha256', $content_data['content']);
        
        // Check if content has changed
        if ($existing && $existing['content_hash'] === $content_hash) {
            // Content hasn't changed, just update timestamp
            $this->update_crawled_timestamp($url);
            return array(
                'title' => $existing['title'],
                'content' => $existing['content'],
                'status' => 'unchanged'
            );
        }
        
        // Store or update content
        $this->store_crawled_content($url, $content_data, $content_hash);
        
        return array(
            'title' => $content_data['title'],
            'content' => $content_data['content'],
            'status' => 'crawled'
        );
    }
    
    /**
     * Fetch content from URL
     */
    private function fetch_url_content($url) {
        $args = array(
            'timeout' => 30,
            'user-agent' => $this->user_agent,
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5'
            )
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('Failed to fetch URL: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            throw new Exception('HTTP error: ' . $response_code);
        }
        
        $html = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        
        // Check if it's HTML content
        if (strpos($content_type, 'text/html') === false) {
            throw new Exception('Not HTML content');
        }
        
        // Extract title and content
        return $this->parse_html_content($html, $url);
    }
    
    /**
     * Parse HTML content
     */
    private function parse_html_content($html, $url) {
        // Create DOMDocument
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        
        // Extract title
        $title_nodes = $dom->getElementsByTagName('title');
        $title = $title_nodes->length > 0 ? trim($title_nodes->item(0)->textContent) : parse_url($url, PHP_URL_HOST);
        
        // Remove script and style elements
        $this->remove_dom_elements($dom, array('script', 'style', 'nav', 'header', 'footer', 'aside'));
        
        // Extract main content
        $content = $this->extract_main_content($dom);
        
        // Clean up content
        $content = $this->clean_content($content);
        
        // Extract metadata
        $metadata = $this->extract_metadata($dom, $url);
        
        return array(
            'title' => $title,
            'content' => $content,
            'metadata' => $metadata
        );
    }
    
    /**
     * Remove DOM elements
     */
    private function remove_dom_elements($dom, $tag_names) {
        foreach ($tag_names as $tag_name) {
            $elements = $dom->getElementsByTagName($tag_name);
            $nodes_to_remove = array();
            
            foreach ($elements as $element) {
                $nodes_to_remove[] = $element;
            }
            
            foreach ($nodes_to_remove as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        }
    }
    
    /**
     * Extract main content from DOM
     */
    private function extract_main_content($dom) {
        // Try to find main content areas
        $content_selectors = array(
            'main',
            'article',
            '.content',
            '.main',
            '.post',
            '.entry',
            '#content',
            '#main'
        );
        
        $xpath = new DOMXPath($dom);
        $content = '';
        
        foreach ($content_selectors as $selector) {
            if (strpos($selector, '.') === 0) {
                // Class selector
                $class_name = substr($selector, 1);
                $nodes = $xpath->query("//*[@class='$class_name']");
            } elseif (strpos($selector, '#') === 0) {
                // ID selector
                $id = substr($selector, 1);
                $nodes = $xpath->query("//*[@id='$id']");
            } else {
                // Tag selector
                $nodes = $dom->getElementsByTagName($selector);
            }
            
            if ($nodes->length > 0) {
                $content = $nodes->item(0)->textContent;
                break;
            }
        }
        
        // Fallback to body content
        if (empty($content)) {
            $body = $dom->getElementsByTagName('body');
            if ($body->length > 0) {
                $content = $body->item(0)->textContent;
            }
        }
        
        return $content;
    }
    
    /**
     * Clean content text
     */
    private function clean_content($content) {
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Remove common noise
        $content = preg_replace('/\b(click here|read more|continue reading)\b/i', '', $content);
        
        // Trim
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Extract metadata from DOM
     */
    private function extract_metadata($dom, $url) {
        $xpath = new DOMXPath($dom);
        $metadata = array();
        
        // Extract meta description
        $description = $xpath->query("//meta[@name='description']/@content");
        if ($description->length > 0) {
            $metadata['description'] = $description->item(0)->value;
        }
        
        // Extract meta keywords
        $keywords = $xpath->query("//meta[@name='keywords']/@content");
        if ($keywords->length > 0) {
            $metadata['keywords'] = $keywords->item(0)->value;
        }
        
        // Extract Open Graph data
        $og_title = $xpath->query("//meta[@property='og:title']/@content");
        if ($og_title->length > 0) {
            $metadata['og_title'] = $og_title->item(0)->value;
        }
        
        $og_description = $xpath->query("//meta[@property='og:description']/@content");
        if ($og_description->length > 0) {
            $metadata['og_description'] = $og_description->item(0)->value;
        }
        
        // Extract language
        $lang = $xpath->query("//html/@lang");
        if ($lang->length > 0) {
            $metadata['language'] = $lang->item(0)->value;
        }
        
        $metadata['crawled_from'] = $url;
        $metadata['crawl_timestamp'] = current_time('mysql');
        
        return $metadata;
    }
    
    /**
     * Extract URLs from content
     */
    private function extract_urls($content, $base_url) {
        $urls = array();
        $base_domain = parse_url($base_url, PHP_URL_HOST);
        
        // Create DOMDocument to parse content
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
        
        $links = $dom->getElementsByTagName('a');
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            
            if (empty($href) || $href === '#') {
                continue;
            }
            
            // Convert relative URLs to absolute
            $absolute_url = $this->resolve_url($href, $base_url);
            
            // Check if URL is from same domain
            $url_domain = parse_url($absolute_url, PHP_URL_HOST);
            if ($url_domain === $base_domain) {
                $urls[] = $absolute_url;
            }
        }
        
        return array_unique($urls);
    }
    
    /**
     * Resolve relative URL to absolute
     */
    private function resolve_url($url, $base_url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url; // Already absolute
        }
        
        $parsed_base = parse_url($base_url);
        
        if ($url[0] === '/') {
            // Absolute path
            return $parsed_base['scheme'] . '://' . $parsed_base['host'] . $url;
        } else {
            // Relative path
            $base_path = dirname($parsed_base['path'] ?? '/');
            return $parsed_base['scheme'] . '://' . $parsed_base['host'] . $base_path . '/' . $url;
        }
    }
    
    /**
     * Store crawled content in database
     */
    private function store_crawled_content($url, $content_data, $content_hash) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_crawled_content';
        
        $data = array(
            'url' => $url,
            'title' => $content_data['title'],
            'content' => $content_data['content'],
            'metadata' => json_encode($content_data['metadata']),
            'content_hash' => $content_hash,
            'status' => 'crawled',
            'crawled_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // Check if URL already exists
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE url = %s",
            $url
        ));
        
        if ($existing_id) {
            // Update existing record
            $wpdb->update(
                $table,
                $data,
                array('id' => $existing_id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $table,
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Get crawled content from database
     */
    private function get_crawled_content($url) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_crawled_content';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE url = %s",
            $url
        ), ARRAY_A);
    }
    
    /**
     * Check if URL was recently crawled
     */
    private function is_recently_crawled($url, $hours = 24) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_crawled_content';
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE url = %s AND crawled_at > %s",
            $url,
            $cutoff
        ));
        
        return $count > 0;
    }
    
    /**
     * Update crawled timestamp
     */
    private function update_crawled_timestamp($url) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_crawled_content';
        
        $wpdb->update(
            $table,
            array('updated_at' => current_time('mysql')),
            array('url' => $url),
            array('%s'),
            array('%s')
        );
    }
    
    /**
     * Mark URL as error
     */
    private function mark_url_error($url, $error_message) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_crawled_content';
        
        $metadata = json_encode(array('error' => $error_message));
        
        $data = array(
            'url' => $url,
            'status' => 'error',
            'metadata' => $metadata,
            'updated_at' => current_time('mysql')
        );
        
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE url = %s",
            $url
        ));
        
        if ($existing_id) {
            $wpdb->update(
                $table,
                $data,
                array('id' => $existing_id)
            );
        } else {
            $wpdb->insert($table, $data);
        }
    }
    
    /**
     * Check if URL is allowed for crawling
     */
    private function is_url_allowed($url) {
        $parsed_url = parse_url($url);
        $domain = $parsed_url['host'] ?? '';
        
        // Check against allowed domains
        $allowed_domains = array();
        foreach ($this->crawl_sites as $site) {
            $site_domain = parse_url($site, PHP_URL_HOST);
            if ($site_domain) {
                $allowed_domains[] = $site_domain;
            }
        }
        
        return in_array($domain, $allowed_domains);
    }
    
    /**
     * Get crawl queue for site
     */
    private function get_crawl_queue($base_url) {
        // Start with base URL
        return array($base_url);
    }
    
    /**
     * Add URLs to crawl queue
     */
    private function add_to_crawl_queue($urls, $base_url) {
        // This could be implemented with a more sophisticated queue system
        // For now, we process URLs immediately
        return $urls;
    }
    
    /**
     * Get crawl status
     */
    public function get_crawl_status() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_crawled_content';
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $crawled = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'crawled'");
        $errors = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'error'");
        $pending = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'");
        
        $last_crawl = $wpdb->get_var(
            "SELECT crawled_at FROM $table WHERE status = 'crawled' ORDER BY crawled_at DESC LIMIT 1"
        );
        
        return array(
            'total_urls' => intval($total),
            'crawled_urls' => intval($crawled),
            'error_urls' => intval($errors),
            'pending_urls' => intval($pending),
            'last_crawl' => $last_crawl,
            'configured_sites' => $this->crawl_sites,
            'auto_crawl_enabled' => $this->auto_crawl_enabled
        );
    }
    
    /**
     * Clear crawled content
     */
    public function clear_crawled_content($url = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_crawled_content';
        
        if ($url) {
            $wpdb->delete($table, array('url' => $url), array('%s'));
        } else {
            $wpdb->query("TRUNCATE TABLE $table");
        }
    }
    
    /**
     * Get crawled content list
     */
    public function get_crawled_content_list($limit = 100, $offset = 0) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_crawled_content';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT url, title, status, crawled_at, updated_at, 
             LENGTH(content) as content_length
             FROM $table 
             ORDER BY updated_at DESC 
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A);
    }
}
