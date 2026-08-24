<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Lightweight provider diagnostics to validate configuration quickly without full login.
 */
class Diagnostics {
    public static function register_ajax_routes() {
        add_action( 'wp_ajax_ventraconnect_sl_run_diagnostics', [ __CLASS__, 'handle' ] );
    }

    public static function handle() {
        $want_json = isset( $_REQUEST['json'] );

        if ( ! current_user_can( 'manage_options' ) ) {
            if ( $want_json ) {
                wp_send_json_error(
                    array(
                        'code'    => 'forbidden',
                        'message' => __( 'You do not have permission to perform this action.', 'ventraconnect-social-login' ),
                    ),
                    403
                );
            }

            wp_die( esc_html__( 'Insufficient permissions.', 'ventraconnect-social-login' ) );
        }


        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- validated via nonce below.
        $provider = '';
        if ( isset( $_GET['provider'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized into $provider via sanitize_key() below.
            $provider_raw = wp_unslash( (string) $_GET['provider'] );
            $provider     = sanitize_text_field( $provider_raw );
        }

        // Legacy per-provider nonce still honored for direct GET usage.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verified via wp_verify_nonce() below.
        $nonce = '';
        if ( isset( $_GET['_wpnonce'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated via wp_verify_nonce below.
            $nonce_raw = wp_unslash( (string) $_GET['_wpnonce'] );
            $nonce     = sanitize_text_field( $nonce_raw );
        }
        if ( ! $provider || ! wp_verify_nonce( $nonce, 'ventraconnect_sl_diag_' . $provider ) ) {
            if ( $want_json ) {
                wp_send_json_error(
                    [
                        'message' => __( 'Invalid request.', 'ventraconnect-social-login' ),
                    ],
                    400
                );
            }
            wp_die( esc_html__( 'Invalid request.', 'ventraconnect-social-login' ) );
        }

        $redirect = OAuth::redirect_uri( $provider );
        $prov     = OAuth::provider( $provider );
        $creds    = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderCreds();
        $c        = (array) ( $creds[ $provider ] ?? [] );

        $lines     = [];
        $has_error = false;

        $ok   = static function ( $msg ) {
            return '✔ ' . $msg;
        };
        $warn = static function ( $msg ) {
            return '⚠ ' . $msg;
        };
        $err  = static function ( $msg ) use ( &$has_error ) {
            $has_error = true;
            return '✖ ' . $msg;
        };

        $lines[] = 'Provider: ' . $provider;
        $lines[] = 'Redirect URI: ' . $redirect;

        // HTTPS / SSL check.
        if ( is_ssl() ) {
            $lines[] = '✔ HTTPS detected. This is required by most providers for production apps.';
        } else {
            $lines[] = '⚠ Site is not using HTTPS. Many providers will reject callbacks without HTTPS in production. Hint: Install a valid SSL certificate and update your WordPress + Site Address URLs to https://.';
        }

        // Basic credential checks.
        if ( empty( $c['client_id'] ) ) {
            $lines[] = $err( 'Client ID is empty. Hint: Copy the Client ID from the provider dashboard into the Credentials → Client ID field.' );
        } else {
            $lines[] = $ok( 'Client ID is present.' );
        }

        if ( ! array_key_exists( 'client_secret', $c ) || '' === (string) $c['client_secret'] ) {
            $lines[] = $err( 'Client Secret is empty. Hint: Copy the Client Secret from the provider dashboard into the Credentials → Client Secret field.' );
        } else {
            $lines[] = $ok( 'Client Secret is present.' );
        }

        // Redirect URI host vs site domain check.
        $site_home      = home_url();
        $site_parts     = wp_parse_url( $site_home );
        $redirect_parts = wp_parse_url( $redirect );

        if ( ! empty( $site_parts ) && ! empty( $redirect_parts ) && ! empty( $site_parts['host'] ) && ! empty( $redirect_parts['host'] ) ) {
            if ( 0 === strcasecmp( $site_parts['host'], $redirect_parts['host'] ) ) {
                $lines[] = '✔ Redirect URI host matches your site domain.';
            } else {
                $lines[] = $err( 'Redirect URI host does not match your site URL. Hint: Ensure the Redirect URL you pasted into the provider exactly matches this site\'s URL (domain, protocol, and path).' );
            }
        } else {
            $lines[] = '⚠ Unable to compare Redirect URI host to site URL. Hint: Verify the Redirect URL exactly matches your site domain and path.';
        }

        if ( $prov ) {
            $state   = OAuth::generate_state( $provider, [ 'verify' => 1 ] );
            $authurl = $prov->get_auth_url( $state, $redirect );
            $lines[] = $ok( 'Auth URL constructed.' );
            $lines[] = 'Authorize URL: ' . $authurl;

            // Live check of the Auth URL (same URL used by "Verify Settings").
            $auth_resp = wp_remote_get(
                $authurl,
                [
                    'timeout'     => 15,
                    'redirection' => 0,
                    'sslverify'   => true,
                ]
            );

            if ( is_wp_error( $auth_resp ) ) {
                $code    = $auth_resp->get_error_code();
                $msg     = $auth_resp->get_error_message();
                $lines[] = $err(
                    'Auth URL check failed: ' . $code . ' - ' . $msg
                    . '. Hint: This usually means your server cannot reach the OAuth authorize endpoint (DNS, firewall, or TLS issue).'
                );
            } else {
                $status = wp_remote_retrieve_response_code( $auth_resp );
                $body   = wp_remote_retrieve_body( $auth_resp );

                if ( $status >= 200 && $status < 400 ) {
                    // 200/3xx is generally expected: either an HTML login page or redirect to it.
                    $lines[] = $ok( 'Auth URL responded with HTTP ' . $status . ' (reachable from this server).' );
                } else {
                    // 4xx/5xx – likely misconfiguration or provider error.
                    $snippet = '';
                    if ( is_string( $body ) && '' !== $body ) {
                        $snippet = wp_strip_all_tags( substr( $body, 0, 300 ) );
                        $snippet = trim( preg_replace( '/\s+/', ' ', $snippet ) );
                    }

                    $msg = 'Auth URL returned HTTP ' . $status . '.';
                    if ( $snippet ) {
                        $msg .= ' Snippet: ' . $snippet;
                    }

                    $msg .= ' Hint: This usually means your provider app configuration or redirect URL is invalid. '
                        . 'Check that the Redirect URL shown above is whitelisted in the provider console and that the app is in Live/Production mode.';

                    $lines[] = $err( $msg );
                }
            }
        } else {
            $lines[] = $err( 'Unknown provider.' );
        }

        // Provider-specific remote checks (reachability and format sanity).
        $reach = static function( $url, $name ) use ( $ok, $warn, $err, &$has_error ) {
            $resp = wp_remote_get(
                $url,
                [
                    'timeout'      => 15,
                    'redirection'  => 3,
                    'sslverify'    => true,
                ]
            );

            if ( is_wp_error( $resp ) ) {
                $code = $resp->get_error_code();
                $msg  = $resp->get_error_message();
                return $err( 'HTTP request to provider failed: ' . $code . ' – ' . $msg . '. Hint: This is usually a server-side issue (firewall, cURL/TLS, DNS). Ask your host to check outbound HTTPS to the provider domain.' );
            }

            $status = wp_remote_retrieve_response_code( $resp );
            if ( $status && $status >= 400 ) {
                $body = wp_remote_retrieve_body( $resp );
                $data = json_decode( $body, true );

                $error          = is_array( $data ) && isset( $data['error'] ) ? (string) $data['error'] : '';
                $error_desc     = is_array( $data ) && isset( $data['error_description'] ) ? (string) $data['error_description'] : '';
                $fb_error       = is_array( $data ) && isset( $data['error']['message'] ) ? (string) $data['error']['message'] : '';
                $provider_error = '';

                if ( $error ) {
                    $provider_error = $error;
                    if ( $error_desc ) {
                        $provider_error .= ' – ' . $error_desc;
                    }
                } elseif ( $fb_error ) {
                    $provider_error = $fb_error;
                }

                $hint = '';
                if ( false !== strpos( $provider_error, 'invalid_client' ) ) {
                    $hint = 'Hint: Double-check your Client ID and Client Secret.';
                } elseif ( false !== strpos( $provider_error, 'redirect_uri' ) || false !== strpos( $provider_error, 'redirect_uri_mismatch' ) ) {
                    $hint = 'Hint: Ensure the exact Redirect URL shown above is added to the provider app\'s allowed redirect/callback URLs.';
                }

                if ( $provider_error ) {
                    $line = '✖ Provider returned HTTP ' . $status . ' with error: ' . $provider_error;
                    if ( $hint ) {
                        $line .= ' ' . $hint;
                    }
                    $has_error = true;
                    return $line;
                }

                $has_error = true;
                return '✖ Provider returned HTTP ' . $status . ' with an unexpected response. Hint: Check the provider dashboard for app errors or try re-generating the credentials.';
            }

            return $ok( $name . ' reachable.' );
        };

        switch ( $provider ) {
            case 'facebook':
                if ( ! empty( $c['client_id'] ) && ! empty( $c['client_secret'] ) ) {
                    $url  = add_query_arg(
                        [
                            'client_id'     => $c['client_id'],
                            'client_secret' => $c['client_secret'],
                            'grant_type'    => 'client_credentials',
                        ],
                        'https://graph.facebook.com/oauth/access_token'
                    );
                    $resp = wp_remote_get(
                        $url,
                        [
                            'timeout'   => 15,
                            'sslverify' => true,
                        ]
                    );

                    if ( is_wp_error( $resp ) ) {
                        $code = $resp->get_error_code();
                        $msg  = $resp->get_error_message();
                        $lines[] = $err( 'HTTP request to provider failed: ' . $code . ' – ' . $msg . '. Hint: This is usually a server-side issue (firewall, cURL/TLS, DNS). Ask your host to check outbound HTTPS to graph.facebook.com.' );
                    } else {
                        $status = wp_remote_retrieve_response_code( $resp );
                        $body   = wp_remote_retrieve_body( $resp );
                        $data   = json_decode( $body, true );

                        if ( $status && $status >= 400 ) {
                            $error          = is_array( $data ) && isset( $data['error'] ) ? (string) $data['error'] : '';
                            $error_desc     = is_array( $data ) && isset( $data['error_description'] ) ? (string) $data['error_description'] : '';
                            $fb_error       = is_array( $data ) && isset( $data['error']['message'] ) ? (string) $data['error']['message'] : '';
                            $provider_error = '';

                            if ( $error ) {
                                $provider_error = $error;
                                if ( $error_desc ) {
                                    $provider_error .= ' – ' . $error_desc;
                                }
                            } elseif ( $fb_error ) {
                                $provider_error = $fb_error;
                            }

                            $hint = '';
                            if ( false !== strpos( $provider_error, 'invalid_client' ) ) {
                                $hint = 'Hint: Double-check your Client ID and Client Secret.';
                            } elseif ( false !== strpos( $provider_error, 'redirect_uri' ) ) {
                                $hint = 'Hint: Ensure the exact Redirect URL shown above is added to the provider app\'s allowed redirect/callback URLs.';
                            }

                            $line = '✖ Provider returned HTTP ' . $status;
                            if ( $provider_error ) {
                                $line .= ' with error: ' . $provider_error;
                            }
                            if ( $hint ) {
                                $line .= ' ' . $hint;
                            }
                            $has_error = true;
                            $lines[]   = $line;
                        } elseif ( is_array( $data ) && ! empty( $data['access_token'] ) ) {
                            $lines[] = $ok( 'Facebook App ID/Secret validated (app access token issued).' );
                        } else {
                            $lines[] = $warn( 'Facebook credentials check did not return an access token.' );
                        }
                    }
                }
                break;

            case 'google':
                $lines[] = $reach( 'https://accounts.google.com/.well-known/openid-configuration', 'Google OpenID metadata' );
                if ( ! empty( $c['client_id'] ) ) {
                    if ( ! preg_match( '/^[0-9]+\-[a-z0-9\-]+\.apps\.googleusercontent\.com$/i', (string) $c['client_id'] ) ) {
                        $lines[] = $warn( 'Client ID is present but does not match the typical format for this provider. Hint: Double-check you copied the correct value (for example, some dashboards show a separate "Secret ID" vs "Secret value").' );
                    } else {
                        $lines[] = $ok( 'Client ID is present and looks valid for this provider.' );
                    }
                }
                break;

            case 'microsoft':
                $lines[] = $reach( 'https://login.microsoftonline.com/common/v2.0/.well-known/openid-configuration', 'Microsoft OpenID metadata' );
                if ( ! empty( $c['client_id'] ) ) {
                    if ( ! preg_match( '/^[0-9a-fA-F\-]{36}$/', (string) $c['client_id'] ) ) {
                        $lines[] = $warn( 'Client ID is present but does not match the typical format for this provider. Hint: Double-check you copied the correct value (for example, some dashboards show a separate "Secret ID" vs "Secret value").' );
                    } else {
                        $lines[] = $ok( 'Client ID is present and looks valid for this provider.' );
                    }
                }
                break;

            case 'linkedin':
                $lines[] = $reach( 'https://www.linkedin.com/oauth/.well-known/openid-configuration', 'LinkedIn OpenID metadata' );
                break;

            case 'yahoo':
                $lines[] = $reach( 'https://api.login.yahoo.com/.well-known/openid-configuration', 'Yahoo OpenID metadata' );
                break;

            case 'slack':
                $lines[] = $reach( 'https://slack.com/.well-known/openid-configuration', 'Slack OpenID metadata' );
                if ( ! empty( $c['client_id'] ) ) {
                    if ( ! preg_match( '/^\d+\.\d+$/', (string) $c['client_id'] ) ) {
                        $lines[] = $warn( 'Client ID is present but does not match the typical format for this provider. Hint: Double-check you copied the correct value (for example, some dashboards show a separate "Secret ID" vs "Secret value").' );
                    } else {
                        $lines[] = $ok( 'Client ID is present and looks valid for this provider.' );
                    }
                }
                break;

            case 'wordpress':
                $lines[] = $reach( 'https://public-api.wordpress.com/oauth2/authorize', 'WordPress.com authorize endpoint' );
                $lines[] = $reach( 'https://public-api.wordpress.com/oauth2/token', 'WordPress.com token endpoint' );
                if ( ! empty( $c['client_id'] ) ) {
                    if ( ! preg_match( '/^\d+$/', (string) $c['client_id'] ) ) {
                        $lines[] = $warn( 'Client ID is present but does not match the typical format for this provider. Hint: Double-check you copied the correct value (for example, some dashboards show a separate "Secret ID" vs "Secret value").' );
                    } else {
                        $lines[] = $ok( 'Client ID is present and looks valid for this provider.' );
                    }
                }
                break;

            case 'discord':
                $lines[] = $reach( 'https://discord.com/api/oauth2/authorize', 'Discord authorize endpoint' );
                $lines[] = $reach( 'https://discord.com/api/oauth2/token', 'Discord token endpoint' );
                if ( ! empty( $c['client_id'] ) ) {
                    if ( ! preg_match( '/^\d+$/', (string) $c['client_id'] ) ) {
                        $lines[] = $warn( 'Client ID is present but does not match the typical format for this provider. Hint: Double-check you copied the correct value (for example, some dashboards show a separate "Secret ID" vs "Secret value").' );
                    } else {
                        $lines[] = $ok( 'Client ID is present and looks valid for this provider.' );
                    }
                }
                break;

            case 'spotify':
                $lines[] = $reach( 'https://accounts.spotify.com/authorize', 'Spotify authorize endpoint' );
                $lines[] = $reach( 'https://accounts.spotify.com/api/token', 'Spotify token endpoint' );
                break;

            case 'line':
                $lines[] = $reach( 'https://access.line.me/oauth2/v2.1/authorize', 'LINE authorize endpoint' );
                $lines[] = $reach( 'https://api.line.me/oauth2/v2.1/token', 'LINE token endpoint' );
                if ( ! empty( $c['client_id'] ) ) {
                    if ( ! preg_match( '/^\d+$/', (string) $c['client_id'] ) ) {
                        $lines[] = $warn( 'Client ID is present but does not match the typical format for this provider. Hint: Double-check you copied the correct value (for example, some dashboards show a separate "Secret ID" vs "Secret value").' );
                    } else {
                        $lines[] = $ok( 'Client ID is present and looks valid for this provider.' );
                    }
                }
                break;

            case 'amazon':
                $lines[] = $reach( 'https://api.amazon.com/auth/o2/token', 'Amazon token endpoint' );
                break;

            case 'github':
                $lines[] = $reach( 'https://github.com/login/oauth/authorize', 'GitHub authorize endpoint' );
                $lines[] = $reach( 'https://github.com/login/oauth/access_token', 'GitHub token endpoint' );
                break;

            case 'twitter':
                $lines[] = $reach( 'https://x.com/i/oauth2/authorize', 'Twitter (X) OAuth2 authorize endpoint' );
                break;

            case 'tiktok':
                if ( ! empty( $c['client_id'] ) ) {
                    if ( preg_match( '/^[0-9a-fA-F\-]{36}$/', (string) $c['client_id'] ) ) {
                        $lines[] = $warn( 'Client ID is present but does not match the typical format for this provider. Hint: Double-check you copied the correct value (for example, some dashboards show a separate "Secret ID" vs "Secret value").' );
                    } else {
                        $lines[] = $ok( 'Client ID is present and looks valid for this provider.' );
                    }
                }
                $lines[] = $reach( 'https://open.tiktokapis.com/', 'TikTok APIs host' );
                break;

            case 'twitch':
                $lines[] = $reach( 'https://id.twitch.tv/oauth2/authorize', 'Twitch authorize endpoint' );
                $lines[] = $reach( 'https://id.twitch.tv/oauth2/token', 'Twitch token endpoint' );
                if ( ! empty( $c['client_id'] ) ) {
                    if ( strlen( (string) $c['client_id'] ) < 20 ) {
                        $lines[] = $warn( 'Client ID is present but does not match the typical format for this provider. Hint: Double-check you copied the correct value (for example, some dashboards show a separate "Secret ID" vs "Secret value").' );
                    } else {
                        $lines[] = $ok( 'Client ID is present and looks valid for this provider.' );
                    }
                }
                break;

            case 'reddit':
                $lines[] = $reach( 'https://www.reddit.com/api/v1/authorize', 'Reddit authorize endpoint' );
                $lines[] = $reach( 'https://www.reddit.com/api/v1/access_token', 'Reddit token endpoint' );
                if ( ! empty( $c['client_id'] ) ) {
                    if ( 'personal_use_script' === $c['client_id'] ) {
                        $lines[] = $warn( 'Client ID is present but does not match the typical format for this provider. Hint: Double-check you copied the correct value (for example, some dashboards show a separate "Secret ID" vs "Secret value").' );
                    } else {
                        $lines[] = $ok( 'Client ID is present and looks valid for this provider.' );
                    }
                }
                break;
        }

        // Summary line.
        if ( $has_error ) {
            $lines[] = 'Summary: ✖ One or more critical checks failed. Fix the issues marked with ✖ above and run diagnostics again.';
        } else {
            $lines[] = 'Summary: ✔ Basic configuration looks good. If login still fails, check the exact error on the login screen or provider dashboard.';
        }

        if ( $want_json ) {
            wp_send_json_success(
                [
                    'provider' => $provider,
                    'lines'    => $lines,
                ]
            );
        }

        echo '<div class="wrap">';
        echo '<h2>' . esc_html__( 'VentraConnect Social Login – Diagnostics', 'ventraconnect-social-login' ) . '</h2>';
        echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=ventraconnect-sl-settings&view=provider&provider=' . rawurlencode( $provider ) ) ) . '">&larr; ' . esc_html__( 'Back to provider', 'ventraconnect-social-login' ) . '</a></p>';
        echo '<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;line-height:1.5;white-space:pre-wrap;">' . esc_html( implode( "\n", $lines ) ) . '</pre>';
        echo '</div>';
        exit;
    }
}

