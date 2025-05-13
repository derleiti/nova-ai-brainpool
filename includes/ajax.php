<?php
// AJAX Handler
add_action('wp_ajax_nova_ai_chat', 'nova_ai_chat_handler');
add_action('wp_ajax_nopriv_nova_ai_chat', 'nova_ai_chat_handler');

function nova_ai_chat_handler() {
    $user_message = sanitize_text_field($_POST['message']);
    $reply = nova_ai_chat_response($user_message);

    wp_send_json_success(['reply' => $reply]);
}
?>
