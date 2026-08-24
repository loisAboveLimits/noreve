<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Generic OAuth 2.0 / OIDC Provider with configurable endpoints.
 * Note: This covers providers that support standard OAuth2/OIDC flows.
 */
class Generic extends Abstract_Provider {
    private $slug;
    private $label;
    private $auth_url;
    private $token_url;
    private $userinfo_url;
    private $scopes;
    private $auth_params;
    private $normalize_cb; // callable|null

    public function __construct( $slug, $label, $client_id, $client_secret, $auth_url, $token_url, $userinfo_url, array $scopes = [], $normalize_cb = null, array $auth_params = [] ) {
        parent::__construct( $client_id, $client_secret );
        $this->slug = $slug;
        $this->label = $label;
        $this->auth_url = $auth_url;
        $this->token_url = $token_url;
        $this->userinfo_url = $userinfo_url;
        $this->scopes = $scopes;
        $this->normalize_cb = $normalize_cb;
        $this->auth_params = $auth_params;
    }

    public function get_slug() { return $this->slug; }
    public function get_label() { return $this->label; }

    public function get_auth_url( $state, $redirect_uri ) {
        $scope = implode( ' ', apply_filters( 'ventraconnect_sl_oauth_scopes', $this->scopes, $this->get_slug() ) );
        $args = [
            'client_id'     => $this->client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => $scope,
            'state'         => $state,
        ];
        if ( 'microsoft' === $this->get_slug() ) {
            $args['prompt'] = 'select_account';
        }
        if ( 'slack' === $this->get_slug() ) {
            $team = (string) ( $this->auth_params['team'] ?? '' );
            if ( $team ) {
                $args['team'] = $team;
            }
        }
        return $this->auth_url . '?' . http_build_query( $args, '', '&' );
    }

    public function exchange_code_for_token( $code, $redirect_uri ) {
        $scope = implode( ' ', apply_filters( 'ventraconnect_sl_oauth_scopes', $this->scopes, $this->get_slug() ) );
        $body = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirect_uri,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
        ];
        if ( '' !== $scope ) {
            $body['scope'] = $scope;
        }
        $args = [
            'body'    => $body,
            'timeout' => 20,
            'headers' => [ 'Accept' => 'application/json' ], // e.g., GitHub
        ];
        $resp = wp_remote_post( $this->token_url, $args );
        if ( is_wp_error( $resp ) ) { return $resp; }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['access_token'] ) ) {
            $error_msg = __( 'Failed to obtain access token.', 'ventraconnect-social-login' );
            if ( isset( $data['error_description'] ) && is_string( $data['error_description'] ) ) {
                $error_msg .= ' ' . $data['error_description'];
            } elseif ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
                $error_msg .= ' (' . $data['error'] . ')';
            }
            return new \WP_Error( 'ventraconnect_sl_token_error', $error_msg, $data );
        }
        return $data;
    }

    public function fetch_user_profile( $access_token ) {
        $resp = wp_remote_get( $this->userinfo_url, [
            'headers' => [ 'Authorization' => 'Bearer ' . $access_token, 'Accept' => 'application/json' ],
            'timeout' => 20,
        ] );
        if ( is_wp_error( $resp ) ) { return $resp; }
        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( ! is_array( $data ) ) {
            return new \WP_Error( 'ventraconnect_sl_profile_error', __( 'Failed to parse user profile.', 'ventraconnect-social-login' ) );
        }
        if ( 'spotify' === $this->slug ) {
            if ( isset( $data['display_name'] ) && '' === trim( (string) ( $data['name'] ?? '' ) ) ) {
                $data['name'] = (string) $data['display_name'];
            }
            if ( empty( $data['picture'] ) && empty( $data['avatar'] ) && empty( $data['avatar_url'] ) && ! empty( $data['images'] ) && is_array( $data['images'] ) ) {
                foreach ( $data['images'] as $img ) {
                    if ( is_array( $img ) && ! empty( $img['url'] ) ) {
                        $data['picture'] = (string) $img['url'];
                        break;
                    }
                }
            }
            if ( ! empty( $data['picture'] ) ) {
                if ( empty( $data['avatar'] ) ) {
                    $data['avatar'] = $data['picture'];
                }
                if ( empty( $data['avatar_url'] ) ) {
                    $data['avatar_url'] = $data['picture'];
                }
            }
            if ( ! empty( $data['display_name'] ) ) {
                $parts = preg_split( '/\s+/', trim( (string) $data['display_name'] ), -1, PREG_SPLIT_NO_EMPTY );
                if ( $parts ) {
                    $first = array_shift( $parts );
                    $last  = $parts ? implode( ' ', $parts ) : '';
                    if ( empty( $data['given_name'] ) ) {
                        $data['given_name'] = (string) $first;
                    }
                    if ( empty( $data['family_name'] ) ) {
                        $data['family_name'] = (string) $last;
                    }
                }
            }
        }
        if ( 'microsoft' === $this->slug ) {
            $graph_resp = wp_remote_get( 'https://graph.microsoft.com/v1.0/me', [
                'headers' => [ 'Authorization' => 'Bearer ' . $access_token, 'Accept' => 'application/json' ],
                'timeout' => 20,
            ] );
            if ( ! is_wp_error( $graph_resp ) ) {
                $graph_data = json_decode( wp_remote_retrieve_body( $graph_resp ), true );
                if ( is_array( $graph_data ) ) {
                    if ( empty( $data['name'] ) && ! empty( $graph_data['displayName'] ) ) {
                        $data['name'] = $graph_data['displayName'];
                    }
                    if ( ! empty( $graph_data['displayName'] ) ) {
                        $data['display_name'] = $graph_data['displayName'];
                    }
                    if ( empty( $data['given_name'] ) && ! empty( $graph_data['givenName'] ) ) {
                        $data['given_name'] = $graph_data['givenName'];
                    }
                    if ( empty( $data['family_name'] ) && ! empty( $graph_data['surname'] ) ) {
                        $data['family_name'] = $graph_data['surname'];
                    }
                    $mail_fallback = $graph_data['mail'] ?? $graph_data['userPrincipalName'] ?? '';
                    if ( empty( $data['email'] ) && $mail_fallback ) {
                        $data['email'] = $mail_fallback;
                    }
                }
            }
            $photo_resp = wp_remote_get( 'https://graph.microsoft.com/v1.0/me/photo/$value', [
                'headers' => [ 'Authorization' => 'Bearer ' . $access_token, 'Accept' => 'image/*' ],
                'timeout' => 20,
            ] );
            if ( ! is_wp_error( $photo_resp ) && 200 === (int) wp_remote_retrieve_response_code( $photo_resp ) ) {
                $photo_body = wp_remote_retrieve_body( $photo_resp );
                if ( $photo_body ) {
                    $photo_type = '';
                    $headers    = wp_remote_retrieve_headers( $photo_resp );
                    if ( is_array( $headers ) && ! empty( $headers['content-type'] ) ) {
                        $photo_type = strtolower( (string) $headers['content-type'] );
                    } elseif ( is_object( $headers ) && isset( $headers->offsetGet ) ) {
                        $photo_type = strtolower( (string) $headers->offsetGet( 'content-type' ) );
                    }
                    if ( ! $photo_type ) {
                        $photo_type = 'image/jpeg';
                    }
                    $encoded = base64_encode( $photo_body );
                    if ( $encoded ) {
                        $data['avatar_blob'] = [
                            'data'     => $encoded,
                            'type'     => $photo_type,
                            'filename' => 'microsoft-avatar',
                        ];
                        $data['picture'] = 'data:' . $photo_type . ';base64,' . $encoded;
                    }
                }
            }
            if ( isset( $data['picture'] ) && is_string( $data['picture'] ) && false !== stripos( $data['picture'], 'graph.microsoft.com' ) ) {
                $data['picture'] = '';
            }
        }
        if ( is_callable( $this->normalize_cb ) ) {
            $normalized = call_user_func( $this->normalize_cb, $data );
        } else {
            $normalized = $this->normalize_common( $data );
        }
        // GitHub email fallback: query /user/emails if primary email missing
        if ( $this->slug === 'github' && empty( $normalized['email'] ) ) {
            $emails_resp = wp_remote_get( 'https://api.github.com/user/emails', [
                'headers' => [ 'Authorization' => 'Bearer ' . $access_token, 'Accept' => 'application/vnd.github+json' ],
                'timeout' => 15,
            ] );
            if ( ! is_wp_error( $emails_resp ) ) {
                $emails = json_decode( wp_remote_retrieve_body( $emails_resp ), true );
                if ( is_array( $emails ) ) {
                    $primary = '';
                    foreach ( $emails as $e ) {
                        if ( is_array( $e ) && ! empty( $e['primary'] ) && ! empty( $e['verified'] ) && ! empty( $e['email'] ) ) { $primary = $e['email']; break; }
                    }
                    if ( ! $primary ) {
                        foreach ( $emails as $e ) { if ( is_array( $e ) && ! empty( $e['email'] ) ) { $primary = $e['email']; break; } }
                    }
                    if ( $primary ) { $normalized['email'] = (string) $primary; }
                }
            }
        }
        if ( 'wordpress' === $this->slug ) {
            if ( empty( $normalized['id'] ) && isset( $data['ID'] ) ) {
                $normalized['id'] = (string) $data['ID'];
            }
            if ( ! empty( $data['display_name'] ) ) {
                $normalized['display_name'] = (string) $data['display_name'];
                if ( empty( $normalized['name'] ) ) {
                    $normalized['name'] = (string) $data['display_name'];
                }
            }
            if ( empty( $normalized['avatar'] ) && ! empty( $data['avatar_URL'] ) ) {
                $normalized['avatar'] = (string) $data['avatar_URL'];
                $normalized['avatar_url'] = (string) $data['avatar_URL'];
            }
            if ( ! empty( $data['profile_URL'] ) ) {
                $normalized['profile_url'] = (string) $data['profile_URL'];
            }
            if ( ! empty( $data['username'] ) && empty( $normalized['nickname'] ) ) {
                $normalized['nickname'] = (string) $data['username'];
            }
            if ( isset( $data['name'] ) && is_array( $data['name'] ) ) {
                if ( ! empty( $data['name']['first'] ) ) {
                    $normalized['given_name'] = (string) $data['name']['first'];
                    $normalized['first_name'] = (string) $data['name']['first'];
                }
                if ( ! empty( $data['name']['last'] ) ) {
                    $normalized['family_name'] = (string) $data['name']['last'];
                    $normalized['last_name'] = (string) $data['name']['last'];
                }
                $combined = trim( ( $data['name']['first'] ?? '' ) . ' ' . ( $data['name']['last'] ?? '' ) );
                if ( $combined ) {
                    $normalized['name'] = $combined;
                }
            }
            if ( empty( $normalized['first_name'] ) && ! empty( $normalized['given_name'] ) ) {
                $normalized['first_name'] = (string) $normalized['given_name'];
            }
            if ( empty( $normalized['last_name'] ) && ! empty( $normalized['family_name'] ) ) {
                $normalized['last_name'] = (string) $normalized['family_name'];
            }
            if ( empty( $normalized['locale'] ) ) {
                $language = $data['locale'] ?? $data['lang'] ?? $data['language'] ?? '';
                if ( $language ) {
                    $normalized['locale'] = (string) $language;
                }
            }
        }
        if ( empty( $normalized['id'] ) ) {
            return new \WP_Error( 'ventraconnect_sl_profile_error', __( 'Failed to fetch user id.', 'ventraconnect-social-login' ) );
        }
        if ( 'microsoft' === $this->slug ) {
            if ( empty( $normalized['name'] ) && ! empty( $data['display_name'] ) ) {
                $normalized['name'] = (string) $data['display_name'];
            }
            if ( empty( $normalized['email'] ) ) {
                $mail_fallback = $data['mail'] ?? $data['email'] ?? '';
                if ( ! $mail_fallback && ! empty( $data['raw']['mail'] ) ) {
                    $mail_fallback = $data['raw']['mail'];
                }
                if ( ! $mail_fallback && ! empty( $data['raw']['userPrincipalName'] ) ) {
                    $mail_fallback = $data['raw']['userPrincipalName'];
                }
                if ( $mail_fallback ) {
                    $normalized['email'] = (string) $mail_fallback;
                }
            }
            if ( ! empty( $data['avatar_blob'] ) && is_array( $data['avatar_blob'] ?? null ) ) {
                $normalized['avatar_blob'] = $data['avatar_blob'];
            }
            if ( empty( $normalized['avatar'] ) || ( is_string( $normalized['avatar'] ) && false !== stripos( $normalized['avatar'], 'graph.microsoft.com' ) ) ) {
                if ( ! empty( $normalized['avatar_blob']['data'] ) && ! empty( $normalized['avatar_blob']['type'] ) ) {
                    $normalized['avatar'] = 'data:' . $normalized['avatar_blob']['type'] . ';base64,' . $normalized['avatar_blob']['data'];
                } else {
                    $normalized['avatar'] = '';
                }
            }
            if ( ! empty( $data['given_name'] ) ) {
                $normalized['given_name'] = (string) $data['given_name'];
            }
            if ( ! empty( $data['family_name'] ) ) {
                $normalized['family_name'] = (string) $data['family_name'];
            }
        }
        $normalized['provider'] = $this->get_slug();
        return $normalized;
    }

    private function normalize_common( array $d ) {
        $id = $d['sub'] ?? $d['id'] ?? ($d['user']['id'] ?? '');
        if ( ! $id && isset( $d['user_id'] ) ) { $id = (string) $d['user_id']; }
        if ( ! $id && isset( $d['ID'] ) ) { $id = (string) $d['ID']; }
        if ( ! $id && isset( $d['user']['ID'] ) ) { $id = (string) $d['user']['ID']; }

        $email = $d['email'] ?? ($d['user']['email'] ?? '');
        if ( empty( $email ) && isset( $d['emails'] ) && is_array( $d['emails'] ) ) {
            $first = $d['emails'][0] ?? [];
            $email = is_array( $first ) ? ( $first['email'] ?? '' ) : $first;
        }

        $given = $d['given_name'] ?? ($d['first_name'] ?? ($d['user']['first_name'] ?? ''));
        $family = $d['family_name'] ?? ($d['last_name'] ?? ($d['user']['last_name'] ?? ''));
        if ( 'slack' === $this->slug ) {
            $slack_name = $d['name'] ?? [];
            if ( is_array( $slack_name ) ) {
                if ( ! $given && ! empty( $slack_name['given_name'] ) ) {
                    $given = $slack_name['given_name'];
                }
                if ( ! $family && ! empty( $slack_name['family_name'] ) ) {
                    $family = $slack_name['family_name'];
                }
            }
            if ( ! $given && isset( $d['user'] ) && is_array( $d['user'] ) ) {
                $inner = $d['user'];
                if ( isset( $inner['profile'] ) && is_array( $inner['profile'] ) ) {
                    if ( ! $given && ! empty( $inner['profile']['first_name'] ) ) {
                        $given = $inner['profile']['first_name'];
                    }
                    if ( ! $family && ! empty( $inner['profile']['last_name'] ) ) {
                        $family = $inner['profile']['last_name'];
                    }
                }
            }
        }

        $name_field = $d['name'] ?? null;
        if ( is_array( $name_field ) ) {
            if ( ! $given && ! empty( $name_field['first'] ) ) {
                $given = $name_field['first'];
            }
            if ( ! $family && ! empty( $name_field['last'] ) ) {
                $family = $name_field['last'];
            }
            $composed = trim( (string) ( $name_field['first'] ?? '' ) . ' ' . (string) ( $name_field['last'] ?? '' ) );
            $name = $composed ?: (string) ( $name_field['display_name'] ?? '' );
        } else {
            $name = $name_field ?? ($d['display_name'] ?? ($d['login'] ?? ($d['username'] ?? '')));
        }

        $avatar = $d['picture'] ?? ($d['avatar_url'] ?? ($d['avatar_URL'] ?? ($d['profile_image_url'] ?? '')));
        $profile_url = $d['profile_url'] ?? ($d['profile'] ?? ($d['profile_URL'] ?? ($d['link'] ?? ($d['url'] ?? ''))));

        $display_name = $d['display_name'] ?? ( is_string( $name ) ? $name : '' );
        if ( ! is_string( $name ) ) {
            $name = $display_name;
        }

        if ( ( ! $given || ! is_string( $given ) || '' === trim( $given ) || ! $family || ! is_string( $family ) || '' === trim( $family ) ) && is_string( $display_name ) && '' !== trim( $display_name ) ) {
            $parts = preg_split( '/\s+/', trim( $display_name ), -1, PREG_SPLIT_NO_EMPTY );
            $parts = array_map( function( $token ) {
                return sanitize_text_field( $token );
            }, $parts );
            $parts = array_values( array_filter( $parts, static function( $token ) {
                return '' !== trim( $token );
            } ) );
            if ( ! empty( $parts ) ) {
                if ( ! $given || '' === trim( (string) $given ) ) {
                    $given = array_shift( $parts );
                }
                if ( ! $family || '' === trim( (string) $family ) ) {
                    if ( $parts ) {
                        $family = implode( ' ', $parts );
                    } else {
                        $family = '';
                    }
                }
            }
        }

        $locale = $d['locale'] ?? ($d['lang'] ?? ($d['language'] ?? ''));
        $nickname = $d['nickname'] ?? ($d['username'] ?? ($d['user']['nickname'] ?? ''));

        return [
            'id'           => (string) $id,
            'email'        => (string) $email,
            'name'         => (string) $name,
            'display_name' => (string) $display_name,
            'given_name'   => (string) $given,
            'family_name'  => (string) $family,
            'first_name'   => (string) $given,
            'last_name'    => (string) $family,
            'avatar'       => (string) $avatar,
            'avatar_url'   => (string) $avatar,
            'profile_url'  => (string) $profile_url,
            'locale'       => (string) $locale,
            'nickname'     => (string) $nickname,
            'raw'          => $d,
        ];
    }
}
