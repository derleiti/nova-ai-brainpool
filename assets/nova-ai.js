jQuery(document).ready(function($) {
    $('#nova-ai-chat').html(`
        <input type="text" id="nova-input" placeholder="Frag Nova AI etwas...">
        <button id="nova-send">Senden</button>
        <div class="response" id="nova-response"></div>
    `);

    $('#nova-send').on('click', function() {
        let message = $('#nova-input').val();

        $.post(nova_ai_ajax.ajax_url, {
            action: 'nova_ai_chat',
            message: message
        }, function(response) {
            if (response.success) {
                $('#nova-response').html(response.data.reply);
            } else {
                $('#nova-response').html('Fehler: ' + response.data);
            }
        });
    });
});
