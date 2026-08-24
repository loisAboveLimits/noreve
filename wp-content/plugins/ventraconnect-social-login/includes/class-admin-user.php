<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin integrations with WordPress Users screens.
 * - Adds link/unlink UI on user profile pages
 * - Adds a Users table column showing linked providers
 */
class Admin_User {

    public function register() {
        add_action( 'show_user_profile', [ $this, 'render_profile_box' ] );
        add_action( 'edit_user_profile', [ $this, 'render_profile_box' ] );
        add_filter( 'manage_users_columns', [ $this, 'add_users_column' ] );
        add_filter( 'manage_users_custom_column', [ $this, 'render_users_column' ], 10, 3 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    /**
     * Render Social Login section on user profile.
     */
    public function render_profile_box( $user ) {
        if ( ! ( $user instanceof \WP_User ) ) { $user = get_userdata( (int) $user ); }
        if ( ! $user ) { return; }

        $links        = new User_Links();
        $current_id   = get_current_user_id();
        $is_own       = ( (int) $user->ID === (int) $current_id );
        $conns        = $links->get_connections( $user->ID );
        $primary      = (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user->ID, User_Links::META_PRIMARY, true, '' );
        $linked_slugs = [];
        foreach ( $conns as $conn ) {
            $slug = sanitize_key( $conn['provider'] ?? '' );
            if ( $slug ) {
                $linked_slugs[] = $slug;
            }
        }
        $linked_slugs = array_values( array_unique( $linked_slugs ) );
        $last_provider = (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user->ID, 'ventraconnect_sl_last_provider', true, '' );
        $creds      = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderCreds();
        $providers  = array_values( array_filter( array_keys( $creds ), function( $slug ) use ( $creds ) {
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
            $providers = array_values( array_intersect( $providers, $enabled_slugs ) );
        } else {
            $providers = [];
        }

        echo '<div id="vcs-social-login-section">';
        echo '<h2>' . esc_html__( 'Social Login', 'ventraconnect-social-login' ) . '</h2>';
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th>' . esc_html__( 'Linked Providers', 'ventraconnect-social-login' ) . '</th><td>';

        if ( empty( $conns ) ) {
            echo '<span class="description">' . esc_html__( 'No provider linked yet.', 'ventraconnect-social-login' ) . '</span>';
        } else {
            echo '<ul style="margin:0;">';
            foreach ( $conns as $c ) {
                $slug = sanitize_text_field( $c['provider'] ?? '' );
                if ( ! $slug || in_array( $slug, User_Links::EPHEMERAL_PROVIDERS, true ) ) { continue; }
                $label = ucfirst( $slug );
                $icon  = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/' . $slug . '.svg' : '';
                $has_icon = $icon && file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $slug . '.svg' );
                echo '<li style="margin:4px 0;display:flex;align-items:center;gap:8px;">';
                if ( $has_icon ) { echo '<img alt="" src="' . esc_url( $icon ) . '" width="18" height="18">'; }
                echo '<strong>' . esc_html( $label ) . '</strong>';
                if ( $primary === $slug ) { echo ' <span class="description">(' . esc_html__( 'primary', 'ventraconnect-social-login' ) . ')</span>'; }
                if ( $is_own ) {
                    $unlink_url = wp_nonce_url( add_query_arg( [ 'ventraconnect_sl_unlink' => $slug ], wp_get_referer() ?: admin_url( 'profile.php' ) ), 'ventraconnect_sl_unlink_' . $slug );
                    echo ' <a class="button button-small" href="' . esc_url( $unlink_url ) . '">' . esc_html__( 'Unlink', 'ventraconnect-social-login' ) . '</a>';
                }
                echo '</li>';
            }
            echo '</ul>';
        }
        echo '</td></tr>';

        $last_sync = (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user->ID, 'ventraconnect_sl_last_profile_sync', true, '' );
        $last_fields = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user->ID, 'ventraconnect_sl_last_sync_fields', true, [] );
        $field_map = [
            'display_name' => __( 'Display name', 'ventraconnect-social-login' ),
            'first_name'   => __( 'First name', 'ventraconnect-social-login' ),
            'last_name'    => __( 'Last name', 'ventraconnect-social-login' ),
            'email'        => __( 'Email', 'ventraconnect-social-login' ),
            'avatar'       => __( 'Avatar', 'ventraconnect-social-login' ),
            'locale'       => __( 'Locale', 'ventraconnect-social-login' ),
            'profile_url'  => __( 'Profile URL', 'ventraconnect-social-login' ),
            'company'      => __( 'Company', 'ventraconnect-social-login' ),
            'headline'     => __( 'Headline', 'ventraconnect-social-login' ),
            'website'      => __( 'Website', 'ventraconnect-social-login' ),
            'location'     => __( 'Location', 'ventraconnect-social-login' ),
        ];
        $fields_summary = [];
        if ( ! empty( $last_fields['fields'] ) && is_array( $last_fields['fields'] ) ) {
            foreach ( $last_fields['fields'] as $field ) {
                if ( isset( $field_map[ $field ] ) ) {
                    $fields_summary[] = $field_map[ $field ];
                }
            }
        }
        echo '<tr><th>' . esc_html__( 'Last profile sync', 'ventraconnect-social-login' ) . '</th><td>';
        if ( $last_sync ) {
            echo '<p style="margin:0;">' . esc_html( $last_sync ) . '</p>';
            if ( ! empty( $fields_summary ) ) {
                echo '<p class="description" style="margin-top:4px;">' . esc_html__( 'Updated fields:', 'ventraconnect-social-login' ) . ' ' . esc_html( implode( ', ', $fields_summary ) ) . '</p>';
            } else {
                echo '<p class="description" style="margin-top:4px;">' . esc_html__( 'No profile fields changed during the last sync.', 'ventraconnect-social-login' ) . '</p>';
            }
        } else {
            echo '<span class="description">' . esc_html__( 'No profile sync recorded yet.', 'ventraconnect-social-login' ) . '</span>';
        }
        if ( current_user_can( 'edit_user', $user->ID ) ) {
            $nonce = wp_create_nonce( 'ventraconnect_sl_resync_user_' . $user->ID );
            echo '<p style="margin-top:8px;"><button type="button" class="button button-secondary vcs-resync-user" data-user="' . (int) $user->ID . '" data-provider="' . esc_attr( $last_provider ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Resync profile', 'ventraconnect-social-login' ) . '</button></p>';
            echo '<div class="vcs-resync-user-output" style="display:none;margin-top:6px;"></div>';
        }
        echo '</td></tr>';

        echo '<tr><th>' . esc_html__( 'Link New Provider', 'ventraconnect-social-login' ) . '</th><td>';
        if ( empty( $providers ) ) {
            echo '<span class="description">' . esc_html__( 'No providers configured. Set up providers under Settings -> Providers.', 'ventraconnect-social-login' ) . '</span>';
        } elseif ( ! $is_own ) {
            echo '<span class="description">' . esc_html__( 'Only the account owner can link or unlink providers here.', 'ventraconnect-social-login' ) . '</span>';
        } else {
            $available_providers = array_values( array_filter( $providers, function( $slug ) use ( $linked_slugs ) {
                return ! in_array( sanitize_key( $slug ), $linked_slugs, true );
            } ) );

            if ( empty( $available_providers ) ) {
                echo '<span class="description">' . esc_html__( 'All configured providers are already linked to this account.', 'ventraconnect-social-login' ) . '</span>';
            }

            $settings    = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
            $use_popup   = ! empty( $settings['use_popup_oauth'] );
            $profile_url = admin_url( 'profile.php' );
            foreach ( $available_providers as $slug ) {
                $prov = OAuth::provider( $slug );
                if ( ! $prov ) { continue; }
                $state_extra = [
                    'return'               => $profile_url,
                    // Ensure link flows return to the current profile page.
                    'redirect_to'          => $profile_url,
                    'ventraconnect_sl_ctx' => 'wp_profile',
                ];
                if ( $use_popup ) {
                    $state_extra['ventraconnect_sl_popup'] = 1;
                }
                $state = OAuth::generate_state( $slug, $state_extra );
                $auth  = $prov->get_auth_url( $state, OAuth::redirect_uri( $slug ) );
                $icon  = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/' . $slug . '.svg' : '';
                $has_icon = $icon && file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $slug . '.svg' );
                echo '<a class="button" data-provider="' . esc_attr( $slug ) . '" style="margin-right:6px;display:inline-flex;align-items:center;gap:6px;" href="' . esc_url( $auth ) . '">';
                if ( $has_icon ) { echo '<img alt="" src="' . esc_url( $icon ) . '" width="16" height="16">'; }
                echo esc_html( sprintf(
                    /* translators: 1: Provider label. */
                    __( 'Link %1$s', 'ventraconnect-social-login' ),
                    ucfirst( $slug )
                ) );
                echo '</a>';
            }
        }
        echo '</td></tr>';
        echo '</table>';
        echo '</div>';
    }

    public function add_users_column( $cols ) {
        $cols['ventraconnect_sl_social'] = __( 'Linked Profiles', 'ventraconnect-social-login' );
        return $cols;
    }

    public function render_users_column( $output, $column_name, $user_id ) {
        if ( 'ventraconnect_sl_social' !== $column_name ) { return $output; }
        $links = new User_Links();
        $conns = $links->get_connections( (int) $user_id );
        $conns = array_values( array_filter( $conns, function( $c ) {
            $slug = strtolower( $c['provider'] ?? '' );
            return $slug && ! in_array( $slug, User_Links::EPHEMERAL_PROVIDERS, true );
        } ) );
        if ( empty( $conns ) ) { return '<span class="dashicons dashicons-dismiss" aria-hidden="true"></span>'; }
        $parts = [];
        foreach ( $conns as $c ) {
            $p = sanitize_text_field( $c['provider'] ?? '' );
            if ( ! $p ) { continue; }
            $icon = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/' . $p . '.svg' : '';
            $has_icon = $icon && file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $p . '.svg' );
            $img = $has_icon ? '<img alt="' . esc_attr( ucfirst( $p ) ) . '" src="' . esc_url( $icon ) . '" width="18" height="18">' : esc_html( strtoupper( substr( $p, 0, 1 ) ) );
            $parts[] = '<span class="wsc-button wsc-button-' . esc_attr( $p ) . '" aria-hidden="true">' . $img . '</span>';
        }
        if ( empty( $parts ) ) { return '<span class="dashicons dashicons-dismiss" aria-hidden="true"></span>'; }
        return '<span class="wsc-buttons wsc-style-compact" style="display:inline-flex;gap:4px;align-items:center;">' . implode( ' ', $parts ) . '</span>';
    }

    public function enqueue( $hook ) {
        if ( in_array( $hook, [ 'profile.php', 'user-edit.php', 'users.php' ], true ) ) {
            wp_enqueue_style( 'wsc-frontend', defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/css/frontend.css' : plugin_dir_url( __FILE__ ) . '../assets/css/frontend.css', [], defined( 'VENTRACONNECT_SL_VERSION' ) ? VENTRACONNECT_SL_VERSION : '1.0' );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'wsc-admin', VENTRACONNECT_SL_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery', 'jquery-ui-sortable' ], defined( 'VENTRACONNECT_SL_VERSION' ) ? VENTRACONNECT_SL_VERSION : '1.0', true );
            wp_localize_script( 'wsc-admin', 'VCS_ADMIN', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'provider_order_nonce' => wp_create_nonce( 'ventraconnect_sl_provider_order' ),
                'provider_order_nonce_legacy' => wp_create_nonce( 'vc_provider_order' ),
            ] );
            wp_localize_script(
                'wsc-admin',
                'VCS_AUTH',
                [
                    'ajax_url'     => admin_url( 'admin-ajax.php' ),
                    'nonce'        => wp_create_nonce( 'ventraconnect_sl_auth' ),
                    'nonce_legacy' => wp_create_nonce( 'ventraconnect_sl_auth' ),
                ]
            );
            if ( in_array( $hook, [ 'profile.php', 'user-edit.php' ], true ) ) {
                $script = 'document.addEventListener("DOMContentLoaded",function(){var sec=document.getElementById("vcs-social-login-section");if(!sec)return;var nameInput=document.querySelector("input#first_name, input[name=\\"first_name\\"]");if(!nameInput)return;var tbl=nameInput.closest("table.form-table");if(!tbl||!tbl.parentNode)return;var ref=tbl;var prev=tbl.previousElementSibling;if(prev&&prev.tagName==="H2"){ref=prev;}tbl.parentNode.insertBefore(sec, ref);});';
                wp_add_inline_script( 'jquery', $script );
                // Enqueue popup auth on profile screens when enabled.
                $settings        = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
                $use_popup_oauth = ! empty( $settings['use_popup_oauth'] );
                if ( $use_popup_oauth ) {
                    wp_enqueue_script( 'vcs-popup-auth', VENTRACONNECT_SL_PLUGIN_URL . 'assets/js/popup-auth.js', [], defined( 'VENTRACONNECT_SL_VERSION' ) ? VENTRACONNECT_SL_VERSION : '1.0', true );
                }
            }
        }
    }
}
