<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Google OAuth 2.0 Provider.
 */
class Google extends Abstract_Provider {
    public function get_slug() { return 'google'; }
    public function get_label() { return 'Google'; }

    public function get_auth_url( $state, $redirect_uri ) {
        $args = [
            'client_id'     => $this->client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => implode( ' ', apply_filters( 'ventraconnect_sl_oauth_scopes', [ 'openid', 'email', 'profile' ], $this->get_slug() ) ),
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'consent',
        ];
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $args, '', '&' );
    }

    public function exchange_code_for_token( $code, $redirect_uri ) {
        $resp = wp_remote_post( 'https://oauth2.googleapis.com/token', [
            'body' => [
                'code'          => $code,
                'client_id'     => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri'  => $redirect_uri,
                'grant_type'    => 'authorization_code',
            ],
            'timeout' => 20,
        ] );
        if ( is_wp_error( $resp ) ) { return $resp; }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['access_token'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_token_error', __( 'Failed to obtain access token from Google.', 'ventraconnect-social-login' ) );
        }
        return $data;
    }

    public function fetch_user_profile( $access_token ) {
        $resp = wp_remote_get( 'https://openidconnect.googleapis.com/v1/userinfo', [
            'headers' => [ 'Authorization' => 'Bearer ' . $access_token ],
            'timeout' => 20,
        ] );
        if ( is_wp_error( $resp ) ) { return $resp; }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['sub'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_profile_error', __( 'Failed to fetch profile from Google.', 'ventraconnect-social-login' ) );
        }
        return [
            'provider' => $this->get_slug(),
            'id'       => $data['sub'],
            'email'    => $data['email'] ?? '',
            'name'     => $data['name'] ?? '',
            'avatar'   => $data['picture'] ?? '',
            'raw'      => $data,
        ];
    }
}
