<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WHP_Image_Handler {
    
    public function process_images_in_content($content, $base_url, $handling = 'download') {
        if ($handling === 'none') {
            return $content;
        }
        
        if ($handling === 'download') {
            return $this->download_and_replace_images($content, $base_url);
        }
        
        // For 'reference' option, just make URLs absolute
        return $this->make_image_urls_absolute($content, $base_url);
    }
    
    private function download_and_replace_images($content, $base_url) {
        if (!preg_match_all('/<img[^>]+src="([^">]+)"/i', $content, $matches)) {
            return $content;
        }
        
        $urls = $matches[1];
        $replacements = array();
        
        foreach ($urls as $src) {
            if (strpos($src, 'data:') === 0) {
                continue;
            }
            
            $full_url = whp_make_absolute_url($src, $base_url);
            $attachment_id = $this->download_image($full_url);
            
            if ($attachment_id && !is_wp_error($attachment_id)) {
                $new_url = wp_get_attachment_url($attachment_id);
                $replacements[$src] = $new_url;
                
                // Update image class
                $content = preg_replace(
                    '/(<img[^>]+src=")' . preg_quote($src, '/') . '("[^>]*>)/i',
                    '$1' . $new_url . '$2 class="wp-image-' . $attachment_id . '"',
                    $content
                );
            }
        }
        
        return $content;
    }
    
    private function make_image_urls_absolute($content, $base_url) {
        if (!preg_match_all('/<img[^>]+src="([^">]+)"/i', $content, $matches)) {
            return $content;
        }
        
        $urls = $matches[1];
        
        foreach ($urls as $src) {
            if (strpos($src, 'data:') === 0 || filter_var($src, FILTER_VALIDATE_URL)) {
                continue;
            }
            
            $full_url = whp_make_absolute_url($src, $base_url);
            $content = str_replace($src, $full_url, $content);
        }
        
        return $content;
    }
    
    public function download_image($url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Check if image already exists
        $existing_id = $this->find_existing_attachment($url);
        if ($existing_id) {
            return $existing_id;
        }
        
        // Download image
        $tmp = download_url($url, 300);
        
        if (is_wp_error($tmp)) {
            return $tmp;
        }
        
        // Prepare file array
        $file_array = array(
            'name' => basename($url),
            'tmp_name' => $tmp
        );
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, 0);
        
        // Clean up temp file
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Store original URL in meta
        update_post_meta($attachment_id, '_whp_original_url', $url);
        
        return $attachment_id;
    }
    
    private function find_existing_attachment($url) {
        global $wpdb;
        
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_whp_original_url' AND meta_value = %s",
            $url
        ));
        
        return $attachment_id ? (int) $attachment_id : false;
    }
}