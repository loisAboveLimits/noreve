<?php
namespace VentraConnect\SocialLogin\Integrations;

use VentraConnect\SocialLogin\Buttons;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WordPress login/register integration.
 */
class WP_Integration {
    private $buttons;
    public function __construct( Buttons $buttons ) { $this->buttons = $buttons; }

    public function register() {
        add_action( 'login_form', [ $this, 'login_buttons' ] );
        add_filter( 'login_message', [ $this, 'render_core_login_notices' ] );
        add_action( 'register_form', [ $this, 'register_buttons' ] );
        add_action( 'comment_form_top', [ $this, 'comment_buttons' ] );
        add_filter( 'ventraconnect_sl_oauth_state_extra', [ $this, 'augment_state' ], 10, 3 );
    }

    public function login_buttons() {
    global $pagenow;

    // Only render on the core WP login screen, not LearnPress or any other front-end forms.
    if ( 'wp-login.php' !== ( $pagenow ?? '' ) ) {
        return;
    }

    $this->buttons->render( 'wp_login' );
}

public function register_buttons() {
    global $pagenow;

    // Only render on the core WP register screen.
    if ( 'wp-login.php' !== ( $pagenow ?? '' ) ) {
        return;
    }

    $this->buttons->render( 'wp_register' );
}

    public function comment_buttons() { $this->buttons->render( 'wp_comments' ); }

    /**
     * Render core login error notices (wp-login.php) based on query args.
     *
     * @param string $message Existing login message HTML.
     * @return string
     */
    public function render_core_login_notices( $message ) {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only error code from URL for display.
        $code = isset( $_GET['ventraconnect_sl_err'] )
            ? sanitize_text_field( wp_unslash( (string) $_GET['ventraconnect_sl_err'] ) )
            : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ( 'core_new_account_blocked' === $code ) {
            $text = __(
                'You can’t create a new account with social login on this screen. Please register using the site’s sign-up form first, then sign in with your social account.',
                'ventraconnect-social-login'
            );

            $notice  = '<div class="notice notice-error ventraconnect-sl-message">';
            $notice .= '<p>' . esc_html( $text ) . '</p>';
            $notice .= '</div>';

            // Prepend our notice before any existing login message.
            return $notice . $message;
        }

        return $message;
    }

    public function augment_state( array $extras, string $provider, string $context ): array {
        if ( 'wp_comments' !== $context ) {
            return $extras;
        }
        if ( empty( $extras['redirect_to'] ) ) {
            $current = $this->current_url();
            if ( $current ) {
                $extras['redirect_to'] = $current;
            }
        }
        if ( empty( $extras['ventraconnect_sl_ctx'] ) ) {
            $extras['ventraconnect_sl_ctx'] = 'comments';
        }
        return $extras;
    }

    private function current_url(): string {
        $host        = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );
        $request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
        if ( '' === $host || '' === $request_uri ) {
            return '';
        }
        $scheme = is_ssl() ? 'https://' : 'http://';
        return esc_url_raw( $scheme . $host . $request_uri );
    }
}
