jQuery(document).ready(function($) {
    const chat = $('#nova-ai-chatbot');
    const apiUrl = chat.data('api-url');
    const theme = nova_ai_vars.theme || 'terminal';
    const placeholderText = nova_ai_vars.placeholder || '> Frag mich was...';
    const conversationId = nova_ai_vars.conversation_id || '';

    // Initialize chat interface
    chat.html(`
        <div class="nova-ai-console-header">
            <span class="nova-ai-title">Nova AI Console</span>
            <div class="nova-ai-status">Ready</div>
        </div>
        <div class="nova-ai-console-output"></div>
        <div class="nova-ai-console-input-area">
            <textarea id="nova-ai-console-input" placeholder="${placeholderText}" rows="1"></textarea>
            <button id="nova-ai-send">Senden</button>
        </div>
    `);

    const input = $('#nova-ai-console-input');
    const output = $('.nova-ai-console-output');
    const status = $('.nova-ai-status');
    const sendButton = $('#nova-ai-send');

    // Initial welcome message
    output.append('<div class="ai-response">Willkommen! Ich bin Nova, dein KI Assistent. Wie kann ich dir helfen?</div>');
    scrollToBottom();

    // Helper functions
    function scrollToBottom() {
        output.scrollTop(output[0].scrollHeight);
    }

    function setStatus(message, isError = false) {
        status.text(message);
        if (isError) {
            status.addClass('error');
        } else {
            status.removeClass('error');
        }
    }

    function sendMessage() {
        const message = input.val().trim();
        if (message.length < 1) return;

        // Disable input while processing
        input.prop('disabled', true);
        sendButton.prop('disabled', true);
        setStatus('Sending...');

        // Add user message to chat
        output.append('<div class="user-input">> ' + message.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>');
        output.append('<div class="ai-response loading">[Warte auf Antwort...]</div>');
        scrollToBottom();

        // Clear input
        input.val('');

        // Send to API
        $.ajax({
            url: apiUrl,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nova_ai_vars.nonce
            },
            data: JSON.stringify({ 
                prompt: message,
                conversation_id: conversationId
            }),
            success: function(response) {
                $('.loading').last().remove();
                
                // Process the response text - add markdown-like formatting
                let formattedResponse = response.reply
                    .replace(/\n\n/g, '<br><br>')
                    .replace(/\n/g, '<br>')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/`(.*?)`/g, '<code>$1</code>');
                
                output.append('<div class="ai-response">' + formattedResponse + '</div>');
                scrollToBottom();
                setStatus('Ready');
            },
            error: function(xhr) {
                $('.loading').last().remove();
                
                let errorMessage = 'Connection error';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                output.append('<div class="ai-response error">[Fehler: ' + errorMessage + ']</div>');
                scrollToBottom();
                setStatus('Error', true);
            },
            complete: function() {
                // Re-enable input
                input.prop('disabled', false);
                sendButton.prop('disabled', false);
                input.focus();
            }
        });
    }

    // Auto-resize textarea as user types
    input.on('input', function() {
        this.style.height = 'auto';
        const newHeight = Math.min(this.scrollHeight, 150); // Max height of 150px
        this.style.height = newHeight + 'px';
    });

    // Event handlers
    input.on('keydown', function(e) {
        // Send on Enter (without Shift)
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    sendButton.on('click', sendMessage);

    // Focus input on start
    input.focus();
});
