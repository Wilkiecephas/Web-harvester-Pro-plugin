<?php
if (!defined('ABSPATH')) exit;

class WHP_AI_Handler {
    
    public function rewrite_content($content) {
        $api_key = get_option('whp_openai_key');
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'OpenAI API key not configured');
        }
        
        // Prepare content (limit to 4000 tokens)
        $content = strip_tags($content);
        $content = substr($content, 0, 12000); // Rough estimate
        
        $prompt = "Rewrite the following content in original words while preserving the meaning and key information:\n\n" . $content;
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'You are a professional content writer who rewrites articles in original words.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.7,
                'max_tokens' => 2000
            )),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('openai_error', $body['error']['message']);
        }
        
        if (empty($body['choices'][0]['message']['content'])) {
            return new WP_Error('no_content', 'No content returned from AI');
        }
        
        return trim($body['choices'][0]['message']['content']);
    }
    
    public function generate_title($content) {
        $api_key = get_option('whp_openai_key');
        
        if (empty($api_key)) {
            return '';
        }
        
        $prompt = "Generate a compelling title for this content:\n\n" . substr(strip_tags($content), 0, 1000);
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.7,
                'max_tokens' => 100
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return '';
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['choices'][0]['message']['content'])) {
            return trim($body['choices'][0]['message']['content']);
        }
        
        return '';
    }
}