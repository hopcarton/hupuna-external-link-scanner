<?php
/**
 * Class Scanner chính - Quét và lọc external links
 * 
 * @package Hupuna_External_Link_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Hupuna_External_Link_Scanner {
    
    /**
     * Domain hiện tại của website
     */
    private $site_domain;
    
    /**
     * Constructor - Khởi tạo domain
     */
    public function __construct() {
        $this->site_domain = $this->get_site_domain();
    }
    
    /**
     * Lấy domain hiện tại của website
     */
    private function get_site_domain() {
        $url = home_url();
        $parsed = parse_url($url);
        return isset($parsed['host']) ? $parsed['host'] : '';
    }
    
    /**
     * Kiểm tra URL có phải external không
     */
    private function is_external_url($url) {
        if (empty($url)) {
            return false;
        }
        
        // Loại bỏ protocol
        $url = preg_replace('#^https?://#', '', $url);
        
        // Loại bỏ www
        $url = preg_replace('#^www\.#', '', $url);
        
        // Lấy domain từ URL
        $parsed = parse_url('http://' . $url);
        if (!isset($parsed['host'])) {
            return false;
        }
        
        $url_domain = preg_replace('#^www\.#', '', $parsed['host']);
        $site_domain = preg_replace('#^www\.#', '', $this->site_domain);
        
        return strtolower($url_domain) !== strtolower($site_domain);
    }
    
    /**
     * Trích xuất các link từ nội dung - Quét tất cả thẻ HTML có chứa URL
     */
    private function extract_links($content) {
        $links = array();
        
        // 1. Quét thẻ <a> với href
        preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => strip_tags($match[2]),
                    'tag' => 'a',
                    'attribute' => 'href'
                );
            }
        }
        
        // 2. Quét thẻ <img> với src và srcset
        preg_match_all('/<img\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'img',
                    'attribute' => 'src'
                );
            }
        }
        
        // Quét srcset trong img
        preg_match_all('/<img\s+[^>]*srcset=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $srcset = $match[1];
            // Parse srcset: "url1 1x, url2 2x" hoặc "url1 100w, url2 200w"
            preg_match_all('/(https?:\/\/[^\s,]+)/i', $srcset, $urls);
            foreach ($urls[1] as $url) {
                $url = $this->normalize_url($url);
                if ($url && $this->is_external_url($url)) {
                    $links[] = array(
                        'url' => $url,
                        'text' => '',
                        'tag' => 'img',
                        'attribute' => 'srcset'
                    );
                }
            }
        }
        
        // 3. Quét thẻ <link> với href
        preg_match_all('/<link\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'link',
                    'attribute' => 'href'
                );
            }
        }
        
        // 4. Quét thẻ <iframe> với src
        preg_match_all('/<iframe\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'iframe',
                    'attribute' => 'src'
                );
            }
        }
        
        // 5. Quét thẻ <video> với src và poster
        preg_match_all('/<video\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'video',
                    'attribute' => 'src'
                );
            }
        }
        
        preg_match_all('/<video\s+[^>]*poster=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'video',
                    'attribute' => 'poster'
                );
            }
        }
        
        // 6. Quét thẻ <audio> với src
        preg_match_all('/<audio\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'audio',
                    'attribute' => 'src'
                );
            }
        }
        
        // 7. Quét thẻ <source> với src và srcset
        preg_match_all('/<source\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'source',
                    'attribute' => 'src'
                );
            }
        }
        
        preg_match_all('/<source\s+[^>]*srcset=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $srcset = $match[1];
            preg_match_all('/(https?:\/\/[^\s,]+)/i', $srcset, $urls);
            foreach ($urls[1] as $url) {
                $url = $this->normalize_url($url);
                if ($url && $this->is_external_url($url)) {
                    $links[] = array(
                        'url' => $url,
                        'text' => '',
                        'tag' => 'source',
                        'attribute' => 'srcset'
                    );
                }
            }
        }
        
        // 8. Quét thẻ <embed> với src
        preg_match_all('/<embed\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'embed',
                    'attribute' => 'src'
                );
            }
        }
        
        // 9. Quét thẻ <object> với data
        preg_match_all('/<object\s+[^>]*data=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'object',
                    'attribute' => 'data'
                );
            }
        }
        
        // 10. Quét CSS background-image trong style attributes
        preg_match_all('/style=["\'][^"\']*background-image:\s*url\(["\']?([^"\'()]+)["\']?\)/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'style',
                    'attribute' => 'background-image'
                );
            }
        }
        
        // 11. Quét thẻ <form> với action
        preg_match_all('/<form\s+[^>]*action=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'form',
                    'attribute' => 'action'
                );
            }
        }
        
        // 12. Quét thẻ <input> với formaction
        preg_match_all('/<input\s+[^>]*formaction=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'input',
                    'attribute' => 'formaction'
                );
            }
        }
        
        // 13. Quét thẻ <button> với formaction
        preg_match_all('/<button\s+[^>]*formaction=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'button',
                    'attribute' => 'formaction'
                );
            }
        }
        
        // 14. Quét thẻ <area> với href
        preg_match_all('/<area\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'area',
                    'attribute' => 'href'
                );
            }
        }
        
        // 15. Quét thẻ <base> với href
        preg_match_all('/<base\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'base',
                    'attribute' => 'href'
                );
            }
        }
        
        // 16. Quét thẻ <track> với src
        preg_match_all('/<track\s+[^>]*src=["\']([^"\']+)["\'][^>]*>/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'track',
                    'attribute' => 'src'
                );
            }
        }
        
        // 17. Quét các data attributes có chứa URL
        preg_match_all('/data-[^=]*=["\'](https?:\/\/[^"\']+)["\']/is', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $this->normalize_url($match[1]);
            if ($url && $this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => '',
                    'tag' => 'data-attribute',
                    'attribute' => 'data-*'
                );
            }
        }
        
        return $links;
    }
    
    /**
     * Chuẩn hóa URL - loại bỏ các URL không hợp lệ và chuyển relative thành absolute
     */
    private function normalize_url($url) {
        if (empty($url)) {
            return false;
        }
        
        // Bỏ qua anchor, mailto, tel, javascript, data URI
        if (strpos($url, '#') === 0 || 
            strpos($url, 'mailto:') === 0 || 
            strpos($url, 'tel:') === 0 ||
            strpos($url, 'javascript:') === 0 ||
            strpos($url, 'data:') === 0) {
            return false;
        }
        
        // Loại bỏ embed URLs (WordPress oEmbed)
        if (strpos($url, '/embed/') !== false || 
            strpos($url, '#?secret=') !== false ||
            strpos($url, '?secret=') !== false) {
            return false;
        }
        
        // Chuyển relative URL thành absolute
        if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0) {
            $url = home_url($url);
        } elseif (strpos($url, '//') === 0) {
            $url = 'http:' . $url;
        }
        
        return $url;
    }
    
    /**
     * Quét tất cả custom post types (bao gồm posts, pages, products, v.v.)
     */
    public function scan_all_post_types() {
        $results = array();
        
        // Lấy tất cả post types (bao gồm custom post types)
        $post_types = get_post_types(array('public' => true), 'names');
        // Thêm cả post types không public nhưng có thể chứa nội dung
        $private_post_types = get_post_types(array('public' => false, '_builtin' => false), 'names');
        $post_types = array_merge($post_types, $private_post_types);
        
        // Loại bỏ attachment và revision
        $post_types = array_diff($post_types, array('attachment', 'revision', 'nav_menu_item'));
        
        foreach ($post_types as $post_type) {
            $args = array(
                'post_type' => $post_type,
                'post_status' => 'any',
                'posts_per_page' => -1,
                'fields' => 'ids'
            );
            
            $posts = get_posts($args);
            
            foreach ($posts as $post_id) {
                $post = get_post($post_id);
                if (!$post) {
                    continue;
                }
                
                // Quét post content
                $content = $post->post_content;
                $links = $this->extract_links($content);
                // Đánh dấu location cho content links
                foreach ($links as &$link) {
                    $link['location'] = 'content';
                }
                
                // Quét post excerpt
                if (!empty($post->post_excerpt)) {
                    $excerpt_links = $this->extract_links($post->post_excerpt);
                    foreach ($excerpt_links as &$link) {
                        $link['location'] = 'excerpt';
                    }
                    $links = array_merge($links, $excerpt_links);
                }
                
                // Quét post meta (chỉ quét các meta không phải embed cache)
                $meta_links = $this->scan_post_meta($post_id);
                foreach ($meta_links as &$link) {
                    $link['location'] = 'meta';
                }
                $links = array_merge($links, $meta_links);
                
                if (!empty($links)) {
                    foreach ($links as $link) {
                        $post_type_obj = get_post_type_object($post_type);
                        $type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $post_type;
                        
                        $location_label = '';
                        if (isset($link['location'])) {
                            switch ($link['location']) {
                                case 'content':
                                    $location_label = ' (Content)';
                                    break;
                                case 'excerpt':
                                    $location_label = ' (Excerpt)';
                                    break;
                                case 'meta':
                                    $location_label = ' (Custom Field)';
                                    break;
                            }
                        }
                        
                        $results[] = array(
                            'type' => $post_type,
                            'id' => $post_id,
                            'title' => $post->post_title . ' (' . $type_label . ')' . $location_label,
                            'url' => $link['url'],
                            'link_text' => $link['text'],
                            'tag' => isset($link['tag']) ? $link['tag'] : 'a',
                            'attribute' => isset($link['attribute']) ? $link['attribute'] : 'href',
                            'location' => isset($link['location']) ? $link['location'] : 'content',
                            'edit_url' => get_edit_post_link($post_id, 'raw'),
                            'view_url' => get_permalink($post_id)
                        );
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Quét post meta (custom fields) - Loại bỏ embed cache và các meta không cần thiết
     */
    private function scan_post_meta($post_id) {
        $links = array();
        $meta = get_post_meta($post_id);
        
        // Các meta keys cần bỏ qua (embed cache, internal WordPress meta)
        $skip_keys = array(
            '_oembed_',
            '_oembed_time_',
            '_edit_lock',
            '_edit_last',
            '_wp_old_slug',
            '_wp_page_template',
            '_thumbnail_id',
            '_wp_attachment_',
            '_wp_old_date',
        );
        
        if (is_array($meta)) {
            foreach ($meta as $key => $values) {
                // Bỏ qua embed cache và các meta WordPress internal
                $should_skip = false;
                foreach ($skip_keys as $skip_key) {
                    if (strpos($key, $skip_key) === 0) {
                        $should_skip = true;
                        break;
                    }
                }
                
                if ($should_skip) {
                    continue;
                }
                
                if (is_array($values)) {
                    foreach ($values as $value) {
                        if (is_string($value) && !empty($value)) {
                            // Chỉ quét nếu có chứa http hoặc HTML tags
                            if (strpos($value, 'http') !== false || strpos($value, '<') !== false) {
                                $meta_links = $this->extract_links($value);
                                // Thêm meta key vào link để biết nguồn
                                foreach ($meta_links as &$link) {
                                    $link['meta_key'] = $key;
                                }
                                $links = array_merge($links, $meta_links);
                            }
                        }
                    }
                }
            }
        }
        
        return $links;
    }
    
    /**
     * Quét tất cả comments
     */
    public function scan_comments() {
        $results = array();
        
        $args = array(
            'status' => 'all',
            'number' => 0
        );
        
        $comments = get_comments($args);
        
        foreach ($comments as $comment) {
            $content = $comment->comment_content;
            $links = $this->extract_links($content);
            
            if (!empty($links)) {
                foreach ($links as $link) {
                    $results[] = array(
                        'type' => 'comment',
                        'id' => $comment->comment_ID,
                        'title' => sprintf('Comment #%d on "%s"', $comment->comment_ID, get_the_title($comment->comment_post_ID)),
                        'url' => $link['url'],
                        'link_text' => $link['text'],
                        'tag' => isset($link['tag']) ? $link['tag'] : 'a',
                        'attribute' => isset($link['attribute']) ? $link['attribute'] : 'href',
                        'edit_url' => get_edit_comment_link($comment->comment_ID),
                        'view_url' => get_comment_link($comment->comment_ID)
                    );
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Quét tất cả widgets
     */
    public function scan_widgets() {
        $results = array();
        
        // Quét tất cả widget options
        global $wpdb;
        $widget_options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'widget_%'"
        );
        
        foreach ($widget_options as $option) {
            $widget_data = maybe_unserialize($option->option_value);
            if (is_array($widget_data)) {
                foreach ($widget_data as $widget_id => $widget) {
                    if (is_array($widget)) {
                        // Quét tất cả các field trong widget
                        foreach ($widget as $key => $value) {
                            if (is_string($value)) {
                                $links = $this->extract_links($value);
                                if (!empty($links)) {
                                    foreach ($links as $link) {
                                        $results[] = array(
                                            'type' => 'widget',
                                            'id' => $widget_id,
                                            'title' => sprintf('Widget: %s #%s', str_replace('widget_', '', $option->option_name), $widget_id),
                                            'url' => $link['url'],
                                            'link_text' => $link['text'],
                                            'tag' => isset($link['tag']) ? $link['tag'] : 'a',
                                            'attribute' => isset($link['attribute']) ? $link['attribute'] : 'href',
                                            'edit_url' => admin_url('widgets.php'),
                                            'view_url' => home_url()
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Quét menu items
     */
    public function scan_menus() {
        $results = array();
        
        $menus = wp_get_nav_menus();
        
        foreach ($menus as $menu) {
            $menu_items = wp_get_nav_menu_items($menu->term_id);
            
            if (is_array($menu_items)) {
                foreach ($menu_items as $item) {
                    // Quét URL của menu item
                    if (!empty($item->url)) {
                        $url = $this->normalize_url($item->url);
                        if ($url && $this->is_external_url($url)) {
                            $results[] = array(
                                'type' => 'menu',
                                'id' => $item->ID,
                                'title' => sprintf('Menu: %s - %s', $menu->name, $item->title),
                                'url' => $url,
                                'link_text' => $item->title,
                                'tag' => 'a',
                                'attribute' => 'href',
                                'edit_url' => admin_url('nav-menus.php'),
                                'view_url' => $item->url
                            );
                        }
                    }
                    
                    // Quét description của menu item
                    if (!empty($item->description)) {
                        $links = $this->extract_links($item->description);
                        if (!empty($links)) {
                            foreach ($links as $link) {
                                $results[] = array(
                                    'type' => 'menu',
                                    'id' => $item->ID,
                                    'title' => sprintf('Menu: %s - %s (Description)', $menu->name, $item->title),
                                    'url' => $link['url'],
                                    'link_text' => $link['text'],
                                    'tag' => isset($link['tag']) ? $link['tag'] : 'a',
                                    'attribute' => isset($link['attribute']) ? $link['attribute'] : 'href',
                                    'edit_url' => admin_url('nav-menus.php'),
                                    'view_url' => $item->url
                                );
                            }
                        }
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Quét term meta (taxonomy terms)
     */
    public function scan_terms() {
        $results = array();
        
        $taxonomies = get_taxonomies(array('public' => true), 'names');
        
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false
            ));
            
            if (is_array($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    // Quét term description
                    if (!empty($term->description)) {
                        $links = $this->extract_links($term->description);
                        if (!empty($links)) {
                            foreach ($links as $link) {
                                $results[] = array(
                                    'type' => 'term',
                                    'id' => $term->term_id,
                                    'title' => sprintf('Term: %s - %s', $taxonomy, $term->name),
                                    'url' => $link['url'],
                                    'link_text' => $link['text'],
                                    'tag' => isset($link['tag']) ? $link['tag'] : 'a',
                                    'attribute' => isset($link['attribute']) ? $link['attribute'] : 'href',
                                    'edit_url' => get_edit_term_link($term->term_id, $taxonomy),
                                    'view_url' => get_term_link($term)
                                );
                            }
                        }
                    }
                    
                    // Quét term meta
                    $term_meta = get_term_meta($term->term_id);
                    if (is_array($term_meta)) {
                        foreach ($term_meta as $key => $values) {
                            if (is_array($values)) {
                                foreach ($values as $value) {
                                    if (is_string($value)) {
                                        $meta_links = $this->extract_links($value);
                                        if (!empty($meta_links)) {
                                            foreach ($meta_links as $link) {
                                                $results[] = array(
                                                    'type' => 'term',
                                                    'id' => $term->term_id,
                                                    'title' => sprintf('Term Meta: %s - %s (%s)', $taxonomy, $term->name, $key),
                                                    'url' => $link['url'],
                                                    'link_text' => $link['text'],
                                                    'tag' => isset($link['tag']) ? $link['tag'] : 'a',
                                                    'attribute' => isset($link['attribute']) ? $link['attribute'] : 'href',
                                                    'edit_url' => get_edit_term_link($term->term_id, $taxonomy),
                                                    'view_url' => get_term_link($term)
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Quét options (theme options, settings)
     */
    public function scan_options() {
        $results = array();
        
        global $wpdb;
        // Quét các options có thể chứa HTML/URL
        $options = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
            WHERE option_name NOT LIKE '_transient%' 
            AND option_name NOT LIKE '_site_transient%'
            AND option_name NOT LIKE 'cron'
            AND option_name NOT LIKE 'rewrite_rules'
            LIMIT 1000"
        );
        
        foreach ($options as $option) {
            $value = $option->option_value;
            if (is_string($value) && (strpos($value, 'http') !== false || strpos($value, '<') !== false)) {
                $links = $this->extract_links($value);
                if (!empty($links)) {
                    foreach ($links as $link) {
                        $results[] = array(
                            'type' => 'option',
                            'id' => $option->option_name,
                            'title' => sprintf('Option: %s', $option->option_name),
                            'url' => $link['url'],
                            'link_text' => $link['text'],
                            'tag' => isset($link['tag']) ? $link['tag'] : 'a',
                            'attribute' => isset($link['attribute']) ? $link['attribute'] : 'href',
                            'edit_url' => admin_url('options-general.php'),
                            'view_url' => home_url()
                        );
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Quét tất cả nội dung website
     */
    public function scan_all() {
        $results = array();
        
        // Quét tất cả post types (posts, pages, products, custom post types)
        $results = array_merge($results, $this->scan_all_post_types());
        
        // Quét comments
        $results = array_merge($results, $this->scan_comments());
        
        // Quét widgets
        $results = array_merge($results, $this->scan_widgets());
        
        // Quét menu items
        $results = array_merge($results, $this->scan_menus());
        
        // Quét terms (categories, tags, custom taxonomies)
        $results = array_merge($results, $this->scan_terms());
        
        // Quét options (theme options, settings)
        $results = array_merge($results, $this->scan_options());
        
        return $results;
    }
    
    /**
     * Nhóm kết quả theo URL
     */
    public function group_by_url($results) {
        $grouped = array();
        
        foreach ($results as $result) {
            $url = $result['url'];
            if (!isset($grouped[$url])) {
                $grouped[$url] = array(
                    'url' => $url,
                    'occurrences' => array()
                );
            }
            $grouped[$url]['occurrences'][] = $result;
        }
        
        return $grouped;
    }
}

