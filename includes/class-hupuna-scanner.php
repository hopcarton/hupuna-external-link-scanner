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
     * Trích xuất các link từ nội dung
     */
    private function extract_links($content) {
        $links = array();
        
        // Pattern để tìm thẻ <a>
        preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $url = $match[1];
            $text = strip_tags($match[2]);
            
            // Bỏ qua URL rỗng, anchor, mailto, tel, javascript
            if (empty($url) || 
                strpos($url, '#') === 0 || 
                strpos($url, 'mailto:') === 0 || 
                strpos($url, 'tel:') === 0 ||
                strpos($url, 'javascript:') === 0) {
                continue;
            }
            
            // Chuyển relative URL thành absolute
            if (strpos($url, 'http') !== 0) {
                $url = home_url($url);
            }
            
            // Chỉ lấy external links
            if ($this->is_external_url($url)) {
                $links[] = array(
                    'url' => $url,
                    'text' => $text,
                    'full_match' => $match[0]
                );
            }
        }
        
        return $links;
    }
    
    /**
     * Quét tất cả posts
     */
    public function scan_posts() {
        $results = array();
        
        $args = array(
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        
        $posts = get_posts($args);
        
        foreach ($posts as $post_id) {
            $post = get_post($post_id);
            $content = $post->post_content;
            $links = $this->extract_links($content);
            
            if (!empty($links)) {
                foreach ($links as $link) {
                    $results[] = array(
                        'type' => 'post',
                        'id' => $post_id,
                        'title' => $post->post_title,
                        'url' => $link['url'],
                        'link_text' => $link['text'],
                        'edit_url' => get_edit_post_link($post_id, 'raw'),
                        'view_url' => get_permalink($post_id)
                    );
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Quét tất cả pages
     */
    public function scan_pages() {
        $results = array();
        
        $args = array(
            'post_type' => 'page',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        
        $pages = get_posts($args);
        
        foreach ($pages as $page_id) {
            $page = get_post($page_id);
            $content = $page->post_content;
            $links = $this->extract_links($content);
            
            if (!empty($links)) {
                foreach ($links as $link) {
                    $results[] = array(
                        'type' => 'page',
                        'id' => $page_id,
                        'title' => $page->post_title,
                        'url' => $link['url'],
                        'link_text' => $link['text'],
                        'edit_url' => get_edit_post_link($page_id, 'raw'),
                        'view_url' => get_permalink($page_id)
                    );
                }
            }
        }
        
        return $results;
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
        
        $widgets = get_option('widget_text');
        
        if (is_array($widgets)) {
            foreach ($widgets as $widget_id => $widget) {
                if (isset($widget['text']) && !empty($widget['text'])) {
                    $content = $widget['text'];
                    $links = $this->extract_links($content);
                    
                    if (!empty($links)) {
                        foreach ($links as $link) {
                            $results[] = array(
                                'type' => 'widget',
                                'id' => $widget_id,
                                'title' => sprintf('Widget: Text #%s', $widget_id),
                                'url' => $link['url'],
                                'link_text' => $link['text'],
                                'edit_url' => admin_url('widgets.php'),
                                'view_url' => home_url()
                            );
                        }
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Quét tất cả nội dung
     */
    public function scan_all() {
        $results = array();
        
        $results = array_merge($results, $this->scan_posts());
        $results = array_merge($results, $this->scan_pages());
        $results = array_merge($results, $this->scan_comments());
        $results = array_merge($results, $this->scan_widgets());
        
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

