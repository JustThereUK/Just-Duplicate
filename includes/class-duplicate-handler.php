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

    private static $last_duplicated_post_id;

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

        // Hook to display the rollback notice.
        add_action( 'admin_notices', [ __CLASS__, 'add_rollback_notice' ] );

        // Hook to handle the rollback action.
        add_action( 'admin_action_rollback_duplicate', [ __CLASS__, 'handle_rollback_action' ] );
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
     * Process the duplication of a post or page with role-based access control.
     */
    public static function process_duplication(): void {
        // Verify nonce.
        if ( ! isset( $_GET['_wpnonce'], $_GET['post'] ) ) {
            wp_die( esc_html__( 'Missing required parameters.', 'just-duplicate' ) );
        }

        $post_id = absint( $_GET['post'] );
        $nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'duplicate_post_' . $post_id ) ) {
            wp_die( esc_html__( 'Nonce verification failed.', 'just-duplicate' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_die( esc_html__( 'The post you are trying to duplicate does not exist.', 'just-duplicate' ) );
        }

        // Role-based access control.
        $current_user_id = get_current_user_id();
        if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'edit_others_posts' ) || 
                 ( current_user_can( 'edit_posts' ) && $current_user_id === $post->post_author ) ) ) {
            wp_die( esc_html__( 'You do not have permission to duplicate this post.', 'just-duplicate' ) );
        }

        // Duplicate the post.
        $new_post_id = self::duplicate_post( $post_id );

        if ( $new_post_id ) {
            // Redirect back to the referring page.
            $referer = wp_get_referer();
            $redirect_url = $referer ? $referer : admin_url( 'edit.php' );
            wp_redirect( $redirect_url );
            exit;
        } else {
            wp_die( esc_html__( 'Failed to duplicate the post.', 'just-duplicate' ) );
        }
    }

    /**
     * Duplicate a post or page.
     *
     * @param int $post_id The ID of the post to duplicate.
     * @return int|null The ID of the duplicated post, or null on failure.
     */
    public static function duplicate_post( int $post_id ): ?int {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return null;
        }

        // Trigger the before duplicate hook.
        do_action( 'just_duplicate_before_duplicate', $post_id );

        $settings = get_option( 'JUST_DUPLICATE_settings', [] );

        // Prepare the new post data.
        $new_post = [
            'post_title'   => $settings['custom_title'] ?: $post->post_title . ' (Copy)',
            'post_name'    => $settings['custom_slug'] ?: '',
            'post_content' => $post->post_content,
            'post_status'  => $settings['custom_post_status'] ?? 'draft',
            'post_type'    => $post->post_type,
            'post_author'  => get_current_user_id(),
            'post_excerpt' => $post->post_excerpt,
            'post_parent'  => $post->post_parent,
        ];

        // Insert the new post.
        $new_post_id = wp_insert_post( $new_post );
        if ( is_wp_error( $new_post_id ) || ! $new_post_id ) {
            return null;
        }

        // Copy metadata, taxonomies, and other related data.
        self::copy_post_meta( $post_id, $new_post_id );
        self::copy_post_taxonomies( $post_id, $new_post_id );

        // If the original post has a featured image, duplicate it.
        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( $thumb_id ) {
            $new_thumb_id = self::duplicate_attachment( $thumb_id, $new_post_id );
            if ( $new_thumb_id ) {
                set_post_thumbnail( $new_post_id, $new_thumb_id );
            }
        }

        // Store the last duplicated post ID.
        self::$last_duplicated_post_id = $new_post_id;

        // Store the last duplicated post ID in a transient.
        if ( $new_post_id ) {
            set_transient( 'just_duplicate_last_post_id', $new_post_id, HOUR_IN_SECONDS );
        }

        // Trigger the after duplicate hook.
        do_action( 'just_duplicate_after_duplicate', $post_id, $new_post_id );

        return $new_post_id;
    }

    /**
     * Get the last duplicated post ID.
     *
     * @return int|null The ID of the last duplicated post, or null if none.
     */
    public static function get_last_duplicated_post_id(): ?int {
        return self::$last_duplicated_post_id;
    }

    /**
     * Rollback the last duplicated post.
     *
     * @return void
     */
    public static function rollback_last_duplicate(): void {
        $last_post_id = get_transient( 'just_duplicate_last_post_id' );

        if ( $last_post_id ) {
            wp_delete_post( $last_post_id, true );
            delete_transient( 'just_duplicate_last_post_id' );
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'The last duplicated post has been rolled back.', 'just-duplicate' ) . '</p></div>';
            } );
        } else {
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'No duplicated post found to rollback.', 'just-duplicate' ) . '</p></div>';
            } );
        }
    }

    /**
     * Add an admin notice with a rollback link after duplication.
     */
    public static function add_rollback_notice(): void {
        $last_post_id = get_transient( 'just_duplicate_last_post_id' );

        if ( $last_post_id ) {
            $rollback_url = add_query_arg(
                [
                    'action' => 'rollback_duplicate',
                    '_wpnonce' => wp_create_nonce( 'rollback_duplicate' ),
                ],
                admin_url( 'admin.php' )
            );

            echo '<div class="notice notice-info is-dismissible"><p>' .
                esc_html__( 'A post has been duplicated. ', 'just-duplicate' ) .
                '<a href="' . esc_url( $rollback_url ) . '">' . esc_html__( 'Undo this action.', 'just-duplicate' ) . '</a>' .
                '</p></div>';
        }
    }

    /**
     * Handle the rollback action.
     */
    public static function handle_rollback_action(): void {
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rollback_duplicate' ) ) {
            wp_die( esc_html__( 'Nonce verification failed.', 'just-duplicate' ) );
        }

        self::rollback_last_duplicate();
        wp_redirect( admin_url( 'edit.php' ) );
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

            // Only copy Elementor-specific meta keys if the original post was edited in Elementor.
            if ( in_array( $key, [ '_elementor_edit_mode', '_elementor_data', '_elementor_template_type', '_elementor_version' ], true ) ) {
                $is_elementor = get_post_meta( $old_post_id, '_elementor_edit_mode', true );
                if ( ! $is_elementor ) {
                    continue;
                }
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
     * Copy custom fields from the original post to the duplicated post.
     *
     * @param int $old_post_id Original post ID.
     * @param int $new_post_id New post ID.
     * @return void
     */
    private static function copy_custom_fields( int $old_post_id, int $new_post_id ): void {
        $meta_data = get_post_meta( $old_post_id );

        foreach ( $meta_data as $key => $values ) {
            foreach ( $values as $value ) {
                add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
            }
        }
    }

    /**
     * Copy custom taxonomies from the original post to the duplicated post.
     *
     * @param int $old_post_id Original post ID.
     * @param int $new_post_id New post ID.
     * @return void
     */
    private static function copy_custom_taxonomies( int $old_post_id, int $new_post_id ): void {
        $taxonomies = get_object_taxonomies( get_post_type( $old_post_id ) );

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
        // Verify the AJAX nonce.
        check_ajax_referer( 'preview_duplicate_post', '_wpnonce' );

        // Check permissions.
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( esc_html__( 'Permission denied.', 'just-duplicate' ) );
        }
        
        $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( esc_html__( 'Post not found.', 'just-duplicate' ) );
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
            'title'        => esc_html( $default_prefix . $post->post_title . $default_suffix ),
            'content'      => wp_kses_post( apply_filters( 'the_content', $post->post_content ) ),
            'excerpt'      => esc_html( $post->post_excerpt ),
            'author'       => esc_html( get_the_author_meta( 'display_name', $post->post_author ) ),
            'date'         => esc_html( $post->post_date ),
            'duplicate_url'=> esc_url( wp_nonce_url(
                add_query_arg(
                    [
                        'action' => 'duplicate_post',
                        'post'   => $post->ID,
                    ],
                    admin_url( 'admin.php' )
                ),
                'duplicate_post_' . $post->ID
            ) ),
        ];

        wp_send_json_success( $preview_data );
    }

    /**
     * Get selective duplication options from settings.
     *
     * @return array
     */
    private static function get_selective_duplication_options(): array {
        $settings = get_option( 'JUST_DUPLICATE_settings', [] );
        return [
            'duplicate_post_meta'      => ! empty( $settings['duplicate_post_meta'] ),
            'duplicate_taxonomies'     => ! empty( $settings['duplicate_taxonomies'] ),
            'duplicate_attachments'    => ! empty( $settings['duplicate_attachments'] ),
            'duplicate_custom_fields'  => ! empty( $settings['duplicate_custom_fields'] ),
            'duplicate_custom_taxonomies' => ! empty( $settings['duplicate_custom_taxonomies'] ),
            'duplicate_comments'       => ! empty( $settings['duplicate_comments'] ),
            'duplicate_featured_image' => ! empty( $settings['duplicate_featured_image'] ),
        ];
    }

    /**
     * Copy comments from the original post to the duplicated post.
     *
     * @param int $old_post_id Original post ID.
     * @param int $new_post_id New post ID.
     * @return void
     */
    private static function copy_comments( int $old_post_id, int $new_post_id ): void {
        $comments = get_comments( [ 'post_id' => $old_post_id ] );
        foreach ( $comments as $comment ) {
            $new_comment = [
                'comment_post_ID'      => $new_post_id,
                'comment_author'       => $comment->comment_author,
                'comment_author_email' => $comment->comment_author_email,
                'comment_author_url'   => $comment->comment_author_url,
                'comment_content'      => $comment->comment_content,
                'comment_type'         => $comment->comment_type,
                'comment_parent'       => $comment->comment_parent,
                'user_id'              => $comment->user_id,
            ];
            wp_insert_comment( $new_comment );
        }
    }
}
