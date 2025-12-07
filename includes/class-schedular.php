<?php
if (!defined('ABSPATH')) exit;

class WHP_Scheduler {
    
    public function __construct() {
        add_action('init', array($this, 'schedule_events'));
    }
    
    public function schedule_events() {
        if (!wp_next_scheduled('whp_hourly_scrape')) {
            wp_schedule_event(time(), 'hourly', 'whp_hourly_scrape');
        }
        
        if (!wp_next_scheduled('whp_daily_scrape')) {
            wp_schedule_event(time(), 'daily', 'whp_daily_scrape');
        }
        
        if (!wp_next_scheduled('whp_weekly_scrape')) {
            wp_schedule_event(time(), 'weekly', 'whp_weekly_scrape');
        }
    }
    
    public function get_scheduled_scrapes() {
        $sources = get_option('whp_sources', array());
        $scheduled = array();
        
        foreach ($sources as $source_id => $source) {
            if ($source['status'] === 'active' && $source['frequency'] !== 'manual') {
                $scheduled[] = array(
                    'source_id' => $source_id,
                    'url' => $source['url'],
                    'frequency' => $source['frequency'],
                    'next_run' => $this->calculate_next_run($source)
                );
            }
        }
        
        return $scheduled;
    }
    
    private function calculate_next_run($source) {
        $last_scraped = $source['last_scraped'] ?? null;
        
        if (!$last_scraped) {
            return __('Now', 'webharvest-pro');
        }
        
        $last_time = strtotime($last_scraped);
        $next_time = $last_time;
        
        switch ($source['frequency']) {
            case 'hourly':
                $next_time += 3600;
                break;
            case 'twicedaily':
                $next_time += 43200;
                break;
            case 'daily':
                $next_time += 86400;
                break;
            case 'weekly':
                $next_time += 604800;
                break;
        }
        
        if ($next_time <= time()) {
            return __('Now', 'webharvest-pro');
        }
        
        return human_time_diff(time(), $next_time);
    }
}