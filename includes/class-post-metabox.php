<?php
// Prevent direct access
if (!defined('ABSPATH')) exit;

class WHP_Post_Metabox {
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_metabox']);
        add_action('save_post', [$this, 'save_suggestion_apply']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_style('whp-metabox', plugins_url('../assets/css/admin.css', __FILE__));
        wp_enqueue_script('whp-metabox', plugins_url('../assets/js/admin.js', __FILE__), ['jquery'], false, true);
    }

    public function register_metabox() {
        add_meta_box('whp_ai_suggestions', __('WHP AI Suggestions', 'webharvest-pro'), [$this, 'render_metabox'], 'post', 'side', 'default');
    }

    public function render_metabox($post) {
        $suggestions = get_post_meta($post->ID, '_whp_ai_suggestions', true);
        if (empty($suggestions) || !is_array($suggestions)) {
            echo '<p>' . esc_html__('No AI suggestions for this post.', 'webharvest-pro') . '</p>';
            return;
        }

        echo '<div id="whp_ai_suggestions_list">';
        foreach ($suggestions as $i => $s) {
            $type = esc_html($s['type'] ?? 'note');
            $reason = esc_html($s['reason'] ?? '');
            $suggestion = wp_kses_post($s['suggestion'] ?? '');
            $excerpt = esc_html($s['path'] ?? '');
            echo '<div class="whp-suggestion" data-index="' . esc_attr($i) . '">';
            echo '<strong>' . $type . '</strong><p>' . $reason . '</p>';
            echo '<p><em>' . $excerpt . '</em></p>';
            echo '<div class="whp-suggestion-sample">' . $suggestion . '</div>';
                        echo '<p>
                                        <label><input type="checkbox" name="whp_apply_suggestion[' . esc_attr($i) . ']" value="1"> ' . esc_html__('Apply', 'webharvest-pro') . '</label>
                                        <button type="button" class="button whp-preview-suggestion" data-index="' . esc_attr($i) . '">' . esc_html__('Preview', 'webharvest-pro') . '</button>
                                    </p>';
            echo '</div>';
        }
        echo '</div>';
        wp_nonce_field('whp_apply_suggestions', 'whp_apply_suggestions_nonce');

        // Inline JS for previewing suggestion (DOM-aware) with modal and apply
        ?>
        <div id="whp_suggestion_modal" style="display:none;">
            <div class="whp-modal-backdrop" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9998;"></div>
            <div class="whp-modal" style="position:fixed;left:50%;top:50%;transform:translate(-50%,-50%);background:#fff;padding:20px;z-index:9999;width:80%;max-width:900px;max-height:80%;overflow:auto;box-shadow:0 4px 20px rgba(0,0,0,.2);">
                <h3><?php echo esc_html__('Suggestion Preview', 'webharvest-pro'); ?></h3>
                <div style="display:flex;gap:10px;">
                    <div style="flex:1;">
                        <h4><?php echo esc_html__('Before', 'webharvest-pro'); ?></h4>
                        <div id="whp_preview_before" style="border:1px solid #eee;padding:10px;max-height:400px;overflow:auto;background:#fafafa;"></div>
                    </div>
                    <div style="flex:1;">
                        <h4><?php echo esc_html__('After', 'webharvest-pro'); ?></h4>
                        <div id="whp_preview_after" style="border:1px solid #eee;padding:10px;max-height:400px;overflow:auto;background:#fff;"></div>
                    </div>
                </div>
                <p style="margin-top:10px;text-align:right;">
                    <button type="button" class="button" id="whp_preview_apply"><?php echo esc_html__('Apply & Close', 'webharvest-pro'); ?></button>
                    <button type="button" class="button" id="whp_preview_close"><?php echo esc_html__('Close', 'webharvest-pro'); ?></button>
                </p>
            </div>
        </div>

        <script>
        (function(){
            function openModal(before, after, index){
                document.getElementById('whp_preview_before').innerHTML = before;
                document.getElementById('whp_preview_after').innerHTML = after;
                var modal = document.getElementById('whp_suggestion_modal');
                modal.style.display = 'block';
                // store index
                modal.setAttribute('data-index', index);
            }

            function closeModal(){
                var modal = document.getElementById('whp_suggestion_modal');
                modal.style.display = 'none';
                modal.removeAttribute('data-index');
            }

            document.addEventListener('click', function(e){
                var btn = e.target.closest && e.target.closest('.whp-preview-suggestion');
                if (!btn) return;
                var idx = btn.getAttribute('data-index');
                var suggestionEl = document.querySelector('.whp-suggestion[data-index="'+idx+'"]');
                var sample = suggestionEl.querySelector('.whp-suggestion-sample').innerHTML;
                var excerpt = suggestionEl.querySelector('em') ? suggestionEl.querySelector('em').innerText : '';

                // For 'before' try to extract surrounding HTML from post content using excerpt; otherwise show excerpt
                var before = excerpt ? ('<pre>' + excerpt + '</pre>') : '<p><?php echo esc_html__('No context available', 'webharvest-pro'); ?></p>';
                var after = sample;
                openModal(before, after, idx);
            });

            document.getElementById('whp_preview_close').addEventListener('click', function(){ closeModal(); });
            document.getElementById('whp_preview_apply').addEventListener('click', function(){
                var modal = document.getElementById('whp_suggestion_modal');
                var idx = modal.getAttribute('data-index');
                // check the corresponding checkbox and submit the post form
                var cb = document.querySelector('.whp-suggestion[data-index="'+idx+'"] input[type="checkbox"]');
                if (cb) cb.checked = true;
                closeModal();
                // submit form
                document.getElementById('post').submit();
            });
        })();
        </script>
        <?php
    }

    public function save_suggestion_apply($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (empty($_POST['whp_apply_suggestion']) || !wp_verify_nonce($_POST['whp_apply_suggestions_nonce'] ?? '', 'whp_apply_suggestions')) return;

        $suggestions = get_post_meta($post_id, '_whp_ai_suggestions', true);
        if (empty($suggestions) || !is_array($suggestions)) return;

        $apply = $_POST['whp_apply_suggestion'];
        $content = get_post_field('post_content', $post_id);

        // Attempt DOM/XPath-aware modifications when possible
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $xpathObj = new \DOMXPath($dom);

        $modified = false;
        foreach ($apply as $index => $val) {
            $index = intval($index);
            if (!isset($suggestions[$index])) continue;
            $s = $suggestions[$index];
            $type = $s['type'] ?? '';
            $path = $s['path'] ?? '';
            $suggest = $s['suggestion'] ?? '';

            if ($path && strpos($path, '/') === 0) {
                // Treat path as XPath
                $nodes = @$xpathObj->query($path);
                if ($nodes && $nodes->length > 0) {
                    foreach ($nodes as $node) {
                                    if ($type === 'remove') {
                                        $node->parentNode->removeChild($node);
                                        $modified = true;
                                    } elseif ($type === 'replace' || $type === 'change') {
                                        // sanitize suggested HTML and replace innerHTML
                                        $safe = wp_kses_post($suggest);
                                        // create fragment from safe HTML
                                        $frag = $dom->createDocumentFragment();
                                        // Use temporary wrapper to import HTML
                                        $tmp = $dom->createElement('div');
                                        $tmp->nodeValue = ''; // ensure
                                        $tmp->appendChild($dom->createTextNode(''));
                                        // import by loading HTML into DOMDocument fragment
                                        $innerDoc = new \DOMDocument();
                                        @$innerDoc->loadHTML('<div>' . $safe . '</div>');
                                        $innerBody = $innerDoc->getElementsByTagName('div')->item(0);
                                        if ($innerBody) {
                                            foreach ($innerBody->childNodes as $child) {
                                                $imported = $dom->importNode($child, true);
                                                $frag->appendChild($imported);
                                            }
                                            while ($node->firstChild) $node->removeChild($node->firstChild);
                                            $node->appendChild($frag);
                                            $modified = true;
                                        }
                                    }
                    }
                } else {
                    // fallback: try text-based replace
                    if ($type === 'remove') {
                        $content = str_replace($path, '', $content);
                        $modified = true;
                    } elseif ($type === 'replace' || $type === 'change') {
                        $content = str_replace($path, $suggest, $content);
                        $modified = true;
                    }
                }
            } else {
                // path not XPath: try text replace
                if ($type === 'remove') {
                    $content = str_replace($path, '', $content);
                    $modified = true;
                } elseif ($type === 'replace' || $type === 'change') {
                    $content = str_replace($path, $suggest, $content);
                    $modified = true;
                }
            }
        }

        if ($modified) {
            // Prefer DOM if we made DOM changes
            $new_content = $dom->saveHTML();
            // Strip doctype/html/body wrappers if present
            $new_content = preg_replace('~^(?:<!DOCTYPE.+?>)?\s*<html[^>]*>\s*<body[^>]*>(.*)</body>\s*</html>\s*$~is', '$1', $new_content);
            $content = $new_content;
        }

        // Update post content without triggering infinite loops
        remove_action('save_post', [$this, 'save_suggestion_apply']);
        wp_update_post(array('ID' => $post_id, 'post_content' => $content));
        add_action('save_post', [$this, 'save_suggestion_apply']);
    }
}

new WHP_Post_Metabox();
