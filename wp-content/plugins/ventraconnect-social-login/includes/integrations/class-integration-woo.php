<?php
namespace VentraConnect\SocialLogin\Integrations;

use VentraConnect\SocialLogin\Buttons;
use VentraConnect\SocialLogin\User_Links;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * WooCommerce login & checkout integration.
 */
class Woo_Integration {

    private $buttons;
    private $did = false;
    public function __construct( Buttons $buttons ) { $this->buttons = $buttons; }

    public function register() {
        if ( ! ( defined( 'VCS_PRO_ACTIVE' ) && VCS_PRO_ACTIVE === true ) ) { return; }
        add_action( 'woocommerce_account_dashboard', [ $this, 'account_linked' ], 20 );
        add_action( 'woocommerce_account_content', [ $this, 'account_linked' ], 20 );
        add_filter( 'ventraconnect_sl_can_unlink', [ $this, 'filter_can_unlink' ], 10, 4 );
    }

    public function login_buttons() { $this->buttons->render( 'woo_login' ); }
    public function checkout_buttons() { /* handled elsewhere to prevent duplication */ }

    public function account_linked() {
        if ( $this->did ) { return; }
        if ( ! is_user_logged_in() ) { return; }
        $this->did = true;
        $user_id   = get_current_user_id();
        $links     = new User_Links();
        $conns     = $links->get_connections( $user_id );
        $conns     = array_values( array_filter( $conns, function( $c ) {
            $slug = strtolower( $c['provider'] ?? '' );
            return $slug && ! in_array( $slug, User_Links::EPHEMERAL_PROVIDERS, true );
        } ) );
        $myaccount = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
        $current_url = function_exists( 'wc_get_account_endpoint_url' )
            ? esc_url_raw( wc_get_account_endpoint_url( '', $myaccount ) )
            : $myaccount;
        $wc_settings = $this->get_wc_settings();
        $prevent_unlink = ! empty( $wc_settings['linking']['prevent_unlink'] );

        echo '<div class="vcs-myaccount-social" style="margin:16px 0;">';
        echo '<h3>' . esc_html__( 'Social Login', 'ventraconnect-social-login' ) . '</h3>';

        echo '<div class="vcs-linked-list">';
        if ( empty( $conns ) ) {
            echo '<p class="description">' . esc_html__( 'No provider linked yet.', 'ventraconnect-social-login' ) . '</p>';
        } else {
            echo '<ul style="margin:0;list-style:none;padding:0;">';
            foreach ( $conns as $c ) {
                $p = sanitize_text_field( $c['provider'] ?? '' );
                if ( ! $p ) { continue; }
                $label = ucfirst( $p );
                $icon  = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/' . $p . '.svg' : '';
                $has_icon = $icon && file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $p . '.svg' );
                $unlink_args = [
                    'ventraconnect_sl_unlink' => $p,
                    '_wpnonce'                => wp_create_nonce( 'ventraconnect_sl_unlink_' . $p ),
                ];
                // Preserve return target so unlink handler can honor it.
                $unlink_args['return'] = $current_url;
                $unlink = add_query_arg( $unlink_args, $myaccount );
                echo '<li style="margin:6px 0;display:flex;align-items:center;gap:8px;">';
                if ( $has_icon ) echo '<img alt="" src="' . esc_url( $icon ) . '" width="20" height="20">';
                echo '<span>' . esc_html( $label ) . '</span>';
                if ( ! $prevent_unlink ) {
                    echo '<a class="button button-small" href="' . esc_url( $unlink ) . '">' . esc_html__( 'Unlink', 'ventraconnect-social-login' ) . '</a>';
                }
                echo '</li>';
            }
            echo '</ul>';
        }
        if ( $prevent_unlink ) {
            echo '<p class="description" style="margin-top:8px;">' . esc_html__( 'Linked providers cannot be disconnected while WooCommerce protection is enabled.', 'ventraconnect-social-login' ) . '</p>';
        }

        echo '</div>';

        $creds = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption( 'ventraconnect_sl_provider_creds', [] );
        $configured = array_values( array_filter( array_keys( $creds ), function( $slug ) use ( $creds ) {
            if ( in_array( $slug, User_Links::EPHEMERAL_PROVIDERS, true ) ) { return false; }
            $cred = (array) ( $creds[ $slug ] ?? [] );
            return ! empty( $cred['client_id'] ) && ! empty( $cred['client_secret'] );
        } ) );
        $settings = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $enabled_slugs = array_values(
            array_filter(
                (array) ( $settings['providers'] ?? [] ),
                static function ( $slug ) {
                    return is_string( $slug ) && '' !== $slug;
                }
            )
        );
        if ( ! empty( $enabled_slugs ) ) {
            $configured = array_values( array_intersect( $configured, $enabled_slugs ) );
        } else {
            $configured = [];
        }
        $linked_slugs = array_map( function( $c ){ return sanitize_text_field( $c['provider'] ?? '' ); }, $conns );
        $to_link = array_values( array_diff( $configured, $linked_slugs ) );

        echo '<div class="vcs-link-new" style="margin-top:10px;">';
        echo '<p class="description" style="margin-bottom:6px;">' . esc_html__( 'Link another account:', 'ventraconnect-social-login' ) . '</p>';
        if ( ! empty( $to_link ) ) {
            $settings  = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
            $use_popup = ! empty( $settings['use_popup_oauth'] );
            foreach ( $to_link as $slug ) {
                $prov = \VentraConnect\SocialLogin\OAuth::provider( $slug );
                if ( ! $prov ) { continue; }
                $state_extra = [
                    'return'               => $current_url,
                    // Ensure link flows return to the current Woo Account page.
                    'redirect_to'          => $current_url,
                    'ventraconnect_sl_ctx' => 'wc_account',
                ];
                if ( $use_popup ) {
                    $state_extra['ventraconnect_sl_popup'] = 1;
                }
                $state = \VentraConnect\SocialLogin\OAuth::generate_state( $slug, $state_extra );
                $auth  = $prov->get_auth_url( $state, \VentraConnect\SocialLogin\OAuth::redirect_uri( $slug ) );
                $icon  = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/' . $slug . '.svg' : '';
                $has_icon = $icon && file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $slug . '.svg' );
                echo '<a class="vcs-btn vcs-btn--wide" data-provider="' . esc_attr( $slug ) . '" data-theme="light" href="' . esc_url( $auth ) . '" style="margin-right:6px;">';
                if ( $has_icon ) {
                    echo '<span class="vcs-btn__icon" aria-hidden="true"><img alt="" src="' . esc_url( $icon ) . '" width="16" height="16"></span>';
                }
                echo '<span class="vcs-btn__label">';
                echo esc_html( sprintf(
                    /* translators: 1: Provider label. */
                    __( 'Link %1$s', 'ventraconnect-social-login' ),
                    ucfirst( $slug )
                ) );
                echo '</span>';
                echo '</a>';
            }
        } else {
            echo '<p class="description">' . esc_html__( 'All available providers are already linked or none are configured.', 'ventraconnect-social-login' ) . '</p>';
        }
        echo '</div>';

        echo '</div>';
    }

    /**
     * Optionally prevent unlinking when coming from WooCommerce My Account,
     * based on the WooCommerce "Prevent unlinking" setting.
     *
     * @param bool   $can        Current allow/deny flag from core/other integrations.
     * @param string $provider   Provider slug (e.g. 'facebook').
     * @param int    $user_id    Current user ID.
     * @param string $return_url Redirect target after unlink.
     *
     * @return bool
     */
    public function filter_can_unlink( bool $can, string $provider, int $user_id, string $return_url ): bool {
        // If someone else already blocked unlink, respect that.
        if ( false === $can ) {
            return false;
        }

        // Get WooCommerce settings to read "Prevent unlinking" toggle.
        $wc_settings = $this->get_wc_settings();
        $prevent_unlink = ! empty( $wc_settings['linking']['prevent_unlink'] );

        // If Woo "Prevent unlinking" is not enabled, do nothing.
        if ( ! $prevent_unlink ) {
            return $can;
        }

        // Determine if this unlink is happening from Woo My Account.
        $is_woo_context = false;

        if ( is_string( $return_url ) && '' !== $return_url ) {
            // Preferred: compare against Woo My Account permalink.
            if ( function_exists( 'wc_get_page_permalink' ) ) {
                $account_url = wc_get_page_permalink( 'myaccount' );
                if ( $account_url && false !== strpos( $return_url, $account_url ) ) {
                    $is_woo_context = true;
                }
            }

            // Fallback: path check for /my-account/ in case My Account URL is customized.
            if ( ! $is_woo_context && false !== strpos( $return_url, '/my-account' ) ) {
                $is_woo_context = true;
            }
        }

        // Only block unlink when:
        //  - Woo "Prevent unlinking" is ON, AND
        //  - The unlink is happening from Woo My Account context.
        if ( $is_woo_context ) {
            return false;
        }

        return $can;
    }

    private function get_wc_settings(): array {
        if ( function_exists( '\VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings' ) ) {
            return \VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings();
        }
        return (array) apply_filters( 'ventraconnect_sl_wc_settings', [] );
    }

}
