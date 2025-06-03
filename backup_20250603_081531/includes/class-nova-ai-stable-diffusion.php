<?php
/**
 * Nova AI Stable Diffusion Class
 * 
 * Handles image generation via Stable Diffusion API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nova_AI_Stable_Diffusion {
    
    private $api_url;
    private $enabled;
    private $max_size;
    private $upload_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_url = get_option('nova_ai_image_api_url', 'https://ailinux.me:7860');
        $this->enabled = get_option('nova_ai_image_generation_enabled', true);
        $this->max_size = intval(get_option('nova_ai_max_image_size', 1024));
        
        // Set up upload directory
        $upload = wp_upload_dir();
        $this->upload_dir = $upload['basedir'] . '/nova-ai-images';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
    }
    
    /**
     * Generate image
     */
    public function generate_image($prompt, $style = 'realistic', $width = 512, $height = 512, $save_local = true) {
        if (!$this->enabled) {
            throw new Exception(__('Image generation is disabled', 'nova-ai-brainpool'));
        }
        
        // Validate dimensions
        $width = $this->validate_dimension($width);
        $height = $this->validate_dimension($height);
        
        // Enhance prompt based on style
        $enhanced_prompt = $this->enhance_prompt($prompt, $style);
        
        // Generate image via API
        $image_data = $this->call_image_api($enhanced_prompt, $width, $height, $style);
        
        $image_url = null;
        $local_path = null;
        
        if ($save_local && isset($image_data['image'])) {
            // Save image locally
            $local_path = $this->save_image_locally($image_data['image'], $prompt);
            $image_url = $this->get_image_url($local_path);
        } elseif (isset($image_data['url'])) {
            $image_url = $image_data['url'];
        }
        
        // Store generation record
        $this->store_generation_record($prompt, $style, $width, $height, $image_url, $local_path);
        
        return $image_url;
    }
    
    /**
     * Validate image dimension
     */
    private function validate_dimension($dimension) {
        $dimension = intval($dimension);
        
        // Ensure it's within bounds
        if ($dimension < 128) {
            $dimension = 128;
        } elseif ($dimension > $this->max_size) {
            $dimension = $this->max_size;
        }
        
        // Round to nearest multiple of 64
        $dimension = round($dimension / 64) * 64;
        
        return $dimension;
    }
    
    /**
     * Enhance prompt based on style
     */
    private function enhance_prompt($prompt, $style) {
        $style_prompts = array(
            'realistic' => array(
                'prefix' => 'photorealistic, high quality, detailed, 8k resolution,',
                'suffix' => ', professional photography, sharp focus, good lighting'
            ),
            'artistic' => array(
                'prefix' => 'artistic, painting, fine art, masterpiece,',
                'suffix' => ', beautiful composition, vibrant colors, artistic style'
            ),
            'anime' => array(
                'prefix' => 'anime style, manga, japanese animation,',
                'suffix' => ', colorful, detailed anime art, studio quality'
            ),
            'cartoon' => array(
                'prefix' => 'cartoon style, animated, colorful,',
                'suffix' => ', fun, playful, cartoon illustration'
            ),
            'fantasy' => array(
                'prefix' => 'fantasy art, magical, mystical,',
                'suffix' => ', epic fantasy scene, detailed fantasy artwork'
            ),
            'scifi' => array(
                'prefix' => 'science fiction, futuristic, cyberpunk,',
                'suffix' => ', high tech, sci-fi concept art'
            )
        );
        
        $style_config = $style_prompts[$style] ?? $style_prompts['realistic'];
        
        $enhanced = $style_config['prefix'] . ' ' . $prompt . ' ' . $style_config['suffix'];
        
        // Add negative prompts
        $negative_prompts = array(
            'low quality', 'blurry', 'pixelated', 'distorted', 'ugly',
            'bad anatomy', 'extra limbs', 'deformed', 'watermark'
        );
        
        return array(
            'prompt' => $enhanced,
            'negative_prompt' => implode(', ', $negative_prompts)
        );
    }
    
    /**
     * Call image generation API
     */
    private function call_image_api($enhanced_prompt, $width, $height, $style) {
        $endpoint = rtrim($this->api_url, '/') . '/sdapi/v1/txt2img';
        
        $data = array(
            'prompt' => $enhanced_prompt['prompt'],
            'negative_prompt' => $enhanced_prompt['negative_prompt'],
            'width' => $width,
            'height' => $height,
            'steps' => 20,
            'cfg_scale' => 7,
            'sampler_name' => 'Euler a',
            'n_iter' => 1,
            'batch_size' => 1,
            'seed' => -1,
            'restore_faces' => false,
            'tiling' => false,
            'enable_hr' => false
        );
        
        // Add style-specific parameters
        $data = $this->add_style_parameters($data, $style);
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 120 // 2 minutes for image generation
        );
        
        nova_ai_log('Calling image API: ' . $endpoint, 'info');
        
        $response = wp_remote_request($endpoint, $args);
        
        if (is_wp_error($response)) {
            nova_ai_log('Image API request failed: ' . $response->get_error_message(), 'error');
            throw new Exception(__('Image generation request failed', 'nova-ai-brainpool'));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            nova_ai_log('Image API returned error: ' . $response_code . ' - ' . $response_body, 'error');
            throw new Exception(sprintf(__('Image API returned error: %d', 'nova-ai-brainpool'), $response_code));
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            nova_ai_log('Invalid JSON response from image API', 'error');
            throw new Exception(__('Invalid image API response', 'nova-ai-brainpool'));
        }
        
        if (!isset($decoded_response['images']) || empty($decoded_response['images'])) {
            throw new Exception(__('No images generated', 'nova-ai-brainpool'));
        }
        
        return array(
            'image' => $decoded_response['images'][0],
            'info' => $decoded_response['info'] ?? null
        );
    }
    
    /**
     * Add style-specific parameters
     */
    private function add_style_parameters($data, $style) {
        switch ($style) {
            case 'realistic':
                $data['cfg_scale'] = 7;
                $data['steps'] = 25;
                break;
                
            case 'artistic':
                $data['cfg_scale'] = 9;
                $data['steps'] = 30;
                break;
                
            case 'anime':
                $data['cfg_scale'] = 8;
                $data['steps'] = 20;
                break;
                
            case 'cartoon':
                $data['cfg_scale'] = 6;
                $data['steps'] = 15;
                break;
        }
        
        return $data;
    }
    
    /**
     * Save image locally
     */
    private function save_image_locally($base64_image, $prompt) {
        // Generate filename
        $filename = $this->generate_filename($prompt) . '.png';
        $filepath = $this->upload_dir . '/' . $filename;
        
        // Decode base64 image
        $image_data = base64_decode($base64_image);
        
        if ($image_data === false) {
            throw new Exception(__('Failed to decode image data', 'nova-ai-brainpool'));
        }
        
        // Save to file
        $result = file_put_contents($filepath, $image_data);
        
        if ($result === false) {
            throw new Exception(__('Failed to save image file', 'nova-ai-brainpool'));
        }
        
        return $filename;
    }
    
    /**
     * Generate filename from prompt
     */
    private function generate_filename($prompt) {
        // Create safe filename from prompt
        $filename = sanitize_title($prompt);
        $filename = substr($filename, 0, 50); // Limit length
        
        // Add timestamp to make unique
        $filename .= '_' . time();
        
        return $filename;
    }
    
    /**
     * Get image URL from local path
     */
    private function get_image_url($local_path) {
        $upload = wp_upload_dir();
        return $upload['baseurl'] . '/nova-ai-images/' . $local_path;
    }
    
    /**
     * Store generation record
     */
    private function store_generation_record($prompt, $style, $width, $height, $image_url, $local_path) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_generated_images';
        
        $data = array(
            'prompt' => $prompt,
            'style' => $style,
            'width' => $width,
            'height' => $height,
            'image_url' => $image_url,
            'local_path' => $local_path,
            'user_id' => get_current_user_id(),
            'session_id' => nova_ai_get_session_id(),
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert(
            $table,
            $data,
            array('%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s')
        );
        
        if ($wpdb->last_error) {
            nova_ai_log('Failed to store generation record: ' . $wpdb->last_error, 'error');
        }
    }
    
    /**
     * Get user's generated images
     */
    public function get_user_images($user_id = null, $limit = 20) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $table = $wpdb->prefix . 'nova_ai_generated_images';
        
        $images = $wpdb->get_results($wpdb->prepare(
            "SELECT id, prompt, style, width, height, image_url, created_at 
             FROM $table 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id,
            $limit
        ), ARRAY_A);
        
        return $images ? $images : array();
    }
    
    /**
     * Get generation statistics
     */
    public function get_generation_stats($days = 30) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_generated_images';
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Total generations
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE created_at >= %s",
            $start_date
        ));
        
        // By style
        $by_style = $wpdb->get_results($wpdb->prepare(
            "SELECT style, COUNT(*) as count 
             FROM $table 
             WHERE created_at >= %s 
             GROUP BY style 
             ORDER BY count DESC",
            $start_date
        ), ARRAY_A);
        
        // Daily stats
        $daily_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM $table 
             WHERE created_at >= %s 
             GROUP BY DATE(created_at) 
             ORDER BY date DESC",
            $start_date
        ), ARRAY_A);
        
        return array(
            'total_generations' => intval($total),
            'by_style' => $by_style,
            'daily_stats' => $daily_stats,
            'period_days' => $days
        );
    }
    
    /**
     * Clean up old images
     */
    public function cleanup_old_images($days = 30) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'nova_ai_generated_images';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Get old records with local paths
        $old_images = $wpdb->get_results($wpdb->prepare(
            "SELECT id, local_path FROM $table WHERE created_at < %s AND local_path IS NOT NULL",
            $cutoff_date
        ), ARRAY_A);
        
        $deleted_count = 0;
        
        foreach ($old_images as $image) {
            $filepath = $this->upload_dir . '/' . $image['local_path'];
            
            // Delete file if it exists
            if (file_exists($filepath)) {
                if (unlink($filepath)) {
                    $deleted_count++;
                }
            }
            
            // Delete database record
            $wpdb->delete(
                $table,
                array('id' => $image['id']),
                array('%d')
            );
        }
        
        nova_ai_log("Cleaned up {$deleted_count} old generated images", 'info');
        
        return $deleted_count;
    }
    
    /**
     * Test image API connection
     */
    public function test_api_connection() {
        try {
            $endpoint = rtrim($this->api_url, '/') . '/sdapi/v1/options';
            
            $response = wp_remote_get($endpoint, array(
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => $response->get_error_message()
                );
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            
            if ($response_code === 200) {
                return array(
                    'success' => true,
                    'message' => __('Image API connection successful', 'nova-ai-brainpool')
                );
            } else {
                return array(
                    'success' => false,
                    'message' => sprintf(__('API returned status: %d', 'nova-ai-brainpool'), $response_code)
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Get available models
     */
    public function get_available_models() {
        try {
            $endpoint = rtrim($this->api_url, '/') . '/sdapi/v1/sd-models';
            
            $response = wp_remote_get($endpoint, array(
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                return array();
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            if ($response_code === 200) {
                $models = json_decode($response_body, true);
                return is_array($models) ? $models : array();
            }
            
        } catch (Exception $e) {
            nova_ai_log('Failed to get available models: ' . $e->getMessage(), 'error');
        }
        
        return array();
    }
    
    /**
     * Generate image with custom parameters
     */
    public function generate_custom_image($params) {
        if (!$this->enabled) {
            throw new Exception(__('Image generation is disabled', 'nova-ai-brainpool'));
        }
        
        // Default parameters
        $defaults = array(
            'prompt' => '',
            'negative_prompt' => 'low quality, blurry',
            'width' => 512,
            'height' => 512,
            'steps' => 20,
            'cfg_scale' => 7,
            'sampler_name' => 'Euler a',
            'seed' => -1,
            'batch_size' => 1
        );
        
        $params = array_merge($defaults, $params);
        
        // Validate dimensions
        $params['width'] = $this->validate_dimension($params['width']);
        $params['height'] = $this->validate_dimension($params['height']);
        
        if (empty($params['prompt'])) {
            throw new Exception(__('Prompt cannot be empty', 'nova-ai-brainpool'));
        }
        
        // Call API
        $endpoint = rtrim($this->api_url, '/') . '/sdapi/v1/txt2img';
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($params),
            'timeout' => 120
        );
        
        $response = wp_remote_request($endpoint, $args);
        
        if (is_wp_error($response)) {
            throw new Exception(__('Image generation request failed', 'nova-ai-brainpool'));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            throw new Exception(sprintf(__('Image API returned error: %d', 'nova-ai-brainpool'), $response_code));
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (!isset($decoded_response['images']) || empty($decoded_response['images'])) {
            throw new Exception(__('No images generated', 'nova-ai-brainpool'));
        }
        
        // Save image locally
        $local_path = $this->save_image_locally($decoded_response['images'][0], $params['prompt']);
        $image_url = $this->get_image_url($local_path);
        
        // Store record
        $this->store_generation_record(
            $params['prompt'],
            'custom',
            $params['width'],
            $params['height'],
            $image_url,
            $local_path
        );
        
        return $image_url;
    }
}
