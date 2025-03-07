<?php
/**
 * Uninstall script for Just Duplicate.
 *
 * This file is executed when the plugin is uninstalled.
 * It is responsible for cleaning up any data created by the plugin.
 *
 * @package Just_Duplicate
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Clean up plugin settings.
 */
delete_option( 'JUST_DUPLICATE_settings' );

/**
 * Optional: Clean up any transients, custom roles, or additional data added by the plugin.
 *
 * For example, if your plugin stored transients:
 * delete_transient( 'just_duplicate_some_transient' );
 *
 * Or if your plugin added custom capabilities to roles, you could remove them here:
 *
 * $role = get_role( 'editor' );
 * if ( $role ) {
 *     $role->remove_cap( 'duplicate_post_capability' );
 * }
 *
 * Add additional cleanup code here if your plugin creates other data.
 */
