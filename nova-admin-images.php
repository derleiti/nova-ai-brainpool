<?php
/**
 * Nova AI Admin Images View
 * 
 * Image generation management and gallery interface
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get the variables passed from the controller
$generated_images = isset($generated_images) ? $generated_images : array();
$generation_stats = isset($generation_stats) ? $generation_stats : array();
?>

<div class="nova-ai-admin-wrap">
    <div class="nova-ai-admin-container">
        
        <div class="nova-ai-admin-header">
            <h1 class="nova-ai-admin-title"><?php _e('Image Generation', 'nova-ai-brainpool'); ?></h1>
            <p class="nova-ai-admin-subtitle"><?php _e('Monitor AI-generated images and manage image generation settings', 'nova-ai-brainpool'); ?></p>
        </div>

        <?php settings_errors('nova_ai_messages'); ?>

        <!-- Generation Statistics -->
        <div class="nova-ai-admin-grid">
            
            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">🖼️</div>
                    <h3 class="nova-ai-card-title"><?php _e('Total Generated', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <span class="nova-ai-stat-number"><?php echo number_format($generation_stats['total_generations'] ?? 0); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Images', 'nova-ai-brainpool'); ?></span>
                    <div class="nova-ai-stat-change positive">
                        <?php printf(__('Last %d days', 'nova-ai-brainpool'), $generation_stats['period_days'] ?? 30); ?>
                    </div>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">🎨</div>
                    <h3 class="nova-ai-card-title"><?php _e('Popular Style', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <?php 
                    $popular_style = 'realistic';
                    if (!empty($generation_stats['by_style']) && is_array($generation_stats['by_style'])) {
                        $popular_style = $generation_stats['by_style'][0]['style'] ?? 'realistic';
                    }
                    ?>
                    <span class="nova-ai-stat-number"><?php echo ucfirst($popular_style); ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Most Used', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">📊</div>
                    <h3 class="nova-ai-card-title"><?php _e('Daily Average', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <?php 
                    $daily_avg = 0;
                    if (!empty($generation_stats['daily_stats']) && is_array($generation_stats['daily_stats'])) {
                        $total_days = count($generation_stats['daily_stats']);
                        $total_images = array_sum(array_column($generation_stats['daily_stats'], 'count'));
                        $daily_avg = $total_days > 0 ? round($total_images / $total_days, 1) : 0;
                    }
                    ?>
                    <span class="nova-ai-stat-number"><?php echo $daily_avg; ?></span>
                    <span class="nova-ai-stat-label"><?php _e('Per Day', 'nova-ai-brainpool'); ?></span>
                </div>
            </div>

            <div class="nova-ai-admin-card nova-ai-stat-card">
                <div class="nova-ai-card-header">
                    <div class="nova-ai-card-icon">⚙️</div>
                    <h3 class="nova-ai-card-title"><?php _e('API Status', 'nova-ai-brainpool'); ?></h3>
                </div>
                <div class="nova-ai-card-content">
                    <?php $api_enabled = get_option('nova_ai_image_generation_enabled', true); ?>
                    <span class="nova-ai-stat-number">
                        <?php echo $api_enabled ? __('Online', 'nova-ai-brainpool') : __('Offline', 'nova-ai-brainpool'); ?>
                    </span>
                    <span class="nova-ai-stat-label"><?php _e('Service', 'nova-ai-brainpool'); ?></span>
                    <div class="nova-ai-stat-change <?php echo $api_enabled ? 'positive' : 'negative'; ?>">
                        <?php echo $api_enabled ? '✅' : '❌'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Style Distribution Chart -->
        <?php if (!empty($generation_stats['by_style'])): ?>
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">📈</div>
                <h3 class="nova-ai-card-title"><?php _e('Generation by Style', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                <div class="nova-ai-style-chart">
                    <?php foreach ($generation_stats['by_style'] as $style_stat): ?>
                        <?php 
                        $percentage = $generation_stats['total_generations'] > 0 
                            ? ($style_stat['count'] / $generation_stats['total_generations']) * 100 
                            : 0;
                        ?>
                        <div class="nova-ai-style-bar">
                            <div class="nova-ai-style-info">
                                <span class="nova-ai-style-name"><?php echo ucfirst($style_stat['style']); ?></span>
                                <span class="nova-ai-style-count"><?php echo number_format($style_stat['count']); ?> (<?php echo round($percentage, 1); ?>%)</span>
                            </div>
                            <div class="nova-ai-style-progress">
                                <div class="nova-ai-style-progress-bar" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Image Generation Controls -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">🎛️</div>
                <h3 class="nova-ai-card-title"><?php _e('Image Generation Controls', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                
                <!-- API Test -->
                <div class="nova-ai-api-test" style="margin-bottom: 2rem;">
                    <h4><?php _e('API Connection Test', 'nova-ai-brainpool'); ?></h4>
                    <p><?php _e('Test your Stable Diffusion API connection', 'nova-ai-brainpool'); ?></p>
                    
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('nova_ai_admin_action', 'nova_ai_nonce'); ?>
                        <input type="hidden" name="nova_ai_action" value="test_image_api">
                        <button type="submit" class="nova-ai-btn nova-ai-btn-secondary">
                            🔗 <?php _e('Test API Connection', 'nova-ai-brainpool'); ?>
                        </button>
                    </form>
                    
                    <div class="nova-ai-api-status" style="margin-top: 1rem; display: none;"></div>
                </div>

                <!-- Test Image Generation -->
                <div class="nova-ai-test-generation" style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                    <h4><?php _e('Test Image Generation', 'nova-ai-brainpool'); ?></h4>
                    <p><?php _e('Generate a test image to verify everything is working correctly', 'nova-ai-brainpool'); ?></p>
                    
                    <div class="nova-ai-test-form">
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.5rem; align-items: end;">
                            <div>
                                <input type="text" 
                                       class="nova-ai-form-input nova-ai-test-prompt" 
                                       placeholder="<?php _e('Enter test prompt...', 'nova-ai-brainpool'); ?>"
                                       value="a beautiful sunset over mountains"
                                       style="margin-bottom: 0;">
                            </div>
                            <div>
                                <select class="nova-ai-form-select nova-ai-test-style" style="margin-bottom: 0;">
                                    <option value="realistic"><?php _e('Realistic', 'nova-ai-brainpool'); ?></option>
                                    <option value="artistic"><?php _e('Artistic', 'nova-ai-brainpool'); ?></option>
                                    <option value="anime"><?php _e('Anime', 'nova-ai-brainpool'); ?></option>
                                    <option value="cartoon"><?php _e('Cartoon', 'nova-ai-brainpool'); ?></option>
                                </select>
                            </div>
                            <div>
                                <select class="nova-ai-form-select nova-ai-test-size" style="margin-bottom: 0;">
                                    <option value="512x512">512x512</option>
                                    <option value="768x768">768x768</option>
                                    <option value="1024x1024">1024x1024</option>
                                </select>
                            </div>
                            <button type="button" class="nova-ai-btn nova-ai-btn-primary nova-ai-test-generate-btn">
                                <?php _e('Generate', 'nova-ai-brainpool'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="nova-ai-test-result" style="margin-top: 1rem; display: none;"></div>
                </div>

                <!-- Cleanup Tools -->
                <div class="nova-ai-cleanup-tools" style="padding-top: 2rem; border-top: 1px solid #e2e8f0;">
                    <h4><?php _e('Maintenance', 'nova-ai-brainpool'); ?></h4>
                    <div class="nova-ai-btn-group">
                        <button type="button" class="nova-ai-btn nova-ai-btn-secondary nova-ai-cleanup-old-images">
                            🧹 <?php _e('Cleanup Old Images', 'nova-ai-brainpool'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=nova-ai-settings#image-settings'); ?>" class="nova-ai-btn nova-ai-btn-outline">
                            ⚙️ <?php _e('Image Settings', 'nova-ai-brainpool'); ?>
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- Generated Images Gallery -->
        <div class="nova-ai-admin-card">
            <div class="nova-ai-card-header">
                <div class="nova-ai-card-icon">🖼️</div>
                <h3 class="nova-ai-card-title"><?php _e('Recent Generated Images', 'nova-ai-brainpool'); ?></h3>
            </div>
            <div class="nova-ai-card-content">
                
                <?php if (!empty($generated_images)): ?>
                    <div class="nova-ai-images-gallery">
                        <?php foreach ($generated_images as $image): ?>
                            <div class="nova-ai-image-card">
                                <?php if (!empty($image['image_url'])): ?>
                                    <div class="nova-ai-image-preview">
                                        <img src="<?php echo esc_url($image['image_url']); ?>" 
                                             alt="<?php echo esc_attr($image['prompt']); ?>"
                                             onclick="window.open('<?php echo esc_url($image['image_url']); ?>', '_blank')">
                                        <div class="nova-ai-image-overlay">
                                            <button type="button" onclick="window.open('<?php echo esc_url($image['image_url']); ?>', '_blank')" title="<?php _e('View Full Size', 'nova-ai-brainpool'); ?>">
                                                🔍
                                            </button>
                                            <button type="button" onclick="window.open('<?php echo esc_url($image['image_url']); ?>', '_blank')" title="<?php _e('Download', 'nova-ai-brainpool'); ?>">
                                                💾
                                            </button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="nova-ai-image-placeholder">
                                        <span>🖼️</span>
                                        <small><?php _e('Image not available', 'nova-ai-brainpool'); ?></small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="nova-ai-image-meta">
                                    <div class="nova-ai-image-prompt" title="<?php echo esc_attr($image['prompt']); ?>">
                                        <strong><?php _e('Prompt:', 'nova-ai-brainpool'); ?></strong>
                                        <?php echo esc_html(wp_trim_words($image['prompt'], 10)); ?>
                                    </div>
                                    
                                    <div class="nova-ai-image-details">
                                        <span class="nova-ai-image-style">
                                            <strong><?php _e('Style:', 'nova-ai-brainpool'); ?></strong>
                                            <?php echo ucfirst($image['style']); ?>
                                        </span>
                                        <span class="nova-ai-image-size">
                                            <strong><?php _e('Size:', 'nova-ai-brainpool'); ?></strong>
                                            <?php echo $image['width']; ?>x<?php echo $image['height']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="nova-ai-image-date">
                                        <?php echo human_time_diff(strtotime($image['created_at'])); ?> <?php _e('ago', 'nova-ai-brainpool'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 2rem; text-align: center; color: #6b7280;">
                        <?php printf(__('Showing latest %d images', 'nova-ai-brainpool'), count($generated_images)); ?>
                    </div>
                    
                <?php else: ?>
                    <div class="nova-ai-empty-state">
                        <div class="nova-ai-empty-icon">🖼️</div>
                        <h3><?php _e('No Images Generated Yet', 'nova-ai-brainpool'); ?></h3>
                        <p><?php _e('No images have been generated yet. Users can create images using the chat interface or image generator shortcode.', 'nova-ai-brainpool'); ?></p>
                        <button type="button" class="nova-ai-btn nova-ai-btn-primary nova-ai-test-generate-trigger">
                            🎨 <?php _e('Generate Test Image', 'nova-ai-brainpool'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
.nova-ai-style-chart {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.nova-ai-style-bar {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.nova-ai-style-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nova-ai-style-name {
    font-weight: 600;
    color: #374151;
}

.nova-ai-style-count {
    color: #6b7280;
    font-size: 0.875rem;
}

.nova-ai-style-progress {
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.nova-ai-style-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #2563eb, #3b82f6);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.nova-ai-images-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.nova-ai-image-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.nova-ai-image-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.nova-ai-image-preview {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
}

.nova-ai-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.2s;
}

.nova-ai-image-preview:hover img {
    transform: scale(1.05);
}

.nova-ai-image-overlay {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    gap: 0.25rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.nova-ai-image-preview:hover .nova-ai-image-overlay {
    opacity: 1;
}

.nova-ai-image-overlay button {
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
}

.nova-ai-image-overlay button:hover {
    background: rgba(0, 0, 0, 0.9);
}

.nova-ai-image-placeholder {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    color: #6b7280;
}

.nova-ai-image-placeholder span {
    font-size: 3rem;
    margin-bottom: 0.5rem;
}

.nova-ai-image-meta {
    padding: 1rem;
}

.nova-ai-image-prompt {
    margin-bottom: 0.75rem;
    color: #374151;
    font-size: 0.875rem;
    line-height: 1.4;
}

.nova-ai-image-details {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.nova-ai-image-date {
    font-size: 0.75rem;
    color: #9ca3af;
    text-align: right;
}

.nova-ai-test-form {
    margin-bottom: 1rem;
}

.nova-ai-test-result {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem;
}

.nova-ai-test-result img {
    max-width: 200px;
    height: auto;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .nova-ai-images-gallery {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .nova-ai-test-form > div {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .nova-ai-image-details {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Test image generation
    $('.nova-ai-test-generate-btn, .nova-ai-test-generate-trigger').on('click', function() {
        const prompt = $('.nova-ai-test-prompt').val().trim() || 'a beautiful sunset over mountains';
        const style = $('.nova-ai-test-style').val() || 'realistic';
        const size = $('.nova-ai-test-size').val() || '512x512';
        const [width, height] = size.split('x').map(Number);
        
        const $btn = $(this);
        const originalText = $btn.text();
        const resultDiv = $('.nova-ai-test-result');
        
        $btn.prop('disabled', true).text('<?php _e('Generating...', 'nova-ai-brainpool'); ?>');
        
        resultDiv.html('<div class="nova-ai-loading">🎨 <?php _e('Generating image, please wait...', 'nova-ai-brainpool'); ?></div>').show();
        
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_generate_image',
                nonce: nova_ai_admin_ajax.nonce,
                prompt: prompt,
                style: style,
                width: width,
                height: height
            },
            timeout: 120000, // 2 minutes
            success: function(response) {
                if (response.success && response.data.image_url) {
                    resultDiv.html(`
                        <div style="text-align: center;">
                            <img src="${response.data.image_url}" alt="${prompt}" onclick="window.open('${response.data.image_url}', '_blank')" style="cursor: pointer;">
                            <div style="margin-top: 1rem;">
                                <p><strong><?php _e('Prompt:', 'nova-ai-brainpool'); ?></strong> ${prompt}</p>
                                <p><strong><?php _e('Style:', 'nova-ai-brainpool'); ?></strong> ${style} | <strong><?php _e('Size:', 'nova-ai-brainpool'); ?></strong> ${size}</p>
                                <button type="button" onclick="window.open('${response.data.image_url}', '_blank')" class="nova-ai-btn nova-ai-btn-outline nova-ai-btn-small">
                                    💾 <?php _e('Download', 'nova-ai-brainpool'); ?>
                                </button>
                            </div>
                        </div>
                    `);
                    NovaAIAdmin.showNotification('<?php _e('Test image generated successfully!', 'nova-ai-brainpool'); ?>', 'success');
                } else {
                    resultDiv.html(`<div class="nova-ai-alert nova-ai-alert-error">❌ <?php _e('Generation failed: ', 'nova-ai-brainpool'); ?>${response.data?.message || '<?php _e('Unknown error', 'nova-ai-brainpool'); ?>'}</div>`);
                }
            },
            error: function() {
                resultDiv.html('<div class="nova-ai-alert nova-ai-alert-error">❌ <?php _e('Generation failed due to network error', 'nova-ai-brainpool'); ?></div>');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Cleanup old images
    $('.nova-ai-cleanup-old-images').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to cleanup old images? This will remove images older than 30 days.', 'nova-ai-brainpool'); ?>')) {
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('<?php _e('Cleaning...', 'nova-ai-brainpool'); ?>');
        
        $.ajax({
            url: nova_ai_admin_ajax.ajax_url,
            method: 'POST',
            data: {
                action: 'nova_ai_cleanup_old_images',
                nonce: nova_ai_admin_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    NovaAIAdmin.showNotification('<?php printf(__('Cleaned up %s old images', 'nova-ai-brainpool'), '${response.data.count || 0}'); ?>', 'success');
                } else {
                    NovaAIAdmin.showNotification('<?php _e('Cleanup failed', 'nova-ai-brainpool'); ?>', 'error');
                }
            },
            error: function() {
                NovaAIAdmin.showNotification('<?php _e('Cleanup failed', 'nova-ai-brainpool'); ?>', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Image gallery interactions
    $('.nova-ai-image-preview img').on('click', function() {
        const imgSrc = $(this).attr('src');
        const prompt = $(this).attr('alt');
        
        // Create modal overlay
        const modal = $(`
            <div class="nova-ai-image-modal" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 999999;
                cursor: pointer;
            ">
                <div style="
                    max-width: 90vw;
                    max-height: 90vh;
                    text-align: center;
                ">
                    <img src="${imgSrc}" alt="${prompt}" style="
                        max-width: 100%;
                        max-height: 80vh;
                        border-radius: 8px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
                    ">
                    <div style="
                        color: white;
                        margin-top: 1rem;
                        font-size: 0.875rem;
                        max-width: 600px;
                        margin-left: auto;
                        margin-right: auto;
                    ">${prompt}</div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        
        modal.on('click', function() {
            $(this).remove();
        });
    });
});
</script>
