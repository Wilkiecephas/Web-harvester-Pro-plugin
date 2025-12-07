<?php
namespace WebHarvest_Pro;

class Core {
    
    private static $instance = null;
    private $scraper;
    private $scheduler;
    private $ai_rewriter;
    private $dashboard;
    private $settings;
    private $logs;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    private function init_hooks() {
        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Cron hooks
        add_filter('cron_schedules', [$this, 'add_custom_schedules']);
        add_action('whp_hourly_scrape', [$this, 'run_scheduled_scrapes']);
        add_action('whp_daily_scrape', [$this, 'run_scheduled_scrapes']);
        add_action('whp_weekly_scrape', [$this, 'run_scheduled_scrapes']);
    }
    
    private function load_dependencies() {
        $this->scraper = new Scraper_Engine();
        $this->scheduler = new Scheduler();
        $this->ai_rewriter = new AI_Rewriter();
        $this->settings = new Settings();
        $this->dashboard = new Dashboard();
        $this->logs = new Logs();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('webharvest-pro', false, dirname(plugin_basename(WHP_PLUGIN_FILE)) . '/languages');
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'webharvest-pro') === false) {
            return;
        }
        
        wp_enqueue_style('whp-admin', WHP_PLUGIN_URL . 'assets/css/admin.css', [], WHP_VERSION);
        wp_enqueue_script('whp-admin', WHP_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'wp-util'], WHP_VERSION, true);
        
        wp_localize_script('whp-admin', 'whp_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('whp_ajax_nonce'),
            'strings' => [
                'confirm_delete' => __('Are you sure you want to delete this source?', 'webharvest-pro'),
                'scraping_started' => __('Scraping started...', 'webharvest-pro'),
                'processing' => __('Processing...', 'webharvest-pro'),
            ]
        ]);
    }
    
    public function add_custom_schedules($schedules) {
        $schedules['whp_30min'] = [
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display' => __('Every 30 Minutes', 'webharvest-pro')
        ];
        $schedules['whp_weekly'] = [
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly', 'webharvest-pro')
        ];
        return $schedules;
    }
    
    public function run_scheduled_scrapes() {
        $sources = get_option('whp_sources', []);
        foreach ($sources as $source) {
            if ($source['status'] === 'active' && $source['frequency'] !== 'manual') {
                $this->scraper->scrape_source($source);
            }
        }
    }
    
    public function register_rest_routes() {
        register_rest_route('webharvest-pro/v1', '/test-scrape', [
            'methods' => 'POST',
            'callback' => [$this, 'test_scrape_endpoint'],
            'permission_callback' => function () {
                return current_user_can('manage_options');
            }
        ]);
    }
}