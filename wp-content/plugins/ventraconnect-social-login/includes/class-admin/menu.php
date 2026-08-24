<?php
namespace VentraConnect\SocialLogin\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin menu registration for VentraConnect Social Login settings pages.
 *
 * Moved from Settings::admin_menu with no logic changes.
 */
class Menu {
    /**
     * Add top-level and submenu entries for the plugin settings.
     */
    public function admin_menu(): void {
        $cap = 'manage_options';
        $icon_url = 'dashicons-admin-generic';
        $svg_path = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ? VENTRACONNECT_SL_PLUGIN_DIR . 'assets/vc-icon.svg' : dirname( dirname( __DIR__ ) ) . '/assets/vc-icon.svg';
        if ( file_exists( $svg_path ) ) {
            $svg = file_get_contents( $svg_path );
            if ( $svg ) {
                $icon_url = 'data:image/svg+xml;base64,' . base64_encode( $svg );
            }
        }
        add_menu_page(
            __( 'VentraConnect', 'ventraconnect-social-login' ),
            __( 'VentraConnect', 'ventraconnect-social-login' ),
            $cap,
            'ventraconnect-sl-settings',
            [ $this, 'render_page' ],
            $icon_url,
            58
        );
        add_submenu_page(
            'ventraconnect-sl-settings',
            __( 'Settings', 'ventraconnect-social-login' ),
            __( 'Settings', 'ventraconnect-social-login' ),
            $cap,
            'ventraconnect-sl-settings',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Render callback proxies to original Settings::render_page to preserve behavior.
     */
    public function render_page(): void {
        ( new \VentraConnect\SocialLogin\Settings() )->render_page();
    }
}
