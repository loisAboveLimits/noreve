<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin Settings orchestrator for VentraConnect Social Login.
 *
 * Wires the Settings API, builds state via Persistence, and delegates all
 * markup to renderers. Avoids direct option reads/writes in favor of
 * Admin\Settings\Persistence. No behavior or HTML structure changes here.
 */
class Settings {
    /**
     * Persistence service for settings/options (read/write helpers).
     *
     * @var \VentraConnect\SocialLogin\Admin\Settings\Persistence
     */
    private $persistence;
    /**
     * Fields renderer for settings UI.
     *
     * @var \VentraConnect\SocialLogin\Admin\Settings\FieldsRenderer
     */
    private $fields_renderer;
    /**
     * Sanitizer for settings payloads (used via static methods).
     *
     * @var \VentraConnect\SocialLogin\Admin\Settings\Sanitizer
     */
    private $sanitizer;
    /**
     * Integrations preview tab orchestrator.
     *
     * @var \VentraConnect\SocialLogin\Admin\IntegrationsPreview\Tab
     */
    private $integrations_tab;

    public function __construct() {
        $this->persistence = new \VentraConnect\SocialLogin\Admin\Settings\Persistence();
        $this->fields_renderer = new \VentraConnect\SocialLogin\Admin\Settings\FieldsRenderer();
        $this->sanitizer = new \VentraConnect\SocialLogin\Admin\Settings\Sanitizer();
        $this->integrations_tab = new \VentraConnect\SocialLogin\Admin\IntegrationsPreview\Tab();
    }
    /**
     * Hook admin menus and settings handlers.
     */
    public function hooks() {
        // Settings API registration remains here for now
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        // Delegate other hooks to the admin ServiceProvider to decouple responsibilities
        if ( is_admin() ) {
            ( new \VentraConnect\SocialLogin\Admin\ServiceProvider() )->register();
        }
    }

    /**
     * Register settings with the WordPress Settings API.
     */
    public function register_settings() {
        register_setting( 'ventraconnect_sl_settings_group', 'ventraconnect_sl_settings', [ $this, 'sanitize' ] );
    }

    /**
     * Sanitize input values and persist mirrors.
     *
     * @param array $input Raw input from Settings API
     */
    public function sanitize( $input ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        }
        // Use pure sanitizer to produce settings array without side effects.
        $existing = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $out = \VentraConnect\SocialLogin\Admin\Settings\Sanitizer::sanitizeAll( (array) $input, (array) $existing );

        // Ensure passwordless_mode is always a known value.
        $allowed_passwordless_modes = array( 'off', 'recommended', 'strict' );
        if ( isset( $input['passwordless_mode'] ) ) {
            $mode = (string) $input['passwordless_mode'];
            if ( in_array( $mode, $allowed_passwordless_modes, true ) ) {
                $out['passwordless_mode'] = $mode;
            } else {
                $out['passwordless_mode'] = 'off';
            }
        } elseif ( ! isset( $out['passwordless_mode'] ) || ! in_array( $out['passwordless_mode'], $allowed_passwordless_modes, true ) ) {
            $out['passwordless_mode'] = 'off';
        }

        // Enforce passwordless constraints for Recommended and Strict modes
        // only when the passwordless_mode field is part of the submitted payload.
        if ( array_key_exists( 'passwordless_mode', (array) $input ) ) {
            $mode = isset( $out['passwordless_mode'] ) ? (string) $out['passwordless_mode'] : 'off';

            if ( 'recommended' === $mode ) {
                $methods      = \VentraConnect\SocialLogin\Passwordless::get_enabled_methods();
                $enabled_cnt  = isset( $methods['enabled_count'] ) ? (int) $methods['enabled_count'] : 0;

                if ( $enabled_cnt <= 0 ) {
                    // No passwordless methods enabled: force mode back to off.
                    $out['passwordless_mode'] = 'off';

                    add_settings_error(
                        'ventraconnect_sl_settings',
                        'vcsl_pwless_recommended_needs_method',
                        esc_html__( 'Recommended mode requires at least one passwordless method enabled (Social Login, Magic Link, or OTP Email).', 'ventraconnect-social-login' ),
                        'error'
                    );
                } elseif ( 1 === $enabled_cnt ) {
                    // Soft warning when only a single method is active.
                    add_settings_error(
                        'ventraconnect_sl_settings',
                        'vcsl_pwless_single_method_warning',
                        esc_html__( 'Recommended mode is using only one passwordless method. For safety, consider enabling an additional method as a backup.', 'ventraconnect-social-login' ),
                        'warning'
                    );
                }
            } elseif ( 'strict' === $mode ) {
                // Previous saved mode (used when strict validation fails).
                $prev      = (array) get_option( 'ventraconnect_sl_settings', array() );
                $prev_mode = isset( $prev['passwordless_mode'] ) ? (string) $prev['passwordless_mode'] : 'off';

                $methods     = \VentraConnect\SocialLogin\Passwordless::get_enabled_methods();
                $enabled_cnt = isset( $methods['enabled_count'] ) ? (int) $methods['enabled_count'] : 0;
                $has_email   = ( ! empty( $methods['magic_link'] ) || ! empty( $methods['otp'] ) );
                $has_context = \VentraConnect\SocialLogin\Passwordless::has_any_active_login_context();

                if ( ! $has_email ) {
                    // Strict mode requires at least one email-based method.
                    $out['passwordless_mode'] = $prev_mode;

                    add_settings_error(
                        'ventraconnect_sl_settings',
                        'vcsl_pwless_strict_needs_email',
                        esc_html__( 'Strict mode requires at least one email-based method (Magic Link or OTP Email) to be enabled.', 'ventraconnect-social-login' ),
                        'error'
                    );
                } elseif ( ! $has_context ) {
                    // Strict mode requires at least one active login context.
                    $out['passwordless_mode'] = $prev_mode;

                    add_settings_error(
                        'ventraconnect_sl_settings',
                        'vcsl_pwless_strict_needs_context',
                        esc_html__( 'Strict mode requires at least one active login context (WordPress Login or a supported integration login form).', 'ventraconnect-social-login' ),
                        'error'
                    );
                } else {
                    // Strict is allowed; warn if only a single method is active.
                    if ( 1 === $enabled_cnt ) {
                        add_settings_error(
                            'ventraconnect_sl_settings',
                            'vcsl_pwless_single_method_warning',
                            esc_html__( 'Strict mode is using only one passwordless method. For safety, consider enabling a second method as a backup.', 'ventraconnect-social-login' ),
                            'warning'
                        );
                    }
                }
            }
        }

        // Side-effects / mirrors handled via Persistence to keep behavior identical
        if ( array_key_exists( 'button_style', (array) $input ) && isset( $out['button_style'] ) ) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::setButtonStyle( (string) $out['button_style'] );
        }
        if ( array_key_exists( 'global_theme', (array) $input ) ) {
            $theme = in_array( $input['global_theme'], [ 'light','dark','minimal' ], true ) ? (string) $input['global_theme'] : 'light';
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::setGlobalTheme( $theme );
        }
        if ( isset( $input['provider_theme'] ) && is_array( $input['provider_theme'] ) ) {
            foreach ( (array) $input['provider_theme'] as $slug => $theme ) {
                $slug  = sanitize_key( $slug );
                $theme = in_array( $theme, [ 'light','dark','minimal' ], true ) ? $theme : 'light';
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::setProviderTheme( $slug, $theme );
            }
        }
        if ( isset( $input['provider_theme_override'] ) && is_array( $input['provider_theme_override'] ) ) {
            foreach ( (array) $input['provider_theme_override'] as $slug => $flag ) {
                $slug = sanitize_key( $slug );
                $value = empty( $flag ) ? 0 : 1;
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::setProviderThemeOverride( $slug, (int) $value );
            }
        }
        if ( isset( $input['provider_text'] ) && is_array( $input['provider_text'] ) ) {
            foreach ( (array) $input['provider_text'] as $slug => $text ) {
                $slug  = sanitize_key( $slug );
                $text  = sanitize_text_field( (string) $text );
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::setProviderText( $slug, $text );
            }
        }

        // Provider creds handled separately
        $in_creds = isset( $input['provider_creds'] ) ? (array) $input['provider_creds'] : ( ( isset( $input['credentials'] ) ? (array) $input['credentials'] : [] ) );
        $existing_creds = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderCreds();
        $slugs = [ 'google','facebook','github','microsoft','linkedin','slack','amazon','yahoo','wordpress','discord','spotify','line','twitter','tiktok','twitch','reddit' ];
        foreach ( $slugs as $slug ) {
            if ( isset( $in_creds[ $slug ] ) && is_array( $in_creds[ $slug ] ) ) {
                if ( array_key_exists( 'client_id', $in_creds[ $slug ] ) ) {
                    $existing_creds[ $slug ]['client_id'] = sanitize_text_field( $in_creds[ $slug ]['client_id'] );
                }
                if ( array_key_exists( 'client_secret', $in_creds[ $slug ] ) ) {
                    $existing_creds[ $slug ]['client_secret'] = sanitize_text_field( $in_creds[ $slug ]['client_secret'] );
                }
                if ( 'slack' === $slug && array_key_exists( 'team', $in_creds[ $slug ] ) ) {
                    $existing_creds[ $slug ]['team'] = sanitize_text_field( $in_creds[ $slug ]['team'] );
                }
            }
        }
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::saveProviderCreds( (array) $existing_creds );

        // Profile Sync options
        if ( isset( $input['sync_free'] ) && is_array( $input['sync_free'] ) ) {
            $sf = [];
            foreach ( $input['sync_free'] as $prov => $vals ) {
                $sf[ sanitize_key( $prov ) ] = [
                    'avatar'       => empty( $vals['avatar'] ) ? 0 : 1,
                    'display_name' => empty( $vals['display_name'] ) ? 0 : 1,
                    'first_last'   => empty( $vals['first_last'] ) ? 0 : 1,
                    'email'        => empty( $vals['email'] ) ? 0 : 1,
                ];
            }
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateOption( 'ventraconnect_sl_sync_free', $sf );
        }
        if ( defined( 'VCS_PRO_ACTIVE' ) && VCS_PRO_ACTIVE && isset( $input['sync_pro'] ) && is_array( $input['sync_pro'] ) ) {
            $sp = [];
            $valid = [ 'fill_blanks','overwrite','never' ];
            foreach ( $input['sync_pro'] as $prov => $fields ) {
                $p = [];
                foreach ( (array) $fields as $field => $policy ) {
                    $p[ sanitize_key( $field ) ] = in_array( $policy, $valid, true ) ? $policy : 'never';
                }
                $sp[ sanitize_key( $prov ) ] = $p;
            }
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateOption( 'ventraconnect_sl_sync_pro', $sp );
            $relay = [];
            if ( isset( $input['email_relay'] ) && is_array( $input['email_relay'] ) ) {
                foreach ( $input['email_relay'] as $prov => $flag ) {
                    if ( empty( $flag ) ) { continue; }
                    $relay[ sanitize_key( $prov ) ] = 1;
                }
            }
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateOption( 'ventraconnect_sl_email_allow_relay_overwrite', $relay );
        } elseif ( ! defined( 'VCS_PRO_ACTIVE' ) || ! VCS_PRO_ACTIVE ) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateOption( 'ventraconnect_sl_email_allow_relay_overwrite', [] );
        }

        return $out;
    }

    /**
     * Render embedded provider configuration (to show inside Providers tab right pane).
     * Mirrors render_provider_page() content without hero/back and without opening a form.
     */
    private function render_provider_embed( string $slug ) {
        $settings = (array) $this->persistence->getSettings();
        $creds    = (array) $this->persistence->getProviderCreds();
        $meta     = \VentraConnect\SocialLogin\Admin\Settings\ProviderMeta::build( $slug, $this->persistence );
        $label    = isset( $meta['label'] ) ? (string) $meta['label'] : ucfirst( $slug );
        $redirect = isset( $meta['redirect_uri'] ) ? (string) $meta['redirect_uri'] : '';
        $rid = 'wsc-redirect-' . sanitize_html_class( $slug );
        $enabled = (array) ( $settings['providers'] ?? [] );

        $provider = \VentraConnect\SocialLogin\OAuth::provider( $slug );
        $verify_url = '';
        if ( $provider ) {
            $return_url = admin_url( 'admin.php?page=ventraconnect-sl-settings&view=provider&provider=' . $slug );
            $state = \VentraConnect\SocialLogin\OAuth::generate_state( $slug, [ 'verify' => 1, 'return' => $return_url ] );
            $verify_url = $provider->get_auth_url( $state, $redirect );
        }

        $diag_nonce = wp_create_nonce( 'ventraconnect_sl_diag_' . $slug );
        $diag_url = admin_url( 'admin-ajax.php?action=ventraconnect_sl_run_diagnostics&provider=' . rawurlencode( $slug ) . '&json=1&_wpnonce=' . $diag_nonce );

        $state = [
            'slug' => $slug,
            'settings' => $settings,
            'creds' => $creds,
            'label' => $label,
            'redirect' => $redirect,
            'redirect_id' => $rid,
            'enabled' => $enabled,
            'admin_email' => (string) get_option( 'admin_email', '' ),
            'verify_url' => $verify_url,
            'diag_nonce' => $diag_nonce,
            'diag_url' => $diag_url,
            'upsell_copy' => \VentraConnect\SocialLogin\Pro_Gates::get_provider_upsell_copy( $slug ),
        ];

        $this->fields_renderer->renderProviderEmbed(
            $state,
            static function () {
                return \VentraConnect\SocialLogin\Pro_Gates::is_pro();
            }
        );
    }
/**
 * Render the main settings page: builds state, routes tabs, delegates rendering.
 */
public function render_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Optional GET-only nonce check for admin views when provided.
    if ( isset( $_GET['_wpnonce'] ) ) {
        check_admin_referer( 'ventraconnect_sl_action' );
    }

    // Provider deep configuration page (only when explicitly requested).
    $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
    if ( ! empty( $view ) && 'provider' === $view && ! empty( $_GET['provider'] ) ) {
        $slug = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : '';
        $this->render_provider_page( $slug );
        return;
    }

    // Build core settings state (same as before).
    $settings = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
    $enabled  = (array) ( $settings['providers'] ?? array() );
    $creds    = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderCreds();

    $redirect_google    = \VentraConnect\SocialLogin\OAuth::redirect_uri( 'google' );
    $redirect_facebook  = \VentraConnect\SocialLogin\OAuth::redirect_uri( 'facebook' );
    $redirect_github    = \VentraConnect\SocialLogin\OAuth::redirect_uri( 'github' );
    $redirect_discord   = \VentraConnect\SocialLogin\OAuth::redirect_uri( 'discord' );
    $redirect_wordpress = \VentraConnect\SocialLogin\OAuth::redirect_uri( 'wordpress' );
    $redirect_slack     = \VentraConnect\SocialLogin\OAuth::redirect_uri( 'slack' );

    $has_creds = false;
    foreach ( array( 'google', 'facebook', 'twitch', 'reddit' ) as $core_slug ) {
        if (
            ! empty( $creds[ $core_slug ]['client_id'] )
            && ! empty( $creds[ $core_slug ]['client_secret'] )
        ) {
            $has_creds = true;
            break;
        }
    }

    $debug_mode        = ! empty( $settings['debug_mode'] );
    $prevent_external  = isset( $settings['prevent_external_override'] ) ? (bool) $settings['prevent_external_override'] : true;
    $redirect_default_login    = isset( $settings['redirect_default_login'] ) ? esc_url( $settings['redirect_default_login'] ) : '';
    $redirect_default_register = isset( $settings['redirect_default_register'] ) ? esc_url( $settings['redirect_default_register'] ) : '';
    $redirect_blacklist        = ( isset( $settings['redirect_blacklist'] ) && is_array( $settings['redirect_blacklist'] ) )
        ? $settings['redirect_blacklist']
        : array();

    $button_style = in_array( ( $settings['button_style'] ?? 'wide' ), array( 'wide', 'compact' ), true )
        ? $settings['button_style']
        : 'wide';

    $global_theme = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getGlobalTheme( 'light' );

    $state = array(
        'settings'                 => $settings,
        'enabled'                  => $enabled,
        'creds'                    => $creds,
        'has_creds'                => $has_creds,
        'debug_mode'               => $debug_mode,
        'prevent_external'         => $prevent_external,
        'redirect_default_login'   => $redirect_default_login,
        'redirect_default_register'=> $redirect_default_register,
        'redirect_blacklist'       => $redirect_blacklist,
        'button_style'             => $button_style,
        'global_theme'             => $global_theme,
        'redirect_google'          => $redirect_google,
        'redirect_facebook'        => $redirect_facebook,
        'redirect_github'          => $redirect_github,
        'redirect_discord'         => $redirect_discord,
        'redirect_wordpress'       => $redirect_wordpress,
        'redirect_slack'           => $redirect_slack,
        'provider_order'           => (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderOrder(),
        'docs_url'                 => 'https://wpventra.com/docs',
        'support_url'              => ( function_exists( 'vcsl_is_pro_active' ) && vcsl_is_pro_active() )
            ? 'https://wpventra.com/support/'
            : 'https://wordpress.org/support/plugin/ventraconnect-social-login/',
    );

    // Tabs + routing.
    $current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'providers';

    $valid_tabs = array(
        'providers',
        'general',
        'passwordless',
        'emails',
        'woocommerce',
        'community-memberships',
        'courses-lms',
        'comments',
        'logs',
        'diagnostics',
        'about',
    );

    if ( ! in_array( $current_tab, $valid_tabs, true ) ) {
        $current_tab = 'providers';
    }

    $current_provider = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : '';

    $tabs = array(
        'providers'             => __( 'Login Methods', 'ventraconnect-social-login' ),
        'general'               => __( 'Settings', 'ventraconnect-social-login' ),
        'diagnostics'           => __( 'Diagnostics &amp; Tools', 'ventraconnect-social-login' ),
        'passwordless'          => __( 'Password Phaseout', 'ventraconnect-social-login' ),
        'emails'                => __( 'Emails & Notifications', 'ventraconnect-social-login' ),
        'woocommerce'           => __( 'WooCommerce', 'ventraconnect-social-login' ),
        'community-memberships' => __( 'Community & Memberships', 'ventraconnect-social-login' ),
        'courses-lms'           => __( 'Courses & LMS', 'ventraconnect-social-login' ),
        'comments'              => __( 'Comments', 'ventraconnect-social-login' ),
        'logs'                  => __( 'Analytics', 'ventraconnect-social-login' ),
        'about'                 => __( 'Support', 'ventraconnect-social-login' ),
    );

    $free_tabs = array(
        'providers',
        'general',
        'diagnostics',
    );

    $advanced_tabs = array(
        'passwordless',
        'emails',
        'woocommerce',
        'community-memberships',
        'courses-lms',
        'comments',
        'logs',
    );

    $support_tabs = array(
        'about',
    );

    ?>
    <div class="wrap wsc-admin vcsl-admin">
        <h1 class="screen-reader-text">
            <?php echo esc_html__( 'VentraConnect Social Login', 'ventraconnect-social-login' ); ?>
        </h1>

        <?php
        // Show WordPress Settings API notices (options.php redirect).
        if ( function_exists( 'settings_errors' ) ) {
            // Unscoped: print any settings errors registered for this request.
            settings_errors();
        }

        // Admin notices (from verification flow).
        $tmp_notice = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getTransient( 'ventraconnect_sl_admin_notice' );
        if ( is_array( $tmp_notice ) && ! empty( $tmp_notice['message'] ) ) {
            $cls = ( 'error' === ( $tmp_notice['type'] ?? '' ) ) ? 'notice notice-error' : 'notice notice-success';
            echo '<div class="' . esc_attr( $cls ) . ' is-dismissible"><p>' . esc_html( $tmp_notice['message'] ) . '</p></div>';
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteTransient( 'ventraconnect_sl_admin_notice' );
        }
        ?>

        <div class="wsc-hero vcsl-header">
            <div class="wsc-title vcsl-header__title" role="heading" aria-level="1">
                <?php
                $vc_logo_url    = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/logo/VentraConnectLogo.svg' : '';
                $vc_logo_exists = $vc_logo_url && file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/logo/VentraConnectLogo.svg' );
                if ( $vc_logo_exists ) :
                    ?>
                    <img class="wsc-hero-logo" src="<?php echo esc_url( $vc_logo_url ); ?>" alt="" width="40" height="40" />
                <?php endif; ?>
                <span><?php echo esc_html__( 'VentraConnect – Native Authentication Stack for WordPress', 'ventraconnect-social-login' ); ?></span>
                <?php if ( defined( 'VENTRACONNECT_SL_VERSION' ) ) : ?>
                    <span class="wsc-version">v<?php echo esc_html( VENTRACONNECT_SL_VERSION ); ?></span>
                <?php endif; ?>
            </div>
            <div class="wsc-cta vcsl-header__actions">
                <?php if ( ! \VentraConnect\SocialLogin\Pro_Gates::is_pro() ) : ?>
                    <a href="https://wpventra.com/pricing/"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="button button-primary vcsl-upgrade-btn">
                        <?php esc_html_e( 'Upgrade to Pro', 'ventraconnect-social-login' ); ?>
                    </a>
                <?php else : ?>
                    <span class="vcsl-badge vcsl-badge--pro">
                        <?php esc_html_e( 'Pro Active', 'ventraconnect-social-login' ); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="vcsl-layout">
            <aside class="vcsl-sidebar">
                <nav class="vcsl-nav" aria-label="<?php esc_attr_e( 'VentraConnect settings sections', 'ventraconnect-social-login' ); ?>">
                    <?php
                    $render_sidebar_item = static function ( string $slug, string $extra_class = '' ) use ( $tabs, $current_tab, $current_provider ): void {
                        if ( ! isset( $tabs[ $slug ] ) ) {
                            return;
                        }

                        $url_args = array(
                            'page' => 'ventraconnect-sl-settings',
                            'tab'  => $slug,
                        );
                        if ( 'providers' === $slug && ! empty( $current_provider ) ) {
                            $url_args['provider'] = $current_provider;
                        }

                        $url   = add_query_arg( $url_args, admin_url( 'admin.php' ) );
                        $class = 'vcsl-nav__item' . ( $current_tab === $slug ? ' is-active' : '' );
                        if ( '' !== $extra_class ) {
                            $class .= ' ' . $extra_class;
                        }

                        echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">';
                        echo esc_html( $tabs[ $slug ] );
                        echo '</a>';
                    };
                    ?>
                    <div class="vcsl-nav__group vcsl-nav__group--primary">
                        <?php
                        foreach ( $free_tabs as $slug ) {
                            $render_sidebar_item( $slug );
                        }
                        ?>
                    </div>

                    <div class="vcsl-nav__group vcsl-nav__group--advanced">
                        <div class="vcsl-nav__divider" aria-hidden="true">
                            <?php esc_html_e( 'ADVANCED FEATURES', 'ventraconnect-social-login' ); ?>
                        </div>
                        <?php
                        foreach ( $advanced_tabs as $slug ) {
                            $render_sidebar_item( $slug );
                        }
                        ?>
                    </div>

                    <div class="vcsl-nav__group vcsl-nav__group--support">
                        <?php
                        foreach ( $support_tabs as $slug ) {
                            $render_sidebar_item( $slug, 'vcsl-nav__item--support' );
                        }
                        ?>
                    </div>
                </nav>
            </aside>

            <main class="vcsl-main">
                <?php
                switch ( $current_tab ) {
                    case 'general':
                        ?>
                        <form method="post" action="options.php">
                            <div class="vcs-options-group" data-group="ventraconnect_sl_settings_group">
                                <?php settings_fields( 'ventraconnect_sl_settings_group' ); ?>
                            </div>
                            <?php
                            $this->fields_renderer->renderGeneralTab(
                                $state,
                                static function () {
                                    return \VentraConnect\SocialLogin\Pro_Gates::is_pro();
                                },
                                true
                            );
                            ?><div class="wsc-admin wsc-savebar"><?php submit_button();?></div>
                        </form>
                        <?php
                        break;

                    case 'passwordless':
                        // Check whether Pro is active using the shared helper.
                        $pro_active_pwless = function_exists( 'vcsl_is_pro_active' ) ? vcsl_is_pro_active() : false;

                        if ( ! $pro_active_pwless ) {
                            // Free version: show banner + locked preview of Passwordless settings.
                            $pwless_preview_state                 = $state;
                            $pwless_preview_state['preview_only'] = true;

                            // Reuse the existing renderer instance.
                            $renderer = $this->fields_renderer;

                            \VentraConnect\SocialLogin\Admin\Pro_Preview::render(
                                array(
                                    'title'       => __( 'Unlock Password Phaseout', 'ventraconnect-social-login' ),
                                    'description' => __(
                                        'Configure how VentraConnect phases out password login using Social Login, Magic Link, OTP Email, and passkeys on supported forms.',
                                        'ventraconnect-social-login'
                                    ),
                                    'upgrade_url' => 'https://wpventra.com/pricing/',
                                ),
                                function () use ( $pwless_preview_state, $renderer ) {
                                    $renderer->renderPasswordlessTab( $pwless_preview_state, true );
                                }
                            );
                        } else {
                            // Pro version: full interactive form (existing behavior).
                            ?>
                            <form method="post" action="options.php">
                                <div class="vcs-options-group" data-group="ventraconnect_sl_settings_group">
                                    <?php settings_fields( 'ventraconnect_sl_settings_group' ); ?>
                                </div>
                                <?php
                                $this->fields_renderer->renderPasswordlessTab( $state, true );
                                ?>
                                <div class="wsc-admin wsc-savebar">
                                    <?php submit_button(); ?>
                                </div>
                            </form>
                            <?php
                        }

                        break;

                    case 'providers':
                        ?>
                        <form method="post" action="options.php">
                            <div class="vcs-options-group" data-group="ventraconnect_sl_settings_group">
                                <?php settings_fields( 'ventraconnect_sl_settings_group' ); ?>
                            </div>
                            <div id="wsc-tab-providers" class="wsc-tab is-active">
                            <?php
                            // Providers dashboard (unchanged UI, just moved into this case).
                            $all_providers = array(
                                array( 'slug' => 'passkey',    'label' => 'Passkey' ),
                                array( 'slug' => 'magic_link', 'label' => 'Magic Link' ),
                                array( 'slug' => 'otp_email',  'label' => 'OTP (Email)' ),
                                array( 'slug' => 'google',     'label' => 'Google' ),
                                array( 'slug' => 'facebook',   'label' => 'Facebook' ),
                                array( 'slug' => 'amazon',     'label' => 'Amazon' ),
                                array( 'slug' => 'twitter',    'label' => 'X' ),
                                array( 'slug' => 'twitch',     'label' => 'Twitch' ),
                                array( 'slug' => 'reddit',     'label' => 'Reddit' ),
                                array( 'slug' => 'microsoft',  'label' => 'Microsoft' ),
                                array( 'slug' => 'yahoo',      'label' => 'Yahoo' ),
                                array( 'slug' => 'wordpress',  'label' => 'WordPress.com' ),
                                array( 'slug' => 'slack',      'label' => 'Slack' ),
                                array( 'slug' => 'discord',    'label' => 'Discord' ),
                                array( 'slug' => 'linkedin',   'label' => 'LinkedIn' ),
                                array( 'slug' => 'spotify',    'label' => 'Spotify' ),
                                array( 'slug' => 'line',       'label' => 'LINE' ),
                                array( 'slug' => 'tiktok',     'label' => 'TikTok' ),
                                array( 'slug' => 'github',     'label' => 'GitHub' ),
                            );

                            $enabled_providers = $enabled;
                            $creds_providers   = $creds;

                            $by_slug = array();
                            foreach ( $all_providers as $p ) {
                                $by_slug[ $p['slug'] ] = $p;
                            }

                            $saved_order = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderOrder();
                            if ( ! empty( $saved_order ) ) {
                                $ordered = array();
                                foreach ( $saved_order as $slug_o ) {
                                    if ( isset( $by_slug[ $slug_o ] ) ) {
                                        $ordered[] = $by_slug[ $slug_o ];
                                        unset( $by_slug[ $slug_o ] );
                                    }
                                }
                                $all_providers = array_merge( $ordered, array_values( $by_slug ) );
                            }

                            $sel = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : '';
                            $overview_active = false;
                            if ( empty( $sel ) ) {
                                $overview_active = true;
                            } elseif ( ! isset( $by_slug[ $sel ] ) ) {
                                $sel             = '';
                                $overview_active = true;
                            }
                            ?>
                            <div class="wsc-providers-dashboard">
                                <noscript>
                                    <div class="notice notice-warning">
                                        <p><?php esc_html_e( 'JavaScript is disabled — settings will save with a full page reload.', 'ventraconnect-social-login' ); ?></p>
                                    </div>
                                </noscript>

                                <aside class="wsc-providers-sidebar"
                                       role="navigation"
                                       aria-label="<?php echo esc_attr__( 'Providers list', 'ventraconnect-social-login' ); ?>">
                                    <?php $overview_item_classes = 'wsc-provider-overview-item' . ( $overview_active ? ' is-active' : '' ); ?>
                                    <button type="button"
                                            class="<?php echo esc_attr( $overview_item_classes ); ?>"
                                            data-overview-panel="1">
                                        <span class="wsc-provider-overview-item__icon" aria-hidden="true">
                                            <span class="dashicons dashicons-admin-home"></span>
                                        </span>
                                        <span class="name"><?php echo esc_html__( 'Overview', 'ventraconnect-social-login' ); ?></span>
                                    </button>
                                    <ul class="wsc-providers-list">
                                        <div class="provider-title-helper"><h4><?php echo esc_html__( 'Login methods & order', 'ventraconnect-social-login' ); ?></h4>
                                        <p><?php echo esc_html__( 'Toggle Social providers, Passkey, Magic Link, and Email OTP on/off and drag to reorder. The order here controls how buttons appear on frontend.', 'ventraconnect-social-login' ); ?></p></div>
                                        <?php foreach ( $all_providers as $p ) :
                                            $slug       = $p['slug'];
                                            $label      = $p['label'];
                                            $icon       = VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/' . $slug . '.svg';
                                            $has_icon   = file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $slug . '.svg' );
                                            $is_active  = in_array( $slug, $enabled_providers, true );
                                            $is_selected = ( $sel === $slug );

                                            $pro_is_active         = function_exists( '\vcsl_is_pro_active' ) ? vcsl_is_pro_active() : false;
                                            // In Free, Magic Link / OTP are first-class providers; Pro gates integration contexts only.
                                            $effective_enabled     = $is_active;
                                            ?>
                                            <li data-provider="<?php echo esc_attr( $slug ); ?>">
                                                <span class="wsc-drag-handle"
                                                      tabindex="0"
                                                      role="button"
                                                      aria-label="<?php echo esc_attr__( 'Drag to reorder', 'ventraconnect-social-login' ); ?>">
                                                    &#8942;&#8942;
                                                </span>
                                                <?php
                                                $item_classes = 'wsc-provider-item' . ( $is_selected ? ' is-active' : '' );
                                                ?>
                                                <button type="button"
                                                        class="<?php echo esc_attr( $item_classes ); ?>"
                                                        data-provider="<?php echo esc_attr( $slug ); ?>">
                                                    <?php if ( $has_icon ) : ?>
                                                        <img alt="" src="<?php echo esc_url( $icon ); ?>" width="30" height="30">
                                                    <?php endif; ?>
                                                    <span class="name"><?php echo esc_html( $label ); ?></span>
                                                    <?php
                                                    $dot_classes = 'wsc-dot ' . ( $effective_enabled ? 'active' : 'inactive' );
                                                    ?>
                                                    <span class="<?php echo esc_attr( $dot_classes ); ?>" aria-hidden="true"></span>
                                                </button>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </aside>

                                <section class="wsc-provider-panes" aria-live="polite">
                                    <div class="wsc-provider-pane wsc-provider-overview-pane<?php echo $overview_active ? ' is-active' : ''; ?>"
                                         data-overview-panel="1">
                                        <?php $this->fields_renderer->renderOverviewTab( $state ); ?>
                                    </div>
                                    <?php foreach ( $all_providers as $p ) :
                                        $slug        = $p['slug'];
                                        $label       = $p['label'];
                                        $icon        = VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/' . $slug . '.svg';
                                        $has_icon    = file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $slug . '.svg' );
                                        $pane_active = ( ! $overview_active && $sel === $slug );
                                        $pane_classes = 'wsc-provider-pane' . ( $pane_active ? ' is-active' : '' );
                                        ?>
                                        <div class="<?php echo esc_attr( $pane_classes ); ?>"
                                             data-provider="<?php echo esc_attr( $slug ); ?>">
                                            <div class="wsc-provider-head">
                                                <?php if ( $has_icon ) : ?>
                                                    <img alt="" src="<?php echo esc_url( $icon ); ?>" width="48" height="48">
                                                <?php endif; ?>
                                                <h3><?php echo esc_html( $label ); ?></h3>
                                            </div>

                                            <?php $this->render_provider_embed( $slug ); ?>
                                        </div>
                    <?php endforeach; ?>
                                </section>
                            </div>
                            </div>
                        </form>
                        <?php
                        break;

                    case 'emails':
                        \VentraConnect\SocialLogin\Admin\VCS_Admin::render_emails_tab();
                        break;

                    case 'comments':
                        \VentraConnect\SocialLogin\Admin\VCS_Admin::render_comments_tab();
                        break;

                    case 'woocommerce':
                        \VentraConnect\SocialLogin\Admin\VCS_Admin::render_wc_tab();
                        break;

                    case 'community-memberships':
                        ?>
                        <div class="vcsl-card">
                            <?php
                            $is_pro = function_exists( 'ventraconnect_sl_is_pro_active' ) && ventraconnect_sl_is_pro_active();
                            if ( $is_pro ) {
                                do_action( 'ventraconnect_sl_render_memberships_tab' );
                            } else {
                                $renderer_memberships = new \VentraConnect\SocialLogin\Admin\Settings\FieldsRenderer();
                                $state_memberships    = array(
                                    'preview_only' => true,
                                );
                                \VentraConnect\SocialLogin\Admin\Pro_Preview::render(
                                    array(
                                        'title'       => __( 'Unlock Community & Membership Integrations', 'ventraconnect-social-login' ),
                                        'description' => __(
                                            'Connect social login to your membership and community plugins for smoother registrations, gated content, and account linking.',
                                            'ventraconnect-social-login'
                                        ),
                                        'upgrade_url' => 'https://wpventra.com/pricing/',
                                    ),
                                    function () use ( $renderer_memberships, $state_memberships ) {
                                        $renderer_memberships->renderMembershipsTab( $state_memberships );
                                    }
                                );
                            }
                            ?>
                        </div>
                        <?php
                        break;

                    case 'courses-lms':
                        ?>
                        <div class="vcsl-card">
                            <?php
                            $is_pro_lms = function_exists( 'ventraconnect_sl_is_pro_active' ) && ventraconnect_sl_is_pro_active();
                            if ( $is_pro_lms ) {
                                do_action( 'ventraconnect_sl_render_lms_tab' );
                            } else {
                                $renderer_lms = new \VentraConnect\SocialLogin\Admin\Settings\FieldsRenderer();
                                $state_lms    = array(
                                    'preview_only' => true,
                                );
                                \VentraConnect\SocialLogin\Admin\Pro_Preview::render(
                                    array(
                                        'title'       => __( 'Unlock Courses & LMS Integrations', 'ventraconnect-social-login' ),
                                        'description' => __(
                                            'Add social login to your course and LMS platforms for frictionless enrollments and student access.',
                                            'ventraconnect-social-login'
                                        ),
                                        'upgrade_url' => 'https://wpventra.com/pricing/',
                                    ),
                                    function () use ( $renderer_lms, $state_lms ) {
                                        $renderer_lms->renderLmsTab( $state_lms );
                                    }
                                );
                            }
                            ?>
                        </div>
                        <?php
                        break;

                    case 'logs':
                        ?>
                        <div class="vcsl-card">
                            <?php
                            if ( ! \VentraConnect\SocialLogin\Pro_Gates::is_pro() ) {
                                $renderer_logs = new \VentraConnect\SocialLogin\Admin\Settings\FieldsRenderer();

                                \VentraConnect\SocialLogin\Admin\Pro_Preview::render(
                                    array(
                                        'title'       => __( 'Analytics (Pro)', 'ventraconnect-social-login' ),
                                        'description' => __(
                                            'View detailed login analytics, top providers, and a debug log of recent events with VentraConnect Pro.',
                                            'ventraconnect-social-login'
                                        ),
                                        'upgrade_url' => 'https://wpventra.com/pricing/',
                                    ),
                                    function () use ( $renderer_logs ) {
                                        $renderer_logs->renderAnalyticsPreview();
                                    }
                                );
                            } else {
                                // Pro: use the real analytics/logs renderer from class-logs.php.
                                do_action( 'ventraconnect_sl_render_tab_logs', (array) $settings );
                            }
                            ?>
                        </div>
                        <?php
                        break;

                    case 'diagnostics':
                        ?>
                        <div class="vcsl-card">
                            <?php
                            $this->fields_renderer->renderDiagnosticsTab( $state );
                            ?>
                        </div>
                        <?php
                        break;

                    case 'about':
                    default:
                        ?>
<div class="vcsl-card">
    <h2><?php esc_html_e( 'Support & Resources', 'ventraconnect-social-login' ); ?></h2>

    <p>
        <?php
        esc_html_e(
            'VentraConnect Social Login helps your users sign in with their favorite providers, Magic Link, and OTP — with deep integrations for WooCommerce, LMS, and membership plugins.',
            'ventraconnect-social-login'
        );
        ?>
    </p>

    <p class="description">
        <?php
        // Version + edition meta.
        $version      = defined( 'VENTRACONNECT_SL_VERSION' ) ? VENTRACONNECT_SL_VERSION : '';
        $pro_version  = defined( 'VENTRACONNECT_SL_PRO_VERSION' ) ? VENTRACONNECT_SL_PRO_VERSION : '';
        $is_pro_active = function_exists( 'vcsl_is_pro_active' ) && vcsl_is_pro_active();

        if ( ! $is_pro_active && $version ) {
            printf(
                /* translators: %s: plugin version number. */
                esc_html__( 'Version %s', 'ventraconnect-social-login' ),
                esc_html( $version )
            );
        } elseif ( $is_pro_active && $version && $pro_version ) {
            printf(
                /* translators: 1: free plugin version number, 2: pro plugin version number. */
                esc_html__( 'Free %1$s — Pro %2$s active', 'ventraconnect-social-login' ),
                esc_html( $version ),
                esc_html( $pro_version )
            );
        } elseif ( $is_pro_active && $version ) {
            printf(
                /* translators: %s: free plugin version number. */
                esc_html__( 'Free %s — Pro active', 'ventraconnect-social-login' ),
                esc_html( $version )
            );
        }
        ?>
    </p>

    <?php
    // Docs + support URLs (free vs pro).
    $docs_url = 'https://wpventra.com/docs'; // Adjust to your actual docs URL when ready.

    if ( $is_pro_active ) {
        $support_url = 'https://wpventra.com/support/';
    } else {
        $support_url = 'https://wordpress.org/support/plugin/ventraconnect-social-login/';
    }
    ?>

    <p>
        <a href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary">
            <?php esc_html_e( 'View Documentation', 'ventraconnect-social-login' ); ?>
        </a>
        <a href="<?php echo esc_url( $support_url ); ?>" target="_blank" rel="noopener noreferrer" class="button">
            <?php esc_html_e( 'Get Support', 'ventraconnect-social-login' ); ?>
        </a>
    </p>

    <p class="description">
        <?php
        esc_html_e(
            'If something is not working, open the Diagnostics & Tools tab and include a copy of the report when you contact support.',
            'ventraconnect-social-login'
        );
        ?>
    </p>
</div>

                        <?php
                        break;
                }
                ?>
            </main>
        </div>

        <?php
        // Expose Pro state for any JS needing to know Pro status (unrelated to tab state).
        echo '<script>window.wscProActive=' . wp_json_encode( \VentraConnect\SocialLogin\Pro_Gates::is_pro() ? true : false ) . ';</script>';
        ?>
    </div>
    <?php
}
    /**
     * Render deep configuration page for a single provider.
     */
    private function render_provider_page( string $slug ) {
    $settings = (array) $this->persistence->getSettings();
    $creds    = (array) $this->persistence->getProviderCreds();
        $meta   = \VentraConnect\SocialLogin\Admin\Settings\ProviderMeta::build( $slug, $this->persistence );
        $label  = isset( $meta['label'] ) ? (string) $meta['label'] : ucfirst( $slug );
        $redirect = isset( $meta['redirect_uri'] ) ? (string) $meta['redirect_uri'] : '';
        $icon  = isset( $meta['icon_url'] ) ? (string) $meta['icon_url'] : '';
        $icon_exists = $icon && file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $slug . '.svg' );
        $requires_secret = ! ( isset( $meta['is_pro_only'] ) && $meta['is_pro_only'] );
        $has_id          = ! empty( $meta['client_id'] );
        $has_secret      = ! $requires_secret || ! empty( $meta['client_secret'] );
        $is_configured   = isset( $meta['is_connected'] ) ? (bool) $meta['is_connected'] : ( $has_id && $has_secret );
        // DEBUG: Output the current $slug value for troubleshooting
        ?>
        <div class="wrap wsc-admin">
            <h1 class="screen-reader-text"><?php echo esc_html__( 'VentraConnect Social Login', 'ventraconnect-social-login' ); ?></h1>
            <?php
            $tmp_notice = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getTransient( 'ventraconnect_sl_admin_notice' );
            if ( is_array( $tmp_notice ) && ! empty( $tmp_notice['message'] ) ) {
                $cls = ( 'error' === ( $tmp_notice['type'] ?? '' ) ) ? 'notice notice-error' : 'notice notice-success';
                echo '<div class="' . esc_attr( $cls ) . ' is-dismissible"><p>' . esc_html( $tmp_notice['message'] ) . '</p></div>';
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteTransient( 'ventraconnect_sl_admin_notice' );
            }
            ?>
            <div class="wsc-hero">
                <div class="wsc-title" role="heading" aria-level="1">
                    <?php
                    // Optional logo (provider page hero) - placed inside title so it aligns with text
                    $vc_logo_url = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/logo/VentraConnectLogo.svg' : '';
                    $vc_logo_exists = $vc_logo_url && file_exists( VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/logo/VentraConnectLogo.svg' );
                    if ( $vc_logo_exists ) :
                    ?>
                        <img class="wsc-hero-logo" src="<?php echo esc_url( $vc_logo_url ); ?>" alt="" width="40" height="40" />
                    <?php endif; ?>
                    <span>
                        <?php if ( $icon_exists ) : ?>
                            <img class="wsc-provider-icon" src="<?php echo esc_url( $icon ); ?>" width="24" height="24" alt="" />
                        <?php endif; ?>
                        <?php echo esc_html( $label ); ?>
                    </span>
                    <?php $badge_classes = 'wsc-badge ' . ( $is_configured ? 'good' : 'warn' ); ?>
                    <span class="<?php echo esc_attr( $badge_classes ); ?>"><?php echo esc_html( $is_configured ? __( 'Configured', 'ventraconnect-social-login' ) : __( 'Not configured', 'ventraconnect-social-login' ) ); ?></span>
                </div>
                <div class="wsc-cta">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ventraconnect-sl-settings#wsc-tab-providers' ) ); ?>" class="button button-primary wsc-back-btn">&larr; <?php echo esc_html__( 'Back to Providers', 'ventraconnect-social-login' ); ?></a>
                </div>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields( 'ventraconnect_sl_settings_group' ); ?>
                <?php wp_nonce_field( 'ventraconnect_sl_settings_nonce', 'ventraconnect_sl_settings_nonce' ); ?>
                
                <?php
                // Assemble provider data for basics renderer.
                $themes    = [ $slug => \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderTheme( $slug ) ];
                $overrides = [ $slug => \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderThemeOverride( $slug ) ];
                $texts     = [ $slug => \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderText( $slug ) ];

                // Provider-specific Getting Started inner HTML (no outer wrappers).
                ob_start();
                // Compute site domain once, used by multiple providers
                $home_url = home_url();
                $parts = wp_parse_url( $home_url );
                $domain = '';
                if ( ! empty( $parts['scheme'] ) && ! empty( $parts['host'] ) ) {
                    $domain = $parts['scheme'] . '://' . $parts['host'];
                    if ( ! empty( $parts['port'] ) ) { $domain .= ':' . $parts['port']; }
                } else {
                    $domain = $home_url;
                }
                if ( 'amazon' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/amazon-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } elseif ( 'google' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/google-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } elseif ( 'facebook' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/facebook-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } elseif ( 'slack' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/slack-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } elseif ( 'github' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/github-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } elseif ( 'microsoft' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/microsoft-getting-started.php',
                        [ 'redirect' => $redirect ]
                    ) );
                } elseif ( 'linkedin' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/linkedin-getting-started.php',
                        [ 'redirect' => $redirect ]
                    ) );
                } elseif ( 'yahoo' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/yahoo-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } elseif ( 'twitch' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/twitch-getting-started.php',
                        [ 'redirect' => $redirect ]
                    ) );
                } elseif ( 'reddit' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/reddit-getting-started.php',
                        [ 'redirect' => $redirect ]
                    ) );
                } elseif ( 'tiktok' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/tiktok-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } elseif ( 'twitter' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/twitter-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } elseif ( 'wordpress' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/wordpress-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } elseif ( 'discord' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/discord-getting-started.php',
                        [ 'redirect' => $redirect ]
                    ) );
                } elseif ( 'spotify' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/spotify-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } elseif ( 'line' === $slug ) {
                    echo wp_kses_post( \VentraConnect\SocialLogin\Admin\View::render(
                        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/line-getting-started.php',
                        [ 'redirect' => $redirect, 'domain' => $domain ]
                    ) );
                } else {
                    ?>
                    <ol class="wsc-steps">
                        <li><?php echo esc_html__( 'Create an app on the provider dashboard.', 'ventraconnect-social-login' ); ?></li>
                        <li><?php echo esc_html__( 'Add the Redirect URI below to allowed/callback URLs.', 'ventraconnect-social-login' ); ?></li>
                        <li><?php echo esc_html__( 'Copy Client ID and Secret into the Credentials section.', 'ventraconnect-social-login' ); ?></li>
                    </ol>
                    <p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
                    <div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
                    <?php
                }
                $getting_started_html = (string) ob_get_clean();

                // Labels for credentials fields (some providers use App vs Client).
                $id_label  = ( 'facebook' === $slug ) ? esc_html__( 'App ID', 'ventraconnect-social-login' ) : esc_html__( 'Client ID', 'ventraconnect-social-login' );
                $sec_label = ( 'facebook' === $slug ) ? esc_html__( 'App Secret', 'ventraconnect-social-login' ) : esc_html__( 'Client Secret', 'ventraconnect-social-login' );

                $provider_data = [
                    'label'                => $label,
                    'redirect'             => $redirect,
                    'id_label'             => $id_label,
                    'secret_label'         => $sec_label,
                    'getting_started_html' => $getting_started_html,
                ];

                // Delegate rendering of basics to the renderer.
                $this->fields_renderer->renderProviderConfigBasics(
                    $slug,
                    $provider_data,
                    $creds,
                    $themes,
                    $overrides,
                    $texts,
                    function() { return \VentraConnect\SocialLogin\Pro_Gates::is_pro(); }
                );
                ?>
                <?php
                // Compute Verification/Test and Diagnostics URLs.
                $test_url = '';
                $diag_url = '';
                $prov = \VentraConnect\SocialLogin\OAuth::provider( $slug );
                if ( $prov ) {
                    $return_url = admin_url( 'admin.php?page=ventraconnect-sl-settings&view=provider&provider=' . $slug );
                    $state = \VentraConnect\SocialLogin\OAuth::generate_state( $slug, array( 'verify' => 1, 'return' => $return_url ) );
                    $test_url = $prov->get_auth_url( $state, $redirect );
                }
                $diag_nonce = wp_create_nonce( 'ventraconnect_sl_diag_' . $slug );
                $diag_url = esc_url( admin_url( 'admin-ajax.php?action=ventraconnect_sl_run_diagnostics&json=1&provider=' . rawurlencode( $slug ) . '&_wpnonce=' . $diag_nonce ) );

                $computed = [
                    'test_url' => $test_url,
                    'diag_url' => $diag_url,
                ];

                // Delegate advanced/verification sections to renderer.
                $this->fields_renderer->renderProviderConfigAdvanced(
                    $slug,
                    $provider_data,
                    $creds,
                    $themes,
                    $overrides,
                    $texts,
                    $computed,
                    function() { return \VentraConnect\SocialLogin\Pro_Gates::is_pro(); }
                );
                ?>
                
            </form>
        </div>
        <?php
    }
}
