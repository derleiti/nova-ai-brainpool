document.addEventListener('DOMContentLoaded', function () {
    const chatbox = document.getElementById('nova-ai-chatbox');
    const form = document.getElementById('nova-ai-form');
    const input = document.getElementById('nova-ai-input');

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const userInput = input.value.trim();
        if (userInput === '') return;

        addMessage('Du', userInput);
        input.value = '';

        fetch('/wp-json/nova-ai/v1/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: userInput })
        })
        .then(response => response.json())
        .then(data => {
            addMessage('Nova', data.reply);
        })
        .catch(() => {
            addMessage('Nova', 'Fehler: Server offline oder AI nicht erreichbar.');
        });
    });

    function addMessage(sender, text) {
        chatbox.innerHTML += `<div><strong>${sender}:</strong> ${text}</div>`;
        chatbox.scrollTop = chatbox.scrollHeight;
    }
});
