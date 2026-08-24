<?php
namespace VentraConnect\SocialLogin\Diagnostics;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Health {
    /**
     * Get passive advanced Passkeys status for the Environment snapshot.
     *
     * @return array<string,mixed>
     */
    private static function get_advanced_passkeys_status(): array {
        $plugin_file        = 'ventraconnect-passkeys/ventraconnect-passkeys.php';
        $standalone_active  = false;
        $standalone_version = defined( 'VENTRACONNECT_PASSKEYS_VERSION' ) ? (string) VENTRACONNECT_PASSKEYS_VERSION : '';
        $pro_active         = function_exists( 'vcsl_is_pro_active' ) && vcsl_is_pro_active();
        $bundled_version    = defined( 'VENTRACONNECT_PRO_PASSKEYS_VERSION' ) ? (string) VENTRACONNECT_PRO_PASSKEYS_VERSION : '';
        $bundled_available  = $pro_active && '' !== $bundled_version;
        $advanced_source    = 'none';
        $advanced_version   = 'n/a';

        if ( function_exists( 'is_plugin_active' ) ) {
            $standalone_active = is_plugin_active( $plugin_file );
        } elseif ( file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            if ( function_exists( 'is_plugin_active' ) ) {
                $standalone_active = is_plugin_active( $plugin_file );
            }
        }

        if ( '' === $standalone_version ) {
            if ( function_exists( 'get_plugins' ) ) {
                $all_plugins = get_plugins();
                if ( isset( $all_plugins[ $plugin_file ]['Version'] ) ) {
                    $standalone_version = (string) $all_plugins[ $plugin_file ]['Version'];
                }
            } elseif ( file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                if ( function_exists( 'get_plugins' ) ) {
                    $all_plugins = get_plugins();
                    if ( isset( $all_plugins[ $plugin_file ]['Version'] ) ) {
                        $standalone_version = (string) $all_plugins[ $plugin_file ]['Version'];
                    }
                }
            }
        }

        if ( $bundled_available ) {
            $advanced_source  = 'pro';
            $advanced_version = $bundled_version;
        } elseif ( $standalone_active || '' !== $standalone_version ) {
            $advanced_source  = 'standalone_local';
            $advanced_version = '' !== $standalone_version ? $standalone_version : 'n/a';
        }

        return [
            'advanced_passkeys_available'       => $bundled_available,
            'advanced_passkeys_source'          => $advanced_source,
            'advanced_passkeys_version'         => $advanced_version,
            'standalone_passkeys_local_active'  => $standalone_active,
            'standalone_passkeys_local_version' => '' !== $standalone_version ? $standalone_version : 'n/a',
            // Deprecated aliases kept for existing diagnostics consumers.
            'passkeys_addon_active'             => $bundled_available,
            'passkeys_addon_version'            => $advanced_version,
        ];
    }

    /**
     * Build a passive passkeys diagnostics snapshot without loading runtime services.
     *
     * @param array<string,mixed> $settings Saved plugin settings.
     * @return array<string,mixed>
     */
    private static function get_passkeys_snapshot( array $settings ): array {
        $php_version                         = PHP_VERSION;
        $php_supported                       = version_compare( $php_version, '8.2', '>=' );
        $passkey_supported_helper_exists     = function_exists( 'ventraconnect_sl_is_passkey_supported' );
        $passkey_method_enabled_helper_exists = function_exists( 'ventraconnect_sl_is_passkey_method_enabled' );
        $passkey_supported                   = defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) && VENTRACONNECT_PASSKEYS_CORE_SUPPORTED;
        $free_vendor_autoload_path           = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' )
            ? VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/core/vendor/autoload.php'
            : dirname( __DIR__, 2 ) . '/includes/passkeys/core/vendor/autoload.php';

        $snapshot = [
            'php_version'                                => $php_version,
            'passkey_core_supported_constant_defined'    => defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ),
            'passkey_core_supported'                     => $passkey_supported,
            'passkey_supported_helper_exists'            => $passkey_supported_helper_exists,
            'passkey_supported_helper_result'            => $passkey_supported_helper_exists ? (bool) ventraconnect_sl_is_passkey_supported() : false,
            'passkey_method_enabled_helper_exists'       => $passkey_method_enabled_helper_exists,
            'passkey_method_enabled'                     => $passkey_method_enabled_helper_exists ? (bool) ventraconnect_sl_is_passkey_method_enabled() : false,
            'users_can_register'                         => (bool) get_option( 'users_can_register' ),
            'wp_login_enabled'                           => ! empty( $settings['wp_login_enabled'] ),
            'wp_register_enabled'                        => ! empty( $settings['wp_register_enabled'] ),
            'free_vendor_autoload_exists'                => file_exists( $free_vendor_autoload_path ),
            'passkey_db_version'                         => (string) get_option( 'ventraconnect_passkeys_db_version', '' ),
            'passkey_runtime_intentionally_unsupported'  => ! $php_supported,
            'class_map'                                  => [
                'database'             => class_exists( 'VentraConnect_SL_Passkeys_Core_Database', false ),
                'installer'            => class_exists( 'VentraConnect_SL_Passkeys_Core_Installer', false ),
                'passkey_repository'   => class_exists( 'VentraConnect_SL_Passkeys_Core_Passkey_Repository', false ),
                'challenge_repository' => class_exists( 'VentraConnect_SL_Passkeys_Core_Challenge_Repository', false ),
                'log_repository'       => class_exists( 'VentraConnect_SL_Passkeys_Core_Log_Repository', false ),
                'webauthn_service'     => class_exists( 'VentraConnect_SL_Passkeys_Core_WebAuthn_Service', false ),
                'public_ajax'          => class_exists( 'VentraConnect_SL_Passkeys_Public_Ajax', false ),
                'management_ajax'      => class_exists( 'VentraConnect_SL_Passkeys_Management_Ajax', false ),
            ],
            'tables'                                     => [
                'passkeys'   => [
                    'name'   => '',
                    'exists' => false,
                ],
                'challenges' => [
                    'name'   => '',
                    'exists' => false,
                ],
                'logs'       => [
                    'name'   => '',
                    'exists' => false,
                ],
            ],
            'counts'                                     => [],
        ];

        if ( current_user_can( 'manage_options' ) ) {
            $snapshot['tables'] = self::get_passkey_table_snapshot();
            $snapshot['counts'] = self::get_passkey_counts( $snapshot['tables'] );
        }

        return $snapshot;
    }

    /**
     * Build passwordless account hardening diagnostics.
     *
     * @return array<string,mixed>
     */
    private static function get_passwordless_accounts_snapshot(): array {
        $snapshot = [
            'generate_internal_password_helper_exists' => function_exists( 'ventraconnect_sl_generate_internal_account_password' ),
            'mark_passwordless_account_helper_exists'  => function_exists( 'ventraconnect_sl_mark_passwordless_account_created' ),
            'mark_password_set_helper_exists'          => function_exists( 'ventraconnect_sl_mark_password_set_by_user' ),
            'after_password_reset_hook_registered'    => false !== has_action( 'after_password_reset', 'ventraconnect_sl_after_password_reset' ),
            'counts'                                  => [],
        ];

        if ( current_user_can( 'manage_options' ) ) {
            $snapshot['counts'] = self::get_passwordless_account_counts();
        }

        return $snapshot;
    }

    /**
     * Get passive passkey table existence data.
     *
     * @return array<string,array<string,mixed>>
     */
    private static function get_passkey_table_snapshot(): array {
        global $wpdb;

        $tables = [
            'passkeys'   => [
                'name'   => $wpdb->prefix . 'ventraconnect_passkeys',
                'exists' => false,
            ],
            'challenges' => [
                'name'   => $wpdb->prefix . 'ventraconnect_passkey_challenges',
                'exists' => false,
            ],
            'logs'       => [
                'name'   => $wpdb->prefix . 'ventraconnect_passkey_logs',
                'exists' => false,
            ],
        ];

        foreach ( $tables as $key => $table ) {
            $table_name              = (string) $table['name'];
            $found_table             = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Health diagnostics schema check must reflect current database state; caching is intentionally avoided.
            $tables[ $key ]['exists'] = ( $found_table === $table_name );
        }

        return $tables;
    }

    /**
     * Read passkey record counts safely.
     *
     * @param array<string,array<string,mixed>> $tables Table snapshot.
     * @return array<string,int>
     */
    private static function get_passkey_counts( array $tables ): array {
        global $wpdb;

        $counts = [
            'total_passkeys'   => 0,
            'active_passkeys'  => 0,
            'passkey_users'    => 0,
        ];

        $passkeys_table = isset( $tables['passkeys']['name'] ) ? (string) $tables['passkeys']['name'] : '';
        $passkeys_exist = ! empty( $tables['passkeys']['exists'] );

        if ( '' === $passkeys_table || ! $passkeys_exist ) {
            return $counts;
        }

        $counts['total_passkeys'] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $passkeys_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin health diagnostics reads live passkey counts; caching is intentionally avoided.
        $counts['passkey_users']  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(DISTINCT user_id) FROM %i', $passkeys_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin health diagnostics reads live passkey counts; caching is intentionally avoided.

        if ( self::table_has_column( $passkeys_table, 'is_active' ) ) {
            $counts['active_passkeys'] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE is_active = 1', $passkeys_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin health diagnostics reads live passkey counts; caching is intentionally avoided.
        }

        return $counts;
    }

    /**
     * Check whether a table contains a column.
     *
     * @param string $table_name  Table name.
     * @param string $column_name Column name.
     * @return bool
     */
    private static function table_has_column( string $table_name, string $column_name ): bool {
        global $wpdb;

        if ( '' === $table_name || '' === $column_name ) {
            return false;
        }

        $column    = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Health diagnostics schema check must reflect current database state; caching is intentionally avoided.
            $wpdb->prepare(
                'SHOW COLUMNS FROM %i LIKE %s',
                $table_name,
                $column_name
            )
        );

        return is_string( $column ) && '' !== $column;
    }

    /**
     * Get passwordless account-related user meta counts.
     *
     * @return array<string,int>
     */
    private static function get_passwordless_account_counts(): array {
        global $wpdb;

        $usermeta = $wpdb->usermeta;
        $counts   = [
            'passwordless_accounts'             => 0,
            'created_with_passkey'              => 0,
            'created_with_magic_link'           => 0,
            'created_with_email_otp'            => 0,
            'created_with_social'               => 0,
            'passwordless_without_user_password' => 0,
        ];

        $counts['passwordless_accounts'] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin health diagnostics reads live account counts; caching is intentionally avoided.
            $wpdb->prepare(
                'SELECT COUNT(DISTINCT user_id) FROM %i WHERE meta_key = %s AND meta_value = %s',
                $usermeta,
                '_ventraconnect_passwordless_account',
                'yes'
            )
        );

        $counts['created_with_passkey'] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin health diagnostics reads live account counts; caching is intentionally avoided.
            $wpdb->prepare(
                'SELECT COUNT(DISTINCT user_id) FROM %i WHERE meta_key = %s AND meta_value = %s',
                $usermeta,
                '_ventraconnect_created_with',
                'passkey'
            )
        );

        $counts['created_with_magic_link'] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin health diagnostics reads live account counts; caching is intentionally avoided.
            $wpdb->prepare(
                'SELECT COUNT(DISTINCT user_id) FROM %i WHERE meta_key = %s AND meta_value = %s',
                $usermeta,
                '_ventraconnect_created_with',
                'magic_link'
            )
        );

        $counts['created_with_email_otp'] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin health diagnostics reads live account counts; caching is intentionally avoided.
            $wpdb->prepare(
                'SELECT COUNT(DISTINCT user_id) FROM %i WHERE meta_key = %s AND meta_value = %s',
                $usermeta,
                '_ventraconnect_created_with',
                'email_otp'
            )
        );

        $counts['created_with_social'] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin health diagnostics reads live account counts; caching is intentionally avoided.
            $wpdb->prepare(
                'SELECT COUNT(DISTINCT user_id) FROM %i WHERE meta_key = %s AND meta_value LIKE %s ESCAPE \'\\\\\'',
                $usermeta,
                '_ventraconnect_created_with',
                $wpdb->esc_like( 'social_' ) . '%'
            )
        );

        $counts['passwordless_without_user_password'] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin health diagnostics reads live account counts; caching is intentionally avoided.
            $wpdb->prepare(
                'SELECT COUNT(DISTINCT pw.user_id)
                FROM %i pw
                INNER JOIN %i ps ON pw.user_id = ps.user_id
                WHERE pw.meta_key = %s AND pw.meta_value = %s
                AND ps.meta_key = %s AND ps.meta_value = %s',
                $usermeta,
                $usermeta,
                '_ventraconnect_passwordless_account',
                'yes',
                '_ventraconnect_password_set_by_user',
                'no'
            )
        );

        return $counts;
    }

    /**
     * Build a diagnostics snapshot of the environment and plugin.
     *
     * @return array
     */
    public static function get_snapshot(): array {
        $settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $creds    = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderCreds();

        $enabled_providers = array_map(
            'sanitize_key',
            (array) ( $settings['providers'] ?? [] )
        );

        // Determine configured OAuth providers based on stored credentials.
        $configured_slugs = [];
        foreach ( $creds as $slug => $conf ) {
            $slug = sanitize_key( (string) $slug );
            if ( '' === $slug ) {
                continue;
            }
            $conf = (array) $conf;
            if ( ! empty( $conf['client_id'] ) && ! empty( $conf['client_secret'] ) ) {
                $configured_slugs[] = $slug;
            }
        }
        $configured_slugs = array_values( array_unique( $configured_slugs ) );

        $configured_count = count( $configured_slugs );
        $has_oauth_config = $configured_count > 0;

        // Environment and version checks.
        $wp_version  = get_bloginfo( 'version' );
        $php_version = PHP_VERSION;

        $min_php = '7.4';
        $min_wp  = '6.2';

        $php_ok = version_compare( $php_version, $min_php, '>=' );
        $wp_ok  = version_compare( $wp_version, $min_wp, '>=' );

        $https_ok = is_ssl();

        $pro_active        = function_exists( 'vcsl_is_pro_active' ) && vcsl_is_pro_active();
        $pro_version       = defined( 'VENTRACONNECT_SL_PRO_VERSION' ) ? VENTRACONNECT_SL_PRO_VERSION : '';
        $advanced_passkeys = self::get_advanced_passkeys_status();

        // WooCommerce integration flags.
        $woo_active = class_exists( 'WooCommerce' );

        $woo_login_context_on = false;
        if ( $woo_active && ! empty( $settings['integrations']['woocommerce'] ) && is_array( $settings['integrations']['woocommerce'] ) ) {
            $wc_conf = (array) $settings['integrations']['woocommerce'];
            foreach ( [ 'login_form', 'my_account', 'checkout' ] as $key ) {
                if ( ! empty( $wc_conf[ $key ]['enabled'] ) ) {
                    $woo_login_context_on = true;
                    break;
                }
            }
        }

        // Passwordless + token providers.
        $passwordless_mode = isset( $settings['passwordless_mode'] ) ? (string) $settings['passwordless_mode'] : 'off';
        if ( ! in_array( $passwordless_mode, [ 'off', 'recommended', 'strict' ], true ) ) {
            $passwordless_mode = 'off';
        }

        $has_magic_link = in_array( 'magic_link', $enabled_providers, true );
        $has_otp_email  = in_array( 'otp_email', $enabled_providers, true );

        // Debug flags.
        $debug_mode        = ! empty( $settings['debug_mode'] );
        $integration_debug = ! empty( $settings['integration_debug'] );

        // REST API basic reachability check.
        $rest_ok = true;
        if ( function_exists( 'rest_get_server' ) && function_exists( 'rest_url' ) && function_exists( 'wp_remote_get' ) ) {
            $rest_url = rest_url( 'wp/v2/types' );
            $resp     = wp_remote_get(
                $rest_url,
                [
                    'timeout'     => 5,
                    'redirection' => 3,
                    'sslverify'   => true,
                ]
            );

            if ( is_wp_error( $resp ) ) {
                $rest_ok = false;
            } else {
                $code = wp_remote_retrieve_response_code( $resp );
                if ( $code < 200 || $code >= 400 ) {
                    $rest_ok = false;
                }
            }
        }

        // Compose explicit health messages.
        $errors   = [];
        $warnings = [];

        if ( ! $php_ok ) {
            $errors[] = sprintf(
                /* translators: 1: Minimum PHP version, 2: Current PHP version. */
                __( 'PHP version is below the minimum required (min: %1$s, current: %2$s).', 'ventraconnect-social-login' ),
                $min_php,
                $php_version
            );
        }

        if ( ! $wp_ok ) {
            $errors[] = sprintf(
                /* translators: 1: Minimum WordPress version, 2: Current WordPress version. */
                __( 'WordPress version is below the minimum required (min: %1$s, current: %2$s).', 'ventraconnect-social-login' ),
                $min_wp,
                $wp_version
            );
        }

        // Only warn about HTTPS when core versions are otherwise OK.
        if ( $php_ok && $wp_ok && ! $https_ok ) {
            $warnings[] = __( 'HTTPS is disabled. Many OAuth providers require HTTPS for live apps.', 'ventraconnect-social-login' );
        }

        if ( ! $rest_ok ) {
            $warnings[] = __( 'REST API is not reachable. This may indicate permalinks, security plugins, or server restrictions.', 'ventraconnect-social-login' );
        }

        // Derive overall status from checks.
        $status = 'ok';
        if ( ! $php_ok || ! $wp_ok ) {
            $status = 'error';
        } elseif ( ! $https_ok || ! $rest_ok ) {
            $status = 'warning';
        }

        $snapshot = [
            'env'           => [
                'site_url'       => home_url(),
                'wp_version'     => $wp_version,
                'php_version'    => $php_version,
                'plugin_version' => defined( 'VENTRACONNECT_SL_VERSION' ) ? VENTRACONNECT_SL_VERSION : '',
                'pro_version'    => $pro_version,
                'pro_active'     => $pro_active,
                'advanced_passkeys_available'       => ! empty( $advanced_passkeys['advanced_passkeys_available'] ),
                'advanced_passkeys_source'          => isset( $advanced_passkeys['advanced_passkeys_source'] ) ? (string) $advanced_passkeys['advanced_passkeys_source'] : 'none',
                'advanced_passkeys_version'         => isset( $advanced_passkeys['advanced_passkeys_version'] ) ? (string) $advanced_passkeys['advanced_passkeys_version'] : 'n/a',
                'standalone_passkeys_local_active'  => ! empty( $advanced_passkeys['standalone_passkeys_local_active'] ),
                'standalone_passkeys_local_version' => isset( $advanced_passkeys['standalone_passkeys_local_version'] ) ? (string) $advanced_passkeys['standalone_passkeys_local_version'] : 'n/a',
                // Deprecated aliases kept for existing diagnostics consumers.
                'passkeys_addon_active'             => ! empty( $advanced_passkeys['passkeys_addon_active'] ),
                'passkeys_addon_version'            => isset( $advanced_passkeys['passkeys_addon_version'] ) ? (string) $advanced_passkeys['passkeys_addon_version'] : 'n/a',
                'https'          => $https_ok,
            ],
            'plugin_health' => [
                'status'   => $status,
                'checks'   => [
                    'php_version_ok' => $php_ok,
                    'wp_version_ok'  => $wp_ok,
                    'https_ok'       => $https_ok,
                    'rest_api_ok'    => $rest_ok,
                ],
                'messages' => [
                    'errors'   => $errors,
                    'warnings' => $warnings,
                ],
            ],
            'providers'     => [
                'configured_count' => $configured_count,
                'configured_slugs' => $configured_slugs,
                'has_oauth_config' => $has_oauth_config,
            ],
            'integrations'  => [
                'woocommerce' => [
                    'active'           => $woo_active,
                    'login_context_on' => $woo_login_context_on,
                ],
                'passwordless' => [
                    'mode' => $passwordless_mode,
                ],
                'magic_link' => [
                    'enabled' => $has_magic_link,
                ],
                'otp' => [
                    'enabled' => $has_otp_email,
                ],
            ],
            'debug'         => [
                'debug_mode'        => $debug_mode,
                'integration_debug' => $integration_debug,
            ],
            'passkeys'      => self::get_passkeys_snapshot( is_array( $settings ) ? $settings : [] ),
            'passwordless_accounts' => self::get_passwordless_accounts_snapshot(),
        ];

        $snapshot = apply_filters(
            'ventraconnect_sl_diagnostics_snapshot',
            $snapshot
        );

        return $snapshot;
    }
}
