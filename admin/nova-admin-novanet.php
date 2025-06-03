<?php
/**
 * Nova AI Admin NovaNet View
 * 
 * NovaNet network management and monitoring interface
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get the variables passed from the controller
$novanet_status = isset($novanet_status) ? $novanet_status : array();
$network_stats = isset($network_stats) ? $network_stats : array();
?>

<div class="nova-ai-admin-wrap">
    <div class="nova-ai-admin-container">
        
        <div class="nova-ai-admin-header">
            <h1 class="nova-ai-admin-title"><?php _e('NovaNet Network', 'nova-ai-brainpool'); ?></h1>
            <p class="nova-ai-admin-subtitle"><?php _e('Connect and collaborate with other Nova AI instances in the distributed network', 'nova-ai-brainpool'); ?></p>
        </div>

        <?php settings_errors('nova_ai_messages'); ?>

        <!-- NovaNet Status -->
        <div class="nova-ai-admin-grid">
            
            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">🌐</div>
                    <h3 class="nova-ai-card-title"><?php _e('Network Status', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <?php $connected = $novanet_status['enabled'] && $novanet_status['registered']; ?>
                    <span class="nova-ai-stat-number">
                        <?php echo $connected ? __('Connected', 'nova-ai-brainpool') : __('Offline', 'nova-ai-brainpool'); ?>
                    </span>
                    <span class="nova-ai-stat-label"><?php _e('Status', 'nova-ai-brainpool'); ?></span>
                    <div class="nova-ai-stat-change <?php echo $connected ? 'positive' : 'negative'; ?>">
                        <?php echo $connected ? '🟢' : '🔴'; ?>
                    </div>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">🏷️</div>
                    <h3 class="nova-ai-card-title"><?php _e('Node ID', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number" style="font-size: 1rem; font-family: monospace;">
                        <?php echo esc_html(substr($novanet_status['node_id'] ?? 'Not assigned', 0, 12)); ?>...
                    </span>
                    <span class="nova-ai-stat-label"><?php _e('Unique Identifier', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">🤝</div>
                    <h3 class="nova-ai-card-title"><?php _e('Network Nodes', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number"><?php echo number_format($network_stats['total_nodes'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Active Nodes', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">⏰</div>
                    <h3 class="nova-ai-card-title"><?php _e('Last Sync', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number">
                        <?php 
                        if (!empty($novanet_status['last_sync'])) {
                            echo human_time_diff(strtotime($novanet_status['last_sync']));
                            echo ' ' . __('ago', 'nova-ai-brainpool');
                        } else {
                            echo __('Never', 'nova-ai-brainpool');
                        }
                        ?>
                    </span>
                    <span class="nova-ai-stat-label"><?php _e('Synchronization', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>
        </div>

        <!-- Connection Status & Controls -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">🔌</div>
                <h3 class="nova-ai-card-title"><?php _e('Connection Management', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                
                <!-- Current Status -->
                <div class="nova-ai-connection-status" style="margin-bottom: 2rem;">
                    <div class="nova-ai-status-grid">
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('NovaNet Enabled', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status <?php echo $novanet_status['enabled'] ? 'nova-ai-status-success' : 'nova-ai-status-error'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo $novanet_status['enabled'] ? __('Yes', 'nova-ai-brainpool') : __('No', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('Node Registered', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status <?php echo $novanet_status['registered'] ? 'nova-ai-status-success' : 'nova-ai-status-warning'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo $novanet_status['registered'] ? __('Yes', 'nova-ai-brainpool') : __('No', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('Contributing Processing', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status <?php echo $novanet_status['contributing'] ? 'nova-ai-status-success' : 'nova-ai-status-info'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo $novanet_status['contributing'] ? __('Yes', 'nova-ai-brainpool') : __('No', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                        
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('Network URL', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-network-url">
                                <?php echo esc_html($novanet_status['network_url'] ?? 'Not configured'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="nova-ai-novanet-actions">
                    <?php if (!$novanet_status['enabled']): ?>
                        <div class="nova-ai-alert nova-ai-alert-warning" style="margin-bottom: 1rem;">
                            <div class="nova-ai-alert-icon">⚠️</div>
                            <div class="nova-ai-alert-content">
                                <div class="nova-ai-alert-title"><?php _e('NovaNet Disabled', 'nova-ai-brainpool'); ?></div>
                                <?php _e('NovaNet is currently disabled. Enable it in settings to join the network.', 'nova-ai-brainpool'); ?>
                                <a href="<?php echo admin_url('admin.php?page=nova-ai-settings#novanet-settings'); ?>" style="color: #92400e; text-decoration: underline;">
                                    <?php _e('Enable NovaNet', 'nova-ai-brainpool'); ?>
                                </a>
                            </div>
                        </div>
                    <?php elseif (!$novanet_status['registered']): ?>
                        <div class="nova-ai-alert nova-ai-alert-info" style="margin-bottom: 1rem;">
                            <div class="nova-ai-alert-icon">ℹ️</div>
                            <div class="nova-ai-alert-content">
                                <div class="nova-ai-alert-title"><?php _e('Ready to Connect', 'nova-ai-brainpool'); ?></div>
                                <?php _e('NovaNet is enabled but your node is not registered with the network yet.', 'nova-ai-brainpool'); ?>
                            </div>
                        </div>
                        
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('nova_ai_admin_action', 'nova_ai_nonce'); ?>
                            <input type="hidden" name="nova_ai_action" value="register_novanet">
                            <button type="submit" class="nova-ai-btn nova-ai-btn-primary">
                                🚀 <?php _e('Register with NovaNet', 'nova-ai-brainpool'); ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="nova-ai-alert nova-ai-alert-success" style="margin-bottom: 1rem;">
                            <div class="nova-ai-alert-icon">✅</div>
                            <div class="nova-ai-alert-content">
                                <div class="nova-ai-alert-title"><?php _e('Connected to NovaNet', 'nova-ai-brainpool'); ?></div>
                                <?php _e('Your node is successfully connected to the NovaNet network and sharing knowledge.', 'nova-ai-brainpool'); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="nova-ai-btn-group">
                        <?php if ($novanet_status['enabled']): ?>
                            <button type="button" class="nova-ai-btn nova-ai-btn-secondary nova-ai-test-novanet-connection">
                                🔗 <?php _e('Test Connection', 'nova-ai-brainpool'); ?>
                            </button>
                            
                            <button type="button" class="nova-ai-btn nova-ai-btn-outline nova-ai-sync-novanet">
                                🔄 <?php _e('Sync Network', 'nova-ai-brainpool'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <a href="<?php echo admin_url('admin.php?page=nova-ai-settings#novanet-settings'); ?>" class="nova-ai-btn nova-ai-btn-outline">
                            ⚙️ <?php _e('NovaNet Settings', 'nova-ai-brainpool'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Network Statistics -->
        <?php if (!empty($network_stats) && $novanet_status['registered']): ?>
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">📊</div>
                <h3 class="nova-ai-card-title"><?php _e('Network Statistics', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                <div class="nova-ai-network-stats">
                    
                    <div class="nova-ai-stat-section">
                        <h4><?php _e('Network Overview', 'nova-ai-brainpool'); ?></h4>
                        <div class="nova-ai-stats-grid">
                            <div class="nova-ai-stat-box">
                                <span class="nova-ai-stat-value"><?php echo number_format($network_stats['total_nodes'] ?? 0); ?></span>
                                <span class="nova-ai-stat-desc"><?php _e('Total Nodes', 'nova-ai-brainpool'); ?></span>
                            </div>
                            <div class="nova-ai-stat-box">
                                <span class="nova-ai-stat-value"><?php echo number_format($network_stats['active_nodes'] ?? 0); ?></span>
                                <span class="nova-ai-stat-desc"><?php _e('Active Nodes', 'nova-ai-brainpool'); ?></span>
                            </div>
                            <div class="nova-ai-stat-box">
                                <span class="nova-ai-stat-value"><?php echo number_format($network_stats['total_requests'] ?? 0); ?></span>
                                <span class="nova-ai-stat-desc"><?php _e('Network Requests', 'nova-ai-brainpool'); ?></span>
                            </div>
                            <div class="nova-ai-stat-box">
                                <span class="nova-ai-stat-value"><?php echo number_format($network_stats['shared_knowledge'] ?? 0); ?></span>
                                <span class="nova-ai-stat-desc"><?php _e('Shared Knowledge', 'nova-ai-brainpool'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="nova-ai-stat-section">
                        <h4><?php _e('Your Node Performance', 'nova-ai-brainpool'); ?></h4>
                        <div class="nova-ai-stats-grid">
                            <div class="nova-ai-stat-box">
                                <span class="nova-ai-stat-value"><?php echo number_format($network_stats['requests_processed'] ?? 0); ?></span>
                                <span class="nova-ai-stat-desc"><?php _e('Requests Processed', 'nova-ai-brainpool'); ?></span>
                            </div>
                            <div class="nova-ai-stat-box">
                                <span class="nova-ai-stat-value"><?php echo number_format($network_stats['knowledge_shared'] ?? 0); ?></span>
                                <span class="nova-ai-stat-desc"><?php _e('Knowledge Shared', 'nova-ai-brainpool'); ?></span>
                            </div>
                            <div class="nova-ai-stat-box">
                                <span class="nova-ai-stat-value"><?php echo number_format($network_stats['requests_sent'] ?? 0); ?></span>
                                <span class="nova-ai-stat-desc"><?php _e('Requests Sent', 'nova-ai-brainpool'); ?></span>
                            </div>
                            <div class="nova-ai-stat-box">
                                <span class="nova-ai-stat-value"><?php echo round($network_stats['success_rate'] ?? 0, 1); ?>%</span>
                                <span class="nova-ai-stat-desc"><?php _e('Success Rate', 'nova-ai-brainpool'); ?></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Knowledge Sharing -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">🧠</div>
                <h3 class="nova-ai-card-title"><?php _e('Knowledge Sharing', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                
                <div class="nova-ai-knowledge-section">
                    <h4><?php _e('Share Knowledge with Network', 'nova-ai-brainpool'); ?></h4>
                    <p><?php _e('Manually share specific knowledge or content with the NovaNet network to help other nodes.', 'nova-ai-brainpool'); ?></p>
                    
                    <div class="nova-ai-share-form">
                        <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
                            <div>
                                <label class="nova-ai-form-label"><?php _e('Content to Share', 'nova-ai-brainpool'); ?></label>
                                <textarea class="nova-ai-form-textarea nova-ai-share-content" 
                                          rows="3" 
                                          placeholder="<?php _e('Enter knowledge or content to share with the network...', 'nova-ai-brainpool'); ?>"
                                          style="margin-bottom: 0;"></textarea>
                            </div>
                            <div>
                                <label class="nova-ai-form-label"><?php _e('Category', 'nova-ai-brainpool'); ?></label>
                                <select class="nova-ai-form-select nova-ai-share-category" style="margin-bottom: 0;">
                                    <option value="general"><?php _e('General', 'nova-ai-brainpool'); ?></option>
                                    <option value="technical"><?php _e('Technical', 'nova-ai-brainpool'); ?></option>
                                    <option value="documentation"><?php _e('Documentation', 'nova-ai-brainpool'); ?></option>
                                    <option value="tutorial"><?php _e('Tutorial', 'nova-ai-brainpool'); ?></option>
                                    <option value="faq"><?php _e('FAQ', 'nova-ai-brainpool'); ?></option>
                                </select>
                            </div>
                            <button type="button" class="nova-ai-btn nova-ai-btn-primary nova-ai-share-knowledge-btn">
                                <?php _e('Share', 'nova-ai-brainpool'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="nova-ai-share-result" style="margin-top: 1rem; display: none;"></div>
                </div>

                <div class="nova-ai-auto-share-section" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                    <h4><?php _e('Automatic Sharing', 'nova-ai-brainpool'); ?></h4>
                    <div class="nova-ai-auto-share-status">
                        <?php $auto_share = get_option('nova_ai_novanet_auto_share', false); ?>
                        <div class="nova-ai-status-item">
                            <span class="nova-ai-status-label"><?php _e('Auto-Share Crawled Content', 'nova-ai-brainpool'); ?></span>
                            <span class="nova-ai-status <?php echo $auto_share ? 'nova-ai-status-success' : 'nova-ai-status-info'; ?>">
                                <span class="nova-ai-status-dot"></span>
                                <?php echo $auto_share ? __('Enabled', 'nova-ai-brainpool') : __('Disabled', 'nova-ai-brainpool'); ?>
                            </span>
                        </div>
                        
                        <?php if (!$auto_share): ?>
                            <p style="color: #6b7280; margin-top: 0.5rem;">
                                <?php _e('Enable auto-sharing in NovaNet settings to automatically share suitable crawled content with the network.', 'nova-ai-brainpool'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Network Query Tool -->
        <?php if ($novanet_status['registered']): ?>
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">🔍</div>
                <h3 class="nova-ai-card-title"><?php _e('Network Knowledge Query', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                
                <div class="nova-ai-query-section">
                    <h4><?php _e('Query Network Knowledge', 'nova-ai-brainpool'); ?></h4>
                    <p><?php _e('Search for knowledge and information across the entire NovaNet network.', 'nova-ai-brainpool'); ?></p>
                    
                    <div class="nova-ai-query-form">
                        <div style="display: flex; gap: 1rem; align-items: end;">
                            <div style="flex: 1;">
                                <input type="text" 
                                       class="nova-ai-form-input nova-ai-query-input" 
                                       placeholder="<?php _e('Enter your query...', 'nova-ai-brainpool'); ?>"
                                       style="margin-bottom: 0;">
                            </div>
                            <button type="button" class="nova-ai-btn nova-ai-btn-primary nova-ai-query-network-btn">
                                🔍 <?php _e('Query Network', 'nova-ai-brainpool'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="nova-ai-query-results" style="margin-top: 2rem; display: none;">
                        <h5><?php _e('Query Results', 'nova-ai-brainpool'); ?></h5>
                        <div class="nova-ai-query-results-content"></div>
                    </div>
                </div>

            </div>
        </div>
        <?php endif; ?>

        <!-- NovaNet Information -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">❓</div>
                <h3 class="nova-ai-card-title"><?php _e('About NovaNet', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                
                <div class="nova-ai-info-grid">
                    <div class="nova-ai-info-section">
                        <h4><?php _e('What is NovaNet?', 'nova-ai-brainpool'); ?></h4>
                        <p><?php _e('NovaNet is a distributed network that connects Nova AI instances across different websites and servers. It enables knowledge sharing, distributed processing, and collaborative AI capabilities.', 'nova-ai-brainpool'); ?></p>
                    </div>
                    
                    <div class="nova-ai-info-section">
                        <h4><?php _e('Benefits', 'nova-ai-brainpool'); ?></h4>
                        <ul>
                            <li><?php _e('Access to shared knowledge from other nodes', 'nova-ai-brainpool'); ?></li>
                            <li><?php _e('Distributed processing for better performance', 'nova-ai-brainpool'); ?></li>
                            <li><?php _e('Automatic load balancing across the network', 'nova-ai-brainpool'); ?></li>
                            <li><?php _e('Community-driven knowledge improvement', 'nova-ai-brainpool'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="nova-ai-info-section">
                        <h4><?php _e('Privacy & Security', 'nova-ai-brainpool'); ?></h4>
                        <p><?php _e('NovaNet respects your privacy. Only content you explicitly choose to share is sent to the network. Personal conversations and sensitive data remain on your server.', 'nova-ai-brainpool'); ?></p>
                    </div>
                    
                    <div class="nova-ai-info-section">
                        <h4><?php _e('Getting Started', 'nova-ai-brainpool'); ?></h4>
                        <p><?php _e('To join NovaNet, enable it in the plugin settings and register your node. Your AI will then be able to benefit from the collective knowledge of the network.', 'nova-ai-brainpool'); ?></p>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<style>
.nova-ai-connection-status .nova-ai-status-grid {
    display: grid;
    gap: 1rem;
}

.nova-ai-connection-status .nova-ai-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
}

.nova-ai-network-url {
    font-family: monospace;
    font-size: 0.875rem;
    color: #2563eb;
    word-break: break-all;
}

.nova-ai-network-stats {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.nova-ai-stat-section h4 {
    margin: 0 0 1rem 0;
    color: #1e293b;
    font-size: 1.125rem;
    font-weight: 600;
    border-bottom: 2px solid #2563eb;
    padding-bottom: 0.5rem;
}

.nova-ai-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.nova-ai-stat-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1.5rem 1rem;
    text-align: center;
}

.nova-ai-stat-value {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #2563eb;
    margin-bottom: 0.5rem;
}

.nova-ai-stat-desc {
    display: block;
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.nova-ai-share-form {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
    margin-bottom: 1rem;
}

.nova-ai-auto-share-status .nova-ai-status-item {
    background: #f1f5f9;
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.nova-ai-query-form {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.nova-ai-query-results {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1.5rem;
}

.nova-ai-query-results h5 {
    margin: 0 0 1rem 0;
    color: #374151;
    font-weight: 600;
}

.nova-ai-query-result-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.nova-ai-query-result-item:last-child {
    margin-bottom: 0;
}

.nova-ai-query-result-source {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.nova-ai-query-result-content {
    color: #374151;
    line-height: 1.6;
}

.nova-ai-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.nova-ai-info-section h4 {
    margin: 0 0 1rem 0;
    color: #1e293b;
    font-weight: 600;
}

.nova-ai-info-section p,
.nova-ai-info-section ul {
    color: #4b5563;
    line-height: 1.6;
    margin: 0;
}

.nova-ai-info-section ul {
    padding-left: 1.5rem;
}

.nova-ai-info-section li {
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .nova-ai-stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .nova-ai-share-form > div {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .nova-ai-query-form > div {
        flex-direction: column;
        gap: 1rem;
    }
    
    .nova-ai-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Test NovaNet connection
    $('.nova-ai-test-novanet-connection').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('<?php _e('Testing...', 'nova-ai-brainpool'); ?>');
        
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_test_novanet_connection',
                nonce: nova_ai_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    NovaAIAdmin.showNotification('<?php _e('NovaNet connection successful!', 'nova-ai-brainpool'); ?>', 'success');
                } else {
                    NovaAIAdmin.showNotification('<?php _e('Connection failed: ', 'nova-ai-brainpool'); ?>' + response.data.message, 'error');
                }
            },
            error: function() {
                NovaAIAdmin.showNotification('<?php _e('Connection test failed', 'nova-ai-brainpool'); ?>', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Sync with NovaNet
    $('.nova-ai-sync-novanet').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('<?php _e('Syncing...', 'nova-ai-brainpool'); ?>');
        
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_sync_novanet',
                nonce: nova_ai_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    NovaAIAdmin.showNotification('<?php _e('Network sync completed', 'nova-ai-brainpool'); ?>', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    NovaAIAdmin.showNotification('<?php _e('Sync failed: ', 'nova-ai-brainpool'); ?>' + response.data.message, 'error');
                }
            },
            error: function() {
                NovaAIAdmin.showNotification('<?php _e('Sync failed', 'nova-ai-brainpool'); ?>', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Share knowledge with network
    $('.nova-ai-share-knowledge-btn').on('click', function() {
        const content = $('.nova-ai-share-content').val().trim();
        const category = $('.nova-ai-share-category').val();
        const resultDiv = $('.nova-ai-share-result');
        
        if (!content) {
            resultDiv.html('<div class="nova-ai-alert nova-ai-alert-warning">⚠️ <?php _e('Please enter content to share', 'nova-ai-brainpool'); ?></div>').show();
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('<?php _e('Sharing...', 'nova-ai-brainpool'); ?>');
        
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_share_knowledge',
                nonce: nova_ai_admin_ajax.nonce,
                content: content,
                category: category
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<div class="nova-ai-alert nova-ai-alert-success">✅ <?php _e('Knowledge shared successfully with the network!', 'nova-ai-brainpool'); ?></div>').show();
                    $('.nova-ai-share-content').val('');
                } else {
                    resultDiv.html('<div class="nova-ai-alert nova-ai-alert-error">❌ <?php _e('Failed to share knowledge: ', 'nova-ai-brainpool'); ?>' + response.data.message + '</div>').show();
                }
            },
            error: function() {
                resultDiv.html('<div class="nova-ai-alert nova-ai-alert-error">❌ <?php _e('Sharing failed', 'nova-ai-brainpool'); ?></div>').show();
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Query network knowledge
    $('.nova-ai-query-network-btn').on('click', function() {
        const query = $('.nova-ai-query-input').val().trim();
        const resultsDiv = $('.nova-ai-query-results');
        const resultsContent = $('.nova-ai-query-results-content');
        
        if (!query) {
            NovaAIAdmin.showNotification('<?php _e('Please enter a query', 'nova-ai-brainpool'); ?>', 'warning');
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('<?php _e('Querying...', 'nova-ai-brainpool'); ?>');
        resultsContent.html('<div class="nova-ai-loading">🔍 <?php _e('Searching network knowledge...', 'nova-ai-brainpool'); ?></div>');
        resultsDiv.show();
        
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_query_network_knowledge',
                nonce: nova_ai_admin_ajax.nonce,
                query: query
            },
            success: function(response) {
                if (response.success && response.data.results) {
                    const results = response.data.results;
                    
                    if (results.length === 0) {
                        resultsContent.html('<div class="nova-ai-alert nova-ai-alert-info">ℹ️ <?php _e('No results found in the network for your query.', 'nova-ai-brainpool'); ?></div>');
                    } else {
                        let html = '';
                        results.forEach(function(result) {
                            html += '<div class="nova-ai-query-result-item">';
                            html += '<div class="nova-ai-query-result-source">' + (result.source || '<?php _e('Unknown source', 'nova-ai-brainpool'); ?>') + '</div>';
                            html += '<div class="nova-ai-query-result-content">' + result.content + '</div>';
                            html += '</div>';
                        });
                        resultsContent.html(html);
                    }
                } else {
                    resultsContent.html('<div class="nova-ai-alert nova-ai-alert-error">❌ <?php _e('Query failed: ', 'nova-ai-brainpool'); ?>' + (response.data?.message || '<?php _e('Unknown error', 'nova-ai-brainpool'); ?>') + '</div>');
                }
            },
            error: function() {
                resultsContent.html('<div class="nova-ai-alert nova-ai-alert-error">❌ <?php _e('Network query failed', 'nova-ai-brainpool'); ?></div>');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Enter key support for query input
    $('.nova-ai-query-input').on('keypress', function(e) {
        if (e.which === 13) {
            $('.nova-ai-query-network-btn').click();
        }
    });
});
</script>
