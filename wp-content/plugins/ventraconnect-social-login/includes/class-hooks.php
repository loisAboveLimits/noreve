<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register WP & Woo hooks for button placements.
 */
class Hooks {
    /** @var \WSC\Buttons */
    private $buttons;
    /** @var \WSC\User_Links */
    private $links;

    public function __construct( $buttons, $links ) {
        $this->buttons = $buttons;
        $this->links   = $links;
    }

    /**
     * Register integration hooks.
     */
    public function register() {
        $settings = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        // Free: WP contexts only
        if ( ! empty( $settings['wp_login_enabled'] ) || ! empty( $settings['wp_register_enabled'] ) ) {
            ( new Integrations\WP_Integration( $this->buttons ) )->register();
        }
        add_filter( 'ventraconnect_sl_can_create_user', [ $this, 'maybe_block_core_login_user_creation' ], 10, 4 );
        // Pro: Woo hooks registered by Pro add-on
        // Unlink handler (optional link UI could be added by theme/account page).
        add_action( 'init', [ $this, 'maybe_handle_unlink' ] );
        add_action( 'init', [ $this, 'maybe_display_consent_notice' ] );
    }

    /**
     * Handle unlink action via nonce.
     */
    public function maybe_handle_unlink() {
        if ( ! is_user_logged_in() ) { return; }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended -- unlink request is fully sanitized and nonce-checked in User_Links::maybe_verify_unlink_from_query().
        $unlink_key = '';
        if ( isset( $_GET['ventraconnect_sl_unlink'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized into $unlink_key below and verified via nonce.
            $unlink_raw = wp_unslash( (string) $_GET['ventraconnect_sl_unlink'] );
            $unlink_key = sanitize_key( $unlink_raw );
        }
        $nonce = '';
        if ( isset( $_GET['_wpnonce'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- used only in wp_verify_nonce() below.
            $nonce_raw = wp_unslash( (string) $_GET['_wpnonce'] );
            $nonce     = sanitize_text_field( $nonce_raw );
        }
        if ( '' === $unlink_key || '' === $nonce || ! wp_verify_nonce( $nonce, 'ventraconnect_sl_unlink_' . $unlink_key ) ) {
            return;
        }
        $provider = $unlink_key;

        $user_id = get_current_user_id();

        // Compute redirect: honor explicit return, then referer, then fall back to current URL without query args.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only return URL; sanitized below.
        $return_url = '';
        if ( isset( $_GET['return'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- public param, normalized via wp_validate_redirect.
            $return_raw = wp_unslash( (string) $_GET['return'] );
            $return_url = $return_raw ? esc_url_raw( $return_raw ) : '';
        }
        $return_to = '';
        if ( $return_url ) {
            $return_to = wp_validate_redirect( $return_url, home_url( '/' ) );
        } else {
            $fallback = wp_get_referer();
            if ( ! $fallback ) {
                $fallback = remove_query_arg( [ 'ventraconnect_sl_unlink', '_wpnonce' ] );
            }
            $return_to = wp_validate_redirect( $fallback, home_url( '/' ) );
        }

        // Allow integrations (e.g. WooCommerce) to veto unlinking.
        $can_unlink = apply_filters(
            'ventraconnect_sl_can_unlink',
            true,          // Default: allow unlink.
            $provider,     // Provider slug, e.g. 'facebook'.
            (int) $user_id, // Current user ID.
            $return_to     // Redirect target URL after unlink.
        );

        if ( $can_unlink ) {
            $this->links->unlink( (int) $user_id, $provider );
        }

        wp_safe_redirect( $return_to );
        exit;
    }

    public function maybe_display_consent_notice() {
        if ( ! is_user_logged_in() ) { return; }
        $user_id = get_current_user_id();
        if ( $user_id <= 0 ) { return; }
        $key = 'ventraconnect_sl_consent_notice_' . $user_id;
        $payload = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getTransient( $key );
        if ( empty( $payload ) || empty( $payload['provider'] ) ) { return; }
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteTransient( $key );
        $provider = sanitize_text_field( $payload['provider'] );
        $label = ucwords( str_replace( [ '-', '_' ], ' ', $provider ) );
        $message = sprintf(
            /* translators: %s = provider label */
            __( "We'll use your name, email, and profile picture from %s to create/update your account. Manage this in My Account -> Social Connections.", 'ventraconnect-social-login' ),
            $label
        );
        if ( is_admin() ) {
            add_action( 'admin_notices', function() use ( $message ) {
                echo '<div class="notice notice-info"><p>' . esc_html( $message ) . '</p></div>';
            } );
        } else {
            add_action( 'wp_footer', function() use ( $message ) {
                echo '<div class="vcs-consent-notice" style="position:fixed;bottom:16px;left:16px;right:16px;max-width:420px;margin:0 auto;background:#111827;color:#f9fafb;padding:12px 16px;border-radius:6px;font-size:14px;line-height:1.5;z-index:9999;box-shadow:0 10px 30px rgba(17,24,39,0.25);">';
                echo '<strong style="display:block;font-size:14px;margin-bottom:4px;">' . esc_html__( 'Social login update', 'ventraconnect-social-login' ) . '</strong>';
                echo '<span>' . esc_html( $message ) . '</span>';
                echo '</div>';
            } );
        }
    }

    /**
     * Conditionally block new user creation from default/core login forms based on the global setting.
     *
     * @param bool   $can_create Whether user creation is currently allowed.
     * @param string $context    Login context string.
     * @param array  $profile    Social profile data.
     * @param string $provider   Provider key.
     *
     * @return bool
     */
    public function maybe_block_core_login_user_creation( $can_create, $context, $profile, $provider ) {
        // If some integration already vetoed, respect that.
        if ( ! $can_create ) {
            return false;
        }

        $settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();

        // Default to "allowed" if option is missing, to keep backward compatibility.
        if ( isset( $settings['allow_core_login_account_creation'] ) ) {
            $allow_core_login = ! empty( $settings['allow_core_login_account_creation'] );
        } else {
            $allow_core_login = true;
        }

        // Only apply to "plain login" contexts.
        $core_login_contexts = [
            'wp_login',
            'shortcode',
        ];

        if ( in_array( $context, $core_login_contexts, true ) && ! $allow_core_login ) {
            return false;
        }

        return $can_create;
    }
}


// Temporary: minimal redirect debug logger (only when WP_DEBUG is true)
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    add_action( 'ventraconnect_sl_redirect_debug', function ( $data ) {
        if ( ! is_array( $data ) ) {
            return;
        }
        $line = sprintf(
            'VCS Redirect Debug: user_id=%d provider=%s redirect=%s',
            isset( $data['user_id'] ) ? (int) $data['user_id'] : 0,
            isset( $data['provider'] ) ? (string) $data['provider'] : '',
            isset( $data['redirect'] ) ? (string) $data['redirect'] : ''
        );
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is true.
            error_log( $line );
        }
    } );
}
