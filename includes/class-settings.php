<?php
namespace WebHarvest_Pro;

class Settings {
    
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    public function register_settings() {
        // AI Settings
        register_setting('whp_ai_settings', 'whp_ai_provider');
        register_setting('whp_ai_settings', 'whp_openai_api_key');
        register_setting('whp_ai_settings', 'whp_anthropic_api_key');
        register_setting('whp_ai_settings', 'whp_google_api_key');
        register_setting('whp_ai_settings', 'whp_ai_tone');
        register_setting('whp_ai_settings', 'whp_ai_style');
        
        // General Settings
        register_setting('whp_general_settings', 'whp_default_author');
        register_setting('whp_general_settings', 'whp_default_category');
        register_setting('whp_general_settings', 'whp_default_status');
        register_setting('whp_general_settings', 'whp_image_handling');
        register_setting('whp_general_settings', 'whp_enable_logging');
        register_setting('whp_general_settings', 'whp_log_retention_days');
        
        // Advanced Settings
        register_setting('whp_advanced_settings', 'whp_request_delay');
        register_setting('whp_advanced_settings', 'whp_user_agent');
        register_setting('whp_advanced_settings', 'whp_verify_ssl');
        register_setting('whp_advanced_settings', 'whp_timeout');
    }
    
    public static function get_default_settings() {
        return [
            'general' => [
                'default_author' => get_current_user_id(),
                'default_category' => 1,
                'default_status' => 'draft',
                'image_handling' => 'download',
                'enable_logging' => true,
                'log_retention_days' => 30,
            ],
            'ai' => [
                'provider' => 'openai',
                'tone' => 'professional',
                'style' => 'blog_post',
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ],
            'advanced' => [
                'request_delay' => 1,
                'user_agent' => 'Mozilla/5.0 (compatible; WebHarvestPro/2.0)',
                'verify_ssl' => false,
                'timeout' => 30,
            ]
        ];
    }
}