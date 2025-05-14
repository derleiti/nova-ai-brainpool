<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Knowledge Base Manager
 * Handles storage and retrieval of knowledge items
 */

/**
 * Get default knowledge base items
 */
function nova_ai_default_knowledge_base() {
    return array(
        array(
            'question' => 'Was ist AILinux?',
            'answer' => 'AILinux ist eine unabhängige, Open-Source Linux-Distribution, die von Markus Leitermann (derleiti.de) erstellt wurde. Sie ist für KI, Gaming und Leistung optimiert und steht nicht in Verbindung zu Alibaba Cloud.',
            'category' => 'ailinux'
        ),
        array(
            'question' => 'Wer hat AILinux erstellt?',
            'answer' => 'AILinux wurde von Markus Leitermann, auch bekannt als derleiti, erstellt. Es wird als Community-gestütztes, optimiertes System für KI und Power-User entwickelt.',
            'category' => 'ailinux'
        ),
        array(
            'question' => 'Was ist Nova AI?',
            'answer' => 'Nova AI ist ein intelligenter Assistent im Terminal-Stil, der für AILinux entwickelt wurde. Er kann Fragen beantworten, bei der Fehlerbehebung helfen und mit dem KI-Backend Ollama für lokale KI-Verarbeitung arbeiten.',
            'category' => 'ailinux'
        ),
        array(
            'question' => 'Welche Modelle unterstützt Nova AI?',
            'answer' => 'Nova AI unterstützt verschiedene Modelle über Ollama, darunter Zephyr, Mistral, LLaMA 2, Neural-Chat und mehr. Diese können lokal ausgeführt werden, ohne dass Daten an externe Dienste gesendet werden müssen.',
            'category' => 'ailinux'
        ),
        array(
            'question' => 'Wie kann ich Nova AI konfigurieren?',
            'answer' => 'In den WordPress-Einstellungen unter "Nova AI" kannst du das Modell, die API-URL, Prompts und andere Einstellungen anpassen. Standardmäßig ist Nova AI für die Verwendung mit Ollama konfiguriert.',
            'category' => 'faq'
        )
    );
}

/**
 * Get combined knowledge base (default + custom)
 */
function nova_ai_knowledge_base() {
    $default = nova_ai_default_knowledge_base();
    $custom = get_option('nova_ai_custom_knowledge', array());
    
    return array_merge($default, $custom);
}

/**
 * Add item to knowledge base
 */
function nova_ai_add_knowledge_item($question, $answer, $category = 'general') {
    // Get current custom knowledge base
    $knowledge = get_option('nova_ai_custom_knowledge', array());
    
    // Add new item
    $knowledge[] = array(
        'question' => $question,
        'answer' => $answer,
        'category' => $category,
        'created' => date('Y-m-d H:i:s')
    );
    
    // Save updated knowledge base
    return update_option('nova_ai_custom_knowledge', $knowledge);
}

/**
 * Delete item from knowledge base
 */
function nova_ai_delete_knowledge_item($index) {
    // Get current custom knowledge base
    $knowledge = get_option('nova_ai_custom_knowledge', array());
    
    // Check if index exists
    if (!isset($knowledge[$index])) {
        return false;
    }
    
    // Remove item
    unset($knowledge[$index]);
    $knowledge = array_values($knowledge); // Reindex array
    
    // Save updated knowledge base
    return update_option('nova_ai_custom_knowledge', $knowledge);
}

/**
 * Import knowledge from JSON
 */
function nova_ai_import_knowledge_from_json($json_data) {
    // Decode JSON
    $data = json_decode($json_data, true);
    
    if (!$data || !is_array($data)) {
        return false;
    }
    
    // Get current custom knowledge base
    $knowledge = get_option('nova_ai_custom_knowledge', array());
    $count = 0;
    
    // Process each item
    foreach ($data as $item) {
        if (isset($item['question']) && isset($item['answer'])) {
            $knowledge[] = array(
                'question' => sanitize_text_field($item['question']),
                'answer' => sanitize_textarea_field($item['answer']),
                'category' => isset($item['category']) ? sanitize_text_field($item['category']) : 'general',
                'created' => date('Y-m-d H:i:s')
            );
            $count++;
        }
    }
    
    // Save updated knowledge base
    if ($count > 0) {
        update_option('nova_ai_custom_knowledge', $knowledge);
    }
    
    return $count;
}

/**
 * Get knowledge items relevant to a prompt
 */
function nova_ai_get_relevant_knowledge($prompt) {
    $knowledge_base = nova_ai_knowledge_base();
    $relevant_items = array();
    $prompt = strtolower(trim($prompt));
    
    // Find relevant items based on keyword matching
    foreach ($knowledge_base as $item) {
        $question = strtolower($item['question']);
        $keywords = explode(' ', $question);
        
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 3 && strpos($prompt, $keyword) !== false) {
                $relevant_items[] = $item;
                break;
            }
        }
    }
    
    // If no matches, try to find items by category
    if (empty($relevant_items)) {
        $categories = array('ailinux', 'linux', 'programming', 'faq');
        
        foreach ($categories as $category) {
            if (strpos($prompt, $category) !== false) {
                foreach ($knowledge_base as $item) {
                    if (isset($item['category']) && $item['category'] === $category) {
                        $relevant_items[] = $item;
                    }
                }
                
                if (!empty($relevant_items)) {
                    break;
                }
            }
        }
    }
    
    // Format the results
    if (!empty($relevant_items)) {
        $knowledge_text = "";
        
        foreach ($relevant_items as $item) {
            $knowledge_text .= "F: {$item['question']}\nA: {$item['answer']}\n\n";
        }
        
        return $knowledge_text;
    }
    
    return "";
}

/**
 * Process crawl results into knowledge items
 */
function nova_ai_process_crawl_to_knowledge($crawl_file) {
    if (!file_exists($crawl_file)) {
        return 0;
    }
    
    // Read and decode the JSON file
    $json_data = file_get_contents($crawl_file);
    $data = json_decode($json_data, true);
    
    if (!$data || !is_array($data)) {
        return 0;
    }
    
    // Get current custom knowledge base
    $knowledge = get_option('nova_ai_custom_knowledge', array());
    $count = 0;
    
    // Process each page
    foreach ($data as $page) {
        if (!isset($page['title']) || !isset($page['content']) || empty($page['content'])) {
            continue;
        }
        
        // Create knowledge items from title and content
        $title = sanitize_text_field($page['title']);
        $content = $page['content'];
        $url = isset($page['url']) ? esc_url_raw($page['url']) : '';
        
        // Create a question from the title
        $question = "Was ist " . $title . "?";
        
        // Create an answer (first 200 words of content)
        $words = explode(' ', $content);
        $answer = implode(' ', array_slice($words, 0, 200));
        
        if (!empty($url)) {
            $answer .= "\n\nQuelle: " . $url;
        }
        
        // Add to knowledge base
        $knowledge[] = array(
            'question' => $question,
            'answer' => sanitize_textarea_field($answer),
            'category' => 'crawled',
            'created' => date('Y-m-d H:i:s'),
            'source' => $url
        );
        $count++;
        
        // If the page has a heading, create another item
        if (isset($page['heading']) && !empty($page['heading'])) {
            $heading = sanitize_text_field($page['heading']);
            
            if ($heading !== $title) {
                $question = "Was ist " . $heading . "?";
                
                $knowledge[] = array(
                    'question' => $question,
                    'answer' => sanitize_textarea_field($answer),
                    'category' => 'crawled',
                    'created' => date('Y-m-d H:i:s'),
                    'source' => $url
                );
                $count++;
            }
        }
    }
    
    // Save updated knowledge base
    if ($count > 0) {
        update_option('nova_ai_custom_knowledge', $knowledge);
    }
    
    return $count;
}
