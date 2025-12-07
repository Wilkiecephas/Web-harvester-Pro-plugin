<?php
namespace WebHarvest_Pro;

class Scraper_Engine {
    
    private $content_parser;
    private $image_handler;
    private $post_creator;
    private $ai_rewriter;
    
    public function __construct() {
        $this->content_parser = new Content_Parser();
        $this->image_handler = new Image_Handler();
        $this->post_creator = new Post_Creator();
        $this->ai_rewriter = new AI_Rewriter();
    }
    
    public function scrape_source($source) {
        $log_id = Logs::add([
            'source_id' => $source['id'],
            'type' => 'scrape_start',
            'message' => sprintf(__('Started scraping: %s', 'webharvest-pro'), $source['url'])
        ]);
        
        try {
            $posts = $this->discover_posts($source['url'], $source);
            
            foreach ($posts as $post_url) {
                $this->process_single_post($post_url, $source);
            }
            
            Logs::update($log_id, [
                'status' => 'success',
                'message' => sprintf(__('Completed: Found %d posts', 'webharvest-pro'), count($posts))
            ]);
            
            return count($posts);
            
        } catch (\Exception $e) {
            Logs::update($log_id, [
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    private function discover_posts($url, $source) {
        $cache_key = 'whp_posts_' . md5($url);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $html = $this->fetch_html($url);
        $posts = $this->extract_post_links($html, $url, $source);
        
        // Filter by date if specified
        if (!empty($source['date_filter'])) {
            $posts = $this->filter_by_date($posts, $source['date_filter']);
        }
        
        // Limit posts
        if (!empty($source['limit'])) {
            $posts = array_slice($posts, 0, $source['limit']);
        }
        
        set_transient($cache_key, $posts, WHP_CACHE_TIME);
        
        return $posts;
    }
    
    private function fetch_html($url) {
        $args = [
            'timeout' => 30,
            'sslverify' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; WebHarvestPro/2.0; +https://tekstep.ug)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ]
        ];
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            throw new \Exception(sprintf(__('HTTP Error: %d', 'webharvest-pro'), $code));
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    private function extract_post_links($html, $base_url, $source) {
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);
        
        $links = [];
        $patterns = [
            '//article//a[@href]',
            '//*[contains(@class, "post")]//a[@href]',
            '//*[contains(@class, "article")]//a[@href]',
            '//h2/a[@href]',
            '//h3/a[@href]',
            '//a[contains(@class, "read-more")]',
        ];
        
        foreach ($patterns as $pattern) {
            $nodes = $xpath->query($pattern);
            foreach ($nodes as $node) {
                $href = $node->getAttribute('href');
                $full_url = $this->make_absolute_url($href, $base_url);
                
                if ($this->is_valid_post_url($full_url, $source)) {
                    $links[] = $full_url;
                }
            }
        }
        
        return array_unique($links);
    }
    
    private function make_absolute_url($url, $base) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        $parsed_base = parse_url($base);
        $scheme = $parsed_base['scheme'] ?? 'https';
        $host = $parsed_base['host'] ?? '';
        
        if (strpos($url, '//') === 0) {
            return $scheme . ':' . $url;
        }
        
        if (strpos($url, '/') === 0) {
            return $scheme . '://' . $host . $url;
        }
        
        $path = $parsed_base['path'] ?? '';
        $dir = dirname($path === '/' ? '' : $path);
        return $scheme . '://' . $host . $dir . '/' . ltrim($url, '/');
    }
    
    private function is_valid_post_url($url, $source) {
        // Check against excluded patterns
        $excluded_patterns = [
            '/wp-admin',
            '/wp-login',
            '/feed',
            '/search',
            '.pdf',
            '.jpg',
            '.png',
            '.gif',
            '.zip',
            '#',
        ];
        
        foreach ($excluded_patterns as $pattern) {
            if (strpos($url, $pattern) !== false) {
                return false;
            }
        }
        
        // Check if already imported
        if ($this->is_duplicate_post($url)) {
            return false;
        }
        
        // Check domain matches source
        $source_domain = parse_url($source['url'], PHP_URL_HOST);
        $url_domain = parse_url($url, PHP_URL_HOST);
        
        return $url_domain === $source_domain;
    }
    
    private function is_duplicate_post($url) {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_whp_source_url' AND meta_value = %s",
            $url
        ));
    }
    
    private function filter_by_date($posts, $date_filter) {
        $filtered = [];
        $cutoff = strtotime($date_filter);
        
        foreach ($posts as $post_url) {
            // Try to fetch and check date
            $html = $this->fetch_html($post_url);
            $date = $this->extract_post_date($html);
            
            if ($date && strtotime($date) >= $cutoff) {
                $filtered[] = $post_url;
            }
        }
        
        return $filtered;
    }
    
    private function extract_post_date($html) {
        preg_match_all('/<meta[^>]+property="article:published_time"[^>]+content="([^"]+)"/i', $html, $matches);
        if (!empty($matches[1][0])) {
            return $matches[1][0];
        }
        
        preg_match_all('/<time[^>]+datetime="([^"]+)"/i', $html, $matches);
        if (!empty($matches[1][0])) {
            return $matches[1][0];
        }
        
        return null;
    }
    
    private function process_single_post($post_url, $source) {
        $html = $this->fetch_html($post_url);
        
        $post_data = $this->content_parser->parse($html, $post_url);
        
        if (empty($post_data['title'])) {
            throw new \Exception(__('No title found', 'webharvest-pro'));
        }
        
        // Process images
        if (!empty($post_data['images'])) {
            $post_data['content'] = $this->image_handler->process_images(
                $post_data['content'],
                $post_url,
                $source['image_handling'] ?? 'download'
            );
        }
        
        // AI Rewriting
        if ($source['rewrite_enabled'] && class_exists('WebHarvest_Pro\AI_Rewriter')) {
            $post_data['content'] = $this->ai_rewriter->rewrite_content(
                $post_data['content'],
                $source['ai_settings'] ?? []
            );
        }
        
        // Create post
        $post_id = $this->post_creator->create($post_data, $source);
        
        if ($post_id) {
            update_post_meta($post_id, '_whp_source_url', $post_url);
            update_post_meta($post_id, '_whp_source_id', $source['id']);
            update_post_meta($post_id, '_whp_scraped_date', current_time('mysql'));
            
            Logs::add([
                'source_id' => $source['id'],
                'post_id' => $post_id,
                'type' => 'post_created',
                'message' => sprintf(__('Created post: %s', 'webharvest-pro'), $post_data['title']),
                'status' => 'success'
            ]);
        }
        
        return $post_id;
    }
}