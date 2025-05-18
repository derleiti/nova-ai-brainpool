<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Knowledge Base Manager – Verbesserte Version
 */

// Standardwissen – fest eingebaut
if (!function_exists('nova_ai_default_knowledge_base')) {
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
            )
        );
    }
}

// Kombiniert Standard- und benutzerdefiniertes Wissen
if (!function_exists('nova_ai_knowledge_base')) {
    function nova_ai_knowledge_base() {
        $default = nova_ai_default_knowledge_base();
        $custom = get_option('nova_ai_custom_knowledge', array());

        return array_merge($default, $custom);
    }
}

// Gibt relevantes Wissen basierend auf dem Prompt zurück
if (!function_exists('nova_ai_get_relevant_knowledge')) {
    function nova_ai_get_relevant_knowledge($prompt) {
        $knowledge_base = nova_ai_knowledge_base();
        $prompt_lower = strtolower($prompt);

        $relevant = array_filter($knowledge_base, function ($item) use ($prompt_lower) {
            return strpos(strtolower($item['question']), $prompt_lower) !== false ||
                   strpos(strtolower($item['answer']), $prompt_lower) !== false ||
                   strpos(strtolower($item['category']), $prompt_lower) !== false;
        });

        if (empty($relevant)) {
            return '';
        }

        $knowledge_text = "Hier sind einige relevante Informationen zur Beantwortung der Frage:\n\n";
        foreach ($relevant as $item) {
            $knowledge_text .= "F: {$item['question']}\nA: {$item['answer']}\n\n";
        }

        return $knowledge_text;
    }
}
