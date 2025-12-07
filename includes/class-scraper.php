<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WHP_Scraper {
    
    private $image_handler;
    
    public function __construct() {
        $this->image_handler = new WHP_Image_Handler();
    }
    
    public function scrape_source($source) {
        $url = $source['url'];
        $source_id = $source['id'];
        
        // Log start
        $log_id = whp_log_action('scrape_start', "Started scraping: {$url}", 'info', $source_id);
        
        try {
            // Fetch the main page
            $html = $this->fetch_url($url);
            
            if (is_wp_error($html)) {
                throw new Exception($html->get_error_message());
            }
            
            // Extract post URLs
            $post_urls = $this->extract_post_urls($html, $url);
            
            if (empty($post_urls)) {
                throw new Exception('No post URLs found');
            }
            
            $posts_processed = 0;
            
            foreach ($post_urls as $post_url) {
                // Check if already imported
                if ($this->is_duplicate($post_url)) {
                    continue;
                }
                
                // Process single post
                $post_id = $this->process_single_post($post_url, $source);
                
                if ($post_id && !is_wp_error($post_id)) {
                    $posts_processed++;
                }
                
                // Delay between requests
                sleep(1);
            }
            
            // Update log
            $this->update_log($log_id, 'success', "Processed {$posts_processed} posts");
            
            return $posts_processed;
            
        } catch (Exception $e) {
            $this->update_log($log_id, 'error', $e->getMessage());
            return false;
        }
    }
    
    public function test_scrape($url) {
        $html = $this->fetch_url($url);
        
        if (is_wp_error($html)) {
            return $html;
        }
        
        $post_urls = $this->extract_post_urls($html, $url);
        
        return array(
            'posts' => array_slice($post_urls, 0, 10), // Limit to 10 for testing
            'info' => array(
                'total_found' => count($post_urls),
                'url' => $url
            )
        );
    }
    
    private function fetch_url($url, $args = array()) {
        $defaults = array(
            'timeout' => get_option('whp_request_timeout', 30),
            'sslverify' => false,
            'headers' => array(
                'User-Agent' => get_option('whp_user_agent', 'Mozilla/5.0 (compatible; WebHarvestPro/1.0)'),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            )
        );
        
        $args = wp_parse_args($args, $defaults);
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return new WP_Error('http_error', "HTTP Error: {$code}");
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    private function extract_post_urls($html, $base_url) {
        $urls = array();
        
        if (empty($html)) {
            return $urls;
        }
        
        // Use libxml
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        
        $xpath = new DOMXPath($dom);
        
        // Common patterns for post links
        $patterns = array(
            '//article//a[@href]',
            '//*[contains(@class, "post")]//a[@href]',
            '//*[contains(@class, "article")]//a[@href]',
            '//h2/a[@href]',
            '//h3/a[@href]',
            '//*[@class="entry-title"]/a[@href]',
        );
        
        foreach ($patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            
            if ($nodes === false) {
                continue;
            }
            
            foreach ($nodes as $node) {
                $href = $node->getAttribute('href');
                
                if (empty($href) || $href === '#') {
                    continue;
                }
                
                $full_url = whp_make_absolute_url($href, $base_url);
                
                if (whp_is_valid_post_url($full_url, $base_url)) {
                    $urls[] = $full_url;
                }
            }
        }
        
        // Remove duplicates
        $urls = array_unique($urls);
        
        return $urls;
    }
    
    private function is_duplicate($url) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_whp_source_url' AND meta_value = %s",
            $url
        ));
        
        return $count > 0;
    }
    
    private function process_single_post($url, $source) {
        $html = $this->fetch_url($url);
        
        if (is_wp_error($html)) {
            return $html;
        }
        
        // Parse content
        $post_data = $this->parse_post_content($html, $url);
        
        if (empty($post_data['title'])) {
            return new WP_Error('no_title', 'No title found');
        }
        
        // Handle images
        if (!empty($post_data['images'])) {
            $post_data['content'] = $this->image_handler->process_images_in_content(
                $post_data['content'],
                $url,
                get_option('whp_image_handling', 'download')
            );
        }
        
        // AI Rewriting
        if (!empty($source['rewrite_enabled'])) {
            $ai_handler = new WHP_AI_Handler();
            $rewritten = $ai_handler->rewrite_content($post_data['content']);
            
            if (!is_wp_error($rewritten)) {
                $post_data['content'] = $rewritten;
            }
        }
        
        // Create post
        $post_id = $this->create_post($post_data, $source);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Store original URL
            update_post_meta($post_id, '_whp_source_url', $url);
            update_post_meta($post_id, '_whp_source_id', $source['id']);
            update_post_meta($post_id, '_whp_scraped_date', current_time('mysql'));
            
            // Log success
            whp_log_action('post_created', "Created post: {$post_data['title']}", 'success', $source['id'], $post_id);
        }
        
        return $post_id;
    }
    
    private function parse_post_content($html, $url) {
        $data = array(
            'title' => '',
            'content' => '',
            'excerpt' => '',
            'images' => array()
        );
        
        if (empty($html)) {
            return $data;
        }
        
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        
        // Extract title
        $title = '';
        $title_el = $dom->getElementsByTagName('title')->item(0);
        if ($title_el) {
            $title = trim($title_el->textContent);
        }
        
        // Try Open Graph title
        if (empty($title)) {
            $xpath = new DOMXPath($dom);
            $og_title = $xpath->query('//meta[@property="og:title"]/@content');
            if ($og_title && $og_title->length > 0) {
                $title = $og_title->item(0)->nodeValue;
            }
        }
        
        // Extract main content
        $content = $this->extract_main_content($dom);
        
        // Extract excerpt
        $excerpt = $this->extract_excerpt($content);
        
        // Extract images
        $images = $this->extract_images($dom, $url);
        
        return array(
            'title' => sanitize_text_field($title),
            'content' => wp_kses_post($content),
            'excerpt' => sanitize_textarea_field($excerpt),
            'images' => $images
        );
    }
    
    private function extract_main_content($dom) {
        $xpath = new DOMXPath($dom);
        
        // Try common content containers
        $content_selectors = array(
            '//article',
            '//*[contains(@class, "post-content")]',
            '//*[contains(@class, "entry-content")]',
            '//*[contains(@class, "article-content")]',
            '//*[@id="content"]',
            '//main',
        );
        
        foreach ($content_selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $content = $this->dom_to_html($nodes->item(0));
                if (strlen(strip_tags($content)) > 100) {
                    return $content;
                }
            }
        }
        
        // Fallback: get body content
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            return $this->dom_to_html($body);
        }
        
        return '';
    }
    
    private function dom_to_html($node) {
        $html = '';
        
        if (!$node) {
            return $html;
        }
        
        $children = $node->childNodes;
        
        foreach ($children as $child) {
            $html .= $node->ownerDocument->saveHTML($child);
        }
        
        return $html;
    }
    
    private function extract_excerpt($content, $length = 200) {
        $clean_content = strip_tags($content);
        $clean_content = preg_replace('/\s+/', ' ', $clean_content);
        
        if (strlen($clean_content) <= $length) {
            return $clean_content;
        }
        
        $excerpt = substr($clean_content, 0, $length);
        $last_space = strrpos($excerpt, ' ');
        
        return substr($excerpt, 0, $last_space) . '...';
    }
    
    private function extract_images($dom, $base_url) {
        $images = array();
        $img_tags = $dom->getElementsByTagName('img');
        
        foreach ($img_tags as $img) {
            $src = $img->getAttribute('src');
            
            if (empty($src) || strpos($src, 'data:') === 0) {
                continue;
            }
            
            $full_src = whp_make_absolute_url($src, $base_url);
            
            $images[] = array(
                'src' => $full_src,
                'alt' => $img->getAttribute('alt'),
                'title' => $img->getAttribute('title')
            );
        }
        
        return $images;
    }
    
    private function create_post($post_data, $source) {
        $post_status = !empty($source['auto_publish']) ? 'publish' : 'draft';
        $author_id = !empty($source['author_id']) ? $source['author_id'] : get_current_user_id();
        
        $post_args = array(
            'post_title' => $post_data['title'],
            'post_content' => $post_data['content'],
            'post_excerpt' => $post_data['excerpt'],
            'post_status' => $post_status,
            'post_type' => 'post',
            'post_author' => $author_id,
        );
        
        $post_id = wp_insert_post($post_args);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Set category
            if (!empty($source['category'])) {
                wp_set_post_categories($post_id, array($source['category']));
            }
            
            // Set tags
            if (!empty($source['tags'])) {
                wp_set_post_tags($post_id, $source['tags']);
            }
            
            // Set featured image if available
            if (!empty($post_data['images'])) {
                $this->set_featured_image($post_id, $post_data['images'][0]['src']);
            }
        }
        
        return $post_id;
    }
    
    private function set_featured_image($post_id, $image_url) {
        $attachment_id = $this->image_handler->download_image($image_url);
        
        if ($attachment_id && !is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }
    
    private function update_log($log_id, $status, $message) {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'whp_logs',
            array(
                'status' => $status,
                'message' => $message
            ),
            array('id' => $log_id)
        );
    }
}