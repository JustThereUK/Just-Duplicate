<?php
declare(strict_types=1);

namespace Just_Duplicate;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Main Loader Class for Just Duplicate.
 *
 * This class handles the initialization of the plugin by loading the necessary files
 * and initializing both front-end and admin components.
 */
final class Loader {

    /**
     * Singleton instance of the Loader.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Gets the singleton instance of the Loader.
     *
     * @return self
     */
    public static function instance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone(): void {}

    /**
     * Prevent unserializing of the instance.
     *
     * @return void
     */
    public function __wakeup(): void {
        throw new \Exception('Cannot unserialize a singleton.');
    }

    /**
     * Private constructor to prevent direct instantiation.
     *
     * Loads plugin components and defines hooks.
     */
    private function __construct() {
        $this->define_hooks();
    }

    /**
     * Define hooks and load required files.
     *
     * @return void
     */
    private function define_hooks(): void {
        // Load the duplicate logger.
        $duplicate_logger_path = JUST_DUPLICATE_PATH . 'includes/class-duplicate-logger.php';
        if ( file_exists( $duplicate_logger_path ) ) {
            require_once $duplicate_logger_path;
        }

        // Load the duplicate handler.
        $duplicate_handler_path = JUST_DUPLICATE_PATH . 'includes/class-duplicate-handler.php';
        if ( file_exists( $duplicate_handler_path ) ) {
            require_once $duplicate_handler_path;
            if ( class_exists( 'Just_Duplicate\Duplicate_Handler' ) ) {
                Duplicate_Handler::init();
            }
        }

        // Load admin-specific components if in admin area.
        if ( is_admin() ) {
            $this->define_admin_hooks();
        }
    }

    /**
     * Define admin-specific hooks and load admin components.
     *
     * @return void
     */
    private function define_admin_hooks(): void {
        // Load Admin Settings.
        $admin_settings_path = JUST_DUPLICATE_PATH . 'includes/admin/class-admin-settings.php';
        if ( file_exists( $admin_settings_path ) ) {
            require_once $admin_settings_path;
            if ( class_exists( 'Just_Duplicate\Admin_Settings' ) ) {
                Admin_Settings::init();
            }
        }
        // Load Menu Duplicator.
        $menu_duplicator_path = JUST_DUPLICATE_PATH . 'includes/admin/class-menu-duplicator.php';
        if ( file_exists( $menu_duplicator_path ) ) {
            require_once $menu_duplicator_path;
            if ( class_exists( 'Just_Duplicate\Admin\Menu_Duplicator' ) ) {
                \Just_Duplicate\Admin\Menu_Duplicator::init();
            }
        }
        // Load Media Duplicator.
        $media_duplicator_path = JUST_DUPLICATE_PATH . 'includes/admin/class-media-duplicator.php';
        if ( file_exists( $media_duplicator_path ) ) {
            require_once $media_duplicator_path;
            if ( class_exists( 'Just_Duplicate\Admin\Media_Duplicator' ) ) {
                \Just_Duplicate\Admin\Media_Duplicator::init();
            }
        }
    }
}
