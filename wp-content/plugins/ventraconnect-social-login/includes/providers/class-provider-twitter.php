<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * X (Twitter) OAuth 2.0 with PKCE (no email by default).
 */
class Twitter extends Abstract_Provider {
    public function get_slug() { return 'twitter'; }
    public function get_label() { return 'X (Twitter)'; }

    private function pkce_verifier() {
        $bytes = random_bytes(32);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
    private function pkce_challenge($verifier) {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public function get_auth_url( $state, $redirect_uri ) {
        $verifier  = $this->pkce_verifier();
        $challenge = $this->pkce_challenge( $verifier );
        set_transient( 'ventraconnect_sl_pkce_' . $state, $verifier, MINUTE_IN_SECONDS * 10 );
        $scope = implode( ' ', apply_filters( 'ventraconnect_sl_oauth_scopes', [ 'tweet.read', 'users.read', 'offline.access' ], $this->get_slug() ) );
        $args = [
            'response_type'         => 'code',
            'client_id'             => $this->client_id,
            'redirect_uri'          => $redirect_uri,
            'scope'                 => $scope,
            'state'                 => $state,
            'code_challenge'        => $challenge,
            'code_challenge_method' => 'S256',
        ];
    return 'https://x.com/i/oauth2/authorize?' . http_build_query( $args, '', '&' );
    }

    public function exchange_code_for_token( $code, $redirect_uri ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth2 'state' param is used for CSRF protection per spec, not a WordPress nonce.
    $cb_state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
        $verifier = $cb_state ? get_transient( 'ventraconnect_sl_pkce_' . $cb_state ) : '';
        if ( ! $verifier ) {
            return new \WP_Error( 'ventraconnect_sl_pkce_missing', __( 'Missing PKCE code verifier for Twitter.', 'ventraconnect-social-login' ) );
        }
        delete_transient( 'ventraconnect_sl_pkce_' . $cb_state );
        $body = [
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'client_id'    => $this->client_id,
            'redirect_uri' => $redirect_uri,
            'code_verifier'=> $verifier,
        ];

        // Prepare headers. Use URL-encoded body for token endpoint.
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        // If a client secret is configured (confidential client), send HTTP Basic auth.
        if ( ! empty( $this->client_secret ) ) {
            $basic = base64_encode( $this->client_id . ':' . $this->client_secret );
            $headers['Authorization'] = 'Basic ' . $basic;
        }

        $resp = wp_remote_post( 'https://api.x.com/2/oauth2/token', [
            'body'    => http_build_query( $body ),
            'timeout' => 25,
            'headers' => $headers,
        ] );

        if ( is_wp_error( $resp ) ) {
            return $resp;
        }

        $raw = wp_remote_retrieve_body( $resp );
        $code_http = wp_remote_retrieve_response_code( $resp );
        $data = json_decode( $raw, true );

        // If token not present, return helpful debug message.
        if ( empty( $data['access_token'] ) ) {
            $msg = __( 'Failed to obtain access token from Twitter.', 'ventraconnect-social-login' );
            // Append response body for debugging when available.
            $details = is_string( $raw ) && trim( $raw ) ? ' Response: ' . $raw : '';
            return new \WP_Error( 'ventraconnect_sl_token_error', $msg . $details, [ 'http_code' => $code_http, 'response' => $data ] );
        }

        return $data;
    }

    public function fetch_user_profile( $access_token ) {
        $attempt_error = '';

        $with_email = $this->request_profile( $access_token, true );
        if ( is_wp_error( $with_email ) ) {
            return $with_email;
        }

        if ( empty( $with_email['success'] ) ) {
            $attempt_error = $with_email['error'] ?? '';
            $with_email = $this->request_profile( $access_token, false );
            if ( is_wp_error( $with_email ) ) {
                return $with_email;
            }
            if ( empty( $with_email['success'] ) ) {
                $message = $with_email['error'] ?? __( 'Failed to fetch profile from Twitter.', 'ventraconnect-social-login' );
                return new \WP_Error( 'ventraconnect_sl_profile_error', $message );
            }
        }

        $payload   = $with_email['body'];
        $meta      = $with_email['meta'];
        $user_data = isset( $payload['data'] ) ? (array) $payload['data'] : [];

        if ( empty( $user_data['id'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_profile_error', __( 'Failed to fetch profile from Twitter.', 'ventraconnect-social-login' ) );
        }

        $meta['email_error'] = $attempt_error;

        return [
            'provider' => $this->get_slug(),
            'id'       => (string) $user_data['id'],
            'email'    => (string) ( $user_data['email'] ?? '' ),
            'name'     => (string) ( $user_data['name'] ?? ( $user_data['username'] ?? '' ) ),
            'avatar'   => (string) ( $user_data['profile_image_url'] ?? '' ),
            'username' => (string) ( $user_data['username'] ?? '' ),
            'meta'     => $meta,
            'raw'      => $payload,
        ];
    }

    private function request_profile( $access_token, bool $with_email ) {
        $fields = [ 'id', 'name', 'username', 'profile_image_url' ];
        if ( $with_email ) {
            $fields[] = 'email';
        }
        $url = 'https://api.x.com/2/users/me?user.fields=' . implode( ',', $fields );
        $resp = wp_remote_get( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'        => 'application/json',
            ],
            'timeout' => 20,
        ] );

        if ( is_wp_error( $resp ) ) {
            return $resp;
        }

        $code = (int) wp_remote_retrieve_response_code( $resp );
        $body = json_decode( wp_remote_retrieve_body( $resp ), true );

        $success = ( $code >= 200 && $code < 300 && ! empty( $body['data']['id'] ) );
        $email   = (string) ( $body['data']['email'] ?? '' );

        $meta = [
            'email_requested' => (bool) $with_email,
            'email_granted'   => ( $with_email && '' !== $email ),
            'email_error'     => '',
        ];

        if ( ! $success ) {
            $meta['email_granted'] = false;
            $error_detail = '';
            if ( isset( $body['errors'][0]['detail'] ) ) {
                $error_detail = (string) $body['errors'][0]['detail'];
            } elseif ( isset( $body['detail'] ) ) {
                $error_detail = (string) $body['detail'];
            }
            return [
                'success' => false,
                'body'    => $body,
                'meta'    => $meta,
                'error'   => $error_detail ?: ( $code ? 'HTTP ' . $code : '' ),
            ];
        }

        if ( empty( $meta['email_granted'] ) ) {
            $meta['email_granted'] = false;
        }

        return [
            'success' => true,
            'body'    => $body,
            'meta'    => $meta,
        ];
    }
}
