<?php
/**
 * Nova AI Admin Dashboard
 * 
 * Main dashboard view for Nova AI Brainpool
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get the variables passed from the controller
$stats = isset($stats) ? $stats : array();
$system_info = isset($system_info) ? $system_info : array();
?>

<div class="nova-ai-admin-wrap">
    <div class="nova-ai-admin-container">
        
        <div class="nova-ai-admin-header">
            <h1 class="nova-ai-admin-title"><?php _e('Nova AI Dashboard', 'nova-ai-brainpool'); ?></h1>
            <p class="nova-ai-admin-subtitle"><?php _e('Monitor your AI assistant performance and system status', 'nova-ai-brainpool'); ?></p>
        </div>

        <!-- Quick Stats -->
        <div class="nova-ai-admin-grid">
            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">💬</div>
                    <h3 class="nova-ai-card-title"><?php _e('Messages Today', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number" data-stat="total_messages"><?php echo number_format($stats['today']['messages'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Total Messages', 'nova-ai-brainpool'); ?></span>
                    <div class="nova-ai-stat-change positive">
                        <?php printf(__('Total: %s', 'nova-ai-brainpool'), number_format($stats['total']['messages'] ?? 0)); ?>
                    </div>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">🗨️</div>
                    <h3 class="nova-ai-card-title"><?php _e('Conversations', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number" data-stat="total_conversations"><?php echo number_format($stats['today']['conversations'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('New Today', 'nova-ai-brainpool'); ?></span>
                    <div class="nova-ai-stat-change positive">
                        <?php printf(__('Total: %s', 'nova-ai-brainpool'), number_format($stats['total']['conversations'] ?? 0)); ?>
                    </div>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">🖼️</div>
                    <h3 class="nova-ai-card-title"><?php _e('Images Generated', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number" data-stat="total_images"><?php echo number_format($stats['today']['images'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Today', 'nova-ai-brainpool'); ?></span>
                    <div class="nova-ai-stat-change positive">
                        <?php printf(__('Total: %s', 'nova-ai-brainpool'), number_format($stats['total']['images'] ?? 0)); ?>
                    </div>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">🕷️</div>
                    <h3 class="nova-ai-card-title"><?php _e('Pages Crawled', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number" data-stat="crawled_pages"><?php echo number_format($stats['today']['crawled_pages'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Today', 'nova-ai-brainpool'); ?></span>
                    <div class="nova-ai-stat-change positive">
                        <?php printf(__('Total: %s', 'nova-ai-brainpool'), number_format($stats['total']['crawled_pages'] ?? 0)); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Chart -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">📈</div>
                <h3 class="nova-ai-card-title"><?php _e('Activity Overview', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                <canvas id="nova-ai-usage-chart" width="400" height="200"></canvas>
            </div>
        </div>

        <div class="nova-ai-admin-grid">
            
            <!-- System Status -->
            <div class="nova-ai-admin-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">⚙️</div>
                    <h3 class="nova-ai-card-title"><?php _e('System Status', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <div class="nova-ai-status-grid">
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('Plugin Version', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status nova-ai-status-info">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo esc_html($system_info['plugin_version'] ?? 'Unknown'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('PHP Version', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status <?php echo version_compare($system_info['php_version'] ?? '0', '8.0', '>=') ? 'nova-ai-status-success' : 'nova-ai-status-warning'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo esc_html($system_info['php_version'] ?? 'Unknown'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('WordPress', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status nova-ai-status-success">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo esc_html($system_info['wordpress_version'] ?? 'Unknown'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('Memory Limit', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status nova-ai-status-info">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo esc_html($system_info['memory_limit'] ?? 'Unknown'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('cURL', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status <?php echo function_exists('curl_version') ? 'nova-ai-status-success' : 'nova-ai-status-error'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo function_exists('curl_version') ? __('Available', 'nova-ai-brainpool') : __('Not Available', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('OpenSSL', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status <?php echo extension_loaded('openssl') ? 'nova-ai-status-success' : 'nova-ai-status-error'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo extension_loaded('openssl') ? __('Available', 'nova-ai-brainpool') : __('Not Available', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="nova-ai-admin-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">⚡</div>
                    <h3 class="nova-ai-card-title"><?php _e('Quick Actions', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <div class="nova-ai-action-grid">
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('nova_ai_admin_action', 'nova_ai_nonce'); ?>
                            <input type="hidden" name="nova_ai_action" value="run_crawler">
                            <button type="submit" class="nova-ai-btn nova-ai-btn-primary" style="width: 100%; margin-bottom: 0.5rem;">
                                🕷️ <?php _e('Run Crawler', 'nova-ai-brainpool'); ?>
                            </button>
                        </form>
                        
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('nova_ai_admin_action', 'nova_ai_nonce'); ?>
                            <input type="hidden" name="nova_ai_action" value="cleanup_old_data">
                            <button type="submit" class="nova-ai-btn nova-ai-btn-secondary" style="width: 100%; margin-bottom: 0.5rem;">
                                🧹 <?php _e('Cleanup Old Data', 'nova-ai-brainpool'); ?>
                            </button>
                        </form>
                        
                        <button type="button" class="nova-ai-btn nova-ai-btn-outline nova-ai-clear-cache" style="width: 100%; margin-bottom: 0.5rem;">
                            🗑️ <?php _e('Clear Cache', 'nova-ai-brainpool'); ?>
                        </button>
                        
                        <a href="<?php echo admin_url('admin.php?page=nova-ai-settings'); ?>" class="nova-ai-btn nova-ai-btn-outline" style="width: 100%; text-decoration: none; text-align: center;">
                            ⚙️ <?php _e('Settings', 'nova-ai-brainpool'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="nova-ai-admin-grid">
            
            <!-- Weekly Trend -->
            <div class="nova-ai-admin-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">📊</div>
                    <h3 class="nova-ai-card-title"><?php _e('7-Day Trend', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <div class="nova-ai-table-container">
                        <table class="nova-ai-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Date', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Messages', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Images', 'nova-ai-brainpool'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stats['weekly_trend'])): ?>
                                    <?php foreach (array_reverse($stats['weekly_trend']) as $day): ?>
                                        <tr>
                                            <td><?php echo date('M d', strtotime($day['date'])); ?></td>
                                            <td>
                                                <span class="nova-ai-trend-number"><?php echo number_format($day['messages']); ?></span>
                                            </td>
                                            <td>
                                                <span class="nova-ai-trend-number"><?php echo number_format($day['images']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: #6b7280;">
                                            <?php _e('No data available', 'nova-ai-brainpool'); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Configuration Status -->
            <div class="nova-ai-admin-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">🔧</div>
                    <h3 class="nova-ai-card-title"><?php _e('Configuration Status', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <div class="nova-ai-config-checks">
                        
                        <div class="nova-ai-config-item">
                            <span class="nova-ai-config-label"><?php _e('AI Provider', 'nova-ai-brainpool'); ?></span>
                            <?php 
                            $api_key = get_option('nova_ai_api_key');
                            $api_url = get_option('nova_ai_api_url');
                            $configured = !empty($api_key) && !empty($api_url);
                            ?>
                            <span class="nova-ai-status <?php echo $configured ? 'nova-ai-status-success' : 'nova-ai-status-warning'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo $configured ? __('Configured', 'nova-ai-brainpool') : __('Needs Setup', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-config-item">
                            <span class="nova-ai-config-label"><?php _e('Crawler', 'nova-ai-brainpool'); ?></span>
                            <?php $crawler_enabled = get_option('nova_ai_crawl_enabled', true); ?>
                            <span class="nova-ai-status <?php echo $crawler_enabled ? 'nova-ai-status-success' : 'nova-ai-status-info'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo $crawler_enabled ? __('Enabled', 'nova-ai-brainpool') : __('Disabled', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-config-item">
                            <span class="nova-ai-config-label"><?php _e('Image Generation', 'nova-ai-brainpool'); ?></span>
                            <?php 
                            $image_enabled = get_option('nova_ai_image_generation_enabled', true);
                            $image_url = get_option('nova_ai_image_api_url');
                            $image_configured = $image_enabled && !empty($image_url);
                            ?>
                            <span class="nova-ai-status <?php echo $image_configured ? 'nova-ai-status-success' : ($image_enabled ? 'nova-ai-status-warning' : 'nova-ai-status-info'); ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php 
                                if (!$image_enabled) {
                                    echo __('Disabled', 'nova-ai-brainpool');
                                } elseif ($image_configured) {
                                    echo __('Configured', 'nova-ai-brainpool');
                                } else {
                                    echo __('Needs Setup', 'nova-ai-brainpool');
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-config-item">
                            <span class="nova-ai-config-label"><?php _e('NovaNet', 'nova-ai-brainpool'); ?></span>
                            <?php $novanet_enabled = get_option('nova_ai_novanet_enabled', false); ?>
                            <span class="nova-ai-status <?php echo $novanet_enabled ? 'nova-ai-status-success' : 'nova-ai-status-info'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo $novanet_enabled ? __('Connected', 'nova-ai-brainpool') : __('Offline', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                        
                    </div>
                    
                    <?php if (!$configured || (!$image_configured && $image_enabled)): ?>
                        <div class="nova-ai-alert nova-ai-alert-warning" style="margin-top: 1rem;">
                            <div class="nova-ai-alert-icon">⚠️</div>
                            <div class="nova-ai-alert-content">
                                <div class="nova-ai-alert-title"><?php _e('Configuration Required', 'nova-ai-brainpool'); ?></div>
                                <?php _e('Some features need to be configured before they can be used.', 'nova-ai-brainpool'); ?>
                                <a href="<?php echo admin_url('admin.php?page=nova-ai-settings'); ?>" style="color: #92400e; text-decoration: underline;">
                                    <?php _e('Configure Now', 'nova-ai-brainpool'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Help & Documentation -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">❓</div>
                <h3 class="nova-ai-card-title"><?php _e('Help & Documentation', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                <div class="nova-ai-help-grid">
                    <div class="nova-ai-help-item">
                        <h4><?php _e('Getting Started', 'nova-ai-brainpool'); ?></h4>
                        <p><?php _e('Learn how to configure and use Nova AI Brainpool effectively.', 'nova-ai-brainpool'); ?></p>
                        <a href="#" class="nova-ai-btn nova-ai-btn-outline nova-ai-btn-small"><?php _e('View Guide', 'nova-ai-brainpool'); ?></a>
                    </div>
                    
                    <div class="nova-ai-help-item">
                        <h4><?php _e('API Documentation', 'nova-ai-brainpool'); ?></h4>
                        <p><?php _e('Detailed information about AI providers and API configuration.', 'nova-ai-brainpool'); ?></p>
                        <a href="https://ailinux.me/docs" target="_blank" class="nova-ai-btn nova-ai-btn-outline nova-ai-btn-small"><?php _e('View Docs', 'nova-ai-brainpool'); ?></a>
                    </div>
                    
                    <div class="nova-ai-help-item">
                        <h4><?php _e('Community Support', 'nova-ai-brainpool'); ?></h4>
                        <p><?php _e('Get help from the Nova AI community and development team.', 'nova-ai-brainpool'); ?></p>
                        <a href="https://ailinux.me/community" target="_blank" class="nova-ai-btn nova-ai-btn-outline nova-ai-btn-small"><?php _e('Get Support', 'nova-ai-brainpool'); ?></a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
.nova-ai-status-grid {
    display: grid;
    gap: 0.75rem;
}

.nova-ai-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.nova-ai-status-item:last-child {
    border-bottom: none;
}

.nova-ai-status-label {
    font-weight: 500;
    color: #374151;
}

.nova-ai-action-grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.nova-ai-trend-number {
    font-weight: 600;
    color: #2563eb;
}

.nova-ai-config-checks {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.nova-ai-config-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.nova-ai-config-label {
    font-weight: 500;
    color: #374151;
}

.nova-ai-help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.nova-ai-help-item {
    padding: 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background: #f8fafc;
}

.nova-ai-help-item h4 {
    margin: 0 0 0.5rem 0;
    color: #1e293b;
    font-size: 1rem;
}

.nova-ai-help-item p {
    margin: 0 0 1rem 0;
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.5;
}

.nova-ai-btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .nova-ai-admin-grid {
        grid-template-columns: 1fr;
    }
    
    .nova-ai-help-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load Chart.js if available
    if (typeof Chart !== 'undefined') {
        // Chart will be initialized by the extended JS file
        // This is just a placeholder in case Chart.js is not loaded
        setTimeout(function() {
            if ($('#nova-ai-usage-chart').length && !$('#nova-ai-usage-chart')[0].chart) {
                $('#nova-ai-usage-chart').parent().html('<p style="text-align: center; color: #6b7280; padding: 2rem;">Chart.js not available. Please include Chart.js to view usage statistics.</p>');
            }
        }, 1000);
    } else {
        $('#nova-ai-usage-chart').parent().html('<p style="text-align: center; color: #6b7280; padding: 2rem;">Chart.js not loaded. Include Chart.js library to view charts.</p>');
    }
});
</script>
