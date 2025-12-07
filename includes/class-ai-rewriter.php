<?php
namespace WebHarvest_Pro;

class AI_Rewriter {
    
    private $providers = [
        'openai' => 'OpenAI',
        'anthropic' => 'Claude (Anthropic)',
        'google' => 'Google Gemini',
    ];
    
    public function rewrite_content($content, $settings) {
        $provider = $settings['provider'] ?? 'openai';
        $api_key = get_option("whp_{$provider}_api_key");
        
        if (empty($api_key)) {
            return $content;
        }
        
        $prompt = $this->build_prompt($content, $settings);
        
        switch ($provider) {
            case 'openai':
                return $this->call_openai($prompt, $api_key, $settings);
            case 'anthropic':
                return $this->call_anthropic($prompt, $api_key, $settings);
            case 'google':
                return $this->call_google($prompt, $api_key, $settings);
            default:
                return $content;
        }
    }
    
    private function build_prompt($content, $settings) {
        $tone = $settings['tone'] ?? 'professional';
        $style = $settings['style'] ?? 'article';
        
        $prompts = [
            'professional' => "Rewrite this content in a professional, formal tone while maintaining accuracy:",
            'casual' => "Rewrite this content in a casual, conversational tone:",
            'seo' => "Rewrite this content for better SEO optimization, include relevant keywords naturally:",
            'simple' => "Simplify this content to make it easy to understand:",
        ];
        
        $base_prompt = $prompts[$tone] ?? $prompts['professional'];
        
        if ($style === 'blog_post') {
            $base_prompt .= " Format as a blog post with engaging introduction and conclusion.";
        } elseif ($style === 'news') {
            $base_prompt .= " Format as a news article with inverted pyramid structure.";
        }
        
        return $base_prompt . "\n\n" . strip_tags(substr($content, 0, 5000));
    }
    
    private function call_openai($prompt, $api_key, $settings) {
        $model = $settings['model'] ?? 'gpt-3.5-turbo';
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => json_encode([
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional content writer.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => $settings['temperature'] ?? 0.7,
                'max_tokens' => $settings['max_tokens'] ?? 2000,
            ]),
            'timeout' => 60,
        ]);
        
        if (is_wp_error($response)) {
            Logs::add([
                'type' => 'ai_error',
                'message' => 'OpenAI API Error: ' . $response->get_error_message(),
                'status' => 'error'
            ]);
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            Logs::add([
                'type' => 'ai_error',
                'message' => 'OpenAI Error: ' . $body['error']['message'],
                'status' => 'error'
            ]);
            return false;
        }
        
        return $body['choices'][0]['message']['content'] ?? '';
    }
    
    private function call_anthropic($prompt, $api_key, $settings) {
        // Implement Claude API
    }
    
    private function call_google($prompt, $api_key, $settings) {
        // Implement Google Gemini API
    }
}