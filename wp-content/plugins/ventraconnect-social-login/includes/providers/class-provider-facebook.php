<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Facebook OAuth 2.0 Provider.
 */
class Facebook extends Abstract_Provider {
    public function get_slug() { return 'facebook'; }
    public function get_label() { return 'Facebook'; }

    public function get_auth_url( $state, $redirect_uri ) {
        $args = [
            'client_id'     => $this->client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => implode( ',', apply_filters( 'ventraconnect_sl_oauth_scopes', [ 'email', 'public_profile' ], $this->get_slug() ) ),
            'state'         => $state,
        ];
        return 'https://www.facebook.com/v12.0/dialog/oauth?' . http_build_query( $args, '', '&' );
    }

    public function exchange_code_for_token( $code, $redirect_uri ) {
        $args = [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri'  => $redirect_uri,
            'code'          => $code,
        ];
        $resp = wp_remote_get( 'https://graph.facebook.com/v12.0/oauth/access_token?' . http_build_query( $args, '', '&' ), [ 'timeout' => 20 ] );
        if ( is_wp_error( $resp ) ) { return $resp; }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['access_token'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_token_error', __( 'Failed to obtain access token from Facebook.', 'ventraconnect-social-login' ) );
        }
        return $data;
    }

    public function fetch_user_profile( $access_token ) {
        $args = [
            'access_token' => $access_token,
            'fields' => 'id,name,email,picture'
        ];
        $resp = wp_remote_get( 'https://graph.facebook.com/me?' . http_build_query( $args, '', '&' ), [ 'timeout' => 20 ] );
        if ( is_wp_error( $resp ) ) { return $resp; }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['id'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_profile_error', __( 'Failed to fetch profile from Facebook.', 'ventraconnect-social-login' ) );
        }
        return [
            'provider' => $this->get_slug(),
            'id'       => $data['id'],
            'email'    => $data['email'] ?? '',
            'name'     => $data['name'] ?? '',
            'avatar'   => isset( $data['picture']['data']['url'] ) ? $data['picture']['data']['url'] : '',
            'raw'      => $data,
        ];
    }
}
