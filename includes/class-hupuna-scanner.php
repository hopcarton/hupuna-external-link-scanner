<?php
/**
 * Main Scanner Class
 * Handles logic for extracting, normalizing, and verifying external links.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Hupuna_External_Link_Scanner {
    
    private $site_domain;
    
    public function __construct() {
        $this->site_domain = $this->get_site_domain();
    }
    
    /**
     * Get current site domain
     */
    private function get_site_domain() {
        $url = home_url();
        $parsed = parse_url($url);
        return isset($parsed['host']) ? $parsed['host'] : '';
    }
    
    /**
     * Check if URL is external and not whitelisted
     */
    public function is_external_url($url) {
        if (empty($url)) {
            return false;
        }
        
        // Remove protocol and www
        $url_clean = preg_replace('#^https?://#', '', $url);
        $url_clean = preg_replace('#^www\.#', '', $url_clean);
        
        $parsed = parse_url('http://' . $url_clean);
        if (!isset($parsed['host'])) {
            return false;
        }
        
        $url_domain = preg_replace('#^www\.#', '', $parsed['host']);
        $site_domain = preg_replace('#^www\.#', '', $this->site_domain);
        
        // Whitelist system domains
        $whitelist = array(
            'wordpress.org',
            'woocommerce.com',
            'gravatar.com',
            'wp.com',
            's0.wp.com', 
            's1.wp.com', 
            's2.wp.com',
            'secure.gravatar.com',
            'w.org'
        );
        
        foreach ($whitelist as $white) {
            if (strpos($url_domain, $white) !== false) {
                return false;
            }
        }
        
        return strtolower($url_domain) !== strtolower($site_domain);
    }
    
    /**
     * Extract links from content string using Regex
     */
    private function extract_links($content) {
        if (empty($content) || (strpos($content, 'http') === false && strpos($content, '<') === false)) {
            return array();
        }

        $links = array();
        
        // Pattern 1: Anchor tags
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
        
        // Pattern 2: Image tags
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
        
        return $links;
    }
    
    /**
     * Normalize URL (handle relative paths)
     */
    private function normalize_url($url) {
        if (empty($url)) return false;
        
        // Skip non-http protocols
        if (strpos($url, '#') === 0 || strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0 || strpos($url, 'javascript:') === 0 || strpos($url, 'data:') === 0) {
            return false;
        }
        
        // Handle relative URLs
        if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0) {
            if (strpos($url, '/') === 0) {
                $url = home_url($url);
            }
        } elseif (strpos($url, '//') === 0) {
            $url = 'http:' . $url;
        }
        
        return $url;
    }
    
    /**
     * Get list of public post types to scan
     */
    public function get_scannable_post_types() {
        $post_types = get_post_types(array('public' => true), 'names');
        $excluded = array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset');
        return array_diff($post_types, $excluded);
    }

    /**
     * Batch Scan: Post Types
     */
    public function scan_post_type_batch($post_type, $page = 1, $per_page = 20) {
        $results = array();
        
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'any',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_term_cache' => false,
        );
        
        $posts = get_posts($args);
        
        if (empty($posts)) {
            return array('results' => array(), 'done' => true);
        }
        
        foreach ($posts as $post_id) {
            $post = get_post($post_id);
            if (!$post) continue;
            
            // Scan Content
            $links = $this->extract_links($post->post_content);
            foreach ($links as $link) {
                $results[] = $this->format_result($link, $post_type, $post_id, $post->post_title, 'Content');
            }
            
            // Scan Excerpt
            if (!empty($post->post_excerpt)) {
                $links = $this->extract_links($post->post_excerpt);
                foreach ($links as $link) {
                    $results[] = $this->format_result($link, $post_type, $post_id, $post->post_title, 'Excerpt');
                }
            }
        }
        
        return array('results' => $results, 'done' => count($posts) < $per_page);
    }
    
    /**
     * Batch Scan: Comments
     */
    public function scan_comments_batch($page = 1, $per_page = 50) {
        $results = array();
        $offset = ($page - 1) * $per_page;
        
        $comments = get_comments(array(
            'number' => $per_page,
            'offset' => $offset,
            'status' => 'all'
        ));
        
        if (empty($comments)) return array('results' => array(), 'done' => true);
        
        foreach ($comments as $comment) {
            $links = $this->extract_links($comment->comment_content);
            foreach ($links as $link) {
                $results[] = array(
                    'type' => 'comment',
                    'id' => $comment->comment_ID,
                    'title' => 'Comment #' . $comment->comment_ID,
                    'url' => $link['url'],
                    'link_text' => $link['text'],
                    'tag' => $link['tag'],
                    'attribute' => $link['attribute'],
                    'location' => 'Content',
                    'edit_url' => get_edit_comment_link($comment->comment_ID),
                    'view_url' => get_comment_link($comment->comment_ID)
                );
            }
        }
        
        return array('results' => $results, 'done' => count($comments) < $per_page);
    }
    
    /**
     * Batch Scan: Options (Optimized SQL)
     */
    public function scan_options_batch($page = 1, $per_page = 100) {
        global $wpdb;
        $offset = ($page - 1) * $per_page;
        $results = array();
        
        // Direct SQL to filter out junk options
        $query = $wpdb->prepare(
            "SELECT option_name, option_value FROM {$wpdb->options} 
            WHERE option_name NOT LIKE '%%transient%%' 
            AND option_name NOT LIKE 'cron'
            AND option_name NOT LIKE 'ptk_patterns'
            AND option_name NOT LIKE 'woocommerce_%%'
            AND (option_value LIKE '%%http%%' OR option_value LIKE '%%<%%')
            LIMIT %d, %d", 
            $offset, $per_page
        );
        
        $options = $wpdb->get_results($query);
        
        if (empty($options)) return array('results' => array(), 'done' => true);
        
        foreach ($options as $option) {
            if (is_string($option->option_value)) {
                $links = $this->extract_links($option->option_value);
                foreach ($links as $link) {
                    $results[] = array(
                        'type' => 'option',
                        'id' => $option->option_name,
                        'title' => 'Option: ' . $option->option_name,
                        'url' => $link['url'],
                        'link_text' => $link['text'],
                        'tag' => $link['tag'],
                        'attribute' => $link['attribute'],
                        'location' => 'Value',
                        'edit_url' => admin_url('options.php'),
                        'view_url' => home_url()
                    );
                }
            }
        }
        
        return array('results' => $results, 'done' => count($options) < $per_page);
    }
    
    /**
     * Format result helper
     */
    private function format_result($link, $type, $id, $title, $location) {
        return array(
            'type' => $type,
            'id' => $id,
            'title' => $title,
            'url' => $link['url'],
            'link_text' => isset($link['text']) ? $link['text'] : '',
            'tag' => isset($link['tag']) ? $link['tag'] : 'a',
            'attribute' => isset($link['attribute']) ? $link['attribute'] : 'href',
            'location' => $location,
            'edit_url' => get_edit_post_link($id, 'raw'),
            'view_url' => get_permalink($id)
        );
    }
}