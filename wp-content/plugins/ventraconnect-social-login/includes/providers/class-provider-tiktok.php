<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * TikTok OAuth 2.0 with POST userinfo.
 */
class TikTok extends Abstract_Provider {
    public function get_slug() { return 'tiktok'; }
    public function get_label() { return 'TikTok'; }

    public function get_auth_url( $state, $redirect_uri ) {
        $scopes = apply_filters( 'ventraconnect_sl_oauth_scopes', [ 'user.info.basic' ], $this->get_slug() );
        $args = [
            'client_key'    => $this->client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => implode( ',', $scopes ),
            'state'         => $state,
        ];
        return 'https://www.tiktok.com/v2/auth/authorize/?' . http_build_query( $args, '', '&' );
    }

    public function exchange_code_for_token( $code, $redirect_uri ) {
        $resp = wp_remote_post( 'https://open.tiktokapis.com/v2/oauth/token/', [
            'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json' ],
            'body'    => [
                'client_key'    => $this->client_id,
                'client_secret' => $this->client_secret,
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $redirect_uri,
            ],
            'timeout' => 25,
        ] );
        if ( is_wp_error( $resp ) ) { return $resp; }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['access_token'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_token_error', __( 'Failed to obtain access token from TikTok.', 'ventraconnect-social-login' ) );
        }
        return $data;
    }

    public function fetch_user_profile( $access_token ) {
        // TikTok user info requires open_id from the token exchange.
        $tok = get_transient( 'ventraconnect_sl_last_tiktok_token' );
        $open_id = '';
        if ( is_array( $tok ) ) {
            $candidates = [
                $tok['open_id'] ?? '',
                isset( $tok['data']['open_id'] ) ? $tok['data']['open_id'] : '',
            ];
            if ( isset( $tok['open_id_list'] ) && is_array( $tok['open_id_list'] ) && ! empty( $tok['open_id_list'][0] ) ) {
                $candidates[] = $tok['open_id_list'][0];
            }
            if ( isset( $tok['data']['open_id_list'] ) && is_array( $tok['data']['open_id_list'] ) && ! empty( $tok['data']['open_id_list'][0] ) ) {
                $candidates[] = $tok['data']['open_id_list'][0];
            }
            foreach ( $candidates as $candidate ) {
                $candidate = (string) $candidate;
                if ( '' !== $candidate ) {
                    $open_id = $candidate;
                    break;
                }
            }
        }
        $body = [ 'fields' => [ 'open_id', 'display_name', 'avatar_url', 'email' ] ];
        if ( $open_id ) {
            $body['user_ids'] = [ $open_id ];
            $body['open_id']  = $open_id;
        }
        $resp = wp_remote_post( 'https://open.tiktokapis.com/v2/user/info/', [
            'headers' => [ 'Authorization' => 'Bearer ' . $access_token, 'Content-Type' => 'application/json', 'Accept' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 20,
        ] );
        if ( is_wp_error( $resp ) ) { return $resp; }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        $u = [];
        if ( isset( $data['data']['user'] ) && is_array( $data['data']['user'] ) ) {
            $u = $data['data']['user'];
        } elseif ( isset( $data['data']['user_list'] ) && is_array( $data['data']['user_list'] ) ) {
            $first = $data['data']['user_list'][0] ?? [];
            if ( isset( $first['user'] ) && is_array( $first['user'] ) ) {
                $u = $first['user'];
            } elseif ( is_array( $first ) ) {
                $u = $first;
            }
        }
        $id = $u['open_id'] ?? '';
        if ( ! $id && $open_id ) {
            $id = $open_id;
        }
        if ( empty( $id ) ) {
            return new \WP_Error( 'ventraconnect_sl_profile_error', __( 'Failed to fetch profile from TikTok.', 'ventraconnect-social-login' ) );
        }
        $display = (string) ( $u['display_name'] ?? '' );
        $nickname = (string) ( $u['username'] ?? ( $u['unique_id'] ?? '' ) );
        $profile_url = (string) ( $u['profile_deep_link'] ?? ( $u['profile_url'] ?? '' ) );
        $first_name = '';
        $last_name  = '';
        if ( $display ) {
            $parts = preg_split( '/\s+/', trim( $display ), -1, PREG_SPLIT_NO_EMPTY );
            if ( ! empty( $parts ) ) {
                $first_name = (string) array_shift( $parts );
                $last_name  = $parts ? (string) implode( ' ', $parts ) : '';
            }
        }
        if ( $first_name ) { $first_name = sanitize_text_field( $first_name ); }
        if ( $last_name ) { $last_name = sanitize_text_field( $last_name ); }
        if ( $nickname ) { $nickname = sanitize_text_field( $nickname ); }
        $profile_url = $profile_url ? esc_url_raw( $profile_url ) : '';
        return [
            'provider' => $this->get_slug(),
            'id'       => (string) $id,
            'email'    => (string) ( $u['email'] ?? '' ),
            'name'     => $display ?: $nickname,
            'display_name' => $display,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'nickname'     => $nickname,
            'profile_url'  => $profile_url,
            'avatar'   => (string) ( $u['avatar_url'] ?? '' ),
            'avatar_url' => (string) ( $u['avatar_url'] ?? '' ),
            'raw'      => $data,
        ];
    }
}
