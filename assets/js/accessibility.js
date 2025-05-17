/**
 * Nova AI Brainpool - Accessibility Enhancements
 * Adds keyboard navigation, screen reader support, and other accessibility features
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Find all Nova AI chat instances
        const chatContainers = $('.nova-ai-chatbot');
        
        if (chatContainers.length === 0) return;
        
        // Add accessibility attributes to each instance
        chatContainers.each(function() {
            makeAccessible($(this));
        });
        
        // Set up automatic dark mode detection
        setupDarkModeDetection();
    });
    
    /**
     * Add accessibility attributes and features to a chat container
     */
    function makeAccessible($container) {
        // Assign proper ARIA roles
        $container.attr({
            'role': 'region',
            'aria-label': 'Nova AI Chat Interface'
        });
        
        // Add title for screen readers
        const $header = $('.nova-ai-console-header', $container);
        if ($header.length) {
            $header.attr('role', 'banner');
            $('.nova-ai-title', $header).attr('id', 'nova-chat-title-' + generateUniqueId());
            $container.attr('aria-labelledby', $('.nova-ai-title', $header).attr('id'));
        }
        
        // Make output area accessible
        const $output = $('.nova-ai-console-output', $container);
        if ($output.length) {
            $output.attr({
                'role': 'log',
                'aria-live': 'polite',
                'aria-relevant': 'additions',
                'aria-atomic': 'false',
                'id': 'nova-chat-output-' + generateUniqueId()
            });
        }
        
        // Make input accessible
        const $input = $('#nova-ai-console-input', $container);
        if ($input.length) {
            $input.attr({
                'aria-label': 'Message to Nova AI',
                'aria-multiline': 'true',
                'role': 'textbox',
                'aria-controls': $output.attr('id')
            });
        }
        
        // Make send button accessible
        const $sendButton = $('#nova-ai-send', $container);
        if ($sendButton.length) {
            $sendButton.attr({
                'aria-label': 'Send message',
                'type': 'button'
            });
        }
        
        // Add keyboard shortcuts
        setupKeyboardShortcuts($container);
    }
    
    /**
     * Add keyboard shortcuts
     */
    function setupKeyboardShortcuts($container) {
        // Add keyboard shortcut for activating the chat interface
        $(window).on('keydown', function(e) {
            // Alt+Shift+N to focus on input
            if (e.altKey && e.shiftKey && e.key === 'N') {
                e.preventDefault();
                $('#nova-ai-console-input', $container).focus();
            }
            
            // Escape key in input to blur
            if (e.key === 'Escape' && document.activeElement === $('#nova-ai-console-input', $container)[0]) {
                document.activeElement.blur();
            }
        });
    }
    
    /**
     * Setup dark mode detection
     */
    function setupDarkModeDetection() {
        // Only apply if theme is set to 'auto'
        if (typeof nova_ai_vars !== 'undefined' && nova_ai_vars.theme === 'auto') {
            const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Apply theme based on current preference
            applyTheme(darkModeMediaQuery.matches ? 'dark' : 'light');
            
            // Listen for changes
            darkModeMediaQuery.addEventListener('change', function(e) {
                applyTheme(e.matches ? 'dark' : 'light');
            });
        }
    }
    
    /**
     * Apply theme class
     */
    function applyTheme(theme) {
        $('.nova-ai-chatbot')
            .removeClass('nova-theme-dark nova-theme-light nova-theme-terminal')
            .addClass('nova-theme-' + theme);
    }
    
    /**
     * Generate a unique ID for accessibility
     */
    function generateUniqueId() {
        return Math.random().toString(36).substring(2, 9);
    }
    
})(jQuery);
