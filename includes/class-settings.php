<?php
namespace WebHarvest_Pro;

class Settings {
    
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_settings_page_hooks']);
        add_action('wp_ajax_whp_sample_fetch', [$this, 'ajax_sample_fetch']);
        add_action('wp_ajax_whp_delete_template', [$this, 'ajax_delete_template']);
        add_action('wp_ajax_whp_get_template', [$this, 'ajax_get_template']);
        add_action('wp_ajax_whp_export_templates', [$this, 'ajax_export_templates']);
        add_action('wp_ajax_whp_import_templates', [$this, 'ajax_import_templates']);
        add_action('wp_ajax_whp_update_template', [$this, 'ajax_update_template']);
    }

    public function add_settings_page_hooks() {
        // Only need AJAX endpoint; settings page already exists elsewhere
        // Enqueue scripts if needed via admin_enqueue_scripts elsewhere
    }
    
    public function register_settings() {
        // AI Settings
        register_setting('whp_ai_settings', 'whp_ai_provider');
        register_setting('whp_ai_settings', 'whp_openai_api_key');
        register_setting('whp_ai_settings', 'whp_anthropic_api_key');
        register_setting('whp_ai_settings', 'whp_google_api_key');
        register_setting('whp_ai_settings', 'whp_ai_tone');
        register_setting('whp_ai_settings', 'whp_ai_style');
        
        // General Settings
        register_setting('whp_general_settings', 'whp_default_author');
        register_setting('whp_general_settings', 'whp_default_category');
        register_setting('whp_general_settings', 'whp_default_status');
        register_setting('whp_general_settings', 'whp_image_handling');
        register_setting('whp_general_settings', 'whp_enable_logging');
        register_setting('whp_general_settings', 'whp_log_retention_days');
        
        // Advanced Settings
        register_setting('whp_advanced_settings', 'whp_request_delay');
        register_setting('whp_advanced_settings', 'whp_user_agent');
        register_setting('whp_advanced_settings', 'whp_verify_ssl');
        register_setting('whp_advanced_settings', 'whp_timeout');

        // Import Filters (title/content rules)
        register_setting('whp_general_settings', 'whp_title_replacements', array($this, 'sanitize_title_replacements'));
        register_setting('whp_general_settings', 'whp_content_rules', array($this, 'sanitize_content_rules'));
        register_setting('whp_general_settings', 'whp_content_removals', array($this, 'sanitize_content_removals'));
        
        // OpenRouter / Qwen AI settings
        register_setting('whp_ai_settings', 'whp_openrouter_enabled');
        register_setting('whp_ai_settings', 'whp_openrouter_api_key', array($this, 'sanitize_openrouter_key'));
    }

    public function sanitize_title_replacements($input) {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input = $decoded;
            } else {
                // Try to preserve previous value
                return get_option('whp_title_replacements', array());
            }
        }

        if (!is_array($input)) {
            return array();
        }

        $out = array();
        foreach ($input as $item) {
            if (!is_array($item)) continue;
            $find = isset($item['find']) ? wp_kses_post($item['find']) : '';
            $replace = isset($item['replace']) ? wp_kses_post($item['replace']) : '';
            if ($find === '') continue;
            $out[] = array('find' => $find, 'replace' => $replace);
        }

        return $out;
    }

    public function sanitize_content_rules($input) {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $input = $decoded;
            } else {
                return get_option('whp_content_rules', array());
            }
        }

        if (!is_array($input)) {
            return array();
        }

        $out = array();
        foreach ($input as $item) {
            if (!is_array($item)) continue;
            $find = isset($item['find']) ? $item['find'] : '';
            $replace = isset($item['replace']) ? $item['replace'] : '';
            if ($find === '') continue;
            $out[] = array('find' => $find, 'replace' => $replace);
        }

        return $out;
    }

    public function sanitize_content_removals($input) {
        // Accept textarea (string with lines) or array
        if (is_string($input)) {
            $lines = preg_split('/\r?\n/', $input);
            $arr = array();
            foreach ($lines as $l) {
                $l = trim($l);
                if ($l === '') continue;
                $arr[] = $l;
            }
            return $arr;
        }

        if (is_array($input)) {
            $arr = array();
            foreach ($input as $l) {
                $l = trim($l);
                if ($l === '') continue;
                $arr[] = $l;
            }
            return $arr;
        }

        return array();
    }

    public function sanitize_openrouter_key($input) {
        if (is_string($input)) {
            $input = trim($input);
            // Basic validation: non-empty and reasonable length
            if ($input === '' || strlen($input) > 1024) {
                return '';
            }
            return sanitize_text_field($input);
        }

        return '';
    }

    public function ajax_sample_fetch() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('forbidden', 403);
        }

        check_ajax_referer('whp_sample_fetch_nonce', 'nonce');

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (empty($url)) {
            wp_send_json_error('missing_url', 400);
        }

        $resp = wp_remote_get($url, array('timeout' => 20, 'sslverify' => false));

        if (is_wp_error($resp)) {
            wp_send_json_error($resp->get_error_message(), 500);
        }

        $code = wp_remote_retrieve_response_code($resp);
        if ($code !== 200) {
            wp_send_json_error("HTTP {$code}", 500);
        }

        $body = wp_remote_retrieve_body($resp);

        // Return a trimmed/sanitized version for client-side selection
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        // Extract title, main article HTML and images
        $title = '';
        $titleEl = $dom->getElementsByTagName('title')->item(0);
        if ($titleEl) $title = $titleEl->textContent;

        $content = '';
        $nodes = $xpath->query('//article|//main|//*[contains(@class, "entry-content")]|//*[contains(@class, "post-content")]');
        if ($nodes && $nodes->length > 0) {
            $node = $nodes->item(0);
            $html = '';
            foreach ($node->childNodes as $child) {
                $html .= $node->ownerDocument->saveHTML($child);
            }
            $content = $html;
        } else {
            // fallback to body
            $bodyEl = $dom->getElementsByTagName('body')->item(0);
            if ($bodyEl) {
                $html = '';
                foreach ($bodyEl->childNodes as $child) {
                    $html .= $bodyEl->ownerDocument->saveHTML($child);
                }
                $content = $html;
            }
        }

        // Return as sanitized HTML (strip script tags)
        $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);

        wp_send_json_success(array(
            'title' => wp_kses_post($title),
            'content' => $content,
            'url' => esc_url($url)
        ));
    }

    // Simple template save handler for the sample UI
    public function ajax_save_template_simple() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('forbidden', 403);
        }

        check_ajax_referer('whp_sample_fetch_nonce', 'nonce');

        $sample_url = isset($_POST['sample_url']) ? esc_url_raw($_POST['sample_url']) : '';
        $include_title = !empty($_POST['include_title']) ? 1 : 0;
        $include_content = !empty($_POST['include_content']) ? 1 : 0;

        if (empty($sample_url)) {
            wp_send_json_error('missing_url', 400);
        }

        // Use host as template key
        $host = wp_parse_url($sample_url, PHP_URL_HOST);
        if (!$host) {
            wp_send_json_error('invalid_url', 400);
        }

        $title_xpath = isset($_POST['title_xpath']) ? sanitize_text_field($_POST['title_xpath']) : '';
        $content_xpath = isset($_POST['content_xpath']) ? sanitize_text_field($_POST['content_xpath']) : '';

        $ai_enable = !empty($_POST['ai_enable']) ? 1 : 0;
        $ai_tone = isset($_POST['ai_tone']) ? sanitize_text_field($_POST['ai_tone']) : '';
        $ai_model = isset($_POST['ai_model']) ? sanitize_text_field($_POST['ai_model']) : '';

        // Validate XPaths by attempting to fetch the sample URL and query
        if (!empty($title_xpath) || !empty($content_xpath)) {
            $resp = wp_remote_get($sample_url, array('timeout' => 20, 'sslverify' => false));
            if (is_wp_error($resp)) {
                wp_send_json_error('unable_fetch_sample', 500);
            }
            $body = wp_remote_retrieve_body($resp);
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8'));
            $xpathObj = new \DOMXPath($dom);

            if (!empty($title_xpath)) {
                $nodes = @$xpathObj->query($title_xpath);
                if ($nodes === false) {
                    wp_send_json_error('invalid_title_xpath', 400);
                }
            }
            if (!empty($content_xpath)) {
                $nodes = @$xpathObj->query($content_xpath);
                if ($nodes === false) {
                    wp_send_json_error('invalid_content_xpath', 400);
                }
            }
        }

        $templates = get_option('whp_site_templates', array());

        $templates[$host] = array(
            'sample_url' => $sample_url,
            'include_title' => $include_title,
            'include_content' => $include_content,
            'title_xpath' => $title_xpath,
            'content_xpath' => $content_xpath,
            'ai_enable' => $ai_enable,
            'ai_tone' => $ai_tone,
            'ai_model' => $ai_model,
            'created' => current_time('mysql')
        );

        update_option('whp_site_templates', $templates);

        wp_send_json_success(true);
    }

    public function ajax_delete_template() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('forbidden', 403);
        }

        check_ajax_referer('whp_sample_fetch_nonce', 'nonce');

        $host = isset($_POST['host']) ? sanitize_text_field($_POST['host']) : '';
        if (empty($host)) {
            wp_send_json_error('missing_host', 400);
        }

        $templates = get_option('whp_site_templates', array());
        if (isset($templates[$host])) {
            unset($templates[$host]);
            update_option('whp_site_templates', $templates);
            wp_send_json_success(true);
        }

        wp_send_json_error('not_found', 404);
    }

    public function ajax_get_template() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('forbidden', 403);
        }
        check_ajax_referer('whp_sample_fetch_nonce', 'nonce');
        $host = isset($_POST['host']) ? sanitize_text_field($_POST['host']) : '';
        if (empty($host)) {
            wp_send_json_error('missing_host', 400);
        }
        $templates = get_option('whp_site_templates', array());
        if (empty($templates[$host])) {
            wp_send_json_error('not_found', 404);
        }
        wp_send_json_success($templates[$host]);
    }

    public function ajax_update_template() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('forbidden', 403);
        }
        check_ajax_referer('whp_sample_fetch_nonce', 'nonce');

        $host = isset($_POST['host']) ? sanitize_text_field($_POST['host']) : '';
        if (empty($host)) {
            wp_send_json_error('missing_host', 400);
        }

        $templates = get_option('whp_site_templates', array());
        if (empty($templates[$host])) {
            wp_send_json_error('not_found', 404);
        }

        $t = $templates[$host];
        // Update fields from request (only known fields)
        if (isset($_POST['sample_url'])) $t['sample_url'] = esc_url_raw($_POST['sample_url']);
        $t['include_title'] = !empty($_POST['include_title']) ? 1 : 0;
        $t['include_content'] = !empty($_POST['include_content']) ? 1 : 0;
        if (isset($_POST['title_xpath'])) $t['title_xpath'] = sanitize_text_field($_POST['title_xpath']);
        if (isset($_POST['content_xpath'])) $t['content_xpath'] = sanitize_text_field($_POST['content_xpath']);
        $t['ai_enable'] = !empty($_POST['ai_enable']) ? 1 : 0;
        if (isset($_POST['ai_tone'])) $t['ai_tone'] = sanitize_text_field($_POST['ai_tone']);
        if (isset($_POST['ai_model'])) $t['ai_model'] = sanitize_text_field($_POST['ai_model']);

        // Optional: validate XPaths against sample_url if provided
        if (!empty($t['sample_url']) && (!empty($t['title_xpath']) || !empty($t['content_xpath']))) {
            $resp = wp_remote_get($t['sample_url'], array('timeout' => 20, 'sslverify' => false));
            if (!is_wp_error($resp)) {
                $body = wp_remote_retrieve_body($resp);
                libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                @$dom->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8'));
                $xpathObj = new \DOMXPath($dom);
                if (!empty($t['title_xpath'])) {
                    $nodes = @$xpathObj->query($t['title_xpath']);
                    if ($nodes === false) wp_send_json_error('invalid_title_xpath', 400);
                }
                if (!empty($t['content_xpath'])) {
                    $nodes = @$xpathObj->query($t['content_xpath']);
                    if ($nodes === false) wp_send_json_error('invalid_content_xpath', 400);
                }
            }
        }

        $templates[$host] = $t;
        update_option('whp_site_templates', $templates);
        wp_send_json_success(true);
    }

    public function ajax_export_templates() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('forbidden', 403);
        }
        $templates = get_option('whp_site_templates', array());
        $payload = json_encode($templates, JSON_PRETTY_PRINT);
        header('Content-Type: application/json');
        echo $payload;
        wp_die();
    }

    public function ajax_import_templates() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('forbidden', 403);
        }
        check_ajax_referer('whp_sample_fetch_nonce', 'nonce');
        if (empty($_FILES['template_file']) || !is_uploaded_file($_FILES['template_file']['tmp_name'])) {
            wp_send_json_error('no_file', 400);
        }
        $contents = file_get_contents($_FILES['template_file']['tmp_name']);
        $data = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            wp_send_json_error('invalid_json', 400);
        }
        // Merge with existing templates
        $templates = get_option('whp_site_templates', array());
        $templates = array_merge($templates, $data);
        update_option('whp_site_templates', $templates);
        wp_send_json_success(true);
    }
    
    public static function get_default_settings() {
        return [
            'general' => [
                'default_author' => get_current_user_id(),
                'default_category' => 1,
                'default_status' => 'draft',
                'image_handling' => 'download',
                'enable_logging' => true,
                'log_retention_days' => 30,
            ],
            'ai' => [
                'provider' => 'openai',
                'tone' => 'professional',
                'style' => 'blog_post',
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ],
            'advanced' => [
                'request_delay' => 1,
                'user_agent' => 'Mozilla/5.0 (compatible; WebHarvestPro/2.0)',
                'verify_ssl' => false,
                'timeout' => 30,
            ]
        ];
    }
}