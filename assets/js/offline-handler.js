/**
 * Nova AI Brainpool - Offline Mode Handler
 * Manages offline detection and recovery
 */
(function($) {
    'use strict';
    
    // State
    const state = {
        isOnline: navigator.onLine,
        pendingMessages: [],
        offlineMode: false
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Setup network event listeners
        setupNetworkListeners();
        
        // Try to restore pending messages from localStorage
        restorePendingMessages();
    });
    
    /**
     * Setup network status detection
     */
    function setupNetworkListeners() {
        // Listen for online/offline events
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
        
        // Initial check
        checkConnection();
    }
    
    /**
     * Handle online event
     */
    function handleOnline() {
        state.isOnline = true;
        
        // Update UI
        $('.nova-ai-chatbot').removeClass('nova-ai-offline');
        $('.nova-ai-status').text('Ready').removeClass('error');
        
        // Enable inputs
        $('#nova-ai-console-input').prop('disabled', false);
        $('#nova-ai-send').prop('disabled', false);
        
        // Process pending messages
        if (state.pendingMessages.length > 0) {
            processPendingMessages();
        }
        
        // Log status
        console.log('Nova AI: Connection restored');
    }
    
    /**
     * Handle offline event
     */
    function handleOffline() {
        state.isOnline = false;
        
        // Update UI
        $('.nova-ai-chatbot').addClass('nova-ai-offline');
        $('.nova-ai-status').text('Offline').addClass('error');
        
        // Show offline message
        $('.nova-ai-console-output').append(
            '<div class="ai-response error">Network connection lost. Your messages will be saved and sent once you\'re back online.</div>'
        );
        
        // Scroll to bottom
        $('.nova-ai-console-output').scrollTop($('.nova-ai-console-output')[0].scrollHeight);
        
        // Log status
        console.log('Nova AI: Connection lost');
    }
    
    /**
     * Check actual connection to API
     */
    function checkConnection() {
        // Ping the server to check actual connection
        $.ajax({
            url: nova_ai_vars.api_url || window.location.href,
            method: 'HEAD',
            timeout: 3000,
            success: function() {
                if (!state.isOnline) {
                    handleOnline();
                }
            },
            error: function() {
                if (state.isOnline) {
                    handleOffline();
                }
            }
        });
    }
    
    /**
     * Save message to be sent when connection is restored
     */
    function saveMessageForLater(message) {
        // Add to pending messages
        state.pendingMessages.push({
            message: message,
            timestamp: new Date().toISOString()
        });
        
        // Save to localStorage for persistence
        try {
            localStorage.setItem('nova_ai_pending_messages', JSON.stringify(state.pendingMessages));
        } catch (e) {
            console.error('Nova AI: Could not save pending messages to localStorage', e);
        }
        
        // Show pending status
        $('.nova-ai-console-output').append(
            '<div class="ai-response pending">Message saved and will be sent when connection is restored.</div>'
        );
        
        // Scroll to bottom
        $('.nova-ai-console-output').scrollTop($('.nova-ai-console-output')[0].scrollHeight);
    }
    
    /**
     * Restore pending messages from localStorage
     */
    function restorePendingMessages() {
        try {
            const savedMessages = localStorage.getItem('nova_ai_pending_messages');
            if (savedMessages) {
                state.pendingMessages = JSON.parse(savedMessages);
                
                // Check if we have pending messages and we're online
                if (state.pendingMessages.length > 0 && state.isOnline) {
                    // Notify user
                    $('.nova-ai-console-output').append(
                        '<div class="ai-response">Found ' + state.pendingMessages.length + ' unsent message(s). Sending now...</div>'
                    );
                    
                    // Process messages
                    processPendingMessages();
                }
            }
        } catch (e) {
            console.error('Nova AI: Could not restore pending messages from localStorage', e);
        }
    }
    
    /**
     * Process pending messages
     */
    function processPendingMessages() {
        if (state.pendingMessages.length === 0 || !state.isOnline) return;
        
        // Get first message
        const pendingMessage = state.pendingMessages.shift();
        
        // Display message as user input
        $('.nova-ai-console-output').append(
            '<div class="user-input">> ' + pendingMessage.message.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>'
        );
        
        // Remove pending status
        $('.ai-response.pending').remove();
        
        // Show loading indicator
        $('.nova-ai-console-output').append('<div class="ai-response loading">[Sending pending message...]</div>');
        
        // Send to API
        $.ajax({
            url: nova_ai_vars.api_url,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ 
                prompt: pendingMessage.message,
                conversation_id: nova_ai_vars.conversation_id || ''
            }),
            success: function(response) {
                // Remove loading indicator
                $('.loading', $('.nova-ai-console-output')).remove();
                
                if (response && response.reply) {
                    // Add AI response to chat
                    $('.nova-ai-console-output').append(
                        '<div class="ai-response">' + formatResponse(response.reply) + '</div>'
                    );
                } else {
                    // Error handling
                    $('.nova-ai-console-output').append(
                        '<div class="ai-response error">[Error: Invalid response format]</div>'
                    );
                }
                
                // Save remaining messages
                try {
                    localStorage.setItem('nova_ai_pending_messages', JSON.stringify(state.pendingMessages));
                } catch (e) {
                    console.error('Nova AI: Could not save pending messages to localStorage', e);
                }
                
                // Process next message
                if (state.pendingMessages.length > 0) {
                    setTimeout(processPendingMessages, 1000); // Wait 1 second between messages
                }
            },
            error: function() {
                // Error handling
                $('.loading', $('.nova-ai-console-output')).remove();
                $('.nova-ai-console-output').append(
                    '<div class="ai-response error">[Error sending message. Will retry later.]</div>'
                );
                
                // Put the message back at the beginning of the queue
                state.pendingMessages.unshift(pendingMessage);
                
                // Save to localStorage
                try {
                    localStorage.setItem('nova_ai_pending_messages', JSON.stringify(state.pendingMessages));
                } catch (e) {
                    console.error('Nova AI: Could not save pending messages to localStorage', e);
                }
            },
            complete: function() {
                // Scroll to bottom
                $('.nova-ai-console-output').scrollTop($('.nova-ai-console-output')[0].scrollHeight);
            }
        });
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
    
    // Expose methods for other scripts
    window.NovaAIOfflineHandler = {
        saveMessageForLater: saveMessageForLater,
        isOnline: function() { return state.isOnline; },
        checkConnection: checkConnection
    };
    
})(jQuery);
