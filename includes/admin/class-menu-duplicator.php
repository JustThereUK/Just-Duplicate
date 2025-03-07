<?php
declare(strict_types=1);

namespace Just_Duplicate\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Menu_Duplicator
 *
 * Provides functionality to duplicate navigation menus along with their items.
 */
class Menu_Duplicator {

    /**
     * Initialize the Menu Duplicator.
     */
    public static function init(): void {
        // Always output the Duplicate Menu button on the nav menus page.
        add_action( 'admin_footer-nav-menus.php', [ __CLASS__, 'output_duplicate_menu_button' ] );
        // Handle the duplication action.
        add_action( 'admin_post_duplicate_menu', [ __CLASS__, 'handle_duplicate_menu' ] );
    }

    /**
     * Output JavaScript to append the "Duplicate Menu" button on the Menus screen.
     */
    public static function output_duplicate_menu_button(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        // Build the base duplication URL using a constant nonce.
        $duplicate_base = wp_nonce_url(
            admin_url( 'admin-post.php?action=duplicate_menu' ),
            'duplicate_menu_action'
        );
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log("Menu Duplicator hook fired");
            // Create the Duplicate Menu button.
            var duplicateButton = $('<a>', {
                text: '<?php esc_html_e( "Duplicate Menu", "just-duplicate" ); ?>',
                href: '#',
                class: 'button button-secondary'
            });
            duplicateButton.on('click', function(e) {
                e.preventDefault();
                // Retrieve the current menu ID from the hidden input with id "menu".
                var menuId = $('#menu').val();
                console.log("Selected menu ID:", menuId);
                if (!menuId || menuId == 0) {
                    alert('<?php echo esc_js( __( "Please select a menu first.", "just-duplicate" ) ); ?>');
                    return;
                }
                // Build the final duplication URL by appending the menu ID.
                var duplicateUrl = '<?php echo esc_url( $duplicate_base ); ?>' + '&menu=' + menuId;
                console.log("Duplicate URL:", duplicateUrl);
                window.location.href = duplicateUrl;
            });
            // Append the button to the container.
            var target = $('#nav-menu-footer .major-publishing-actions');
            if (target.length) {
                target.append(duplicateButton);
                console.log("Duplicate button appended to .major-publishing-actions");
            } else {
                $('#nav-menu-footer').append(duplicateButton);
                console.log("Duplicate button appended to #nav-menu-footer");
            }
        });
        </script>
        <?php
    }

    /**
     * Handle the menu duplication action.
     */
    public static function handle_duplicate_menu(): void {
        if ( ! isset( $_GET['_wpnonce'] ) || ! isset( $_GET['menu'] ) ) {
            wp_die( esc_html( __( 'Missing required parameters.', 'just-duplicate' ) ) );
        }
        $menu_id = intval( $_GET['menu'] );
        $nonce   = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

        if ( ! wp_verify_nonce( $nonce, 'duplicate_menu_action' ) ) {
            wp_die( esc_html( __( 'Nonce verification failed.', 'just-duplicate' ) ) );
        }
        if ( ! $menu_id ) {
            wp_die( esc_html( __( 'No menu specified.', 'just-duplicate' ) ) );
        }
        $new_menu_id = self::duplicate_menu( $menu_id );
        if ( is_wp_error( $new_menu_id ) ) {
            wp_die( esc_html( $new_menu_id->get_error_message() ) );
        }
        wp_redirect( esc_url( add_query_arg( 'duplicated_menu', $new_menu_id, admin_url( 'nav-menus.php' ) ) ) );
        exit;
    }

    /**
     * Duplicate a menu and its items.
     *
     * @param int $menu_id The ID of the menu to duplicate.
     * @return int|WP_Error New menu ID on success, or WP_Error on failure.
     */
    public static function duplicate_menu( int $menu_id ) {
        $original_menu = wp_get_nav_menu_object( $menu_id );
        if ( ! $original_menu ) {
            return new \WP_Error( 'menu_not_found', esc_html__( 'Menu not found.', 'just-duplicate' ) );
        }
        $new_menu_name = $original_menu->name . ' (Copy)';
        $new_menu_id   = wp_create_nav_menu( $new_menu_name );
        if ( is_wp_error( $new_menu_id ) ) {
            return $new_menu_id;
        }
        $menu_items = wp_get_nav_menu_items( $menu_id );
        if ( $menu_items ) {
            $old_to_new = [];
            foreach ( $menu_items as $item ) {
                $args = [
                    'menu-item-object-id' => $item->object_id,
                    'menu-item-object'    => $item->object,
                    'menu-item-parent-id' => 0,
                    'menu-item-position'  => $item->menu_order,
                    'menu-item-type'      => $item->type,
                    'menu-item-title'     => $item->title,
                    'menu-item-url'       => $item->url,
                    'menu-item-status'    => $item->post_status,
                ];
                $new_item_id = wp_update_nav_menu_item( $new_menu_id, 0, $args );
                if ( $new_item_id ) {
                    $old_to_new[ $item->ID ] = $new_item_id;
                }
            }
            foreach ( $menu_items as $item ) {
                if ( $item->menu_item_parent && isset( $old_to_new[ $item->menu_item_parent ] ) ) {
                    wp_update_nav_menu_item(
                        $new_menu_id,
                        $old_to_new[ $item->ID ],
                        [ 'menu-item-parent-id' => $old_to_new[ $item->menu_item_parent ] ]
                    );
                }
            }
        }
        return $new_menu_id;
    }
}
