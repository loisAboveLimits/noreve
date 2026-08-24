<?php
namespace VentraConnect\SocialLogin\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Wires WordPress admin hooks to decoupled admin classes.
 *
 * Responsibilities:
 * - Register admin menu page
 * - Enqueue admin assets for the settings screen
 * - Register lightweight admin AJAX handlers
 *
 * This class only wires hooks; it contains no business logic.
 */
class ServiceProvider {
    /**
     * Register WordPress hooks for admin features.
     */
    public function register(): void {
        // Admin menu and page routing
        add_action( 'admin_menu', [ $this, 'on_admin_menu' ] );

        // Admin assets
        add_action( 'admin_enqueue_scripts', [ $this, 'on_admin_enqueue' ] );

        // AJAX: provider order save
        add_action( 'wp_ajax_vc_save_provider_order', [ $this, 'on_ajax_save_provider_order' ] );
        // Canonical AJAX action (new): also register canonical action name for forwards-compat
        add_action( 'wp_ajax_ventraconnect_sl_save_provider_order', [ $this, 'on_ajax_save_provider_order' ] );
        // AJAX: provider settings save
        add_action( 'wp_ajax_ventraconnect_sl_save_provider_settings', [ new \VentraConnect\SocialLogin\Admin\Settings\Ajax(), 'ajax_save_provider_settings' ] );
    }

    /**
     * Delegate admin_menu to Menu class.
     */
    public function on_admin_menu(): void {
        ( new Menu() )->admin_menu();
    }

    /**
     * Delegate enqueue to Assets class.
     *
     * @param string $hook
     */
    public function on_admin_enqueue( $hook ): void {
        ( new Assets() )->enqueue( $hook );
    }

    /**
     * Delegate AJAX handler to Admin\Settings\Ajax class.
     */
    public function on_ajax_save_provider_order(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array(
                    'code'    => 'forbidden',
                    'message' => __( 'You do not have permission to perform this action.', 'ventraconnect-social-login' ),
                ),
                403
            );
        }

        ( new \VentraConnect\SocialLogin\Admin\Settings\Ajax() )->ajax_save_provider_order();
    }
}
