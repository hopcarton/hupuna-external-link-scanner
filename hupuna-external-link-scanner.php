<?php
/**
 * Plugin Name: Hupuna External Link Scanner
 * Description: Scans the entire website content for external links. Optimized for large databases with batch processing.
 * Version: 2.0.0
 * Author: Mai Sy Dat
 * Text Domain: hupuna-external-link-scanner
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HUPUNA_ELS_VERSION', '2.0.0');
define('HUPUNA_ELS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HUPUNA_ELS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load core classes
require_once HUPUNA_ELS_PLUGIN_DIR . 'includes/class-hupuna-scanner.php';
require_once HUPUNA_ELS_PLUGIN_DIR . 'includes/class-hupuna-admin.php';

/**
 * Initialize the plugin
 */
function hupuna_els_init() {
    $admin = new Hupuna_External_Link_Scanner_Admin();
    $admin->init();
}
add_action('plugins_loaded', 'hupuna_els_init');