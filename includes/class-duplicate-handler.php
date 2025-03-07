<?php
declare(strict_types=1);

namespace Just_Duplicate;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles the duplication logic for posts, pages, and custom post types.
 */
class Duplicate_Handler {

    /**
     * Initialize the duplication handler.
     *
     * Adds filters for displaying duplicate links and registers the duplicate action.
     *
     * @return void
     */
    public static function init(): void {
        // Add duplicate action links for posts and pages.
        add_filter( 'post_row_actions', [ __CLASS__, 'add_duplicate_link' ], 10, 2 );
        add_filter( 'page_row_actions', [ __CLASS__, 'add_duplicate_link' ], 10, 2 );

        // Handle the duplication action.
        add_action( 'admin_action_duplicate_post', [ __CLASS__, 'process_duplication' ] );
    }

    /**
     * Add a "Duplicate" link and a "Preview Duplicate" link to the post and page row actions.
     *
     * @param array    $actions Array of row action links.
     * @param \WP_Post $post    The current post object.
     * @return array Modified array of row action links.
     */
    public static function add_duplicate_link( array $actions, \WP_Post $post ): array {
        if ( current_user_can( 'edit_posts', $post->ID ) ) {
            // Build duplicate URL.
            $duplicate_url = wp_nonce_url(
                add_query_arg(
                    [
                        'action' => 'duplicate_post',
                        'post'   => $post->ID,
                    ],
                    admin_url( 'admin.php' )
                ),
                'duplicate_post_' . $post->ID
            );
            // Build preview URL for AJAX preview.
            $preview_url = wp_nonce_url(
                add_query_arg(
                    [
                        'action' => 'preview_duplicate_post',
                        'post'   => $post->ID,
                    ],
                    admin_url( 'admin-ajax.php' )
                ),
                'preview_duplicate_post_' . $post->ID
            );
            $actions['duplicate'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url( $duplicate_url ),
                esc_html__( 'Duplicate', 'just-duplicate' )
            );
            $actions['preview_duplicate'] = sprintf(
                '<a href="#" class="preview-duplicate" data-preview-url="%s">%s</a>',
                esc_url( $preview_url ),
                esc_html__( 'Preview Duplicate', 'just-duplicate' )
            );
        }

        return $actions;
    }

    /**
     * Process the duplication of a post or page.
     *
     * Validates permissions, duplicates the post along with its meta, taxonomies, and attachments (if enabled),
     * and then conditionally redirects based on the "redirect_after_duplicate" setting.
     *
     * @return void
     */
    public static function process_duplication(): void {
        // Verify required parameters.
        if ( ! isset( $_GET['_wpnonce'], $_GET['post'] ) ) {
            wp_die( esc_html__( 'Missing required parameters.', 'just-duplicate' ) );
        }

        $post_id = absint( $_GET['post'] );
        $nonce   = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

        if ( ! wp_verify_nonce( $nonce, 'duplicate_post_' . $post_id ) ) {
            wp_die( esc_html__( 'You do not have permission to duplicate this item.', 'just-duplicate' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_die( esc_html__( 'The post you are trying to duplicate does not exist.', 'just-duplicate' ) );
        }

        // Additional permission check.
        if ( ! current_user_can( 'edit_posts', $post_id ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to duplicate this post.', 'just-duplicate' ) );
        }

        // Prepare the duplicated post.
        $new_post_data = [
            'post_title'   => $post->post_title . ' (Copy)',
            'post_content' => $post->post_content,
            'post_status'  => 'draft',
            'post_type'    => $post->post_type,
            'post_author'  => get_current_user_id(),
            'post_excerpt' => $post->post_excerpt,
            'post_parent'  => $post->post_parent,
        ];

        // Insert the duplicated post.
        $new_post_id = wp_insert_post( $new_post_data );
        if ( is_wp_error( $new_post_id ) || ! $new_post_id ) {
            wp_die( esc_html__( 'Failed to duplicate the post.', 'just-duplicate' ) );
        }

        // Retrieve plugin settings.
        $settings = get_option( 'JUST_DUPLICATE_settings', [] );

        // Conditionally copy post meta.
        if ( ! empty( $settings['duplicate_post_meta'] ) ) {
            self::copy_post_meta( $post_id, $new_post_id );
        }
        // Conditionally copy taxonomies.
        if ( ! empty( $settings['duplicate_taxonomies'] ) ) {
            self::copy_post_taxonomies( $post_id, $new_post_id );
        }
        // Conditionally duplicate attachments (e.g., featured image).
        if ( ! empty( $settings['duplicate_attachments'] ) ) {
            $thumb_id = get_post_thumbnail_id( $post_id );
            if ( $thumb_id ) {
                $new_thumb_id = self::duplicate_attachment( $thumb_id, $new_post_id );
                if ( $new_thumb_id ) {
                    set_post_thumbnail( $new_post_id, $new_thumb_id );
                }
            }
        }

        // Check the "redirect_after_duplicate" setting.
        if ( ! empty( $settings['redirect_after_duplicate'] ) ) {
            // Redirect to the edit screen of the new post.
            wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
        } else {
            // Redirect back to the referring page (fallback to the posts list if no referer).
            $redirect_url = wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php' );
            // Optionally, add a query parameter to indicate duplication success.
            $redirect_url = add_query_arg( 'duplicated', $new_post_id, $redirect_url );
            wp_redirect( $redirect_url );
        }
        exit;
    }

    /**
     * Copy metadata from the original post to the duplicated post.
     *
     * Certain internal meta keys (e.g., _edit_lock, _edit_last) are excluded.
     *
     * @param int $old_post_id Original post ID.
     * @param int $new_post_id New post ID.
     * @return void
     */
    private static function copy_post_meta( int $old_post_id, int $new_post_id ): void {
        $meta_data = get_post_meta( $old_post_id );

        // Meta keys to exclude.
        $exclude_keys = [
            '_edit_lock',
            '_edit_last',
        ];

        foreach ( $meta_data as $key => $values ) {
            if ( in_array( $key, $exclude_keys, true ) ) {
                continue;
            }
            foreach ( $values as $value ) {
                add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
            }
        }
    }

    /**
     * Duplicate an attachment (e.g., featured image) and attach it to the new post.
     *
     * Copies the file, creates a new attachment post, and generates attachment metadata.
     *
     * @param int $attachment_id The original attachment ID.
     * @param int $new_parent_id The new parent post ID.
     * @return int|null The new attachment ID on success, or null on failure.
     */
    public static function duplicate_attachment( int $attachment_id, int $new_parent_id ): ?int {
        $file = get_attached_file( $attachment_id );
        if ( ! file_exists( $file ) ) {
            return null;
        }
        $upload_dir = wp_upload_dir();

        // Create a new filename by appending "-copy" before the extension.
        $file_info    = pathinfo( $file );
        $new_filename = $file_info['filename'] . '-copy.' . $file_info['extension'];
        $new_file     = $file_info['dirname'] . '/' . $new_filename;

        if ( ! copy( $file, $new_file ) ) {
            return null;
        }

        // Get file type information.
        $filetype = wp_check_filetype( basename( $new_file ), null );

        // Prepare attachment data.
        $attachment_data = [
            'guid'           => $upload_dir['url'] . '/' . basename( $new_file ),
            'post_mime_type' => $filetype['type'],
            'post_title'     => get_the_title( $attachment_id ) . ' (Copy)',
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_parent'    => $new_parent_id,
        ];
        $new_attachment_id = wp_insert_attachment( $attachment_data, $new_file, $new_parent_id );
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
        }
        $attach_data = wp_generate_attachment_metadata( $new_attachment_id, $new_file );
        wp_update_attachment_metadata( $new_attachment_id, $attach_data );
        return $new_attachment_id;
    }

    /**
     * Copy taxonomies from the original post to the duplicated post.
     *
     * Copies all taxonomies (terms) associated with the original post.
     *
     * @param int $old_post_id Original post ID.
     * @param int $new_post_id New post ID.
     * @return void
     */
    private static function copy_post_taxonomies( int $old_post_id, int $new_post_id ): void {
        $post_type  = get_post_type( $old_post_id );
        $taxonomies = get_object_taxonomies( $post_type );

        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $old_post_id, $taxonomy, [ 'fields' => 'slugs' ] );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                wp_set_object_terms( $new_post_id, $terms, $taxonomy );
            }
        }
    }

    /**
     * Preview the duplication of a post.
     *
     * This method is called via AJAX and returns a JSON response containing a preview
     * of the duplicated post (including title, content, author, date, and the duplicate action URL).
     *
     * @return void
     */
    public static function preview_duplicate(): void {
        // Check permissions.
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'just-duplicate' ) );
        }
        
        $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
        $nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'preview_duplicate_post_' . $post_id ) ) {
            wp_send_json_error( __( 'Nonce verification failed.', 'just-duplicate' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( __( 'Post not found.', 'just-duplicate' ) );
        }

        // Retrieve plugin settings.
        $settings = get_option( 'JUST_DUPLICATE_settings', [] );
        $default_prefix = isset( $settings['default_prefix'] ) ? (string) $settings['default_prefix'] : '';
        $default_suffix = ( isset( $settings['default_suffix'] ) && '' !== $settings['default_suffix'] )
            ? (string) $settings['default_suffix']
            : ' (Copy)';

        // Build preview data.
        $preview_data = [
            'post_id'      => $post->ID,
            'title'        => $default_prefix . $post->post_title . $default_suffix,
            'content'      => apply_filters( 'the_content', $post->post_content ),
            'excerpt'      => $post->post_excerpt,
            'author'       => get_the_author_meta( 'display_name', $post->post_author ),
            'date'         => $post->post_date,
            'duplicate_url'=> wp_nonce_url(
                                add_query_arg(
                                    [
                                        'action' => 'duplicate_post',
                                        'post'   => $post->ID,
                                    ],
                                    admin_url( 'admin.php' )
                                ),
                                'duplicate_post_' . $post->ID
                              ),
        ];

        wp_send_json_success( $preview_data );
    }
}
