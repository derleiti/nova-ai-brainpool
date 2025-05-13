<?php
if (!defined('ABSPATH')) exit;

function nova_ai_knowledge_base() {
    $default = array(
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
        )
    );

    $custom = get_option('nova_ai_custom_knowledge', array());

    return array_merge($default, $custom);
}
