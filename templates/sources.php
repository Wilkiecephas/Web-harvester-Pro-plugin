<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap whp-admin">
    <h1><?php esc_html_e('Scraping Sources', 'webharvest-pro'); ?></h1>
    
    <div class="whp-add-source-form">
        <h2><?php esc_html_e('Add New Source', 'webharvest-pro'); ?></h2>
        <form id="whp-add-source-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="source_url"><?php esc_html_e('Website URL', 'webharvest-pro'); ?></label></th>
                    <td><input type="url" name="source_url" id="source_url" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="scrape_frequency"><?php esc_html_e('Scrape Frequency', 'webharvest-pro'); ?></label></th>
                    <td>
                        <select name="scrape_frequency" id="scrape_frequency">
                            <option value="manual"><?php esc_html_e('Manual Only', 'webharvest-pro'); ?></option>
                            <option value="hourly"><?php esc_html_e('Hourly', 'webharvest-pro'); ?></option>
                            <option value="twicedaily"><?php esc_html_e('Twice Daily', 'webharvest-pro'); ?></option>
                            <option value="daily"><?php esc_html_e('Daily', 'webharvest-pro'); ?></option>
                            <option value="weekly"><?php esc_html_e('Weekly', 'webharvest-pro'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="rewrite_with_ai"><?php esc_html_e('Rewrite with AI?', 'webharvest-pro'); ?></label></th>
                    <td><input type="checkbox" name="rewrite_with_ai" id="rewrite_with_ai" value="1" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="auto_publish"><?php esc_html_e('Auto Publish?', 'webharvest-pro'); ?></label></th>
                    <td><input type="checkbox" name="auto_publish" id="auto_publish" value="1" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="author_id"><?php esc_html_e('Author', 'webharvest-pro'); ?></label></th>
                    <td>
                        <?php
                        wp_dropdown_users(array(
                            'name' => 'author_id',
                            'id' => 'author_id',
                            'show_option_none' => __('Select Author', 'webharvest-pro'),
                            'selected' => get_current_user_id()
                        ));
                        ?>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e('Add Source', 'webharvest-pro'); ?></button>
            </p>
        </form>
    </div>
    
    <hr>
    
    <div class="whp-sources-list">
        <h2><?php esc_html_e('Existing Sources', 'webharvest-pro'); ?></h2>
        <?php
        $sources = get_option('whp_sources', array());
        
        if ($sources) {
            echo '<table class="widefat fixed whp-sources-table">';
            echo '<thead><tr>
                <th>' . __('URL', 'webharvest-pro') . '</th>
                <th>' . __('Frequency', 'webharvest-pro') . '</th>
                <th>' . __('Status', 'webharvest-pro') . '</th>
                <th>' . __('Rewrite', 'webharvest-pro') . '</th>
                <th>' . __('Auto Publish', 'webharvest-pro') . '</th>
                <th>' . __('Last Scraped', 'webharvest-pro') . '</th>
                <th>' . __('Actions', 'webharvest-pro') . '</th>
            </tr></thead>';
            echo '<tbody>';
            
            foreach ($sources as $source_id => $source) {
                echo '<tr>';
                echo '<td>' . esc_url($source['url']) . '</td>';
                echo '<td>' . esc_html($source['frequency']) . '</td>';
                echo '<td><span class="whp-source-status whp-source-' . esc_attr($source['status']) . '">' 
                     . esc_html($source['status']) . '</span></td>';
                echo '<td>' . ($source['rewrite_enabled'] ? __('Yes', 'webharvest-pro') : __('No', 'webharvest-pro')) . '</td>';
                echo '<td>' . ($source['auto_publish'] ? __('Yes', 'webharvest-pro') : __('No', 'webharvest-pro')) . '</td>';
                echo '<td>' . ($source['last_scraped'] ? esc_html(human_time_diff(strtotime($source['last_scraped']))) . ' ' . __('ago', 'webharvest-pro') : __('Never', 'webharvest-pro')) . '</td>';
                echo '<td class="whp-actions">
                    <button class="button button-small whp-run-scrape" data-source-id="' . esc_attr($source_id) . '">' . __('Scrape Now', 'webharvest-pro') . '</button>
                    <button class="button button-small whp-test-scrape" data-source-id="' . esc_attr($source_id) . '">' . __('Test', 'webharvest-pro') . '</button>
                    <button class="button button-small whp-toggle-source" data-source-id="' . esc_attr($source_id) . '" data-status="' . esc_attr($source['status']) . '">' 
                           . ($source['status'] === 'active' ? __('Deactivate', 'webharvest-pro') : __('Activate', 'webharvest-pro')) . '</button>
                    <button class="button button-small button-link-delete whp-delete-source" data-source-id="' . esc_attr($source_id) . '">' . __('Delete', 'webharvest-pro') . '</button>
                </td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__('No sources added yet.', 'webharvest-pro') . '</p>';
        }
        ?>
    </div>
</div>