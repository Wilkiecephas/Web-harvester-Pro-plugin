<?php
namespace WebHarvest_Pro;

class Content_Parser {
    
    public function parse($html, $url) {
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        
        return [
            'title' => $this->extract_title($dom),
            'content' => $this->extract_content($dom),
            'excerpt' => $this->extract_excerpt($dom),
            'images' => $this->extract_images($dom, $url),
            'categories' => $this->extract_categories($dom),
            'tags' => $this->extract_tags($dom),
            'meta' => $this->extract_meta($dom),
        ];
    }
    
    private function extract_title($dom) {
        // Try Open Graph first
        $title = $this->extract_meta_property($dom, 'og:title');
        if ($title) return $title;
        
        // Try Twitter card
        $title = $this->extract_meta_name($dom, 'twitter:title');
        if ($title) return $title;
        
        // Fallback to <title> tag
        $titles = $dom->getElementsByTagName('title');
        if ($titles->length > 0) {
            return trim($titles->item(0)->textContent);
        }
        
        // Try h1
        $h1s = $dom->getElementsByTagName('h1');
        if ($h1s->length > 0) {
            return trim($h1s->item(0)->textContent);
        }
        
        return '';
    }
    
    private function extract_content($dom) {
        $xpath = new \DOMXPath($dom);
        
        // Try common content containers
        $content_selectors = [
            '//article',
            '//*[contains(@class, "post-content")]',
            '//*[contains(@class, "entry-content")]',
            '//*[contains(@class, "article-content")]',
            '//*[@id="content"]',
            '//main',
            '//*[contains(@class, "content")]',
        ];
        
        foreach ($content_selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $content = $this->clean_html($nodes->item(0));
                if (strlen(strip_tags($content)) > 100) {
                    return $content;
                }
            }
        }
        
        // Fallback: body content
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            return $this->clean_html($body);
        }
        
        return '';
    }
    
    private function clean_html($node) {
        // Remove unwanted elements
        $unwanted = [
            'script', 'style', 'iframe', 'nav', 'header', 'footer',
            'aside', 'form', '.social-share', '.comments', '.advertisement'
        ];
        
        $dom = new \DOMDocument();
        $cloned = $node->cloneNode(true);
        $dom->appendChild($dom->importNode($cloned, true));
        $xpath = new \DOMXPath($dom);
        
        foreach ($unwanted as $selector) {
            if (strpos($selector, '.') === 0) {
                $elements = $xpath->query('//*[contains(@class, "' . substr($selector, 1) . '")]');
            } else {
                $elements = $xpath->query('//' . $selector);
            }
            
            foreach ($elements as $element) {
                $element->parentNode->removeChild($element);
            }
        }
        
        // Convert relative URLs to absolute
        $this->make_urls_absolute($dom);
        
        return $dom->saveHTML();
    }
    
    private function make_urls_absolute($dom) {
        $xpath = new \DOMXPath($dom);
        $tags = [
            'a' => 'href',
            'img' => 'src',
            'iframe' => 'src',
            'source' => 'src',
        ];
        
        foreach ($tags as $tag => $attribute) {
            $nodes = $xpath->query('//' . $tag . '[@' . $attribute . ']');
            foreach ($nodes as $node) {
                $url = $node->getAttribute($attribute);
                if (!filter_var($url, FILTER_VALIDATE_URL) && $url) {
                    // Make absolute (base URL needed here)
                }
            }
        }
    }
    
    private function extract_excerpt($dom, $length = 200) {
        $content = strip_tags($this->extract_content($dom));
        $content = preg_replace('/\s+/', ' ', $content);
        
        if (strlen($content) <= $length) {
            return $content;
        }
        
        $excerpt = substr($content, 0, $length);
        $last_space = strrpos($excerpt, ' ');
        return substr($excerpt, 0, $last_space) . '...';
    }
    
    private function extract_images($dom, $base_url) {
        $images = [];
        $img_tags = $dom->getElementsByTagName('img');
        
        foreach ($img_tags as $img) {
            $src = $img->getAttribute('src');
            if (!$src || strpos($src, 'data:') === 0) {
                continue;
            }
            
            $images[] = [
                'src' => $this->make_absolute_url($src, $base_url),
                'alt' => $img->getAttribute('alt'),
                'title' => $img->getAttribute('title'),
            ];
        }
        
        return $images;
    }
    
    private function extract_meta_property($dom, $property) {
        $xpath = new \DOMXPath($dom);
        $meta = $xpath->query("//meta[@property='{$property}']");
        return $meta->length > 0 ? $meta->item(0)->getAttribute('content') : null;
    }
    
    private function extract_meta_name($dom, $name) {
        $xpath = new \DOMXPath($dom);
        $meta = $xpath->query("//meta[@name='{$name}']");
        return $meta->length > 0 ? $meta->item(0)->getAttribute('content') : null;
    }
}