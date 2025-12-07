<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap whp-admin">
    <h1><?php esc_html_e('WebHarvest Pro Dashboard', 'webharvest-pro'); ?></h1>
    
    <div class="whp-stats-grid">
        <div class="whp-stat-card">
            <h3><?php esc_html_e('Total Sources', 'webharvest-pro'); ?></h3>
            <div class="whp-stat-number">
                <?php 
                $sources = get_option('whp_sources', array());
                echo count($sources);
                ?>
            </div>
        </div>
        
        <div class="whp-stat-card">
            <h3><?php esc_html_e('Active Sources', 'webharvest-pro'); ?></h3>
            <div class="whp-stat-number">
                <?php echo whp_get_active_sources_count(); ?>
            </div>
        </div>
        
        <div class="whp-stat-card">
            <h3><?php esc_html_e('Imported Posts', 'webharvest-pro'); ?></h3>
            <div class="whp-stat-number">
                <?php echo whp_get_total_imported_posts(); ?>
            </div>
        </div>
    </div>
    
    <div class="whp-quick-actions">
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
    
    <div class="whp-recent-activity">
        <h2><?php esc_html_e('Recent Activity', 'webharvest-pro'); ?></h2>
        <?php
        $logs = whp_get_recent_logs(10);
        
        if ($logs) {
            echo '<table class="widefat fixed">';
            echo '<thead><tr>
                <th>' . __('Type', 'webharvest-pro') . '</th>
                <th>' . __('Message', 'webharvest-pro') . '</th>
                <th>' . __('Status', 'webharvest-pro') . '</th>
                <th>' . __('Time', 'webharvest-pro') . '</th>
            </tr></thead>';
            echo '<tbody>';
            
            foreach ($logs as $log) {
                echo '<tr class="whp-log-' . esc_attr($log->status) . '">';
                echo '<td>' . esc_html($log->type) . '</td>';
                echo '<td>' . esc_html($log->message) . '</td>';
                echo '<td><span class="whp-status-badge whp-status-' . esc_attr($log->status) . '">' 
                     . esc_html($log->status) . '</span></td>';
                echo '<td>' . esc_html(human_time_diff(strtotime($log->created_at))) . ' ' . __('ago', 'webharvest-pro') . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__('No recent activity', 'webharvest-pro') . '</p>';
        }
        ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#whp-quick-scrape').on('click', function() {
        var url = prompt('<?php esc_html_e('Enter URL to scrape:', 'webharvest-pro'); ?>');
        if (url) {
            $.ajax({
                url: whp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'whp_test_scrape',
                    url: url,
                    nonce: whp_ajax.nonce
                },
                beforeSend: function() {
                    $('#whp-quick-scrape').prop('disabled', true).text(whp_ajax.strings.processing);
                },
                success: function(response) {
                    if (response.success) {
                        alert('<?php esc_html_e('Found', 'webharvest-pro'); ?> ' + response.data.count + ' <?php esc_html_e('potential posts', 'webharvest-pro'); ?>');
                    } else {
                        alert('<?php esc_html_e('Error:', 'webharvest-pro'); ?> ' + response.data);
                    }
                },
                complete: function() {
                    $('#whp-quick-scrape').prop('disabled', false).text('<?php esc_html_e('Quick Scrape', 'webharvest-pro'); ?>');
                }
            });
        }
    });
});
</script>