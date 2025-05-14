<?php
if (!defined('ABSPATH')) exit;

/**
 * Nova AI Knowledge Base Manager - Minimal Version
 */

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

if (!function_exists('nova_ai_knowledge_base')) {
    function nova_ai_knowledge_base() {
        $default = nova_ai_default_knowledge_base();
        $custom = get_option('nova_ai_custom_knowledge', array());
        
        return array_merge($default, $custom);
    }
}

if (!function_exists('nova_ai_get_relevant_knowledge')) {
    function nova_ai_get_relevant_knowledge($prompt) {
        $knowledge_base = nova_ai_knowledge_base();
        
        // Einfache Implementierung, die alle Wissenseinträge zurückgibt
        $knowledge_text = "Hier sind einige relevante Informationen zur Beantwortung der Frage:\n\n";
        foreach ($knowledge_base as $item) {
            $knowledge_text .= "F: {$item['question']}\nA: {$item['answer']}\n\n";
        }
        
        return $knowledge_text;
    }
}
