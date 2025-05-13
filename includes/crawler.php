<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Enhanced Web Crawler
 * 
 * Crawls specified URLs and stores the results in JSON format
 * for use in the Nova AI knowledge base.
 */

// Get crawl targets from WordPress options
function nova_ai_get_custom_targets() {
    $default = [
        'https://wiki.ubuntuusers.de/',
        'https://wiki.archlinux.org/',
        'https://ss64.com/osx/',
        'https://ss64.com/nt/',
        'https://wiki.termux.com/wiki/Main_Page',
        'https://www.freebsd.org/doc/',
        'https://man.openbsd.org/',
        'https://itsfoss.com/linux-commands/'
    ];
    $urls = get_option('nova_ai_crawl_urls', implode("\n", $default));
    return array_filter(array_map('trim', explode("\n", $urls)));
}

// Main crawler function
function nova_ai_run_crawler() {
    // Create necessary directories
    $base_dir = wp_upload_dir()['basedir'] . '/nova-ai-brainpool/knowledge/general/';
    if (!file_exists($base_dir)) {
        wp_mkdir_p($base_dir);
    }
    
    // Get crawler settings
    $targets = nova_ai_get_custom_targets();
    $depth = get_option('nova_ai_crawl_depth', 1);
    $char_limit = get_option('nova_ai_crawl_limit', 5000);
    
    // Start log
    $log = "Nova AI Crawler Log - " . date('Y-m-d H:i:s') . "\n";
    $log .= "------------------------------------\n";
    $log .= "Targets: " . count($targets) . " URLs\n";
    $log .= "Depth: " . $depth . "\n";
    $log .= "Character limit: " . $char_limit . " per page\n\n";
    
    $results = [];
    $processed_urls = [];
    
    // Process each target URL
    foreach ($targets as $url) {
        $log .= "Processing: " . $url . "\n";
        
        try {
            // Crawl the URL and its child pages
            $site_data = nova_ai_crawl_url($url, $depth, $char_limit, $processed_urls);
            
            if (!empty($site_data)) {
                $results = array_merge($results, $site_data);
                $log .= "✓ Success: " . count($site_data) . " pages processed\n";
            } else {
                $log .= "✗ Failed: No data retrieved\n";
            }
        } catch (Exception $e) {
            $log .= "✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // Save results to file if we have any
    if (!empty($results)) {
        $filename = $base_dir . 'web-' . date('Y-m-d_H-i-s') . '.json';
        $success = file_put_contents($filename, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $log .= "\nCrawl completed: " . count($results) . " total pages processed\n";
        $log .= "Output saved to: " . $filename . "\n";
        
        // Save log
        file_put_contents($base_dir . 'crawler-log-' . date('Y-m-d_H-i-s') . '.txt', $log);
        
        return $filename;
    } else {
        $log .= "\nCrawl failed: No data collected\n";
        file_put_contents($base_dir . 'crawler-log-' . date('Y-m-d_H-i-s') . '.txt', $log);
        return false;
    }
}

/**
 * Crawl a URL and its child pages
 *
 * @param string $url The URL to crawl
 * @param int $depth How many levels deep to crawl
 * @param int $char_limit Maximum characters to extract per page
 * @param array &$processed_urls Array of already processed URLs
 * @return array Collected page data
 */
function nova_ai_crawl_url($url, $depth = 1, $char_limit = 5000, &$processed_urls = []) {
    // Don't process URLs we've already seen
    if (in_array($url, $processed_urls)) {
        return [];
    }
    
    $processed_urls[] = $url;
    $results = [];
    
    // Get the content with better error handling
    $response = wp_remote_get($url, [
        'timeout' => 15,
        'sslverify' => false,
        'user-agent' => 'Mozilla/5.0 (compatible; Nova AI Crawler/1.0; +https://ailinux.me)'
    ]);
    
    if (is_wp_error($response)) {
        throw new Exception("Failed to fetch URL: " . $response->get_error_message());
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        throw new Exception("HTTP error: " . $status_code);
    }
    
    $html = wp_remote_retrieve_body($response);
    if (empty($html)) {
        throw new Exception("Empty response");
    }
    
    // Extract the title
    preg_match('/<title>(.*?)<\/title>/i', $html, $title_matches);
    $title = isset($title_matches[1]) ? trim($title_matches[1]) : $url;
    
    // Extract and clean text content
    $text = nova_ai_extract_text_from_html($html);
    $text = trim(preg_replace('/\s+/', ' ', $text));
    $text = mb_substr($text, 0, $char_limit);
    
    // Extract meta description
    preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $desc_matches);
    if (empty($desc_matches)) {
        preg_match('/<meta[^>]*content=["\']([^"\']*)["\'][^>]*name=["\']description["\'][^>]*>/i', $html, $desc_matches);
    }
    $description = isset($desc_matches[1]) ? trim($desc_matches[1]) : '';
    
    // Extract the main heading
    preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html, $h1_matches);
    $heading = isset($h1_matches[1]) ? trim(strip_tags($h1_matches[1])) : '';
    
    // Add this page to results
    if (!empty($text)) {
        $results[] = [
            'url' => $url,
            'title' => $title,
            'heading' => $heading,
            'description' => $description,
            'content' => $text,
            'crawled_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // If we need to go deeper, find links and crawl them
    if ($depth > 1 && count($processed_urls) < 50) { // Limit to 50 URLs total to prevent overload
        // Extract links
        preg_match_all('/<a\s+(?:[^>]*?\s+)?href=["\']([^"\']*)["\'][^>]*>/i', $html, $link_matches);
        
        if (isset($link_matches[1]) && !empty($link_matches[1])) {
            $base_url_parts = parse_url($url);
            $base_domain = $base_url_parts['scheme'] . '://' . $base_url_parts['host'];
            
            foreach ($link_matches[1] as $link) {
                // Skip non-HTTP links, anchors, or external domains
                if (strpos($link, '#') === 0 || strpos($link, 'javascript:') === 0 || strpos($link, 'mailto:') === 0) {
                    continue;
                }
                
                // Normalize the URL
                if (strpos($link, 'http') !== 0) {
                    if (strpos($link, '/') === 0) {
                        $link = $base_domain . $link;
                    } else {
                        $dir = dirname($url);
                        $link = $dir . '/' . $link;
                    }
                }
                
                // Make sure we're staying on the same domain
                $link_parts = parse_url($link);
                if (!isset($link_parts['host']) || $link_parts['host'] !== $base_url_parts['host']) {
                    continue;
                }
                
                // Skip already processed URLs
                if (in_array($link, $processed_urls)) {
                    continue;
                }
                
                // Recursively crawl this link
                try {
                    $child_results = nova_ai_crawl_url($link, $depth - 1, $char_limit, $processed_urls);
                    $results = array_merge($results, $child_results);
                } catch (Exception $e) {
                    // Skip failed URLs and continue with others
                    continue;
                }
                
                // Limit the total number of pages
                if (count($results) >= 50) {
                    break 2;
                }
            }
        }
    }
    
    return $results;
}

/**
 * Extract useful text from HTML while filtering out navigation, footers, etc.
 *
 * @param string $html The HTML content
 * @return string Extracted text
 */
function nova_ai_extract_text_from_html($html) {
    // Remove script, style, and other non-content elements
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
    $html = preg_replace('/<nav\b[^>]*>(.*?)<\/nav>/is', '', $html);
    $html = preg_replace('/<footer\b[^>]*>(.*?)<\/footer>/is', '', $html);
    $html = preg_replace('/<header\b[^>]*>(.*?)<\/header>/is', '', $html);
    $html = preg_replace('/<aside\b[^>]*>(.*?)<\/aside>/is', '', $html);
    
    // Try to find the main content
    $main_content = '';
    
    // Look for article or main tags
    if (preg_match('/<article[^>]*>(.*?)<\/article>/is', $html, $matches)) {
        $main_content = $matches[1];
    } elseif (preg_match('/<main[^>]*>(.*?)<\/main>/is', $html, $matches)) {
        $main_content = $matches[1];
    } elseif (preg_match('/<div[^>]*id=["\']content["\'][^>]*>(.*?)<\/div>/is', $html, $matches)) {
        $main_content = $matches[1];
    } elseif (preg_match('/<div[^>]*class=["\'][^"\']*content[^"\']*["\'][^>]*>(.*?)<\/div>/is', $html, $matches)) {
        $main_content = $matches[1];
    }
    
    // If we found main content, use it; otherwise use the whole page
    $text_to_process = $main_content ? $main_content : $html;
    
    // Convert to plain text
    $text = wp_strip_all_tags($text_to_process);
    
    // Clean up the text
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    $text = str_replace(["\r", "\n", "\t"], ' ', $text);
    $text = preg_replace('/\s{2,}/', ' ', $text);
    
    return trim($text);
}
