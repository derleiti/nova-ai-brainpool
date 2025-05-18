/**
 * Nova AI Brainpool Admin JavaScript
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Tab navigation
        $('.nova-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            $('.nova-tab').removeClass('active');
            $(this).addClass('active');
            
            $('.nova-tab-content').removeClass('active');
            $(target).addClass('active');
            
            // Save active tab to localStorage
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('nova_ai_active_tab', target);
            }
        });
        
        // Restore active tab from localStorage
        if (typeof(Storage) !== "undefined") {
            var activeTab = localStorage.getItem('nova_ai_active_tab');
            if (activeTab) {
                $('.nova-tab[href="' + activeTab + '"]').click();
            }
        }
        
        // API type toggle
        $('#nova_ai_api_type').on('change', function() {
            var apiType = $(this).val();
            
            // Hide all API-specific fields
            $('.api-field').hide();
            
            // Show fields for selected API
            $('.api-' + apiType).show();
        }).trigger('change');
        
        // Theme selection
        $('input[name="nova_ai_theme_style"]').on('change', function() {
            // Highlight the selected theme
            $('.theme-preview').removeClass('theme-selected');
            $(this).closest('label').find('.theme-preview').addClass('theme-selected');
        });
        
        // Temperature slider
        $('#nova_ai_temperature').on('input', function() {
            $('#temperature-value').text($(this).val());
        });
        
        // Refresh models button
        $('#refresh-models').on('click', function() {
            var button = $(this);
            var originalText = button.text();
            var statusDiv = $('#model-status');
            var apiUrl = $('#nova_ai_api_url').val();
            
            // Disable button and show loading
            button.prop('disabled', true).text('Loading...');
            statusDiv.html('<p><em>Checking available models...</em></p>');
            
            // Make AJAX request
            $.ajax({
                url: nova_ai_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'nova_ai_refresh_models',
                    api_url: apiUrl,
                    _wpnonce: nova_ai_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var modelSelect = $('#nova_ai_model');
                        var currentValue = modelSelect.val();
                        
                        // Clear and rebuild select options
                        modelSelect.empty();
                        
                        $.each(response.data.models, function(id, name) {
                            var selected = (id === currentValue) ? 'selected' : '';
                            modelSelect.append('<option value="' + id + '" ' + selected + '>' + name + '</option>');
                        });
                        
                        statusDiv.html('<p class="nova-status nova-status-success">✓ Found ' + Object.keys(response.data.models).length + ' models</p>');
                    } else {
                        statusDiv.html('<p class="nova-status nova-status-error">✗ Error: ' + response.data + '</p>');
                    }
                },
                error: function() {
                    statusDiv.html('<p class="nova-status nova-status-error">✗ Connection error</p>');
                },
                complete: function() {
                    // Re-enable button and restore text
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Test connection button (prevents form submission)
        $('button[name="nova_ai_test_connection"]').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var originalText = button.text();
            var statusDiv = $('#connection-status');
            var apiType = $('#nova_ai_api_type').val();
            var apiUrl = $('#nova_ai_api_url').val();
            var apiKey = $('#nova_ai_api_key').val();
            var model = $('#nova_ai_model').val();
            
            // Disable button and show loading
            button.prop('disabled', true).text('Testing...');
            statusDiv.html('<p><em>Testing connection...</em></p>');
            
            // Make AJAX request
            $.ajax({
                url: nova_ai_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'nova_ai_test_connection',
                    api_type: apiType,
                    api_url: apiUrl,
                    api_key: apiKey,
                    model: model,
                    _wpnonce: nova_ai_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        statusDiv.html('<p class="nova-status nova-status-success">✓ ' + response.data.message + '</p>');
                    } else {
                        statusDiv.html('<p class="nova-status nova-status-error">✗ ' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    statusDiv.html('<p class="nova-status nova-status-error">✗ Connection error</p>');
                },
                complete: function() {
                    // Re-enable button and restore text
                    button.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Auto-resize textarea
        $('textarea.auto-resize').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        }).trigger('input');
        
        // Copy shortcode to clipboard
        $('.copy-shortcode').on('click', function(e) {
            e.preventDefault();
            
            var shortcode = $(this).data('shortcode');
            var tempInput = $('<input>');
            
            $('body').append(tempInput);
            tempInput.val(shortcode).select();
            document.execCommand('copy');
            tempInput.remove();
            
            // Show success notification
            var button = $(this);
            var originalText = button.text();
            
            button.text('Copied!');
            setTimeout(function() {
                button.text(originalText);
            }, 2000);
        });
    });
})(jQuery);
