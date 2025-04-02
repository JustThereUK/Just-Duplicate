<?php
declare(strict_types=1);

namespace Just_Duplicate;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles scheduled duplication of posts/pages.
 */
class Scheduled_Duplicator {

    /**
     * Initialize the scheduled duplicator.
     */
    public static function init(): void {
        add_action( 'just_duplicate_schedule_event', [ __CLASS__, 'process_scheduled_duplication' ] );
    }

    /**
     * Schedule a duplication event.
     *
     * @param int $post_id The ID of the post to duplicate.
     * @param string $timestamp The timestamp for the scheduled duplication.
     */
    public static function schedule_duplication( int $post_id, string $timestamp ): void {
        wp_schedule_single_event( strtotime( $timestamp ), 'just_duplicate_schedule_event', [ $post_id ] );
    }

    /**
     * Process the scheduled duplication.
     *
     * @param int $post_id The ID of the post to duplicate.
     */
    public static function process_scheduled_duplication( int $post_id ): void {
        if ( ! $post_id ) {
            return;
        }

        // Use the existing duplication logic.
        Duplicate_Handler::process_duplication_by_id( $post_id );

        // Ensure Elementor meta is copied.
        self::copy_elementor_meta( $post_id );
    }

    /**
     * Copy Elementor-specific meta data to the duplicated post.
     *
     * @param int $original_post_id The ID of the original post.
     */
    private static function copy_elementor_meta( int $original_post_id ): void {
        $duplicated_post_id = Duplicate_Handler::get_last_duplicated_post_id();

        if ( ! $duplicated_post_id ) {
            return;
        }

        $meta_keys = [
            '_elementor_data',
            '_elementor_edit_mode',
            '_elementor_template_type',
            '_elementor_version',
        ];

        foreach ( $meta_keys as $meta_key ) {
            $meta_value = get_post_meta( $original_post_id, $meta_key, true );
            if ( $meta_value ) {
                update_post_meta( $duplicated_post_id, $meta_key, $meta_value );
            }
        }
    }
}
