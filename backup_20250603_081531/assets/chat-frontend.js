/**
 * Nova AI Chat Frontend
 * 
 * Handles chat interface, image generation, and crawling functionality
 */

(function($) {
    'use strict';

    class NovaAIChat {
        constructor(container) {
            this.container = $(container);
            this.messagesContainer = this.container.find('#nova-ai-messages');
            this.messageInput = this.container.find('.nova-ai-message-input');
            this.sendButton = this.container.find('.nova-ai-send-btn');
            this.crawlerPanel = this.container.find('.nova-ai-crawler-panel');
            this.imagePanel = this.container.find('.nova-ai-image-panel');
            
            this.conversationId = this.generateConversationId();
            this.isProcessing = false;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.setupAutoResize();
            this.displayWelcomeMessage();
        }

        bindEvents() {
            // Send message events
            this.sendButton.on('click', () => this.sendMessage());
            this.messageInput.on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Panel toggles
            this.container.find('.nova-ai-crawler-toggle').on('click', () => {
                this.crawlerPanel.slideToggle();
            });

            this.container.find('.nova-ai-image-toggle').on('click', () => {
                this.imagePanel.slideToggle();
            });

            // Crawler functionality
            this.container.find('.nova-ai-crawl-btn').on('click', () => this.crawlUrl());
            this.container.find('.nova-ai-crawl-url').on('keypress', (e) => {
                if (e.which === 13) {
                    this.crawlUrl();
                }
            });

            // Image generation
            this.container.find('.nova-ai-generate-btn').on('click', () => this.generateImage());
            this.container.find('.nova-ai-image-prompt').on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.generateImage();
                }
            });
        }

        setupAutoResize() {
            this.messageInput.on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }

        generateConversationId() {
            return 'conv_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        displayWelcomeMessage() {
            const welcomeMessage = {
                role: 'assistant',
                content: nova_ai_ajax.strings.welcome || 'Hello! I\'m Nova AI. How can I help you today?',
                timestamp: new Date()
            };
            this.addMessage(welcomeMessage);
        }

        async sendMessage() {
            const message = this.messageInput.val().trim();
            
            if (!message || this.isProcessing) {
                return;
            }

            // Add user message to chat
            this.addMessage({
                role: 'user',
                content: message,
                timestamp: new Date()
            });

            // Clear input
            this.messageInput.val('').trigger('input');

            // Show typing indicator
            const typingId = this.addTypingIndicator();

            try {
                this.isProcessing = true;
                this.updateSendButton(false);

                const response = await this.callChatAPI(message);

                if (response.success) {
                    // Remove typing indicator
                    this.removeTypingIndicator(typingId);
                    
                    // Add AI response
                    this.addMessage({
                        role: 'assistant',
                        content: response.data.response,
                        timestamp: new Date(),
                        contextUsed: response.data.context_used
                    });

                    // Update conversation ID if provided
                    if (response.data.conversation_id) {
                        this.conversationId = response.data.conversation_id;
                    }
                } else {
                    throw new Error(response.data.message || nova_ai_ajax.strings.error);
                }

            } catch (error) {
                this.removeTypingIndicator(typingId);
                this.addMessage({
                    role: 'error',
                    content: error.message || nova_ai_ajax.strings.error,
                    timestamp: new Date()
                });
            } finally {
                this.isProcessing = false;
                this.updateSendButton(true);
                this.messageInput.focus();
            }
        }

        async callChatAPI(message) {
            const data = {
                action: 'nova_ai_chat',
                nonce: nova_ai_ajax.nonce,
                message: message,
                conversation_id: this.conversationId,
                use_crawled_data: true
            };

            const response = await $.ajax({
                url: nova_ai_ajax.ajax_url,
                method: 'POST',
                data: data,
                timeout: 60000 // 60 seconds
            });

            return response;
        }

        addMessage(messageData) {
            const messageId = 'msg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            const messageElement = this.createMessageElement(messageData, messageId);
            
            this.messagesContainer.append(messageElement);
            this.scrollToBottom();
            
            // Animate in
            messageElement.hide().fadeIn(300);
            
            return messageId;
        }

        createMessageElement(messageData, messageId) {
            const timestamp = this.formatTimestamp(messageData.timestamp);
            let messageClass = 'nova-ai-message';
            let roleClass = 'nova-ai-' + messageData.role;
            let avatar = '';
            let username = '';
            let content = messageData.content;

            switch (messageData.role) {
                case 'user':
                    username = 'You';
                    avatar = '<div class="nova-ai-avatar nova-ai-user-avatar">👤</div>';
                    break;
                case 'assistant':
                    username = 'Nova AI';
                    avatar = '<div class="nova-ai-avatar nova-ai-ai-avatar">🤖</div>';
                    if (messageData.contextUsed) {
                        content += '<div class="nova-ai-context-indicator">📚 Used crawled knowledge</div>';
                    }
                    break;
                case 'error':
                    username = 'System';
                    avatar = '<div class="nova-ai-avatar nova-ai-error-avatar">⚠️</div>';
                    roleClass = 'nova-ai-error';
                    break;
            }

            // Format content (basic markdown support)
            content = this.formatContent(content);

            return $(`
                <div id="${messageId}" class="${messageClass} ${roleClass}">
                    <div class="nova-ai-message-header">
                        ${avatar}
                        <div class="nova-ai-message-meta">
                            <span class="nova-ai-username">${username}</span>
                            <span class="nova-ai-timestamp">${timestamp}</span>
                        </div>
                    </div>
                    <div class="nova-ai-message-content">${content}</div>
                </div>
            `);
        }

        formatContent(content) {
            // Basic markdown formatting
            content = content
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/`(.*?)`/g, '<code>$1</code>')
                .replace(/\n/g, '<br>');

            // Code blocks
            content = content.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');

            return content;
        }

        formatTimestamp(timestamp) {
            const now = new Date();
            const diff = now.getTime() - timestamp.getTime();
            const seconds = Math.floor(diff / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);

            if (seconds < 60) {
                return 'just now';
            } else if (minutes < 60) {
                return minutes + 'm ago';
            } else if (hours < 24) {
                return hours + 'h ago';
            } else {
                return timestamp.toLocaleDateString();
            }
        }

        addTypingIndicator() {
            const typingId = 'typing_' + Date.now();
            const typingElement = $(`
                <div id="${typingId}" class="nova-ai-message nova-ai-assistant nova-ai-typing">
                    <div class="nova-ai-message-header">
                        <div class="nova-ai-avatar nova-ai-ai-avatar">🤖</div>
                        <div class="nova-ai-message-meta">
                            <span class="nova-ai-username">Nova AI</span>
                            <span class="nova-ai-timestamp">now</span>
                        </div>
                    </div>
                    <div class="nova-ai-message-content">
                        <div class="nova-ai-typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span class="nova-ai-typing-text">${nova_ai_ajax.strings.thinking}</span>
                    </div>
                </div>
            `);

            this.messagesContainer.append(typingElement);
            this.scrollToBottom();

            return typingId;
        }

        removeTypingIndicator(typingId) {
            $(`#${typingId}`).fadeOut(200, function() {
                $(this).remove();
            });
        }

        updateSendButton(enabled) {
            this.sendButton.prop('disabled', !enabled);
            if (enabled) {
                this.sendButton.text(this.sendButton.data('original-text') || 'Send');
            } else {
                if (!this.sendButton.data('original-text')) {
                    this.sendButton.data('original-text', this.sendButton.text());
                }
                this.sendButton.text('...');
            }
        }

        scrollToBottom() {
            this.messagesContainer.animate({
                scrollTop: this.messagesContainer[0].scrollHeight
            }, 300);
        }

        // Crawler functionality
        async crawlUrl() {
            const url = this.container.find('.nova-ai-crawl-url').val().trim();
            const statusDiv = this.container.find('.nova-ai-crawl-status');
            
            if (!url) {
                this.showCrawlStatus('Please enter a URL', 'error');
                return;
            }

            try {
                this.showCrawlStatus(nova_ai_ajax.strings.crawling, 'info');

                const response = await $.ajax({
                    url: nova_ai_ajax.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'nova_ai_crawl_url',
                        nonce: nova_ai_ajax.nonce,
                        url: url
                    }
                });

                if (response.success) {
                    this.showCrawlStatus(`Successfully crawled: ${response.data.title}`, 'success');
                    this.container.find('.nova-ai-crawl-url').val('');
                } else {
                    this.showCrawlStatus(`Error: ${response.data.message}`, 'error');
                }

            } catch (error) {
                this.showCrawlStatus('Crawling failed', 'error');
            }
        }

        showCrawlStatus(message, type) {
            const statusDiv = this.container.find('.nova-ai-crawl-status');
            statusDiv.removeClass('success error info').addClass(type).text(message).show();
            
            if (type === 'success') {
                setTimeout(() => {
                    statusDiv.fadeOut();
                }, 3000);
            }
        }

        // Image generation functionality
        async generateImage() {
            const prompt = this.container.find('.nova-ai-image-prompt').val().trim();
            const style = this.container.find('.nova-ai-image-style').val();
            const size = this.container.find('.nova-ai-image-size').val();
            const resultDiv = this.container.find('.nova-ai-image-result');
            
            if (!prompt) {
                this.showImageStatus('Please enter an image description', 'error');
                return;
            }

            try {
                this.showImageStatus(nova_ai_ajax.strings.generating_image, 'info');

                const [width, height] = size.split('x').map(Number);

                const response = await $.ajax({
                    url: nova_ai_ajax.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'nova_ai_generate_image',
                        nonce: nova_ai_ajax.nonce,
                        prompt: prompt,
                        style: style,
                        width: width,
                        height: height
                    },
                    timeout: 120000 // 2 minutes for image generation
                });

                if (response.success && response.data.image_url) {
                    this.displayGeneratedImage(response.data.image_url, prompt);
                    this.container.find('.nova-ai-image-prompt').val('');
                } else {
                    this.showImageStatus(`Error: ${response.data.message}`, 'error');
                }

            } catch (error) {
                this.showImageStatus('Image generation failed', 'error');
            }
        }

        displayGeneratedImage(imageUrl, prompt) {
            const resultDiv = this.container.find('.nova-ai-image-result');
            const imageElement = $(`
                <div class="nova-ai-generated-image">
                    <img src="${imageUrl}" alt="${prompt}" onclick="window.open('${imageUrl}', '_blank')">
                    <div class="nova-ai-image-info">
                        <p><strong>Prompt:</strong> ${prompt}</p>
                        <button class="nova-ai-download-btn" onclick="window.open('${imageUrl}', '_blank')">
                            Download Image
                        </button>
                    </div>
                </div>
            `);
            
            resultDiv.empty().append(imageElement).show();
        }

        showImageStatus(message, type) {
            const resultDiv = this.container.find('.nova-ai-image-result');
            const statusElement = $(`<div class="nova-ai-status ${type}">${message}</div>`);
            resultDiv.empty().append(statusElement).show();
            
            if (type === 'success') {
                setTimeout(() => {
                    statusElement.fadeOut();
                }, 3000);
            }
        }

        // Export chat functionality
        exportChat() {
            const messages = [];
            this.messagesContainer.find('.nova-ai-message').each(function() {
                const $this = $(this);
                const role = $this.hasClass('nova-ai-user') ? 'user' : 
                            $this.hasClass('nova-ai-assistant') ? 'assistant' : 'system';
                const content = $this.find('.nova-ai-message-content').text();
                const timestamp = $this.find('.nova-ai-timestamp').text();
                
                messages.push({ role, content, timestamp });
            });

            const chatData = {
                conversation_id: this.conversationId,
                messages: messages,
                exported_at: new Date().toISOString()
            };

            const blob = new Blob([JSON.stringify(chatData, null, 2)], {
                type: 'application/json'
            });
            
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `nova-ai-chat-${this.conversationId}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        // Clear chat
        clearChat() {
            this.messagesContainer.empty();
            this.conversationId = this.generateConversationId();
            this.displayWelcomeMessage();
        }
    }

    // Initialize chat instances
    $(document).ready(function() {
        $('.nova-ai-chat-container').each(function() {
            new NovaAIChat(this);
        });

        // Global keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + E to export chat
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                const chatInstance = $('.nova-ai-chat-container').data('novaAIChat');
                if (chatInstance) {
                    chatInstance.exportChat();
                }
            }
        });
    });

    // Image generator shortcode support
    class NovaAIImageGenerator {
        constructor(container) {
            this.container = $(container);
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            this.container.find('.nova-ai-generate-btn').on('click', () => this.generateImage());
            this.container.find('.nova-ai-image-prompt').on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.generateImage();
                }
            });
        }

        async generateImage() {
            const prompt = this.container.find('.nova-ai-image-prompt').val().trim();
            const style = this.container.find('.nova-ai-image-style').val();
            const size = this.container.find('.nova-ai-image-size').val();
            const button = this.container.find('.nova-ai-generate-btn');
            const resultDiv = this.container.find('.nova-ai-image-result');
            
            if (!prompt) {
                this.showStatus('Please enter an image description', 'error');
                return;
            }

            const originalText = button.text();
            
            try {
                button.prop('disabled', true).text('Generating...');
                this.showStatus('Generating your image...', 'info');

                const [width, height] = size.split('x').map(Number);

                const response = await $.ajax({
                    url: nova_ai_ajax.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'nova_ai_generate_image',
                        nonce: nova_ai_ajax.nonce,
                        prompt: prompt,
                        style: style,
                        width: width,
                        height: height
                    },
                    timeout: 120000
                });

                if (response.success && response.data.image_url) {
                    this.displayImage(response.data.image_url, prompt, style, size);
                } else {
                    this.showStatus(`Error: ${response.data.message}`, 'error');
                }

            } catch (error) {
                this.showStatus('Image generation failed. Please try again.', 'error');
            } finally {
                button.prop('disabled', false).text(originalText);
            }
        }

        displayImage(imageUrl, prompt, style, size) {
            const resultDiv = this.container.find('.nova-ai-image-result');
            const imageElement = $(`
                <div class="nova-ai-image-wrapper">
                    <div class="nova-ai-image-container">
                        <img src="${imageUrl}" alt="${prompt}" class="nova-ai-generated-img">
                        <div class="nova-ai-image-overlay">
                            <button class="nova-ai-image-action" onclick="window.open('${imageUrl}', '_blank')" title="View Full Size">
                                🔍
                            </button>
                            <button class="nova-ai-image-action" onclick="window.open('${imageUrl}', '_blank')" title="Download">
                                💾
                            </button>
                        </div>
                    </div>
                    <div class="nova-ai-image-meta">
                        <div class="nova-ai-image-prompt"><strong>Prompt:</strong> ${prompt}</div>
                        <div class="nova-ai-image-details">
                            <span><strong>Style:</strong> ${style}</span>
                            <span><strong>Size:</strong> ${size}</span>
                        </div>
                    </div>
                </div>
            `);
            
            resultDiv.empty().append(imageElement).show();
            
            // Animate in
            imageElement.hide().fadeIn(500);
        }

        showStatus(message, type) {
            const resultDiv = this.container.find('.nova-ai-image-result');
            const statusElement = $(`<div class="nova-ai-status nova-ai-status-${type}">${message}</div>`);
            resultDiv.empty().append(statusElement).show();
        }
    }

    // Initialize image generator instances
    $(document).ready(function() {
        $('.nova-ai-image-generator').each(function() {
            new NovaAIImageGenerator(this);
        });
    });

    // Utility functions
    window.NovaAI = {
        exportChat: function(containerId) {
            const container = $(containerId || '.nova-ai-chat-container').first();
            const chatInstance = container.data('novaAIChat');
            if (chatInstance) {
                chatInstance.exportChat();
            }
        },
        
        clearChat: function(containerId) {
            const container = $(containerId || '.nova-ai-chat-container').first();
            const chatInstance = container.data('novaAIChat');
            if (chatInstance) {
                chatInstance.clearChat();
            }
        }
    };

})(jQuery);
