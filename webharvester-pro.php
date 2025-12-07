<?php
/**
 * Plugin Name: WebHarvest Pro
 * Plugin URI: https://tekstep.ug/webharvest-pro
 * Description: Advanced WordPress web scraper with AI rewriting, scheduled imports, content curation, and automated publishing.
 * Version: 2.0.0
 * Author: Wilkie Cephas
 * Author URI: https://tekstep.ug
 * License: GPL v2 or later
 * Text Domain: webharvest-pro
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WHP_VERSION', '2.0.0');
define('WHP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WHP_PLUGIN_FILE', __FILE__);
define('WHP_CACHE_TIME', HOUR_IN_SECONDS * 6);

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'WebHarvest_Pro\\';
    $base_dir = WHP_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Include required files
require_once WHP_PLUGIN_DIR . 'includes/class-activator.php';
require_once WHP_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once WHP_PLUGIN_DIR . 'includes/class-core.php';
require_once WHP_PLUGIN_DIR . 'includes/class-ajax-handler.php';
require_once WHP_PLUGIN_DIR . 'includes/class-scheduler.php';
require_once WHP_PLUGIN_DIR . 'includes/class-scraper-engine.php';
require_once WHP_PLUGIN_DIR . 'includes/class-ai-rewriter.php';
require_once WHP_PLUGIN_DIR . 'includes/class-image-handler.php';
require_once WHP_PLUGIN_DIR . 'includes/class-content-parser.php';
require_once WHP_PLUGIN_DIR . 'includes/class-post-creator.php';
require_once WHP_PLUGIN_DIR . 'includes/class-settings.php';
require_once WHP_PLUGIN_DIR . 'includes/class-dashboard.php';
require_once WHP_PLUGIN_DIR . 'includes/class-logs.php';

// Initialize the plugin
add_action('plugins_loaded', function () {
    if (class_exists('WebHarvest_Pro\\Core')) {
        WebHarvest_Pro\Core::get_instance();
    }
});

// Activation/Deactivation hooks
register_activation_hook(__FILE__, ['WebHarvest_Pro\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['WebHarvest_Pro\Deactivator', 'deactivate']);