<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Minimal internal logger for authentication flows.
 */
class Logger {
    /**
     * Log auth-related context to the PHP error log.
     *
     * @param string $provider
     * @param array<string,mixed> $context
     */
    public static function auth( string $provider, array $context = [] ): void {
        $chunks   = [ '[VCS][auth]', 'provider=' . sanitize_key( $provider ) ];

        foreach ( $context as $key => $value ) {
            $key = sanitize_key( (string) $key );
            if ( '' === $key ) {
                continue;
            }
            if ( is_bool( $value ) ) {
                $value = $value ? 'true' : 'false';
            } elseif ( is_numeric( $value ) ) {
                $value = (string) $value;
            } elseif ( is_array( $value ) ) {
                $value = wp_json_encode( $value );
            } else {
                $value = trim( (string) $value );
            }
            if ( '' === $value ) {
                continue;
            }
            $chunks[] = $key . '=' . self::truncate( $value );
        }

        self::dispatch_log( $chunks );
    }

    private static function truncate( string $value, int $limit = 180 ): string {
        if ( strlen( $value ) <= $limit ) {
            return $value;
        }
        return substr( $value, 0, $limit - 3 ) . '...';
    }

    /**
     * Route the prepared log line to WordPress-aware handlers.
     *
     * @param array<int,string> $chunks
     */
    private static function dispatch_log( array $chunks ): void {
        $message = implode( ' ', $chunks );

        /**
         * Fires when an authentication log entry is recorded.
         *
         * @param string $message
         * @param array  $chunks
         */
        do_action( 'ventraconnect_social_login_auth_log', $message, $chunks );

        $default_logger = apply_filters( 'ventraconnect_social_login_default_logger', 'error_log' );
        if ( is_callable( $default_logger ) ) {
            call_user_func( $default_logger, $message );
        }
    }
}
