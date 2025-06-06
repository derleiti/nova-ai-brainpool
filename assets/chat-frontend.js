document.addEventListener('DOMContentLoaded', function() {
    console.log('Nova AI Chat: DOM loaded, initializing...');
    
    const chatbox = document.querySelector('.nova-ai-chatbox');
    const textarea = document.getElementById('nova-ai-input');
    const send = document.getElementById('nova-ai-send');
    const messages = document.getElementById('nova-ai-messages');
    const form = document.getElementById('nova-ai-chat-form');

    console.log('Elements found:', {
        chatbox: !!chatbox,
        textarea: !!textarea,
        send: !!send,
        messages: !!messages,
        form: !!form
    });

    if (!chatbox || !textarea || !send || !messages || !form) {
        console.error('Nova AI Chat: Erforderliche Elemente nicht gefunden');
        return;
    }

    // Auto-resize für Textarea
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    });

    // Form-Submit verhindern - WICHTIG!
    form.addEventListener('submit', function(e) {
        console.log('Form submit event triggered');
        e.preventDefault();
        e.stopPropagation();
        sendMessage();
        return false;
    });

    // Senden-Button Click-Event
    send.addEventListener('click', function(e) {
        console.log('Send button clicked');
        e.preventDefault();
        e.stopPropagation();
        sendMessage();
        return false;
    });

    // Tastatur-Events für Textarea
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            console.log('Enter key pressed');
            e.preventDefault();
            e.stopPropagation();
            sendMessage();
            return false;
        }
    });

    // Zusätzlicher Event-Listener für Sicherheit
    textarea.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });

    function sendMessage() {
        console.log('sendMessage function called');
        
        const userMsg = textarea.value.trim();
        if (!userMsg) {
            console.log('Empty message, focusing textarea');
            textarea.focus();
            return false;
        }

        console.log('Sending message:', userMsg);

        // Button sofort deaktivieren
        send.disabled = true;
        send.textContent = 'Sende...';
        
        // Message hinzufügen
        addMsg('Du', userMsg);
        textarea.value = '';
        textarea.style.height = 'auto';
        
        // Lade-Indikator
        const loadingMsg = addMsg('Nova', '⌛ Denke nach...');
        loadingMsg.classList.add('loading');

        // AJAX-Konfiguration prüfen
        const ajaxUrl = window.nova_ai_chat_ajax ? window.nova_ai_chat_ajax.ajaxurl : '/wp-admin/admin-ajax.php';
        const nonce = window.nova_ai_chat_ajax ? window.nova_ai_chat_ajax.nonce : '';

        console.log('AJAX config:', {
            ajaxUrl: ajaxUrl,
            nonce: nonce ? 'verfügbar' : 'FEHLT!'
        });

        if (!nonce) {
            console.error('Nova AI Chat: Nonce nicht verfügbar');
            removeMsg(loadingMsg);
            addMsg('Nova', '❌ Sicherheitsfehler: Seite neu laden und erneut versuchen');
            resetSendButton();
            return false;
        }

        // AJAX-Request
        const formData = new URLSearchParams({
            action: 'nova_ai_chat',
            prompt: userMsg,
            nonce: nonce
        });

        console.log('Sending AJAX request to:', ajaxUrl);

        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        })
        .then(response => {
            console.log('Response received:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            removeMsg(loadingMsg);
            
            if (data.success && data.data && data.data.answer) {
                addMsg('Nova', data.data.answer);
            } else {
                const errorMsg = data.data && data.data.msg ? data.data.msg : 'Unbekannter Fehler';
                addMsg('Nova', '❌ Fehler: ' + errorMsg);
                console.error('API Error:', data);
            }
        })
        .catch(error => {
            console.error('Nova AI Chat Fehler:', error);
            removeMsg(loadingMsg);
            addMsg('Nova', '❌ Verbindungsfehler: ' + error.message);
        })
        .finally(() => {
            resetSendButton();
            textarea.focus();
        });
        
        return false;
    }

    function addMsg(who, msg) {
        const el = document.createElement('div');
        el.className = 'nova-ai-msg ' + (who === 'Nova' ? 'ai' : 'user');
        
        // Sicherheit: HTML-Escape für Benutzereingaben
        const escapedMsg = who === 'Du' ? escapeHtml(msg) : msg;
        
        el.innerHTML = `<b>${escapeHtml(who)}:</b> ${escapedMsg.replace(/\n/g, '<br>')}`;
        messages.appendChild(el);
        
        // Smooth scroll nach unten
        messages.scrollTo({
            top: messages.scrollHeight,
            behavior: 'smooth'
        });
        
        return el;
    }

    function removeMsg(element) {
        if (element && element.parentNode) {
            element.parentNode.removeChild(element);
        }
    }

    function resetSendButton() {
        send.disabled = false;
        send.textContent = 'Senden';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initial focus auf Input
    textarea.focus();
});
