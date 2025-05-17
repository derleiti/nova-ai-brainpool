/**
 * Nova AI Brainpool - Chat Frontend
 * Optimized for performance and error handling
 */
(function($) {
    'use strict';
    
    // Chat state
    const state = {
        isInitialized: false,
        isPending: false,
        inputHistory: [],
        historyPosition: 0,
        connectionRetries: 0,
        maxRetries: 3
    };
    
    // DOM elements
    let $chat, $output, $input, $send, $status;
    
    // Config
    const config = {
        apiUrl: '',
        conversation_id: '',
        placeholderText: '> Type your message...',
        welcomeMessage: '',
        theme: 'terminal',
        introText: 'Welcome to Nova AI! I\'m here to help you with your questions.'
    };
    
    /**
     * Initialize the chat interface
     */
    function init() {
        if (state.isInitialized) return;
        
        // Get chat container
        $chat = $('#nova-ai-chatbot');
        if (!$chat.length) return;
        
        // Load configuration
        config.apiUrl = $chat.data('api-url') || '';
        config.theme = nova_ai_vars.theme || 'terminal';
        config.placeholderText = nova_ai_vars.placeholder || '> Type your message...';
        config.conversation_id = nova_ai_vars.conversation_id || '';
        config.welcomeMessage = nova_ai_vars.welcome || '';
        
        // Create chat structure
        $chat.html(`
            <div class="nova-ai-console-header">
                <span class="nova-ai-title">Nova AI Console</span>
                <div class="nova-ai-status">Ready</div>
            </div>
            <div class="nova-ai-console-output"></div>
            <div class="nova-ai-console-input-area">
                <textarea id="nova-ai-console-input" placeholder="${config.placeholderText}" rows="1"></textarea>
                <button id="nova-ai-send">Send</button>
            </div>
        `);
        
        // Cache DOM elements
        $output = $('.nova-ai-console-output', $chat);
        $input = $('#nova-ai-console-input', $chat);
        $send = $('#nova-ai-send', $chat);
        $status = $('.nova-ai-status', $chat);
        
        // Bind events
        bindEvents();
        
        // Add welcome message
        if (config.welcomeMessage) {
            addAIResponse(config.welcomeMessage);
        } else {
            addAIResponse(config.introText);
        }
        
        // Focus on input
        setTimeout(() => $input.focus(), 100);
        
        state.isInitialized = true;
    }
    
    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Auto-resize textarea
        $input.on('input', function() {
            this.style.height = 'auto';
            const newHeight = Math.min(this.scrollHeight, 150); // Max height 150px
            this.style.height = newHeight + 'px';
        });
        
        // Send button click
        $send.on('click', function() {
            sendMessage();
        });
        
        // Textarea key handling
        $input.on('keydown', function(e) {
            // Handle Enter key (without Shift) to send
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
            
            // Arrow up/down for input history
            if (e.key === 'ArrowUp' && state.inputHistory.length > 0) {
                e.preventDefault();
                
                // Move up in history (if not at beginning)
                if (state.historyPosition > 0) {
                    state.historyPosition--;
                    $input.val(state.inputHistory[state.historyPosition]);
                    
                    // Move cursor to end
                    setTimeout(() => {
                        $input[0].selectionStart = $input[0].selectionEnd = $input.val().length;
                    }, 0);
                }
            } else if (e.key === 'ArrowDown' && state.inputHistory.length > 0) {
                e.preventDefault();
                
                // Move down in history or clear if at end
                if (state.historyPosition < state.inputHistory.length - 1) {
                    state.historyPosition++;
                    $input.val(state.inputHistory[state.historyPosition]);
                    
                    // Move cursor to end
                    setTimeout(() => {
                        $input[0].selectionStart = $input[0].selectionEnd = $input.val().length;
                    }, 0);
                } else if (state.historyPosition === state.inputHistory.length - 1) {
                    state.historyPosition++;
                    $input.val('');
                }
            }
        });
        
        // Window resize handler to scroll to bottom
        $(window).on('resize', function() {
            scrollToBottom();
        });
    }
    
    /**
     * Send message to API
     */
    function sendMessage() {
        const message = $input.val().trim();
        if (message.length < 1 || state.isPending) return;
        
        // Save to input history
        state.inputHistory.push(message);
        state.historyPosition = state.inputHistory.length;
        
        // Show user input in chat
        addUserInput(message);
        
        // Clear input field
        $input.val('');
        $input.trigger('input'); // Trigger to resize textarea
        
        // Disable input while processing
        setInputEnabled(false);
        state.isPending = true;
        
        // Show loading indicator
        setStatus('Sending...');
        addLoadingIndicator();
        
        // Send API request
        $.ajax({
            url: config.apiUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ 
                prompt: message,
                conversation_id: config.conversation_id
            }),
            success: function(response) {
                removeLoadingIndicator();
                
                if (response && response.reply) {
                    addAIResponse(response.reply);
                } else {
                    handleApiError('Invalid response format');
                }
            },
            error: function(xhr, status, error) {
                removeLoadingIndicator();
                handleApiError(xhr, status, error);
            },
            complete: function() {
                state.isPending = false;
                setInputEnabled(true);
                $input.focus();
                setStatus('Ready');
            }
        });
    }
    
    /**
     * Handle API error with retry logic
     */
    function handleApiError(xhr, status, error) {
        let errorMessage = 'Connection error';
        
        if (typeof xhr === 'string') {
            // Direct error message
            errorMessage = xhr;
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
            // Error from API
            errorMessage = xhr.responseJSON.message;
        } else if (xhr.statusText) {
            // HTTP error
            errorMessage = xhr.statusText;
        }
        
        // Check for retry (only for network-related errors)
        if ((status === 'timeout' || status === 'error' || error === 'timeout') && state.connectionRetries < state.maxRetries) {
            state.connectionRetries++;
            addAIResponse(`Connection error. Retrying (${state.connectionRetries}/${state.maxRetries})...`);
            
            // Retry after delay
            setTimeout(function() {
                const lastMessage = state.inputHistory[state.inputHistory.length - 1];
                if (lastMessage) {
                    // Reset the input to retry
                    $input.val(lastMessage);
                    sendMessage();
                }
            }, 2000);
        } else {
            // Show error message
            state.connectionRetries = 0;
            addAIResponse(`Error: ${errorMessage}`, true);
        }
    }
    
    /**
     * Add user input to chat output
     */
    function addUserInput(text) {
        // Escape HTML to prevent XSS
        const safeText = $('<div>').text(text).html();
        $output.append(`<div class="user-input">> ${safeText}</div>`);
        scrollToBottom();
    }
    
    /**
     * Add AI response to chat output
     */
    function addAIResponse(text, isError = false) {
        // Process markdown-like format
        const formattedText = formatResponse(text);
        const className = isError ? 'ai-response error' : 'ai-response';
        $output.append(`<div class="${className}">${formattedText}</div>`);
        scrollToBottom();
    }
    
    /**
     * Add loading indicator to chat
     */
    function addLoadingIndicator() {
        $output.append('<div class="ai-response loading">[Waiting for response...]</div>');
        scrollToBottom();
    }
    
    /**
     * Remove loading indicator from chat
     */
    function removeLoadingIndicator() {
        $('.loading', $output).remove();
    }
    
    /**
     * Format AI response text with markdown-like syntax
     */
    function formatResponse(text) {
        // Escape HTML first to prevent XSS
        let safeText = $('<div>').text(text).html();
        
        // Apply formatting
        safeText = safeText
            .replace(/\n\n/g, '<br><br>')
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`(.*?)`/g, '<code>$1</code>')
            .replace(/^# (.*?)$/gm, '<h3>$1</h3>')
            .replace(/^## (.*?)$/gm, '<h4>$1</h4>');
        
        return safeText;
    }
    
    /**
     * Set status message
     */
    function setStatus(message, isError = false) {
        $status.text(message);
        
        if (isError) {
            $status.addClass('error');
        } else {
            $status.removeClass('error');
        }
    }
    
    /**
     * Enable or disable input
     */
    function setInputEnabled(enabled) {
        $input.prop('disabled', !enabled);
        $send.prop('disabled', !enabled);
    }
    
    /**
     * Scroll output to bottom
     */
    function scrollToBottom() {
        $output.stop().animate({ scrollTop: $output[0].scrollHeight }, 100);
    }
    
    // Initialize when document is ready
    $(document).ready(init);
    
})(jQuery);
