document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('#nova-ai-chat-form');
    const input = document.querySelector('#nova-ai-user-input');
    const fileInput = document.querySelector('#nova-ai-image-upload');
    const output = document.querySelector('#nova-ai-chat-output');

    if (!form || !input || !output) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const text = input.value.trim();
        const file = fileInput.files[0];

        if (!text && !file) {
            output.innerHTML = "<p><em>Bitte eine Frage eingeben oder ein Bild hochladen.</em></p>";
            return;
        }

        output.innerHTML = "<p><em>Verarbeite Anfrage...</em></p>";

        if (file) {
            const reader = new FileReader();
            reader.onload = function () {
                const base64Image = reader.result.split(',')[1];

                fetch(nova_ai_vars.chat_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        model: nova_ai_vars.model,
                        prompt: text || "Beschreibe dieses Bild",
                        image: base64Image
                    })
                })
                .then(res => res.json())
                .then(data => {
                    const response = data.message?.content || data.choices?.[0]?.message?.content || 'Keine Antwort.';
                    output.innerHTML = `<p>${response}</p>`;
                })
                .catch(err => {
                    output.innerHTML = "<p><strong>Fehler bei der Analyse.</strong></p>";
                    console.error(err);
                });
            };
            reader.readAsDataURL(file);
        } else {
            fetch(nova_ai_vars.chat_url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    model: nova_ai_vars.model,
                    messages: [{ role: 'user', content: text }],
                    stream: false
                })
            })
            .then(res => res.json())
            .then(data => {
                const response = data.message?.content || data.choices?.[0]?.message?.content || 'Keine Antwort.';
                output.innerHTML = `<p>${response}</p>`;
            })
            .catch(err => {
                output.innerHTML = "<p><strong>Fehler bei der Anfrage.</strong></p>";
                console.error(err);
            });
        }
    });

    console.log("Nova AI Modell:", nova_ai_vars.model);
});
