<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WHP_AI_Handler {
    
    public function rewrite_content($content, $settings = array()) {
        // Prefer OpenRouter/Qwen if enabled. Allow per-call override from $settings.
        $openrouter_enabled = $settings['openrouter_enabled'] ?? get_option('whp_openrouter_enabled');
        $openrouter_key = $settings['openrouter_api_key'] ?? get_option('whp_openrouter_api_key');

        // Prepare content (limit)
        $plain = strip_tags($content);
        $plain = substr($plain, 0, 12000);
        $prompt = "Rewrite the following content in original words while preserving the meaning and key information:\n\n" . $plain;

        if (!empty($openrouter_enabled) && !empty($openrouter_key)) {
            // Call OpenRouter endpoint
            $body = json_encode(array(
                'model' => 'qwen-v1',
                'messages' => array(
                    array('role' => 'system', 'content' => 'You are a professional content writer.'),
                    array('role' => 'user', 'content' => $prompt)
                ),
                'temperature' => 0.7,
                'max_tokens' => 2000
            ));

                $response = wp_remote_post('https://openrouter.ai/v1/chat/completions', array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $openrouter_key
                ),
                'body' => $body,
                'timeout' => 60,
            ));

            if (!is_wp_error($response)) {
                $resp_body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($resp_body['choices'][0]['message']['content'])) {
                    return trim($resp_body['choices'][0]['message']['content']);
                }
            }
            // fall through to OpenAI if OpenRouter failed
        }

        // Fallback to OpenAI
        $api_key = get_option('whp_openai_key');
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'No AI API key configured');
        }

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(
                    array('role' => 'system', 'content' => 'You are a professional content writer.'),
                    array('role' => 'user', 'content' => $prompt)
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

        return $body['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Analyze content and return structured suggestions for edits/removals/changes.
     * Returns array of suggestions: [ { type: 'remove'|'change'|'replace', selector: '', reason: '', suggestion: '' }, ... ]
     */
    public function analyze_content($content, $settings = array()) {
        $openrouter_enabled = $settings['openrouter_enabled'] ?? get_option('whp_openrouter_enabled');
        $openrouter_key = $settings['openrouter_api_key'] ?? get_option('whp_openrouter_api_key');
        $model = $settings['model'] ?? ($settings['ai_model'] ?? 'gpt-3.5-turbo');

        $plain = strip_tags($content);
        $plain = substr($plain, 0, 12000);

        $prompt = "Analyze the following article content and return a JSON array of suggestions. Each suggestion should include: 'type' (remove|change|replace|highlight), 'path' (a short XPath or text excerpt where change is recommended), 'reason' (why), and 'suggestion' (what to do). Only output valid JSON. Content:\n\n" . $plain;

        // Try OpenRouter first if enabled
        if (!empty($openrouter_enabled) && !empty($openrouter_key')) {
            $body = json_encode([
                'model' => $settings['model'] ?? 'qwen-v1',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an assistant that analyzes web article HTML content and returns structured JSON suggestions.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.0,
                'max_tokens' => 800
            ]);

            $response = wp_remote_post('https://openrouter.ai/v1/chat/completions', [
                'headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $openrouter_key ],
                'body' => $body,
                'timeout' => 60
            ]);

            if (!is_wp_error($response)) {
                $resp = json_decode(wp_remote_retrieve_body($response), true);
                $text = $resp['choices'][0]['message']['content'] ?? '';
                $json = $this->extract_json_from_text($text);
                if ($json !== null) return $json;
            }
        }

        // Fallback to OpenAI if configured
        $api_key = get_option('whp_openai_key');
        if (empty($api_key)) {
            return array();
        }

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key ],
            'body' => json_encode([
                'model' => $model,
                'messages' => [ ['role' => 'system', 'content' => 'You are an assistant that analyzes web article HTML content and returns structured JSON suggestions.'], ['role' => 'user', 'content' => $prompt] ],
                'temperature' => 0.0,
                'max_tokens' => 800
            ]),
            'timeout' => 60
        ]);

        if (is_wp_error($response)) return array();
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $text = $body['choices'][0]['message']['content'] ?? '';
        $json = $this->extract_json_from_text($text);
        return $json ?? array();
    }

    private function extract_json_from_text($text) {
        // Try to find the first JSON array/object in the model output
        if (preg_match('/(\{.*\}|\[.*\])/s', $text, $m)) {
            $json = json_decode($m[1], true);
            if (json_last_error() === JSON_ERROR_NONE) return $json;
        }
        return null;
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