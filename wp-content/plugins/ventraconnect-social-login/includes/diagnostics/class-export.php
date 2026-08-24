<?php
namespace VentraConnect\SocialLogin\Diagnostics;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Export {
    /**
     * Build a plain-text diagnostics blob suitable for support.
     *
     * @param array $snapshot
     * @param array $events
     * @return string
     */
    public static function build_support_blob( array $snapshot, array $events ): string {
        $env   = isset( $snapshot['env'] ) && is_array( $snapshot['env'] ) ? $snapshot['env'] : [];
        $prov  = isset( $snapshot['providers'] ) && is_array( $snapshot['providers'] ) ? $snapshot['providers'] : [];
        $int   = isset( $snapshot['integrations'] ) && is_array( $snapshot['integrations'] ) ? $snapshot['integrations'] : [];
        $debug = isset( $snapshot['debug'] ) && is_array( $snapshot['debug'] ) ? $snapshot['debug'] : [];
        $passkeys = isset( $snapshot['passkeys'] ) && is_array( $snapshot['passkeys'] ) ? $snapshot['passkeys'] : [];
        $passwordless_accounts = isset( $snapshot['passwordless_accounts'] ) && is_array( $snapshot['passwordless_accounts'] ) ? $snapshot['passwordless_accounts'] : [];

        $site_url       = isset( $env['site_url'] ) ? (string) $env['site_url'] : '';
        $wp_version     = isset( $env['wp_version'] ) ? (string) $env['wp_version'] : '';
        $php_version    = isset( $env['php_version'] ) ? (string) $env['php_version'] : '';
        $plugin_version = isset( $env['plugin_version'] ) ? (string) $env['plugin_version'] : '';
        $https_on       = ! empty( $env['https'] );

        $configured_count = isset( $prov['configured_count'] ) ? (int) $prov['configured_count'] : 0;
        $configured_slugs = isset( $prov['configured_slugs'] ) && is_array( $prov['configured_slugs'] ) ? $prov['configured_slugs'] : [];

        $passwordless      = isset( $int['passwordless'] ) && is_array( $int['passwordless'] ) ? $int['passwordless'] : [];
        $passwordless_mode = isset( $passwordless['mode'] ) ? (string) $passwordless['mode'] : 'off';
        $passkey_supported = ! empty( $passkeys['passkey_supported_helper_result'] ) || ! empty( $passkeys['passkey_core_supported'] );
        $passkey_method_enabled = ! empty( $passkeys['passkey_method_enabled'] );
        $passkey_db_version = isset( $passkeys['passkey_db_version'] ) ? (string) $passkeys['passkey_db_version'] : '';
        $passkey_tables = isset( $passkeys['tables'] ) && is_array( $passkeys['tables'] ) ? $passkeys['tables'] : [];
        $passkey_counts = isset( $passkeys['counts'] ) && is_array( $passkeys['counts'] ) ? $passkeys['counts'] : [];
        $passwordless_counts = isset( $passwordless_accounts['counts'] ) && is_array( $passwordless_accounts['counts'] ) ? $passwordless_accounts['counts'] : [];

        $debug_mode = ! empty( $debug['debug_mode'] );

        $recent_error_line = 'Recent error: none recorded.';

        if ( ! empty( $events ) && is_array( $events ) ) {
            for ( $i = count( $events ) - 1; $i >= 0; $i-- ) {
                $event = $events[ $i ];
                if ( ! is_array( $event ) ) {
                    continue;
                }
                $type = isset( $event['type'] ) ? (string) $event['type'] : '';
                if ( 'error' !== $type ) {
                    continue;
                }

                $timestamp = '';
                if ( isset( $event['timestamp'] ) ) {
                    $timestamp = (string) $event['timestamp'];
                } elseif ( isset( $event['time'] ) ) {
                    $timestamp = (string) $event['time'];
                }

                $context = '';
                if ( isset( $event['context'] ) ) {
                    $context = (string) $event['context'];
                } elseif ( isset( $event['provider'] ) ) {
                    $context = (string) $event['provider'];
                }

                $message = '';
                if ( isset( $event['message'] ) ) {
                    $message = (string) $event['message'];
                } elseif ( isset( $event['detail'] ) ) {
                    $message = (string) $event['detail'];
                }

                $parts = [];
                if ( $timestamp !== '' ) {
                    $parts[] = '[' . $timestamp . ']';
                }
                if ( $context !== '' ) {
                    $parts[] = $context;
                }
                if ( $message !== '' ) {
                    $parts[] = '– ' . $message;
                }

                $line = trim( implode( ' ', $parts ) );
                if ( '' !== $line ) {
                    $recent_error_line = $line;
                }
                break;
            }
        }

        $lines   = [];
        $lines[] = 'Site: ' . $site_url;
        $plugin  = 'Plugin: VentraConnect Social Login';
        if ( '' !== $plugin_version ) {
            $plugin .= ' v' . $plugin_version;
        }
        $lines[] = $plugin;
        $lines[] = 'WP: ' . ( $wp_version !== '' ? $wp_version : 'unknown' ) . ', PHP: ' . ( $php_version !== '' ? $php_version : 'unknown' );
        $lines[] = 'HTTPS: ' . ( $https_on ? 'yes' : 'no' );

        $pro_active  = function_exists( 'vcsl_is_pro_active' ) && vcsl_is_pro_active();
        $pro_version = defined( 'VENTRACONNECT_SL_PRO_VERSION' ) ? VENTRACONNECT_SL_PRO_VERSION : '';

        $lines[] = '--- VentraConnect Social Login Pro ---';
        $lines[] = 'active: ' . ( $pro_active ? 'yes' : 'no' );
        $lines[] = 'version: ' . ( $pro_version ? $pro_version : 'n/a' );

        if ( ! empty( $configured_slugs ) ) {
            $lines[] = 'Providers configured: ' . implode( ', ', array_map( 'strval', $configured_slugs ) );
        } else {
            $lines[] = 'Providers configured: ' . $configured_count;
        }

        // WooCommerce integration status
        $wc_plugin_active  = class_exists( 'WooCommerce' ) || function_exists( 'WC' );
        $wc_vc_enabled     = false;
        $wc_plugin_version = '';

        if ( $wc_plugin_active && function_exists( 'vcsl_is_pro_active' ) && vcsl_is_pro_active() ) {
            if ( function_exists( '\VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings' ) ) {
                $wc_settings = \VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings();
            } else {
                $wc_settings = (array) get_option( 'ventraconnect_sl_wc_settings', [] );
            }

            if ( ! empty( $wc_settings['enabled'] ) ) {
                $wc_vc_enabled = true;
            }
        }

        if ( $wc_plugin_active && function_exists( 'get_plugins' ) ) {
            $all_plugins = get_plugins();
            if ( isset( $all_plugins['woocommerce/woocommerce.php'] ) && ! empty( $all_plugins['woocommerce/woocommerce.php']['Version'] ) ) {
                $wc_plugin_version = $all_plugins['woocommerce/woocommerce.php']['Version'];
            }
        }

        $lines[] = '--- WooCommerce ---';
        $lines[] = 'plugin_active: ' . ( $wc_plugin_active ? 'yes' : 'no' );
        $lines[] = 'plugin_version: ' . ( $wc_plugin_version ? $wc_plugin_version : 'n/a' );
        $lines[] = 'vc_integration_enabled: ' . ( $wc_vc_enabled ? 'yes' : 'no' );

        $passkey_tables_present = ! empty( $passkey_tables )
            && ! empty( $passkey_tables['passkeys']['exists'] )
            && ! empty( $passkey_tables['challenges']['exists'] )
            && ! empty( $passkey_tables['logs']['exists'] );

        $lines[] = '--- Passkeys ---';
        $lines[] = 'supported: ' . ( $passkey_supported ? 'yes' : 'no' );
        $lines[] = 'method_enabled: ' . ( $passkey_method_enabled ? 'yes' : 'no' );
        $lines[] = 'db_version: ' . ( '' !== $passkey_db_version ? $passkey_db_version : 'n/a' );
        $lines[] = 'db_tables_present: ' . ( $passkey_tables_present ? 'yes' : 'no' );
        $lines[] = 'vendor_autoload_exists: ' . ( ! empty( $passkeys['free_vendor_autoload_exists'] ) ? 'yes' : 'no' );
        $lines[] = 'runtime_intentionally_unsupported: ' . ( ! empty( $passkeys['passkey_runtime_intentionally_unsupported'] ) ? 'yes' : 'no' );
        if ( ! empty( $passkey_counts ) ) {
            $lines[] = sprintf(
                'passkey_counts: total=%1$d, active=%2$d, users=%3$d',
                (int) ( $passkey_counts['total_passkeys'] ?? 0 ),
                (int) ( $passkey_counts['active_passkeys'] ?? 0 ),
                (int) ( $passkey_counts['passkey_users'] ?? 0 )
            );
        }

        $lines[] = '--- Passwordless accounts ---';
        $lines[] = 'generate_internal_password_helper: ' . ( ! empty( $passwordless_accounts['generate_internal_password_helper_exists'] ) ? 'yes' : 'no' );
        $lines[] = 'mark_passwordless_account_helper: ' . ( ! empty( $passwordless_accounts['mark_passwordless_account_helper_exists'] ) ? 'yes' : 'no' );
        $lines[] = 'mark_password_set_helper: ' . ( ! empty( $passwordless_accounts['mark_password_set_helper_exists'] ) ? 'yes' : 'no' );
        $lines[] = 'after_password_reset_hook_registered: ' . ( ! empty( $passwordless_accounts['after_password_reset_hook_registered'] ) ? 'yes' : 'no' );
        if ( ! empty( $passwordless_counts ) ) {
            $lines[] = sprintf(
                'passwordless_counts: accounts=%1$d, passkey=%2$d, magic_link=%3$d, email_otp=%4$d, social=%5$d, password_not_set=%6$d',
                (int) ( $passwordless_counts['passwordless_accounts'] ?? 0 ),
                (int) ( $passwordless_counts['created_with_passkey'] ?? 0 ),
                (int) ( $passwordless_counts['created_with_magic_link'] ?? 0 ),
                (int) ( $passwordless_counts['created_with_email_otp'] ?? 0 ),
                (int) ( $passwordless_counts['created_with_social'] ?? 0 ),
                (int) ( $passwordless_counts['passwordless_without_user_password'] ?? 0 )
            );
        }

        $lines[] = 'Passwordless mode: ' . $passwordless_mode;
        $lines[] = 'Debug mode: ' . ( $debug_mode ? 'on' : 'off' );
        $lines[] = '';
        $lines[] = 'Recent error:';
        $lines[] = $recent_error_line;

        $blob = implode( "\n", $lines );

        $blob = apply_filters(
            'ventraconnect_sl_diagnostics_support_blob',
            $blob,
            $snapshot,
            $events
        );

        return $blob;
    }
}

