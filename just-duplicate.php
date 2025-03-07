<?php
/**
 * Plugin Name: Just Duplicate
 * Plugin URI: https://wordpress.org/plugins/just-duplicate
 * Description: A powerful plugin to duplicate pages, posts, custom post types, WooCommerce products, menus, and more. Supports batch duplication, customizable options, and compatibility with major plugins and themes.
 * Version: 1.0.2
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Requires PHP: 7.0
 * Author: Just There
 * Author URI: https://justthere.co.uk/
 * Support Us: https://justthere.co.uk/donate
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: just-duplicate
 * Domain Path: /languages
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'JUST_DUPLICATE_VERSION', '1.0.2' );
define( 'JUST_DUPLICATE_PATH', plugin_dir_path( __FILE__ ) );
define( 'JUST_DUPLICATE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin text domain for translations.
 */
function just_duplicate_load_textdomain() {
    load_plugin_textdomain(
        'just-duplicate',
        false,
        basename( JUST_DUPLICATE_PATH ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'just_duplicate_load_textdomain' );

/**
 * Include the loader class.
 */
$loader_file = JUST_DUPLICATE_PATH . 'includes/class-just-duplicate-loader.php';
if ( file_exists( $loader_file ) ) {
    require_once $loader_file;
}

/**
 * Initialize the plugin by instantiating the loader class.
 */
function just_duplicate_init() {
    if ( class_exists( 'Just_Duplicate\Loader' ) ) {
        \Just_Duplicate\Loader::instance();
    }
}
add_action( 'plugins_loaded', 'just_duplicate_init' );

/**
 * Register AJAX action for previewing a duplicate.
 */
add_action( 'wp_ajax_preview_duplicate_post', [ 'Just_Duplicate\Duplicate_Handler', 'preview_duplicate' ] );

/**
 * Enqueue admin assets (CSS and JS) for the Just Duplicate plugin.
 *
 * Assets are loaded on all admin pages so that the preview duplicate functionality
 * is available wherever you can create or modify a page.
 *
 * @param string $hook_suffix The current admin page's hook suffix.
 */
function just_duplicate_enqueue_admin_assets( $hook_suffix ) {
    wp_enqueue_style(
        'just-duplicate-admin-style',
        JUST_DUPLICATE_URL . 'assets/css/admin-style.css',
        [],
        JUST_DUPLICATE_VERSION
    );

    wp_enqueue_script(
        'just-duplicate-admin-script',
        JUST_DUPLICATE_URL . 'assets/js/admin-script.js',
        [ 'jquery' ],
        JUST_DUPLICATE_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'just_duplicate_enqueue_admin_assets' );
