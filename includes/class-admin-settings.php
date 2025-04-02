<?php
declare(strict_types=1);

namespace Just_Duplicate;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles the admin settings page for Just Duplicate.
 */
class Admin_Settings {

    /**
     * Render the Help & Support page.
     *
     * @return void
     */
    public static function render_help_page(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Help & Support', 'just-duplicate' ); ?></h1>
            <p><?php esc_html_e( 'Welcome to the Help and Support section for the Just Duplicate plugin.', 'just-duplicate' ); ?></p>
            <p><?php esc_html_e( 'This plugin supports duplicating layouts created with Elementor, Divi, and other major page builders.', 'just-duplicate' ); ?></p>
            <p><?php esc_html_e( 'Ensure Elementor is active to duplicate and edit Elementor pages properly.', 'just-duplicate' ); ?></p>
        </div>
        <?php
    }

    /**
     * Add a duplicate button to the Classic editor.
     */
    public static function add_classic_editor_button(): void {
        global $post;
        if ( $post ) {
            $nonce = wp_create_nonce( 'duplicate_post_' . $post->ID );
            $url   = admin_url( 'admin.php?action=duplicate_post&post=' . $post->ID . '&_wpnonce=' . $nonce );
            echo '<div class="misc-pub-section">';
            echo '<a href="' . esc_url( $url ) . '" class="button">' . esc_html__( 'Duplicate This', 'just-duplicate' ) . '</a>';
            echo '</div>';
        }
    }

    /**
     * Add a duplicate button to the Gutenberg editor.
     */
    public static function add_gutenberg_editor_button(): void {
        global $post;
        if ( $post ) {
            $nonce = wp_create_nonce( 'duplicate_post_' . $post->ID );
            $url   = admin_url( 'admin.php?action=duplicate_post&post=' . $post->ID . '&_wpnonce=' . $nonce );
            wp_enqueue_script(
                'just-duplicate-gutenberg',
                JUST_DUPLICATE_URL . 'assets/js/gutenberg-duplicate.js',
                [ 'wp-edit-post', 'wp-plugins', 'wp-element' ],
                JUST_DUPLICATE_VERSION,
                true
            );
            wp_localize_script( 'just-duplicate-gutenberg', 'JustDuplicate', [ 'url' => $url ] );
        }
    }

    /**
     * Initialize the Admin Settings functionality.
     *
     * @return void
     */
    public static function init(): void {
        add_action( 'post_submitbox_misc_actions', [ __CLASS__, 'add_classic_editor_button' ] );
        add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'add_gutenberg_editor_button' ] );
    }
}

Admin_Settings::init();
