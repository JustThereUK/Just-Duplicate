<?php
/**
 * Plugin Name: Just Duplicate
 * Plugin URI: https://wordpress.org/plugins/just-duplicate
 * Description: A powerful plugin to duplicate pages, posts, custom post types, WooCommerce products, menus, and more. Supports batch duplication, customizable options, and compatibility with major plugins and themes.
 * Version: 1.0.5
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.0
 * Author: Just There
 * Author URI: https://justthere.co.uk/
 * Support Us: https://justthere.co.uk/plugins/support-us/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: just-duplicate
 * Domain Path: /languages
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'JUST_DUPLICATE_VERSION', '1.0.5' );
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
 * Assets are loaded only on the plugin's settings page and post/page list screens.
 *
 * @param string $hook_suffix The current admin page's hook suffix.
 */
function just_duplicate_enqueue_admin_assets( $hook_suffix ) {
    $allowed_pages = [
        'toplevel_page_just-duplicate-settings',
        'edit.php',
        'upload.php',
        'nav-menus.php',
    ];

    if ( in_array( $hook_suffix, $allowed_pages, true ) ) {
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
}
add_action( 'admin_enqueue_scripts', 'just_duplicate_enqueue_admin_assets' );

/**
 * Add a "Support Us" link to the plugin's action links on the plugins page.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function just_duplicate_add_support_us_link( array $links ): array {
    $support_link = '<a href="https://justthere.co.uk/plugins/support-us/" style="color: red;" target="_blank">' . esc_html__( 'Support Us', 'just-duplicate' ) . '</a>';
    array_unshift( $links, $support_link ); // Add the link to the beginning of the array.
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'just_duplicate_add_support_us_link' );
