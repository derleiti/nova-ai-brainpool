<?php
/**
 * Nova AI Admin Crawler View
 * 
 * Crawler management and monitoring interface
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get the variables passed from the controller
$crawler_status = isset($crawler_status) ? $crawler_status : array();
$crawled_content = isset($crawled_content) ? $crawled_content : array();
?>

<div class="nova-ai-admin-wrap">
    <div class="nova-ai-admin-container">
        
        <div class="nova-ai-admin-header">
            <h1 class="nova-ai-admin-title"><?php _e('Web Crawler', 'nova-ai-brainpool'); ?></h1>
            <p class="nova-ai-admin-subtitle"><?php _e('Monitor and manage the web crawler that gathers knowledge for your AI assistant', 'nova-ai-brainpool'); ?></p>
        </div>

        <?php settings_errors('nova_ai_messages'); ?>

        <!-- Crawler Status -->
        <div class="nova-ai-admin-grid">
            
            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">📊</div>
                    <h3 class="nova-ai-card-title"><?php _e('Total URLs', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number total-urls"><?php echo number_format($crawler_status['total_urls'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Discovered', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">✅</div>
                    <h3 class="nova-ai-card-title"><?php _e('Successfully Crawled', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number crawled-urls"><?php echo number_format($crawler_status['crawled_urls'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Pages', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">❌</div>
                    <h3 class="nova-ai-card-title"><?php _e('Errors', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number error-urls"><?php echo number_format($crawler_status['error_urls'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Failed URLs', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">⏰</div>
                    <h3 class="nova-ai-card-title"><?php _e('Last Crawl', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number last-crawl">
                        <?php 
                        if (!empty($crawler_status['last_crawl'])) {
                            echo human_time_diff(strtotime($crawler_status['last_crawl']));
                            echo ' ' . __('ago', 'nova-ai-brainpool');
                        } else {
                            echo __('Never', 'nova-ai-brainpool');
                        }
                        ?>
                    </span>
                    <span class="nova-ai-stat-label"><?php _e('Last Activity', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>
        </div>

        <!-- Crawler Controls -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">🎛️</div>
                <h3 class="nova-ai-card-title"><?php _e('Crawler Controls', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                
                <!-- Progress Bar -->
                <?php 
                $total_urls = $crawler_status['total_urls'] ?? 0;
                $crawled_urls = $crawler_status['crawled_urls'] ?? 0;
                $progress = $total_urls > 0 ? ($crawled_urls / $total_urls) * 100 : 0;
                ?>
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span><?php _e('Crawl Progress', 'nova-ai-brainpool'); ?></span>
                        <span><?php echo round($progress, 1); ?>%</span>
                    </div>
                    <div class="nova-ai-progress">
                        <div class="nova-ai-progress-bar" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="nova-ai-btn-group">
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('nova_ai_admin_action', 'nova_ai_nonce'); ?>
                        <input type="hidden" name="nova_ai_action" value="run_crawler">
                        <button type="submit" class="nova-ai-btn nova-ai-btn-primary">
                            🚀 <?php _e('Run Crawler Now', 'nova-ai-brainpool'); ?>
                        </button>
                    </form>
                    
                    <button type="button" class="nova-ai-btn nova-ai-btn-secondary nova-ai-run-crawler">
                        🔄 <?php _e('Refresh Status', 'nova-ai-brainpool'); ?>
                    </button>
                    
                    <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to clear all crawled data?', 'nova-ai-brainpool'); ?>')">
                        <?php wp_nonce_field('nova_ai_admin_action', 'nova_ai_nonce'); ?>
                        <input type="hidden" name="nova_ai_action" value="clear_crawled_data">
                        <button type="submit" class="nova-ai-btn nova-ai-btn-danger">
                            🗑️ <?php _e('Clear All Data', 'nova-ai-brainpool'); ?>
                        </button>
                    </form>
                </div>

                <!-- Manual URL Crawler -->
                <div class="nova-ai-manual-crawler" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                    <h4><?php _e('Manual URL Crawler', 'nova-ai-brainpool'); ?></h4>
                    <p><?php _e('Crawl a specific URL immediately', 'nova-ai-brainpool'); ?></p>
                    
                    <div style="display: flex; gap: 0.5rem; align-items: end;">
                        <div style="flex: 1;">
                            <input type="url" 
                                   class="nova-ai-form-input nova-ai-crawl-url-manual" 
                                   placeholder="<?php _e('Enter URL to crawl...', 'nova-ai-brainpool'); ?>"
                                   style="margin-bottom: 0;">
                        </div>
                        <button type="button" class="nova-ai-btn nova-ai-btn-primary nova-ai-crawl-manual-btn">
                            <?php _e('Crawl URL', 'nova-ai-brainpool'); ?>
                        </button>
                    </div>
                    
                    <div class="nova-ai-manual-crawl-status" style="margin-top: 1rem; display: none;"></div>
                </div>

            </div>
        </div>

        <!-- Configuration Summary -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">⚙️</div>
                <h3 class="nova-ai-card-title"><?php _e('Configuration', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                <div class="nova-ai-config-grid">
                    
                    <div class="nova-ai-config-section">
                        <h4><?php _e('Status', 'nova-ai-brainpool'); ?></h4>
                        <div class="nova-ai-config-item">
                            <span><?php _e('Crawler Enabled', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status <?php echo get_option('nova_ai_crawl_enabled', true) ? 'nova-ai-status-success' : 'nova-ai-status-error'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo get_option('nova_ai_crawl_enabled', true) ? __('Yes', 'nova-ai-brainpool') : __('No', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-config-item">
                            <span><?php _e('Auto-Crawl', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status <?php echo get_option('nova_ai_auto_crawl_enabled', true) ? 'nova-ai-status-success' : 'nova-ai-status-warning'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo get_option('nova_ai_auto_crawl_enabled', true) ? __('Enabled', 'nova-ai-brainpool') : __('Disabled', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                    </div>

                    <div class="nova-ai-config-section">
                        <h4><?php _e('Settings', 'nova-ai-brainpool'); ?></h4>
                        <div class="nova-ai-config-item">
                            <span><?php _e('Crawl Interval', 'nova-ai-brainpool'); ?></span>
                            <span><?php echo ucfirst(get_option('nova_ai_crawl_interval', 'hourly')); ?></span>
                        </div>
                        
                        <div class="nova-ai-config-item">
                            <span><?php _e('Max Depth', 'nova-ai-brainpool'); ?></span>
                            <span><?php echo get_option('nova_ai_max_crawl_depth', 3); ?></span>
                        </div>
                        
                        <div class="nova-ai-config-item">
                            <span><?php _e('Delay (ms)', 'nova-ai-brainpool'); ?></span>
                            <span><?php echo number_format(get_option('nova_ai_crawl_delay', 1000)); ?></span>
                        </div>
                    </div>

                    <div class="nova-ai-config-section">
                        <h4><?php _e('Target Sites', 'nova-ai-brainpool'); ?></h4>
                        <?php 
                        $sites = json_decode(get_option('nova_ai_crawl_sites', '["https://ailinux.me"]'), true);
                        if (is_array($sites) && !empty($sites)):
                        ?>
                            <div class="nova-ai-sites-list">
                                <?php foreach (array_slice($sites, 0, 5) as $site): ?>
                                    <div class="nova-ai-site-item">
                                        <span class="nova-ai-site-url"><?php echo esc_html($site); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($sites) > 5): ?>
                                    <div class="nova-ai-site-item">
                                        <span style="color: #6b7280; font-style: italic;">
                                            <?php printf(__('... and %d more', 'nova-ai-brainpool'), count($sites) - 5); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #6b7280; font-style: italic;"><?php _e('No sites configured', 'nova-ai-brainpool'); ?></p>
                        <?php endif; ?>
                    </div>

                </div>
                
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                    <a href="<?php echo admin_url('admin.php?page=nova-ai-settings#crawler-settings'); ?>" class="nova-ai-btn nova-ai-btn-outline">
                        ⚙️ <?php _e('Crawler Settings', 'nova-ai-brainpool'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Crawled Content -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">📄</div>
                <h3 class="nova-ai-card-title"><?php _e('Recent Crawled Content', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                
                <?php if (!empty($crawled_content)): ?>
                    <div class="nova-ai-table-container">
                        <table class="nova-ai-table">
                            <thead>
                                <tr>
                                    <th><?php _e('URL', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Title', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Status', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Content Length', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Last Updated', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Actions', 'nova-ai-brainpool'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($crawled_content as $item): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url($item['url']); ?>" target="_blank" class="nova-ai-url-link">
                                                <?php echo esc_html(parse_url($item['url'], PHP_URL_HOST) . parse_url($item['url'], PHP_URL_PATH)); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span title="<?php echo esc_attr($item['title']); ?>">
                                                <?php echo esc_html(wp_trim_words($item['title'], 8)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = 'nova-ai-status-info';
                                            switch ($item['status']) {
                                                case 'crawled':
                                                    $status_class = 'nova-ai-status-success';
                                                    $status_text = __('Success', 'nova-ai-brainpool');
                                                    break;
                                                case 'error':
                                                    $status_class = 'nova-ai-status-error';
                                                    $status_text = __('Error', 'nova-ai-brainpool');
                                                    break;
                                                case 'pending':
                                                    $status_class = 'nova-ai-status-warning';
                                                    $status_text = __('Pending', 'nova-ai-brainpool');
                                                    break;
                                                default:
                                                    $status_text = ucfirst($item['status']);
                                            }
                                            ?>
                                            <span class="nova-ai-status <?php echo $status_class; ?>">
                                                <span class="nova-ai-status-dot"></span>
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo number_format($item['content_length'] ?? 0); ?> <?php _e('chars', 'nova-ai-brainpool'); ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($item['updated_at'])) {
                                                echo human_time_diff(strtotime($item['updated_at'])) . ' ' . __('ago', 'nova-ai-brainpool');
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="nova-ai-btn nova-ai-btn-outline nova-ai-btn-small nova-ai-recrawl-url" 
                                                    data-url="<?php echo esc_attr($item['url']); ?>"
                                                    title="<?php _e('Recrawl this URL', 'nova-ai-brainpool'); ?>">
                                                🔄
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 1rem; text-align: center; color: #6b7280;">
                        <?php printf(__('Showing latest %d entries', 'nova-ai-brainpool'), count($crawled_content)); ?>
                    </div>
                    
                <?php else: ?>
                    <div class="nova-ai-empty-state">
                        <div class="nova-ai-empty-icon">📄</div>
                        <h3><?php _e('No Content Yet', 'nova-ai-brainpool'); ?></h3>
                        <p><?php _e('No pages have been crawled yet. Run the crawler to start gathering content.', 'nova-ai-brainpool'); ?></p>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('nova_ai_admin_action', 'nova_ai_nonce'); ?>
                            <input type="hidden" name="nova_ai_action" value="run_crawler">
                            <button type="submit" class="nova-ai-btn nova-ai-btn-primary">
                                🚀 <?php _e('Start Crawling', 'nova-ai-brainpool'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
.nova-ai-crawler-status {
    /* This class is used by JavaScript for real-time updates */
}

.nova-ai-config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.nova-ai-config-section h4 {
    margin: 0 0 1rem 0;
    color: #1e293b;
    font-size: 1rem;
    font-weight: 600;
    border-bottom: 2px solid #2563eb;
    padding-bottom: 0.5rem;
}

.nova-ai-config-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.nova-ai-config-item:last-child {
    border-bottom: none;
}

.nova-ai-sites-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.nova-ai-site-item {
    padding: 0.5rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.25rem;
}

.nova-ai-site-url {
    font-family: monospace;
    font-size: 0.875rem;
    color: #2563eb;
    word-break: break-all;
}

.nova-ai-url-link {
    color: #2563eb;
    text-decoration: none;
    font-family: monospace;
    font-size: 0.875rem;
}

.nova-ai-url-link:hover {
    text-decoration: underline;
}

.nova-ai-empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.nova-ai-empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.nova-ai-empty-state h3 {
    margin: 0 0 0.5rem 0;
    color: #374151;
    font-size: 1.25rem;
}

.nova-ai-empty-state p {
    margin: 0 0 1.5rem 0;
    font-size: 0.875rem;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .nova-ai-config-grid {
        grid-template-columns: 1fr;
    }
    
    .nova-ai-table-container {
        overflow-x: auto;
    }
    
    .nova-ai-btn-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .nova-ai-btn-group .nova-ai-btn {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Manual URL crawling
    $('.nova-ai-crawl-manual-btn').on('click', function() {
        const url = $('.nova-ai-crawl-url-manual').val().trim();
        const statusDiv = $('.nova-ai-manual-crawl-status');
        
        if (!url) {
            statusDiv.removeClass('nova-ai-status-success nova-ai-status-error')
                     .addClass('nova-ai-status-warning')
                     .text('<?php _e('Please enter a URL', 'nova-ai-brainpool'); ?>')
                     .show();
            return;
        }
        
        $(this).prop('disabled', true).text('<?php _e('Crawling...', 'nova-ai-brainpool'); ?>');
        
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_crawl_url',
                nonce: nova_ai_admin_ajax.nonce,
                url: url
            },
            success: function(response) {
                if (response.success) {
                    statusDiv.removeClass('nova-ai-status-warning nova-ai-status-error')
                             .addClass('nova-ai-status-success')
                             .text('<?php _e('Successfully crawled: ', 'nova-ai-brainpool'); ?>' + (response.data.title || url))
                             .show();
                    $('.nova-ai-crawl-url-manual').val('');
                    
                    // Refresh status after successful crawl
                    setTimeout(function() {
                        NovaAIAdmin.updateCrawlerStatus();
                    }, 1000);
                } else {
                    statusDiv.removeClass('nova-ai-status-success nova-ai-status-warning')
                             .addClass('nova-ai-status-error')
                             .text('<?php _e('Error: ', 'nova-ai-brainpool'); ?>' + response.data.message)
                             .show();
                }
            },
            error: function() {
                statusDiv.removeClass('nova-ai-status-success nova-ai-status-warning')
                         .addClass('nova-ai-status-error')
                         .text('<?php _e('Crawling failed', 'nova-ai-brainpool'); ?>')
                         .show();
            },
            complete: function() {
                $('.nova-ai-crawl-manual-btn').prop('disabled', false).text('<?php _e('Crawl URL', 'nova-ai-brainpool'); ?>');
            }
        });
    });
    
    // Re-crawl individual URLs
    $('.nova-ai-recrawl-url').on('click', function() {
        const url = $(this).data('url');
        const $btn = $(this);
        
        $btn.prop('disabled', true).text('🔄');
        
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_crawl_url',
                nonce: nova_ai_admin_ajax.nonce,
                url: url
            },
            success: function(response) {
                if (response.success) {
                    NovaAIAdmin.showNotification('<?php _e('URL recrawled successfully', 'nova-ai-brainpool'); ?>', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    NovaAIAdmin.showNotification('<?php _e('Recrawl failed: ', 'nova-ai-brainpool'); ?>' + response.data.message, 'error');
                }
            },
            error: function() {
                NovaAIAdmin.showNotification('<?php _e('Recrawl failed', 'nova-ai-brainpool'); ?>', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text('🔄');
            }
        });
    });
    
    // Refresh status button
    $('.nova-ai-run-crawler').on('click', function() {
        NovaAIAdmin.updateCrawlerStatus();
        NovaAIAdmin.showNotification('<?php _e('Status refreshed', 'nova-ai-brainpool'); ?>', 'info', 2000);
    });
    
    // Auto-refresh status every 30 seconds
    setInterval(function() {
        NovaAIAdmin.updateCrawlerStatus();
    }, 30000);
});
</script>
