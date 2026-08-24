<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class RedirectResolver {
    /**
     * Compute the final redirect URL after social login.
     *
     * @param int         $user_id  The logged-in user ID.
     * @param string|null $provider Provider slug (e.g. google, facebook).
     * @param array       $state    State data from OAuth callback.
     * @param array|null  $request  Optional $_REQUEST snapshot.
     * @return string Sanitized redirect URL.
     */
    public static function compute( $user_id, $provider = null, $state = [], $request = null ) {
        $user_id  = (int) $user_id;
        $provider = is_string( $provider ) ? sanitize_key( $provider ) : null;
        $state    = is_array( $state ) ? $state : [];
        $request  = is_array( $request ) ? $request : [];

        // Resolve flow context early to allow profile-specific handling.
        $flow_context = isset( $state['ventraconnect_sl_ctx'] )
            ? sanitize_key( (string) $state['ventraconnect_sl_ctx'] )
            : ( isset( $state['context'] ) ? sanitize_key( (string) $state['context'] ) : '' );

        if ( '' === $flow_context ) {
            $flow_context = 'global';
        }

        // Profile contexts: if a valid explicit return URL is provided in state,
        // prefer it over all other redirect rules and fallbacks.
        $profile_contexts = [ 'wc_account', 'lifterlms_account', 'wp_profile' ];
        if ( in_array( $flow_context, $profile_contexts, true ) && ! empty( $state['return'] ) ) {
            $candidate = esc_url_raw( (string) $state['return'] );
            if ( '' !== $candidate ) {
                $candidate = self::sanitize( $candidate );
                $candidate = wp_validate_redirect( $candidate, home_url( '/' ) );

                do_action(
                    'ventraconnect_sl_redirect_debug',
                    [
                        'user_id'  => $user_id,
                        'provider' => $provider,
                        'state'    => $state,
                        'redirect' => $candidate,
                    ]
                );

                return $candidate;
            }
        }

        // 1) Start with requested or same-page URL.
        $redirect = '';
        if ( ! empty( $state['redirect_to'] ) ) {
            $redirect = esc_url_raw( (string) $state['redirect_to'] );
        } elseif ( ! empty( $request['redirect_to'] ) ) {
            $redirect = esc_url_raw( (string) $request['redirect_to'] );
        }

        if ( '' === $redirect && ! empty( $state['return'] ) ) {
            $redirect = esc_url_raw( (string) $state['return'] );
        }

        // Apply "Default redirect URL" settings for generic flows.
        if ( class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
            $settings         = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
            $default_login    = isset( $settings['redirect_default_login'] ) ? trim( (string) $settings['redirect_default_login'] ) : '';
            $default_register = isset( $settings['redirect_default_register'] ) ? trim( (string) $settings['redirect_default_register'] ) : '';
            $ctx              = (string) $flow_context;

            // Generic login contexts (core + shortcodes/widgets).
            $login_contexts    = [ 'wp_login', 'global', 'shortcode', 'widget', 'login_form', 'passkey_login' ];
            // Generic registration contexts (core + shortcodes/widgets).
            $register_contexts = [ 'wp_register', 'register', 'signup', 'shortcode_register', 'widget_register', 'wp_register_form', 'verified_passkey_registration' ];

            if ( 'passkey' === (string) $provider && function_exists( '\VentraConnect\SocialLogin\Helpers\compute_passkey_redirect_for_user' ) ) {
                $passkey_conf     = isset( $settings['passkey'] ) && is_array( $settings['passkey'] ) ? $settings['passkey'] : [];
                $passkey_redirect = \VentraConnect\SocialLogin\Helpers\compute_passkey_redirect_for_user( $user_id, $passkey_conf, $request, $state );

                if ( '' !== $passkey_redirect ) {
                    $redirect = esc_url_raw( $passkey_redirect );
                }
            }

            // New: skip defaults for passwordless when override is enabled.
            $skip_defaults = false;

            if ( in_array( (string) $provider, [ 'magic_link', 'otp_email', 'passkey' ], true ) ) {
                $conf_key = ( 'magic_link' === $provider ) ? 'magic_link' : ( 'otp_email' === $provider ? 'otp_email' : 'passkey' );
                $conf     = isset( $settings[ $conf_key ] ) && is_array( $settings[ $conf_key ] ) ? $settings[ $conf_key ] : [];

                if ( ! empty( $conf['redirect_override'] ) ) {
                    $skip_defaults = true;
                }
            }

            if ( ! $skip_defaults ) {
                // Registration flows: treat core wp_register and verified native
                // passkey registration as strong overrides, but keep their
                // fallback semantics distinct. Other register contexts remain
                // fallback-only.
                if ( in_array( $ctx, $register_contexts, true ) ) {
                    if ( 'wp_register' === $ctx ) {
                        // Core WP register form: preserve existing behaviour.
                        if ( '' !== $default_register ) {
                            $redirect = esc_url_raw( $default_register );
                        } elseif ( '' !== $default_login ) {
                            $redirect = esc_url_raw( $default_login );
                        }
                    } elseif ( 'verified_passkey_registration' === $ctx ) {
                        // Verified native passkey registration:
                        // only force the Register default when it exists.
                        // Otherwise keep the requested redirect_to intact.
                        if ( '' !== $default_register ) {
                            $redirect = esc_url_raw( $default_register );
                        }
                    } else {
                        // Other register contexts: only apply defaults if no redirect was chosen yet.
                        if ( '' === $redirect ) {
                            if ( '' !== $default_register ) {
                                $redirect = esc_url_raw( $default_register );
                            } elseif ( '' !== $default_login ) {
                                $redirect = esc_url_raw( $default_login );
                            }
                        }
                    }
                } elseif ( '' !== $default_login && in_array( $ctx, $login_contexts, true ) ) {
                    // Generic login flows: default login URL always wins in these contexts.
                    $redirect = esc_url_raw( $default_login );
                }
            }
        }

        if ( '' === $redirect ) {
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                $referer = wp_get_referer();
                if ( $referer ) {
                    $redirect = esc_url_raw( $referer );
                } else {
                    $redirect = esc_url_raw( home_url( '/' ) );
                }
            } else {
                $redirect = self::current_url();
            }
        }

        // 2) Normalize & validate.
        $redirect = self::sanitize( $redirect );
        $redirect = wp_validate_redirect( $redirect, home_url( '/' ) );

        // 3) Let module filters (Woo, Comments, LMS, PMPro, Rules) override.
        // When available, prefer a flow-specific context from OAuth state.
        $redirect = apply_filters(
            'ventraconnect_sl_redirect_url',
            $redirect,
            $flow_context,
            $provider,
            $user_id
        );

        // 4) Final sanitize & validate again.
        $redirect = self::sanitize( $redirect );
        $redirect = wp_validate_redirect( $redirect, home_url( '/' ) );

        return $redirect;
    }

    /**
     * Shared redirect sanitizer for internal-only URLs.
     */
    public static function sanitize( $url ) {
        $url = is_string( $url ) ? $url : '';
        $url = trim( $url );

        // Allow admin-configured Default Redirect URLs (login/register) even when
        // they point to a different host (e.g. subdomain, different www variant).
        if ( class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
            $settings         = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
            $default_login    = isset( $settings['redirect_default_login'] ) ? trim( (string) $settings['redirect_default_login'] ) : '';
            $default_register = isset( $settings['redirect_default_register'] ) ? trim( (string) $settings['redirect_default_register'] ) : '';

            if ( ( '' !== $default_login && $url === $default_login )
                || ( '' !== $default_register && $url === $default_register )
            ) {
                // Basic normalization only; skip strict same-host enforcement.
                return wp_sanitize_redirect( $url );
            }
        }

        // For all other URLs, normalize then enforce same-host rules.
        $url  = wp_sanitize_redirect( $url );
        $url  = self::normalize_to_canonical_site_origin( $url );
        $home = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        $host = (string) wp_parse_url( (string) $url, PHP_URL_HOST );

        // Prevent external redirects: allow only same host (ignoring www) or relative URLs
        if ( $host ) {
            $h1 = ltrim( strtolower( $host ), '.' );
            $h2 = ltrim( strtolower( $home ), '.' );
            if ( 0 === strpos( $h1, 'www.' ) ) { $h1 = substr( $h1, 4 ); }
            if ( 0 === strpos( $h2, 'www.' ) ) { $h2 = substr( $h2, 4 ); }
            if ( $h1 !== $h2 ) {
                return home_url( '/' );
            }
        }

        // Block login traps
        $blocked = [ 'wp-login.php', 'action=login', 'reauth=1', 'interim-login=1' ];
        $hay = strtolower( (string) $url );
        foreach ( $blocked as $pattern ) {
            if ( false !== strpos( $hay, strtolower( (string) $pattern ) ) ) {
                return home_url( '/' );
            }
        }

        return $url;
    }

    /**
     * Normalize same-site redirects onto the canonical WordPress home origin.
     */
    private static function normalize_to_canonical_site_origin( $url ): string {
        $url = is_string( $url ) ? trim( $url ) : '';

        if ( '' === $url ) {
            return '';
        }

        $requested_parts = wp_parse_url( $url );

        if ( ! is_array( $requested_parts ) ) {
            return $url;
        }

        $requested_host = isset( $requested_parts['host'] ) ? strtolower( (string) $requested_parts['host'] ) : '';

        if ( '' === $requested_host ) {
            return $url;
        }

        $canonical_home = home_url( '/' );
        $canonical_site = site_url( '/' );
        $home_parts     = wp_parse_url( $canonical_home );
        $site_parts     = wp_parse_url( $canonical_site );
        $home_host      = isset( $home_parts['host'] ) ? strtolower( (string) $home_parts['host'] ) : '';
        $site_host      = isset( $site_parts['host'] ) ? strtolower( (string) $site_parts['host'] ) : '';

        if ( $requested_host !== $home_host && $requested_host !== $site_host ) {
            return $url;
        }

        $scheme   = isset( $home_parts['scheme'] ) ? (string) $home_parts['scheme'] : 'http';
        $host     = $home_host;
        $port     = isset( $home_parts['port'] ) ? absint( $home_parts['port'] ) : 0;
        $path     = isset( $requested_parts['path'] ) ? (string) $requested_parts['path'] : '/';
        $query    = isset( $requested_parts['query'] ) ? (string) $requested_parts['query'] : '';
        $fragment = isset( $requested_parts['fragment'] ) ? (string) $requested_parts['fragment'] : '';
        $origin   = strtolower( $scheme ) . '://' . $host;

        if ( $port > 0 ) {
            $origin .= ':' . $port;
        }

        if ( '' === $path ) {
            $path = '/';
        }

        $normalized = $origin . $path;

        if ( '' !== $query ) {
            $normalized .= '?' . $query;
        }

        if ( '' !== $fragment ) {
            $normalized .= '#' . $fragment;
        }

        return $normalized;
    }

    /**
     * Best-effort current URL resolver for same-page redirects.
     */
    private static function current_url(): string {
        $host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['HTTP_HOST'] ) ) : '';
        $uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) ) : '';
        if ( '' === $host || '' === $uri ) {
            return esc_url_raw( home_url( '/' ) );
        }
        $scheme = is_ssl() ? 'https://' : 'http://';
        return esc_url_raw( $scheme . $host . $uri );
    }

    /**
     * Final safety net: enforce global default redirect URLs for generic
     * login/registration contexts after all other filters have run.
     *
     * @param string $redirect     Current redirect URL.
     * @param string $flow_context Context string (e.g. wp_login, wp_register, shortcode).
     * @param string $provider     Provider slug.
     * @param int    $user_id      User ID.
     * @return string Enforced redirect URL.
     */
    public static function enforce_default_redirects( $redirect, $flow_context, $provider, $user_id ) {
        // Allow callers (e.g. Pro module) to skip default enforcement entirely.
        if ( apply_filters(
            'ventraconnect_sl_skip_enforce_defaults',
            false,
            $provider,
            $flow_context,
            [],
            [],
            $redirect
        ) ) {
            return $redirect;
        }

        // When passwordless redirect override is enabled for Magic Link, OTP, or Passkey,
        // respect the computed override and skip enforcing generic defaults.
        if ( class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
            $settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();

            if ( 'otp_email' === $provider ) {
                $otp_conf = isset( $settings['otp_email'] ) && is_array( $settings['otp_email'] ) ? $settings['otp_email'] : array();
                if ( ! empty( $otp_conf['redirect_override'] ) ) {
                    return $redirect;
                }
            } elseif ( 'magic_link' === $provider ) {
                $ml_conf = isset( $settings['magic_link'] ) && is_array( $settings['magic_link'] ) ? $settings['magic_link'] : array();
                if ( ! empty( $ml_conf['redirect_override'] ) ) {
                    return $redirect;
                }
            } elseif ( 'passkey' === $provider ) {
                $passkey_conf = isset( $settings['passkey'] ) && is_array( $settings['passkey'] ) ? $settings['passkey'] : array();
                if ( ! empty( $passkey_conf['redirect_override'] ) ) {
                    return $redirect;
                }
            }
        }

        if ( ! class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
            return $redirect;
        }

        $settings         = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $default_login    = isset( $settings['redirect_default_login'] ) ? trim( (string) $settings['redirect_default_login'] ) : '';
        $default_register = isset( $settings['redirect_default_register'] ) ? trim( (string) $settings['redirect_default_register'] ) : '';
        $ctx              = (string) $flow_context;

        // Generic login contexts (core + shortcodes/widgets).
        $login_contexts    = [ 'wp_login', 'global', 'shortcode', 'widget', 'login_form', 'passkey_login' ];
        // Generic registration contexts (core + shortcodes/widgets).
        $register_contexts = [ 'wp_register', 'register', 'signup', 'shortcode_register', 'widget_register', 'wp_register_form', 'verified_passkey_registration' ];

        // Registration flows.
        if ( in_array( $ctx, $register_contexts, true ) ) {
            if ( 'wp_register' === $ctx ) {
                // Core WP register form: preserve existing behaviour.
                if ( '' !== $default_register ) {
                    $redirect = esc_url_raw( $default_register );
                } elseif ( '' !== $default_login ) {
                    $redirect = esc_url_raw( $default_login );
                }
            } elseif ( 'verified_passkey_registration' === $ctx ) {
                // Verified native passkey registration: only the Register
                // default may override. Do not fall back to the Login default.
                if ( '' !== $default_register ) {
                    $redirect = esc_url_raw( $default_register );
                }
            } else {
                // Other register contexts: only apply defaults if redirect is empty.
                if ( '' === (string) $redirect ) {
                    if ( '' !== $default_register ) {
                        $redirect = esc_url_raw( $default_register );
                    } elseif ( '' !== $default_login ) {
                        $redirect = esc_url_raw( $default_login );
                    }
                }
            }
        } elseif ( in_array( $ctx, $login_contexts, true ) && '' !== $default_login ) {
            // Generic login flows: default login URL always wins in these contexts.
            $redirect = esc_url_raw( $default_login );
        }

        return $redirect;
    }
}

add_filter(
    'ventraconnect_sl_redirect_url',
    [ '\VentraConnect\SocialLogin\RedirectResolver', 'enforce_default_redirects' ],
    9999,
    4
);
