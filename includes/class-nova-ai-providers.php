<?php
// includes/class-nova-ai-providers.php

class NovaAI_Providers {
    public function __construct() {
        // Hier werden Provider wie Zephyr, Ollama, OpenAI angebunden
    }

    public function get_models() {
        // Demo: Liste verfügbarer Modelle (später dynamisch aus Settings)
        return ['zephyr', 'nova', 'mistral', 'llava', 'stable-diffusion'];
    }
}
?>
