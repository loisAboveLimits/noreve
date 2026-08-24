<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Passwordless {

    public static function get_mode(): string {
        // If Pro is not active, treat passwordless as off.
        if ( ! defined( 'VCS_PRO_ACTIVE' ) || ! VCS_PRO_ACTIVE ) {
            return 'off';
        }

        $settings = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $mode     = isset( $settings['passwordless_mode'] ) && is_string( $settings['passwordless_mode'] )
            ? $settings['passwordless_mode']
            : 'off';

        if ( in_array( $mode, array( 'off', 'recommended', 'strict' ), true ) ) {
            return $mode;
        }

        return 'off';
    }

    public static function is_enabled(): bool {
        return 'off' !== self::get_mode();
    }

    public static function is_soft(): bool {
        return 'recommended' === self::get_mode();
    }

    public static function is_strict(): bool {
        // If a password bypass is active for this request, treat Strict as off.
        if ( self::is_password_bypass_active() ) {
            return false;
        }

        return 'strict' === self::get_mode();
    }

    /**
     * Determine whether a per-request password bypass is active.
     *
     * @return bool
     */
    private static function is_password_bypass_active(): bool {
        // 1) Global "break-glass" override: if defined and truthy, always allow passwords.
        if ( defined( 'VENTRACONNECT_ALLOW_PASSWORD' ) && VENTRACONNECT_ALLOW_PASSWORD ) {
            return true;
        }

        // 2) Per-request bypass via secret URL flag (e.g. wp-login.php?vcsl_pwless_bypass=1).
        //    This restores normal password behaviour for this request (still requires a valid password).
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- public bypass flag checked in a controlled way.
        $bypass_pwless = false;
        if ( isset( $_GET['vcsl_pwless_bypass'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $raw_bypass   = wp_unslash( (string) $_GET['vcsl_pwless_bypass'] );
            $bypass_value = sanitize_text_field( $raw_bypass );

            // Treat "1" or "true" (case-insensitive) as explicit bypass.
            if ( '1' === $bypass_value || 'true' === strtolower( $bypass_value ) ) {
                $bypass_pwless = true;
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        // 3) Allow external filters to override or extend the bypass logic.
        return (bool) apply_filters( 'ventraconnect_sl_passwordless_bypass_active', $bypass_pwless );
    }

    /**
     * Get all enabled provider slugs from settings.
     *
     * @return array
     */
    private static function get_enabled_providers(): array {
        $settings  = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $providers = array();

        if ( isset( $settings['providers'] ) && is_array( $settings['providers'] ) ) {
            $providers = $settings['providers'];
        }

        return $providers;
    }

    /**
     * Check if Magic Link provider is enabled.
     */
    public static function is_magic_link_enabled(): bool {
        $providers = self::get_enabled_providers();
        return in_array( 'magic_link', $providers, true );
    }

    /**
     * Check if OTP Email provider is enabled.
     */
    public static function is_otp_enabled(): bool {
        $providers = self::get_enabled_providers();
        return in_array( 'otp_email', $providers, true );
    }

    /**
     * Check if Passkey is safely available as a passwordless method.
     */
    public static function is_passkey_enabled(): bool {
        return function_exists( 'ventraconnect_sl_is_passkey_supported' )
            && ventraconnect_sl_is_passkey_supported()
            && function_exists( 'ventraconnect_sl_is_passkey_method_enabled' )
            && ventraconnect_sl_is_passkey_method_enabled()
            && class_exists( 'VentraConnect_SL_Passkeys_Core_Passkey_Repository', false );
    }

    /**
     * Count active social providers (excluding Magic Link and OTP).
     */
    public static function get_social_active_providers_count(): int {
        $providers = self::get_enabled_providers();
        if ( empty( $providers ) ) {
            return 0;
        }

        $social = array_diff( $providers, array( 'magic_link', 'otp_email', 'passkey' ) );
        return count( $social );
    }

    /**
     * Whether any social provider is enabled.
     */
    public static function has_social_enabled(): bool {
        return self::get_social_active_providers_count() > 0;
    }

    /**
     * Summarise enabled passwordless methods.
     *
     * @return array
     */
    public static function get_enabled_methods(): array {
        $social_count = self::get_social_active_providers_count();

        $methods = array(
            'social'        => $social_count > 0,
            'magic_link'    => self::is_magic_link_enabled(),
            'otp'           => self::is_otp_enabled(),
            'passkey'       => self::is_passkey_enabled(),
            'social_count'  => $social_count,
            'enabled_count' => 0,
        );

        $enabled_flags            = array( $methods['social'], $methods['magic_link'], $methods['otp'], $methods['passkey'] );
        $methods['enabled_count'] = count( array_filter( $enabled_flags ) );

        return $methods;
    }

    /**
     * Build a human-readable list of enabled methods for login copy.
     *
     * @return string
     */
    public static function build_login_methods_phrase(): string {
        $methods = self::get_enabled_methods();
        if ( $methods['enabled_count'] <= 0 ) {
            return '';
        }

        $labels = array();

        if ( $methods['social'] ) {
            $labels[] = esc_html__( 'Social Login', 'ventraconnect-social-login' );
        }
        if ( $methods['magic_link'] ) {
            $labels[] = esc_html__( 'an email Magic Link', 'ventraconnect-social-login' );
        }
        if ( $methods['otp'] ) {
            $labels[] = esc_html__( 'a one-time code sent by email', 'ventraconnect-social-login' );
        }
        if ( $methods['passkey'] ) {
            $labels[] = esc_html__( 'a passkey', 'ventraconnect-social-login' );
        }

        return self::human_join_or( $labels );
    }

    /**
     * Build a human-readable list of enabled methods for registration copy.
     *
     * @return string
     */
    public static function build_register_methods_phrase(): string {
        $methods = self::get_enabled_methods();
        if ( $methods['enabled_count'] <= 0 ) {
            return '';
        }

        $labels = array();

        if ( $methods['social'] ) {
            $labels[] = esc_html__( 'Social Login', 'ventraconnect-social-login' );
        }
        if ( $methods['magic_link'] ) {
            $labels[] = esc_html__( 'an email Magic Link', 'ventraconnect-social-login' );
        }
        if ( $methods['otp'] ) {
            $labels[] = esc_html__( 'a one-time code sent by email', 'ventraconnect-social-login' );
        }
        if ( $methods['passkey'] ) {
            $labels[] = esc_html__( 'a passkey', 'ventraconnect-social-login' );
        }

        return self::human_join_or( $labels );
    }

    /**
     * Join items into a human-readable list using "or".
     *
     * @param array $items List of labels.
     * @return string
     */
    private static function human_join_or( array $items ): string {
        $items = array_values(
            array_filter(
                $items,
                static function ( $item ) {
                    return '' !== $item && null !== $item;
                }
            )
        );

        $count = count( $items );

        if ( 0 === $count ) {
            return '';
        }

        if ( 1 === $count ) {
            return (string) $items[0];
        }

        if ( 2 === $count ) {
            
            return sprintf(
                // Translators: %1$s and %2$s are the two items in the list.
                esc_html__( '%1$s or %2$s', 'ventraconnect-social-login' ),
                $items[0],
                $items[1]
            );
        }

        $last  = array_pop( $items );
        $first = implode( ', ', $items );
       
        return sprintf(
             // Translators: %1$s is the comma-separated list of items except the last one; %2$s is the final item.
            esc_html__( '%1$s, or %2$s', 'ventraconnect-social-login' ),
            $first,
            $last
        );
    }

    /**
     * Get status for all known login contexts.
     *
     * @return array
     */
    public static function get_login_context_status(): array {
        $statuses = array();

        // Main settings (ventraconnect_sl_settings).
        $settings = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();

        // Core WordPress login.
        $statuses['wp_login'] = array(
            'label'     => esc_html__( 'WordPress login', 'ventraconnect-social-login' ),
            'available' => true,
            'enabled'   => ! empty( $settings['wp_login_enabled'] ),
        );

        // WooCommerce login.
        $woo_available = class_exists( 'WooCommerce' );
        $woo_enabled   = false;

        if ( $woo_available ) {
            // Ensure WooCommerce helpers are loaded so we can read sanitized settings.
            if ( ! function_exists( '\VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings' ) ) {
                if ( defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ) {
                    $helpers_path = VENTRACONNECT_SL_PLUGIN_DIR . 'includes/modules/woocommerce/helpers.php';
                    if ( file_exists( $helpers_path ) ) {
                        require_once $helpers_path;
                    }
                }
            }

            if ( function_exists( '\VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings' ) ) {
                $woo_settings = (array) \VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings();
                $placements   = isset( $woo_settings['placements'] ) && is_array( $woo_settings['placements'] )
                    ? $woo_settings['placements']
                    : array();

                $woo_enabled = ! empty( $woo_settings['enabled'] )
                    && isset( $placements['login_form'] )
                    && 'none' !== $placements['login_form'];
            }
        }

        $statuses['woo_login'] = array(
            'label'     => esc_html__( 'WooCommerce login', 'ventraconnect-social-login' ),
            'available' => $woo_available,
            'enabled'   => $woo_enabled,
        );

        // Pro integrations option (ventraconnect_sl_integrations).
        $integrations = array();
        if ( class_exists( '\VentraConnect\SocialLogin\Pro\Options\Integrations' ) ) {
            $integrations = (array) \VentraConnect\SocialLogin\Pro\Options\Integrations::get();
        }

        // Helper to fetch a single integration config.
        $get_integration = static function ( array $all, string $slug ): array {
            $cfg = isset( $all[ $slug ] ) && is_array( $all[ $slug ] ) ? $all[ $slug ] : array();
            return $cfg;
        };

        // Tutor LMS.
        $tutor_available = defined( 'TUTOR_VERSION' );
        $tutor_cfg       = $get_integration( $integrations, 'tutor_lms' );
        $tutor_enabled   = ! empty( $tutor_cfg['enabled'] )
            && ! empty( $tutor_cfg['places'] )
            && ! empty( $tutor_cfg['places']['login'] );

        $statuses['tutor_lms_login'] = array(
            'label'     => esc_html__( 'Tutor LMS login', 'ventraconnect-social-login' ),
            'available' => $tutor_available,
            'enabled'   => $tutor_enabled,
        );

        // LearnPress.
        $lp_available = defined( 'LEARNPRESS_VERSION' ) || class_exists( 'LearnPress', false );
        $lp_cfg       = $get_integration( $integrations, 'learnpress' );
        $lp_enabled   = ! empty( $lp_cfg['enabled'] )
            && ! empty( $lp_cfg['places'] )
            && ! empty( $lp_cfg['places']['login'] );

        $statuses['learnpress_login'] = array(
            'label'     => esc_html__( 'LearnPress login', 'ventraconnect-social-login' ),
            'available' => $lp_available,
            'enabled'   => $lp_enabled,
        );

        // LearnDash.
        $ld_available = defined( 'LEARNDASH_VERSION' ) || class_exists( 'SFWD_LMS' );
        $ld_cfg       = $get_integration( $integrations, 'learndash' );
        $ld_enabled   = ! empty( $ld_cfg['enabled'] )
            && ! empty( $ld_cfg['places'] )
            && ! empty( $ld_cfg['places']['login'] );

        $statuses['learndash_login'] = array(
            'label'     => esc_html__( 'LearnDash login', 'ventraconnect-social-login' ),
            'available' => $ld_available,
            'enabled'   => $ld_enabled,
        );

        // LifterLMS.
        $lifter_available = defined( 'LLMS_PLUGIN_FILE' ) || class_exists( 'LifterLMS' );
        $lifter_cfg       = $get_integration( $integrations, 'lifterlms' );
        $lifter_enabled   = ! empty( $lifter_cfg['enabled'] )
            && ! empty( $lifter_cfg['places'] )
            && ! empty( $lifter_cfg['places']['login'] );

        $statuses['lifterlms_login'] = array(
            'label'     => esc_html__( 'LifterLMS login', 'ventraconnect-social-login' ),
            'available' => $lifter_available,
            'enabled'   => $lifter_enabled,
        );

        // Ultimate Member.
        $um_available = defined( 'um_path' ) || class_exists( 'UM' );
        $um_cfg       = $get_integration( $integrations, 'ultimate_member' );
        $um_enabled   = ! empty( $um_cfg['enabled'] )
            && ! empty( $um_cfg['places'] )
            && ! empty( $um_cfg['places']['login'] );

        $statuses['ultimate_member_login'] = array(
            'label'     => esc_html__( 'Ultimate Member login', 'ventraconnect-social-login' ),
            'available' => $um_available,
            'enabled'   => $um_enabled,
        );

        // MemberPress.
        $mp_available = class_exists( 'MeprApp', false )
            || class_exists( 'MeprHooks', false )
            || defined( 'MEPR_VERSION' );
        $mp_cfg       = $get_integration( $integrations, 'memberpress' );
        $mp_enabled   = ! empty( $mp_cfg['enabled'] )
            && ! empty( $mp_cfg['places'] )
            && ! empty( $mp_cfg['places']['login'] );

        $statuses['memberpress_login'] = array(
            'label'     => esc_html__( 'MemberPress login', 'ventraconnect-social-login' ),
            'available' => $mp_available,
            'enabled'   => $mp_enabled,
        );

        // Paid Memberships Pro.
        $pmpro_available = defined( 'PMPRO_VERSION' );
        $pmpro_cfg       = $get_integration( $integrations, 'pmpro' );
        $pmpro_enabled   = ! empty( $pmpro_cfg['enabled'] )
            && ! empty( $pmpro_cfg['places'] )
            && ! empty( $pmpro_cfg['places']['login'] );

        $statuses['pmpro_login'] = array(
            'label'     => esc_html__( 'Paid Memberships Pro login', 'ventraconnect-social-login' ),
            'available' => $pmpro_available,
            'enabled'   => $pmpro_enabled,
        );

        // Paid Membership Subscriptions.
        $pms_available = defined( 'PMS_VERSION' );
        $pms_cfg       = $get_integration( $integrations, 'pms' );
        $pms_enabled   = ! empty( $pms_cfg['enabled'] )
            && ! empty( $pms_cfg['places'] )
            && ! empty( $pms_cfg['places']['login'] );

        $statuses['pms_login'] = array(
            'label'     => esc_html__( 'Paid Membership Subscriptions login', 'ventraconnect-social-login' ),
            'available' => $pms_available,
            'enabled'   => $pms_enabled,
        );

        // BuddyPress.
        $bp_available = function_exists( 'buddypress' )
            || defined( 'BP_PLUGIN_DIR' )
            || class_exists( '\BuddyBossPlatform\BuddyBoss' );
        $bp_cfg       = $get_integration( $integrations, 'buddypress' );
        $bp_enabled   = ! empty( $bp_cfg['enabled'] )
            && ! empty( $bp_cfg['places'] )
            && ! empty( $bp_cfg['places']['login'] );

        $statuses['buddypress_login'] = array(
            'label'     => esc_html__( 'BuddyPress login', 'ventraconnect-social-login' ),
            'available' => $bp_available,
            'enabled'   => $bp_enabled,
        );

        return $statuses;
    }

    /**
     * Check if any login context is both available and enabled.
     */
    public static function has_any_active_login_context(): bool {
        $statuses = self::get_login_context_status();

        foreach ( $statuses as $status ) {
            if ( ! empty( $status['available'] ) && ! empty( $status['enabled'] ) ) {
                return true;
            }
        }

        return false;
    }

    public static function allow_password_for_user( ?\WP_User $user ): bool {
        // If a password bypass is active, never block password logins.
        if ( self::is_password_bypass_active() ) {
            return true;
        }

        // When passwordless mode is off, do not restrict anything.
        if ( ! self::is_enabled() ) {
            return true;
        }

        // If no explicit user was passed, fall back to the current user.
        if ( null === $user ) {
            $user = wp_get_current_user();
        }

        // Capability-based bypass: site admins can always use passwords.
        if ( $user instanceof \WP_User ) {
            $user_id = (int) $user->ID;

            if ( $user_id > 0 ) {
                if ( user_can( $user, 'manage_options' ) || is_super_admin( $user_id ) ) {
                    return true;
                }
            }
        }

        if ( ! $user instanceof \WP_User ) {
            return false;
        }

        // Soft / recommended mode: only admins (manage_options) are allowed to use passwords.
        if ( self::is_soft() ) {
            return user_can( $user, 'manage_options' );
        }

        // Strict mode: everything is blocked unless break-glass flag is set (handled above).
        if ( self::is_strict() ) {
            return false;
        }

        // Fallback: allow by default.
        return true;
    }

    /**
     * Authenticate filter handler enforcing passwordless mode.
     *
     * @param null|\WP_User|\WP_Error $user     Current user or error from previous filters.
     * @param string                  $username Submitted username or email.
     * @param string                  $password Submitted password.
     * @return null|\WP_User|\WP_Error
     */
    public static function filter_authenticate( $user, $username, $password ) {
        // 1) Early bail: mode off or no password provided.
        if ( ! self::is_enabled() ) {
            return $user;
        }

        if ( '' === $password || null === $password ) {
            return $user;
        }

        // 2) If user already resolved and allowed, return as-is.
        if ( $user instanceof \WP_User ) {
            if ( self::allow_password_for_user( $user ) ) {
                return $user;
            }
            // If not allowed, fall through to hard block below.
        }

        // 3) Resolve candidate user by username or email.
        $resolved = null;

        if ( $user instanceof \WP_User ) {
            $resolved = $user;
        } elseif ( '' !== $username ) {
            $resolved = get_user_by( 'login', $username );

            if ( ! $resolved && is_email( $username ) ) {
                $resolved = get_user_by( 'email', $username );
            }
        }

        // 4) If passwordless is enabled and this user is allowed (admin in soft mode or break-glass), allow.
        if ( $resolved instanceof \WP_User && self::allow_password_for_user( $resolved ) ) {
            return $user;
        }

        // 5) Otherwise, block password login with a clear error.
        $methods = self::build_login_methods_phrase();

        $message = __( 'Password login is disabled on this site.', 'ventraconnect-social-login' );
        if ( '' !== $methods ) {
            $message .= ' ' . sprintf(
                /* translators: 1: Login methods phrase (e.g. "Social Login or an email Magic Link"). */
                __( 'Please use passwordless login using %1$s.', 'ventraconnect-social-login' ),
                $methods
            );
        }

        return new \WP_Error(
            'ventraconnect_sl_passwordless_blocked',
            $message
        );
    }

    /**
     * Filter whether a user is allowed to reset their password.
     *
     * @param bool         $allow Current allow flag from core / other plugins.
     * @param int|\WP_User $user  User ID or WP_User.
     * @return bool
     */
    public static function filter_allow_password_reset( $allow, $user ) {
        // If passwordless mode is off, do not change default behaviour.
        if ( ! self::is_enabled() ) {
            return $allow;
        }

        // Normalise to a WP_User instance if possible.
        if ( $user instanceof \WP_User ) {
            $u = $user;
        } elseif ( is_numeric( $user ) ) {
            $u = get_user_by( 'id', (int) $user );
        } else {
            $u = null;
        }

        if ( ! $u instanceof \WP_User ) {
            // If we cannot resolve a concrete user, fall back to the original flag.
            return $allow;
        }

        // If this user is allowed to use passwords (admin in soft mode or break-glass),
        // keep the original behaviour.
        if ( self::allow_password_for_user( $u ) ) {
            return $allow;
        }

        // Otherwise, block password reset for this user.
        return false;
    }

    /**
     * Customize lost password error messages when passwordless mode is active.
     *
     * @param \WP_Error $errors      Error object from core.
     * @param string    $redirect_to Redirect URL (unused).
     * @return \WP_Error
     */
    public static function filter_lostpassword_errors( $errors, $redirect_to ) {
        if ( ! self::is_enabled() ) {
            return $errors;
        }

        if ( ! $errors instanceof \WP_Error ) {
            return $errors;
        }

        // Only rewrite our own "no_password_reset" case.
        if ( $errors->get_error_message( 'no_password_reset' ) ) {
            $errors->remove( 'no_password_reset' );

            $methods = self::build_login_methods_phrase();

            $message = __( 'Password reset is disabled because this site uses passwordless login.', 'ventraconnect-social-login' );
            if ( '' !== $methods ) {
                $message .= ' ' . sprintf(
                    /* translators: 1: Login methods phrase (e.g. "Social Login or an email Magic Link"). */
                    __( 'Please sign in using %1$s instead.', 'ventraconnect-social-login' ),
                    $methods
                );
            }

            $errors->add( 'no_password_reset', $message );
        }

        return $errors;
    }

    /**
     * Hide core profile password fields for users who cannot use passwords.
     *
     * @param bool    $show        Whether to show password fields.
     * @param \WP_User $profileuser Profile user object.
     * @return bool
     */
    public static function filter_show_password_fields( $show, $profileuser ) {
        if ( ! self::is_enabled() ) {
            return $show;
        }

        if ( ! $profileuser instanceof \WP_User ) {
            return $show;
        }

        // Only show password fields for users that are allowed to use passwords.
        return self::allow_password_for_user( $profileuser );
    }

    /**
     * Show a small note on the profile screen for passwordless users.
     *
     * @param \WP_User $profileuser Profile user object.
     */
    public static function maybe_profile_passwordless_note( $profileuser ): void {
        if ( ! self::is_enabled() ) {
            return;
        }

        if ( ! $profileuser instanceof \WP_User ) {
            return;
        }

        // If this user is allowed to use passwords, no note is needed.
        if ( self::allow_password_for_user( $profileuser ) ) {
            return;
        }

        echo '<tr class="vcs-passwordless-profile-note">';
        echo '<th scope="row"></th>';
        echo '<td><p class="description">';
        echo esc_html__( 'This account uses passwordless login. You don\'t need a password to sign in.', 'ventraconnect-social-login' );
        echo '</p></td>';
        echo '</tr>';
    }

    /**
     * Output CSS tweaks on wp-login.php when strict passwordless mode is active.
     *
     * Hides password + remember-me + lost password in strict mode.
     *
     * Runs only on the core login screen.
     *
     * @return void
     */
    public static function on_login_head(): void {
        if ( ! self::is_strict() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="ventraconnect-passwordless-login">
        /* Strict mode: hide all core password UI on wp-login.php */

        /* Login form: hide username/email, password, remember-me, and default submit */
        #loginform label[for="user_login"],
        #loginform #user_login,
        #loginform .user-pass-wrap,
        #loginform .user-pass-wrap label,
        #loginform .user-pass-wrap input,
        #loginform .forgetmenot,
        #loginform .wp-pwd,
        #loginform .wp-pwd .button.wp-hide-pw,
        #loginform p.submit {
            display: none !important;
        }

        /* Lost password form: hide the entire form (we show our own message instead) */
        #lostpasswordform {
            display: none !important;
        }

         /* Hide the "Lost your password?" link in the footer nav in strict mode */
        #login #nav a[href*="action=lostpassword"],
        #login #nav a:last-child {
            display: none !important;
        }
            
        /* Hide any OR separator we render inside the login form when in strict mode */
        #loginform .vcs-login-or-divider,
        #loginform .vcs-login-or,
        #loginform .vcs-or-separator {
            display: none !important;
        }

        /* Registration form: hide any password fields that might be added by core or other plugins in strict mode */
        #registerform input[type="password"],
        #registerform .user-pass1-wrap,
        #registerform .user-pass2-wrap,
        #registerform .pw-weak,
        #registerform .pw-checkbox {
            display: none !important;
        }

        /* Make the passwordless notice visually prominent */
        #login .vcs-passwordless-message {
            background: #eff6ff; /* light blue */
            border-left: 4px solid #2563eb; /* stronger accent */
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 14px;
            line-height: 1.5;
            font-weight: 500;
            margin-bottom: 18px;
        }

        #login .vcs-passwordless-message strong {
            font-weight: 700;
            display: block;
            margin-bottom: 4px;
        }
    </style>';
    }

    /**
     * Output frontend CSS for WooCommerce login forms in strict passwordless mode.
     */
    public static function on_frontend_head(): void {
        if ( ! self::is_strict() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="ventraconnect-passwordless-woocommerce">
            /* WooCommerce login form adjustments for strict passwordless mode */

            /* Hide Woo username/password login UI on My Account and Checkout login forms */
            .woocommerce .woocommerce-form-login label[for="username"],
            .woocommerce .woocommerce-form-login label[for="password"],
            .woocommerce .woocommerce-form-login #username,
            .woocommerce .woocommerce-form-login #password,
            .woocommerce .woocommerce-form-login .password-input,
            .woocommerce .woocommerce-form-login .woocommerce-form-login__rememberme,
            .woocommerce .woocommerce-form-login .lost_password,
            .woocommerce .woocommerce-form-login button[type="submit"] {
                display: none !important;
            }

            /* Woo Register: in strict mode, hide Woo\'s own email field and register button (helper text handled via gettext) */
            .woocommerce form.woocommerce-form-register .woocommerce-form-row--wide,
            .woocommerce form.woocommerce-form-register .woocommerce-form-row.form-row {
                display: none !important;
            }
        </style>';
    }

    /**
     * Output CSS to hide LearnDash password fields when strict passwordless mode is active.
     */
    public static function on_learndash_head(): void {
        // Only in strict mode.
        if ( ! self::is_strict() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="ventraconnect-passwordless-learndash">
        /* LearnDash modal login (strict passwordless): hide classic username/password login block */
        .ld-login-modal-form p.login-username,
        .ld-login-modal-form p.login-password,
        .ld-login-modal-form p.login-remember,
        .ld-login-modal-form p.login-submit,
        .ld-login-modal-form a.ld-forgot-password-link {
            display: none !important;
        }

        /* No point showing our "or" separator when there is no form under it */
            .ld-login-modal-form .vcs-lifterlms-separator {
                display: none !important;
            }

        /* LearnDash inline login (registration screen) */
        .ld-registration__login-form p.login-username,
        .ld-registration__login-form p.login-password,
        .ld-registration__login-form p.login-remember,
        .ld-registration__login-form p.login-submit {
            display: none !important;
        }

        .ld-registration__login-form a.ld-forgot-password-link,
        .ld-registration__login-form .vcs-lifterlms-separator {
            display: none !important;
        }
    </style>';
    }

    /**
     * Output CSS to adjust MemberPress login forms when strict passwordless mode is active.
     */
    public static function on_memberpress_head(): void {
        // Only in strict mode.
        if ( ! self::is_strict() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="ventraconnect-passwordless-memberpress">
    /* MemberPress login (strict passwordless): hide the classic username/password form
       that comes immediately after our login container. */

    .vcs-sl-mp-login-container + form,
    .vcs-sl-mp-login-container + form.mepr-form,
    .vcs-sl-mp-login-container + form.mepr-login-form {
        display: none !important;
    }

    /* Hide MemberPress "Forgot Password" actions that follow our container. */
    .vcs-sl-mp-login-container + form + .mepr-login-actions,
    .vcs-sl-mp-login-container ~ .mepr-login-actions {
        display: none !important;
    }

    /* If we render an "or" separator under our buttons for MemberPress, hide it too. */
    .vcs-sl-mp-login-container .vcs-lifterlms-separator,
    .vcs-sl-mp-login-container .vcs-separator {
        display: none !important;
    }
    </style>';
    }

    /**
     * Output CSS to adjust LifterLMS login forms when strict passwordless mode is active.
     *
     * We target the core LifterLMS login form markup directly so this works
     * whether our buttons are rendered above or below the form fields.
     */
    public static function on_lifterlms_head(): void {
        // Only strict mode.
        if ( ! self::is_strict() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="ventraconnect-passwordless-lifterlms">
    /* LifterLMS login (strict passwordless): hide username/password UI regardless of button placement. */

    /* Hide username/email, password, remember-me, submit button and "lost password" block
       inside LifterLMS login forms. Our passwordless banner + buttons are not using llms-form-field. */
    .llms-login .llms-form-field.type-email,
    .llms-login .llms-form-field.type-password,
    .llms-login .llms-form-field.type-submit,
    .llms-login .llms-form-field.type-checkbox,
    .llms-login .llms-form-field.type-html,
    .llms-login .llms-form-field.llms-lost-password,
    .llms-login .llms-lost-password,
    .llms-login a[href*="lost-password"] {
        display: none !important;
    }

    /* No "or" separator when the form is hidden. */
    .llms-login .vcs-lifterlms-separator,
    .llms-login .vcs-separator {
        display: none !important;
    }
    </style>';
    }

/**
 * Output CSS to adjust LearnPress login forms when strict passwordless mode is active.
 */
public static function on_learnpress_head(): void {
    // Only strict mode.
    if ( ! self::is_strict() ) {
        return;
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '<style id="ventraconnect-passwordless-learnpress">
    /* === Standalone LearnPress login form ============================== */

    /* Hide the separator + native login form under our buttons. */
    .learn-press-form-login.learn-press-form .vcs-lifterlms-separator,
    .learn-press-form-login.learn-press-form .vcs-learnpress-separator {
        display: none !important;
    }

    .learn-press-form-login.learn-press-form form[name="learn-press-login"] {
        display: none !important;
    }

    /* === LearnPress checkout account section (strict passwordless) ===== */

    /* Remove the separator under our buttons at checkout (we don\'t need "or"). */
    .lp-checkout-form__after .vcs-lifterlms-separator,
    .lp-checkout-form__after .vcs-learnpress-separator {
        display: none !important;
    }

    /* Keep both account blocks; just hide the classic login credentials. */
    .lp-checkout-form__after #checkout-account-login .lp-form-fields,
    .lp-checkout-form__after #checkout-account-login .lp-checkout-remember {
        display: none !important;
    }

    /* DO NOT touch #checkout-account-register or the Sign up link – we want
       the registration form and the "Don\'t have an account? Sign up" toggle
       to keep working normally. */
    </style>';
}

    /**
     * Output CSS to adjust Tutor LMS login forms when strict passwordless mode is active.
     *
     * Only the Tutor login form is affected. Tutor registration and profile screens remain untouched.
     */
    public static function on_tutor_head(): void {
        if ( ! self::is_strict() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="ventraconnect-passwordless-tutor">
    /* Tutor LMS login (strict passwordless): hide classic username/password UI only on the login form. */

    [data-vcs-tutor-context="tutor_login"] .vcs-lifterlms-separator {
        display: none !important;
    }

    #tutor-login-form input[type="text"],
    #tutor-login-form input[type="email"],
    #tutor-login-form input[type="password"],
    #tutor-login-form input[name="log"],
    #tutor-login-form input[name="pwd"],
    #tutor-login-form input[name="rememberme"],
    #tutor-login-form button[type="submit"],
    #tutor-login-form input[type="submit"],
    #tutor-login-form .tutor-btn[type="submit"],
    #tutor-login-form .login-username,
    #tutor-login-form .login-password,
    #tutor-login-form .login-remember,
    #tutor-login-form .login-submit,
    #tutor-login-form label[for*="remember"],
    #tutor-login-form .tutor-form-check,
    .tutor-login-form-wrapper form input[type="text"],
    .tutor-login-form-wrapper form input[type="email"],
    .tutor-login-form-wrapper form input[type="password"],
    .tutor-login-form-wrapper form input[name="log"],
    .tutor-login-form-wrapper form input[name="pwd"],
    .tutor-login-form-wrapper form input[name="rememberme"],
    .tutor-login-form-wrapper form button[type="submit"],
    .tutor-login-form-wrapper form input[type="submit"],
    .tutor-login-form-wrapper form .tutor-btn[type="submit"],
    .tutor-login-form-wrapper form .login-username,
    .tutor-login-form-wrapper form .login-password,
    .tutor-login-form-wrapper form .login-remember,
    .tutor-login-form-wrapper form .login-submit,
    .tutor-login-form-wrapper form label[for*="remember"],
    .tutor-login-form-wrapper form .tutor-form-check {
        display: none !important;
    }

    #tutor-login-form a[href*="lost-password"],
    #tutor-login-form a[href*="lostpassword"],
    #tutor-login-form a[href*="forgot"],
    .tutor-login-form-wrapper a[href*="lost-password"],
    .tutor-login-form-wrapper a[href*="lostpassword"],
    .tutor-login-form-wrapper a[href*="forgot"],
    #tutor-login-form .tutor-lost-password,
    .tutor-login-form-wrapper .tutor-lost-password {
        display: none !important;
    }

    #tutor-login-form .tutor-form-group:has(input[type="text"]),
    #tutor-login-form .tutor-form-group:has(input[type="email"]),
    #tutor-login-form .tutor-form-group:has(input[type="password"]),
    #tutor-login-form .tutor-form-group:has(input[name="log"]),
    #tutor-login-form .tutor-form-group:has(input[name="pwd"]),
    #tutor-login-form .tutor-form-group:has(input[name="rememberme"]),
    #tutor-login-form .tutor-form-group:has(button[type="submit"]),
    #tutor-login-form .tutor-form-group:has(input[type="submit"]),
    #tutor-login-form .tutor-form-row:has(input[type="text"]),
    #tutor-login-form .tutor-form-row:has(input[type="email"]),
    #tutor-login-form .tutor-form-row:has(input[type="password"]),
    #tutor-login-form .tutor-form-row:has(input[name="log"]),
    #tutor-login-form .tutor-form-row:has(input[name="pwd"]),
    #tutor-login-form .tutor-form-row:has(input[name="rememberme"]),
    #tutor-login-form .tutor-form-row:has(button[type="submit"]),
    #tutor-login-form .tutor-form-row:has(input[type="submit"]),
    .tutor-login-form-wrapper form .tutor-form-group:has(input[type="text"]),
    .tutor-login-form-wrapper form .tutor-form-group:has(input[type="email"]),
    .tutor-login-form-wrapper form .tutor-form-group:has(input[type="password"]),
    .tutor-login-form-wrapper form .tutor-form-group:has(input[name="log"]),
    .tutor-login-form-wrapper form .tutor-form-group:has(input[name="pwd"]),
    .tutor-login-form-wrapper form .tutor-form-group:has(input[name="rememberme"]),
    .tutor-login-form-wrapper form .tutor-form-group:has(button[type="submit"]),
    .tutor-login-form-wrapper form .tutor-form-group:has(input[type="submit"]),
    .tutor-login-form-wrapper form .tutor-form-row:has(input[type="text"]),
    .tutor-login-form-wrapper form .tutor-form-row:has(input[type="email"]),
    .tutor-login-form-wrapper form .tutor-form-row:has(input[type="password"]),
    .tutor-login-form-wrapper form .tutor-form-row:has(input[name="log"]),
    .tutor-login-form-wrapper form .tutor-form-row:has(input[name="pwd"]),
    .tutor-login-form-wrapper form .tutor-form-row:has(input[name="rememberme"]),
    .tutor-login-form-wrapper form .tutor-form-row:has(button[type="submit"]),
    .tutor-login-form-wrapper form .tutor-form-row:has(input[type="submit"]) {
        display: none !important;
    }
    </style>';
    }

    /**
     * Adjust WooCommerce "set a new password" helper text for passwordless mode.
     *
     * @param string $translated Translated text.
     * @param string $text       Original text.
     * @param string $domain     Text domain.
     * @return string
     */
    public static function filter_woo_register_password_message( $translated, $text, $domain ) {
        // Only do anything when passwordless mode is actually enabled.
        if ( ! self::is_enabled() ) {
            return $translated;
        }

        // Only touch WooCommerce strings.
        if ( 'woocommerce' !== $domain ) {
            return $translated;
        }

        // Replace Woo's default helper line on the register form.
        if ( 'A link to set a new password will be sent to your email address.' === $text ) {
            return __( 'We\'ll email you a confirmation link. To access your account later, use Social Login, an email magic link, or a one-time code on the login page instead of a password.', 'ventraconnect-social-login' );
        }

        return $translated;
    }

    /**
     * Inject messages on wp-login.php when passwordless mode is active.
     *
     * @param string $message Existing login message HTML.
     * @return string
     */
    public static function filter_login_message( $message ) {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    $action     = 'login';
    $action_raw = '';

    // phpcs:disable WordPress.Security.NonceVerification.Missing -- Login screen action context; not an authenticated action. Sanitized below.
    if ( isset( $_GET['action'] ) ) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $action_raw = wp_unslash( (string) $_GET['action'] );
    } elseif ( isset( $_POST['action'] ) ) {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $action_raw = wp_unslash( (string) $_POST['action'] );
    }
    // phpcs:enable WordPress.Security.NonceVerification.Missing

    if ( '' !== $action_raw ) {
        $action = sanitize_key( $action_raw );
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    // Strict mode: keep existing behaviour (login / lostpassword / register).
    if ( self::is_strict() ) {
            $msg = '';

            if ( '' === $action || 'login' === $action ) {
                $msg = '<p class="message vcs-passwordless-message">'
                    . '<strong>' . esc_html__( 'This site uses passwordless login.', 'ventraconnect-social-login' ) . '</strong> '
                    . esc_html__( 'To sign in, use one of the options below: a social account, an email magic link, or a one-time code.', 'ventraconnect-social-login' )
                    . '</p>';
            } elseif ( 'lostpassword' === $action ) {
                $msg = '<p class="message vcs-passwordless-message">'
                    . '<strong>' . esc_html__( 'Password reset is not available on this site.', 'ventraconnect-social-login' ) . '</strong> '
                    . esc_html__( 'Because we use passwordless login, please access your account using a social account, an email magic link, or a one-time code instead.', 'ventraconnect-social-login' )
                    . '</p>';
            } elseif ( 'register' === $action ) {
                $msg = '<p class="message vcs-passwordless-message">'
                    . '<strong>' . esc_html__( 'This site uses passwordless login.', 'ventraconnect-social-login' ) . '</strong> '
                    . esc_html__( 'After you register, you will sign in using one of the options below: a social account, an email magic link, or a one-time code, instead of a password.', 'ventraconnect-social-login' )
                    . '</p>';
            }

            if ( '' === $msg ) {
                return $message;
            }

            // Prepend our message to whatever WordPress already outputs.
            return $msg . (string) $message;
        }

        // Recommended (soft) mode: add a gentle hint on the login screen only.
        if ( self::is_soft() && 'login' === $action ) {
            $methods = self::build_login_methods_phrase();

            $hint  = '<p class="message vcs-passwordless-message">';
            $hint .= esc_html__( 'Sign in without a password using one of the options below.', 'ventraconnect-social-login' );
            if ( '' !== $methods ) {
                $hint .= ' ' . sprintf(
                    /* translators: 1: Login methods phrase (e.g. "Social Login or an email Magic Link"). */
                    esc_html__( 'You can sign in using %1$s.', 'ventraconnect-social-login' ),
                    esc_html( $methods )
                );
            }
            $hint .= '</p>';

            return $hint . (string) $message;
        }

        // All other cases: leave the login message untouched.
        return $message;
    }

    /**
     * Front-end CSS for Woo "Edit account" page for passwordless users.
     * Hides password change fields when the current user cannot use passwords.
     */
    public static function on_account_head(): void {
        if ( ! self::is_enabled() ) {
            return;
        }

        if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
            return;
        }

        $user = wp_get_current_user();
        if ( ! $user instanceof \WP_User ) {
            return;
        }

        // If this user is allowed to use passwords, leave Woo account form alone.
        if ( self::allow_password_for_user( $user ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="ventraconnect-passwordless-woocommerce-account">
        /* Woo My Account → Account details: hide password fields for passwordless users */
        .woocommerce-EditAccountForm label[for="password_current"],
        .woocommerce-EditAccountForm #password_current,
        .woocommerce-EditAccountForm label[for="password_1"],
        .woocommerce-EditAccountForm #password_1,
        .woocommerce-EditAccountForm label[for="password_2"],
        .woocommerce-EditAccountForm #password_2 {
            display: none !important;
        }
    </style>';
    }

    /**
     * Show a note on Woo "Edit account" form for passwordless users.
     */
    public static function maybe_account_passwordless_note(): void {
        if ( ! self::is_enabled() ) {
            return;
        }

        $user = wp_get_current_user();
        if ( ! $user instanceof \WP_User ) {
            return;
        }

        if ( self::allow_password_for_user( $user ) ) {
            return;
        }

        echo '<p class="vcs-passwordless-account-note">';
        echo esc_html__( 'This account uses passwordless login. You don\'t need a password to sign in. Use Social Login, a magic link, or a one-time code on the login page.', 'ventraconnect-social-login' );
        echo '</p>';
    }

    /**
     * Output CSS to adjust PMPro login forms when strict passwordless mode is active.
     *
     * We only hide the classic username/password login UI. Registration and checkout
     * flows are handled by PMPro itself.
     */
    public static function on_pmpro_head(): void {
        // Only in strict mode.
        if ( ! self::is_strict() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="ventraconnect-passwordless-pmpro-login">
    /* PMPro login (strict passwordless): hide classic username/password UI on the pmpro_login section. */

    #pmpro_login form .login-username,
    #pmpro_login form .login-password,
    #pmpro_login form .login-remember,
    #pmpro_login form .login-submit {
        display: none !important;
    }

    /* Optional: hide the default "Register" / "Lost Password?" nav links under the card. */
    #pmpro_login .pmpro_actions_nav {
        display: none !important;
    }
    </style>';
    }

    /**
     * Output CSS to adjust Paid Membership Subscriptions login forms when strict passwordless mode is active.
     *
     * This targets only the PMS login form instance rendered via wp_login_form() with form_id="pms_login".
     * PMS registration, plan selection, subscription, and payment forms are intentionally untouched.
     */
    public static function on_pms_head(): void {
        if ( ! self::is_strict() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="ventraconnect-passwordless-pms-login">
    /* PMS login (strict passwordless): hide classic username/password UI only inside form#pms_login. */

    form#pms_login .login-username,
    form#pms_login .login-password,
    form#pms_login .login-remember,
    form#pms_login .login-submit,
    form#pms_login p.login-username,
    form#pms_login p.login-password,
    form#pms_login p.login-remember,
    form#pms_login p.login-submit,
    form#pms_login label[for="user_login"],
    form#pms_login label[for="user_pass"],
    form#pms_login input[name="log"],
    form#pms_login input[name="pwd"],
    form#pms_login input[name="rememberme"],
    form#pms_login input[type="password"],
    form#pms_login input[type="submit"],
    form#pms_login button[type="submit"] {
        display: none !important;
    }

    form#pms_login a[href*="lostpassword"],
    form#pms_login a[href*="lost-password"],
    form#pms_login a[href*="forgot"] {
        display: none !important;
    }
    </style>';
    }

    /**
 * Output CSS to adjust BuddyPress login widget/forms when strict passwordless mode is active.
 *
 * We only hide the classic username/password login UI. Registration remains visible.
 */
public static function on_buddypress_head(): void {
    // Only strict mode.
    if ( ! self::is_strict() ) {
        return;
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '<style id="ventraconnect-passwordless-buddypress">
    /* BuddyPress login widget (strict passwordless): hide classic username/password UI. */

    /* Target the core BP login widget container + form */
    section.widget_bp_core_login_widget.buddypress form#bp-login-widget-form .login-username,
    section.widget_bp_core_login_widget.buddypress form#bp-login-widget-form .login-password,
    section.widget_bp_core_login_widget.buddypress form#bp-login-widget-form .login-remember,
    section.widget_bp_core_login_widget.buddypress form#bp-login-widget-form .login-submit,
    section.widget_bp_core_login_widget.buddypress form#bp-login-widget-form .bp-login-widget-register-link,
    section.widget_bp_core_login_widget.buddypress form#bp-login-widget-form p.bp-login-widget-register-link {
        display: none !important;
    }

    /* Hide the "or" separator inside the widget when the form is hidden */
    section.widget_bp_core_login_widget.buddypress .vcs-lifterlms-separator,
    section.widget_bp_core_login_widget.buddypress .vcs-separator {
        display: none !important;
    }
    </style>';
}

    /**
     * Output CSS to adjust Ultimate Member login forms when strict passwordless mode is active.
     *
     * We hide the password-based login UI but keep the Register button on the login form.
     */
    public static function on_ultimate_member_head(): void {
        // Only in strict mode.
        if ( ! self::is_strict() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '<style id="ventraconnect-passwordless-ultimate-member">
    /* Ultimate Member login (strict passwordless):
       - Keep our banner + social / Magic Link / OTP.
       - Hide username + password + remember me + login submit + forgot password.
       - Keep the "Register" button in the login form visible. */

    /* Hide our "or" separator under the buttons on UM login. */
    .um.um-login .um-form .vcsl-um-separator,
    .um.um-login .um-form .vcs-lifterlms-separator {
        display: none !important;
    }

    /* Hide the main login row that contains username + password fields. */
    .um.um-login .um-form .um-row,
    .um.um-login .um-form .um-field-username,
    .um.um-login .um-form .um-field-user_password {
        display: none !important;
    }

    /* Inside the lower action bar (.um-col-alt):
       - Hide "Keep me signed in" checkbox block.
       - Hide the left column with the Login submit button.
       - Keep the right column (Register button) visible. */
    .um.um-login .um-form .um-col-alt .um-field.um-field-c,
    .um.um-login .um-form .um-col-alt .um-left {
        display: none !important;
    }

    /* Hide the forgot-password link block under the actions. */
    .um.um-login .um-form .um-col-alt-b,
    .um.um-login .um-form .um-col-alt-b a.um-link-alt {
        display: none !important;
    }

    /* For any Ultimate Member "account login" areas (if used):
       fully hide password-based login UI in strict mode. */
    .um-account-login .um-form .um-row-user_login,
    .um-account-login .um-form .um-row-username,
    .um-account-login .um-form .um-row-user_email,
    .um-account-login .um-form .um-row-user_password,
    .um-account-login .um-form .um-row-user_rememberme,
    .um-account-login .um-form .um-field-user_login,
    .um-account-login .um-form .um-field-username,
    .um-account-login .um-form .um-field-user_email,
    .um-account-login .um-form .um-field-user_password,
    .um-account-login .um-form .um-field-user_rememberme,
    .um-account-login .um-form .um-col-alt,
    .um-account-login .um-form .um-col-alt-b {
        display: none !important;
    }

    .um-account-login .um-form a.um-forgot-password,
    .um-account-login .um-form a.um-forgotpw {
        display: none !important;
    }
    </style>';
    }
}
