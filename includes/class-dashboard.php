<?php
namespace WebHarvest_Pro;

class Dashboard {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_init', [$this, 'handle_actions']);
    }
    
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            __('WebHarvest Pro', 'webharvest-pro'),
            __('WebHarvest Pro', 'webharvest-pro'),
            'manage_options',
            'webharvest-pro',
            [$this, 'render_dashboard'],
            'dashicons-cloud-download',
            30
        );
        
        // Submenus
        add_submenu_page(
            'webharvest-pro',
            __('Sources', 'webharvest-pro'),
            __('Sources', 'webharvest-pro'),
            'manage_options',
            'webharvest-pro-sources',
            [$this, 'render_sources_page']
        );
        
        add_submenu_page(
            'webharvest-pro',
            __('Logs', 'webharvest-pro'),
            __('Logs', 'webharvest-pro'),
            'manage_options',
            'webharvest-pro-logs',
            [$this, 'render_logs_page']
        );
        
        add_submenu_page(
            'webharvest-pro',
            __('Settings', 'webharvest-pro'),
            __('Settings', 'webharvest-pro'),
            'manage_options',
            'webharvest-pro-settings',
            [$this, 'render_settings_page']
        );
    }
    
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
    
    public function handle_actions() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'webharvest-pro') === false) {
            return;
        }
        
        if (isset($_GET['action']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'whp_action')) {
                $this->process_action($_GET['action'], $_GET);
            }
        }
    }
    
    private function process_action($action, $data) {
        switch ($action) {
            case 'delete_source':
                $this->delete_source($data['source_id']);
                break;
            case 'toggle_source':
                $this->toggle_source($data['source_id']);
                break;
            case 'test_scrape':
                $this->test_scrape($data['source_id']);
                break;
        }
    }
}