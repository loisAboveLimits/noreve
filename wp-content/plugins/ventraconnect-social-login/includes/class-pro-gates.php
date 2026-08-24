<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Pro feature gating and upsell helpers.
 */
class Pro_Gates {
    /**
     * Check if Pro is active and licensed.
     */
    public static function is_pro(): bool {
        if ( defined( 'VENTRACONNECT_PRO' ) && VENTRACONNECT_PRO ) {
            return true;
        }
        if ( function_exists( 'apply_filters' ) && apply_filters( 'ventraconnect_sl_is_pro', false ) ) {
            return true;
        }
        return defined( 'VCS_PRO_ACTIVE' ) && VCS_PRO_ACTIVE === true;
    }

    /**
     * Get upsell link.
     */
    public static function upsell_link( string $context = '' ): string {
        $url = 'https://wpventra.com/pricing/';
        return esc_url( apply_filters( 'ventraconnect_sl_upsell_link', $url, $context ) );
    }

    /**
     * Render a small inline upsell notice.
     */
    public static function upsell_inline( string $message ): string {
        if ( self::is_pro() ) {
            return '';
        }
        $link = self::upsell_link( 'inline' );
        return '<span class="wsc-lock"></span> <a href="' . esc_url( $link ) . '" target="_blank" rel="noopener">' . esc_html( $message ) . '</a>';
    }

    /**
     * Guard a feature; optionally echo locked UI.
     */
    public static function guard_or_upsell( string $feature_key, bool $render_locked_ui = true ): bool {
        if ( self::is_pro() ) {
            return true;
        }
        if ( $render_locked_ui ) {
            echo '<div class="wsc-upsell">' . esc_html__( 'Add social login to WooCommerce checkout + styling and redirects — Get Pro', 'ventraconnect-social-login' ) . ' ' . wp_kses_post( self::upsell_inline( __( 'Upgrade', 'ventraconnect-social-login' ) ) ) . '</div>';
        }
        return false;
    }

    /**
     * Provider-specific upsell copy for Pro-only providers.
     * Moved from Settings::get_provider_upsell_copy (no behavior change).
     *
     * @param string $slug
     * @return array{title?:string,description?:string,cta_url?:string}
     */
    public static function get_provider_upsell_copy( string $slug ): array {
        $copy = [
            'magic_link' => [
                'title'       => __( 'Unlock Magic Link Login', 'ventraconnect-social-login' ),
                'description' => __( 'Send one-tap sign-in links by email so users can skip remembering passwords.', 'ventraconnect-social-login' ),
                'cta_url'     => self::upsell_link( 'provider-magic-link' ),
            ],
            'otp_email' => [
                'title'       => __( 'Unlock Email OTP Login', 'ventraconnect-social-login' ),
                'description' => __( 'Deliver time-sensitive verification codes via email for secure, passwordless access.', 'ventraconnect-social-login' ),
                'cta_url'     => self::upsell_link( 'provider-otp-email' ),
            ],
        ];

        return $copy[ $slug ] ?? [];
    }
}
