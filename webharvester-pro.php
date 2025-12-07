<?php
/**
 * Plugin Name: WebHarvest Pro
 * Plugin URI: https://tekstep.ug
 * Description: Advanced web scraping tool for WordPress with AI rewriting and scheduling.
 * Version: 1.0.0
 * Author: Wilkie Cephas
 * License: GPL v2 or later
 * Text Domain: webharvest-pro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('WHP_VERSION', '1.0.0');
define('WHP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WHP_PLUGIN_FILE', __FILE__);

// Include necessary files
require_once WHP_PLUGIN_DIR . 'includes/class-scraper.php';
require_once WHP_PLUGIN_DIR . 'includes/class-ai-handler.php';
require_once WHP_PLUGIN_DIR . 'includes/class-image-handler.php';
require_once WHP_PLUGIN_DIR . 'includes/class-scheduler.php';

/**
 * Main plugin class
 */
class WebHarvest_Pro_Main {
    
    private static $instance = null;
    private $scraper;
    private $ai_handler;
    private $scheduler;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }
    
    private function init_hooks() {
        // Activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_whp_add_source', array($this, 'ajax_add_source'));
        add_action('wp_ajax_whp_delete_source', array($this, 'ajax_delete_source'));
        add_action('wp_ajax_whp_run_scrape', array($this, 'ajax_run_scrape'));
        add_action('wp_ajax_whp_test_scrape', array($this, 'ajax_test_scrape'));
        
        // Cron hooks
        add_action('whp_hourly_scrape', array($this, 'run_scheduled_scrapes'));
        add_action('whp_daily_scrape', array($this, 'run_scheduled_scrapes'));
        add_action('whp_weekly_scrape', array($this, 'run_scheduled_scrapes'));
        
        // Add custom cron schedules
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
        
        // Settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    private function init_components() {
        $this->scraper = new WHP_Scraper();
        $this->ai_handler = new WHP_AI_Handler();
        $this->scheduler = new WHP_Scheduler();
    }
    
    public function activate() {
        // Create database table for logs
        global $wpdb;
        $table_name = $wpdb->prefix . 'whp_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_id varchar(100),
            post_id bigint(20),
            type varchar(50) NOT NULL,
            message text NOT NULL,
            status varchar(20) DEFAULT 'info',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_id (source_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Schedule initial cron
        wp_schedule_event(time(), 'hourly', 'whp_hourly_scrape');
        wp_schedule_event(time(), 'daily', 'whp_daily_scrape');
        wp_schedule_event(time(), 'weekly', 'whp_weekly_scrape');
    }
    
    public function deactivate() {
        // Clear cron jobs
        wp_clear_scheduled_hook('whp_hourly_scrape');
        wp_clear_scheduled_hook('whp_daily_scrape');
        wp_clear_scheduled_hook('whp_weekly_scrape');
    }
    
    public function add_cron_schedules($schedules) {
        $schedules['whp_30min'] = array(
            'interval' => 30 * 60,
            'display' => __('Every 30 Minutes', 'webharvest-pro')
        );
        $schedules['whp_2hours'] = array(
            'interval' => 2 * 60 * 60,
            'display' => __('Every 2 Hours', 'webharvest-pro')
        );
        $schedules['whp_6hours'] = array(
            'interval' => 6 * 60 * 60,
            'display' => __('Every 6 Hours', 'webharvest-pro')
        );
        return $schedules;
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('WebHarvest Pro', 'webharvest-pro'),
            __('WebHarvest Pro', 'webharvest-pro'),
            'manage_options',
            'webharvest-pro',
            array($this, 'render_dashboard'),
            'dashicons-cloud-download',
            30
        );
        
        add_submenu_page(
            'webharvest-pro',
            __('Sources', 'webharvest-pro'),
            __('Sources', 'webharvest-pro'),
            'manage_options',
            'webharvest-pro-sources',
            array($this, 'render_sources_page')
        );
        
        add_submenu_page(
            'webharvest-pro',
            __('Logs', 'webharvest-pro'),
            __('Logs', 'webharvest-pro'),
            'manage_options',
            'webharvest-pro-logs',
            array($this, 'render_logs_page')
        );
        
        add_submenu_page(
            'webharvest-pro',
            __('Settings', 'webharvest-pro'),
            __('Settings', 'webharvest-pro'),
            'manage_options',
            'webharvest-pro-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'webharvest-pro') === false) {
            return;
        }
        
        wp_enqueue_style('webharvest-pro-admin', WHP_PLUGIN_URL . 'assets/css/admin.css', array(), WHP_VERSION);
        wp_enqueue_script('webharvest-pro-admin', WHP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WHP_VERSION, true);
        
        wp_localize_script('webharvest-pro-admin', 'whp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('whp_ajax_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this source?', 'webharvest-pro'),
                'processing' => __('Processing...', 'webharvest-pro'),
                'scraping' => __('Scraping...', 'webharvest-pro'),
            )
        ));
    }
    
    public function register_settings() {
        register_setting('whp_settings', 'whp_openai_key');
        register_setting('whp_settings', 'whp_default_author');
        register_setting('whp_settings', 'whp_default_category');
        register_setting('whp_settings', 'whp_default_status');
        register_setting('whp_settings', 'whp_image_handling');
        register_setting('whp_settings', 'whp_user_agent');
        register_setting('whp_settings', 'whp_request_timeout');
    }
    
    // AJAX Handlers
    public function ajax_add_source() {
        check_ajax_referer('whp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $source_url = sanitize_url($_POST['source_url'] ?? '');
        $frequency = sanitize_text_field($_POST['scrape_frequency'] ?? 'manual');
        $rewrite_enabled = isset($_POST['rewrite_with_ai']) ? 1 : 0;
        $auto_publish = isset($_POST['auto_publish']) ? 1 : 0;
        $category = sanitize_text_field($_POST['category'] ?? '');
        $tags = sanitize_text_field($_POST['tags'] ?? '');
        $author_id = intval($_POST['author_id'] ?? 0);
        
        if (empty($source_url)) {
            wp_send_json_error(__('Please enter a valid URL', 'webharvest-pro'));
        }
        
        $sources = get_option('whp_sources', array());
        $source_id = uniqid('src_');
        
        $sources[$source_id] = array(
            'id' => $source_id,
            'url' => $source_url,
            'frequency' => $frequency,
            'rewrite_enabled' => $rewrite_enabled,
            'auto_publish' => $auto_publish,
            'category' => $category,
            'tags' => $tags,
            'author_id' => $author_id ?: get_current_user_id(),
            'status' => 'active',
            'created_at' => current_time('mysql'),
            'last_scraped' => null
        );
        
        update_option('whp_sources', $sources);
        
        // Log the action
        $this->log_action('source_added', "Added source: {$source_url}");
        
        wp_send_json_success(__('Source added successfully!', 'webharvest-pro'));
    }
    
    public function ajax_delete_source() {
        check_ajax_referer('whp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $source_id = sanitize_text_field($_POST['source_id'] ?? '');
        
        if (empty($source_id)) {
            wp_send_json_error(__('Invalid source ID', 'webharvest-pro'));
        }
        
        $sources = get_option('whp_sources', array());
        
        if (isset($sources[$source_id])) {
            $url = $sources[$source_id]['url'];
            unset($sources[$source_id]);
            update_option('whp_sources', $sources);
            
            $this->log_action('source_deleted', "Deleted source: {$url}");
            wp_send_json_success(__('Source deleted successfully!', 'webharvest-pro'));
        } else {
            wp_send_json_error(__('Source not found', 'webharvest-pro'));
        }
    }
    
    public function ajax_run_scrape() {
        check_ajax_referer('whp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $source_id = sanitize_text_field($_POST['source_id'] ?? '');
        $sources = get_option('whp_sources', array());
        
        if (!isset($sources[$source_id])) {
            wp_send_json_error(__('Source not found', 'webharvest-pro'));
        }
        
        $source = $sources[$source_id];
        
        // Run scrape in background
        wp_schedule_single_event(time() + 2, 'whp_run_single_scrape', array($source));
        
        $this->log_action('scrape_started', "Started scraping: {$source['url']}");
        
        wp_send_json_success(__('Scraping started in background. Check logs for progress.', 'webharvest-pro'));
    }
    
    public function ajax_test_scrape() {
        check_ajax_referer('whp_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $url = sanitize_url($_POST['url'] ?? '');
        
        if (empty($url)) {
            wp_send_json_error(__('Please enter a URL', 'webharvest-pro'));
        }
        
        $test_results = $this->scraper->test_scrape($url);
        
        if (is_wp_error($test_results)) {
            wp_send_json_error($test_results->get_error_message());
        }
        
        wp_send_json_success(array(
            'count' => count($test_results['posts']),
            'posts' => $test_results['posts'],
            'info' => $test_results['info']
        ));
    }
    
    public function run_scheduled_scrapes() {
        $sources = get_option('whp_sources', array());
        $current_time = current_time('mysql');
        
        foreach ($sources as $source_id => $source) {
            if ($source['status'] !== 'active' || $source['frequency'] === 'manual') {
                continue;
            }
            
            $last_scraped = $source['last_scraped'] ?? null;
            $should_scrape = false;
            
            if (!$last_scraped) {
                $should_scrape = true;
            } else {
                $last_scraped_time = strtotime($last_scraped);
                $current_time_stamp = strtotime($current_time);
                
                switch ($source['frequency']) {
                    case 'hourly':
                        $should_scrape = ($current_time_stamp - $last_scraped_time) >= 3600;
                        break;
                    case 'twicedaily':
                        $should_scrape = ($current_time_stamp - $last_scraped_time) >= 43200;
                        break;
                    case 'daily':
                        $should_scrape = ($current_time_stamp - $last_scraped_time) >= 86400;
                        break;
                    case 'weekly':
                        $should_scrape = ($current_time_stamp - $last_scraped_time) >= 604800;
                        break;
                }
            }
            
            if ($should_scrape) {
                $this->scraper->scrape_source($source);
                
                // Update last scraped time
                $sources[$source_id]['last_scraped'] = $current_time;
                update_option('whp_sources', $sources);
            }
        }
    }
    
    private function log_action($type, $message, $status = 'info', $source_id = null, $post_id = null) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'whp_logs',
            array(
                'source_id' => $source_id,
                'post_id' => $post_id,
                'type' => $type,
                'message' => $message,
                'status' => $status,
                'created_at' => current_time('mysql')
            )
        );
    }
    
    // Render Methods
    public function render_dashboard() {
        include WHP_PLUGIN_DIR . 'templates/dashboard.php';
    }
    
    public function render_sources_page() {
        include WHP_PLUGIN_DIR . 'templates/sources.php';
    }
    
    public function render_logs_page() {
        include WHP_PLUGIN_DIR . 'templates/logs.php';
    }
    
    public function render_settings_page() {
        include WHP_PLUGIN_DIR . 'templates/settings.php';
    }
}

// Initialize the plugin
function webharvest_pro_init() {
    return WebHarvest_Pro_Main::get_instance();
}
add_action('plugins_loaded', 'webharvest_pro_init');

// Add action for single scrape
add_action('whp_run_single_scrape', function($source) {
    $plugin = WebHarvest_Pro_Main::get_instance();
    // We'll need to make scraper public or create a method
});