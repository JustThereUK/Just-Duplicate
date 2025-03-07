<?php
declare(strict_types=1);

namespace Just_Duplicate\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Media_Duplicator
 *
 * Provides functionality to duplicate media attachments.
 */
class Media_Duplicator {

    /**
     * Initialize the Media Duplicator.
     */
    public static function init(): void {
        // Add duplicate link in list view.
        add_filter( 'media_row_actions', [ __CLASS__, 'add_duplicate_media_link' ], 10, 2 );
        // Add duplicate button to attachment edit (grid view) using the attachment_fields_to_edit filter.
        add_filter( 'attachment_fields_to_edit', [ __CLASS__, 'add_duplicate_media_field' ], 10, 2 );
        // Handle the AJAX duplication action for media.
        add_action( 'wp_ajax_duplicate_media', [ __CLASS__, 'handle_duplicate_media' ] );
    }

    /**
     * Add a duplicate link to the media row actions.
     *
     * @param array    $actions Existing row actions.
     * @param \WP_Post $post    The media attachment post.
     * @return array Modified row actions.
     */
    public static function add_duplicate_media_link( array $actions, \WP_Post $post ): array {
        if ( current_user_can( 'upload_files' ) ) {
            $url = wp_nonce_url(
                add_query_arg(
                    [
                        'action' => 'duplicate_media',
                        'media'  => $post->ID,
                    ],
                    admin_url( 'admin-ajax.php' )
                ),
                'duplicate_media_' . $post->ID
            );
            $actions['duplicate_media'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Duplicate', 'just-duplicate' ) . '</a>';
        }
        return $actions;
    }

    /**
     * Add a duplicate button to the attachment edit form (grid view).
     *
     * @param array   $form_fields The existing form fields.
     * @param \WP_Post $post       The attachment post.
     * @return array Modified form fields.
     */
    public static function add_duplicate_media_field( array $form_fields, \WP_Post $post ): array {
        if ( current_user_can( 'upload_files' ) ) {
            $nonce = wp_create_nonce( 'duplicate_media_' . $post->ID );
            $duplicate_url = wp_nonce_url(
                add_query_arg(
                    [
                        'action' => 'duplicate_media',
                        'media'  => $post->ID,
                    ],
                    admin_url( 'admin-ajax.php' )
                ),
                'duplicate_media_' . $post->ID
            );
            $form_fields['duplicate_media'] = [
                'label' => __( 'Duplicate Media', 'just-duplicate' ),
                'input' => 'html',
                'html'  => '<a href="' . esc_url( $duplicate_url ) . '" class="button">' . __( 'Duplicate Media', 'just-duplicate' ) . '</a>',
            ];
        }
        return $form_fields;
    }

    /**
     * Handle the duplication of a media attachment.
     */
    public static function handle_duplicate_media(): void {
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_die( esc_html__( 'Permission denied.', 'just-duplicate' ) );
        }

        if ( ! isset( $_GET['_wpnonce'], $_GET['media'] ) ) {
            wp_die( esc_html__( 'Missing parameters.', 'just-duplicate' ) );
        }

        $media_id = absint( $_GET['media'] );
        $nonce    = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

        if ( ! wp_verify_nonce( $nonce, 'duplicate_media_' . $media_id ) ) {
            wp_die( esc_html__( 'Nonce verification failed.', 'just-duplicate' ) );
        }

        // Use the duplicate_attachment method from Duplicate_Handler.
        $new_media_id = \Just_Duplicate\Duplicate_Handler::duplicate_attachment( $media_id, 0 );
        if ( ! $new_media_id ) {
            wp_die( esc_html__( 'Failed to duplicate media.', 'just-duplicate' ) );
        }

        // Redirect back to the media library.
        wp_redirect( admin_url( 'upload.php?duplicated=' . $new_media_id ) );
        exit;
    }
}
