<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Reddit OAuth 2.0 Provider.
 */
class Reddit extends Abstract_Provider {
    private function user_agent(): string {
        $version = defined( 'VENTRACONNECT_SL_VERSION' ) ? VENTRACONNECT_SL_VERSION : '1.0.0';
        return 'VentraConnectSocialLogin/' . $version . ' (+https://ventraconnect.com/)';
    }

    public function get_slug() { return 'reddit'; }
    public function get_label() { return 'Reddit'; }

    public function get_auth_url( $state, $redirect_uri ) {
        $scopes = implode( ' ', apply_filters( 'ventraconnect_sl_oauth_scopes', $this->get_scopes(), $this->get_slug() ) );
        $args = [
            'client_id'    => $this->client_id,
            'response_type'=> 'code',
            'state'        => $state,
            'redirect_uri' => $redirect_uri,
            'duration'     => 'permanent',
            'scope'        => $scopes,
        ];
        return 'https://www.reddit.com/api/v1/authorize?' . http_build_query( $args, '', '&' );
    }

    public function get_scopes() {
        // Reddit requires manual approval for the `email` scope. Default to `identity`
        // so the flow works out-of-the-box; developers can add `email` via the
        // OAuth scopes filter once their app is approved.
        return [ 'identity' ];
    }

    public function exchange_code_for_token( $code, $redirect_uri ) {
        $body = [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => $redirect_uri,
        ];
        $auth = base64_encode( $this->client_id . ':' . (string) $this->client_secret );
        $headers = [
            'Authorization' => 'Basic ' . $auth,
            'User-Agent'    => $this->user_agent(),
        ];
        $resp = wp_remote_post( 'https://www.reddit.com/api/v1/access_token', [
            'body'    => $body,
            'timeout' => 20,
            'headers' => $headers,
        ] );
        if ( is_wp_error( $resp ) ) {
            return $resp;
        }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['access_token'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_token_error', __( 'Failed to obtain access token from Reddit.', 'ventraconnect-social-login' ) );
        }
        return $data;
    }

    public function fetch_user_profile( $access_token ) {
        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'User-Agent'    => $this->user_agent(),
            'Accept'        => 'application/json',
        ];
        $resp = wp_remote_get( 'https://oauth.reddit.com/api/v1/me', [
            'headers' => $headers,
            'timeout' => 15,
        ] );
        if ( is_wp_error( $resp ) ) {
            return $resp;
        }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( ! is_array( $data ) || empty( $data['id'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_profile_error', __( 'Failed to fetch profile from Reddit.', 'ventraconnect-social-login' ) );
        }

        $email = (string) ( $data['email'] ?? '' );
        $username = (string) ( $data['name'] ?? '' );
        $avatar = (string) ( $data['icon_img'] ?? $data['snoovatar_img'] ?? '' );
        $profile_url = $username ? 'https://www.reddit.com/user/' . $username . '/' : '';

        $meta = [
            'email_granted'          => ( '' !== $email ),
            'email_verified_default' => (bool) ( $data['has_verified_email'] ?? ( '' !== $email ) ),
            'email_error'            => '',
        ];

        return [
            'provider'       => $this->get_slug(),
            'id'             => (string) $data['id'],
            'email'          => $email,
            'email_verified' => (bool) ( $data['has_verified_email'] ?? ( '' !== $email ) ),
            'name'           => $username,
            'profile_url'    => $profile_url,
            'avatar'         => $avatar,
            'meta'           => $meta,
            'raw'            => $data,
        ];
    }
}
