<?php
declare(strict_types=1);

namespace Just_Duplicate;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Handles logging of duplication actions.
 */
class Duplicate_Logger {

    /**
     * Log a duplication action.
     *
     * @param int    $original_id The ID of the original item.
     * @param int    $new_id      The ID of the duplicated item.
     * @param string $type        The type of item duplicated (post, menu, media).
     * @return void
     */
    public static function log( int $original_id, int $new_id, string $type ): void {
        $log = get_option( 'JUST_DUPLICATE_log', [] );
        $log[] = [
            'original_id' => $original_id,
            'new_id'      => $new_id,
            'type'        => $type,
            'user'        => get_current_user_id(),
            'date'        => current_time( 'mysql' ),
        ];
        update_option( 'JUST_DUPLICATE_log', $log );
    }

    /**
     * Get the duplication log.
     *
     * @return array
     */
    public static function get_log(): array {
        return get_option( 'JUST_DUPLICATE_log', [] );
    }
}
