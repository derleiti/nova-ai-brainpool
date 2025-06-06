/**
 * Nova AI Brainpool - Extended JavaScript
 * Multi-Provider, Stable Diffusion, NovaNet Frontend
 */

// Nova AI Namespace
window.NovaAI = window.NovaAI || {};

(function($) {
    'use strict';
    
    // Globale Konfiguration
    NovaAI.config = window.nova_ai_config || {};
    NovaAI.activeChats = {};
    
    console.log('Nova AI Extended: Initializing with config', NovaAI.config);
    
    /**
     * Chat-Instanz initialisieren
     */
    NovaAI.initializeChat = function(chatId, options) {
        console.log('Nova AI: Initializing chat', chatId, options);
        
        const chat = {
            id: chatId,
            options: options,
            elements: {
                container: document.getElementById(chatId + '-container'),
                messages: document.getElementById(chatId + '-messages'),
                input: document.getElementById(chatId + '-input'),
                providerSelect: document.getElementById(chatId + '-provider'),
                modelSelect: document.getElementById(chatId + '-model'),
                imageMode: document.getElementById(chatId + '-image-mode'),
                status: document.getElementById(chatId + '-status')
            },
            currentProvider: options.activeProvider,
            currentModel: '',
            imageMode: false,
            isProcessing: false
        };
        
        // Elemente validieren
        if (!chat.elements.container || !chat.elements.messages || !chat.elements.input) {
            console.error('Nova AI: Required elements not found for chat', chatId);
            return;
        }
        
        // Event-Listener registrieren
        this.setupChatEvents(chat);
        
        // Provider/Model-Listen aktualisieren
        this.updateProviderModels(chat);
        
        // Chat in globaler Liste speichern
        NovaAI.activeChats[chatId] = chat;
        
        // Initial fokussieren
        chat.elements.input.focus();
        
        console.log('Nova AI: Chat initialized successfully', chatId);
    };
    
    /**
     * Event-Listener für Chat einrichten
     */
    NovaAI.setupChatEvents = function(chat) {
        const elements = chat.elements;
        
        // Input-Events
        if (elements.input) {
            // Enter-Taste
            elements.input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    NovaAI.sendMessage(chat.id);
                    return false;
                }
                
                // Auto-resize
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            });
        }
        
        // Provider-Wechsel
        if (elements.providerSelect) {
            elements.providerSelect.addEventListener('change', function() {
                chat.currentProvider = this.value;
                NovaAI.updateProviderModels(chat);
                NovaAI.updateStatus(chat, `Gewechselt zu ${this.options[this.selectedIndex].text}`);
            });
        }
        
        // Model-Wechsel
        if (elements.modelSelect) {
            elements.modelSelect.addEventListener('change', function() {
                chat.currentModel = this.value;
                NovaAI.updateStatus(chat, `Modell: ${this.value}`);
            });
        }
        
        // Bildmodus-Toggle
        if (elements.imageMode) {
            elements.imageMode.addEventListener('change', function() {
                chat.imageMode = this.checked;
                const mode = this.checked ? '🎨 Bildmodus' : '💬 Textmodus';
                NovaAI.updateStatus(chat, mode);
                
                // Placeholder anpassen
                if (elements.input) {
                    const placeholder = this.checked ? 
                        'Beschreibe das Bild das du generieren möchtest...' :
                        'Deine Nachricht an Nova...';
                    elements.input.placeholder = placeholder;
                }
            });
        }
    };
    
    /**
     * Provider-Modelle aktualisieren
     */
    NovaAI.updateProviderModels = function(chat) {
        if (!chat.elements.modelSelect || !chat.options.providers) return;
        
        const provider = chat.options.providers[chat.currentProvider];
        if (!provider) return;
        
        // Model-Select leeren
        chat.elements.modelSelect.innerHTML = '';
        
        // Modelle hinzufügen
        provider.models.forEach(model => {
            const option = document.createElement('option');
            option.value = model;
            option.textContent = model;
            if (model === provider.default_model) {
                option.selected = true;
                chat.currentModel = model;
            }
            chat.elements.modelSelect.appendChild(option);
        });
        
        console.log('Nova AI: Updated models for provider', chat.currentProvider, provider.models);
    };
    
    /**
     * Nachricht senden
     */
    NovaAI.sendMessage = function(chatId) {
        const chat = NovaAI.activeChats[chatId];
        if (!chat || chat.isProcessing) return;
        
        const message = chat.elements.input.value.trim();
        if (!message) {
            chat.elements.input.focus();
            return;
        }
        
        console.log('Nova AI: Sending message', {chatId, message, provider: chat.currentProvider});
        
        // Processing-Status setzen
        chat.isProcessing = true;
        NovaAI.updateStatus(chat, '⌛ Verarbeite...');
        
        // User-Nachricht anzeigen
        NovaAI.addMessage(chat, 'Du', message, 'user');
        
        // Input leeren
        chat.elements.input.value = '';
        chat.elements.input.style.height = 'auto';
        
        // Loading-Nachricht
        const loadingMsg = NovaAI.addMessage(chat, 'Nova', '⌛ Denke nach...', 'ai loading');
        
        // AJAX-Request vorbereiten
        const formData = new FormData();
        formData.append('action', chat.imageMode ? 'nova_ai_generate_image' : 'nova_ai_chat');
        formData.append('prompt', message);
        formData.append('provider', chat.currentProvider);
        formData.append('model', chat.currentModel);
        formData.append('nonce', NovaAI.config.nonce);
        
        // AJAX-Request senden
        fetch(NovaAI.config.ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(response => {
            console.log('Nova AI: Response received', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Nova AI: Response data', data);
            NovaAI.removeMessage(loadingMsg);
            
            if (data.success) {
                NovaAI.handleSuccessResponse(chat, data.data);
            } else {
                const error = data.data?.msg || 'Unbekannter Fehler';
                NovaAI.addMessage(chat, 'Nova', '❌ ' + error, 'ai error');
            }
        })
        .catch(error => {
            console.error('Nova AI: Request failed', error);
            NovaAI.removeMessage(loadingMsg);
            NovaAI.addMessage(chat, 'Nova', '❌ Verbindungsfehler: ' + error.message, 'ai error');
        })
        .finally(() => {
            chat.isProcessing = false;
            NovaAI.updateStatus(chat, 'Bereit');
            chat.elements.input.focus();
        });
    };
    
    /**
     * Erfolgreiche Antwort verarbeiten
     */
    NovaAI.handleSuccessResponse = function(chat, data) {
        if (data.type === 'image_generation') {
            // Bildgenerierung
            NovaAI.addImageMessage(chat, data);
        } else {
            // Standard-Antwort
            NovaAI.addMessage(chat, 'Nova', data.answer, 'ai');
        }
        
        // Provider-Info anzeigen
        if (data.provider && data.model) {
            NovaAI.updateStatus(chat, `${data.provider}/${data.model} • ${data.tokens_used || 0} Tokens`);
        }
    };
    
    /**
     * Bild-Nachricht hinzufügen
     */
    NovaAI.addImageMessage = function(chat, data) {
        const messageEl = document.createElement('div');
        messageEl.className = 'nova-ai-msg ai image-generation';
        
        messageEl.innerHTML = `
            <div class="nova-msg-header">
                <span class="nova-avatar">🤖</span>
                <span class="nova-sender">Nova</span>
                <span class="nova-provider-badge">🎨 Stable Diffusion</span>
            </div>
            <div class="nova-msg-content">
                <div class="nova-image-result">
                    <img src="${data.image_url}" alt="Generiertes Bild" class="nova-generated-image" />
                    <div class="nova-image-info">
                        <p><strong>Prompt:</strong> ${NovaAI.escapeHtml(data.image_prompt)}</p>
                        <div class="nova-image-actions">
                            <a href="${data.image_url}" download="nova-ai-generated.png" class="nova-btn-small">
                                📥 Download
                            </a>
                            <button onclick="NovaAI.copyImagePrompt('${NovaAI.escapeHtml(data.image_prompt)}')" 
                                    class="nova-btn-small">
                                📋 Prompt kopieren
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        chat.elements.messages.appendChild(messageEl);
        NovaAI.scrollToBottom(chat);
        
        // Bildmodus automatisch deaktivieren
        if (chat.elements.imageMode) {
            chat.elements.imageMode.checked = false;
            chat.imageMode = false;
        }
    };
    
    /**
     * Standard-Nachricht hinzufügen
     */
    NovaAI.addMessage = function(chat, sender, content, type) {
        const messageEl = document.createElement('div');
        messageEl.className = `nova-ai-msg ${type}`;
        
        const avatar = sender === 'Nova' ? '🤖' : '👤';
        const providerBadge = type === 'ai' && chat.currentProvider ? 
            `<span class="nova-provider-badge">${chat.currentProvider}</span>` : '';
        
        messageEl.innerHTML = `
            <div class="nova-msg-header">
                <span class="nova-avatar">${avatar}</span>
                <span class="nova-sender">${NovaAI.escapeHtml(sender)}</span>
                ${providerBadge}
                <span class="nova-timestamp">${new Date().toLocaleTimeString()}</span>
            </div>
            <div class="nova-msg-content">
                ${NovaAI.formatMessage(content)}
            </div>
        `;
        
        chat.elements.messages.appendChild(messageEl);
        NovaAI.scrollToBottom(chat);
        
        return messageEl;
    };
    
    /**
     * Nachricht formatieren
     */
    NovaAI.formatMessage = function(content) {
        return NovaAI.escapeHtml(content)
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>');
    };
    
    /**
     * Nach unten scrollen
     */
    NovaAI.scrollToBottom = function(chat) {
        chat.elements.messages.scrollTo({
            top: chat.elements.messages.scrollHeight,
            behavior: 'smooth'
        });
    };
    
    /**
     * Nachricht entfernen
     */
    NovaAI.removeMessage = function(messageEl) {
        if (messageEl && messageEl.parentNode) {
            messageEl.parentNode.removeChild(messageEl);
        }
    };
    
    /**
     * Status aktualisieren
     */
    NovaAI.updateStatus = function(chat, status) {
        if (chat.elements.status) {
            chat.elements.status.textContent = status;
        }
    };
    
    /**
     * HTML escapen
     */
    NovaAI.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };
    
    // === GLOBALE HELPER-FUNKTIONEN ===
    
    /**
     * Quick-Prompt einfügen
     */
    window.novaQuickPrompt = function(chatId, prefix) {
        const chat = NovaAI.activeChats[chatId];
        if (chat && chat.elements.input) {
            chat.elements.input.value = prefix;
            chat.elements.input.focus();
            chat.elements.input.setSelectionRange(prefix.length, prefix.length);
        }
    };
    
    /**
     * Nachricht senden (global verfügbar)
     */
    window.novaSendMessage = function(chatId) {
        NovaAI.sendMessage(chatId);
    };
    
    /**
     * Prompt in Zwischenablage kopieren
     */
    NovaAI.copyImagePrompt = function(prompt) {
        navigator.clipboard.writeText(prompt).then(() => {
            // Kurze Erfolgsmeldung
            console.log('Prompt copied to clipboard');
        });
    };
    
    console.log('Nova AI Extended: JavaScript loaded successfully');
    
})(jQuery || window.$ || function() { return arguments[0]; });

// DOM Ready Handler
document.addEventListener('DOMContentLoaded', function() {
    console.log('Nova AI Extended: DOM ready, version', window.nova_ai_config?.version);
});
