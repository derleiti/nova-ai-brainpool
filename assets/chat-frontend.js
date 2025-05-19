document.addEventListener('DOMContentLoaded', function() {
    const chatbox = document.getElementById('nova-ai-chatbox');
    if (!chatbox) return;

    const input = document.getElementById('nova-ai-input');
    const send = document.getElementById('nova-ai-send');
    const messages = document.getElementById('nova-ai-messages');

    send.addEventListener('click', sendMessage);
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') sendMessage();
    });

    function sendMessage() {
        const userMsg = input.value.trim();
        if (!userMsg) return;
        addMsg('Du', userMsg);
        input.value = '';
        // AJAX-Request an WP (WordPress Ajax-Endpoint)
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
        el.innerHTML = `<b>${who}:</b> ${msg}`;
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
    }
});
