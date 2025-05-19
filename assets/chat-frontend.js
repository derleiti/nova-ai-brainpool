document.addEventListener('DOMContentLoaded', function() {
    const chatbox = document.getElementById('nova-ai-chatbox');
    if (!chatbox) return;

    const textarea = document.getElementById('nova-ai-input');
    const send = document.getElementById('nova-ai-send');
    const messages = document.getElementById('nova-ai-messages');
    const form = document.getElementById('nova-ai-chat-form');

    // Senden mit Button oder Enter, neue Zeile mit Shift+Enter
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    function sendMessage() {
        const userMsg = textarea.value.trim();
        if (!userMsg) return;
        addMsg('Du', userMsg);
        textarea.value = '';
        textarea.focus();

        fetch((window.nova_ai_chat_ajax ? window.nova_ai_chat_ajax.ajaxurl : '/wp-admin/admin-ajax.php'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'nova_ai_chat',
                prompt: userMsg
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                addMsg('Nova', data.data.answer);
            } else {
                addMsg('Nova', 'Fehler: ' + (data.data.msg || 'Unbekannt'));
            }
        })
        .catch(() => addMsg('Nova', 'Serverfehler.'));
    }

    function addMsg(who, msg) {
        const el = document.createElement('div');
        el.className = 'nova-ai-msg ' + (who === 'Nova' ? 'ai' : 'user');
        el.innerHTML = `<b>${who}:</b> ${msg.replace(/\n/g, '<br>')}`;
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
    }
});
