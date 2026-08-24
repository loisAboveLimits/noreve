<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Render login buttons.
 */
class Buttons {
    /** @var array */
    private $settings;

    public function __construct( $settings = null ) {
        $this->settings = $settings ?? \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        // Allow essential SVG presentation properties in inline style during KSES sanitization
        add_filter( 'safe_style_css', function( $props ) {
            $extras = [ 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'fill-opacity', 'stroke-opacity', 'stop-color', 'stop-opacity' ];
            foreach ( $extras as $prop ) {
                if ( ! in_array( $prop, $props, true ) ) { $props[] = $prop; }
            }
            return $props;
        } );
        // Frontend asset loader for all social button placements (core + integrations).
        add_action(
            'wp_enqueue_scripts',
            function() {
                self::enqueue_frontend_assets();
            }
        );
        // Ensure styles also load on wp-login.php.
        add_action(
            'login_enqueue_scripts',
            function() {
                self::enqueue_frontend_assets();
            }
        );
        // Ensure shortcode is available on front/back end
        if ( class_exists( __NAMESPACE__ . '\\Shortcodes' ) ) {
            \call_user_func( [ __NAMESPACE__ . '\\Shortcodes', 'init' ] );
        }
    }

    /**
     * Enqueue frontend styles and scripts for social buttons.
     *
     * This is reused by Pro integrations (LearnPress, LifterLMS, MemberPress, Woo, etc.)
     * whenever their forms render and need the same CSS/JS bundle as core login buttons.
     */
    public static function enqueue_frontend_assets(): void {
        wp_enqueue_style( 'wsc-frontend', VENTRACONNECT_SL_PLUGIN_URL . 'assets/css/frontend.css', [], VENTRACONNECT_SL_VERSION );
        wp_enqueue_style( 'vcs-buttons', VENTRACONNECT_SL_PLUGIN_URL . 'assets/css/vcs-buttons.css', [], VENTRACONNECT_SL_VERSION );
        wp_enqueue_script( 'vcs-auth', VENTRACONNECT_SL_PLUGIN_URL . 'assets/js/auth.js', [ 'jquery' ], VENTRACONNECT_SL_VERSION, true );
        // Popup auth script (optional)
        $settings        = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $use_popup_oauth = ! empty( $settings['use_popup_oauth'] );
        if ( $use_popup_oauth ) {
            wp_enqueue_script( 'vcs-popup-auth', VENTRACONNECT_SL_PLUGIN_URL . 'assets/js/popup-auth.js', [], VENTRACONNECT_SL_VERSION, true );
        }
        wp_localize_script(
            'vcs-auth',
            'VCS_AUTH',
            [
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'ventraconnect_sl_auth' ),
                'nonce_legacy' => wp_create_nonce( 'ventraconnect_sl_auth' ),
                'popup'        => (bool) $use_popup_oauth,
                'use_popup_oauth' => (bool) $use_popup_oauth,
                // JS runtime keys (canonical only; legacy aliases point to the same names).
                'login_channel'            => 'ventraconnect_sl_login_channel',
                'login_channel_legacy'     => 'ventraconnect_sl_login_channel',
                'magic_storage_key'        => 'ventraconnect_sl_magic_state',
                'magic_storage_key_legacy' => 'ventraconnect_sl_magic_state',
                'magic_channel'            => 'ventraconnect_sl_magic_channel',
                'magic_channel_legacy'     => 'ventraconnect_sl_magic_channel',
                'otp_storage_key'          => 'ventraconnect_sl_otp_state',
                'otp_storage_key_legacy'   => 'ventraconnect_sl_otp_state',
                'otp_channel'              => 'ventraconnect_sl_otp_channel',
                'otp_channel_legacy'       => 'ventraconnect_sl_otp_channel',
                'logged_in_query'          => 'ventraconnect_sl_logged_in',
                'logged_in_query_legacy'   => 'ventraconnect_sl_logged_in',
            ]
        );
    }

    /**
     * Resolve SVG icon source for a provider/style/theme combo.
     */
    public static function resolve_icon_source( $provider, $style = 'wide', $theme = 'light' ) {
        $style = in_array( $style, [ 'compact', 'wide' ], true ) ? $style : 'wide';
        $theme = in_array( $theme, [ 'light', 'dark', 'minimal' ], true ) ? $theme : 'light';
        $icon_dir = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ? VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' : '';
        $icon_url_base = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/' : '';
        $resolved = [
            'filename' => '',
            'path'     => '',
            'url'      => '',
            'svg'      => '',
        ];
        if ( empty( $icon_dir ) ) {
            return apply_filters( 'ventraconnect_sl_icon_source', $resolved, $provider, $style, $theme );
        }
        $candidates = [
            $provider . '--' . $style . '--' . $theme . '.svg',
            $provider . '--' . $style . '.svg',
            $provider . '.svg',
        ];
        foreach ( $candidates as $filename ) {
            $path = $icon_dir . $filename;
            if ( ! file_exists( $path ) ) { continue; }
            $svg = @file_get_contents( $path );
            if ( is_string( $svg ) ) {
                if ( preg_match( '#<svg[\s\S]*?</svg>#i', $svg, $matches ) ) {
                    $svg = $matches[0];
                }
                $svg = trim( $svg );
            }
            $resolved['filename'] = $filename;
            $resolved['path']     = $path;
            $resolved['url']      = $icon_url_base ? $icon_url_base . $filename : '';
            $resolved['svg']      = is_string( $svg ) ? $svg : '';
            break;
        }
        return apply_filters( 'ventraconnect_sl_icon_source', $resolved, $provider, $style, $theme );
    }

    /**
     * Output buttons HTML for context (login, register, checkout, etc.).
     */
    public function render( $context = 'login' ) {
        if ( is_user_logged_in() ) {
            return;
        }
        if ( 'wp_comments' === $context && empty( $this->settings['comments_enabled'] ) ) { return; }
    // Context-specific enablement checks. Only bail early for the
    // exact context when that particular setting is disabled.
    if ( 'wp_login' === $context && empty( $this->settings['wp_login_enabled'] ) ) { return; }
    if ( 'wp_register' === $context && empty( $this->settings['wp_register_enabled'] ) ) { return; }
        $context = is_string( $context ) ? strtolower( trim( $context ) ) : 'login';

        $pro_is_active = false;
        if ( function_exists( '\vcsl_is_pro_active' ) ) {
            $pro_is_active = (bool) \vcsl_is_pro_active();
        } elseif ( defined( 'VCS_PRO_ACTIVE' ) && VCS_PRO_ACTIVE ) {
            $pro_is_active = true;
        }

        $block_passwordless = ! $pro_is_active
            && \VentraConnect\SocialLogin\Helpers\is_pro_integration_context( $context );

        // Only show providers explicitly marked Active (via toggle) in settings.
        $enabled = array_values( array_filter( (array) ( $this->settings['providers'] ?? [] ) ) );

        /**
         * Let add-ons register extra authentication methods for the shared renderer.
         *
         * This does not change core output on its own. Add-ons can use the returned
         * method metadata to signal that they intend to render additional UI through
         * the companion action later in this method.
         *
         * @param array<string,mixed> $extra_methods Extra method definitions.
         * @param string              $context       Render context such as wp_login or shortcode.
         * @param array               $settings      VentraConnect settings snapshot.
         */
        $extra_methods = apply_filters( 'ventraconnect_sl_extra_methods', array(), $context, $this->settings );
        // Reorder according to saved sidebar order if present
        $saved_order = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderOrder();
        if ( ! empty( $saved_order ) && ! empty( $enabled ) ) {
            $pos = array_flip( $saved_order );
            usort( $enabled, function( $a, $b ) use ( $pos ) {
                $pa = isset( $pos[ $a ] ) ? $pos[ $a ] : PHP_INT_MAX;
                $pb = isset( $pos[ $b ] ) ? $pos[ $b ] : PHP_INT_MAX;
                if ( $pa === $pb ) { return 0; }
                return ( $pa < $pb ) ? -1 : 1;
            } );
        }
        if ( empty( $enabled ) && empty( $extra_methods ) ) { return; }
        $style = in_array( $this->settings['button_style'] ?? 'wide', [ 'wide','compact' ], true ) ? $this->settings['button_style'] : 'wide';
        $is_wp_login = ( 'wp_login' === $context );
        $divider_label = __( 'OR', 'ventraconnect-social-login' );
        $wrapper_bits = [
            'class="wsc-buttons wsc-style-' . esc_attr( $style ) . '"',
            'data-wsc-context="' . esc_attr( $context ) . '"',
        ];
        if ( $is_wp_login ) {
            $wrapper_bits[] = 'data-vcs-login-buttons="1"';
            $wrapper_bits[] = 'data-vcs-divider-label="' . esc_attr( $divider_label ) . '"';
        }
        $html = '<div ' . implode( ' ', $wrapper_bits ) . '>';
        $token_slugs = [ 'magic_link', 'otp_email' ];
        $full_width_passwordless_slugs = array_merge( [ 'passkey' ], $token_slugs );
        $special_slugs = array_merge( $token_slugs, [ 'passkey' ] );
        $ordered = $enabled;
        $ordered = $this->add_passkey_to_render_queue( $ordered, $saved_order, $extra_methods );
        $has_buttons = false;
        // Prepare an allowlist just for sanitizing inline SVG content.
        $svg_allowed = wp_kses_allowed_html( 'post' );
        $svg_allowed['svg'] = [
            'class' => true,
            'xmlns' => true,
            'xmlns:xlink' => true,
            // Note: KSES lower-cases attribute names; allow 'viewbox'.
            'viewbox' => true,
            'width' => true,
            'height' => true,
            'fill' => true,
            'style' => true,
            'role' => true,
            'aria-hidden' => true,
            'focusable' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'preserveaspectratio' => true,
            'id' => true,
        ];
        $svg_allowed['path'] = [
            'd' => true,
            'fill' => true,
            'style' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'clip-rule' => true,
            'fill-rule' => true,
            'transform' => true,
            'id' => true,
        ];
        $svg_allowed['g'] = [
            'class' => true,
            'fill' => true,
            'style' => true,
            'stroke' => true,
            'stroke-width' => true,
            'stroke-linecap' => true,
            'stroke-linejoin' => true,
            'clip-path' => true,
            'transform' => true,
            'id' => true,
        ];
    $svg_allowed['title'] = [];
    $svg_allowed['use'] = [ 'xlink:href' => true, 'href' => true, 'fill' => true ];
    $svg_allowed['defs'] = [];
    // KSES lowercases element names; use lowercase keys
    $svg_allowed['lineargradient'] = [ 'id' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'gradientunits' => true ];
    $svg_allowed['stop'] = [ 'offset' => true, 'stop-color' => true, 'stop-opacity' => true ];
    $svg_allowed['clippath'] = [ 'id' => true ];
    $svg_allowed['mask'] = [ 'id' => true, 'maskunits' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true ];
    $svg_allowed['symbol'] = [ 'id' => true, 'viewbox' => true, 'preserveaspectratio' => true ];
    $svg_allowed['rect'] = [ 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'fill-opacity' => true, 'stroke-opacity' => true ];
    $svg_allowed['circle'] = [ 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'fill-opacity' => true, 'stroke-opacity' => true ];
    $svg_allowed['ellipse'] = [ 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'fill-opacity' => true, 'stroke-opacity' => true ];
    $svg_allowed['polygon'] = [ 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'fill-opacity' => true, 'stroke-opacity' => true ];
    $svg_allowed['polyline'] = [ 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'fill-opacity' => true, 'stroke-opacity' => true ];
        $svg_allowed['line'] = [ 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-opacity' => true ];
        $compact_divider_inserted = false;
        $last_rendered_compact_variant = null;
        foreach ( $ordered as $provider ) {
            // In Free + Pro-only context, hide Magic Link + OTP providers
            if (
                $block_passwordless
                && in_array( $provider, array( 'magic_link', 'otp_email' ), true )
            ) {
                continue;
            }
            $is_token_provider   = in_array( $provider, $token_slugs, true );
            $is_passkey_provider = ( 'passkey' === $provider );
            $is_special_provider = in_array( $provider, $special_slugs, true );
            $prov                = $is_special_provider ? null : OAuth::provider( $provider );
            if ( ! $is_special_provider && ! $prov ) {
                continue;
            }
            $creds = [];
            if ( ! $is_special_provider ) {
                $creds_all = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderCreds();
                if ( empty( $creds_all ) && ! empty( $this->settings['provider_creds'] ) ) {
                    $creds_all = (array) $this->settings['provider_creds'];
                }
                $creds = (array) ( $creds_all[ $provider ] ?? [] );
                // Skip if no creds present for OAuth providers
                if ( empty( $creds ) || ! array_filter( $creds ) ) {
                    continue;
                }
            }
            // Determine label and theme with per-provider options
            if ( 'magic_link' === $provider ) {
                $provider_name = __( 'Magic Link', 'ventraconnect-social-login' );
            } elseif ( 'otp_email' === $provider ) {
                $provider_name = __( 'OTP (Email)', 'ventraconnect-social-login' );
            } elseif ( $is_passkey_provider ) {
                $provider_name = __( 'Passkey', 'ventraconnect-social-login' );
            } else {
                $provider_name = ucfirst( $provider );
            }
            if ( $is_passkey_provider && class_exists( 'VentraConnect_SL_Passkeys_Public_Frontend', false ) && method_exists( 'VentraConnect_SL_Passkeys_Public_Frontend', 'get_shared_button_default_label' ) ) {
                $label_default = \VentraConnect_SL_Passkeys_Public_Frontend::get_shared_button_default_label( $context );
            } else {
                $label_default = sprintf(
                    /* translators: 1: Provider label. */
                    __( 'Continue with %1$s', 'ventraconnect-social-login' ),
                    $provider_name
                );
            }
            $text_opt  = 'ventraconnect_sl_provider_' . $provider . '_text';
            $theme_opt = 'ventraconnect_sl_provider_' . $provider . '_theme';
            $override_opt = 'ventraconnect_sl_provider_' . $provider . '_theme_override';
            $pro_active = ( defined( 'VCS_PRO_ACTIVE' ) && VCS_PRO_ACTIVE === true );
            $label = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderText( $provider, $label_default );
            if ( ! $pro_active ) { $label = $label_default; }
            $auth_url = '#';
            $or_pos   = 'none';
            if ( ! $is_special_provider ) {
                $state_extra = apply_filters(
                    'ventraconnect_sl_oauth_state_extra',
                    array(),
                    $provider,
                    $context
                );
                if ( ! is_array( $state_extra ) ) { $state_extra = []; }
                if ( empty( $state_extra['context'] ) ) {
                    $state_extra['context'] = $context;
                }
                if ( empty( $state_extra['ventraconnect_sl_ctx'] ) ) {
                    $state_extra['ventraconnect_sl_ctx'] = $context;
                }
                if ( empty( $state_extra['redirect_to'] ) ) {
                    $state_extra['redirect_to'] = $this->current_url();
                }
                // If admin setting opts into popup flow, mark the state so callback can render popup bridge.
                $settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
                if ( ! empty( $settings['use_popup_oauth'] ) ) {
                    $state_extra['ventraconnect_sl_popup'] = 1;
                }
                $state = OAuth::generate_state( $provider, $state_extra );
                $redirect = OAuth::redirect_uri( $provider );
                $auth_url = $prov->get_auth_url( $state, $redirect );
            } else {
                $provider_conf = (array) ( $this->settings[ $provider ] ?? [] );
                $or_pos        = isset( $provider_conf['or_separator'] ) ? (string) $provider_conf['or_separator'] : 'none';
                if ( ! in_array( $or_pos, [ 'none', 'above', 'below', 'both' ], true ) ) {
                    $or_pos = 'none';
                }
            }
            // New semantic markup with tokens
            $global_theme = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getGlobalTheme( 'light' );
            if ( ! in_array( $global_theme, [ 'light','dark','minimal' ], true ) ) {
                $global_theme = 'light';
            }
            $theme = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderTheme( $provider, '' );
            $theme = in_array( $theme, [ 'light','dark','minimal' ], true ) ? $theme : '';
            $override_enabled = $pro_active ? (bool) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderThemeOverride( $provider, 0 ) : false;
            if ( ! $pro_active || ! $override_enabled ) {
                $theme = $global_theme;
            } elseif ( '' === $theme ) {
                $theme = $global_theme;
            }
            if ( ! in_array( $theme, [ 'light','dark','minimal' ], true ) ) {
                $theme = 'light';
            }
            $force_wide_passwordless = ( 'compact' === $style && in_array( $provider, $full_width_passwordless_slugs, true ) );
            $style_variant = $force_wide_passwordless ? 'wide' : ( ( 'compact' === $style ) ? 'compact' : 'wide' );
            $current_renders_compact = ( 'compact' === $style_variant );
            if ( $is_passkey_provider && ! empty( $extra_methods ) ) {
                $ordered_extra_method_key = $this->get_passkey_visual_method_key( $extra_methods );

                if ( '' !== $ordered_extra_method_key ) {
                    $ordered_extra_method_markup = (string) apply_filters(
                        'ventraconnect_sl_render_ordered_extra_method_markup',
                        '',
                        $ordered_extra_method_key,
                        $context,
                        $this->settings,
                        $enabled,
                        $extra_methods
                    );

                    if ( '' !== trim( $ordered_extra_method_markup ) ) {
                        if (
                            'compact' === $style
                            && $has_buttons
                            && ! $compact_divider_inserted
                            && null !== $last_rendered_compact_variant
                            && $last_rendered_compact_variant !== $current_renders_compact
                        ) {
                            $html .= '<div class="vcs-compact-divider" data-vcs-divider="compact" role="presentation"><span>' . esc_html( $divider_label ) . '</span></div>';
                            $compact_divider_inserted = true;
                        }
                        $passkey_or_pos = $or_pos;
                        if ( $is_wp_login && ! $has_buttons ) {
                            if ( 'above' === $passkey_or_pos ) {
                                $passkey_or_pos = 'none';
                            } elseif ( 'both' === $passkey_or_pos ) {
                                $passkey_or_pos = 'below';
                            }
                        }
                        if ( in_array( $passkey_or_pos, array( 'above', 'both' ), true ) ) {
                            $html .= $this->render_or_separator( $divider_label );
                        }
                        $html .= $ordered_extra_method_markup;
                        if ( in_array( $passkey_or_pos, array( 'below', 'both' ), true ) ) {
                            $html .= $this->render_or_separator( $divider_label );
                        }
                        unset( $extra_methods[ $ordered_extra_method_key ] );
                        $has_buttons = true;
                        $last_rendered_compact_variant = $current_renders_compact;
                        continue;
                    }
                }
            }
            if (
                $is_passkey_provider
                && (
                    ! class_exists( 'VentraConnect_SL_Passkeys_Public_Frontend', false )
                    || ! method_exists( 'VentraConnect_SL_Passkeys_Public_Frontend', 'should_render_in_context' )
                    || ! \VentraConnect_SL_Passkeys_Public_Frontend::should_render_in_context( $context, $this->settings )
                )
            ) {
                continue;
            }
            if (
                'compact' === $style
                && $has_buttons
                && ! $compact_divider_inserted
                && null !== $last_rendered_compact_variant
                && $last_rendered_compact_variant !== $current_renders_compact
            ) {
                $html .= '<div class="vcs-compact-divider" data-vcs-divider="compact" role="presentation"><span>' . esc_html( $divider_label ) . '</span></div>';
                $compact_divider_inserted = true;
            }
            if ( $is_passkey_provider ) {
                $passkey_or_pos = $or_pos;
                if ( $is_wp_login && ! $has_buttons ) {
                    if ( 'above' === $passkey_or_pos ) {
                        $passkey_or_pos = 'none';
                    } elseif ( 'both' === $passkey_or_pos ) {
                        $passkey_or_pos = 'below';
                    }
                }
                if ( in_array( $passkey_or_pos, array( 'above', 'both' ), true ) ) {
                    $html .= $this->render_or_separator( $divider_label );
                }
                if ( class_exists( 'VentraConnect_SL_Passkeys_Public_Frontend', false ) && method_exists( 'VentraConnect_SL_Passkeys_Public_Frontend', 'get_shared_button_markup' ) ) {
                    $html .= \VentraConnect_SL_Passkeys_Public_Frontend::get_shared_button_markup( $context, $style_variant, $theme, $label );
                    $has_buttons = true;
                    $last_rendered_compact_variant = $current_renders_compact;
                }
                if ( in_array( $passkey_or_pos, array( 'below', 'both' ), true ) ) {
                    $html .= $this->render_or_separator( $divider_label );
                }
                continue;
            }
            $class    = 'vcs-btn vcs-btn--' . $style_variant;
            if ( $is_token_provider ) { $class .= ' vcs-btn--' . ( 'magic_link' === $provider ? 'magic-link' : 'otp' ); }
            $aria     = ( 'compact' === $style_variant ) ? ' aria-label="' . esc_attr( $label ) . '"' : '';
            $icon_source = self::resolve_icon_source( $provider, $style_variant, $theme );
            $svg = $icon_source['svg'] ?? '';
            if ( $is_token_provider && ( 'above' === $or_pos || 'both' === $or_pos ) ) {
                $html .= $this->render_or_separator( $divider_label );
            }
            $html .= '<a class="' . esc_attr( $class ) . '" data-provider="' . esc_attr( $provider ) . '" data-theme="' . esc_attr( $theme ) . '" href="' . esc_url( $auth_url ) . '"' . $aria . '>';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $svg is loaded from fixed plugin asset paths (no user input) and restricted to a single <svg> element via regex in resolve_icon_source(), so this trusted inline SVG is safe to output unescaped.
            $html .= '<span class="vcs-btn__icon" aria-hidden="true">' . $svg . '</span>';
            $html .= '<span class="vcs-btn__label">' . esc_html( $label ) . '</span>';
            $html .= '</a>';
            if ( $is_token_provider && ( 'below' === $or_pos || 'both' === $or_pos ) ) {
                $html .= $this->render_or_separator( $divider_label );
            }
            $has_buttons = true;
            $last_rendered_compact_variant = $current_renders_compact;
        }
        $extra_methods_markup = '';

        if ( ! empty( $extra_methods ) ) {
            ob_start();

            /**
             * Render add-on authentication methods inside the shared method wrapper.
             *
             * Core VentraConnect does not output anything here. Add-ons such as future
             * authentication methods can hook in to append their own markup while reusing
             * the same placement context as Social Login, Magic Link, and Email OTP.
             *
             * @param string              $context       Render context such as wp_login or shortcode.
             * @param array               $settings      VentraConnect settings snapshot.
             * @param array<int,string>   $enabled       Enabled core provider slugs after ordering.
             * @param array<string,mixed> $extra_methods Extra method definitions from the companion filter.
             */
            do_action( 'ventraconnect_sl_render_extra_methods', $context, $this->settings, $enabled, $extra_methods );

            $extra_methods_markup = trim( (string) ob_get_clean() );
        }

        if ( '' !== $extra_methods_markup ) {
            $html       .= $extra_methods_markup;
            $has_buttons = true;
        }

        if ( ! $has_buttons ) {
            return;
        }

        $html .= '</div>';
        if ( $is_wp_login ) {
            $html = $this->render_or_separator( $divider_label ) . $html;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup built with esc_* where applicable; inline SVG sanitized above; filter returns HTML by contract.
        echo apply_filters( 'ventraconnect_sl_button_markup', $html, $enabled, $context );
    }

    private function render_or_separator( string $label ): string {
        return '<div class="vcs-login-divider vcs-or-separator" data-vcs-divider="1" role="presentation"><span>' . esc_html( $label ) . '</span></div>';
    }

    /**
     * Add Passkey to the ordered render queue when an eligible extra method needs the shared slot.
     *
     * Passkey visual order is still controlled by the single saved `passkey` provider key, even when
     * the current context needs the registration-specific `passkey_register` runtime.
     *
     * @param array<int,string>          $ordered       Current ordered core provider list.
     * @param array<int,string>          $saved_order   Saved provider order.
     * @param array<string,mixed>        $extra_methods Eligible extra methods for the current context.
     * @return array<int,string>
     */
    private function add_passkey_to_render_queue( array $ordered, array $saved_order, array $extra_methods ): array {
        if ( '' === $this->get_passkey_visual_method_key( $extra_methods ) ) {
            return $ordered;
        }

        if ( in_array( 'passkey', $ordered, true ) ) {
            return $ordered;
        }

        if ( empty( $saved_order ) || ! in_array( 'passkey', $saved_order, true ) ) {
            $ordered[] = 'passkey';

            return array_values( array_unique( $ordered ) );
        }

        $saved_positions = array_flip( $saved_order );
        $passkey_index   = isset( $saved_positions['passkey'] ) ? (int) $saved_positions['passkey'] : PHP_INT_MAX;
        $insert_at       = count( $ordered );

        foreach ( $ordered as $index => $slug ) {
            $current_position = isset( $saved_positions[ $slug ] ) ? (int) $saved_positions[ $slug ] : PHP_INT_MAX;

            if ( $current_position > $passkey_index ) {
                $insert_at = (int) $index;
                break;
            }
        }

        array_splice( $ordered, $insert_at, 0, array( 'passkey' ) );

        return array_values( array_unique( $ordered ) );
    }

    /**
     * Resolve the visual Passkey method key for the current shared-render context.
     *
     * `passkey_register` intentionally borrows the single saved `passkey` sidebar slot.
     *
     * @param array<string,mixed> $extra_methods Eligible extra methods for the current context.
     * @return string
     */
    private function get_passkey_visual_method_key( array $extra_methods ): string {
        if ( ! empty( $extra_methods['passkey'] ) ) {
            return 'passkey';
        }

        if ( ! empty( $extra_methods['passkey_register'] ) ) {
            return 'passkey_register';
        }

        return '';
    }

    private function current_url(): string {
        $host        = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );
        $request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
        if ( '' === $host || '' === $request_uri ) {
            return esc_url_raw( home_url( '/' ) );
        }
        $scheme = is_ssl() ? 'https://' : 'http://';
        return esc_url_raw( $scheme . $host . $request_uri );
    }
}

// Helper renderers for token providers (exposed for templates/hooks)
if ( ! function_exists( '\ventraconnect_sl_render_magic_link_button' ) ) {
    function ventraconnect_sl_render_magic_link_button( $context = 'login' ) {
        $settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $settings['providers'] = array_unique( array_merge( (array) ( $settings['providers'] ?? [] ), [ 'magic_link' ] ) );
        ( new \VentraConnect\SocialLogin\Buttons( $settings ) )->render( $context );
    }
}
if ( ! function_exists( '\ventraconnect_sl_render_otp_button' ) ) {
    function ventraconnect_sl_render_otp_button( $context = 'login' ) {
        $settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $settings['providers'] = array_unique( array_merge( (array) ( $settings['providers'] ?? [] ), [ 'otp_email' ] ) );
        ( new \VentraConnect\SocialLogin\Buttons( $settings ) )->render( $context );
    }
}

