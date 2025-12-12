<?php
/**
 * Class Admin - Quản lý giao diện và chức năng admin
 * 
 * @package Hupuna_External_Link_Scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

class Hupuna_External_Link_Scanner_Admin {
    
    /**
     * Instance của class Scanner
     */
    private $scanner;
    
    /**
     * Constructor - Khởi tạo scanner
     */
    public function __construct() {
        $this->scanner = new Hupuna_External_Link_Scanner();
    }
    
    /**
     * Khởi tạo các hook admin
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_hupuna_scan_links', array($this, 'ajax_scan_links'));
    }
    
    /**
     * Thêm menu vào admin
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Hupuna Scan Links', 'hupuna-external-link-scanner'),
            __('Hupuna Scan Links', 'hupuna-external-link-scanner'),
            'manage_options',
            'hupuna-scan-links',
            array($this, 'render_admin_page'),
            'dashicons-admin-links',
            30
        );
    }
    
    /**
     * Nạp CSS và JS cho admin
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_hupuna-scan-links') {
            return;
        }
        
        wp_enqueue_style(
            'hupuna-els-admin',
            HUPUNA_ELS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            HUPUNA_ELS_VERSION
        );
        
        wp_enqueue_script(
            'hupuna-els-admin',
            HUPUNA_ELS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            HUPUNA_ELS_VERSION,
            true
        );
        
        wp_localize_script('hupuna-els-admin', 'hupunaEls', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hupuna_scan_links_nonce'),
            'scanning' => __('Scanning...', 'hupuna-external-link-scanner'),
            'completed' => __('Completed!', 'hupuna-external-link-scanner')
        ));
    }
    
    /**
     * Xử lý AJAX quét links
     */
    public function ajax_scan_links() {
        check_ajax_referer('hupuna_scan_links_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Access denied.', 'hupuna-external-link-scanner')));
        }
        
        $results = $this->scanner->scan_all();
        $grouped = $this->scanner->group_by_url($results);
        
        wp_send_json_success(array(
            'results' => $results,
            'grouped' => $grouped,
            'total' => count($results),
            'unique' => count($grouped)
        ));
    }
    
    /**
     * Hiển thị trang admin
     */
    public function render_admin_page() {
        ?>
        <div class="wrap hupuna-els-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="hupuna-els-header">
                <p class="description">
                    <?php _e('This plugin will scan entire website content (posts, pages, comments, widgets) to find external links that point outside the current domain.', 'hupuna-external-link-scanner'); ?>
                </p>
                <p>
                    <strong><?php _e('Current Domain:', 'hupuna-external-link-scanner'); ?></strong> 
                    <code><?php echo esc_html(home_url()); ?></code>
                </p>
            </div>
            
            <div class="hupuna-els-actions">
                <button type="button" id="hupuna-scan-button" class="button button-primary button-large">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Start Scan', 'hupuna-external-link-scanner'); ?>
                </button>
                <span id="hupuna-scan-status" class="hupuna-scan-status"></span>
            </div>
            
            <div id="hupuna-scan-results" class="hupuna-scan-results" style="display: none;">
                <div class="hupuna-results-summary">
                    <h2><?php _e('Results Summary', 'hupuna-external-link-scanner'); ?></h2>
                    <p>
                        <strong><?php _e('Total Links:', 'hupuna-external-link-scanner'); ?></strong> 
                        <span id="total-links">0</span>
                    </p>
                    <p>
                        <strong><?php _e('Unique Links:', 'hupuna-external-link-scanner'); ?></strong> 
                        <span id="unique-links">0</span>
                    </p>
                </div>
                
                <div class="hupuna-results-tabs">
                    <button class="tab-button active" data-tab="grouped"><?php _e('Grouped by URL', 'hupuna-external-link-scanner'); ?></button>
                    <button class="tab-button" data-tab="all"><?php _e('All Links', 'hupuna-external-link-scanner'); ?></button>
                </div>
                
                <div id="hupuna-results-content" class="hupuna-results-content">
                    <!-- Results will be loaded here -->
                </div>
            </div>
        </div>
        <?php
    }
}

