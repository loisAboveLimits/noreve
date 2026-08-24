<?php
namespace VentraConnect\SocialLogin\Admin\Settings;

use VentraConnect\SocialLogin\OAuth;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Tiny helper to centralize provider metadata assembly.
 *
 * Minimal logic only; mirrors existing lookups used in Settings UI.
 */
class ProviderMeta {
    /**
     * Build provider metadata for a given slug.
     *
     * @param string $slug Provider slug (e.g. 'google').
     * @param Persistence $persistence Settings persistence instance.
     * @return array{
     *   slug:string,
     *   label:string,
     *   icon_url:string,
     *   redirect_uri:string,
     *   is_pro_only:bool,
     *   is_connected:bool,
     *   client_id:?string,
     *   client_secret:?string,
     * }
     */
    public static function build( string $slug, Persistence $persistence ): array {
        // Labels mapping used across Settings pages
        $labels = [
            'facebook' => 'Facebook',
            'google' => 'Google',
            'twitch' => 'Twitch',
            'reddit' => 'Reddit',
            'github' => 'GitHub',
            'microsoft' => 'Microsoft',
            'linkedin' => 'LinkedIn',
            'slack' => 'Slack',
            'amazon' => 'Amazon',
            'yahoo' => 'Yahoo',
            'wordpress' => 'WordPress.com',
            'discord' => 'Discord',
            'spotify' => 'Spotify',
            'line' => 'LINE',
            'twitter' => 'X',
            'tiktok' => 'TikTok',
            'magic_link' => 'Magic Link',
            'otp_email' => 'OTP (Email)',
            'passkey' => 'Passkey',
        ];

        $label = $labels[ $slug ] ?? ucfirst( $slug );
        $icon_url = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? ( VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/' . $slug . '.svg' ) : '';
        $redirect = OAuth::redirect_uri( $slug );
        $is_pro_only = in_array( $slug, [ 'magic_link', 'otp_email', 'passkey' ], true );

        $creds = (array) $persistence->getProviderCreds();
        $cid = null;
        $sec = null;
        if ( isset( $creds[ $slug ] ) && is_array( $creds[ $slug ] ) ) {
            if ( array_key_exists( 'client_id', $creds[ $slug ] ) ) {
                $cid = (string) $creds[ $slug ]['client_id'];
                if ( '' === $cid ) { $cid = null; }
            }
            if ( array_key_exists( 'client_secret', $creds[ $slug ] ) ) {
                $sec = (string) $creds[ $slug ]['client_secret'];
                if ( '' === $sec ) { $sec = null; }
            }
        }

        $has_id = ! empty( $cid );
        $has_secret = $is_pro_only ? true : ( ! empty( $sec ) );
        $is_connected = ( $has_id && $has_secret );

        return [
            'slug' => $slug,
            'label' => $label,
            'icon_url' => $icon_url,
            'redirect_uri' => is_string( $redirect ) ? $redirect : '',
            'is_pro_only' => $is_pro_only,
            'is_connected' => (bool) $is_connected,
            'client_id' => $cid,
            'client_secret' => $sec,
        ];
    }
}
