<?php
/**
 * Admin Controller Class
 * Manages admin pages, assets, and AJAX handlers.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Hupuna_External_Link_Scanner_Admin {
    
    private $scanner;
    
    public function __construct() {
        $this->scanner = new Hupuna_External_Link_Scanner();
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_hupuna_scan_batch', array($this, 'ajax_scan_batch'));
    }
    
    /**
     * Register Admin Menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Hupuna Link Scanner',
            'Link Scanner',
            'manage_options',
            'hupuna-scan-links',
            array($this, 'render_admin_page'),
            'dashicons-admin-links',
            30
        );
    }
    
    /**
     * Enqueue Assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_hupuna-scan-links') return;
        
        wp_enqueue_style('hupuna-els-admin', HUPUNA_ELS_PLUGIN_URL . 'assets/css/admin.css', array(), HUPUNA_ELS_VERSION);
        wp_enqueue_script('hupuna-els-admin', HUPUNA_ELS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), HUPUNA_ELS_VERSION, true);
        
        wp_localize_script('hupuna-els-admin', 'hupunaEls', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hupuna_scan_links_nonce'),
            'postTypes' => $this->scanner->get_scannable_post_types()
        ));
    }
    
    /**
     * AJAX Handler for Batch Scanning
     */
    public function ajax_scan_batch() {
        check_ajax_referer('hupuna_scan_links_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Access denied'));
        }
        
        // Prevent timeout
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $step = isset($_POST['step']) ? sanitize_text_field($_POST['step']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $sub_step = isset($_POST['sub_step']) ? sanitize_text_field($_POST['sub_step']) : '';
        
        $response = array(
            'results' => array(),
            'done' => false
        );
        
        switch ($step) {
            case 'post_type':
                if ($sub_step) {
                    $scan_data = $this->scanner->scan_post_type_batch($sub_step, $page, 20);
                    $response['results'] = $scan_data['results'];
                    $response['done'] = $scan_data['done'];
                } else {
                    $response['done'] = true;
                }
                break;
                
            case 'comment':
                $scan_data = $this->scanner->scan_comments_batch($page, 50);
                $response['results'] = $scan_data['results'];
                $response['done'] = $scan_data['done'];
                break;
                
            case 'option':
                $scan_data = $this->scanner->scan_options_batch($page, 100);
                $response['results'] = $scan_data['results'];
                $response['done'] = $scan_data['done'];
                break;
                
            default:
                $response['done'] = true;
                break;
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Render Admin Interface
     */
    public function render_admin_page() {
        ?>
        <div class="wrap hupuna-els-wrap">
            <h1>Hupuna External Link Scanner</h1>
            
            <div class="hupuna-els-header">
                <p><strong>Current Domain:</strong> <code><?php echo esc_html(home_url()); ?></code></p>
                <p class="description">Scans posts, pages, comments, and options for external links. System domains (WordPress, WooCommerce, Gravatar) and patterns are automatically ignored.</p>
            </div>
            
            <div class="hupuna-els-actions">
                <button type="button" id="hupuna-scan-button" class="button button-primary button-large">
                    <span class="dashicons dashicons-search"></span> Start Scan
                </button>
                
                <div id="hupuna-progress-wrap" style="display:none;">
                    <div class="hupuna-progress-bar"><div class="hupuna-progress-fill" style="width: 0%"></div></div>
                    <div id="hupuna-progress-text">Initializing...</div>
                </div>
            </div>
            
            <div id="hupuna-scan-results" class="hupuna-scan-results" style="display: none;">
                <div class="hupuna-results-summary">
                    <p><strong>Total Links Found:</strong> <span id="total-links">0</span> | <strong>Unique URLs:</strong> <span id="unique-links">0</span></p>
                </div>
                
                <div class="hupuna-results-tabs">
                    <button class="tab-button active" data-tab="grouped">Grouped by URL</button>
                    <button class="tab-button" data-tab="all">All Occurrences</button>
                </div>
                
                <div id="hupuna-results-content" class="hupuna-results-content"></div>
            </div>
        </div>
        <?php
    }
}