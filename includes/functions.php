<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log an action to the database
 */
function whp_log_action($type, $message, $status = 'info', $source_id = null, $post_id = null) {
    global $wpdb;
    
    $wpdb->insert(
        $wpdb->prefix . 'whp_logs',
        array(
            'source_id' => $source_id,
            'post_id' => $post_id,
            'type' => $type,
            'message' => $message,
            'status' => $status,
            'created_at' => current_time('mysql')
        )
    );
    
    return $wpdb->insert_id;
}

/**
 * Get recent logs
 */
function whp_get_recent_logs($limit = 10) {
    global $wpdb;
    
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}whp_logs 
             ORDER BY created_at DESC 
             LIMIT %d",
            $limit
        )
    );
}

/**
 * Get total imported posts count
 */
function whp_get_total_imported_posts() {
    global $wpdb;
    
    return $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_whp_source_url'"
    ) ?: 0;
}

/**
 * Get active sources count
 */
function whp_get_active_sources_count() {
    $sources = get_option('whp_sources', array());
    $active = 0;
    
    foreach ($sources as $source) {
        if ($source['status'] === 'active') {
            $active++;
        }
    }
    
    return $active;
}

/**
 * Make a URL absolute
 */
function whp_make_absolute_url($url, $base) {
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

/**
 * Check if URL is a valid post URL
 */
function whp_is_valid_post_url($url, $base_url) {
    // Skip invalid URLs
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    
    // Skip common non-post URLs
    $exclude_patterns = array(
        '/wp-admin',
        '/wp-login',
        '/feed',
        '/search',
        '/tag/',
        '/category/',
        '/author/',
        '/page/',
        '.pdf',
        '.jpg',
        '.png',
        '.gif',
        '.zip',
        '.rar',
        '#'
    );
    
    foreach ($exclude_patterns as $pattern) {
        if (strpos($url, $pattern) !== false) {
            return false;
        }
    }
    
    // Ensure same domain
    $base_domain = parse_url($base_url, PHP_URL_HOST);
    $url_domain = parse_url($url, PHP_URL_HOST);
    
    return $base_domain === $url_domain;
}