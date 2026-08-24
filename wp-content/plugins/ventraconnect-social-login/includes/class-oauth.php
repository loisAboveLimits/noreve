<?php
namespace VentraConnect\SocialLogin;

if (!defined('ABSPATH')) {
    exit;
}

use VentraConnect\SocialLogin\Providers\Abstract_Provider;
use VentraConnect\SocialLogin\Providers\Google;
use VentraConnect\SocialLogin\Providers\Facebook;
use VentraConnect\SocialLogin\Providers\Generic;
use VentraConnect\SocialLogin\Providers\Line;
use VentraConnect\SocialLogin\Providers\Twitch;
use VentraConnect\SocialLogin\Providers\Reddit;

/**
 * Common OAuth helpers and AJAX callbacks.
 */
class OAuth
{
    /**
     * Register AJAX routes for OAuth callback.
     */
    public static function register_ajax_routes()
    {
        add_action('wp_ajax_nopriv_ventraconnect_sl_oauth_callback', [__CLASS__, 'handle_callback']);
        add_action('wp_ajax_ventraconnect_sl_oauth_callback', [__CLASS__, 'handle_callback']);
        // Allow safe redirects across www/non-www variants of the site's host.
        add_filter('allowed_redirect_hosts', [__CLASS__, 'allowed_redirect_hosts']);
        add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
    }

    /**
     * Ensure core provider classes are loaded even if the autoloader fails.
     */
    private static function ensure_providers_loaded(): void
    {
        // If key provider classes already exist, assume autoloading is working.
        if (
            class_exists(\VentraConnect\SocialLogin\Providers\Facebook::class)
            && class_exists(\VentraConnect\SocialLogin\Providers\Google::class)
        ) {
            return;
        }

        $dir = __DIR__ . '/providers/';
        $files = array(
            'abstract-provider.php',
            'capabilities.php',
            'normalizer.php',
            'registry.php',
            'class-provider-facebook.php',
            'class-provider-google.php',
            'class-provider-generic.php',
            'class-provider-line.php',
            'class-provider-reddit.php',
            'class-provider-tiktok.php',
            'class-provider-twitch.php',
            'class-provider-twitter.php',
        );

        foreach ($files as $file) {
            $path = $dir . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Build provider instance by slug.
     */
    public static function provider($slug)
    {
        self::ensure_providers_loaded();

        $all = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderCreds();
        if (empty($all)) {
            $settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
            $all = (array) ($settings['provider_creds'] ?? []);
        }
        $creds = $all[$slug] ?? [];
        switch ($slug) {
            case 'google':
                return new Google($creds['client_id'] ?? '', $creds['client_secret'] ?? '');
            case 'facebook':
                return new Facebook($creds['client_id'] ?? '', $creds['client_secret'] ?? '');
            case 'github':
                return new Generic('github', 'GitHub', $creds['client_id'] ?? '', $creds['client_secret'] ?? '', 'https://github.com/login/oauth/authorize', 'https://github.com/login/oauth/access_token', 'https://api.github.com/user', ['read:user', 'user:email'], function ($d) {
                    $email = $d['email'] ?? '';
                    return ['id' => (string) ($d['id'] ?? ''), 'email' => (string) $email, 'name' => (string) ($d['name'] ?? ($d['login'] ?? '')), 'avatar' => (string) ($d['avatar_url'] ?? ''), 'raw' => $d];
                });
            case 'microsoft':
                return new Generic('microsoft', 'Microsoft', $creds['client_id'] ?? '', $creds['client_secret'] ?? '', 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize', 'https://login.microsoftonline.com/common/oauth2/v2.0/token', 'https://graph.microsoft.com/oidc/userinfo', ['openid', 'email', 'profile', 'User.Read', 'offline_access']);
            case 'linkedin':
                // Use LinkedIn OpenID Connect userinfo endpoint for simpler profile mapping.
                return new Generic('linkedin', 'LinkedIn', $creds['client_id'] ?? '', $creds['client_secret'] ?? '', 'https://www.linkedin.com/oauth/v2/authorization', 'https://www.linkedin.com/oauth/v2/accessToken', 'https://api.linkedin.com/v2/userinfo', ['openid', 'email', 'profile']);
            case 'slack':
                $auth_params = [];
                if (!empty($creds['team'])) {
                    $auth_params['team'] = $creds['team'];
                }
                return new Generic('slack', 'Slack', $creds['client_id'] ?? '', $creds['client_secret'] ?? '', 'https://slack.com/openid/connect/authorize', 'https://slack.com/api/openid.connect.token', 'https://slack.com/api/openid.connect.userInfo', ['openid', 'email', 'profile'], null, $auth_params);
            case 'amazon':
                return new Generic('amazon', 'Amazon', $creds['client_id'] ?? '', $creds['client_secret'] ?? '', 'https://www.amazon.com/ap/oa', 'https://api.amazon.com/auth/o2/token', 'https://api.amazon.com/user/profile', ['profile']);
            case 'yahoo':
                return new Generic('yahoo', 'Yahoo', $creds['client_id'] ?? '', $creds['client_secret'] ?? '', 'https://api.login.yahoo.com/oauth2/request_auth', 'https://api.login.yahoo.com/oauth2/get_token', 'https://api.login.yahoo.com/openid/v1/userinfo', ['openid', 'email', 'profile']);
            case 'wordpress':
                return new Generic('wordpress', 'WordPress.com', $creds['client_id'] ?? '', $creds['client_secret'] ?? '', 'https://public-api.wordpress.com/oauth2/authorize', 'https://public-api.wordpress.com/oauth2/token', 'https://public-api.wordpress.com/rest/v1/me', ['global']);
            case 'discord':
                return new Generic('discord', 'Discord', $creds['client_id'] ?? '', $creds['client_secret'] ?? '', 'https://discord.com/api/oauth2/authorize', 'https://discord.com/api/oauth2/token', 'https://discord.com/api/users/@me', ['identify', 'email']);
            case 'spotify':
                return new Generic('spotify', 'Spotify', $creds['client_id'] ?? '', $creds['client_secret'] ?? '', 'https://accounts.spotify.com/authorize', 'https://accounts.spotify.com/api/token', 'https://api.spotify.com/v1/me', ['user-read-email']);
            case 'line':
                return new Line($creds['client_id'] ?? '', $creds['client_secret'] ?? '');
            case 'twitch':
                return new Twitch($creds['client_id'] ?? '', $creds['client_secret'] ?? '');
            case 'reddit':
                return new Reddit($creds['client_id'] ?? '', $creds['client_secret'] ?? '');
            case 'twitter':
                return new \VentraConnect\SocialLogin\Providers\Twitter($creds['client_id'] ?? '', $creds['client_secret'] ?? '');
            case 'tiktok':
                return new \VentraConnect\SocialLogin\Providers\TikTok($creds['client_id'] ?? '', $creds['client_secret'] ?? '');
            default:
                return null;
        }
    }

    /**
     * Generate and store state.
     */
    public static function generate_state($provider, $extra = array())
    {
        $provider = sanitize_key((string) $provider);
        $payload = array(
            'provider' => $provider,
            'user' => get_current_user_id(),
            'issued' => time(),
        );
        if (is_array($extra) && !empty($extra)) {
            $payload = array_merge($payload, $extra);
        }
        if (defined('VENTRACONNECT_PRO') && VENTRACONNECT_PRO && function_exists('\VentraConnect\SocialLogin\Auth\ventraconnect_sl_build_signed_state')) {
            return \VentraConnect\SocialLogin\Auth\ventraconnect_sl_build_signed_state($payload);
        }
        $nonce = wp_generate_uuid4();
        $hash = wp_hash($nonce . '|' . $provider . '|' . (int) is_user_logged_in());
        $data = array_merge(
            array(
                'nonce' => $nonce,
                'provider' => $provider,
                'time' => time(),
            ),
            is_array($extra) ? $extra : []
        );
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::setTransient('ventraconnect_sl_state_' . $hash, $data, MINUTE_IN_SECONDS * 10);
        if ('microsoft' === $provider) {
            return self::build_state_envelope($provider, $hash);
        }
        return $hash;
    }

    /**
     * Verify state.
     */
    public static function verify_state($state, $provider)
    {
        $provider = sanitize_key((string) $provider);
        if ('' === (string) $state) {
            return false;
        }

        $envelope = self::parse_state_envelope($state);
        if ($envelope['is_envelope']) {
            $nonce = $envelope['nonce'];
            $envelope_provider = $envelope['provider'];
            if ($envelope_provider) {
                if ($provider && $provider !== $envelope_provider) {
                    return false;
                }
                if (!$provider) {
                    $provider = $envelope_provider;
                }
            }
            if (!$nonce) {
                return false;
            }
            $data = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getTransient('ventraconnect_sl_state_' . $nonce);
            if (empty($data)) {
                return false;
            }
            if (!empty($provider) && isset($data['provider']) && $data['provider'] !== $provider) {
                return false;
            }
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteTransient('ventraconnect_sl_state_' . $nonce);
            return $data;
        }

        if (defined('VENTRACONNECT_PRO') && VENTRACONNECT_PRO && function_exists('\VentraConnect\SocialLogin\Auth\ventraconnect_sl_parse_and_verify_state')) {
            $data = \VentraConnect\SocialLogin\Auth\ventraconnect_sl_parse_and_verify_state((string) $state);
            if (empty($data)) {
                return false;
            }
            if (isset($data['provider']) && $provider && $data['provider'] !== $provider) {
                return false;
            }
            return $data;
        }

        $data = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getTransient('ventraconnect_sl_state_' . $state);
        if (empty($data)) {
            return false;
        }
        $valid = (isset($data['provider']) && (!$provider || $data['provider'] === $provider));
        if ($valid) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteTransient('ventraconnect_sl_state_' . $state);
            return $data;
        }
        return false;
    }

    private static function build_state_envelope(string $provider, string $nonce): string
    {
        $payload = array(
            'provider' => $provider,
            'action' => 'ventraconnect_sl_oauth_callback',
            'nonce' => $nonce,
        );
        $json = wp_json_encode($payload);
        if (!is_string($json) || '' === $json) {
            return $nonce;
        }
        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    private static function parse_state_envelope(string $state): array
    {
        $result = array(
            'is_envelope' => false,
            'nonce' => '',
            'provider' => '',
            'action' => '',
        );
        if ('' === (string) $state) {
            return $result;
        }
        $padded = strtr((string) $state, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder) {
            $padded .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode($padded, true);
        if (false === $decoded) {
            return $result;
        }
        $data = json_decode($decoded, true);
        if (!is_array($data) || empty($data['nonce'])) {
            return $result;
        }
        $result['is_envelope'] = true;
        $result['nonce'] = (string) $data['nonce'];
        $result['provider'] = isset($data['provider']) ? sanitize_key($data['provider']) : '';
        $result['action'] = isset($data['action']) ? (string) $data['action'] : '';
        $result['raw'] = $data;
        return $result;
    }

    /**
     * Check if a redirect URL contains a "new account blocked" error.
     *
     * We parse the query string and normalize multiple variants
     * (vcsl_err, vcs_ld_err, etc.) into one canonical decision.
     *
     * @param string $redirect_url Redirect URL passed to the popup bridge.
     * @return bool True if the URL indicates a blocked new account.
     */
    private static function redirect_has_blocked_error($redirect_url)
    {
        if (!is_string($redirect_url) || '' === $redirect_url) {
            return false;
        }

        $parts = wp_parse_url($redirect_url);
        if (empty($parts['query'])) {
            return false;
        }

        $query = array();
        parse_str($parts['query'], $query);
        if (empty($query) || !is_array($query)) {
            return false;
        }

        // Accepted query keys that might carry our error.
        $keys = array(
            'vcsl_err',
            'vcs_ld_err',
            'vcs_bp_err',
            'ventraconnect_sl_err',
        );

        // Accepted blocked values (normalize variants).
        $blocked_values = array(
            'new_account_blocked',
            'core_new_account_blocked',
            'ventraconnect_sl_new_accounts_disabled',
        );

        foreach ($keys as $key) {
            if (!empty($query[$key]) && in_array($query[$key], $blocked_values, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get redirect URI for provider.
     */
    public static function redirect_uri($provider)
    {
        $provider = sanitize_key((string) $provider);
        $rest_capable = array('microsoft', 'tiktok');
        if (in_array($provider, $rest_capable, true)) {
            $rest = rest_url('ventraconnect_sl/v1/oauth/' . $provider);
            if ($rest) {
                return $rest;
            }
            if ('microsoft' === $provider) {
                // Fallback for environments without REST; maintain legacy behaviour.
                return home_url('/wp-admin/admin-ajax.php?action=ventraconnect_sl_oauth_callback&provider=microsoft');
            }
        }
        // Use home_url host to avoid cookie domain mismatches between site_url and home_url.
        return home_url('/wp-admin/admin-ajax.php?action=ventraconnect_sl_oauth_callback&provider=' . rawurlencode($provider));
    }

    public static function register_rest_routes()
    {
        register_rest_route(
            'ventraconnect_sl/v1',
            '/oauth/(?P<provider>[a-z0-9_-]+)',
            array(
                'methods' => array(\WP_REST_Server::READABLE, \WP_REST_Server::CREATABLE),
                'callback' => [__CLASS__, 'rest_callback'],
                'permission_callback' => '__return_true',
            )
        );
    }

    public static function rest_callback(\WP_REST_Request $request)
    {
        $params  = $request->get_params();
        $method  = $request->get_method();

        // Provider from route param.
        $provider = sanitize_key( (string) $request->get_param( 'provider' ) );

        // Recover provider from state envelope if missing.
        if ( ! $provider && ! empty( $params['state'] ) ) {
            $envelope = self::parse_state_envelope( (string) $params['state'] );
            if ( ! empty( $envelope['provider'] ) ) {
                $provider = sanitize_key( (string) $envelope['provider'] );
            }
        }

        // Whitelist known providers and ignore obviously-invalid ones.
        $known_providers = array(
            'facebook',
            'google',
            'amazon',
            'twitter',
            'microsoft',
            'yahoo',
            'wordpress',
            'slack',
            'discord',
            'linkedin',
            'spotify',
            'line',
            'tiktok',
            'github',
            'twitch',
            'reddit',
            'magic_link',
            'otp_email',
        );

        if ( '' === $provider || ! in_array( $provider, $known_providers, true ) ) {
            // Public endpoint, but ignore obviously-invalid providers.
            return rest_ensure_response( array( 'handled' => false ) );
        }

        // Mirror sanitized provider into superglobals for handle_callback().
        // IMPORTANT:
        // We intentionally modify $_REQUEST/$_GET/$_POST here so that the shared
        // OAuth::handle_callback() flow can work for both:
        //  - Classic callback URLs (wp-login.php, admin-ajax.php), and
        //  - This REST API endpoint (ventraconnect_sl/v1/oauth/<provider>).
        //
        // The $provider value has already been sanitized and validated against the
        // allowed provider list, so this does not introduce a new injection vector.
        // This is a controlled bridge to keep handle_callback() logic unified.
        $_REQUEST['provider'] = $provider;
        if ( 'GET' === $method ) {
            $_GET['provider'] = $provider;
        } else {
            $_POST['provider'] = $provider;
        }

        // Only mirror a small allowlist of parameters used by handle_callback().
        $allowed = array( 'state', 'code', 'error', 'redirect_to', 'ventraconnect_sl_ctx' );
        foreach ( $params as $key => $value ) {
            if ( 'provider' === $key || ! is_scalar( $value ) || ! in_array( $key, $allowed, true ) ) {
                continue;
            }
            $value = (string) $value;
            $_REQUEST[ $key ] = $value;
            if ( 'GET' === $method ) {
                $_GET[ $key ] = $value;
            } else {
                $_POST[ $key ] = $value;
            }
        }

        self::handle_callback();

        return rest_ensure_response( array( 'handled' => true ) );
    }

    /**
     * Handle OAuth callback.
     */
    public static function handle_callback()
    {
        $settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $debug = !empty($settings['debug_mode']);
        // Accept params from GET or POST (some providers return form_post)
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $provider = isset($_REQUEST['provider']) ? sanitize_text_field(wp_unslash($_REQUEST['provider'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $state = isset($_REQUEST['state']) ? sanitize_text_field(wp_unslash($_REQUEST['state'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $error = isset($_REQUEST['error']) ? sanitize_text_field(wp_unslash($_REQUEST['error'])) : '';

        // OAuth GET callback: use 'state' instead of nonce; verified below.
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $code = sanitize_text_field(wp_unslash($_GET['code'] ?? ''));
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $state_cb = sanitize_text_field(wp_unslash($_GET['state'] ?? ''));
        // Prepare sanitized request context
        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $redirect_to = '';
        if ( isset( $_REQUEST['redirect_to'] ) ) {
             // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized immediately below.
            $raw_redirect = wp_unslash( (string) $_REQUEST['redirect_to'] );
            $redirect_to  = esc_url_raw( $raw_redirect );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'] ?? ''));
        $ref = wp_get_referer();
        if (!$ref) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            $ref = esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'] ?? ''));
        }
        // Also accept form_post code
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $code_post = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : '';

        if (empty($state) && !empty($state_cb)) {
            $state = $state_cb;
        }
        $state_envelope = self::parse_state_envelope($state);
        if (empty($provider) && !empty($state_envelope['provider'])) {
            $provider = $state_envelope['provider'];
            $_REQUEST['provider'] = $provider;
            $_GET['provider'] = $provider;
        }
        if (empty($provider)) {
            wp_die(esc_html__('Invalid request.', 'ventraconnect-social-login'));
        }
        // Verify state before using code
        $state_data = self::verify_state($state, $provider);
        if (!$state_data) {
            wp_die(esc_html__('Invalid state.', 'ventraconnect-social-login'));
        }
        $is_verify = !empty($state_data['verify']);
        // Compute a best-effort return URL from state/request for downstream flows (e.g. Lifter blocked-account handling).
        $return_to = '';
        if (!empty($state_data['return'])) {
            $return_to = esc_url_raw((string) $state_data['return']);
        } elseif (!empty($state_data['redirect_to'])) {
            $return_to = esc_url_raw((string) $state_data['redirect_to']);
        } elseif ( '' !== $redirect_to ) {
            $return_to = $redirect_to;
        }
        $flow_context = isset($state_data['context']) ? sanitize_key($state_data['context']) : '';
        $popup_mode = !empty($state_data['ventraconnect_sl_popup'] ?? null);
        if ('' === $flow_context) {
            $flow_context = sanitize_key($state_data['ventraconnect_sl_ctx'] ?? '');
        }
        // Derive redirect from state (first from 'redirect', then 'redirect_to').
        $state_redirect = '';

        if ( isset( $state_data['redirect'] ) && is_string( $state_data['redirect'] ) ) {
            $state_redirect = esc_url_raw( $state_data['redirect'] );
        }

        if ( ! $state_redirect && isset( $state_data['redirect_to'] ) && is_string( $state_data['redirect_to'] ) ) {
            $state_redirect = esc_url_raw( $state_data['redirect_to'] );
        }

        // Only override when no higher-priority redirect is already set.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $existing_redirect = $redirect_to;

        if ( $state_redirect && '' === $existing_redirect ) {
            $_REQUEST['redirect_to'] = $state_redirect;
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if ('' !== $flow_context) {
            // Populate canonical ctx keys only (server writes canonical-only).
            $_REQUEST['ventraconnect_sl_ctx'] = $flow_context;
            $_GET['ventraconnect_sl_ctx'] = $flow_context;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $sl_ctx = '';
        if ( isset( $_REQUEST['ventraconnect_sl_ctx'] ) ) {
            $sl_ctx = sanitize_text_field( wp_unslash( (string) $_REQUEST['ventraconnect_sl_ctx'] ) );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if (!empty($error)) {
            if ($is_verify) {
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::setTransient('ventraconnect_sl_admin_notice', array(
                    'type' => 'error',
                    'message' => sprintf(
                        /* translators: 1: Provider label, 2: Error message. */
                        __('Verification failed for %1$s: %2$s', 'ventraconnect-social-login'),
                        ucfirst($provider),
                        $error
                    )
                ), MINUTE_IN_SECONDS * 2);
                if (!empty($return_to)) {
                    wp_safe_redirect($return_to);
                } else {
                    wp_safe_redirect(admin_url());
                }
                exit;
            }
            wp_die(esc_html__('Authorization error: ', 'ventraconnect-social-login') . esc_html($error));
        }

        $prov = self::provider($provider);
        if (!$prov) {
            wp_die(esc_html__('Unknown provider.', 'ventraconnect-social-login'));
        }
        $redirect_uri = self::redirect_uri($provider);
        $code_to_use = '' !== $code ? $code : $code_post;
        $token = $prov->exchange_code_for_token($code_to_use, $redirect_uri);
        if (is_wp_error($token)) {
            if ($is_verify) {
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::setTransient('ventraconnect_sl_admin_notice', array('type' => 'error', 'message' => $token->get_error_message()), MINUTE_IN_SECONDS * 2);
                if (!empty($return_to)) {
                    wp_safe_redirect($return_to);
                } else {
                    wp_safe_redirect(admin_url());
                }
                exit;
            }
            wp_die(esc_html($token->get_error_message()));
        }
        if ('tiktok' === $provider) {
            if (is_array($token)) {
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::setTransient('ventraconnect_sl_last_tiktok_token', $token, MINUTE_IN_SECONDS * 10);
            }
        }
        $profile = $prov->fetch_user_profile($token['access_token'] ?? '');
        if (is_wp_error($profile)) {
            if ($is_verify) {
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::setTransient('ventraconnect_sl_admin_notice', array('type' => 'error', 'message' => $profile->get_error_message()), MINUTE_IN_SECONDS * 2);
                if (!empty($return_to)) {
                    wp_safe_redirect($return_to);
                } else {
                    wp_safe_redirect(admin_url());
                }
                exit;
            }
            wp_die(esc_html($profile->get_error_message()));
        }

        // If this was a verification-only flow, do not link/login users.
        if ($is_verify) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::setTransient('ventraconnect_sl_admin_notice', array(
                'type' => 'success',
                'message' => sprintf(
                    /* translators: 1: Provider label. */
                    __('%1$s settings verified successfully.', 'ventraconnect-social-login'),
                    ucfirst($provider)
                )
            ), MINUTE_IN_SECONDS * 2);
            if (!empty($return_to)) {
                wp_safe_redirect($return_to);
            } else {
                wp_safe_redirect(admin_url());
            }
            exit;
        }

        // If this provider account is already linked to a user, log them in immediately
        // before handling any missing-email flow.
        if (!empty($profile['id'])) {
            $pid_existing = sanitize_text_field((string) $profile['id']);
            if ($pid_existing) {
                $links = new User_Links();
                $linked_user_id = $links->find_user_by_connection($provider, $pid_existing);
                if ($linked_user_id) {
                    if (is_array($token)) {
                        $links->link_provider((int) $linked_user_id, $provider, $pid_existing, (array) $profile, (array) $token);
                    }

                    do_action('ventraconnect_sl_login_success', (int) $linked_user_id, $provider, $profile, false);

                    $user = get_user_by('id', (int) $linked_user_id);
                    if ($user && !is_wp_error($user)) {
                        if (function_exists('ob_get_length') && ob_get_length()) {
                            @ob_clean();
                        }
                        wp_set_current_user((int) $linked_user_id);
                        wp_set_auth_cookie((int) $linked_user_id, true, is_ssl());
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core WordPress hook.
                        do_action('wp_login', $user->user_login, $user);
                    }

                    // Unified redirect resolution
                    $target = \VentraConnect\SocialLogin\RedirectResolver::compute((int) $linked_user_id, $provider, $state_data, $_REQUEST); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback validated via state token.

                    if ($popup_mode) {
                        nocache_headers();
                        $charset = get_bloginfo('charset');
                        header('Content-Type: text/html; charset=' . $charset);
                        $redir = esc_url_raw($target);
                        echo '<!DOCTYPE html><html><head><meta charset="' . esc_attr($charset) . '"><title>Authenticating…</title></head><body>';
                        echo '<script>(function(){var r=' . wp_json_encode($redir) . ';var payload={type:"vcs_oauth_result",success:true,redirect:r};try{if(window.opener&&!window.opener.closed){try{window.opener.postMessage(payload,"*");}catch(e){}try{window.opener.location.href=r;}catch(e){}setTimeout(function(){try{window.close();}catch(e){}},500);}else{try{window.location.href=r;}catch(e){}}}catch(e){try{window.location.href=r;}catch(e2){}}}());</script>';
                        echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url($target) . '"></noscript></body></html>';
                        exit;
                    }
                    wp_safe_redirect($target);
                    exit;
                }
            }
        }

        // Pro-only: If provider did not return an email and admin prefers to ask user, defer account creation.
        if (\VentraConnect\SocialLogin\Pro_Gates::is_pro()) {
            $emails_settings = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption('ventraconnect_sl_emails_settings', []);
            // Default disabled unless explicitly set to 'ask_user'
            $action_missing = isset($emails_settings['handle_missing_email_action']) ? (string) $emails_settings['handle_missing_email_action'] : '';
            $profile_email = isset($profile['email']) ? sanitize_email((string) $profile['email']) : '';
            if ('ask_user' === $action_missing && '' === $profile_email) {
                // Store enriched payload and redirect to the finish-signup endpoint.
                $key = wp_generate_password(20, false);
                // Build a suggested username from provider data (not guaranteed unique; will be uniquified later).
                $suggested_username = '';
                if (!empty($profile['username'])) {
                    $suggested_username = sanitize_user((string) $profile['username'], true);
                } else {
                    $pid_s = (string) ($profile['id'] ?? '');
                    $base = $pid_s ? ($provider . '_' . $pid_s) : '';
                    $suggested_username = sanitize_user($base, true);
                }
                // Ensure we use the most up-to-date redirect_to value, after OAuth state/return handling.
                // phpcs:disable WordPress.Security.NonceVerification.Recommended
                $redirect_to = '';
                if ( isset( $_REQUEST['redirect_to'] ) ) {
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
                    $raw_redirect = wp_unslash( (string) $_REQUEST['redirect_to'] );
                    $redirect_to  = esc_url_raw( $raw_redirect );
                }
                // phpcs:enable WordPress.Security.NonceVerification.Recommended
                $payload = [
                    'provider' => (string) $provider,
                    'profile' => [
                        'provider' => (string) ($profile['provider'] ?? $provider),
                        'id' => (string) ($profile['id'] ?? ''),
                        'name' => (string) ($profile['name'] ?? ''),
                        'username' => (string) ($profile['username'] ?? ''),
                        'avatar' => (string) ($profile['avatar'] ?? ''),
                    ],
                    'suggested_username' => (string) $suggested_username,
                    'redirect_to' => $redirect_to,
                    'popup' => $popup_mode ? 1 : 0,
                ];
                // Added: preserve integration context for RedirectResolver on the missing-email endpoint.
                if (isset($flow_context) && '' !== $flow_context) {
                    $payload['ventraconnect_sl_ctx'] = (string) $flow_context; // Added key ventraconnect_sl_ctx.
                }
                // Added: distinguish login vs link flows and capture user_id for link flows.
                $mode = 'login';
                if (is_user_logged_in()) {
                    $mode = 'link';
                    $payload['user_id'] = (int) get_current_user_id(); // Added key user_id for link flows.
                }
                $payload['mode'] = $mode; // Added key mode ('login' or 'link').

                \VentraConnect\SocialLogin\Admin\Settings\Persistence::setTransient('ventraconnect_sl_missing_email_' . $key, $payload, MINUTE_IN_SECONDS * 60);
                // Build endpoint URL
                $endpoint = function_exists('\\VentraConnect\\SocialLogin\\Pro\\Emails\\ventraconnect_sl_missing_email_url')
                    ? \VentraConnect\SocialLogin\Pro\Emails\ventraconnect_sl_missing_email_url($key)
                    : add_query_arg(
                        [
                            'ventraconnect_sl_complete_email' => '1',
                            'ventraconnect_sl_complete_key' => rawurlencode($key),
                        ],
                        home_url('/')
                    );
                wp_safe_redirect($endpoint);
                exit;
            }
        }

        // Link or create user (do not output here). Note: User_Links also sets cookies; we'll set them explicitly below too.
        $link_result = (new User_Links())->link_or_login_user($profile, is_array($token) ? $token : []);
        if (is_wp_error($link_result)) {
            $code = $link_result->get_error_code();
            $message = $link_result->get_error_message();

            // Global "new accounts disabled for this login form" error.
            if ('ventraconnect_sl_new_accounts_disabled' === $code) {
                // Determine login context.
                $ctx = (string) $sl_ctx;

                // Base redirect target.
                if ('buddypress_login' === $ctx && function_exists('bp_get_signup_page')) {
                    $redirect = bp_get_signup_page();
                } else {
                    // Prefer explicit return_to, then referer, then home.
                    $redirect = $return_to;

                    if (!$redirect) {
                        $redirect = wp_get_referer();
                    }

                    if (!$redirect) {
                        $redirect = home_url('/');
                    }
                }

                // Always set a generic error flag so auth.js can show the popup.
                $redirect = add_query_arg(
                    'vcsl_err',
                    'new_account_blocked',
                    $redirect
                );

                // Context-specific flags (backwards compatibility only).
                if (in_array($ctx, array('wp_login', 'shortcode'), true)) {
                    $redirect = add_query_arg(
                        'ventraconnect_sl_err',
                        'new_account_blocked',
                        $redirect
                    );
                } elseif ('learndash_login' === $ctx) {
                    $redirect = add_query_arg(
                        'vcs_ld_err',
                        'new_account_blocked',
                        $redirect
                    );
                } elseif ('buddypress_login' === $ctx) {
                    $redirect = add_query_arg(
                        'vcs_bp_err',
                        'new_account_blocked',
                        $redirect
                    );
                }

                // Respect popup bridge mode vs normal redirect.
                if ($popup_mode) {
                    self::popup_bridge_redirect($redirect);
                }

                wp_safe_redirect($redirect);
                exit;
            }

            // WooCommerce-specific: new account creation blocked on login form.
            if ('ventraconnect_sl_wc_new_account_blocked' === $code) {
                // Base redirect target: prefer explicit return_to, then referer, then home.
                $redirect = $return_to;

                if (!$redirect) {
                    $redirect = wp_get_referer();
                }

                if (!$redirect) {
                    $redirect = home_url('/');
                }

                // Always set a generic error flag so auth.js / popup bridge can show the overlay.
                $redirect = add_query_arg(
                    'vcsl_err',
                    'new_account_blocked',
                    $redirect
                );

                // Optional generic flag for compatibility with existing views.
                $redirect = add_query_arg(
                    'ventraconnect_sl_err',
                    'new_account_blocked',
                    $redirect
                );

                if ($popup_mode) {
                    self::popup_bridge_redirect($redirect);
                }

                wp_safe_redirect($redirect);
                exit;
            }

            // LifterLMS-specific: new account creation blocked on login form.
            if ('ventraconnect_sl_lifter_new_account_blocked' === $code && function_exists('llms_add_notice')) {
                llms_add_notice(
                    __('We couldn’t find a student account for this social login. Please register for an account first, then sign in with social.', 'ventraconnect-social-login'),
                    'error'
                );

                $redirect = $return_to;

                if (!$redirect) {
                    $redirect = wp_get_referer();
                }

                if (!$redirect && function_exists('llms_get_user_dashboard_url')) {
                    $redirect = llms_get_user_dashboard_url(0, 'dashboard');
                }

                if (!$redirect) {
                    $redirect = home_url('/');
                }

                if ($popup_mode) {
                    self::popup_bridge_redirect($redirect);
                }

                wp_safe_redirect($redirect);
                exit;
            }

            // LearnPress-specific: new account creation blocked on login form.
            if ('ventraconnect_sl_learnpress_new_account_blocked' === $code) {
                $redirect = $return_to;

                if (!$redirect) {
                    $redirect = wp_get_referer();
                }

                if (!$redirect) {
                    $redirect = home_url('/');
                }

                // Flag LearnPress login error so the LP integration can display a notice.
                $redirect = add_query_arg(
                    'vcs_lp_err',
                    'new_account_blocked',
                    $redirect
                );

                if ($popup_mode) {
                    self::popup_bridge_redirect($redirect);
                }

                wp_safe_redirect($redirect);
                exit;
            }

            // MemberPress-specific: new account creation blocked on login form.
            if ('ventraconnect_sl_memberpress_new_account_blocked' === $code) {
                $redirect = $return_to;

                if (!$redirect) {
                    $redirect = wp_get_referer();
                }

                if (!$redirect) {
                    $redirect = home_url('/');
                }

                // Flag MemberPress login error so the integration can display a notice.
                $redirect = add_query_arg(
                    'vcs_mp_err',
                    'new_account_blocked',
                    $redirect
                );

                if ($popup_mode) {
                    self::popup_bridge_redirect($redirect);
                }

                wp_safe_redirect($redirect);
                exit;
            }

            // Fallback for all other errors: preserve existing behavior.
            wp_die(
                esc_html($message),
                esc_html__('Social Login Error', 'ventraconnect-social-login')
            );
        }

        $is_new_user = null;
        if (is_array($link_result)) {
            $user_id = isset($link_result['user_id']) ? (int) $link_result['user_id'] : 0;
            $is_new_user = isset($link_result['is_new_user']) ? (bool) $link_result['is_new_user'] : false;
        } else {
            $user_id = (int) $link_result;
        }

        if ($user_id <= 0) {
            wp_safe_redirect(home_url('/'));
            exit;
        }

        // Track last-used social provider for identity card rendering
        if ($user_id > 0 && !empty($provider)) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta((int) $user_id, 'ventraconnect_sl_last_login_provider', sanitize_key((string) $provider));
        }

        do_action('ventraconnect_sl_login_success', $user_id, $provider, $profile, (bool) $is_new_user);

        if ($is_new_user) {
            if (function_exists('\\VentraConnect\\SocialLogin\\Pro\\Emails\\ventraconnect_sl_send_new_account_email_for_context')) {
                // Determine WooCommerce context via unified ventraconnect_sl_ctx flag.
                // phpcs:disable WordPress.Security.NonceVerification.Recommended
                $sl_ctx = '';
                if (isset($_REQUEST['ventraconnect_sl_ctx'])) {
                    $sl_ctx = sanitize_text_field(wp_unslash((string) $_REQUEST['ventraconnect_sl_ctx']));
                }
                // phpcs:enable WordPress.Security.NonceVerification.Recommended

                $is_woo_ctx = ('' !== $sl_ctx) && (0 === strpos($sl_ctx, 'wc_') || 'checkout' === $sl_ctx);

                $ctx = [
                    'provider'       => (string) $provider,
                    'is_new_user'    => true,
                    'is_woocommerce' => $is_woo_ctx,
                ];

                call_user_func(
                    '\\VentraConnect\\SocialLogin\\Pro\\Emails\\ventraconnect_sl_send_new_account_email_for_context',
                    (int) $user_id,
                    $ctx
                );
            } else {
                // Free-only fallback: trigger default WordPress "New User" notifications.
                $user_id_int = (int) $user_id;
                if ($user_id_int > 0) {
                    if (function_exists('wp_send_new_user_notifications')) {
                        wp_send_new_user_notifications($user_id_int, 'both');
                    } else {
                        // Back-compat for older WordPress versions.
                        wp_new_user_notification($user_id_int, null, 'both');
                    }
                }
            }
        } else {
            // Existing user linking a provider: allow Pro handler to send link/security emails if enabled.
            if (function_exists('\\VentraConnect\\SocialLogin\\Pro\\Emails\\ventraconnect_sl_handle_social_registration_emails')) {
                call_user_func('\\VentraConnect\\SocialLogin\\Pro\\Emails\\ventraconnect_sl_handle_social_registration_emails', $user_id, $provider, (bool) $is_new_user, false);
            }
        }

        // Implement strict post-auth sequence: set auth cookies, pick safe redirect, and never end on wp-login.php.
        $user = get_user_by('id', $user_id);
        if (!$user || is_wp_error($user)) {
            wp_safe_redirect(home_url('/'));
            exit;
        }

        if (function_exists('ob_get_length') && ob_get_length()) {
            @ob_clean();
        }

        // 2) Set auth state BEFORE any output
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true, is_ssl());
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core WordPress hook.
        do_action('wp_login', $user->user_login, $user);

        // 3) Choose redirect target via unified resolver
        $final_redirect = \VentraConnect\SocialLogin\RedirectResolver::compute($user_id, $provider, $state_data, $_REQUEST); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback validated via state token.
        $final_redirect = wp_validate_redirect( (string) $final_redirect, home_url( '/' ) );

        // On success, always remove any stale VentraConnect/LD error or notice flags.
        $final_redirect = remove_query_arg(
            array(
                'vcsl_err',
                'vcs_ld_err',
                'ventraconnect_sl_notice',
                'ventraconnect_sl_err',
            ),
            $final_redirect
        );

        // If popup mode was used, redirect the opener and close this window.
        if ($popup_mode) {
            // Bridge HTML with postMessage to notify opener, then auto-close the popup.
            // Avoid any extra whitespace before this output.
            nocache_headers();
            $charset = get_bloginfo('charset');
            header('Content-Type: text/html; charset=' . $charset);
            $redir = esc_url_raw($final_redirect);
            echo '<!DOCTYPE html><html><head><meta charset="' . esc_attr($charset) . '"><title>Authenticating…</title></head><body style="margin:0;padding:0;background:#f5f5f5"><div style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:system-ui,-apple-system,sans-serif;color:#666;font-size:14px;">Authenticating…</div>';
            echo '<script>' . "\n";
            echo '(function(){' . "\n";
            echo '  var payload = { type: "vcs_oauth_result", success: true, redirect: ' . wp_json_encode($redir) . ' };' . "\n";
            echo '  try {' . "\n";
            echo '    if (window.opener && !window.opener.closed) {' . "\n";
            echo '      try { window.opener.postMessage(payload, "*"); } catch (e) {}' . "\n";
            echo '      try { window.opener.location.href = ' . wp_json_encode($redir) . '; } catch (e) {}' . "\n";
            echo '      setTimeout(function(){ try { window.close(); } catch (e) {} }, 500);' . "\n";
            echo '    } else {' . "\n";
            echo '      try { window.location.href = ' . wp_json_encode($redir) . '; } catch (e) {}' . "\n";
            echo '    }' . "\n";
            echo '  } catch (e) {' . "\n";
            echo '    try { window.location.href = ' . wp_json_encode($redir) . '; } catch (e2) {}' . "\n";
            echo '  }' . "\n";
            echo '})();' . "\n";
            echo '</script>' . "\n";
            echo '<noscript><p><a href="' . esc_url($final_redirect) . '">Click here to continue</a></p></noscript>';
            echo '</body></html>';
            exit;
        }

        // Normal redirect flow
        wp_safe_redirect($final_redirect);
        exit;
    }

    /**
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) { error_log( sprintf( '[VCS][OAuth] normal redirect: user=%d provider=%s final_redirect=%s', $user_id, $provider, $final_redirect ) ); }
     */
    public static function allowed_redirect_hosts($hosts)
    {
        $hosts = is_array($hosts) ? $hosts : [];
        $home = wp_parse_url(home_url(), PHP_URL_HOST);
        if ($home) {
            $hosts[] = $home;
            if (0 === strpos($home, 'www.')) {
                $hosts[] = substr($home, 4);
            } else {
                $hosts[] = 'www.' . $home;
            }
        }
        return array_values(array_unique($hosts));
    }

    /**
     * Output popup bridge HTML that redirects opener to URL and closes the popup.
     */
    public static function popup_bridge_redirect(string $redirect_url)
    {
        nocache_headers();
        $charset = get_bloginfo('charset');
        header('Content-Type: text/html; charset=' . $charset);
        $has_blocked_err = self::redirect_has_blocked_error($redirect_url);
        $redir = esc_url_raw($redirect_url);
        echo '<!DOCTYPE html><html><head><meta charset="' . esc_attr($charset) . '"><title>Authenticating…</title></head><body style="margin:0;padding:0;background:#f5f5f5"><div style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:system-ui,-apple-system,sans-serif;color:#666;font-size:14px;">Authenticating…</div>';
        echo '<script>' . "\n";
        echo '(function(){' . "\n";
        if ($has_blocked_err) {
            echo '  var payload = { type: "vcs_oauth_result", success: false, redirect: ' . wp_json_encode($redir) . ' };' . "\n";
            echo '  payload.vcsl_err = "new_account_blocked";' . "\n";
        } else {
            echo '  var payload = { type: "vcs_oauth_result", success: true, redirect: ' . wp_json_encode($redir) . ' };' . "\n";
        }
        echo '  try {' . "\n";
        echo '    if (window.opener && !window.opener.closed) {' . "\n";
        echo '      try { window.opener.postMessage(payload, "*"); } catch (e) {}' . "\n";
        if (!$has_blocked_err) {
            echo '      try { window.opener.location.href = ' . wp_json_encode($redir) . '; } catch (e) {}' . "\n";
        }
        echo '      setTimeout(function(){ try { window.close(); } catch (e) {} }, 500);' . "\n";
        echo '    } else {' . "\n";
        echo '      try { window.location.href = ' . wp_json_encode($redir) . '; } catch (e) {}' . "\n";
        echo '    }' . "\n";
        echo '  } catch (e) {' . "\n";
        echo '    try { window.location.href = ' . wp_json_encode($redir) . '; } catch (e2) {}' . "\n";
        echo '  }' . "\n";
        echo '})();' . "\n";
        echo '</script>' . "\n";
        echo '<noscript><p><a href="' . esc_url($redir) . '">Click here to continue</a></p></noscript>';
        echo '</body></html>';
        exit;
    }
}
