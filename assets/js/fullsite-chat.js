k/**
 * Nova AI Fullsite Chat JavaScript
 * Handles the interaction with the chat interface and API communication.
 */

(function($) {
    'use strict';
    
    // Chat state
    const state = {
        isOpen: false,
        isMinimized: false,
        isTyping: false,
        messages: [],
        chatHistory: {
            messages: [],
            lastInteraction: null
        }
    };
    
    // DOM elements
    let $chat;
    let $button;
    let $container;
    let $messages;
    let $input;
    let $send;
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Cache DOM elements
        $chat = $('#nova-ai-fullsite-chat');
        $button = $('.nova-ai-chat-button');
        $container = $('.nova-ai-chat-container');
        $messages = $('.nova-ai-chat-messages');
        $input = $('.nova-ai-chat-input');
        $send = $('.nova-ai-chat-send');
        
        // Bind event handlers
        bindEvents();
        
        // Load chat history from local storage
        loadChatHistory();
        
        // Auto-expand textarea as user types
        setupTextareaAutoResize();
        
        // Add initial messages
        if (state.messages.length === 0) {
            // Only add welcome message if no history exists
            const welcomeMessage = nova_ai_chat_settings.welcome_message || "Hi! I'm Nova AI. How can I help you?";
            addAIMessage(welcomeMessage);
        } else {
            // Restore messages from history
            renderMessages();
        }
    });
    
    // Bind event handlers
    function bindEvents() {
        // Toggle chat with button
        $button.on('click', toggleChat);
        
        // Close chat
        $('.nova-ai-close').on('click', closeChat);
        
        // Minimize chat
        $('.nova-ai-minimize').on('click', minimizeChat);
        
        // Send message on button click
        $send.on('click', sendMessage);
        
        // Send message on Enter key (but allow Shift+Enter for new line)
        $input.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Enable/disable send button based on input
        $input.on('input', function() {
            $send.prop('disabled', $input.val().trim() === '');
        });
    }
    
    // Toggle chat open/closed
    function toggleChat() {
        if (state.isOpen) {
            closeChat();
        } else {
            openChat();
        }
    }
    
    // Open the chat
    function openChat() {
        state.isOpen = true;
        state.isMinimized = false;
        $chat.addClass('active');
        setTimeout(() => $input.focus(), 300);
        updateLocalStorage();
    }
    
    // Close the chat
    function closeChat() {
        state.isOpen = false;
        $chat.removeClass('active');
        updateLocalStorage();
    }
    
    // Minimize the chat
    function minimizeChat() {
        state.isMinimized = true;
        closeChat();
        updateLocalStorage();
    }
    
    // Send message to AI
    function sendMessage() {
        const message = $input.val().trim();
        if (message === '') return;
        
        // Add user message to chat
        addUserMessage(message);
        
        // Clear input
        $input.val('');
        $input.trigger('input');
        
        // Show typing indicator
        showTypingIndicator();
        
        // Send message to API
        sendMessageToAPI(message);
    }
    
    // Add a user message to the chat
    function addUserMessage(text) {
        const message = {
            role: 'user',
            content: text,
            timestamp: new Date().toISOString()
        };
        
        state.messages.push(message);
        
        const $messageElement = $(`
            <div class="nova-ai-message nova-ai-message-user">
                <div class="nova-ai-message-content">${escapeHtml(text)}</div>
            </div>
        `);
        
        $messages.append($messageElement);
        scrollToBottom();
        updateLocalStorage();
    }
    
    // Add an AI message to the chat
    function addAIMessage(text) {
        const message = {
            role: 'ai',
            content: text,
            timestamp: new Date().toISOString()
        };
        
        state.messages.push(message);
        
        const $messageElement = $(`
            <div class="nova-ai-message nova-ai-message-ai">
                <div class="nova-ai-message-avatar"></div>
                <div class="nova-ai-message-content">${formatAIMessage(text)}</div>
            </div>
        `);
        
        $messages.append($messageElement);
        scrollToBottom();
        updateLocalStorage();
    }
    
    // Show typing indicator
    function showTypingIndicator() {
        state.isTyping = true;
        
        const $typingIndicator = $(`
            <div class="nova-ai-message nova-ai-message-ai nova-ai-typing">
                <div class="nova-ai-message-avatar"></div>
                <div class="nova-ai-typing-indicator">
                    <div class="nova-ai-typing-dot"></div>
                    <div class="nova-ai-typing-dot"></div>
                    <div class="nova-ai-typing-dot"></div>
                </div>
            </div>
        `);
        
        $messages.append($typingIndicator);
        scrollToBottom();
    }
    
    // Hide typing indicator
    function hideTypingIndicator() {
        state.isTyping = false;
        $('.nova-ai-typing').remove();
    }
    
    // Send message to API
    function sendMessageToAPI(message) {
        // Disable the send button while processing
        $send.prop('disabled', true);
        
        // Create the conversation history to send
        const conversationHistory = state.messages
            .slice(-10) // Limit to last 10 messages to prevent context overflow
            .map(msg => {
                return {
                    role: msg.role === 'user' ? 'user' : 'assistant',
                    content: msg.content
                };
            });
        
        // Make API request
        $.ajax({
            url: nova_ai_chat_settings.api_url,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nova_ai_chat_settings.nonce
            },
            data: JSON.stringify({
                prompt: message,
                conversation: conversationHistory
            }),
            success: function(response) {
                // Hide typing indicator
                hideTypingIndicator();
                
                // Add AI response to chat
                if (response && response.reply) {
                    addAIMessage(response.reply);
                    
                    // Update chat stats via AJAX
                    updateChatStats();
                } else {
                    addAIMessage("I'm sorry, I couldn't generate a response. Please try again.");
                }
                
                // Re-enable the send button
                $send.prop('disabled', false);
            },
            error: function(xhr, status, error) {
                // Hide typing indicator
                hideTypingIndicator();
                
                // Add error message
                let errorMessage = "I'm having trouble connecting to my brain. Please try again in a moment.";
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                addAIMessage(errorMessage);
                
                // Log error
                console.error('Nova AI Chat Error:', error);
                
                // Re-enable the send button
                $send.prop('disabled', false);
            }
        });
    }
    
    // Update chat statistics
    function updateChatStats() {
        $.ajax({
            url: nova_ai_chat_settings.api_url + '/stats',
            method: 'POST',
            headers: {
                'X-WP-Nonce': nova_ai_chat_settings.nonce
            },
            error: function(xhr, status, error) {
                // Silently fail - this is just for stats
                console.error('Nova AI Stats Error:', error);
            }
        });
    }
    
    // Format AI message (handle markdown, code, etc.)
    function formatAIMessage(text) {
        // Simple markdown-like formatting
        let formatted = escapeHtml(text);
        
        // Bold
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Italic
        formatted = formatted.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        // Code
        formatted = formatted.replace(/`(.*?)`/g, '<code>$1</code>');
        
        // Links
        formatted = formatted.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
        
        // Line breaks
        formatted = formatted.replace(/\n/g, '<br>');
        
        return formatted;
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Scroll to bottom of chat
    function scrollToBottom() {
        $messages.scrollTop($messages[0].scrollHeight);
    }
    
    // Setup textarea auto-resize
    function setupTextareaAutoResize() {
        $input.on('input', function() {
            this.style.height = 'auto';
            const newHeight = Math.min(this.scrollHeight, 120);
            this.style.height = newHeight + 'px';
        });
    }
    
    // Save chat state to local storage
    function updateLocalStorage() {
        const chatState = {
            isOpen: state.isOpen,
            isMinimized: state.isMinimized,
            messages: state.messages,
            lastInteraction: new Date().toISOString()
        };
        
        try {
            localStorage.setItem('nova_ai_chat_state', JSON.stringify(chatState));
        } catch (e) {
            console.error('Nova AI: Could not save to localStorage', e);
        }
    }
    
    // Load chat state from local storage
    function loadChatHistory() {
        try {
            const savedState = localStorage.getItem('nova_ai_chat_state');
            
            if (savedState) {
                const chatState = JSON.parse(savedState);
                
                // Check if chat history is from today
                const lastInteraction = new Date(chatState.lastInteraction);
                const today = new Date();
                const isToday = lastInteraction.toDateString() === today.toDateString();
                
                if (isToday) {
                    // Restore chat state
                    state.isOpen = chatState.isOpen || false;
                    state.isMinimized = chatState.isMinimized || false;
                    state.messages = chatState.messages || [];
                    
                    // Apply open state to UI if needed
                    if (state.isOpen && !state.isMinimized) {
                        $chat.addClass('active');
                    }
                }
            }
        } catch (e) {
            console.error('Nova AI: Could not load from localStorage', e);
        }
    }
    
    // Render messages from state
    function renderMessages() {
        $messages.empty();
        
        state.messages.forEach(message => {
            if (message.role === 'user') {
                const $messageElement = $(`
                    <div class="nova-ai-message nova-ai-message-user">
                        <div class="nova-ai-message-content">${escapeHtml(message.content)}</div>
                    </div>
                `);
                $messages.append($messageElement);
            } else {
                const $messageElement = $(`
                    <div class="nova-ai-message nova-ai-message-ai">
                        <div class="nova-ai-message-avatar"></div>
                        <div class="nova-ai-message-content">${formatAIMessage(message.content)}</div>
                    </div>
                `);
                $messages.append($messageElement);
            }
        });
        
        scrollToBottom();
    }
    
})(jQuery);
