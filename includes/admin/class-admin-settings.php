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
     * Option key for storing plugin settings.
     *
     * @var string
     */
    public const OPTION_KEY = 'JUST_DUPLICATE_settings';

    /**
     * Default settings for Just Duplicate.
     *
     * Using a class constant ensures that the default value is static.
     *
     * @var array
     */
    private const DEFAULT_SETTINGS = [
        'redirect_after_duplicate' => false,
        'default_prefix'           => '',
        'default_suffix'           => '',
        'duplicate_post_meta'      => true,
        'duplicate_taxonomies'     => true,
        'duplicate_attachments'    => false,
        'duplicate_custom_fields'  => true,
        'duplicate_custom_taxonomies' => true,
        'duplicate_comments'       => true,
        'duplicate_featured_image' => true,
    ];

    /**
     * Initialize the admin settings functionality.
     *
     * Registers the settings page, settings, and bulk actions.
     *
     * @return void
     */
    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_filter( 'bulk_actions-edit-post', [ __CLASS__, 'register_bulk_action' ] );
        add_filter( 'bulk_actions-edit-page', [ __CLASS__, 'register_bulk_action' ] );
        add_action( 'handle_bulk_actions-edit-post', [ __CLASS__, 'handle_bulk_action' ], 10, 3 );
        add_action( 'handle_bulk_actions-edit-page', [ __CLASS__, 'handle_bulk_action' ], 10, 3 );
    }

    /**
     * Add the settings page to the WordPress admin menu.
     *
     * @return void
     */
    public static function add_settings_page(): void {
        add_menu_page(
            __( 'Just Duplicate', 'just-duplicate' ),
            __( 'Just Duplicate', 'just-duplicate' ),
            'manage_options',
            'just-duplicate-settings',
            [ __CLASS__, 'render_settings_page' ],
            'dashicons-admin-page'
        );
    }

    /**
     * Register plugin settings.
     *
     * @return void
     */
    public static function register_settings(): void {
        register_setting(
            self::OPTION_KEY, // Option group name.
            self::OPTION_KEY, // Option name.
            [
                'type'              => 'array',
                'description'       => __( 'Settings for Just Duplicate', 'just-duplicate' ),
                'sanitize_callback' => [ __CLASS__, 'sanitize_settings' ], // Explicitly defined callback
                'show_in_rest'      => false,
                'default'           => self::DEFAULT_SETTINGS,
            ]
        );

        add_settings_section(
            'JUST_DUPLICATE_general',
            __( 'General Settings', 'just-duplicate' ),
            '__return_false',
            self::OPTION_KEY
        );

        // Redirect After Duplication field.
        add_settings_field(
            'redirect_after_duplicate',
            __( 'Redirect After Duplication', 'just-duplicate' ),
            [ __CLASS__, 'render_redirect_field' ],
            self::OPTION_KEY,
            'JUST_DUPLICATE_general'
        );

        // Default Prefix field.
        add_settings_field(
            'default_prefix',
            __( 'Default Prefix', 'just-duplicate' ),
            [ __CLASS__, 'render_prefix_field' ],
            self::OPTION_KEY,
            'JUST_DUPLICATE_general'
        );

        // Default Suffix field.
        add_settings_field(
            'default_suffix',
            __( 'Default Suffix', 'just-duplicate' ),
            [ __CLASS__, 'render_suffix_field' ],
            self::OPTION_KEY,
            'JUST_DUPLICATE_general'
        );

        // Duplicate Post Meta field.
        add_settings_field(
            'duplicate_post_meta',
            __( 'Duplicate Post Meta', 'just-duplicate' ),
            [ __CLASS__, 'render_duplicate_post_meta_field' ],
            self::OPTION_KEY,
            'JUST_DUPLICATE_general'
        );

        // Duplicate Taxonomies field.
        add_settings_field(
            'duplicate_taxonomies',
            __( 'Duplicate Taxonomies', 'just-duplicate' ),
            [ __CLASS__, 'render_duplicate_taxonomies_field' ],
            self::OPTION_KEY,
            'JUST_DUPLICATE_general'
        );

        // Duplicate Attachments field.
        add_settings_field(
            'duplicate_attachments',
            __( 'Duplicate Attachments', 'just-duplicate' ),
            [ __CLASS__, 'render_duplicate_attachments_field' ],
            self::OPTION_KEY,
            'JUST_DUPLICATE_general'
        );

        // Duplicate Custom Fields field.
        add_settings_field(
            'duplicate_custom_fields',
            __( 'Duplicate Custom Fields', 'just-duplicate' ),
            [ __CLASS__, 'render_duplicate_custom_fields_field' ],
            self::OPTION_KEY,
            'JUST_DUPLICATE_general'
        );

        // Duplicate Custom Taxonomies field.
        add_settings_field(
            'duplicate_custom_taxonomies',
            __( 'Duplicate Custom Taxonomies', 'just-duplicate' ),
            [ __CLASS__, 'render_duplicate_custom_taxonomies_field' ],
            self::OPTION_KEY,
            'JUST_DUPLICATE_general'
        );

        // Duplicate Comments field.
        add_settings_field(
            'duplicate_comments',
            __( 'Duplicate Comments', 'just-duplicate' ),
            [ __CLASS__, 'render_duplicate_comments_field' ],
            self::OPTION_KEY,
            'JUST_DUPLICATE_general'
        );

        // Duplicate Featured Image field.
        add_settings_field(
            'duplicate_featured_image',
            __( 'Duplicate Featured Image', 'just-duplicate' ),
            [ __CLASS__, 'render_duplicate_featured_image_field' ],
            self::OPTION_KEY,
            'JUST_DUPLICATE_general'
        );
    }

    /**
     * Sanitize plugin settings before saving.
     *
     * @param array $settings The submitted settings.
     * @return array Sanitized settings.
     */
    public static function sanitize_settings( array $settings ): array {
        return [
            'redirect_after_duplicate' => isset( $settings['redirect_after_duplicate'] ) ? (bool) $settings['redirect_after_duplicate'] : false,
            'default_prefix'           => sanitize_text_field( $settings['default_prefix'] ?? '' ),
            'default_suffix'           => sanitize_text_field( $settings['default_suffix'] ?? '' ),
            'duplicate_post_meta'      => isset( $settings['duplicate_post_meta'] ) ? (bool) $settings['duplicate_post_meta'] : true,
            'duplicate_taxonomies'     => isset( $settings['duplicate_taxonomies'] ) ? (bool) $settings['duplicate_taxonomies'] : true,
            'duplicate_attachments'    => isset( $settings['duplicate_attachments'] ) ? (bool) $settings['duplicate_attachments'] : false,
            'duplicate_custom_fields'  => isset( $settings['duplicate_custom_fields'] ) ? (bool) $settings['duplicate_custom_fields'] : true,
            'duplicate_custom_taxonomies' => isset( $settings['duplicate_custom_taxonomies'] ) ? (bool) $settings['duplicate_custom_taxonomies'] : true,
            'duplicate_comments'       => isset( $settings['duplicate_comments'] ) ? (bool) $settings['duplicate_comments'] : true,
            'duplicate_featured_image' => isset( $settings['duplicate_featured_image'] ) ? (bool) $settings['duplicate_featured_image'] : true,
        ];
    }

    /**
     * Render the "Redirect After Duplication" field.
     *
     * @return void
     */
    public static function render_redirect_field(): void {
        $settings = get_option( self::OPTION_KEY, [] );
        ?>
        <p>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[redirect_after_duplicate]" value="1" <?php checked( $settings['redirect_after_duplicate'] ?? false, true ); ?> />
            <label><?php esc_html_e( 'Redirect to the edit screen of the duplicated item.', 'just-duplicate' ); ?></label>
        </p>
        <?php
    }

    /**
     * Render the "Default Prefix" field.
     *
     * @return void
     */
    public static function render_prefix_field(): void {
        $settings = get_option( self::OPTION_KEY, [] );
        ?>
        <p>
            <input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_prefix]" value="<?php echo esc_attr( $settings['default_prefix'] ?? '' ); ?>" />
            <span class="description"><?php esc_html_e( 'Set a default prefix for duplicated items (optional).', 'just-duplicate' ); ?></span>
        </p>
        <?php
    }

    /**
     * Render the "Default Suffix" field.
     *
     * @return void
     */
    public static function render_suffix_field(): void {
        $settings = get_option( self::OPTION_KEY, [] );
        ?>
        <p>
            <input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[default_suffix]" value="<?php echo esc_attr( $settings['default_suffix'] ?? '' ); ?>" />
            <span class="description"><?php esc_html_e( 'Set a default suffix for duplicated items (optional).', 'just-duplicate' ); ?></span>
        </p>
        <?php
    }

    /**
     * Render the "Duplicate Post Meta" field.
     *
     * @return void
     */
    public static function render_duplicate_post_meta_field(): void {
        $settings = get_option( self::OPTION_KEY, [] );
        ?>
        <p>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[duplicate_post_meta]" value="1" <?php checked( $settings['duplicate_post_meta'] ?? true, true ); ?> />
            <label><?php esc_html_e( 'Duplicate all custom fields (post meta).', 'just-duplicate' ); ?></label>
        </p>
        <?php
    }

    /**
     * Render the "Duplicate Taxonomies" field.
     *
     * @return void
     */
    public static function render_duplicate_taxonomies_field(): void {
        $settings = get_option( self::OPTION_KEY, [] );
        ?>
        <p>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[duplicate_taxonomies]" value="1" <?php checked( $settings['duplicate_taxonomies'] ?? true, true ); ?> />
            <label><?php esc_html_e( 'Duplicate taxonomies and terms.', 'just-duplicate' ); ?></label>
        </p>
        <?php
    }

    /**
     * Render the "Duplicate Attachments" field.
     *
     * @return void
     */
    public static function render_duplicate_attachments_field(): void {
        $settings = get_option( self::OPTION_KEY, [] );
        ?>
        <p>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[duplicate_attachments]" value="1" <?php checked( $settings['duplicate_attachments'] ?? false, true ); ?> />
            <label><?php esc_html_e( 'Duplicate featured image and attachments (if available).', 'just-duplicate' ); ?></label>
        </p>
        <?php
    }

    /**
     * Render the "Duplicate Custom Fields" field.
     *
     * @return void
     */
    public static function render_duplicate_custom_fields_field(): void {
        $settings = get_option( self::OPTION_KEY, [] );
        ?>
        <p>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[duplicate_custom_fields]" value="1" <?php checked( $settings['duplicate_custom_fields'] ?? true, true ); ?> />
            <label><?php esc_html_e( 'Duplicate custom fields (meta boxes).', 'just-duplicate' ); ?></label>
        </p>
        <?php
    }

    /**
     * Render the "Duplicate Custom Taxonomies" field.
     *
     * @return void
     */
    public static function render_duplicate_custom_taxonomies_field(): void {
        $settings = get_option( self::OPTION_KEY, [] );
        ?>
        <p>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[duplicate_custom_taxonomies]" value="1" <?php checked( $settings['duplicate_custom_taxonomies'] ?? true, true ); ?> />
            <label><?php esc_html_e( 'Duplicate custom taxonomies.', 'just-duplicate' ); ?></label>
        </p>
        <?php
    }

    /**
     * Render the "Duplicate Comments" field.
     *
     * @return void
     */
    public static function render_duplicate_comments_field(): void {
        $settings = get_option( self::OPTION_KEY, [] );
        ?>
        <p>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[duplicate_comments]" value="1" <?php checked( $settings['duplicate_comments'] ?? true, true ); ?> />
            <label><?php esc_html_e( 'Duplicate comments.', 'just-duplicate' ); ?></label>
        </p>
        <?php
    }

    /**
     * Render the "Duplicate Featured Image" field.
     *
     * @return void
     */
    public static function render_duplicate_featured_image_field(): void {
        $settings = get_option( self::OPTION_KEY, [] );
        ?>
        <p>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[duplicate_featured_image]" value="1" <?php checked( $settings['duplicate_featured_image'] ?? true, true ); ?> />
            <label><?php esc_html_e( 'Duplicate featured image.', 'just-duplicate' ); ?></label>
        </p>
        <?php
    }

    /**
     * Add a custom bulk action for duplication.
     *
     * @param array $bulk_actions Existing bulk actions.
     * @return array Modified bulk actions.
     */
    public static function register_bulk_action( array $bulk_actions ): array {
        $bulk_actions['duplicate'] = __( 'Duplicate', 'just-duplicate' );
        return $bulk_actions;
    }

    /**
     * Handle the bulk duplication action.
     *
     * Processes each selected post and returns the modified redirect URL.
     *
     * @param string $redirect_url The URL to redirect to after action.
     * @param string $action       The action being performed.
     * @param array  $post_ids     Array of selected post IDs.
     * @return string Modified redirect URL.
     */
    public static function handle_bulk_action( string $redirect_url, string $action, array $post_ids ): string {
        if ( 'duplicate' !== $action ) {
            return $redirect_url;
        }
        foreach ( $post_ids as $post_id ) {
            self::duplicate_post( (int) $post_id );
        }
        return add_query_arg( 'bulk_duplicated', count( $post_ids ), $redirect_url );
    }

    /**
     * Duplicate a single post.
     *
     * Creates a new post by copying the original post's content and settings,
     * and conditionally copies meta, taxonomies, and attachments.
     *
     * @param int $post_id The ID of the post to duplicate.
     * @return void
     */
    private static function duplicate_post( int $post_id ): void {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return;
        }
        $settings       = get_option( self::OPTION_KEY, [] );
        $default_prefix = isset( $settings['default_prefix'] ) ? (string) $settings['default_prefix'] : '';
        $default_suffix = isset( $settings['default_suffix'] ) && '' !== $settings['default_suffix']
            ? (string) $settings['default_suffix']
            : ' (Copy)';
        $new_post = [
            'post_title'   => $default_prefix . $post->post_title . $default_suffix,
            'post_content' => $post->post_content,
            'post_status'  => 'draft',
            'post_type'    => $post->post_type,
            'post_author'  => get_current_user_id(),
            'post_excerpt' => $post->post_excerpt,
            'post_parent'  => $post->post_parent,
        ];
        $new_post_id = wp_insert_post( $new_post );
        if ( is_wp_error( $new_post_id ) || ! $new_post_id ) {
            return;
        }
        if ( ! empty( $settings['duplicate_post_meta'] ) ) {
            self::copy_post_meta( $post_id, (int) $new_post_id );
        }
        if ( ! empty( $settings['duplicate_taxonomies'] ) ) {
            self::copy_post_taxonomies( $post_id, (int) $new_post_id );
        }
        if ( ! empty( $settings['duplicate_attachments'] ) ) {
            $thumb_id = get_post_thumbnail_id( $post_id );
            if ( $thumb_id ) {
                $new_thumb_id = \Just_Duplicate\Duplicate_Handler::duplicate_attachment( $thumb_id, (int) $new_post_id );
                if ( $new_thumb_id ) {
                    set_post_thumbnail( (int) $new_post_id, $new_thumb_id );
                }
            }
        }
    }

    /**
     * Copy metadata from the original post to the duplicated post.
     *
     * @param int $old_post_id Original post ID.
     * @param int $new_post_id New post ID.
     * @return void
     */
    private static function copy_post_meta( int $old_post_id, int $new_post_id ): void {
        $meta_data = get_post_meta( $old_post_id );
        foreach ( $meta_data as $key => $values ) {
            foreach ( $values as $value ) {
                add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
            }
        }
    }

    /**
     * Copy taxonomies from the original post to the duplicated post.
     *
     * @param int $old_post_id Original post ID.
     * @param int $new_post_id New post ID.
     * @return void
     */
    private static function copy_post_taxonomies( int $old_post_id, int $new_post_id ): void {
        $taxonomies = get_object_taxonomies( get_post_type( $old_post_id ) );
        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $old_post_id, $taxonomy, [ 'fields' => 'slugs' ] );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                wp_set_object_terms( $new_post_id, $terms, $taxonomy );
            }
        }
    }

    /**
     * Render the settings page.
     *
     * This method outputs the complete HTML for the settings page using a simple tabbed interface.
     *
     * @return void
     */
    public static function render_settings_page(): void {
        ?>
        <div class="wrap just-duplicate-wrap">
            <h1><?php esc_html_e( 'Just Duplicate Settings', 'just-duplicate' ); ?></h1>
            <!-- Tabs Navigation -->
            <ul class="jd-tabs">
                <li class="jd-tab active" data-tab="general-tab"><?php esc_html_e( 'General', 'just-duplicate' ); ?></li>
                <li class="jd-tab" data-tab="advanced-tab"><?php esc_html_e( 'Advanced', 'just-duplicate' ); ?></li>
                <li class="jd-tab" data-tab="help-tab"><?php esc_html_e( 'Help & Support', 'just-duplicate' ); ?></li>
                <li class="jd-tab" data-tab="report-tab"><?php esc_html_e( 'Report', 'just-duplicate' ); ?></li>
            </ul>
            <!-- Tabs Content -->
            <div class="jd-tab-content active" id="general-tab">
                <form method="post" action="options.php">
                    <?php
                        settings_fields( self::OPTION_KEY );
                        do_settings_sections( self::OPTION_KEY );
                        submit_button();
                    ?>
                </form>
            </div>
            <div class="jd-tab-content" id="advanced-tab">
                <h2><?php esc_html_e( 'Advanced Settings', 'just-duplicate' ); ?></h2>
                <p><?php esc_html_e( 'Coming Soon...', 'just-duplicate' ); ?></p>
            </div>
            <div class="jd-tab-content" id="help-tab">
                <?php self::render_help_page(); ?>
            </div>
            <div class="jd-tab-content" id="report-tab">
                <?php self::render_report_page(); ?>
            </div>
        </div>
        <?php
    }

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
            <h2><?php esc_html_e( 'Useful Links', 'just-duplicate' ); ?></h2>
            <ul>
                <li>
                    <a href="https://justthere.co.uk/plugins/just-duplicate" target="_blank">
                        <?php esc_html_e( 'Plugin Listing', 'just-duplicate' ); ?>
                    </a>
                </li>
                <li>
                    <a href="https://justthere.co.uk/plugins/just-duplicate/documentation" target="_blank">
                        <?php esc_html_e( 'Documentation', 'just-duplicate' ); ?>
                    </a>
                </li>
                <li>
                    <a href="https://justthere.co.uk/plugins/just-duplicate/support" target="_blank">
                        <?php esc_html_e( 'Support', 'just-duplicate' ); ?>
                    </a>
                </li>
                <li>
                    <a href="https://justthere.co.uk/plugins/just-duplicate/feature-request" target="_blank">
                        <?php esc_html_e( 'Feature Request', 'just-duplicate' ); ?>
                    </a>
                </li>
                <li>
                    <a href="https://justthere.co.uk/donate" target="_blank">
                        <?php esc_html_e( 'Buy Us a Coffee', 'just-duplicate' ); ?>
                    </a>
                </li>
            </ul>
            <h2><?php esc_html_e( 'Getting Started', 'just-duplicate' ); ?></h2>
            <p><?php esc_html_e( 'For a detailed guide on using the plugin, check out our documentation linked above. If you encounter any issues, please use the support link to report them.', 'just-duplicate' ); ?></p>
            <h2><?php esc_html_e( 'System Requirements', 'just-duplicate' ); ?></h2>
            <ul>
                <li><?php esc_html_e( 'WordPress version: 5.5 or higher', 'just-duplicate' ); ?></li>
                <li><?php esc_html_e( 'PHP version: 7.4 or higher', 'just-duplicate' ); ?></li>
                <li><?php esc_html_e( 'MySQL version: 5.6 or higher', 'just-duplicate' ); ?></li>
            </ul>
            <h2><?php esc_html_e( 'Contact Us', 'just-duplicate' ); ?></h2>
            <p><?php esc_html_e( 'For further assistance, feel free to reach out via the support link. We appreciate your feedback and suggestions!', 'just-duplicate' ); ?></p>
        </div>
        <?php
    }

    /**
     * Render the Report page.
     *
     * @return void
     */
    public static function render_report_page(): void {
        $log = Duplicate_Logger::get_log();
        ?>
        <h2><?php esc_html_e( 'Duplication Report', 'just-duplicate' ); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Original ID', 'just-duplicate' ); ?></th>
                    <th><?php esc_html_e( 'New ID', 'just-duplicate' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'just-duplicate' ); ?></th>
                    <th><?php esc_html_e( 'User', 'just-duplicate' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'just-duplicate' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $log as $entry ) : ?>
                    <tr>
                        <td><?php echo esc_html( $entry['original_id'] ); ?></td>
                        <td><?php echo esc_html( $entry['new_id'] ); ?></td>
                        <td><?php echo esc_html( $entry['type'] ); ?></td>
                        <td><?php echo esc_html( get_userdata( $entry['user'] )->user_login ); ?></td>
                        <td><?php echo esc_html( $entry['date'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Handle the preview duplication AJAX request.
     *
     * Returns a JSON response containing preview data for a duplicated post.
     *
     * @return void
     */
    public static function preview_duplicate(): void {
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
        $settings = get_option( self::OPTION_KEY, [] );
        $default_prefix = isset( $settings['default_prefix'] ) ? (string) $settings['default_prefix'] : '';
        $default_suffix = isset( $settings['default_suffix'] ) && '' !== $settings['default_suffix'] ? (string) $settings['default_suffix'] : ' (Copy)';
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
