<?php
namespace VentraConnect\SocialLogin;
use function VentraConnect\SocialLogin\Helpers\can_create_new_user_for_method;
use function VentraConnect\SocialLogin\Helpers\can_create_new_account;

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( '\VentraConnect\SocialLogin\Providers\VCS_Provider_Data', false ) ) {
    $ventraconnect_sl_normalizer_path = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ? VENTRACONNECT_SL_PLUGIN_DIR . 'includes/providers/normalizer.php' : __DIR__ . '/providers/normalizer.php';
    if ( file_exists( $ventraconnect_sl_normalizer_path ) ) {
        require_once $ventraconnect_sl_normalizer_path;
    }
}

/**
 * Link/unlink provider with WordPress users (normalized storage).
 */
class User_Links {
    const META_CONNECTIONS        = '_ventraconnect_sl_linked_providers';
    const META_CONNECTIONS_LEGACY = '_ventraconnect_sl_connections';
    const META_PRIMARY            = '_ventraconnect_sl_primary_provider';
    const EPHEMERAL_PROVIDERS     = [ 'magic_link', 'otp_email' ];

    /**
     * Optional helper to sanitize and verify unlink requests coming via GET.
     * Does not change existing behavior unless explicitly invoked by callers.
     */
    public static function maybe_verify_unlink_from_query(): void {
        // Sanitize GET params
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized immediately below.
        $unlink_raw = isset( $_GET['ventraconnect_sl_unlink'] ) ? wp_unslash( (string) $_GET['ventraconnect_sl_unlink'] ) : '';
        $unlink_key = sanitize_key( $unlink_raw );

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized immediately via sanitize_text_field() and used only in wp_verify_nonce checks.
        $nonce_raw         = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce'] ) ) : '';
        $nonce_legacy_raw  = isset( $_GET['_wpnonce_legacy'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['_wpnonce_legacy'] ) ) : '';
        $nonce             = $nonce_raw;
        $nonce_legacy      = $nonce_legacy_raw;

        // Verify nonce before unlinking if requested
        if ( $unlink_key ) {
            if ( is_admin() && ! current_user_can( 'read' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'ventraconnect-social-login' ) );
            }
            // Verify only canonical unlink actions.
            $ok = (
                ( ! empty( $nonce ) && (
                    wp_verify_nonce( $nonce, 'ventraconnect_sl_unlink_' . $unlink_key ) || wp_verify_nonce( $nonce, 'ventraconnect_sl_unlink' )
                ) )
                || ( ! empty( $nonce_legacy ) && wp_verify_nonce( $nonce_legacy, 'ventraconnect_sl_unlink_' . $unlink_key ) )
            );
            if ( ! $ok ) {
                wp_die( esc_html__( 'Security check failed.', 'ventraconnect-social-login' ) );
            }
        }
    }

    private static function normalize_provider_slug( $provider ): string {
        $slug = strtolower( sanitize_key( (string) $provider ) );
        if ( 'x' === $slug ) {
            return 'twitter';
        }
        return $slug;
    }

    private static function allow_store_provider( $provider ): bool {
        $provider = self::normalize_provider_slug( $provider );
        if ( empty( $provider ) ) { return false; }
        return ! in_array( $provider, self::EPHEMERAL_PROVIDERS, true );
    }

    private static function is_explicit_true( $value ): bool {
        return true === $value || 1 === $value || '1' === $value || 'true' === strtolower( (string) $value );
    }

    private static function is_email_trusted_for_account_matching( string $provider, array $profile ): bool {
        $provider = self::normalize_provider_slug( $provider );
        $raw      = is_array( $profile['raw'] ?? null ) ? (array) $profile['raw'] : [];

        switch ( $provider ) {
            case 'google':
            case 'microsoft':
            case 'linkedin':
            case 'slack':
            case 'yahoo':
            case 'line':
                return (
                    ( array_key_exists( 'email_verified', $profile ) && self::is_explicit_true( $profile['email_verified'] ) )
                    || ( array_key_exists( 'email_verified', $raw ) && self::is_explicit_true( $raw['email_verified'] ) )
                );

            case 'discord':
                return (
                    ( array_key_exists( 'email_verified', $profile ) && self::is_explicit_true( $profile['email_verified'] ) )
                    || ( array_key_exists( 'verified', $raw ) && self::is_explicit_true( $raw['verified'] ) )
                );

            case 'github':
                return array_key_exists( 'email_verified', $profile ) && self::is_explicit_true( $profile['email_verified'] );
        }

        return false;
    }

    /**
     * Link profile to user or create a new user; log them in.
     * Profile keys: provider,id,email,name,avatar,raw
     * @param array $profile
     * @param array $tokens
     * @return array|\WP_Error When successful returns ['user_id' => int, 'is_new_user' => bool]
     */
    public function link_or_login_user( $profile, array $tokens = [] ) {
        $is_new_user = false;
        $meta_source = [];
        if ( isset( $profile['meta'] ) && is_array( $profile['meta'] ) ) {
            $meta_source = (array) $profile['meta'];
        }

        $email_raw = $profile['email'] ?? '';
        $email     = sanitize_email( $email_raw );
        $provider  = self::normalize_provider_slug( $profile['provider'] ?? '' );
        $pid       = sanitize_text_field( $profile['id'] ?? '' );
        $profile['provider'] = $provider;

        $meta_email_granted = $meta_source['email_granted'] ?? false;
        $email_granted = filter_var( $meta_email_granted, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
        if ( null === $email_granted ) {
            $email_granted = (bool) $meta_email_granted;
        }
        $email_error = isset( $meta_source['email_error'] ) ? sanitize_text_field( (string) $meta_source['email_error'] ) : '';
        if ( empty( $pid ) || empty( $provider ) ) {
            $this->log_x_attempt( $provider, $email_granted, $email_error, false );
            return new \WP_Error( 'ventraconnect_sl_missing_profile', __( 'Missing profile data.', 'ventraconnect-social-login' ) );
        }

        $allow_new_account = can_create_new_account();

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        $sl_ctx = '';
        if ( isset( $_REQUEST['ventraconnect_sl_ctx'] ) ) {
            $sl_ctx = sanitize_text_field( wp_unslash( (string) $_REQUEST['ventraconnect_sl_ctx'] ) );
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $ctx = $sl_ctx;

        $current_user_id = get_current_user_id();
        $user_id = $this->find_user_by_connection( $provider, $pid );
        if ( ! $user_id && 0 === $current_user_id && $email && self::is_email_trusted_for_account_matching( $provider, $profile ) ) {
            $user = get_user_by( 'email', $email );
            if ( $user ) {
                $user_id = (int) $user->ID;
            }
        }

        // Profile-linking contexts: if no user found yet but a user is logged in,
        // link this provider to the current user instead of creating a new account.
        if ( ! $user_id ) {
            if ( $current_user_id > 0 ) {
                $is_profile_link = false;
                $ctx_str        = (string) $ctx;

                // Explicit core WP profile context.
                if ( 'wp_profile' === $ctx_str ) {
                    $is_profile_link = true;
                } else {
                    // Generic rule: any ctx containing "profile" or "account"
                    // is treated as an account/profile page, not a login form.
                    if ( false !== strpos( $ctx_str, 'profile' ) || false !== strpos( $ctx_str, 'account' ) ) {
                        $is_profile_link = true;
                    }
                }

                if ( $is_profile_link ) {
                    $user_id = $current_user_id;
                }
            }
        }

        $is_x = ( 'twitter' === $provider );

        if ( ! $user_id && $block_lifter_new_account ) {
            return new \WP_Error(
                'ventraconnect_sl_lifter_new_account_blocked',
                __( 'We couldn’t find a student account for this social login. Please register for an account first, then sign in with social.', 'ventraconnect-social-login' )
            );
        }

        if ( ! $user_id && $block_learnpress_new_account ) {
            return new \WP_Error(
                'ventraconnect_sl_learnpress_new_account_blocked',
                __( 'We couldnƒ?Tt find a student account for this social login. Please use the LearnPress registration or checkout form to create your account first, then sign in with social.', 'ventraconnect-social-login' )
            );
        }

          if ( ! $user_id && $block_memberpress_new_account ) {
              return new \WP_Error(
                  'ventraconnect_sl_memberpress_new_account_blocked',
                  __( 'We couldn\'t find an existing MemberPress account for this social login. Please complete your membership signup first, then sign in with social.', 'ventraconnect-social-login' )
              );
          }

          if ( ! $user_id ) {
            $guardrail_args = [
                'email'    => $email,
                'provider' => $provider,
                'profile'  => $profile,
            ];

            $can_create = can_create_new_user_for_method( 'social', $ctx, $guardrail_args );

            if ( ! $can_create ) {
                $this->log_x_attempt( $provider, $email_granted, $email_error, false );
                return new \WP_Error(
                    'ventraconnect_sl_new_accounts_disabled',
                    __( 'You can’t create a new account with social login on this screen. Please register using the site’s sign-up form first, then sign in with your social account.', 'ventraconnect-social-login' )
                );
            }

            if ( empty( $email ) && $is_x ) {
                if ( ! $allow_new_account ) {
                    $this->log_x_attempt( $provider, $email_granted, $email_error, false );
                    return new \WP_Error( 'ventraconnect_sl_wc_new_account_blocked', __( 'Creating a new WooCommerce account via social login is disabled. Please sign in with an existing account.', 'ventraconnect-social-login' ) );
                }
                $user_id = $this->find_user_by_connection( $provider, $pid );
                if ( ! $user_id ) {
                    $username = $this->unique_login_from_username( $profile['username'] ?? '', 'x_' . $pid );
                    $args = [
                        'user_login'   => $username,
                        'user_pass'    => ventraconnect_sl_generate_internal_account_password(),
                        'user_email'   => '',
                        'display_name' => sanitize_text_field( $profile['name'] ?? ( $profile['username'] ?? $username ) ),
                    ];
                    $role = $this->resolve_social_login_role();
                    if ( $role ) {
                        $args['role'] = $role;
                    }
                    $user_id = wp_insert_user( $args );
                    if ( is_wp_error( $user_id ) ) {
                        $this->log_x_attempt( $provider, $email_granted, $email_error, false );
                        return $user_id;
                    }
                    ventraconnect_sl_mark_passwordless_account_created( (int) $user_id, 'social_' . sanitize_key( (string) $provider ), $ctx );
                    $is_new_user = true;
                }
            } else {
                if ( ! $allow_new_account ) {
                    $this->log_x_attempt( $provider, $email_granted, $email_error, false );
                    return new \WP_Error( 'ventraconnect_sl_wc_new_account_blocked', __( 'Creating a new WooCommerce account via social login is disabled. Please sign in with an existing account.', 'ventraconnect-social-login' ) );
                }
                $user_id = $this->find_user_by_connection( $provider, $pid );
                if ( ! $user_id ) {
                    $username = $this->unique_login_from_email_or_name( $email, $profile['name'] ?? ( $provider . '_' . $pid ) );
                    $args     = [
                        'user_login'   => $username,
                        'user_pass'    => ventraconnect_sl_generate_internal_account_password(),
                        'user_email'   => $email,
                        'display_name' => sanitize_text_field( $profile['name'] ?? $username ),
                    ];
                    $role = $this->resolve_social_login_role();
                    if ( $role ) {
                        $args['role'] = $role;
                    }
                    $user_id = wp_insert_user( $args );
                    if ( is_wp_error( $user_id ) ) {
                        $this->log_x_attempt( $provider, $email_granted, $email_error, false );
                        return $user_id;
                    }
                    ventraconnect_sl_mark_passwordless_account_created( (int) $user_id, 'social_' . sanitize_key( (string) $provider ), $ctx );
                    $is_new_user = true;
                }
            }
        }

        if ( self::allow_store_provider( $provider ) ) {
            $profile['email'] = $email;
            $this->link_provider( $user_id, $provider, $pid, $profile, $tokens );
        }

        $current_user_before = get_current_user_id();
        $should_set_auth     = ( 0 === $current_user_before ) || ( $user_id === $current_user_before );

        if ( $should_set_auth ) {
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id, true );
            $userdata = get_userdata( $user_id );
            if ( $userdata ) {
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core WordPress hook.
                do_action( 'wp_login', $userdata->user_login, $userdata );
            }
        }

        $this->log_x_attempt( $provider, $email_granted, $email_error, false );

        return [
            'user_id'     => (int) $user_id,
            'is_new_user' => $is_new_user,
        ];
    }

    /**
     * Upsert provider connection for a user (idempotent per provider).
     */
    public function link_provider( int $user_id, string $provider, string $provider_user_id, array $profile = [], array $tokens = [] ) {
        $provider = self::normalize_provider_slug( $provider );
        if ( ! self::allow_store_provider( $provider ) ) {
            return;
        }
        $provider_user_id = sanitize_text_field( $provider_user_id );

        $owner = $this->find_user_by_connection( $provider, $provider_user_id );
        if ( $owner && $owner !== $user_id ) {
            $this->unlink( $owner, $provider );
        }

        $connections = $this->read_connections( $user_id );
        $by_slug = [];
        foreach ( $connections as $conn ) {
            $slug = self::normalize_provider_slug( $conn['provider'] ?? '' );
            if ( ! $slug ) { continue; }
            $by_slug[ $slug ] = $conn;
        }
        $now = current_time( 'mysql' );
        $existing = $by_slug[ $provider ] ?? null;
        $linked_at = $existing['linked_at'] ?? $now;

        $normalized = [];
        if ( class_exists( '\VentraConnect\SocialLogin\Providers\VCS_Provider_Data', false ) ) {
            $normalized = \VentraConnect\SocialLogin\Providers\VCS_Provider_Data::normalize( $provider, (array) $profile );
        }

        $incoming_flags = [];
        if ( ! empty( $profile['flags'] ) && is_array( $profile['flags'] ) ) {
            foreach ( $profile['flags'] as $flag ) {
                $incoming_flags[] = sanitize_key( $flag );
            }
        }
        $existing_flags   = (array) ( $existing['profile']['snapshot']['flags'] ?? [] );
        $normalized_flags = (array) ( $normalized['flags'] ?? [] );
        $merged_flags = array_values( array_unique( array_filter( array_merge( $normalized_flags, $incoming_flags, $existing_flags ) ) ) );

        $profile_payload = [
            'name'   => sanitize_text_field( $normalized['display_name'] ?? $profile['name'] ?? ( $existing['profile']['name'] ?? '' ) ),
            'avatar' => esc_url_raw( $normalized['avatar_url'] ?? $profile['avatar'] ?? ( $existing['profile']['avatar'] ?? '' ) ),
            'snapshot' => [
                'id'      => (string) ( $normalized['id'] ?? $profile['id'] ?? ( $existing['profile']['snapshot']['id'] ?? '' ) ),
                'email'   => (string) ( $normalized['email'] ?? $profile['email'] ?? ( $existing['profile']['snapshot']['email'] ?? '' ) ),
                'locale'  => (string) ( $normalized['locale'] ?? ( $existing['profile']['snapshot']['locale'] ?? '' ) ),
                'flags'   => $merged_flags,
            ],
        ];

        $tokens_payload = ( is_array( $tokens ) && ! empty( $tokens ) ) ? $tokens : ( $existing['tokens'] ?? [] );
        $email = sanitize_email( $profile['email'] ?? '' );
        if ( '' === $email ) {
            $email = sanitize_email( $existing['email'] ?? '' );
        }

        $by_slug[ $provider ] = [
            'provider'         => $provider,
            'provider_user_id' => $provider_user_id,
            'email'            => $email,
            'profile'          => $profile_payload,
            'tokens'           => $tokens_payload,
            'linked_at'        => $linked_at,
            'last_login_at'    => $now,
        ];

        $this->write_connections( $user_id, array_values( $by_slug ) );

        $primary = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_PRIMARY, true, '' );
        if ( '' === $primary ) {
            $primary = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_last_login_provider', true, '' );
        }
        if ( ! $primary ) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_PRIMARY, $provider );
        }

        // Detect whether this provider link is new for this user.
        // If there was no existing row before upserting, treat as a new link.
        $is_new_link = empty( $existing );

        $event_profile = [
            'provider_user_id' => $provider_user_id,
            'email'            => $email,
            'profile'          => $profile_payload,
            'tokens'           => $tokens_payload,
            // Expose "is_new_link" both as a dedicated arg and inside the payload
            // so 3-arg listeners can still read it.
            'is_new_link'      => $is_new_link,
        ];

        /**
         * Fires after a provider has been linked to a user account.
         *
         * @since 1.1.0
         */
        do_action( 'ventraconnect_sl_social_linked', $user_id, $provider, $event_profile, $is_new_link );
        do_action( 'ventraconnect_sl_after_user_linked', $user_id, $provider, $by_slug[ $provider ] );
    }

    /**
     * Return normalized provider connections for a user.
     */
    public function get_connections( int $user_id ): array {
        return $this->read_connections( $user_id );
    }

    /**
     * Find user id by provider and provider_user_id (normalized scan).
     */
    public function find_user_by_connection( $provider, $provider_user_id ) {
        $provider = self::normalize_provider_slug( $provider );
        if ( ! self::allow_store_provider( $provider ) ) {
            return 0;
        }
        $provider_user_id = sanitize_text_field( $provider_user_id );
        $args = [
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for user lookup by provider, only runs on demand, not on every page load.
            'meta_query' => [
                [
                    'key'     => self::META_CONNECTIONS,
                    'compare' => 'EXISTS',
                ],
            ],
            'number' => 50,
            'fields' => 'ids',
        ];
        $users = get_users( $args );
        foreach ( $users as $uid ) {
            $conns = $this->read_connections( (int) $uid );
            foreach ( $conns as $conn ) {
                if ( self::normalize_provider_slug( $conn['provider'] ?? '' ) === $provider && ( $conn['provider_user_id'] ?? '' ) === $provider_user_id ) {
                    return (int) $uid;
                }
            }
        }
        return 0;
    }

    /**
     * Explicit unlink for settings UI.
     */
    public function unlink( $user_id, $provider ) {
        $provider = self::normalize_provider_slug( $provider );
        $connections = $this->read_connections( $user_id );
        $connections = array_values( array_filter( $connections, function( $c ) use ( $provider ) {
            return self::normalize_provider_slug( $c['provider'] ?? '' ) !== $provider;
        } ) );
        $this->write_connections( $user_id, $connections );
        $primary = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_PRIMARY, true, '' );
        if ( '' === $primary ) {
            $primary = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_last_login_provider', true, '' );
        }
        if ( strtolower( $primary ) === $provider ) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, self::META_PRIMARY );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_last_login_provider' );
        }
    }

    private function woo_linking_rules(): array {
        $defaults = [
            'allow_new_account'  => true,
        ];
        $ctx_raw = filter_input( INPUT_POST, 'ventraconnect_sl_ctx', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE );
        if ( null === $ctx_raw || '' === $ctx_raw || false === $ctx_raw ) {
            $ctx_raw = filter_input( INPUT_GET, 'ventraconnect_sl_ctx', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE );
        }
        if ( null === $ctx_raw || '' === $ctx_raw || false === $ctx_raw ) {
            return $defaults;
        }
        $ctx_raw = is_string( $ctx_raw ) ? $ctx_raw : '';
        $ctx = sanitize_text_field( wp_unslash( $ctx_raw ) );
        if ( 'checkout' !== $ctx ) { return $defaults; }
        $settings = $this->get_wc_settings();
        $linking = (array) ( $settings['linking'] ?? [] );
        $defaults['allow_new_account']  = ! empty( $linking['allow_new_account'] );
        return $defaults;
    }

    /**
     * Determine which role should be applied to newly created users.
     */
    private function resolve_social_login_role(): ?string {
        if ( class_exists( '\VentraConnect\SocialLogin\Pro\Helpers\UserRoles' ) ) {
            $role = \VentraConnect\SocialLogin\Pro\Helpers\UserRoles::resolve_default_social_role();
            if ( is_string( $role ) && $role !== '' ) {
                return $role;
            }
        }
        return null;
    }

    private function get_wc_settings(): array {
        if ( function_exists( '\VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings' ) ) {
            return \VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings();
        }
        return (array) apply_filters( 'ventraconnect_sl_wc_settings', [] );
    }

    /**
     * Build a unique username.
     */
    private function unique_login_from_email_or_name( $email, $fallback ) {
        $base = $email ? sanitize_user( current( explode( '@', $email ) ), true ) : sanitize_user( $fallback, true );
        if ( empty( $base ) ) { $base = 'user_' . wp_generate_password( 6, false ); }
        $login = $base;
        $i = 1;
        while ( username_exists( $login ) ) {
            $login = $base . '_' . $i++;
        }
        return $login;
    }

    private function unique_login_from_username( $username, $fallback ) {
        $base = sanitize_user( (string) $username, true );
        if ( '' === $base ) {
            return $this->unique_login_from_email_or_name( '', $fallback );
        }
        $login = $base;
        $i = 1;
        while ( username_exists( $login ) ) {
            $login = $base . '_' . $i++;
        }
        return $login;
    }

    private function log_x_attempt( string $provider, $email_granted, string $email_error, bool $used_placeholder ): void {
        if ( self::normalize_provider_slug( $provider ) !== 'twitter' ) {
            return;
        }
        $granted = filter_var( $email_granted, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
        if ( null === $granted ) {
            $granted = (bool) $email_granted;
        }
        Logger::auth( 'x', [
            'email_granted'    => $granted,
            'email_error'      => $email_error,
            'used_placeholder' => $used_placeholder,
        ] );
    }

    /**
     * Load and normalize connections for a user.
     */
    private function read_connections( int $user_id ): array {
        $current = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_CONNECTIONS, true, [] );
        if ( ! is_array( $current ) ) {
            $current = [];
        }
        $normalized = $this->normalize_connections( $current );
        if ( $normalized !== $current || ! metadata_exists( 'user', $user_id, self::META_CONNECTIONS ) ) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_CONNECTIONS, $normalized );
        }
        // Drop any legacy mirror if present.
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, self::META_CONNECTIONS_LEGACY );
        return $normalized;
    }

    private function write_connections( int $user_id, array $connections ): void {
        $normalized = $this->normalize_connections( $connections );
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_CONNECTIONS, $normalized );
    }

    private function normalize_connections( array $connections ): array {
        $by_slug = [];
        foreach ( $connections as $conn ) {
            $slug = self::normalize_provider_slug( $conn['provider'] ?? '' );
            if ( ! $slug || in_array( $slug, self::EPHEMERAL_PROVIDERS, true ) ) { continue; }
            $entry = [
                'provider'         => $slug,
                'provider_user_id' => sanitize_text_field( $conn['provider_user_id'] ?? '' ),
                'email'            => sanitize_email( $conn['email'] ?? '' ),
                'profile'          => [],
                'tokens'           => is_array( $conn['tokens'] ?? null ) ? $conn['tokens'] : [],
                'linked_at'        => $conn['linked_at'] ?? current_time( 'mysql' ),
                'last_login_at'    => $conn['last_login_at'] ?? $conn['linked_at'] ?? current_time( 'mysql' ),
            ];
            if ( ! empty( $conn['profile'] ) && is_array( $conn['profile'] ) ) {
                $entry['profile']['name']   = sanitize_text_field( $conn['profile']['name'] ?? '' );
                $entry['profile']['avatar'] = esc_url_raw( $conn['profile']['avatar'] ?? '' );
                $snapshot = is_array( $conn['profile']['snapshot'] ?? null ) ? $conn['profile']['snapshot'] : [];
                $entry['profile']['snapshot'] = [
                    'id'     => (string) ( $snapshot['id'] ?? '' ),
                    'email'  => (string) ( $snapshot['email'] ?? '' ),
                    'locale' => (string) ( $snapshot['locale'] ?? '' ),
                    'flags'  => array_values( array_unique( array_filter( (array) ( $snapshot['flags'] ?? [] ) ) ) ),
                ];
            }
            $existing = $by_slug[ $slug ] ?? null;
            if ( $existing ) {
                $existing_ts  = strtotime( $existing['last_login_at'] ?? '' ) ?: 0;
                $candidate_ts = strtotime( $entry['last_login_at'] ?? '' ) ?: 0;
                if ( $candidate_ts >= $existing_ts ) {
                    if ( empty( $entry['linked_at'] ) ) {
                        $entry['linked_at'] = $existing['linked_at'] ?? current_time( 'mysql' );
                    }
                    $by_slug[ $slug ] = $entry;
                }
            } else {
                $by_slug[ $slug ] = $entry;
            }
        }
        return array_values( $by_slug );
    }
}
