function nova_ai_register_settings() {
    register_setting('nova_ai_options_group', 'nova_ai_theme_mode');
}
add_action('admin_init', 'nova_ai_register_settings');
