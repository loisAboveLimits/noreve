<?php
namespace VentraConnect\SocialLogin\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin assets loader for the settings UI.
 *
 * Moved from Settings::enqueue with no logic changes.
 */
class Assets {
    /**
     * Enqueue admin CSS/JS for the settings screen.
     *
     * @param string $hook
     */
    public function enqueue( $hook ): void {
        if ( false === strpos( (string) $hook, 'ventraconnect-sl-settings' ) ) { return; }
        wp_enqueue_style( 'wsc-admin', VENTRACONNECT_SL_PLUGIN_URL . 'assets/css/admin.css', [], VENTRACONNECT_SL_VERSION );
        wp_enqueue_style(
            'ventraconnect-sl-admin-components',
            plugins_url( 'assets/css/admin-components.css', VENTRACONNECT_SL_PLUGIN_FILE ),
            [],
            VENTRACONNECT_SL_VERSION
        );
        wp_enqueue_style( 'wp-color-picker' );
        // Load frontend styles for the in-admin preview button.
        wp_enqueue_style( 'wsc-frontend', VENTRACONNECT_SL_PLUGIN_URL . 'assets/css/frontend.css', [], VENTRACONNECT_SL_VERSION );
        // New token-based buttons CSS for previews
        wp_enqueue_style( 'ventraconnect-sl-buttons', VENTRACONNECT_SL_PLUGIN_URL . 'assets/css/vcs-buttons.css', [], VENTRACONNECT_SL_VERSION );
        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'wsc-admin', VENTRACONNECT_SL_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ], VENTRACONNECT_SL_VERSION, true );
        wp_localize_script(
            'wsc-admin',
            'VCS_ADMIN',
            [
                'ajax_url'             => admin_url( 'admin-ajax.php' ),
                // Canonical admin nonce (legacy key retained only as an alias).
                'nonce'                => wp_create_nonce( 'ventraconnect_sl_admin_nonce' ),
                'nonce_legacy'         => wp_create_nonce( 'ventraconnect_sl_admin_nonce' ),
                // Dedicated nonce for provider order saving (canonical + legacy vc_ prefix still allowed).
                'provider_order_nonce' => wp_create_nonce( 'ventraconnect_sl_provider_order' ),
                'provider_order_nonce_legacy' => wp_create_nonce( 'vc_provider_order' ),
            ]
        );
        // Nonce for token-auth admin tests (canonical only; legacy key is an alias).
        wp_localize_script(
            'wsc-admin',
            'VCS_AUTH',
            [
                'ajax_url'      => admin_url( 'admin-ajax.php' ),
                'nonce'         => wp_create_nonce( 'ventraconnect_sl_auth' ),
                'nonce_legacy'  => wp_create_nonce( 'ventraconnect_sl_auth' ),
            ]
        );
    }
}
