/**
 * Nova AI Extended JavaScript
 * 
 * Advanced functionality for admin interface and extended features
 */

(function($) {
    'use strict';

    // Global Nova AI Admin object
    window.NovaAIAdmin = {
        init: function() {
            this.initTabs();
            this.initForms();
            this.initModals();
            this.initCharts();
            this.initRealTimeUpdates();
            this.initKeyboardShortcuts();
        },

        // Tab functionality
        initTabs: function() {
            $('.nova-ai-tab-button').on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const tabId = $this.data('tab');
                const $container = $this.closest('.nova-ai-tabs');
                
                // Update buttons
                $container.find('.nova-ai-tab-button').removeClass('active');
                $this.addClass('active');
                
                // Update panels
                $container.find('.nova-ai-tab-panel').removeClass('active');
                $container.find(`#${tabId}`).addClass('active');
            });
        },

        // Form handling
        initForms: function() {
            // Auto-save forms
            $('.nova-ai-form[data-autosave]').each(function() {
                const $form = $(this);
                const autosaveInterval = $form.data('autosave') || 30000; // 30 seconds default
                
                setInterval(() => {
                    NovaAIAdmin.autoSaveForm($form);
                }, autosaveInterval);
            });

            // Form validation
            $('.nova-ai-form').on('submit', function(e) {
                const $form = $(this);
                if (!NovaAIAdmin.validateForm($form)) {
                    e.preventDefault();
                    return false;
                }
            });

            // Real-time validation
            $('.nova-ai-form-input, .nova-ai-form-textarea').on('blur', function() {
                NovaAIAdmin.validateField($(this));
            });

            // API connection test
            $('.nova-ai-test-connection').on('click', function(e) {
                e.preventDefault();
                NovaAIAdmin.testApiConnection($(this));
            });

            // Import/Export functionality
            $('.nova-ai-export-settings').on('click', function(e) {
                e.preventDefault();
                NovaAIAdmin.exportSettings();
            });

            $('.nova-ai-import-settings').on('click', function(e) {
                e.preventDefault();
                NovaAIAdmin.importSettings();
            });
        },

        // Auto-save form data
        autoSaveForm: function($form) {
            const formData = $form.serialize();
            const formId = $form.attr('id') || 'nova-ai-form';
            
            $.ajax({
                url: nova_ai_admin_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'nova_ai_autosave',
                    nonce: nova_ai_admin_ajax.nonce,
                    form_id: formId,
                    form_data: formData
                },
                success: function(response) {
                    if (response.success) {
                        NovaAIAdmin.showNotification('Settings auto-saved', 'success');
                    }
                }
            });
        },

        // Form validation
        validateForm: function($form) {
            let isValid = true;
            
            $form.find('[required]').each(function() {
                if (!NovaAIAdmin.validateField($(this))) {
                    isValid = false;
                }
            });
            
            return isValid;
        },

        // Field validation
        validateField: function($field) {
            const value = $field.val();
            const fieldType = $field.attr('type') || $field.prop('tagName').toLowerCase();
            const isRequired = $field.attr('required');
            let isValid = true;
            let errorMessage = '';

            // Clear previous errors
            $field.removeClass('error');
            $field.siblings('.nova-ai-form-error').remove();

            // Required field check
            if (isRequired && !value.trim()) {
                isValid = false;
                errorMessage = 'This field is required';
            }

            // Type-specific validation
            if (value && isValid) {
                switch (fieldType) {
                    case 'email':
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            isValid = false;
                            errorMessage = 'Please enter a valid email address';
                        }
                        break;
                    
                    case 'url':
                        try {
                            new URL(value);
                        } catch {
                            isValid = false;
                            errorMessage = 'Please enter a valid URL';
                        }
                        break;
                    
                    case 'number':
                        if (isNaN(value)) {
                            isValid = false;
                            errorMessage = 'Please enter a valid number';
                        }
                        break;
                }
            }

            // Custom validation patterns
            const pattern = $field.attr('pattern');
            if (value && pattern && isValid) {
                const regex = new RegExp(pattern);
                if (!regex.test(value)) {
                    isValid = false;
                    errorMessage = $field.attr('title') || 'Invalid format';
                }
            }

            // Show error if invalid
            if (!isValid) {
                $field.addClass('error');
                $field.after(`<div class="nova-ai-form-error">${errorMessage}</div>`);
            }

            return isValid;
        },

        // Test API connection
        testApiConnection: function($button) {
            const $form = $button.closest('form');
            const apiUrl = $form.find('[name="nova_ai_api_url"]').val();
            const apiKey = $form.find('[name="nova_ai_api_key"]').val();
            
            if (!apiUrl) {
                NovaAIAdmin.showNotification('Please enter an API URL first', 'error');
                return;
            }

            $button.prop('disabled', true).text('Testing...');

            $.ajax({
                url: nova_ai_admin_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'nova_ai_test_connection',
                    nonce: nova_ai_admin_ajax.nonce,
                    api_url: apiUrl,
                    api_key: apiKey
                },
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        NovaAIAdmin.showNotification('Connection successful!', 'success');
                    } else {
                        NovaAIAdmin.showNotification('Connection failed: ' + response.data.message, 'error');
                    }
                },
                error: function() {
                    NovaAIAdmin.showNotification('Connection test failed', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Connection');
                }
            });
        },

        // Export settings
        exportSettings: function() {
            $.ajax({
                url: nova_ai_admin_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'nova_ai_export_settings',
                    nonce: nova_ai_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const blob = new Blob([JSON.stringify(response.data, null, 2)], {
                            type: 'application/json'
                        });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `nova-ai-settings-${new Date().toISOString().split('T')[0]}.json`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        
                        NovaAIAdmin.showNotification('Settings exported successfully', 'success');
                    } else {
                        NovaAIAdmin.showNotification('Export failed', 'error');
                    }
                }
            });
        },

        // Import settings
        importSettings: function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const settings = JSON.parse(e.target.result);
                        NovaAIAdmin.processImportedSettings(settings);
                    } catch (error) {
                        NovaAIAdmin.showNotification('Invalid file format', 'error');
                    }
                };
                reader.readAsText(file);
            };
            input.click();
        },

        // Process imported settings
        processImportedSettings: function(settings) {
            $.ajax({
                url: nova_ai_admin_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'nova_ai_import_settings',
                    nonce: nova_ai_admin_ajax.nonce,
                    settings: JSON.stringify(settings)
                },
                success: function(response) {
                    if (response.success) {
                        NovaAIAdmin.showNotification('Settings imported successfully', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        NovaAIAdmin.showNotification('Import failed: ' + response.data.message, 'error');
                    }
                }
            });
        },

        // Modal functionality
        initModals: function() {
            // Open modal
            $(document).on('click', '[data-modal]', function(e) {
                e.preventDefault();
                const modalId = $(this).data('modal');
                NovaAIAdmin.openModal(modalId);
            });

            // Close modal
            $(document).on('click', '.nova-ai-modal-close, .nova-ai-modal-overlay', function(e) {
                if (e.target === this) {
                    NovaAIAdmin.closeModal();
                }
            });

            // ESC key to close modal
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    NovaAIAdmin.closeModal();
                }
            });
        },

        // Open modal
        openModal: function(modalId) {
            const $modal = $(`#${modalId}`);
            if ($modal.length) {
                $modal.show().addClass('active');
                $('body').addClass('nova-ai-modal-open');
            }
        },

        // Close modal
        closeModal: function() {
            $('.nova-ai-modal').removeClass('active').hide();
            $('body').removeClass('nova-ai-modal-open');
        },

        // Charts initialization
        initCharts: function() {
            // Usage statistics chart
            if ($('#nova-ai-usage-chart').length && typeof Chart !== 'undefined') {
                NovaAIAdmin.createUsageChart();
            }

            // Performance chart
            if ($('#nova-ai-performance-chart').length && typeof Chart !== 'undefined') {
                NovaAIAdmin.createPerformanceChart();
            }
        },

        // Create usage statistics chart
        createUsageChart: function() {
            const ctx = document.getElementById('nova-ai-usage-chart').getContext('2d');
            
            $.ajax({
                url: nova_ai_admin_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'nova_ai_get_usage_stats',
                    nonce: nova_ai_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    label: 'Messages',
                                    data: data.messages,
                                    borderColor: '#2563eb',
                                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                    tension: 0.4
                                }, {
                                    label: 'Images Generated',
                                    data: data.images,
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    },
                                    title: {
                                        display: true,
                                        text: 'Usage Statistics (Last 30 Days)'
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },

        // Real-time updates
        initRealTimeUpdates: function() {
            // Update statistics every 30 seconds
            setInterval(() => {
                NovaAIAdmin.updateStatistics();
            }, 30000);

            // Update crawler status every 10 seconds
            setInterval(() => {
                NovaAIAdmin.updateCrawlerStatus();
            }, 10000);
        },

        // Update statistics
        updateStatistics: function() {
            $.ajax({
                url: nova_ai_admin_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'nova_ai_get_quick_stats',
                    nonce: nova_ai_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const stats = response.data;
                        
                        // Update stat cards
                        $('.nova-ai-stat[data-stat="total_messages"] .nova-ai-stat-number').text(stats.total_messages || 0);
                        $('.nova-ai-stat[data-stat="total_conversations"] .nova-ai-stat-number').text(stats.total_conversations || 0);
                        $('.nova-ai-stat[data-stat="total_images"] .nova-ai-stat-number').text(stats.total_images || 0);
                        $('.nova-ai-stat[data-stat="crawled_pages"] .nova-ai-stat-number').text(stats.crawled_pages || 0);
                    }
                }
            });
        },

        // Update crawler status
        updateCrawlerStatus: function() {
            if (!$('.nova-ai-crawler-status').length) return;

            $.ajax({
                url: nova_ai_admin_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'nova_ai_get_crawler_status',
                    nonce: nova_ai_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const status = response.data;
                        NovaAIAdmin.updateCrawlerStatusDisplay(status);
                    }
                }
            });
        },

        // Update crawler status display
        updateCrawlerStatusDisplay: function(status) {
            const $container = $('.nova-ai-crawler-status');
            
            $container.find('.total-urls').text(status.total_urls || 0);
            $container.find('.crawled-urls').text(status.crawled_urls || 0);
            $container.find('.error-urls').text(status.error_urls || 0);
            $container.find('.last-crawl').text(status.last_crawl || 'Never');

            // Update progress bar
            const progress = status.total_urls > 0 ? (status.crawled_urls / status.total_urls) * 100 : 0;
            $container.find('.nova-ai-progress-bar').css('width', progress + '%');
        },

        // Keyboard shortcuts
        initKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + S to save current form
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    const $activeForm = $('.nova-ai-form:visible').first();
                    if ($activeForm.length) {
                        $activeForm.submit();
                    }
                }

                // Ctrl/Cmd + Shift + C to clear cache
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
                    e.preventDefault();
                    NovaAIAdmin.clearCache();
                }

                // Ctrl/Cmd + Shift + R to run crawler
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'R') {
                    e.preventDefault();
                    NovaAIAdmin.runCrawler();
                }
            });
        },

        // Clear cache
        clearCache: function() {
            if (!confirm('Are you sure you want to clear all cache?')) return;

            $.ajax({
                url: nova_ai_admin_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'nova_ai_clear_cache',
                    nonce: nova_ai_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        NovaAIAdmin.showNotification('Cache cleared successfully', 'success');
                    } else {
                        NovaAIAdmin.showNotification('Failed to clear cache', 'error');
                    }
                }
            });
        },

        // Run crawler manually
        runCrawler: function() {
            $.ajax({
                url: nova_ai_admin_ajax.ajax_url,
                method: 'POST',
                data: {
                    action: 'nova_ai_run_crawler',
                    nonce: nova_ai_admin_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        NovaAIAdmin.showNotification('Crawler started successfully', 'success');
                        setTimeout(() => NovaAIAdmin.updateCrawlerStatus(), 2000);
                    } else {
                        NovaAIAdmin.showNotification('Failed to start crawler', 'error');
                    }
                }
            });
        },

        // Show notification
        showNotification: function(message, type = 'info', duration = 5000) {
            const $notification = $(`
                <div class="nova-ai-notification nova-ai-notification-${type}">
                    <div class="nova-ai-notification-content">
                        <span class="nova-ai-notification-icon">${this.getNotificationIcon(type)}</span>
                        <span class="nova-ai-notification-message">${message}</span>
                        <button class="nova-ai-notification-close">&times;</button>
                    </div>
                </div>
            `);

            // Add to container
            if (!$('.nova-ai-notifications').length) {
                $('body').append('<div class="nova-ai-notifications"></div>');
            }
            $('.nova-ai-notifications').append($notification);

            // Show with animation
            setTimeout(() => $notification.addClass('show'), 10);

            // Auto-hide
            if (duration > 0) {
                setTimeout(() => {
                    $notification.removeClass('show');
                    setTimeout(() => $notification.remove(), 300);
                }, duration);
            }

            // Close button
            $notification.find('.nova-ai-notification-close').on('click', function() {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            });
        },

        // Get notification icon
        getNotificationIcon: function(type) {
            const icons = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
            };
            return icons[type] || icons.info;
        },

        // Utility functions
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Format numbers
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },

        // Format bytes
        formatBytes: function(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        NovaAIAdmin.init();

        // Add loading states to buttons
        $('.nova-ai-btn[type="submit"]').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Processing...');
            
            setTimeout(() => {
                $btn.prop('disabled', false).text(originalText);
            }, 5000); // Reset after 5 seconds as fallback
        });

        // Auto-expand textareas
        $('.nova-ai-form-textarea').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Copy to clipboard functionality
        $('.nova-ai-copy-btn').on('click', function() {
            const text = $(this).data('copy') || $(this).prev('input, textarea').val();
            navigator.clipboard.writeText(text).then(() => {
                NovaAIAdmin.showNotification('Copied to clipboard', 'success', 2000);
            });
        });

        // Color picker initialization
        if ($.fn.wpColorPicker) {
            $('.nova-ai-color-picker').wpColorPicker();
        }

        // Media uploader
        $('.nova-ai-media-upload').on('click', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $input = $btn.siblings('input');
            
            const mediaUploader = wp.media({
                title: 'Select Image',
                button: {
                    text: 'Use Image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                $input.val(attachment.url);
                $btn.siblings('.nova-ai-media-preview').html(`<img src="${attachment.url}" style="max-width: 100px; max-height: 100px;">`);
            });
            
            mediaUploader.open();
        });
    });

    // Expose utility functions globally
    window.NovaAI = window.NovaAI || {};
    window.NovaAI.Admin = NovaAIAdmin;

})(jQuery);

// Add notification styles if not already present
if (!document.querySelector('#nova-ai-notification-styles')) {
    const styles = `
        <style id="nova-ai-notification-styles">
            .nova-ai-notifications {
                position: fixed;
                top: 32px;
                right: 20px;
                z-index: 999999;
                max-width: 400px;
            }
            .nova-ai-notification {
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                margin-bottom: 10px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            }
            .nova-ai-notification.show {
                opacity: 1;
                transform: translateX(0);
            }
            .nova-ai-notification-content {
                padding: 16px;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .nova-ai-notification-icon {
                font-size: 18px;
                font-weight: bold;
            }
            .nova-ai-notification-message {
                flex: 1;
                color: #374151;
            }
            .nova-ai-notification-close {
                background: none;
                border: none;
                font-size: 20px;
                cursor: pointer;
                color: #6b7280;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .nova-ai-notification-success {
                border-left: 4px solid #10b981;
            }
            .nova-ai-notification-success .nova-ai-notification-icon {
                color: #10b981;
            }
            .nova-ai-notification-error {
                border-left: 4px solid #ef4444;
            }
            .nova-ai-notification-error .nova-ai-notification-icon {
                color: #ef4444;
            }
            .nova-ai-notification-warning {
                border-left: 4px solid #f59e0b;
            }
            .nova-ai-notification-warning .nova-ai-notification-icon {
                color: #f59e0b;
            }
            .nova-ai-notification-info {
                border-left: 4px solid #2563eb;
            }
            .nova-ai-notification-info .nova-ai-notification-icon {
                color: #2563eb;
            }
        </style>
    `;
    document.head.insertAdjacentHTML('beforeend', styles);
}
