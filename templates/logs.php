<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap whp-admin">
    <h1><?php esc_html_e('WebHarvest Pro Logs', 'webharvest-pro'); ?></h1>
    
    <div class="whp-logs-container">
        <?php
        global $wpdb;
        $logs = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}whp_logs 
             ORDER BY created_at DESC 
             LIMIT 100"
        );
        
        if ($logs) {
            echo '<table class="widefat fixed">';
            echo '<thead><tr>
                <th>' . __('ID', 'webharvest-pro') . '</th>
                <th>' . __('Type', 'webharvest-pro') . '</th>
                <th>' . __('Message', 'webharvest-pro') . '</th>
                <th>' . __('Status', 'webharvest-pro') . '</th>
                <th>' . __('Source', 'webharvest-pro') . '</th>
                <th>' . __('Post', 'webharvest-pro') . '</th>
                <th>' . __('Time', 'webharvest-pro') . '</th>
            </tr></thead>';
            echo '<tbody>';
            
            foreach ($logs as $log) {
                echo '<tr class="whp-log-' . esc_attr($log->status) . '">';
                echo '<td>' . esc_html($log->id) . '</td>';
                echo '<td>' . esc_html($log->type) . '</td>';
                echo '<td>' . esc_html($log->message) . '</td>';
                echo '<td><span class="whp-status-badge whp-status-' . esc_attr($log->status) . '">' 
                     . esc_html($log->status) . '</span></td>';
                echo '<td>' . ($log->source_id ? esc_html($log->source_id) : '-') . '</td>';
                echo '<td>' . ($log->post_id ? '<a href="' . get_edit_post_link($log->post_id) . '">#' . $log->post_id . '</a>' : '-') . '</td>';
                echo '<td>' . esc_html($log->created_at) . '<br><small>' . esc_html(human_time_diff(strtotime($log->created_at))) . ' ' . __('ago', 'webharvest-pro') . '</small></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__('No logs found.', 'webharvest-pro') . '</p>';
        }
        ?>
    </div>
</div>