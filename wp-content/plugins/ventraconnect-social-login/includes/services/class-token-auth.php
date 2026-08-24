<?php
namespace VentraConnect\SocialLogin\Services;

use WP_Error;
use function VentraConnect\SocialLogin\Helpers\can_create_new_account;
use function VentraConnect\SocialLogin\Helpers\can_create_new_user_for_method;
use function VentraConnect\SocialLogin\Helpers\is_pro_integration_context;
use function VentraConnect\SocialLogin\Helpers\compute_magic_link_redirect_for_user;
use function VentraConnect\SocialLogin\Helpers\compute_otp_redirect_for_user;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Token-based authentication (Magic Link + OTP Email).
 * - Stores tokens in DB table {prefix}ventraconnect_sl_auth_tokens
 * - Sends emails, enforces throttles and attempts
 * - Provides AJAX endpoints and a magic link consumer via query args
 */
class Token_Auth {
    const TABLE = 'ventraconnect_sl_auth_tokens';
    const OTP_SECRET_PREFIX = 'otp_hmac_sha256:';
    const OTP_VERIFY_RATE_WINDOW = 600;
    const OTP_VERIFY_EMAIL_LIMIT = 10;
    const OTP_VERIFY_IP_LIMIT = 30;
    const OTP_VERIFY_EMAIL_IP_LIMIT = 10;

    /** @var bool */
    private static $routes_registered = false;

    public static function table_name(): string {
        global $wpdb; return $wpdb->prefix . self::TABLE;
    }

    public static function maybe_create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $table = self::table_name();
        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(190) NOT NULL,
            `mode` VARCHAR(32) NOT NULL,
            `secret` VARCHAR(190) NOT NULL,
            `context` VARCHAR(64) DEFAULT '' NOT NULL,
            `ip` VARCHAR(45) DEFAULT '' NOT NULL,
            `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
            `max_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
            `expires_at` DATETIME NOT NULL,
            `used_at` DATETIME NULL,
            `meta` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `email_mode` (`email`,`mode`),
            KEY `secret_mode` (`secret`,`mode`),
            KEY `expires_at` (`expires_at`),
            KEY `used_at` (`used_at`)
        ) $charset_collate;";
        dbDelta( $sql );
    }

    public static function maybe_schedule_cron() {
        if ( ! wp_next_scheduled( 'ventraconnect_sl_purge_tokens_daily' ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ventraconnect_sl_purge_tokens_daily' );
        }
    }

    private static function ensure_table_ready() {
        static $ready = null;
        if ( null !== $ready ) {
            return $ready;
        }
        global $wpdb;
        $table = self::table_name();
        $pattern = $wpdb->esc_like( $table );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SHOW TABLES is a schema check; caching is handled by the static $ready flag.
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $pattern ) );
        if ( $exists !== $table ) {
            self::maybe_create_table();
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- re-checking after table creation; static flag prevents repeated queries.
            $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $pattern ) );
        }
        $ready = ( $exists === $table );
        return $ready;
    }

        public static function purge_expired() {
            global $wpdb;

            // Internal tokens table; table name is not user input — safe to interpolate.
            $table      = self::table_name();
            $table_safe = esc_sql( $table );

            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM `{$table_safe}` WHERE (`expires_at` < %s) OR (`used_at` IS NOT NULL)",
                    current_time( 'mysql' )
                )
            );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

    public static function register_routes() {
        if ( self::$routes_registered ) {
            return;
        }
        self::$routes_registered = true;

        self::ensure_table_ready();
        add_action( 'wp_ajax_nopriv_ventraconnect_sl_magic_link_send', [ __CLASS__, 'ajax_magic_link_send' ] );
        add_action( 'wp_ajax_ventraconnect_sl_magic_link_send', [ __CLASS__, 'ajax_magic_link_send' ] );
        add_action( 'wp_ajax_nopriv_ventraconnect_sl_otp_send', [ __CLASS__, 'ajax_otp_send' ] );
        add_action( 'wp_ajax_ventraconnect_sl_otp_send', [ __CLASS__, 'ajax_otp_send' ] );
        add_action( 'wp_ajax_nopriv_ventraconnect_sl_otp_verify', [ __CLASS__, 'ajax_otp_verify' ] );
        add_action( 'wp_ajax_ventraconnect_sl_otp_verify', [ __CLASS__, 'ajax_otp_verify' ] );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_consume_magic_link' ] );
    }

    /* ---------- Helpers ---------- */
    private static function get_client_ip(): string {
        foreach ( [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ] as $k ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized immediately below via sanitize_text_field.
            if ( ! empty( $_SERVER[ $k ] ) ) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $raw = wp_unslash( (string) $_SERVER[ $k ] );
                $val = sanitize_text_field( $raw );
                $v   = trim( explode( ',', $val )[0] );

                if ( filter_var( $v, FILTER_VALIDATE_IP ) ) {
                    return $v;
                }
            }
        }

        return '';
    }

    private static function normalize_otp_email( string $email ): string {
        return strtolower( sanitize_email( trim( $email ) ) );
    }

    public static function revoke_legacy_otp_tokens(): bool {
        if ( ! self::ensure_table_ready() ) {
            return false;
        }

        global $wpdb;
        $table      = self::table_name();
        $table_safe = esc_sql( $table );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE `{$table_safe}` SET `used_at` = %s WHERE `mode` = %s AND `used_at` IS NULL AND ( `secret` IS NULL OR `secret` NOT LIKE %s )",
                current_time( 'mysql' ),
                'otp_email',
                $wpdb->esc_like( self::OTP_SECRET_PREFIX ) . '%'
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return false !== $result;
    }

    private static function throttle_key( string $mode, string $email_or_ip ): string {
        return 'ventraconnect_sl_throttle_' . md5( strtolower( $mode . '|' . $email_or_ip ) );
    }

    private static function otp_verify_rate_key( string $scope, string $value ): string {
        return 'ventraconnect_sl_otp_verify_' . md5( strtolower( $scope . '|' . $value ) );
    }

    private static function is_pro_enabled(): bool {
        return defined( 'VCS_PRO_ACTIVE' ) && VCS_PRO_ACTIVE === true;
    }

    private static function settings_for( string $mode ): array {
        $s = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        if ( 'magic_link' === $mode ) return (array) ( $s['magic_link'] ?? [] );
        if ( 'otp_email' === $mode ) return (array) ( $s['otp_email'] ?? [] );
        return [];
    }

    private static function can_send_now( string $mode, string $email, int $throttle ): array {
        $ip = self::get_client_ip();
        $now = time();
        $ek = self::throttle_key( $mode, 'e:' . $email );
        $ik = self::throttle_key( $mode, 'i:' . $ip );
        $e_until = (int) get_transient( $ek );
        $i_until = (int) get_transient( $ik );
        $wait = max( 0, max( $e_until - $now, $i_until - $now ) );
        if ( $wait > 0 ) return [ false, $wait ];
        if ( $throttle > 0 ) {
            set_transient( $ek, $now + $throttle, $throttle );
            set_transient( $ik, $now + $throttle, $throttle );
        }
        return [ true, 0 ];
    }

    private static function otp_secret_hash( string $code ): string {
        return self::OTP_SECRET_PREFIX . hash_hmac( 'sha256', trim( $code ), wp_salt( 'auth' ) );
    }

    private static function is_otp_secret_hash( string $secret ): bool {
        return 0 === strpos( $secret, self::OTP_SECRET_PREFIX );
    }

    private static function otp_verify_rate_keys( string $email, bool $include_email = true ): array {
        $ip    = self::get_client_ip();
        $email = self::normalize_otp_email( $email );

        $keys = array(
            array( self::otp_verify_rate_key( 'ip', $ip ), self::OTP_VERIFY_IP_LIMIT ),
        );

        if ( $include_email ) {
            $keys[] = array( self::otp_verify_rate_key( 'email', $email ), self::OTP_VERIFY_EMAIL_LIMIT );
            $keys[] = array( self::otp_verify_rate_key( 'email_ip', $email . '|' . $ip ), self::OTP_VERIFY_EMAIL_IP_LIMIT );
        }

        return $keys;
    }

    private static function is_otp_verify_rate_limited( string $email ): bool {
        foreach ( self::otp_verify_rate_keys( $email ) as $entry ) {
            if ( (int) get_transient( $entry[0] ) >= (int) $entry[1] ) {
                return true;
            }
        }

        return false;
    }

    private static function record_otp_verify_failure( string $email, bool $include_email = true ): void {
        foreach ( self::otp_verify_rate_keys( $email, $include_email ) as $entry ) {
            $key   = $entry[0];
            $count = (int) get_transient( $key );
            set_transient( $key, $count + 1, self::OTP_VERIFY_RATE_WINDOW );
        }
    }

    private static function insert_token( string $mode, string $email, string $secret, array $opts ) {
        if ( ! self::ensure_table_ready() ) {
            return false;
        }
        global $wpdb; $table = self::table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- intentional write; token inserts cannot use a cache layer.
        $inserted = $wpdb->insert( $table, [
            'email'        => 'otp_email' === $mode ? self::normalize_otp_email( $email ) : strtolower( $email ),
            'mode'         => $mode,
            'secret'       => $secret,
            'context'      => (string) ( $opts['context'] ?? '' ),
            'ip'           => self::get_client_ip(),
            'attempts'     => 0,
            'max_attempts' => (int) ( $opts['max_attempts'] ?? 0 ),
            'expires_at'   => gmdate( 'Y-m-d H:i:s', time() + (int) ( $opts['expiry'] ?? 600 ) ),
            'meta'         => maybe_serialize( (array) ( $opts['meta'] ?? [] ) ),
            'created_at'   => current_time( 'mysql' ),
        ], [ '%s','%s','%s','%s','%s','%d','%d','%s','%s','%s' ] );
        if ( false === $inserted ) {
            return false;
        }
        return (int) $wpdb->insert_id;
    }

    private static function revoke_active_otp_tokens( string $email ): bool {
        if ( ! self::ensure_table_ready() ) {
            return false;
        }

        global $wpdb;
        $table      = self::table_name();
        $table_safe = esc_sql( $table );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE `{$table_safe}` SET `used_at` = %s WHERE `mode` = %s AND `email` = %s AND `used_at` IS NULL",
                current_time( 'mysql' ),
                'otp_email',
                self::normalize_otp_email( $email )
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return false !== $result;
    }

    private static function find_active_otp_token( string $email ) {
        if ( ! self::ensure_table_ready() ) {
            return null;
        }

        global $wpdb;
        $table      = self::table_name();
        $table_safe = esc_sql( $table );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$table_safe}` WHERE `mode` = %s AND `email` = %s AND `used_at` IS NULL AND `expires_at` >= %s ORDER BY `created_at` DESC, `id` DESC LIMIT 1",
                'otp_email',
                self::normalize_otp_email( $email ),
                gmdate( 'Y-m-d H:i:s' )
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return $row ?: null;
    }

        private static function find_token( string $mode, string $secret, ?string $email = null ) {
        if ( ! self::ensure_table_ready() ) {
            return null;
        }

        global $wpdb;
        $table      = self::table_name();
        $table_safe = esc_sql( $table );

        // For OTP, bind token lookup to the email as well for tighter scoping.
        if ( 'otp_email' === $mode && null !== $email ) {
            $email = sanitize_email( $email );

            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `{$table_safe}` WHERE `mode` = %s AND `email` = %s AND `secret` = %s LIMIT 1",
                    $mode,
                    $email,
                    $secret
                ),
                ARRAY_A
            );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

            return $row ?: null;
        }

        // Default behaviour for other modes (e.g. magic_link) stays the same.
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$table_safe}` WHERE `mode` = %s AND `secret` = %s LIMIT 1",
                $mode,
                $secret
            ),
            ARRAY_A
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return $row ?: null;
    }


    private static function mark_used( int $id ) {
        global $wpdb; $table = self::table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- intentional write; token state changes cannot use a cache layer.
        $wpdb->update( $table, [ 'used_at' => current_time( 'mysql' ) ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
    }

    private static function bump_attempts( int $id ) {
        global $wpdb;

        // Internal tokens table; table name is not user input.
        $table      = self::table_name();
        $table_safe = esc_sql( $table );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE `{$table_safe}` SET `attempts` = `attempts` + 1 WHERE `id` = %d",
                $id
            )
        );
        $attempts = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `attempts` FROM `{$table_safe}` WHERE `id` = %d LIMIT 1",
                $id
            )
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return (int) $attempts;
    }

    private static function email_send( string $to, string $subject, string $body, string $from_name = '', bool $is_prepared_html = false ) {
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        $filter = null;
        $name   = trim( $from_name );
        if ( '' !== $name ) {
            $decoded = wp_specialchars_decode( $name, ENT_QUOTES );
            $filter  = static function () use ( $decoded ) {
                return $decoded;
            };
            add_filter( 'wp_mail_from_name', $filter );
        }
        try {
            $message = $is_prepared_html ? $body : wpautop( $body );
            return wp_mail( $to, $subject, $message, $headers );
        } finally {
            if ( null !== $filter ) {
                remove_filter( 'wp_mail_from_name', $filter );
            }
        }
    }

    /* ---------- AJAX: Magic Link Send ---------- */
    public static function ajax_magic_link_send() {
        check_ajax_referer( 'ventraconnect_sl_auth', 'nonce' );
        if ( ! self::ensure_table_ready() ) {
            wp_send_json_error(
                [
                    'code'    => 'service_unavailable',
                    'message' => __( 'Service unavailable. Please try again later.', 'ventraconnect-social-login' ),
                ],
                500
            );
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public login endpoint, validated via token logic.
        $email = '';
        if ( isset( $_POST['email'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via sanitize_email() below.
            $email_raw = wp_unslash( (string) $_POST['email'] );
            $email     = sanitize_email( $email_raw );
        }
        $context = 'login';
        if ( isset( $_POST['context'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- public login endpoint; sanitized via sanitize_text_field() below.
            $context_raw = wp_unslash( (string) $_POST['context'] );
            $context     = sanitize_text_field( $context_raw );
        }

        // If Pro is not active, block integration contexts but allow core contexts in Free.
        if ( ! self::is_pro_enabled() && \VentraConnect\SocialLogin\Helpers\is_pro_integration_context( $context ) ) {
            wp_send_json_error(
                [
                    'code'    => 'pro_required_for_integration',
                    'message' => __( 'Passwordless login on this page requires VentraConnect Pro. Please contact the site administrator.', 'ventraconnect-social-login' ),
                ],
                403
            );
        }
        if ( ! is_email( $email ) ) {
            wp_send_json_error(
                [
                    'code'    => 'invalid_email',
                    'message' => __( 'Invalid email.', 'ventraconnect-social-login' ),
                ],
                400
            );
        }
        $email = strtolower( trim( $email ) );
        $s = self::settings_for( 'magic_link' );
        $mode = isset( $s['registration_mode'] ) ? (string) $s['registration_mode'] : 'login_and_register';
        if ( ! in_array( $mode, [ 'login_and_register', 'login_only' ], true ) ) {
            $mode = 'login_and_register';
        }
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            $guardrail_args = array(
                'email'    => $email,
                'provider' => 'magic_link',
                'profile'  => array(),
            );

            // phpcs:disable WordPress.Security.NonceVerification.Recommended -- context is a read-only hint.
            $sl_ctx = '';
            if ( isset( $_POST['context'] ) ) {
                $sl_ctx = sanitize_text_field( wp_unslash( (string) $_POST['context'] ) );
            }
            // phpcs:enable WordPress.Security.NonceVerification.Recommended

            $allowed = can_create_new_user_for_method( 'magic_link', $sl_ctx, $guardrail_args );

            if ( ! $allowed ) {
                if ( 'login_only' === $mode ) {
                    wp_send_json(
                        array(
                            'ok'      => false,
                            'error'   => 'ventraconnect_sl_registration_login_only',
                            'message' => __( 'New registrations are disabled for this login method.', 'ventraconnect-social-login' ),
                        )
                    );
                }

                wp_send_json(
                    array(
                        'ok'      => false,
                        'error'   => 'ventraconnect_sl_new_accounts_disabled',
                        'message' => __( 'You can\'t create a new account with this login method on this screen. Please register using the site\'s sign-up form first.', 'ventraconnect-social-login' ),
                    )
                );
            }
        }
        $expiry_minutes = max( 1, (int) ( $s['expiry'] ?? 10 ) );
        $expiry   = max( 60, $expiry_minutes * 60 );
        $throttle = max( 0, (int) ( $s['resend_throttle'] ?? 60 ) );
        list( $ok, $wait ) = self::can_send_now( 'magic_link', $email, $throttle );
        if ( ! $ok ) {
            wp_send_json_error(
                array(
                    'code'        => 'throttled',
                    'message'     => __( "You're requesting magic links too quickly. Please wait a few seconds and try again.", 'ventraconnect-social-login' ),
                    'retry_after' => (int) $wait,
                ),
                429
            );
        }
        $token      = wp_generate_password( 32, false );
        $single_use = ! empty( $s['single_use'] );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- public login endpoint; sanitized via esc_url_raw() below.
        $redirect_raw = isset( $_POST['redirect_to'] ) ? wp_unslash( (string) $_POST['redirect_to'] ) : '';
        $redirect_to  = $redirect_raw ? esc_url_raw( $redirect_raw ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- public login endpoint; sanitized via esc_url_raw() below.
        $origin_raw = isset( $_POST['origin_url'] ) ? wp_unslash( (string) $_POST['origin_url'] ) : '';
        $origin_url = $origin_raw ? esc_url_raw( $origin_raw ) : '';
        // Use canonical query param for magic link marker when generating consume URL.
        $consume_url = add_query_arg( [ 'ventraconnect_sl_magic' => 1, 'token' => rawurlencode( $token ), 'ctx' => rawurlencode( $context ) ], home_url( '/' ) );
        if ( $redirect_to ) {
            $consume_url = add_query_arg( 'redirect_to', $redirect_to, $consume_url );
        }
        $meta = [ 'single' => (int) $single_use ];
        if ( $redirect_to ) {
            $meta['redirect_to'] = $redirect_to;
        }
        if ( $origin_url ) {
            $meta['origin_url'] = $origin_url;
        }
        $token_id = self::insert_token( 'magic_link', $email, $token, [ 'expiry' => $expiry, 'context' => $context, 'meta' => $meta ] );
        if ( ! $token_id ) {
            wp_send_json_error( [ 'code' => 'token_error', 'message' => __( 'Unable to generate sign-in link. Please try again.', 'ventraconnect-social-login' ) ], 500 );
        }
        $site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $subj = (string) ( $s['email_subject'] ?? __( 'Your magic sign-in link', 'ventraconnect-social-login' ) );
        $body = (string) ( $s['email_body'] ?? __( 'Click to sign in: {magic_link}', 'ventraconnect-social-login' ) );
        $repl = [
            '{magic_link}' => $consume_url,
            '{site_name}'  => $site,
            '{user_email}' => $email,
            '{expires_in}' => (string) $expiry_minutes,
        ];
        $subj = strtr( $subj, $repl );
        $body_html = Passwordless_Email_Renderer::render( 'magic_link', $body, $repl );
        if ( ! is_string( $body_html ) || '' === trim( $body_html ) ) {
            $body_html = wpautop( strtr( $body, $repl ) );
        }
        $sender = trim( (string) ( $s['email_sender'] ?? '' ) );
        self::email_send( $email, $subj, $body_html, $sender, true );
        wp_send_json_success( [
            'ok'        => true,
            'expires_in'=> (int) $expiry,
            'throttle'  => (int) $throttle,
        ] );
    }

    /* ---------- AJAX: OTP Send ---------- */
    public static function ajax_otp_send() {
        check_ajax_referer( 'ventraconnect_sl_auth', 'nonce' );
        if ( ! self::ensure_table_ready() ) {
            wp_send_json_error(
                [
                    'code'    => 'service_unavailable',
                    'message' => __( 'Service unavailable. Please try again later.', 'ventraconnect-social-login' ),
                ],
                500
            );
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public login endpoint, validated via token logic.
        $email = '';
        if ( isset( $_POST['email'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via sanitize_email() below.
            $email_raw = wp_unslash( (string) $_POST['email'] );
            $email     = self::normalize_otp_email( $email_raw );
        }
        $context = 'login';
        if ( isset( $_POST['context'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- public login endpoint; sanitized via sanitize_text_field() below.
            $context_raw = wp_unslash( (string) $_POST['context'] );
            $context     = sanitize_text_field( $context_raw );
        }

        if ( ! self::is_pro_enabled() && \VentraConnect\SocialLogin\Helpers\is_pro_integration_context( $context ) ) {
            wp_send_json_error(
                [
                    'code'    => 'pro_required_for_integration',
                'message' => __( 'Passwordless login on this page requires VentraConnect Pro. Please contact the site administrator.', 'ventraconnect-social-login' ),
                ],
                403
            );
        }
        if ( ! is_email( $email ) ) {
            wp_send_json_error(
                [
                    'code'    => 'invalid_email',
                    'message' => __( 'Invalid email.', 'ventraconnect-social-login' ),
                ],
                400
            );
        }
        $s = self::settings_for( 'otp_email' );
        $mode = isset( $s['registration_mode'] ) ? (string) $s['registration_mode'] : 'login_and_register';
        if ( ! in_array( $mode, [ 'login_and_register', 'login_only' ], true ) ) {
            $mode = 'login_and_register';
        }
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            $guardrail_args = array(
                'email'    => $email,
                'provider' => 'otp_email',
                'profile'  => array(),
            );

            // phpcs:disable WordPress.Security.NonceVerification.Recommended -- context is a read-only hint.
            $sl_ctx = '';
            if ( isset( $_POST['context'] ) ) {
                $sl_ctx = sanitize_text_field( wp_unslash( (string) $_POST['context'] ) );
            }
            // phpcs:enable WordPress.Security.NonceVerification.Recommended

            $allowed = can_create_new_user_for_method( 'otp_email', $sl_ctx, $guardrail_args );

            if ( ! $allowed ) {
                if ( 'login_only' === $mode ) {
                    wp_send_json(
                        array(
                            'ok'      => false,
                            'error'   => 'ventraconnect_sl_registration_login_only',
                            'message' => __( 'New registrations are disabled for this login method.', 'ventraconnect-social-login' ),
                        )
                    );
                }

                wp_send_json(
                    array(
                        'ok'      => false,
                        'error'   => 'ventraconnect_sl_new_accounts_disabled',
                        'message' => __( 'You can\'t create a new account with this login method on this screen. Please register using the site\'s sign-up form first.', 'ventraconnect-social-login' ),
                    )
                );
            }
        }
        $len      = min( 8, max( 6, (int) ( $s['code_length'] ?? 6 ) ) );
        $expiry_minutes = max( 1, (int) ( $s['expiry'] ?? 10 ) );
        $expiry   = max( 60, $expiry_minutes * 60 );
        $throttle = max( 0, (int) ( $s['resend_throttle'] ?? 60 ) );
        $max_attempts = max( 1, (int) ( $s['max_attempts'] ?? 5 ) );
            list( $ok, $wait ) = self::can_send_now( 'otp_email', $email, $throttle );
        if ( ! $ok ) {
            wp_send_json_error(
                array(
                    'code'        => 'throttled',
                    'message'     => __( "You're requesting codes too quickly. Please wait a few seconds and try again.", 'ventraconnect-social-login' ),
                    'retry_after' => (int) $wait,
                ),
                429
           );
       }
        if ( ! self::revoke_active_otp_tokens( $email ) ) {
            wp_send_json_error( [ 'code' => 'token_error', 'message' => __( 'Unable to generate verification code. Please try again.', 'ventraconnect-social-login' ) ], 500 );
        }
        $code = '';
        for ( $i = 0; $i < $len; $i++ ) {
            $code .= (string) random_int( 0, 9 );
        }
        $token_id = self::insert_token( 'otp_email', $email, self::otp_secret_hash( $code ), [ 'expiry' => $expiry, 'max_attempts' => $max_attempts, 'context' => $context ] );
        if ( ! $token_id ) {
            wp_send_json_error( [ 'code' => 'token_error', 'message' => __( 'Unable to generate verification code. Please try again.', 'ventraconnect-social-login' ) ], 500 );
        }
        $site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $subj = (string) ( $s['email_subject'] ?? __( 'Your sign-in code', 'ventraconnect-social-login' ) );
        $mins = ceil( $expiry / 60 );
        $body = (string) ( $s['email_body'] ?? __( 'Your code: {otp_code}. Expires in {expires_in} minutes.', 'ventraconnect-social-login' ) );
        $repl = [ '{otp_code}' => $code, '{site_name}' => $site, '{user_email}' => $email, '{expires_in}' => $mins ];
        $subj = strtr( $subj, $repl );
        $body_html = Passwordless_Email_Renderer::render( 'otp_email', $body, $repl );
        if ( ! is_string( $body_html ) || '' === trim( $body_html ) ) {
            $body_html = wpautop( strtr( $body, $repl ) );
        }
        $sender = trim( (string) ( $s['email_sender'] ?? '' ) );
        self::email_send( $email, $subj, $body_html, $sender, true );
        // Provide server_expires_at (ms since epoch) and server_now for clients that prefer server-derived times
        $server_now = round( microtime( true ) * 1000 );
        $server_expires_at = round( ( time() + (int) $expiry ) * 1000 );
        wp_send_json_success( [
            'ok'               => true,
            'expires_in'       => (int) $expiry,
            'server_now'       => $server_now,
            'server_expires_at'=> $server_expires_at,
            'throttle'         => (int) $throttle,
        ] );
    }

    /* ---------- AJAX: OTP Verify ---------- */
    public static function ajax_otp_verify() {
          check_ajax_referer( 'ventraconnect_sl_auth', 'nonce' );
          if ( ! self::ensure_table_ready() ) {
              wp_send_json_error(
                  [
                      'message' => __( 'Service unavailable. Please try again later.', 'ventraconnect-social-login' ),
                ],
                500
            );
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public login endpoint, validated via token logic.
        $email = '';
        if ( isset( $_POST['email'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via sanitize_email() below.
            $email_raw = wp_unslash( (string) $_POST['email'] );
            $email     = self::normalize_otp_email( $email_raw );
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public login endpoint, validated via token logic.
        $code = '';
        if ( isset( $_POST['code'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via sanitize_text_field() below.
            $code_raw = wp_unslash( (string) $_POST['code'] );
            $code     = sanitize_text_field( $code_raw );
        }
        // Optional context hint from the request for gating; may be overridden by token row.
        $flow_ctx = 'login';
        if ( isset( $_POST['context'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- public login endpoint; sanitized via sanitize_text_field() below.
            $ctx_raw  = wp_unslash( (string) $_POST['context'] );
            $flow_ctx = sanitize_text_field( $ctx_raw );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public login endpoint, validated via token logic.
        $redirect_to = '';
        if ( isset( $_POST['redirect_to'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via esc_url_raw() below.
            $redirect_raw = wp_unslash( (string) $_POST['redirect_to'] );
            $redirect_to  = $redirect_raw ? esc_url_raw( $redirect_raw ) : '';
        }
        if ( ! is_email( $email ) || '' === $code ) {
            wp_send_json_error(
                [
                    'code'    => 'invalid_request',
                    'message' => __( 'Invalid request.', 'ventraconnect-social-login' ),
                ],
                400
            );
        }
        if ( self::is_otp_verify_rate_limited( $email ) ) {
            wp_send_json_error(
                [
                    'code'    => 'rate_limited',
                    'message' => __( 'Invalid code.', 'ventraconnect-social-login' ),
                ],
                429
            );
        }

        $row = self::find_active_otp_token( $email );
        if ( ! $row ) {
            self::record_otp_verify_failure( $email, false );
            wp_send_json_error(
                [
                    'code'    => 'invalid_code',
                    'message' => __( 'Invalid code.', 'ventraconnect-social-login' ),
                ],
                400
            );
        }
        $row_id = (int) $row['id'];

        // Prefer the stored context for gating when available.
        if ( ! empty( $row['context'] ) && is_string( $row['context'] ) ) {
            $flow_ctx = (string) $row['context'];
        }

        if ( (int) $row['max_attempts'] > 0 && (int) $row['attempts'] >= (int) $row['max_attempts'] ) {
            self::mark_used( $row_id );
            wp_send_json_error( [ 'code' => 'invalid_code', 'message' => __( 'Invalid code.', 'ventraconnect-social-login' ) ], 400 );
        }
        if ( ! self::is_otp_secret_hash( (string) $row['secret'] ) || ! hash_equals( (string) $row['secret'], self::otp_secret_hash( $code ) ) ) {
            self::record_otp_verify_failure( $email );
            $attempts = self::bump_attempts( $row_id );
            if ( (int) $row['max_attempts'] > 0 && $attempts >= (int) $row['max_attempts'] ) {
                self::mark_used( $row_id );
            }
            wp_send_json_error( [ 'code' => 'invalid_code', 'message' => __( 'Invalid code.', 'ventraconnect-social-login' ) ], 400 );
        }

        if ( ! self::is_pro_enabled() && \VentraConnect\SocialLogin\Helpers\is_pro_integration_context( $flow_ctx ) ) {
            wp_send_json_error(
                [
                    'code'    => 'pro_required_for_integration',
                    'message' => __( 'Passwordless login on this page requires VentraConnect Pro. Please contact the site administrator.', 'ventraconnect-social-login' ),
                ],
                403
            );
        }

        // Map stored token context to unified ventraconnect_sl_ctx for downstream logic (Woo detection, emails)
        $flow_ctx = isset( $row['context'] ) ? trim( (string) $row['context'] ) : '';
        if ( '' !== $flow_ctx ) {
            $_REQUEST['ventraconnect_sl_ctx'] = $flow_ctx;
            $_GET['ventraconnect_sl_ctx']     = $flow_ctx;
        }

        $result = self::login_or_create_by_email( $row['email'], 'otp_email', 'otp:' . $row_id );
        if ( is_wp_error( $result ) ) {
            self::bump_attempts( $row_id );
            wp_send_json( [
                'ok'      => false,
                'error'   => $result->get_error_code(),
                'message' => $result->get_error_message(),
            ] );
          }
          self::mark_used( $row_id );
          $user_id = (int) $result;
          $state = [];
          if ( ! empty( $redirect_to ) ) { $state['redirect_to'] = $redirect_to; }
          // Ensure context from the token row is passed into RedirectResolver.
          if ( '' !== $flow_ctx && empty( $state['ventraconnect_sl_ctx'] ) ) {
              $state['ventraconnect_sl_ctx'] = $flow_ctx;
          }
          // Compute OTP-specific redirect override (Free + Pro shared helper).
          $otp_settings = self::settings_for( 'otp_email' );
          $request      = is_array( $_REQUEST ) ? (array) $_REQUEST : array();
          if ( '' !== $redirect_to ) {
              $request['redirect_to'] = $redirect_to;
          }
          $override_url = compute_otp_redirect_for_user( $user_id, $otp_settings, $request, $state );
          if ( '' !== $override_url ) {
              $state['redirect_to'] = $override_url;
          }
          $dest = \VentraConnect\SocialLogin\RedirectResolver::compute( $user_id, 'otp_email', $state, $_REQUEST );
          wp_send_json_success( [ 'ok' => true, 'redirect' => $dest ] );
      }

    /* ---------- Magic Link Consumer ---------- */
    public static function maybe_consume_magic_link() {
        // This endpoint consumes a one-time magic link delivered via email.
        // Authentication is handled by the cryptographic token, not a nonce —
        // nonce verification is intentionally omitted per WP Coding Standards guidance
        // for token-authenticated GET flows.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        // Accept canonical magic marker only.
        if ( ! ( isset( $_GET['ventraconnect_sl_magic'] ) && isset( $_GET['token'] ) ) ) {
            return;
        }
        if ( ! self::ensure_table_ready() ) { wp_die( esc_html__( 'Service unavailable. Please try again later.', 'ventraconnect-social-login' ) ); }
        $token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
        // Map Magic Link context to unified ventraconnect_sl_ctx for downstream logic (Woo detection, emails)
        $ctx_param = isset( $_GET['ctx'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ctx'] ) ) : '';
        // Make the initial context visible to downstream code early,
        // mirroring OAuth behaviour (both $_REQUEST and $_GET).
        if ( $ctx_param ) {
            $_REQUEST['ventraconnect_sl_ctx'] = $ctx_param;
            $_GET['ventraconnect_sl_ctx']     = $ctx_param;
        }
        $redirect_raw = isset( $_GET['redirect_to'] ) ? wp_unslash( (string) $_GET['redirect_to'] ) : '';
        $redirect_to  = esc_url_raw( $redirect_raw );
        $row = self::find_token( 'magic_link', $token );
        if ( ! $row ) { wp_die( esc_html__( 'Invalid or expired link.', 'ventraconnect-social-login' ) ); }
        $row_id = (int) $row['id'];
        if ( $row['used_at'] ) { wp_die( esc_html__( 'Link already used.', 'ventraconnect-social-login' ) ); }
        if ( strtotime( $row['expires_at'] ) < time() ) {
            self::mark_used( $row_id );
            wp_die( esc_html__( 'Link expired.', 'ventraconnect-social-login' ) );
        }
        $meta = maybe_unserialize( $row['meta'] );

        // Determine effective context for gating: prefer stored row context, fall back to ctx param.
        $ctx = '';
        if ( ! empty( $row['context'] ) && is_string( $row['context'] ) ) {
            $ctx = (string) $row['context'];
        } elseif ( '' !== $ctx_param ) {
            $ctx = $ctx_param;
        }

        if ( ! self::is_pro_enabled() && \VentraConnect\SocialLogin\Helpers\is_pro_integration_context( $ctx ) ) {
            wp_die(
                esc_html__( 'Passwordless login on this page requires VentraConnect Pro. Please contact the site administrator.', 'ventraconnect-social-login' ),
                esc_html__( 'VentraConnect Pro Required', 'ventraconnect-social-login' ),
                [ 'response' => 403 ]
            );
        }

        if ( '' !== $ctx ) {
            $_REQUEST['ventraconnect_sl_ctx'] = $ctx;
            $_GET['ventraconnect_sl_ctx']     = $ctx;
        }
        if ( empty( $redirect_to ) && ! empty( $meta['redirect_to'] ) ) {
            $redirect_to = esc_url_raw( (string) $meta['redirect_to'] );
        }
        if ( empty( $redirect_to ) && ! empty( $meta['origin_url'] ) ) {
            $redirect_to = esc_url_raw( (string) $meta['origin_url'] );
        }
        if ( ! empty( $redirect_to ) ) {
            $_REQUEST['redirect_to'] = $redirect_to;
        }
        $single = ! empty( $meta['single'] );
        // Enforce same-IP restriction if enabled in settings
        $settings = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $ml_conf = isset( $settings['magic_link'] ) && is_array( $settings['magic_link'] ) ? $settings['magic_link'] : [];
        $require_same_ip = ! empty( $ml_conf['require_same_ip'] );
        if ( $require_same_ip ) {
            $token_ip = trim( (string) $row['ip'] );
            $current_ip = self::get_client_ip();
            if ( '' !== $token_ip && $token_ip !== $current_ip ) {
                // prevent reuse and redirect with an error marker so the UI can display a friendly message
                self::mark_used( $row_id );
                $err_dest = \VentraConnect\SocialLogin\RedirectResolver::compute( 0, 'magic_link', is_array( $meta ) ? $meta : [], $_REQUEST );
                $err_dest = add_query_arg( 'ventraconnect_sl_magic_error', 'ip_mismatch', $err_dest );
                wp_safe_redirect( $err_dest );
                exit;
            }
        }
        $res = self::login_or_create_by_email( $row['email'], 'magic_link', 'magic:' . $row_id );
        if ( is_wp_error( $res ) ) {
            wp_die( esc_html( $res->get_error_message() ) );
        }

        // For security, Magic Links must always be single-use.
        self::mark_used( $row_id );

        $user_id = (int) $res;
        $state = is_array( $meta ) ? $meta : [];
        if ( ! empty( $redirect_to ) && empty( $state['redirect_to'] ) ) { $state['redirect_to'] = $redirect_to; }
        // Ensure context is passed into RedirectResolver just like OAuth state.
        if ( '' !== $ctx && empty( $state['ventraconnect_sl_ctx'] ) ) {
            $state['ventraconnect_sl_ctx'] = $ctx;
        }
        // Compute Magic Link-specific redirect override (Free + Pro shared helper).
        $request       = is_array( $_REQUEST ) ? (array) $_REQUEST : array();
        if ( '' !== $redirect_to ) {
            $request['redirect_to'] = $redirect_to;
        }
        $override_url  = compute_magic_link_redirect_for_user( $user_id, $ml_conf, $request, $state );
        if ( '' !== $override_url ) {
            $state['redirect_to'] = $override_url;
        }
        $dest = \VentraConnect\SocialLogin\RedirectResolver::compute( $user_id, 'magic_link', $state, $_REQUEST );
        // append a canonical marker so client tabs can detect a successful login via magic link
        $dest = add_query_arg( 'ventraconnect_sl_logged_in', '1', $dest );
        wp_safe_redirect( $dest );
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        exit;
    }

    // (resolve_redirect removed; unified RedirectResolver is now used across flows)

    private static function login_or_create_by_email( string $email, string $provider, string $pid ) {
        $email = strtolower( trim( $email ) );
        $existing_user = get_user_by( 'email', $email );
        $current_user_id = get_current_user_id();
        $user = null;
        $is_new_user = false;

        // Logged-in safety and registration_mode enforcement only apply to passwordless flows.
        if ( in_array( $provider, [ 'magic_link', 'otp_email' ], true ) ) {
            if ( $current_user_id > 0 ) {
                // Already logged in: do not switch accounts or create new ones.
                if ( $existing_user && (int) $existing_user->ID !== (int) $current_user_id ) {
                    return new WP_Error(
                        'ventraconnect_sl_logged_in_mismatch',
                        __( 'You are already logged in as a different user.', 'ventraconnect-social-login' )
                    );
                }
                if ( ! $existing_user ) {
                    return new WP_Error(
                        'ventraconnect_sl_logged_in_cannot_register',
                        __( 'You are already logged in.', 'ventraconnect-social-login' )
                    );
                }
                $user = get_user_by( 'id', $current_user_id );
            } else {
                // Visitor not logged in.
                if ( $existing_user ) {
                    $user = $existing_user;
                } else {
                    $provider_settings = self::settings_for( $provider );
                    $mode = isset( $provider_settings['registration_mode'] ) ? (string) $provider_settings['registration_mode'] : 'login_and_register';
                    if ( ! in_array( $mode, [ 'login_and_register', 'login_only' ], true ) ) {
                        $mode = 'login_and_register';
                    }
                    if ( 'login_only' === $mode ) {
                        return new WP_Error(
                            'ventraconnect_sl_registration_login_only',
                            __( 'New registrations are disabled for this login method.', 'ventraconnect-social-login' )
                        );
                    }
                }
            }
        }

        // Fallback to legacy behavior (and user creation) when a user has not been resolved yet.
        if ( ! $user ) {
            if ( ! $existing_user ) {
                // phpcs:disable WordPress.Security.NonceVerification.Recommended
                $sl_ctx = '';
                if ( isset( $_REQUEST['ventraconnect_sl_ctx'] ) ) {
                    $sl_ctx = sanitize_text_field( wp_unslash( (string) $_REQUEST['ventraconnect_sl_ctx'] ) );
                }
                // phpcs:enable WordPress.Security.NonceVerification.Recommended

                $guardrail_args = [
                    'email'    => $email,
                    'provider' => $provider,
                    'profile'  => [],
                ];

                $allowed = can_create_new_user_for_method( $provider, $sl_ctx, $guardrail_args );

                if ( ! $allowed ) {
                    $settings = get_option( 'ventraconnect_sl_settings', [] );

                    $mode = 'login_and_register';
                    if ( 'magic_link' === $provider && isset( $settings['magic_link']['registration_mode'] ) ) {
                        $mode = $settings['magic_link']['registration_mode'];
                    } elseif ( 'otp_email' === $provider && isset( $settings['otp_email']['registration_mode'] ) ) {
                        $mode = $settings['otp_email']['registration_mode'];
                    }

                    if ( 'login_only' === $mode ) {
                        $message = __( 'New registrations are disabled for this login method. Please use your existing account to sign in.', 'ventraconnect-social-login' );
                    } else {
                        $message = __( 'You can\'t create a new account with this login method on this screen. Please register using the site\'s sign-up form first.', 'ventraconnect-social-login' );
                    }

                    return new WP_Error(
                        'ventraconnect_sl_passwordless_new_account_blocked',
                        $message
                    );
                }

                $username = sanitize_user( current( explode( '@', $email ) ), true );
                if ( empty( $username ) ) { $username = 'user_' . wp_generate_password( 6, false ); }
                $i = 1; $base = $username;
                while ( username_exists( $username ) ) { $username = $base . '_' . $i++; }
                $args = [
                    'user_login'   => $username,
                    'user_pass'    => ventraconnect_sl_generate_internal_account_password(),
                    'user_email'   => $email,
                    'display_name' => $username,
                ];
                $role = self::resolve_social_login_role();
                if ( $role ) {
                    $args['role'] = $role;
                }

                $uid = wp_insert_user( $args );
                if ( is_wp_error( $uid ) ) {
                    return $uid;
                }
                ventraconnect_sl_mark_passwordless_account_created( (int) $uid, $provider, $sl_ctx );
                $is_new_user = true;

                // Notify email/notification system for new accounts based on context.
                if ( function_exists( '\\VentraConnect\\SocialLogin\\Pro\\Emails\\ventraconnect_sl_send_new_account_email_for_context' ) ) {
                    $is_woo_ctx = ( '' !== $sl_ctx ) && ( 0 === strpos( $sl_ctx, 'wc_' ) || 'checkout' === $sl_ctx );

                    $ctx = [
                        'provider'       => $provider,
                        'is_new_user'    => true,
                        'is_woocommerce' => $is_woo_ctx,
                    ];
                    call_user_func( '\\VentraConnect\\SocialLogin\\Pro\\Emails\\ventraconnect_sl_send_new_account_email_for_context', (int) $uid, $ctx );
                }
                $user = get_user_by( 'id', $uid );
            } else {
                $user = $existing_user;
            }
        }

        if ( ! $user instanceof \WP_User ) {
            return new WP_Error(
                'ventraconnect_sl_login_failed',
                __( 'Unable to log you in.', 'ventraconnect-social-login' )
            );
        }

        if ( ! in_array( $provider, [ 'magic_link', 'otp_email' ], true ) ) {
            $profile = [
                'provider' => $provider,
                'id'       => $pid,
                'email'    => $email,
                'name'     => $user->display_name ?: $email,
                'avatar'   => '',
                'raw'      => [],
            ];
            ( new \VentraConnect\SocialLogin\User_Links() )->link_provider( $user->ID, $provider, $pid, $profile, [] );
        }

        $user_id = (int) $user->ID;
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );
        do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WordPress login lifecycle hook invoked after token authentication succeeds.

        if ( in_array( $provider, [ 'magic_link', 'otp_email' ], true ) ) {
            /**
             * Fire a unified login success hook for passwordless flows (Magic Link, OTP, etc.).
             *
             * @param int    $user_id      User ID.
             * @param string $provider_key Provider key (e.g. 'magic_link', 'otp_email').
             * @param array  $profile      Profile data (at minimum, include the email).
             * @param bool   $is_new_user  True if a new user was created.
             */
            do_action(
                'ventraconnect_sl_login_success',
                $user_id,
                $provider,
                [ 'email' => $email ],
                $is_new_user
            );
        }

        return $user_id;
    }

    private static function resolve_social_login_role(): ?string {
        if ( class_exists( '\VentraConnect\SocialLogin\Pro\Helpers\UserRoles' ) ) {
            $role = \VentraConnect\SocialLogin\Pro\Helpers\UserRoles::resolve_default_social_role();
            if ( is_string( $role ) && $role !== '' ) {
                return $role;
            }
        }
        return null;
    }
}
