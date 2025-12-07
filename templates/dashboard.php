<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap webharvest-pro">
    <h1><?php esc_html_e('WebHarvest Pro Dashboard', 'webharvest-pro'); ?></h1>
    
    <div class="stats-grid">
        <div class="card">
            <h3><?php esc_html_e('Total Sources', 'webharvest-pro'); ?></h3>
            <div class="stat-number"><?php echo count(get_option('whp_sources', [])); ?></div>
        </div>
        
        <div class="card">
            <h3><?php esc_html_e('Imported Posts', 'webharvest-pro'); ?></h3>
            <div class="stat-number">
                <?php
                $count = wp_count_posts();
                echo $count->publish + $count->draft + $count->pending;
                ?>
            </div>
        </div>
        
        <div class="card">
            <h3><?php esc_html_e('Last Scrape', 'webharvest-pro'); ?></h3>
            <div class="stat-number">
                <?php
                $last_scrape = get_option('whp_last_scrape');
                echo $last_scrape ? human_time_diff(strtotime($last_scrape)) . ' ago' : 'Never';
                ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h2><?php esc_html_e('Quick Actions', 'webharvest-pro'); ?></h2>
        <p>
            <button id="whp-quick-scrape" class="button button-primary">
                <?php esc_html_e('Quick Scrape', 'webharvest-pro'); ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=webharvest-pro-sources'); ?>" class="button">
                <?php esc_html_e('Manage Sources', 'webharvest-pro'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=webharvest-pro-settings'); ?>" class="button">
                <?php esc_html_e('Settings', 'webharvest-pro'); ?>
            </a>
        </p>
    </div>
    
    <div class="card">
        <h2><?php esc_html_e('Recent Activity', 'webharvest-pro'); ?></h2>
        <div id="whp-recent-logs">
            <?php
            global $wpdb;
            $logs = $wpdb->get_results(
                "SELECT * FROM {$wpdb->prefix}whp_logs ORDER BY created_at DESC LIMIT 10"
            );
            
            if ($logs) {
                echo '<table class="widefat">';
                foreach ($logs as $log) {
                    echo '<tr class="log-item log-' . esc_attr($log->status) . '">';
                    echo '<td>' . esc_html($log->type) . '</td>';
                    echo '<td>' . esc_html($log->message) . '</td>';
                    echo '<td>' . esc_html(human_time_diff(strtotime($log->created_at))) . ' ago</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p>' . esc_html__('No recent activity', 'webharvest-pro') . '</p>';
            }
            ?>
        </div>
    </div>
    
    <div id="whp-test-results" style="display: none;"></div>
</div>