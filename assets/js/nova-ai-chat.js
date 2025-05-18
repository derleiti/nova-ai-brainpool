/**
 * Nova AI Brainpool - AI Chat Interface
 * Optimized for WordPress with improved security and accessibility
 */
document.addEventListener('DOMContentLoaded', function () {
    // Get DOM elements
    const form = document.querySelector('#nova-ai-chat-form');
    const input = document.querySelector('#nova-ai-user-input');
    const fileInput = document.querySelector('#nova-ai-image-upload');
    const output = document.querySelector('#nova-ai-chat-output');

    // Exit if elements not found
    if (!form || !input || !output) return;

    // Handle form submission
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const text = input.value.trim();
        const file = fileInput.files[0];

        // Validate input
        if (!text && !file) {
            output.innerHTML = "<p><em>" + (nova_ai_vars.error_message || "Please enter a question or upload an image.") + "</em></p>";
            return;
        }

        // Show loading state
        output.innerHTML = "<p><em>" + (nova_ai_vars.loading_message || "Processing request...") + "</em></p>";

        // If file is uploaded, handle image processing
        if (file) {
            // File size check
            if (file.size > 5000000) { // 5MB limit
                output.innerHTML = "<p><strong>Error: Image too large (max 5MB).</strong></p>";
                return;
            }

            const reader = new FileReader();
            reader.onload = function () {
                try {
                    const base64Image = reader.result.split(',')[1];

                    // Security: add nonce to headers
                    const headers = {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': nova_ai_vars.nonce
                    };

                    // Create request payload
                    const payload = {
                        model: nova_ai_vars.model,
                        prompt: text || "Describe this image",
                        image: base64Image
                    };

                    // Send to API
                    fetch(nova_ai_vars.chat_url, {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify(payload),
                        credentials: 'same-origin'
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Server returned ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        const response = data.message?.content || data.choices?.[0]?.message?.content || data.reply || 'No response.';
                        output.innerHTML = `<p>${escapeHTML(response)}</p>`;
                        
                        // Update stats if enabled
                        updateStats();
                    })
                    .catch(err => {
                        output.innerHTML = "<p><strong>Error analyzing image.</strong></p>";
                        console.error('Nova AI Error:', err);
                    });
                } catch (err) {
                    output.innerHTML = "<p><strong>Error processing image.</strong></p>";
                    console.error('Nova AI Error:', err);
                }
            };
            reader.onerror = function() {
                output.innerHTML = "<p><strong>Error reading file.</strong></p>";
            };
            reader.readAsDataURL(file);
        } else {
            // Text-only request
            const headers = {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nova_ai_vars.nonce
            };

            const payload = {
                model: nova_ai_vars.model,
                messages: [{ role: 'user', content: text }],
                stream: false
            };

            fetch(nova_ai_vars.chat_url, {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(payload),
                credentials: 'same-origin'
            })
            .then(res => {
                if (!res.ok) throw new Error('Server returned ' + res.status);
                return res.json();
            })
            .then(data => {
                const response = data.message?.content || data.choices?.[0]?.message?.content || data.reply || 'No response.';
                output.innerHTML = `<p>${formatResponse(response)}</p>`;
                
                // Update stats if enabled
                updateStats();
            })
            .catch(err => {
                output.innerHTML = "<p><strong>Error processing request.</strong></p>";
                console.error('Nova AI Error:', err);
            });
        }

        // Clear file input
        fileInput.value = '';
    });

    // Helper to escape HTML
    function escapeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Format response with simple markdown support
    function formatResponse(text) {
        if (!text) return '';

        // Escape HTML first
        let formatted = escapeHTML(text);

        // Simple markdown formatting
        formatted = formatted
            .replace(/\n\n/g, '<br><br>')
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>');

        return formatted;
    }

    // Update usage statistics
    function updateStats() {
        if (!nova_ai_vars.ajax_url || !nova_ai_vars.nonce) return;
        
        fetch(nova_ai_vars.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'nova_ai_chat_stats',
                nonce: nova_ai_vars.nonce
            }),
            credentials: 'same-origin'
        }).catch(e => console.log('Stats update error:', e));
    }

    // Log initialization if debug mode
    if (nova_ai_vars.debug) {
        console.log("Nova AI Chat initialized with model:", nova_ai_vars.model);
    }
});
