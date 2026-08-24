<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * LINE Login provider via OpenID Connect.
 */
class Line extends Abstract_Provider {
    public function get_slug() { return 'line'; }
    public function get_label() { return 'LINE'; }

    public function get_auth_url( $state, $redirect_uri ) {
        $scopes = apply_filters( 'ventraconnect_sl_oauth_scopes', [ 'openid', 'profile', 'email' ], $this->get_slug() );
        if ( ! is_array( $scopes ) ) {
            $scopes = [ 'openid', 'profile', 'email' ];
        }
        $args = [
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => $redirect_uri,
            'state'         => $state,
            'scope'         => implode( ' ', array_unique( array_filter( array_map( 'trim', $scopes ) ) ) ),
        ];
        return 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query( $args, '', '&' );
    }

    public function exchange_code_for_token( $code, $redirect_uri ) {
        $body = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirect_uri,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        ];
        $resp = wp_remote_post( 'https://api.line.me/oauth2/v2.1/token', [
            'body'    => $body,
            'timeout' => 20,
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ] );
        if ( is_wp_error( $resp ) ) { return $resp; }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['access_token'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_token_error', __( 'Failed to obtain access token from LINE.', 'ventraconnect-social-login' ) );
        }
        return $data;
    }

    public function fetch_user_profile( $access_token ) {
        $resp = wp_remote_get( 'https://api.line.me/oauth2/v2.1/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'        => 'application/json',
            ],
            'timeout' => 20,
        ] );
        if ( is_wp_error( $resp ) ) { return $resp; }

        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( ! is_array( $data ) ) {
            return new \WP_Error( 'ventraconnect_sl_profile_error', __( 'Failed to parse profile from LINE.', 'ventraconnect-social-login' ) );
        }

        $id = $data['sub'] ?? '';
        if ( empty( $id ) ) {
            return new \WP_Error( 'ventraconnect_sl_profile_error', __( 'Failed to fetch profile from LINE.', 'ventraconnect-social-login' ) );
        }

        $display = (string) ( $data['name'] ?? '' );
        $nickname = (string) ( $data['preferred_username'] ?? '' );
        if ( '' === $display && '' !== $nickname ) {
            $display = $nickname;
        }

        $profile = [
            'provider' => $this->get_slug(),
            'id'       => (string) $id,
            'email'    => (string) ( $data['email'] ?? '' ),
            'name'     => $display,
            'display_name' => $display,
            'nickname' => $nickname,
            'avatar'   => (string) ( $data['picture'] ?? '' ),
            'avatar_url' => (string) ( $data['picture'] ?? '' ),
            'raw'      => $data,
        ];

        if ( empty( $profile['name'] ) || empty( $profile['avatar'] ) ) {
            $extra = wp_remote_get( 'https://api.line.me/v2/profile', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Accept'        => 'application/json',
                ],
                'timeout' => 15,
            ] );
            if ( ! is_wp_error( $extra ) ) {
                $extra_data = json_decode( wp_remote_retrieve_body( $extra ), true );
                if ( is_array( $extra_data ) ) {
                    if ( empty( $profile['name'] ) && ! empty( $extra_data['displayName'] ) ) {
                        $profile['name'] = (string) $extra_data['displayName'];
                        $profile['display_name'] = (string) $extra_data['displayName'];
                    }
                    if ( empty( $profile['avatar'] ) && ! empty( $extra_data['pictureUrl'] ) ) {
                        $profile['avatar'] = (string) $extra_data['pictureUrl'];
                        $profile['avatar_url'] = (string) $extra_data['pictureUrl'];
                    }
                    if ( empty( $profile['nickname'] ) && ! empty( $extra_data['userId'] ) ) {
                        $profile['nickname'] = (string) $extra_data['userId'];
                    }
                    $profile['raw']['line_profile'] = $extra_data;
                }
            }
        }

        $display_final = (string) ( $profile['display_name'] ?? $profile['name'] ?? '' );
        $first_name = '';
        $last_name  = '';
        if ( '' !== $display_final ) {
            $parts = preg_split( '/\s+/', trim( $display_final ), -1, PREG_SPLIT_NO_EMPTY );
            if ( ! empty( $parts ) ) {
                $first_name = sanitize_text_field( array_shift( $parts ) );
                $last_name  = $parts ? sanitize_text_field( implode( ' ', $parts ) ) : '';
            }
        }
        $profile['first_name'] = $first_name;
        $profile['last_name']  = $last_name;

        return $profile;
    }
}
