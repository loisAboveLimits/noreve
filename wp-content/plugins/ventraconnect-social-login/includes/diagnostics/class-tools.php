<?php
namespace VentraConnect\SocialLogin\Diagnostics;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Tools {
    /**
     * Run full site-level diagnostics: health snapshot + derived checks.
     *
     * @return array {
     *   @type array $snapshot Snapshot from Health::get_snapshot().
     *   @type array $checks   Derived checks.
     * }
     */
    public static function run_full(): array {
        // Hardening: ensure only admins (or equivalent) can run full diagnostics,
        // even if this method is called outside the AJAX handler.
        if ( ! current_user_can( 'manage_options' ) ) {
            return [
                'snapshot' => [],
                'checks'   => [
                    'permission' => [
                        'ok'     => false,
                        'detail' => 'Insufficient permissions to run diagnostics.',
                    ],
                ],
            ];
        }

        $snapshot = Health::get_snapshot();
        $checks   = [];

        // External HTTP connectivity (neutral target).
        $ext_ok     = true;
        $ext_detail = '';
        if ( function_exists( 'wp_remote_get' ) ) {
            $resp = wp_remote_get(
                'https://wordpress.org/',
                [
                    'timeout'     => 10,
                    'redirection' => 3,
                    'sslverify'   => true,
                ]
            );

            if ( is_wp_error( $resp ) ) {
                $ext_ok     = false;
                $ext_detail = sprintf(
                    'External HTTP request failed: %1$s – %2$s. This may indicate firewall, DNS, or TLS issues on the server.',
                    $resp->get_error_code(),
                    $resp->get_error_message()
                );
            } else {
                $code = wp_remote_retrieve_response_code( $resp );
                if ( $code >= 200 && $code < 400 ) {
                    $ext_ok     = true;
                    $ext_detail = 'External HTTP appears reachable (wordpress.org responded successfully).';
                } else {
                    $ext_ok     = false;
                    $ext_detail = sprintf(
                        'External HTTP request returned HTTP %d. This may indicate outgoing HTTP issues or upstream problems.',
                        $code
                    );
                }
            }
        } else {
            $ext_ok     = false;
            $ext_detail = 'wp_remote_get() is unavailable; cannot perform external HTTP diagnostics.';
        }

        $checks['external_http'] = [
            'ok'     => $ext_ok,
            'detail' => $ext_detail,
        ];

        // Options API write/read test (non-destructive, temporary key).
        $opt_ok     = false;
        $opt_detail = '';
        $key        = 'ventraconnect_sl_diag_' . ( function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : wp_generate_password( 16, false ) );
        $value      = time();

        try {
            $updated = update_option( $key, $value, false );
            $read    = get_option( $key, null );
            delete_option( $key );

            if ( null !== $read && (int) $read === (int) $value ) {
                $opt_ok     = true;
                $opt_detail = 'Options API write/read test succeeded.';
            } elseif ( $updated && null === $read ) {
                $opt_ok     = false;
                $opt_detail = 'Options API write/read test could not confirm stored value; this may indicate database or caching issues.';
            } else {
                $opt_ok     = false;
                $opt_detail = 'Options API write/read test failed – this may indicate database issues or blocked option writes.';
            }
        } catch ( \Throwable $e ) {
            $opt_ok     = false;
            $opt_detail = 'Options API write/read test threw an error: ' . $e->getMessage();
        }

        $checks['options_write'] = [
            'ok'     => $opt_ok,
            'detail' => $opt_detail,
        ];

        // wp_mail capability check (no actual email sent).
        $mail_ok     = function_exists( 'wp_mail' );
        $mail_detail = $mail_ok
            ? 'wp_mail() is available. Actual delivery still depends on your mail server or SMTP plugin.'
            : 'wp_mail() is not available or has been removed; email-based flows may not work correctly.';

        $checks['wp_mail'] = [
            'ok'     => $mail_ok,
            'detail' => $mail_detail,
        ];

        $passkeys = isset( $snapshot['passkeys'] ) && is_array( $snapshot['passkeys'] ) ? $snapshot['passkeys'] : [];
        $passkey_tables = isset( $passkeys['tables'] ) && is_array( $passkeys['tables'] ) ? $passkeys['tables'] : [];
        $passkey_counts = isset( $passkeys['counts'] ) && is_array( $passkeys['counts'] ) ? $passkeys['counts'] : [];
        $passwordless_accounts = isset( $snapshot['passwordless_accounts'] ) && is_array( $snapshot['passwordless_accounts'] ) ? $snapshot['passwordless_accounts'] : [];

        $passkey_supported = ! empty( $passkeys['passkey_supported_helper_result'] ) || ! empty( $passkeys['passkey_core_supported'] );
        $passkey_php_unsupported = ! empty( $passkeys['passkey_runtime_intentionally_unsupported'] );
        $passkey_method_enabled = ! empty( $passkeys['passkey_method_enabled'] );
        $vendor_autoload_exists = ! empty( $passkeys['free_vendor_autoload_exists'] );
        $passkey_db_version = isset( $passkeys['passkey_db_version'] ) ? (string) $passkeys['passkey_db_version'] : '';

        $checks['passkey_php_support_gate'] = [
            'ok'     => $passkey_supported || $passkey_php_unsupported,
            'detail' => $passkey_php_unsupported
                ? __( 'Native passkeys are intentionally disabled on this site because PHP is below 8.2.', 'ventraconnect-social-login' )
                : __( 'Native passkeys can run on this PHP version.', 'ventraconnect-social-login' ),
        ];

        $checks['passkey_method_status'] = [
            'ok'     => true,
            'detail' => $passkey_method_enabled
                ? __( 'The Free passkey method is enabled in VentraConnect settings.', 'ventraconnect-social-login' )
                : __( 'The Free passkey method is currently disabled in VentraConnect settings.', 'ventraconnect-social-login' ),
        ];

        $passkey_tables_present = ! empty( $passkey_tables )
            && ! empty( $passkey_tables['passkeys']['exists'] )
            && ! empty( $passkey_tables['challenges']['exists'] )
            && ! empty( $passkey_tables['logs']['exists'] );

        $passkey_table_detail = __( 'All native passkey database tables are present.', 'ventraconnect-social-login' );
        if ( ! $passkey_tables_present ) {
            $passkey_table_detail = $passkey_method_enabled
                ? __( 'One or more native passkey database tables are missing while the passkey method is enabled.', 'ventraconnect-social-login' )
                : __( 'One or more native passkey database tables are not present yet. This is informational while passkeys remain disabled.', 'ventraconnect-social-login' );
        }
        if ( ! empty( $passkey_counts ) && ! empty( $passkey_tables['passkeys']['exists'] ) ) {
            $passkey_table_detail .= ' ' . sprintf(
                /* translators: 1: total passkeys, 2: active passkeys, 3: users with passkeys. */
                __( 'Counts: %1$d total, %2$d active, %3$d users.', 'ventraconnect-social-login' ),
                (int) ( $passkey_counts['total_passkeys'] ?? 0 ),
                (int) ( $passkey_counts['active_passkeys'] ?? 0 ),
                (int) ( $passkey_counts['passkey_users'] ?? 0 )
            );
        }

        $checks['passkey_db_tables'] = [
            'ok'     => $passkey_tables_present || ! $passkey_method_enabled,
            'detail' => $passkey_table_detail,
        ];

        $checks['passkey_vendor_autoload'] = [
            'ok'     => $vendor_autoload_exists || $passkey_php_unsupported,
            'detail' => $passkey_php_unsupported
                ? __( 'The Free passkey vendor autoload file is not required while PHP support is intentionally gated off.', 'ventraconnect-social-login' )
                : ( $vendor_autoload_exists
                    ? __( 'The Free passkey vendor autoload file is present.', 'ventraconnect-social-login' )
                    : __( 'The Free passkey vendor autoload file is missing.', 'ventraconnect-social-login' ) ),
        ];

        $checks['passwordless_internal_password_helper'] = [
            'ok'     => ! empty( $passwordless_accounts['generate_internal_password_helper_exists'] ),
            'detail' => ! empty( $passwordless_accounts['generate_internal_password_helper_exists'] )
                ? __( 'Internal random password generation helper is available for passwordless account creation.', 'ventraconnect-social-login' )
                : __( 'Internal random password generation helper is missing.', 'ventraconnect-social-login' ),
        ];

        $checks['passwordless_account_marker_helper'] = [
            'ok'     => ! empty( $passwordless_accounts['mark_passwordless_account_helper_exists'] ),
            'detail' => ! empty( $passwordless_accounts['mark_passwordless_account_helper_exists'] )
                ? __( 'Passwordless account creation marker helper is available.', 'ventraconnect-social-login' )
                : __( 'Passwordless account creation marker helper is missing.', 'ventraconnect-social-login' ),
        ];

        $checks['passwordless_password_reset_hook'] = [
            'ok'     => ! empty( $passwordless_accounts['after_password_reset_hook_registered'] ) && ! empty( $passwordless_accounts['mark_password_set_helper_exists'] ),
            'detail' => ( ! empty( $passwordless_accounts['after_password_reset_hook_registered'] ) && ! empty( $passwordless_accounts['mark_password_set_helper_exists'] ) )
                ? __( 'Password reset hook is registered to mark when a passwordless account later gets a user-set password.', 'ventraconnect-social-login' )
                : __( 'Password reset marker hook or helper is missing.', 'ventraconnect-social-login' ),
        ];

        $checks = apply_filters(
            'ventraconnect_sl_diagnostics_checks',
            $checks,
            $snapshot
        );

        return [
            'snapshot' => $snapshot,
            'checks'   => $checks,
        ];
    }
}
