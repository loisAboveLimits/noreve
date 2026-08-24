<?php
namespace VentraConnect\SocialLogin\Diagnostics;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Ajax {
    /**
     * Register AJAX routes for diagnostics.
     */
    public static function register(): void {
        if ( ! is_admin() ) {
            return;
        }

        add_action(
            'wp_ajax_ventraconnect_sl_site_diagnostics',
            [ __CLASS__, 'handle_site_diagnostics' ]
        );
    }

    /**
     * Handle site-level diagnostics request (admin-ajax).
     */
    public static function handle_site_diagnostics(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                [
                    'message' => __( 'Insufficient permissions.', 'ventraconnect-social-login' ),
                ],
                403
            );
        }
        check_ajax_referer( 'ventraconnect_sl_site_diagnostics', '_wpnonce' );

        $limit = 20;
        if ( isset( $_GET['limit'] ) ) {
            $limit = absint( $_GET['limit'] );
        }
        if ( $limit <= 0 ) {
            $limit = 20;
        }
        if ( $limit > 1000 ) {
            $limit = 1000;
        }

        $type = null;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized into $limit/$type above.
        if ( isset( $_GET['type'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized into $limit/$type above.
            $type_raw = wp_unslash( (string) $_GET['type'] );
            $type     = sanitize_key( $type_raw );
        }
        if ( '' === $type ) {
            $type = null;
        }

        $report = Report::build_full_report( $limit, $type );

        if (
            function_exists( 'vcsl_is_pro_active' )
            && vcsl_is_pro_active()
            && class_exists( '\VentraConnect\SocialLogin\Pro\Diagnostics\Hooks' )
            && method_exists( '\VentraConnect\SocialLogin\Pro\Diagnostics\Hooks', 'augment_support_blob' )
        ) {
            $snapshot = isset( $report['snapshot'] ) && is_array( $report['snapshot'] ) ? $report['snapshot'] : [];
            $events   = isset( $report['events'] ) && is_array( $report['events'] ) ? $report['events'] : [];
            $blob     = isset( $report['support_blob'] ) && is_string( $report['support_blob'] ) ? $report['support_blob'] : '';

            $report['support_blob'] = \VentraConnect\SocialLogin\Pro\Diagnostics\Hooks::augment_support_blob(
                $blob,
                $snapshot,
                $events
            );
        }
        
        wp_send_json_success( $report );
    }
}
