<?php
/**
 * Plugin Name: Social Login, Passkeys, Magic Link & Email OTP – Passwordless Login by VentraConnect
 * Description:  Social login & passwordless login with Passkeys, Magic Link and Email OTP, plus Guardrails to control spam registrations.
 * Author: Fahad Aslam
 * Author URI: https://wpventra.com
 * Version: 1.4.4
 * Requires at least: 6.2
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Text Domain: ventraconnect-social-login
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Add Settings link to plugin row actions on Plugins page.
add_filter(
    'plugin_action_links_' . plugin_basename( __FILE__ ),
    function( $links ) {
        $settings_url = admin_url( 'admin.php?page=ventraconnect-sl-settings' );
        array_unshift(
            $links,
            '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'ventraconnect-social-login' ) . '</a>'
        );
        return $links;
    }
);

// Core constants.
if ( ! defined( 'VENTRACONNECT_SL_VERSION' ) ) {
    define( 'VENTRACONNECT_SL_VERSION', '1.4.4' );
}
if ( ! defined( 'VENTRACONNECT_SL_OTP_SECURITY_MIGRATION' ) ) {
    define( 'VENTRACONNECT_SL_OTP_SECURITY_MIGRATION', '2026_06_24_otp_hmac_v1' );
}
if ( ! defined( 'VENTRACONNECT_SL_PLUGIN_FILE' ) ) {
    define( 'VENTRACONNECT_SL_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ) {
    define( 'VENTRACONNECT_SL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ) {
    define( 'VENTRACONNECT_SL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) ) {
    /**
     * Whether the native Free passkey core can be considered supported.
     *
     * Important:
     * - This is a PHP capability gate only.
     * - WebAuthn runtime and vendor files must never be loaded below PHP 8.2.
     * - Support here does not mean the Free runtime is active yet.
     */
    define( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED', PHP_VERSION_ID >= 80200 );
}

// Passkeys core foundation bootstrap.
if ( file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/core/bootstrap.php' ) ) {
    require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/passkeys/core/bootstrap.php';
}

if ( ! function_exists( 'ventraconnect_sl_maybe_upgrade_passkeys_core' ) ) {
    /**
     * Run the Free passkeys DB installer only when the PHP support gate is open.
     *
     * Important:
     * - This prepares DB ownership only.
     * - Native passkey runtime is still inactive in this phase.
     * - This must never load vendor/WebAuthn runtime on unsupported PHP.
     *
     * @return void
     */
    function ventraconnect_sl_maybe_upgrade_passkeys_core() {
        if ( ! defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) || ! VENTRACONNECT_PASSKEYS_CORE_SUPPORTED ) {
            return;
        }

        if ( ! class_exists( 'VentraConnect_SL_Passkeys_Core_Installer', false ) ) {
            return;
        }

        VentraConnect_SL_Passkeys_Core_Installer::maybe_upgrade();
    }
}

if ( ! function_exists( 'ventraconnect_sl_boot_passkeys_management_foundation' ) ) {
    /**
     * Boot Free-owned passkeys admin/profile management foundation on supported PHP only.
     *
     * Important:
     * - This phase is limited to native logged-in/admin surfaces.
     * - Public login/register buttons and auth AJAX remain inactive.
     *
     * @return void
     */
    function ventraconnect_sl_boot_passkeys_management_foundation() {
        if ( ! defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) || ! VENTRACONNECT_PASSKEYS_CORE_SUPPORTED ) {
            return;
        }

        if ( ! is_admin() ) {
            return;
        }

        if ( ! class_exists( 'VentraConnect_SL_Passkeys_Admin', false ) ) {
            return;
        }

        static $booted = false;

        if ( $booted ) {
            return;
        }

        $booted = true;

        $admin = new VentraConnect_SL_Passkeys_Admin();
        $admin->init();
    }
}

if ( ! function_exists( 'ventraconnect_sl_register_passkeys_management_ajax' ) ) {
    /**
     * Register Free native logged-in passkeys management AJAX routes.
     *
     * Important:
     * - Logged-in profile management only.
     * - No public login/register/discoverable AJAX is registered here.
     *
     * @return void
     */
    function ventraconnect_sl_register_passkeys_management_ajax() {
        if ( ! defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) || ! VENTRACONNECT_PASSKEYS_CORE_SUPPORTED ) {
            return;
        }

        if ( ! class_exists( 'VentraConnect_SL_Passkeys_Management_Ajax', false ) ) {
            return;
        }

        static $registered = false;

        if ( $registered ) {
            return;
        }

        $registered = true;

        $ajax = new VentraConnect_SL_Passkeys_Management_Ajax();
        $ajax->register_hooks();
    }
}

// Autoloader.
require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-loader.php';

if ( ! function_exists( 'ventraconnect_sl_generate_internal_account_password' ) ) {
    /**
     * Generate a secure internal password for passwordless-created accounts.
     *
     * @return string
     */
    function ventraconnect_sl_generate_internal_account_password() {
        return wp_generate_password( 32, true, true );
    }
}

if ( ! function_exists( 'ventraconnect_sl_normalize_created_with_method' ) ) {
    /**
     * Normalize a creation method slug for account metadata.
     *
     * @param string $method Raw method/provider slug.
     * @return string
     */
    function ventraconnect_sl_normalize_created_with_method( $method ) {
        $method = sanitize_key( (string) $method );

        if ( 'otp_email' === $method ) {
            return 'email_otp';
        }

        return $method;
    }
}

if ( ! function_exists( 'ventraconnect_sl_mark_passwordless_account_created' ) ) {
    /**
     * Mark a newly created VentraConnect passwordless account with core metadata.
     *
     * @param int    $user_id  User ID.
     * @param string $method   Creation method/provider slug.
     * @param string $context  Optional creation context.
     * @return void
     */
    function ventraconnect_sl_mark_passwordless_account_created( $user_id, $method, $context = '' ) {
        $user_id = absint( $user_id );

        if ( $user_id <= 0 ) {
            return;
        }

        $method  = ventraconnect_sl_normalize_created_with_method( $method );
        $context = sanitize_key( (string) $context );

        update_user_meta( $user_id, '_ventraconnect_created_with', $method );
        update_user_meta( $user_id, '_ventraconnect_passwordless_account', 'yes' );
        if ( 'yes' !== (string) get_user_meta( $user_id, '_ventraconnect_password_set_by_user', true ) ) {
            update_user_meta( $user_id, '_ventraconnect_password_set_by_user', 'no' );
        }

        /**
         * Fires after VentraConnect creates a new passwordless account.
         *
         * @param int    $user_id User ID.
         * @param string $method  Creation method/provider slug.
         * @param string $context Optional creation context slug.
         */
        do_action( 'ventraconnect_sl_passwordless_user_created', $user_id, $method, $context );
    }
}

if ( ! function_exists( 'ventraconnect_sl_mark_password_set_by_user' ) ) {
    /**
     * Mark that a user has set a password through a native WordPress reset flow.
     *
     * @param int $user_id User ID.
     * @return void
     */
    function ventraconnect_sl_mark_password_set_by_user( $user_id ) {
        $user_id = absint( $user_id );

        if ( $user_id <= 0 ) {
            return;
        }

        update_user_meta( $user_id, '_ventraconnect_password_set_by_user', 'yes' );
    }
}

if ( ! function_exists( 'ventraconnect_sl_maybe_run_otp_security_migration' ) ) {
    /**
     * Revoke legacy plaintext Email OTP rows once after the security update.
     *
     * @return void
     */
    function ventraconnect_sl_maybe_run_otp_security_migration() {
        $stored = (string) get_option( 'ventraconnect_sl_otp_security_migration', '' );
        if ( VENTRACONNECT_SL_OTP_SECURITY_MIGRATION === $stored ) {
            return;
        }

        \VentraConnect\SocialLogin\Services\Token_Auth::maybe_create_table();
        \VentraConnect\SocialLogin\Services\Token_Auth::maybe_schedule_cron();
        if ( \VentraConnect\SocialLogin\Services\Token_Auth::revoke_legacy_otp_tokens() ) {
            update_option( 'ventraconnect_sl_otp_security_migration', VENTRACONNECT_SL_OTP_SECURITY_MIGRATION, false );
        }
    }
}

if ( ! function_exists( 'ventraconnect_sl_after_password_reset' ) ) {
    /**
     * Track when a user sets a password through WordPress' password reset flow.
     *
     * @param WP_User $user     User object.
     * @param string  $new_pass New password provided by WordPress.
     * @return void
     */
    function ventraconnect_sl_after_password_reset( $user, $new_pass ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        if ( ! $user instanceof WP_User ) {
            return;
        }

        ventraconnect_sl_mark_password_set_by_user( $user->ID );
    }
}

add_action( 'after_password_reset', 'ventraconnect_sl_after_password_reset', 10, 2 );

if ( ! function_exists( 'ventraconnect_sl_is_pro_active' ) ) {
    /**
     * Whether the Pro add-on is active.
     *
     * @return bool
     */
    function ventraconnect_sl_is_pro_active(): bool {
        return defined( 'VENTRACONNECT_SL_PRO_ACTIVE' ) && VENTRACONNECT_SL_PRO_ACTIVE;
    }
}

if ( ! function_exists( 'vcsl_is_pro_active' ) ) {
    /**
     * Unified helper to check whether Pro is effectively active.
     *
     * Prefer the Pro_Gates::is_pro() helper when available, then fall back
     * to legacy helpers/constants.
     *
     * @return bool
     */
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- public helper kept for backwards compatibility.
    function vcsl_is_pro_active(): bool {
        if ( class_exists( '\VentraConnect\SocialLogin\Pro_Gates' ) && method_exists( '\VentraConnect\SocialLogin\Pro_Gates', 'is_pro' ) ) {
            return \VentraConnect\SocialLogin\Pro_Gates::is_pro();
        }

        if ( function_exists( 'ventraconnect_sl_is_pro_active' ) ) {
            return ventraconnect_sl_is_pro_active();
        }

        if ( defined( 'VENTRACONNECT_SL_PRO_ACTIVE' ) ) {
            return (bool) VENTRACONNECT_SL_PRO_ACTIVE;
        }
        if ( defined( 'VENTRACONNECT_PRO' ) ) {
            return (bool) VENTRACONNECT_PRO;
        }
        if ( defined( 'VCS_PRO_ACTIVE' ) ) {
            return (bool) VCS_PRO_ACTIVE;
        }

        return false;
    }
}

if ( ! function_exists( 'ventraconnect_sl_is_passkey_supported' ) ) {
    /**
     * Whether Free can safely support native passkey core on this PHP version.
     *
     * This must remain a pure capability check. It must not load vendor files,
     * initialize WebAuthn runtime, or imply frontend availability.
     *
     * @return bool
     */
    function ventraconnect_sl_is_passkey_supported() {
        return defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) && VENTRACONNECT_PASSKEYS_CORE_SUPPORTED;
    }
}

if ( ! function_exists( 'ventraconnect_sl_get_current_php_version_label' ) ) {
    /**
     * Get the current PHP version as a safe display string.
     *
     * @return string
     */
    function ventraconnect_sl_get_current_php_version_label() {
        return is_string( PHP_VERSION ) ? PHP_VERSION : '';
    }
}

if ( ! function_exists( 'ventraconnect_sl_get_passkey_php_requirement_message' ) ) {
    /**
     * Get the centralized Free passkey PHP requirement notice.
     *
     * @return string
     */
    function ventraconnect_sl_get_passkey_php_requirement_message() {
        return sprintf(
            /* translators: %s: current PHP version. */
            __( 'Passkeys require PHP 8.2 or higher. Your site is currently running PHP %s. Please update PHP to use passkeys.', 'ventraconnect-social-login' ),
            ventraconnect_sl_get_current_php_version_label()
        );
    }
}

if ( ! function_exists( 'ventraconnect_sl_is_passkey_method_enabled' ) ) {
    /**
     * Whether the Free Passkey method is enabled in settings.
     *
     * Important:
     * - This remains a pure settings check.
     * - It must not imply runtime activation outside the native PHP support gate.
     *
     * @return bool
     */
    function ventraconnect_sl_is_passkey_method_enabled() {
        if ( ! function_exists( 'ventraconnect_sl_is_passkey_supported' ) || ! ventraconnect_sl_is_passkey_supported() ) {
            return false;
        }

        if ( ! class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
            return false;
        }

        $settings  = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $providers = isset( $settings['providers'] ) && is_array( $settings['providers'] ) ? array_map( 'sanitize_key', $settings['providers'] ) : array();

        return in_array( 'passkey', $providers, true );
    }
}

if ( ! function_exists( 'ventraconnect_sl_boot_passkeys_public_foundation' ) ) {
    /**
     * Boot native WordPress public passkeys foundation on supported PHP only.
     *
     * Important:
     * - Native wp-login/wp-register only.
     * - No Woo/LMS/membership contexts belong here.
     * - Runtime stays disabled unless the Passkey method is enabled in Free settings.
     *
     * @return void
     */
    function ventraconnect_sl_boot_passkeys_public_foundation() {
        if ( ! defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) || ! VENTRACONNECT_PASSKEYS_CORE_SUPPORTED ) {
            return;
        }

        if ( ! ventraconnect_sl_is_passkey_method_enabled() ) {
            return;
        }

        if ( ! class_exists( 'VentraConnect_SL_Passkeys_Public_Frontend', false ) ) {
            return;
        }

        static $booted = false;

        if ( $booted ) {
            return;
        }

        $booted = true;

        $frontend = new VentraConnect_SL_Passkeys_Public_Frontend();
        $frontend->register_hooks();
    }
}

if ( ! function_exists( 'ventraconnect_sl_register_passkeys_public_ajax' ) ) {
    /**
     * Register native WordPress public passkeys AJAX routes on supported PHP only.
     *
     * Important:
     * - Native wp-login/wp-register only.
     * - No integration or checkout routes are registered here.
     *
     * @return void
     */
    function ventraconnect_sl_register_passkeys_public_ajax() {
        if ( ! defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) || ! VENTRACONNECT_PASSKEYS_CORE_SUPPORTED ) {
            return;
        }

        if ( ! ventraconnect_sl_is_passkey_method_enabled() ) {
            return;
        }

        if ( ! class_exists( 'VentraConnect_SL_Passkeys_Public_Ajax', false ) ) {
            return;
        }

        static $registered = false;

        if ( $registered ) {
            return;
        }

        $registered = true;

        $ajax = new VentraConnect_SL_Passkeys_Public_Ajax();
        $ajax->register_hooks();
    }
}

// Load shared helpers (non-class files)
if ( file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'includes/helpers-woo.php' ) ) {
    require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/helpers-woo.php';
}
if ( file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'includes/helpers-guardrails.php' ) ) {
    require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/helpers-guardrails.php';
}
if ( file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'includes/helpers-redirects.php' ) ) {
    require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/helpers-redirects.php';
}

add_action(
    'plugins_loaded',
    function() {
        /**
         * Allow the Pro add-on to bootstrap itself.
         *
         * @since 1.2.0
         */
        do_action( 'ventraconnect_sl_pro_bootstrap' );
    },
    5
);

if ( class_exists( '\VentraConnect\SocialLogin\Shortcodes' ) ) {
    \VentraConnect\SocialLogin\Shortcodes::init();
}

// Register site-level diagnostics AJAX routes for admins.
add_action(
    'admin_init',
    function() {
        if ( class_exists( '\VentraConnect\SocialLogin\Diagnostics\Ajax' ) ) {
            \VentraConnect\SocialLogin\Diagnostics\Ajax::register();
        }
    }
);

// Store provider credentials in a separate, non-autoloaded option on save.
add_filter(
    'pre_update_option_ventraconnect_sl_settings',
    function( $value, $old_value, $option ) {
        if ( isset( $value['provider_creds'] ) && is_array( $value['provider_creds'] ) ) {
            $incoming = (array) $value['provider_creds'];
            $existing = get_option( 'ventraconnect_sl_provider_creds', false );
            if ( false === $existing ) {
                add_option( 'ventraconnect_sl_provider_creds', [], '', 'no' );
                $existing = [];
            }
            $existing = (array) $existing;
            $slugs    = [ 'google','facebook','github','microsoft','linkedin','slack','amazon','yahoo','wordpress','discord','spotify','line','twitter','tiktok','twitch','reddit' ];
            foreach ( $slugs as $slug ) {
                if ( isset( $incoming[ $slug ] ) && is_array( $incoming[ $slug ] ) ) {
                    if ( array_key_exists( 'client_id', $incoming[ $slug ] ) ) {
                        $existing[ $slug ]['client_id'] = sanitize_text_field( $incoming[ $slug ]['client_id'] );
                    }
                    if ( array_key_exists( 'client_secret', $incoming[ $slug ] ) ) {
                        $existing[ $slug ]['client_secret'] = sanitize_text_field( $incoming[ $slug ]['client_secret'] );
                    }
                }
            }
            update_option( 'ventraconnect_sl_provider_creds', $existing );
            unset( $value['provider_creds'] );
        }
        return $value;
    },
    10,
    3
);

// Activation: set defaults and migrate from legacy options if present.
register_activation_hook(
    __FILE__,
    function() {
        // Prepare Free-owned passkeys DB tables only when the PHP support gate is open.
        ventraconnect_sl_maybe_upgrade_passkeys_core();
        ventraconnect_sl_maybe_run_otp_security_migration();

        // Settings that may autoload.
        $default_settings = [
            'wp_login_enabled'    => true,
            'wp_register_enabled' => true,
            'comments_enabled'    => false,
            // Do not activate any providers by default on first install.
            'providers'           => [],
            // Default button style.
            'button_style'        => 'wide',
        ];
        // Provider credentials should not autoload.
        $default_creds = [
            'google'   => [ 'client_id' => '', 'client_secret' => '' ],
            'facebook' => [ 'client_id' => '', 'client_secret' => '' ],
            'twitch'   => [ 'client_id' => '', 'client_secret' => '' ],
            'reddit'   => [ 'client_id' => '', 'client_secret' => '' ],
        ];

        $existing = get_option( 'ventraconnect_sl_settings', [] );
        $legacy   = get_option( 'wsc_settings', [] );

        // Optional migration from very old wsc_settings, only when canonical settings are empty.
        if ( ! empty( $legacy ) && empty( $existing ) ) {
            $migrated_settings = [
                'wp_login_enabled'    => ! empty( $legacy['enable_wp_login_buttons'] ),
                'wp_register_enabled' => ! empty( $legacy['enable_wp_login_buttons'] ),
                'comments_enabled'    => false,
                'providers'           => [ $legacy['free_provider'] ?? 'google' ],
            ];
            $migrated_creds = [
                'google'   => $legacy['credentials']['google'] ?? $default_creds['google'],
                'facebook' => $legacy['credentials']['facebook'] ?? $default_creds['facebook'],
            ];
            update_option( 'ventraconnect_sl_settings', wp_parse_args( $migrated_settings, $default_settings ) );
            if ( false === get_option( 'ventraconnect_sl_provider_creds', false ) ) {
                add_option( 'ventraconnect_sl_provider_creds', $migrated_creds, '', 'no' );
            } else {
                update_option( 'ventraconnect_sl_provider_creds', $migrated_creds );
            }
            ventraconnect_sl_maybe_run_otp_security_migration();
            return;
        }

        // Create creds option if missing (non-autoload).
        if ( false === get_option( 'ventraconnect_sl_provider_creds', false ) ) {
            add_option( 'ventraconnect_sl_provider_creds', $default_creds, '', 'no' );
        }

        // If settings array contains provider_creds, migrate them out.
        if ( isset( $existing['provider_creds'] ) && is_array( $existing['provider_creds'] ) ) {
            update_option( 'ventraconnect_sl_provider_creds', $existing['provider_creds'] );
            unset( $existing['provider_creds'] );
        }

        update_option( 'ventraconnect_sl_settings', wp_parse_args( $existing, $default_settings ) );

        /**
         * Allow Pro add-on to perform activation tasks (e.g., token tables).
         *
         * @since 1.2.0
         */
        do_action( 'ventraconnect_sl_pro_on_activate' );
    }
);

// Keep the Free-owned passkeys schema up to date on supported PHP after plugin updates.
add_action(
    'plugins_loaded',
    function() {
        ventraconnect_sl_maybe_upgrade_passkeys_core();
        ventraconnect_sl_maybe_run_otp_security_migration();
    },
    1
);

add_action(
    'plugins_loaded',
    function() {
        ventraconnect_sl_boot_passkeys_management_foundation();
    },
    20
);

add_action(
    'plugins_loaded',
    function() {
        ventraconnect_sl_register_passkeys_management_ajax();
    },
    20
);

add_action(
    'plugins_loaded',
    function() {
        ventraconnect_sl_boot_passkeys_public_foundation();
    },
    20
);

add_action(
    'plugins_loaded',
    function() {
        ventraconnect_sl_register_passkeys_public_ajax();
    },
    20
);

// Register AJAX routes early.
add_action(
    'init',
    function() {
        \VentraConnect\SocialLogin\OAuth::register_ajax_routes();
        \VentraConnect\SocialLogin\Diagnostics::register_ajax_routes();
        \VentraConnect\SocialLogin\Services\Token_Auth::register_routes();
        /**
         * Give Pro add-on an opportunity to register additional AJAX routes.
         *
         * @since 1.2.0
         */
        do_action( 'ventraconnect_sl_pro_register_ajax_routes' );
    }
);

// Bootstrap settings and integrations.
add_action(
    'plugins_loaded',
    function() {
        if ( is_admin() ) {
            ( new \VentraConnect\SocialLogin\Settings() )->hooks();
            ( new \VentraConnect\SocialLogin\Admin_User() )->register();
            require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/admin/helpers-esc.php';
            // Load WooCommerce admin helpers (Pro tab renderer).
            if ( file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'includes/admin/class-vcs-admin.php' ) ) {
                require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/admin/class-vcs-admin.php';
                \VentraConnect\SocialLogin\Admin\VCS_Admin::init();
            }
        }
        $buttons = new \VentraConnect\SocialLogin\Buttons();
        $links   = new \VentraConnect\SocialLogin\User_Links();
        ( new \VentraConnect\SocialLogin\Hooks( $buttons, $links ) )->register();
        // Ensure profile sync service hooks (avatar, names, email) are available on every request.
        if ( file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'includes/services/class-profile-sync.php' ) ) {
            require_once VENTRACONNECT_SL_PLUGIN_DIR . 'includes/services/class-profile-sync.php';
        }

        // Always show link/unlink UI on Woo My Account if WooCommerce is active.
        // This is lightweight and independent of Pro checkout placements.
        if ( class_exists( 'WooCommerce' ) ) {
            ( new \VentraConnect\SocialLogin\Integrations\Woo_Integration( $buttons ) )->register();
        }

        /**
         * Allow the Pro add-on to register additional frontend modules.
         *
         * @since 1.2.0
         *
         * @param \VentraConnect\SocialLogin\Buttons $buttons
         */
        do_action( 'ventraconnect_sl_pro_register_frontend_modules', $buttons );

        /**
         * Provide a hook for the Pro add-on to boot deeper integrations.
         *
         * @since 1.2.0
         *
         * @param \VentraConnect\SocialLogin\Buttons     $buttons
         * @param \VentraConnect\SocialLogin\User_Links $links
         */
        do_action( 'ventraconnect_sl_pro_after_core_boot', $buttons, $links );
    }
);

// Daily cron: purge expired/used tokens
add_action(
    'ventraconnect_sl_purge_tokens_daily',
    function() {
        \VentraConnect\SocialLogin\Services\Token_Auth::purge_expired();
        do_action( 'ventraconnect_sl_pro_purge_tokens' );
    }
);
