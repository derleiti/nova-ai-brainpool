jQuery(document).ready(function($) {

    function sendMessage() {
        var userInput = $('#nova-ai-input').val();
        if (userInput.trim().length == 0) return;
        $('#nova-ai-messages').append('<div class="user-msg">'+userInput+'</div>');
        $('#nova-ai-input').val('');

        $.ajax({
            url: '/wp-json/nova-ai/v1/chat',
            method: 'POST',
            data: { prompt: userInput },
            success: function(response) {
                $('#nova-ai-messages').append('<div class="ai-msg">'+response.reply+'</div>');
                $('#nova-ai-messages').scrollTop($('#nova-ai-messages')[0].scrollHeight);
            },
            error: function() {
                $('#nova-ai-messages').append('<div class="ai-msg">Fehler bei Anfrage.</div>');
            }
        });
    }

    $('#nova-ai-send').click(function() {
        sendMessage();
    });

    $('#nova-ai-input').keypress(function(e) {
        if(e.which == 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

});
