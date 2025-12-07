<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap whp-admin">
    <h1><?php esc_html_e('WebHarvest Pro Settings', 'webharvest-pro'); ?></h1>
    
    <form method="post" action="options.php" class="whp-settings-form">
        <?php settings_fields('whp_settings'); ?>
        
        <div class="whp-settings-section">
            <h2><?php esc_html_e('AI Settings', 'webharvest-pro'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="whp_openai_key"><?php esc_html_e('OpenAI API Key', 'webharvest-pro'); ?></label></th>
                    <td>
                        <input type="password" name="whp_openai_key" id="whp_openai_key" 
                               value="<?php echo esc_attr(get_option('whp_openai_key')); ?>" 
                               class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('Enter your OpenAI API key for AI rewriting functionality.', 'webharvest-pro'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="whp-settings-section">
            <h2><?php esc_html_e('General Settings', 'webharvest-pro'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="whp_default_author"><?php esc_html_e('Default Author', 'webharvest-pro'); ?></label></th>
                    <td>
                        <?php
                        wp_dropdown_users(array(
                            'name' => 'whp_default_author',
                            'id' => 'whp_default_author',
                            'selected' => get_option('whp_default_author', get_current_user_id()),
                            'show_option_none' => __('Select Default Author', 'webharvest-pro'),
                        ));
                        ?>
                        <p class="description">
                            <?php esc_html_e('Default author for imported posts.', 'webharvest-pro'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whp_default_status"><?php esc_html_e('Default Post Status', 'webharvest-pro'); ?></label></th>
                    <td>
                        <select name="whp_default_status" id="whp_default_status">
                            <option value="draft" <?php selected(get_option('whp_default_status'), 'draft'); ?>>
                                <?php esc_html_e('Draft', 'webharvest-pro'); ?>
                            </option>
                            <option value="publish" <?php selected(get_option('whp_default_status'), 'publish'); ?>>
                                <?php esc_html_e('Publish', 'webharvest-pro'); ?>
                            </option>
                            <option value="pending" <?php selected(get_option('whp_default_status'), 'pending'); ?>>
                                <?php esc_html_e('Pending Review', 'webharvest-pro'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Default status for imported posts.', 'webharvest-pro'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whp_image_handling"><?php esc_html_e('Image Handling', 'webharvest-pro'); ?></label></th>
                    <td>
                        <select name="whp_image_handling" id="whp_image_handling">
                            <option value="download" <?php selected(get_option('whp_image_handling'), 'download'); ?>>
                                <?php esc_html_e('Download and import', 'webharvest-pro'); ?>
                            </option>
                            <option value="reference" <?php selected(get_option('whp_image_handling'), 'reference'); ?>>
                                <?php esc_html_e('Use original URLs', 'webharvest-pro'); ?>
                            </option>
                            <option value="none" <?php selected(get_option('whp_image_handling'), 'none'); ?>>
                                <?php esc_html_e('Remove images', 'webharvest-pro'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('How to handle images in scraped content.', 'webharvest-pro'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="whp-settings-section">
            <h2><?php esc_html_e('Advanced Settings', 'webharvest-pro'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="whp_user_agent"><?php esc_html_e('User Agent', 'webharvest-pro'); ?></label></th>
                    <td>
                        <input type="text" name="whp_user_agent" id="whp_user_agent" 
                               value="<?php echo esc_attr(get_option('whp_user_agent', 'Mozilla/5.0 (compatible; WebHarvestPro/1.0)')); ?>" 
                               class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('User agent string to use when scraping.', 'webharvest-pro'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whp_request_timeout"><?php esc_html_e('Request Timeout', 'webharvest-pro'); ?></label></th>
                    <td>
                        <input type="number" name="whp_request_timeout" id="whp_request_timeout" 
                               value="<?php echo esc_attr(get_option('whp_request_timeout', 30)); ?>" 
                               class="small-text" min="10" max="120" /> <?php esc_html_e('seconds', 'webharvest-pro'); ?>
                        <p class="description">
                            <?php esc_html_e('Timeout for HTTP requests.', 'webharvest-pro'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>