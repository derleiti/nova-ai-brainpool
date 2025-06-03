<?php
/**
 * Nova AI Admin Conversations View
 * 
 * Chat conversation management and analytics interface
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get the variables passed from the controller
$conversations = isset($conversations) ? $conversations : array();
$conversation_stats = isset($conversation_stats) ? $conversation_stats : array();
?>

<div class="nova-ai-admin-wrap">
    <div class="nova-ai-admin-container">
        
        <div class="nova-ai-admin-header">
            <h1 class="nova-ai-admin-title"><?php _e('Chat Conversations', 'nova-ai-brainpool'); ?></h1>
            <p class="nova-ai-admin-subtitle"><?php _e('Monitor and manage AI chat conversations and user interactions', 'nova-ai-brainpool'); ?></p>
        </div>

        <?php settings_errors('nova_ai_messages'); ?>

        <!-- Conversation Statistics -->
        <div class="nova-ai-admin-grid">
            
            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">💬</div>
                    <h3 class="nova-ai-card-title"><?php _e('Total Messages', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number"><?php echo number_format($conversation_stats['total_messages'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('All Time', 'nova-ai-brainpool'); ?></span>
                    <div class="nova-ai-stat-change positive">
                        <?php printf(__('Last %d days', 'nova-ai-brainpool'), $conversation_stats['period_days'] ?? 30); ?>
                    </div>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">👥</div>
                    <h3 class="nova-ai-card-title"><?php _e('User Messages', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number"><?php echo number_format($conversation_stats['user_messages'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('From Users', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">🤖</div>
                    <h3 class="nova-ai-card-title"><?php _e('AI Responses', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number"><?php echo number_format($conversation_stats['ai_responses'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Generated', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">📊</div>
                    <h3 class="nova-ai-card-title"><?php _e('Avg Messages', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number"><?php echo number_format($conversation_stats['avg_messages_per_conversation'] ?? 0, 1); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Per Conversation', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>
        </div>

        <!-- Conversation Management -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">🎛️</div>
                <h3 class="nova-ai-card-title"><?php _e('Conversation Management', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                
                <!-- Search and Filters -->
                <div class="nova-ai-conversation-filters" style="margin-bottom: 2rem;">
                    <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
                        <div>
                            <label class="nova-ai-form-label"><?php _e('Search Conversations', 'nova-ai-brainpool'); ?></label>
                            <input type="text" 
                                   class="nova-ai-form-input nova-ai-conversation-search" 
                                   placeholder="<?php _e('Search by title, user, or conversation ID...', 'nova-ai-brainpool'); ?>"
                                   style="margin-bottom: 0;">
                        </div>
                        <div>
                            <label class="nova-ai-form-label"><?php _e('Time Range', 'nova-ai-brainpool'); ?></label>
                            <select class="nova-ai-form-select nova-ai-conversation-filter" style="margin-bottom: 0;">
                                <option value="all"><?php _e('All Time', 'nova-ai-brainpool'); ?></option>
                                <option value="today"><?php _e('Today', 'nova-ai-brainpool'); ?></option>
                                <option value="week"><?php _e('This Week', 'nova-ai-brainpool'); ?></option>
                                <option value="month"><?php _e('This Month', 'nova-ai-brainpool'); ?></option>
                            </select>
                        </div>
                        <button type="button" class="nova-ai-btn nova-ai-btn-secondary nova-ai-filter-btn">
                            🔍 <?php _e('Filter', 'nova-ai-brainpool'); ?>
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="nova-ai-bulk-actions" style="margin-bottom: 1rem;">
                    <div class="nova-ai-btn-group">
                        <button type="button" class="nova-ai-btn nova-ai-btn-outline nova-ai-export-conversations">
                            📁 <?php _e('Export Selected', 'nova-ai-brainpool'); ?>
                        </button>
                        <button type="button" class="nova-ai-btn nova-ai-btn-danger nova-ai-delete-conversations">
                            🗑️ <?php _e('Delete Selected', 'nova-ai-brainpool'); ?>
                        </button>
                        <form method="post" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to cleanup old conversations?', 'nova-ai-brainpool'); ?>')">
                            <?php wp_nonce_field('nova_ai_admin_action', 'nova_ai_nonce'); ?>
                            <input type="hidden" name="nova_ai_action" value="cleanup_old_data">
                            <button type="submit" class="nova-ai-btn nova-ai-btn-warning">
                                🧹 <?php _e('Cleanup Old Data', 'nova-ai-brainpool'); ?>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        <!-- Conversations List -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">💬</div>
                <h3 class="nova-ai-card-title"><?php _e('Recent Conversations', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                
                <?php if (!empty($conversations)): ?>
                    <div class="nova-ai-table-container">
                        <table class="nova-ai-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" class="nova-ai-select-all-conversations">
                                    </th>
                                    <th><?php _e('Conversation', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('User', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Messages', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Started', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Last Activity', 'nova-ai-brainpool'); ?></th>
                                    <th><?php _e('Actions', 'nova-ai-brainpool'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($conversations as $conversation): ?>
                                    <tr class="nova-ai-conversation-row" data-conversation-id="<?php echo esc_attr($conversation['conversation_id']); ?>">
                                        <td>
                                            <input type="checkbox" class="nova-ai-conversation-checkbox" value="<?php echo esc_attr($conversation['conversation_id']); ?>">
                                        </td>
                                        <td>
                                            <div class="nova-ai-conversation-info">
                                                <div class="nova-ai-conversation-title">
                                                    <?php 
                                                    $title = !empty($conversation['title']) ? $conversation['title'] : __('Untitled Conversation', 'nova-ai-brainpool');
                                                    echo esc_html(wp_trim_words($title, 8));
                                                    ?>
                                                </div>
                                                <div class="nova-ai-conversation-id">
                                                    ID: <?php echo esc_html(substr($conversation['conversation_id'], 0, 12)); ?>...
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($conversation['user_name'])): ?>
                                                <div class="nova-ai-user-info">
                                                    <strong><?php echo esc_html($conversation['user_name']); ?></strong>
                                                    <?php if ($conversation['user_id']): ?>
                                                        <div style="font-size: 0.75rem; color: #6b7280;">
                                                            ID: <?php echo intval($conversation['user_id']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="nova-ai-guest-user">
                                                    👤 <?php _e('Guest User', 'nova-ai-brainpool'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="nova-ai-message-count">
                                                <?php echo number_format($conversation['message_count'] ?? 0); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($conversation['created_at'])) {
                                                echo '<span title="' . esc_attr($conversation['created_at']) . '">';
                                                echo human_time_diff(strtotime($conversation['created_at'])) . ' ' . __('ago', 'nova-ai-brainpool');
                                                echo '</span>';
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($conversation['updated_at'])) {
                                                echo '<span title="' . esc_attr($conversation['updated_at']) . '">';
                                                echo human_time_diff(strtotime($conversation['updated_at'])) . ' ' . __('ago', 'nova-ai-brainpool');
                                                echo '</span>';
                                            } else {
                                                echo '—';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="nova-ai-conversation-actions">
                                                <button type="button" 
                                                        class="nova-ai-btn nova-ai-btn-outline nova-ai-btn-small nova-ai-view-conversation" 
                                                        data-conversation-id="<?php echo esc_attr($conversation['conversation_id']); ?>"
                                                        title="<?php _e('View Conversation', 'nova-ai-brainpool'); ?>">
                                                    👁️
                                                </button>
                                                <button type="button" 
                                                        class="nova-ai-btn nova-ai-btn-outline nova-ai-btn-small nova-ai-export-single-conversation" 
                                                        data-conversation-id="<?php echo esc_attr($conversation['conversation_id']); ?>"
                                                        title="<?php _e('Export Conversation', 'nova-ai-brainpool'); ?>">
                                                    📁
                                                </button>
                                                <button type="button" 
                                                        class="nova-ai-btn nova-ai-btn-danger nova-ai-btn-small nova-ai-delete-single-conversation" 
                                                        data-conversation-id="<?php echo esc_attr($conversation['conversation_id']); ?>"
                                                        title="<?php _e('Delete Conversation', 'nova-ai-brainpool'); ?>">
                                                    🗑️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 1rem; text-align: center; color: #6b7280;">
                        <?php printf(__('Showing latest %d conversations', 'nova-ai-brainpool'), count($conversations)); ?>
                    </div>
                    
                <?php else: ?>
                    <div class="nova-ai-empty-state">
                        <div class="nova-ai-empty-icon">💬</div>
                        <h3><?php _e('No Conversations Yet', 'nova-ai-brainpool'); ?></h3>
                        <p><?php _e('No chat conversations have been recorded yet. Conversations will appear here once users start chatting with the AI assistant.', 'nova-ai-brainpool'); ?></p>
                        <?php if (!get_option('nova_ai_save_conversations', true)): ?>
                            <div class="nova-ai-alert nova-ai-alert-info" style="margin-top: 1rem;">
                                <div class="nova-ai-alert-icon">ℹ️</div>
                                <div class="nova-ai-alert-content">
                                    <div class="nova-ai-alert-title"><?php _e('Conversation Saving Disabled', 'nova-ai-brainpool'); ?></div>
                                    <?php _e('Conversation saving is currently disabled. Enable it in settings to see conversations here.', 'nova-ai-brainpool'); ?>
                                    <a href="<?php echo admin_url('admin.php?page=nova-ai-settings#general-settings'); ?>" style="color: #1e40af; text-decoration: underline;">
                                        <?php _e('Enable Now', 'nova-ai-brainpool'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Conversation Detail Modal -->
<div id="nova-ai-conversation-modal" class="nova-ai-modal" style="display: none;">
    <div class="nova-ai-modal-overlay"></div>
    <div class="nova-ai-modal-content">
        <div class="nova-ai-modal-header">
            <h3><?php _e('Conversation Details', 'nova-ai-brainpool'); ?></h3>
            <button type="button" class="nova-ai-modal-close">&times;</button>
        </div>
        <div class="nova-ai-modal-body">
            <div class="nova-ai-conversation-details">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
        <div class="nova-ai-modal-footer">
            <button type="button" class="nova-ai-btn nova-ai-btn-outline nova-ai-modal-close">
                <?php _e('Close', 'nova-ai-brainpool'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.nova-ai-conversation-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.nova-ai-conversation-title {
    font-weight: 600;
    color: #374151;
}

.nova-ai-conversation-id {
    font-size: 0.75rem;
    color: #6b7280;
    font-family: monospace;
}

.nova-ai-user-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.nova-ai-guest-user {
    color: #6b7280;
    font-style: italic;
}

.nova-ai-message-count {
    font-weight: 600;
    color: #2563eb;
}

.nova-ai-conversation-actions {
    display: flex;
    gap: 0.25rem;
}

.nova-ai-conversation-filters {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.nova-ai-bulk-actions {
    padding: 1rem;
    background: #f1f5f9;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

/* Modal Styles */
.nova-ai-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nova-ai-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.nova-ai-modal-content {
    position: relative;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
    max-width: 90vw;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    width: 800px;
}

.nova-ai-modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nova-ai-modal-header h3 {
    margin: 0;
    color: #1e293b;
}

.nova-ai-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.nova-ai-modal-close:hover {
    background: #f3f4f6;
}

.nova-ai-modal-body {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
}

.nova-ai-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.nova-ai-conversation-messages {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

.nova-ai-conversation-message {
    padding: 1rem;
    border-radius: 0.5rem;
    border: 1px solid #e2e8f0;
}

.nova-ai-conversation-message.user {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    margin-left: 2rem;
}

.nova-ai-conversation-message.assistant {
    background: #f8fafc;
    margin-right: 2rem;
}

.nova-ai-message-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.75rem;
    opacity: 0.8;
}

.nova-ai-message-content {
    line-height: 1.6;
}

@media (max-width: 768px) {
    .nova-ai-conversation-filters > div {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .nova-ai-conversation-actions {
        flex-direction: column;
        gap: 0.125rem;
    }
    
    .nova-ai-bulk-actions .nova-ai-btn-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .nova-ai-modal-content {
        width: 95vw;
        max-height: 95vh;
    }
    
    .nova-ai-conversation-message.user {
        margin-left: 0.5rem;
    }
    
    .nova-ai-conversation-message.assistant {
        margin-right: 0.5rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Select all conversations checkbox
    $('.nova-ai-select-all-conversations').on('change', function() {
        $('.nova-ai-conversation-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // View conversation details
    $('.nova-ai-view-conversation').on('click', function() {
        const conversationId = $(this).data('conversation-id');
        loadConversationDetails(conversationId);
    });
    
    // Delete single conversation
    $('.nova-ai-delete-single-conversation').on('click', function() {
        const conversationId = $(this).data('conversation-id');
        if (confirm('<?php _e('Are you sure you want to delete this conversation?', 'nova-ai-brainpool'); ?>')) {
            deleteConversations([conversationId]);
        }
    });
    
    // Export single conversation
    $('.nova-ai-export-single-conversation').on('click', function() {
        const conversationId = $(this).data('conversation-id');
        exportConversations([conversationId]);
    });
    
    // Bulk delete conversations
    $('.nova-ai-delete-conversations').on('click', function() {
        const selected = getSelectedConversations();
        if (selected.length === 0) {
            NovaAIAdmin.showNotification('<?php _e('Please select conversations to delete', 'nova-ai-brainpool'); ?>', 'warning');
            return;
        }
        
        if (confirm('<?php printf(__('Are you sure you want to delete %s conversations?', 'nova-ai-brainpool'), '${selected.length}'); ?>')) {
            deleteConversations(selected);
        }
    });
    
    // Bulk export conversations
    $('.nova-ai-export-conversations').on('click', function() {
        const selected = getSelectedConversations();
        if (selected.length === 0) {
            NovaAIAdmin.showNotification('<?php _e('Please select conversations to export', 'nova-ai-brainpool'); ?>', 'warning');
            return;
        }
        
        exportConversations(selected);
    });
    
    // Search and filter
    $('.nova-ai-filter-btn').on('click', function() {
        const search = $('.nova-ai-conversation-search').val();
        const filter = $('.nova-ai-conversation-filter').val();
        
        filterConversations(search, filter);
    });
    
    // Real-time search
    let searchTimeout;
    $('.nova-ai-conversation-search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const search = $(this).val();
            const filter = $('.nova-ai-conversation-filter').val();
            filterConversations(search, filter);
        }, 500);
    });
    
    function getSelectedConversations() {
        const selected = [];
        $('.nova-ai-conversation-checkbox:checked').each(function() {
            selected.push($(this).val());
        });
        return selected;
    }
    
    function loadConversationDetails(conversationId) {
        const modal = $('#nova-ai-conversation-modal');
        const content = modal.find('.nova-ai-conversation-details');
        
        content.html('<div class="nova-ai-loading">Loading conversation...</div>');
        modal.show();
        
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_get_conversation_details',
                nonce: nova_ai_admin_ajax.nonce,
                conversation_id: conversationId
            },
            success: function(response) {
                if (response.success) {
                    const conversation = response.data;
                    let html = '<div class="nova-ai-conversation-meta">';
                    html += `<p><strong><?php _e('Conversation ID:', 'nova-ai-brainpool'); ?></strong> ${conversation.conversation_id}</p>`;
                    html += `<p><strong><?php _e('Title:', 'nova-ai-brainpool'); ?></strong> ${conversation.title || '<?php _e('Untitled', 'nova-ai-brainpool'); ?>'}</p>`;
                    html += `<p><strong><?php _e('Messages:', 'nova-ai-brainpool'); ?></strong> ${conversation.messages.length}</p>`;
                    html += `<p><strong><?php _e('Created:', 'nova-ai-brainpool'); ?></strong> ${conversation.created_at}</p>`;
                    html += '</div>';
                    
                    html += '<div class="nova-ai-conversation-messages">';
                    conversation.messages.forEach(function(message) {
                        html += `<div class="nova-ai-conversation-message ${message.role}">`;
                        html += `<div class="nova-ai-message-meta">`;
                        html += `<span>${message.role === 'user' ? '<?php _e('User', 'nova-ai-brainpool'); ?>' : '<?php _e('AI Assistant', 'nova-ai-brainpool'); ?>'}</span>`;
                        html += `<span>${message.created_at}</span>`;
                        html += `</div>`;
                        html += `<div class="nova-ai-message-content">${message.content.replace(/\n/g, '<br>')}</div>`;
                        html += `</div>`;
                    });
                    html += '</div>';
                    
                    content.html(html);
                } else {
                    content.html(`<div class="nova-ai-alert nova-ai-alert-error"><?php _e('Failed to load conversation details', 'nova-ai-brainpool'); ?></div>`);
                }
            },
            error: function() {
                content.html(`<div class="nova-ai-alert nova-ai-alert-error"><?php _e('Failed to load conversation details', 'nova-ai-brainpool'); ?></div>`);
            }
        });
    }
    
    function deleteConversations(conversationIds) {
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_delete_conversations',
                nonce: nova_ai_admin_ajax.nonce,
                conversation_ids: conversationIds
            },
            success: function(response) {
                if (response.success) {
                    NovaAIAdmin.showNotification('<?php _e('Conversations deleted successfully', 'nova-ai-brainpool'); ?>', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    NovaAIAdmin.showNotification('<?php _e('Failed to delete conversations', 'nova-ai-brainpool'); ?>', 'error');
                }
            },
            error: function() {
                NovaAIAdmin.showNotification('<?php _e('Failed to delete conversations', 'nova-ai-brainpool'); ?>', 'error');
            }
        });
    }
    
    function exportConversations(conversationIds) {
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_export_conversations',
                nonce: nova_ai_admin_ajax.nonce,
                conversation_ids: conversationIds
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    const blob = new Blob([JSON.stringify(response.data, null, 2)], {
                        type: 'application/json'
                    });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `nova-ai-conversations-${new Date().toISOString().split('T')[0]}.json`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    
                    NovaAIAdmin.showNotification('<?php _e('Conversations exported successfully', 'nova-ai-brainpool'); ?>', 'success');
                } else {
                    NovaAIAdmin.showNotification('<?php _e('Failed to export conversations', 'nova-ai-brainpool'); ?>', 'error');
                }
            },
            error: function() {
                NovaAIAdmin.showNotification('<?php _e('Failed to export conversations', 'nova-ai-brainpool'); ?>', 'error');
            }
        });
    }
    
    function filterConversations(search, timeFilter) {
        $('.nova-ai-conversation-row').each(function() {
            const $row = $(this);
            const title = $row.find('.nova-ai-conversation-title').text().toLowerCase();
            const userName = $row.find('.nova-ai-user-info strong').text().toLowerCase();
            const conversationId = $row.data('conversation-id').toLowerCase();
            
            const matchesSearch = !search || 
                title.includes(search.toLowerCase()) || 
                userName.includes(search.toLowerCase()) || 
                conversationId.includes(search.toLowerCase());
            
            // Time filter logic would go here
            const matchesTime = true; // Simplified for now
            
            if (matchesSearch && matchesTime) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    }
});
</script>
