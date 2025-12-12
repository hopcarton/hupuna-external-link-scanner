<?php
/**
 * Plugin Name: Hupuna External Link Scanner
 * Description: Scan entire website content, filter external links that differ from current domain, display location and view button.
 * Version: 1.0.0
 * Author: Mai Sỹ Đạt
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hupuna-external-link-scanner
 * Domain Path: /languages
 */

// Kiểm tra truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

// Định nghĩa các hằng số plugin
define('HUPUNA_ELS_VERSION', '1.0.0');
define('HUPUNA_ELS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HUPUNA_ELS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HUPUNA_ELS_PLUGIN_FILE', __FILE__);

// Nạp các class chính
require_once HUPUNA_ELS_PLUGIN_DIR . 'includes/class-hupuna-scanner.php';
require_once HUPUNA_ELS_PLUGIN_DIR . 'includes/class-hupuna-admin.php';

/**
 * Khởi tạo plugin
 */
function hupuna_els_init() {
    $admin = new Hupuna_External_Link_Scanner_Admin();
    $admin->init();
}
add_action('plugins_loaded', 'hupuna_els_init');

/**
 * Hook kích hoạt plugin
 */
register_activation_hook(__FILE__, 'hupuna_els_activate');
function hupuna_els_activate() {
    // Làm mới rewrite rules nếu cần
    flush_rewrite_rules();
}

/**
 * Hook vô hiệu hóa plugin
 */
register_deactivation_hook(__FILE__, 'hupuna_els_deactivate');
function hupuna_els_deactivate() {
    // Dọn dẹp nếu cần
}

