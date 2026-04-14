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
                <tr>
                    <th scope="row"><label for="whp_openrouter_enabled"><?php esc_html_e('Enable OpenRouter/Qwen', 'webharvest-pro'); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" name="whp_openrouter_enabled" id="whp_openrouter_enabled" value="1" <?php checked(get_option('whp_openrouter_enabled'), '1'); ?> />
                            <?php esc_html_e('Use OpenRouter/Qwen for AI rewriting', 'webharvest-pro'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, you can enter an OpenRouter API key below to route requests to Qwen or other models supported by OpenRouter.', 'webharvest-pro'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whp_openrouter_api_key"><?php esc_html_e('OpenRouter API Key', 'webharvest-pro'); ?></label></th>
                    <td>
                        <input type="password" name="whp_openrouter_api_key" id="whp_openrouter_api_key" 
                               value="<?php echo esc_attr(get_option('whp_openrouter_api_key')); ?>" 
                               class="regular-text" />
                        <p class="description">
                            <?php esc_html_e('Enter your OpenRouter API key. Leave blank if not using OpenRouter/Qwen.', 'webharvest-pro'); ?>
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

        <div class="whp-settings-section">
            <h2><?php esc_html_e('Import Filters (Title / Content)', 'webharvest-pro'); ?></h2>
            <p class="description">
                <?php esc_html_e('Define title replacements and content removal/replacement rules as JSON arrays. Examples in descriptions below.', 'webharvest-pro'); ?>
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="whp_title_replacements"><?php esc_html_e('Title Replacements (JSON)', 'webharvest-pro'); ?></label></th>
                    <td>
                        <textarea name="whp_title_replacements" id="whp_title_replacements" rows="6" class="large-text code"><?php echo esc_textarea(json_encode(get_option('whp_title_replacements', array()), JSON_PRETTY_PRINT)); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Array of objects with "find" and "replace". Use plain text or regex (wrapped in /pattern/). Example:', 'webharvest-pro'); ?>
                            <code>[{"find":" - Source","replace":""},{"find":"/\s+\|\s+.*/","replace":""}]</code>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whp_content_rules"><?php esc_html_e('Content Rules (JSON)', 'webharvest-pro'); ?></label></th>
                    <td>
                        <textarea name="whp_content_rules" id="whp_content_rules" rows="6" class="large-text code"><?php echo esc_textarea(json_encode(get_option('whp_content_rules', array()), JSON_PRETTY_PRINT)); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Array of objects with "find" and "replace". To remove use empty string for replace. Example:', 'webharvest-pro'); ?>
                            <code>[{"find":"<div class=\"related-posts\">[\s\S]*?<\/div>","replace":""},{"find":"Read more","replace":""}]</code>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whp_content_removals"><?php esc_html_e('Legacy Removals (one per line)', 'webharvest-pro'); ?></label></th>
                    <td>
                        <textarea name="whp_content_removals" id="whp_content_removals" rows="4" class="large-text code"><?php echo esc_textarea(implode("\n", (array) get_option('whp_content_removals', array()))); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Older simple removals: enter plain strings or regex per line. Saved for backward compatibility.', 'webharvest-pro'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="whp-settings-section">
            <h2><?php esc_html_e('Sample & Create Template', 'webharvest-pro'); ?></h2>
            <p class="description"><?php esc_html_e('Fetch a single page to create a site template. You can then choose which parts (title, images, content) to map and save as a template for that site.', 'webharvest-pro'); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="whp_sample_url"><?php esc_html_e('Sample Page URL', 'webharvest-pro'); ?></label></th>
                    <td>
                        <input type="url" id="whp_sample_url" class="regular-text" placeholder="https://example.com/some-article" />
                        <button type="button" class="button" id="whp_fetch_sample"><?php esc_html_e('Fetch Sample', 'webharvest-pro'); ?></button>
                        <p class="description"><?php esc_html_e('Enter a single article URL from the target site to build a template.', 'webharvest-pro'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whp_title_xpath"><?php esc_html_e('Title XPath (optional)', 'webharvest-pro'); ?></label></th>
                    <td>
                        <input type="text" id="whp_title_xpath" class="regular-text" placeholder="//h1 | //meta[@property=\'og:title\']/@content" />
                        <p class="description"><?php esc_html_e('Optional XPath to extract title from the page. If left empty the scraper will use the &lt;title&gt; tag or heuristics.', 'webharvest-pro'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="whp_content_xpath"><?php esc_html_e('Content XPath (optional)', 'webharvest-pro'); ?></label></th>
                    <td>
                        <input type="text" id="whp_content_xpath" class="regular-text" placeholder="//article | //div[contains(@class,\'entry-content\')]" />
                        <p class="description"><?php esc_html_e('Optional XPath to extract main content. Using XPath gives precise control for templates.', 'webharvest-pro'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Per-Template AI Settings', 'webharvest-pro'); ?></th>
                    <td>
                        <label><input type="checkbox" id="whp_tpl_ai_enable" checked> <?php esc_html_e('Enable AI rewrite for this template', 'webharvest-pro'); ?></label>
                        <p class="description"><?php esc_html_e('When enabled the template will request AI rewriting for matched content. Settings below override global AI settings for this template.', 'webharvest-pro'); ?></p>
                        <p>
                            <label><?php esc_html_e('Tone:', 'webharvest-pro'); ?>
                                <select id="whp_tpl_ai_tone">
                                    <option value="professional">Professional</option>
                                    <option value="casual">Casual</option>
                                    <option value="seo">SEO</option>
                                    <option value="simple">Simple</option>
                                </select>
                            </label>
                        </p>
                        <p>
                            <label><?php esc_html_e('Model:', 'webharvest-pro'); ?>
                                <input type="text" id="whp_tpl_ai_model" class="regular-text" placeholder="gpt-3.5-turbo or qwen-v1" />
                            </label>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Sample Result', 'webharvest-pro'); ?></th>
                    <td>
                        <div id="whp_sample_result">
                            <p class="description"><?php esc_html_e('No sample loaded.', 'webharvest-pro'); ?></p>
                        </div>
                        <p class="description"><?php esc_html_e('After fetching, select the parts to include and click "Save Template" to store it for this site.', 'webharvest-pro'); ?></p>
                        <input type="hidden" id="whp_current_host" value="" />
                        <p>
                            <button type="button" class="button button-primary" id="whp_save_template"><?php esc_html_e('Save Template', 'webharvest-pro'); ?></button>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="whp-settings-section">
            <h2><?php esc_html_e('Saved Templates', 'webharvest-pro'); ?></h2>
            <div id="whp_saved_templates">
                <?php
                $templates = get_option('whp_site_templates', array());
                if (empty($templates)) {
                    echo '<p class="description">' . esc_html__('No templates saved yet.', 'webharvest-pro') . '</p>';
                } else {
                    echo '<table class="widefat"><thead><tr><th>Site</th><th>Sample URL</th><th>Actions</th></tr></thead><tbody>';
                    foreach ($templates as $host => $t) {
                        $sample = esc_url($t['sample_url'] ?? '');
                        echo '<tr><td>' . esc_html($host) . '</td><td><a href="' . $sample . '" target="_blank">' . esc_html($sample) . '</a></td><td><button class="button whp-edit-template" data-host="' . esc_attr($host) . '">Edit</button> <button class="button whp-delete-template" data-host="' . esc_attr($host) . '">Delete</button></td></tr>';
                    }
                    echo '</tbody></table>';
                }
                ?>
            </div>
            <p>
                <button type="button" class="button" id="whp_export_templates"><?php esc_html_e('Export Templates', 'webharvest-pro'); ?></button>
                <label class="button" style="display:inline-block;">
                    <?php esc_html_e('Import', 'webharvest-pro'); ?>
                    <input type="file" id="whp_import_file" name="template_file" style="display:none" />
                </label>
            </p>
        </div>

        <script>
        (function($){
            $(document).on('click', '.whp-delete-template', function(){
                if (!confirm('Delete template for this site?')) return;
                var host = $(this).data('host');
                $.post(ajaxurl, { action: 'whp_delete_template', host: host, nonce: '<?php echo wp_create_nonce('whp_sample_fetch_nonce'); ?>' }, function(resp){
                    if (resp && resp.success) {
                        location.reload();
                    } else {
                        alert('Error deleting template');
                    }
                }, 'json');
            });
            $(document).on('click', '.whp-edit-template', function(){
                var host = $(this).data('host');
                $.post(ajaxurl, { action: 'whp_get_template', host: host, nonce: '<?php echo wp_create_nonce('whp_sample_fetch_nonce'); ?>' }, function(resp){
                    if (!resp || !resp.success) { alert('Error loading template'); return; }
                    var t = resp.data;
                    // Prefill UI
                    $('#whp_sample_url').val(t.sample_url || '');
                    $('#whp_title_xpath').val(t.title_xpath || '');
                    $('#whp_content_xpath').val(t.content_xpath || '');
                    $('.whp_select_part[data-part="title"]').prop('checked', !!t.include_title);
                    $('.whp_select_part[data-part="content"]').prop('checked', !!t.include_content);
                    $('#whp_tpl_ai_enable').prop('checked', !!t.ai_enable);
                    $('#whp_tpl_ai_tone').val(t.ai_tone || 'professional');
                    $('#whp_tpl_ai_model').val(t.ai_model || '');
                    $('#whp_current_host').val(host);
                    // Scroll to sample section
                    $('html,body').animate({scrollTop: $('#whp_sample_url').offset().top - 40}, 300);
                }, 'json');
            });

            $('#whp_export_templates').on('click', function(){
                window.location = ajaxurl + '?action=whp_export_templates';
            });

            $('#whp_import_file').on('change', function(){
                var file = this.files[0];
                if (!file) return;
                var fd = new FormData();
                fd.append('action', 'whp_import_templates');
                fd.append('template_file', file);
                fd.append('nonce', '<?php echo wp_create_nonce('whp_sample_fetch_nonce'); ?>');
                $.ajax({
                    url: ajaxurl,
                    data: fd,
                    processData: false,
                    contentType: false,
                    type: 'POST',
                    success: function(resp){ if (resp && resp.success) location.reload(); else alert('Import failed'); }
                });
            });
        })(jQuery);
        </script>

        <script>
        (function($){
                $('#whp_fetch_sample').on('click', function(){
                var url = $('#whp_sample_url').val();
                if (!url) { alert('Enter a URL'); return; }
                $('#whp_sample_result').html('<p>Loading…</p>');
                $.post(ajaxurl, { action: 'whp_sample_fetch', url: url, nonce: '<?php echo wp_create_nonce('whp_sample_fetch_nonce'); ?>' }, function(resp){
                    if (!resp || !resp.success) {
                        $('#whp_sample_result').html('<p>Error fetching sample: '+(resp && resp.data?resp.data:'unknown')+'</p>');
                        return;
                    }
                    var data = resp.data;
                    var html = '<h3>Title</h3>' + '<label><input type="checkbox" class="whp_select_part" data-part="title" checked> Include title</label>' + '<div class="whp_sample_title">'+ $('<div>').text(data.title).html() +'</div>';
                    html += '<h3>Content</h3><label><input type="checkbox" class="whp_select_part" data-part="content" checked> Include content</label>' + '<div id="whp_sample_content_inner" class="whp_sample_content">'+ data.content +'</div>';
                    html += '<p class="description">' + 'Click any element in the sample content to select it as a XPath for title or content. Use the dropdown near the Save button to choose target.' + '</p>';
                    $('#whp_sample_result').html(html);

                    // Attach click handler to allow selecting element and capturing XPath
                    $('#whp_sample_content_inner').on('click', '*', function(e){
                        e.preventDefault();
                        e.stopPropagation();
                        var el = this;
                        // highlight
                        $('#whp_sample_content_inner').find('.whp-selected').removeClass('whp-selected');
                        $(el).addClass('whp-selected');
                        var xpath = computeXPath(el);
                        // Ask user whether this is title or content selector via prompt
                        var target = prompt('Enter target for this selector: "title" or "content"', 'content');
                        if (target === 'title') {
                            $('#whp_title_xpath').val(xpath);
                        } else {
                            $('#whp_content_xpath').val(xpath);
                        }
                    });
                }, 'json');
            });

            // Compute XPath for a DOM element
            function computeXPath(el) {
                if (el.id) {
                    return 'id("' + el.id + '")';
                }
                var parts = [];
                while (el && el.nodeType === Node.ELEMENT_NODE) {
                    var nb = 0;
                    var idx = 0;
                    var sib = el.previousSibling;
                    while (sib) {
                        if (sib.nodeType === Node.DOCUMENT_TYPE_NODE) { sib = sib.previousSibling; continue; }
                        if (sib.nodeName === el.nodeName) { nb++; }
                        sib = sib.previousSibling;
                    }
                    var name = el.nodeName.toLowerCase();
                    var part = name + (nb ? '[' + (nb+1) + ']' : '');
                    parts.unshift(part);
                    el = el.parentNode;
                }
                return '//' + parts.join('/');
            }

            // Add simple style for selected element
            $('<style>').prop('type', 'text/css').html('.whp-selected{outline:2px solid #00a0d2!important;}').appendTo('head');

            $('#whp_save_template').on('click', function(){
                var url = $('#whp_sample_url').val();
                if (!url) { alert('Enter a sample URL first'); return; }
                // Collect selections
                var includeTitle = $('.whp_select_part[data-part="title"]').prop('checked') ? 1 : 0;
                var includeContent = $('.whp_select_part[data-part="content"]').prop('checked') ? 1 : 0;
                // For now we store a simple template object in an option via AJAX
                var ai_enable = $('#whp_tpl_ai_enable').prop('checked') ? 1 : 0;
                var ai_tone = $('#whp_tpl_ai_tone').val();
                var ai_model = $('#whp_tpl_ai_model').val();

                var currentHost = $('#whp_current_host').val();
                var action = currentHost ? 'whp_update_template' : 'whp_save_template_simple';
                var data = { action: action, sample_url: url, include_title: includeTitle, include_content: includeContent, title_xpath: $('#whp_title_xpath').val(), content_xpath: $('#whp_content_xpath').val(), ai_enable: ai_enable, ai_tone: ai_tone, ai_model: ai_model, nonce: '<?php echo wp_create_nonce('whp_sample_fetch_nonce'); ?>' };
                if (currentHost) data.host = currentHost;

                $.post(ajaxurl, data, function(resp){
                    if (!resp || !resp.success) {
                        alert('Error saving template: '+(resp && resp.data?resp.data:'unknown'));
                        return;
                    }
                    alert('Template saved');
                        if (currentHost) location.reload();
                        else $('#whp_current_host').val('');
                }, 'json');
            });
        })(jQuery);
        </script>
        
        <?php submit_button(); ?>
    </form>
</div>