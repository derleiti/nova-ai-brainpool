jQuery(document).ready(function($) {

    const chat = $('#nova-ai-chatbot');
    const apiUrl = chat.data('api-url');

    chat.html(`
        <div class="nova-ai-console-output"></div>
        <textarea id="nova-ai-console-input" placeholder="> Frag mich was..." rows="1"></textarea>
        <button id="nova-ai-send">Senden</button>
    `);

    const input = $('#nova-ai-console-input');
    const output = $('.nova-ai-console-output');

    function scrollBottom() {
        output.scrollTop(output[0].scrollHeight);
    }

    function sendMessage() {
        const message = input.val().trim();
        if (message.length < 1) return;

        output.append('<div class="user-input">> ' + message + '</div>');
        output.append('<div class="ai-response loading">[Warte auf Antwort...]</div>');
        scrollBottom();

        input.val('');

        $.ajax({
            url: apiUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ prompt: message }),
            success: function(response) {
                $('.loading').last().remove();
                output.append('<div class="ai-response">' + response.reply + '</div>');
                scrollBottom();
            },
            error: function() {
                $('.loading').last().remove();
                output.append('<div class="ai-response error">[Fehler bei Verbindung]</div>');
                scrollBottom();
            }
        });
    }

    input.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    $('#nova-ai-send').on('click', sendMessage);
});
