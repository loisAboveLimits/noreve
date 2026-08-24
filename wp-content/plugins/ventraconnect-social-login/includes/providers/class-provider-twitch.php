<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Twitch OAuth 2.0 Provider.
 */
class Twitch extends Abstract_Provider {
    public function get_slug() { return 'twitch'; }
    public function get_label() { return 'Twitch'; }

    public function get_auth_url( $state, $redirect_uri ) {
        $scopes = implode( ' ', apply_filters( 'ventraconnect_sl_oauth_scopes', $this->get_scopes(), $this->get_slug() ) );
        $args = [
            'client_id'     => $this->client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => $scopes,
            'state'         => $state,
            'force_verify'  => 'true',
        ];
        return 'https://id.twitch.tv/oauth2/authorize?' . http_build_query( $args, '', '&' );
    }

    public function get_scopes() {
        return [ 'user:read:email' ];
    }

    public function exchange_code_for_token( $code, $redirect_uri ) {
        $body = [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirect_uri,
        ];
        $resp = wp_remote_post( 'https://id.twitch.tv/oauth2/token', [
            'body'    => $body,
            'timeout' => 20,
            'headers' => [ 'Accept' => 'application/json' ],
        ] );
        if ( is_wp_error( $resp ) ) {
            return $resp;
        }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['access_token'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_token_error', __( 'Failed to obtain access token from Twitch.', 'ventraconnect-social-login' ) );
        }
        return $data;
    }

    public function fetch_user_profile( $access_token ) {
        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Client-Id'     => $this->client_id,
            'Accept'        => 'application/json',
        ];
        $resp = wp_remote_get( 'https://api.twitch.tv/helix/users', [
            'headers' => $headers,
            'timeout' => 15,
        ] );
        if ( is_wp_error( $resp ) ) {
            return $resp;
        }
        $payload = json_decode( wp_remote_retrieve_body( $resp ), true );
        $user = is_array( $payload['data'][0] ?? null ) ? $payload['data'][0] : [];
        if ( empty( $user['id'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_profile_error', __( 'Failed to fetch profile from Twitch.', 'ventraconnect-social-login' ) );
        }
        $email  = (string) ( $user['email'] ?? '' );
        $login  = (string) ( $user['login'] ?? '' );
        $name   = (string) ( $user['display_name'] ?? $login );
        $avatar = (string) ( $user['profile_image_url'] ?? '' );
        $profile_url = $login ? 'https://www.twitch.tv/' . $login : '';

        $meta = [
            'email_granted'          => ( '' !== $email ),
            'email_verified_default' => (bool) ( $user['email_verified'] ?? ( '' !== $email ) ),
        ];

        return [
            'provider'       => $this->get_slug(),
            'id'             => (string) $user['id'],
            'email'          => $email,
            'email_verified' => (bool) ( $user['email_verified'] ?? ( '' !== $email ) ),
            'name'           => $name,
            'profile_url'    => $profile_url,
            'avatar'         => $avatar,
            'meta'           => $meta,
            'raw'            => $payload,
        ];
    }
}
