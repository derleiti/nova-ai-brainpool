<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Knowledge Base Manager
 * 
 * Manages the knowledge base for the Nova AI assistant.
 * Supports adding, editing, and managing question/answer pairs.
 * Supports importing and exporting knowledge in JSON format.
 */

// Get the built-in default knowledge base
function nova_ai_default_knowledge_base() {
    return array(
        array(
            'question' => 'What is AILinux?',
            'answer' => 'AILinux is an independent, open-source Linux Distribution created by Markus Leitermann (derleiti.de). It is optimized for AI, Gaming, and Performance, and not related to Alibaba Cloud.'
        ),
        array(
            'question' => 'Who created AILinux?',
            'answer' => 'AILinux was created by Markus Leitermann, also known as derleiti. It is developed as a community-driven, optimized system for AI and power users.'
        ),
        array(
            'question' => 'What is ailinux.me?',
            'answer' => 'ailinux.me is the official website of the AILinux project. It provides downloads, updates, a forum, and documentation for the AILinux OS and its tools.'
        ),
        array(
            'question' => 'Who is Nova?',
            'answer' => 'Nova is the built-in AI assistant of AILinux. She helps users with Linux tasks, system optimization, and documentation lookup.'
        ),
        array(
            'question' => 'What is Nova AI Brainpool?',
            'answer' => 'Nova AI Brainpool is a WordPress plugin that provides a minimalist AI chat interface in a terminal style. It connects to local LLMs through Ollama or to cloud APIs like OpenAI.'
        ),
        array(
            'question' => 'How can I customize Nova AI?',
            'answer' => 'You can customize Nova AI through the WordPress admin panel under "Nova AI". You can change the theme, configure the AI provider, and manage the knowledge base.'
        ),
        array(
            'question' => 'What AI models can Nova use?',
            'answer' => 'Nova AI can use Ollama local models like Mistral, Llama, or any other model Ollama supports. It can also connect to the OpenAI API to use GPT models.'
        )
    );
}

// Get the complete knowledge base (default + custom)
function nova_ai_knowledge_base() {
    $default = nova_ai_default_knowledge_base();
    $custom = get_option('nova_ai_custom_knowledge', array());
    
    return array_merge($default, $custom);
}

// Add an item to the knowledge base
function nova_ai_add_knowledge_item($question, $answer, $category = 'general') {
    if (empty($question) || empty($answer)) {
        return false;
    }
    
    $knowledge_base = get_option('nova_ai_custom_knowledge', array());
    $knowledge_base[] = array(
        'question' => $question,
        'answer' => $answer,
        'category' => $category,
        'added' => date('Y-m-d H:i:s')
    );
    
    return update_option('nova_ai_custom_knowledge', $knowledge_base);
}

// Update a knowledge item
function nova_ai_update_knowledge_item($index, $question, $answer, $category = null) {
    $knowledge_base = get_option('nova_ai_custom_knowledge', array());
    
    if (!isset($knowledge_base[$index])) {
        return false;
    }
    
    $knowledge_base[$index]['question'] = $question;
    $knowledge_base[$index]['answer'] = $answer;
    
    if ($category !== null) {
        $knowledge_base[$index]['category'] = $category;
    }
    
    $knowledge_base[$index]['updated'] = date('Y-m-d H:i:s');
    
    return update_option('nova_ai_custom_knowledge', $knowledge_base);
}

// Delete a knowledge item
function nova_ai_delete_knowledge_item($index) {
    $knowledge_base = get_option('nova_ai_custom_knowledge', array());
    
    if (!isset($knowledge_base[$index])) {
        return false;
    }
    
    unset($knowledge_base[$index]);
    $knowledge_base = array_values($knowledge_base); // Reindex array
    
    return update_option('nova_ai_custom_knowledge', $knowledge_base);
}

// Import knowledge items from JSON
function nova_ai_import_knowledge_from_json($json_data) {
    if (empty($json_data)) {
        return false;
    }
    
    $imported_data = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($imported_data)) {
        return false;
    }
    
    $valid_items = array();
    
    foreach ($imported_data as $item) {
        if (isset($item['question']) && isset($item['answer']) && 
            !empty($item['question']) && !empty($item['answer'])) {
            $valid_items[] = array(
                'question' => sanitize_text_field($item['question']),
                'answer' => sanitize_textarea_field($item['answer']),
                'category' => isset($item['category']) ? sanitize_text_field($item['category']) : 'general',
                'imported' => date('Y-m-d H:i:s')
            );
        }
    }
    
    if (empty($valid_items)) {
        return false;
    }
    
    $current_knowledge = get_option('nova_ai_custom_knowledge', array());
    $merged_knowledge = array_merge($current_knowledge, $valid_items);
    
    update_option('nova_ai_custom_knowledge', $merged_knowledge);
    
    return count($valid_items);
}

// Process crawled content into QA format
function nova_ai_process_crawl_to_knowledge($crawl_json_file) {
    if (!file_exists($crawl_json_file)) {
        return false;
    }
    
    $json_data = file_get_contents($crawl_json_file);
    $crawled_pages = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($crawled_pages)) {
        return false;
    }
    
    $knowledge_items = array();
    
    foreach ($crawled_pages as $page) {
        if (empty($page['content']) || empty($page['url'])) {
            continue;
        }
        
        // Create title-based question
        if (!empty($page['title'])) {
            $title_question = "What is " . strip_tags($page['title']) . "?";
            $title_answer = wp_trim_words($page['content'], 60, '...');
            
            $knowledge_items[] = array(
                'question' => sanitize_text_field($title_question),
                'answer' => sanitize_textarea_field($title_answer),
                'category' => 'crawled',
                'source_url' => esc_url_raw($page['url']),
                'added' => date('Y-m-d H:i:s')
            );
        }
        
        // If we have a heading, create another question
        if (!empty($page['heading']) && $page['heading'] !== $page['title']) {
            $heading_question = "Tell me about " . strip_tags($page['heading']);
            $heading_answer = wp_trim_words($page['content'], 60, '...');
            
            $knowledge_items[] = array(
                'question' => sanitize_text_field($heading_question),
                'answer' => sanitize_textarea_field($heading_answer),
                'category' => 'crawled',
                'source_url' => esc_url_raw($page['url']),
                'added' => date('Y-m-d H:i:s')
            );
        }
    }
    
    if (empty($knowledge_items)) {
        return false;
    }
    
    $current_knowledge = get_option('nova_ai_custom_knowledge', array());
    $merged_knowledge = array_merge($current_knowledge, $knowledge_items);
    
    update_option('nova_ai_custom_knowledge', $merged_knowledge);
    
    return count($knowledge_items);
}

// Filter knowledge base items by relevance to the prompt
function nova_ai_filter_relevant_knowledge($knowledge_base, $prompt, $limit = 10) {
    if (empty($knowledge_base) || count($knowledge_base) <= $limit) {
        return $knowledge_base;
    }
    
    // Simple relevance scoring based on word overlap
    $prompt_words = preg_split('/\W+/', strtolower($prompt));
    $scored_items = array();
    
    foreach ($knowledge_base as $index => $item) {
        $question_words = preg_split('/\W+/', strtolower($item['question']));
        $answer_words = preg_split('/\W+/', strtolower($item['answer']));
        $all_words = array_merge($question_words, $answer_words);
        
        $score = 0;
        foreach ($prompt_words as $word) {
            if (strlen($word) < 3) continue; // Skip short words
            if (in_array($word, $all_words)) {
                $score++;
            }
        }
        
        $scored_items[$index] = $score;
    }
    
    // Sort by score (descending)
    arsort($scored_items);
    
    // Take top N items
    $relevant_indices = array_slice(array_keys($scored_items), 0, $limit);
    $relevant_items = array();
    
    foreach ($relevant_indices as $index) {
        $relevant_items[] = $knowledge_base[$index];
    }
    
    return $relevant_items;
}

// Register REST API endpoint for knowledge export
add_action('rest_api_init', function () {
    register_rest_route('nova-ai/v1', '/knowledge.json', array(
        'methods' => 'GET',
        'callback' => 'nova_ai_export_knowledge_callback',
        'permission_callback' => function() {
            return current_user_can('manage_options') || get_option('nova_ai_public_export', false);
        }
    ));
});

// Export knowledge base via REST API
function nova_ai_export_knowledge_callback() {
    $include_default = isset($_GET['include_default']) && $_GET['include_default'] === 'true';
    
    if ($include_default) {
        return nova_ai_knowledge_base();
    } else {
        return get_option('nova_ai_custom_knowledge', array());
    }
}
