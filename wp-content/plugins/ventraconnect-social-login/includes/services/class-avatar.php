<?php
namespace VentraConnect\SocialLogin\Services;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Avatar {
    public static function download_and_attach( string $url, int $user_id ) {
        if ( empty( $url ) || $user_id <= 0 ) { return 0; }
        if ( ! function_exists( 'download_url' ) ) { require_once ABSPATH . 'wp-admin/includes/file.php'; }
        if ( ! function_exists( 'media_handle_sideload' ) ) { require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php'; }

        $timeout   = (int) apply_filters( 'ventraconnect_sl_avatar_request_timeout', 12 );
        $max_bytes = (int) apply_filters( 'ventraconnect_sl_avatar_max_bytes', 2 * 1024 * 1024 );
    $scheme    = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_SCHEME ) );
        $cleanup   = [];
        $basename  = '';
        $tmp       = '';
        $tmp_file  = '';

        $resolve_ext = function( string $type, string $fallback = '' ) {
            $map = [
                'image/jpeg' => 'jpg',
                'image/jpg'  => 'jpg',
                'image/pjpeg'=> 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                'image/x-icon' => 'ico',
                'image/vnd.microsoft.icon' => 'ico',
                'image/bmp'  => 'bmp',
                'image/heic' => 'heic',
            ];
            $ctype = strtolower( trim( $type ) );
            if ( isset( $map[ $ctype ] ) ) {
                return $map[ $ctype ];
            }
            $parts = explode( '/', $ctype );
            if ( isset( $parts[1] ) ) {
                $sub = preg_replace( '/[^a-z0-9]+/i', '', $parts[1] );
                if ( $sub ) {
                    return strtolower( $sub );
                }
            }
            return $fallback;
        };

        if ( 'data' === $scheme ) {
            if ( ! preg_match( '#^data:(image/[a-z0-9.+-]+);base64,(.+)$#i', $url, $matches ) ) {
                return 0;
            }
            $type    = strtolower( $matches[1] );
            $encoded = $matches[2];
            $binary  = base64_decode( $encoded );
            if ( false === $binary || '' === $binary ) {
                return 0;
            }
            $tmp = wp_tempnam();
            if ( ! $tmp ) { return 0; }
            $ext = $resolve_ext( $type, 'jpg' );
            $tmp_file = $tmp . ( $ext ? '.' . $ext : '' );
            if ( false === file_put_contents( $tmp_file, $binary ) ) {
                if ( file_exists( $tmp ) ) { wp_delete_file( $tmp ); }
                if ( $tmp_file !== $tmp && file_exists( $tmp_file ) ) { wp_delete_file( $tmp_file ); }
                return 0;
            }
            $basename = 'avatar-' . $user_id . '.' . ( $ext ?: 'jpg' );
            $cleanup  = array_unique( [ $tmp, $tmp_file ] );
        } else {
            if ( 'https' !== $scheme ) {
                return 0;
            }

            $host      = strtolower( (string) wp_parse_url( (string) $url, PHP_URL_HOST ) );
            $skip_head = false;
            if ( $host && ( preg_match( '#(?:^|\\.)linkedin\\.com$#', $host ) || preg_match( '#(?:^|\\.)licdn\\.com$#', $host ) || preg_match( '#(?:^|\\.)tiktokcdn\\.com$#', $host ) || preg_match( '#(?:^|\\.)tiktok\\.com$#', $host ) ) ) {
                $skip_head = true;
            }
            $skip_head = (bool) apply_filters( 'ventraconnect_sl_avatar_skip_head', $skip_head, $url, $host, $user_id );

            $headers           = [];
            $type              = '';
            $length            = 0;
            $user_agent        = (string) apply_filters( 'ventraconnect_sl_avatar_http_user_agent', 'Mozilla/5.0 (compatible; VCS-SocialLogin/1.0; +https://ventraconnect.com)', $url, $host, $user_id );
            $fallback_statuses = (array) apply_filters( 'ventraconnect_sl_avatar_head_fallback_codes', [ 401, 403, 405, 999 ], $url, $host, $user_id );

            if ( ! $skip_head ) {
                $head = wp_remote_head( $url, [
                    'timeout'     => $timeout,
                    'redirection' => 2,
                    'headers'     => [
                        'Accept'     => 'image/*',
                        'User-Agent' => $user_agent,
                    ],
                ] );
                if ( is_wp_error( $head ) ) {
                    $skip_head = true;
                } else {
                    $code = (int) wp_remote_retrieve_response_code( $head );
                    if ( $code >= 400 || 0 === $code ) {
                        if ( in_array( $code, $fallback_statuses, true ) ) {
                            $skip_head = true;
                        } else {
                            return 0;
                        }
                    } else {
                        $headers = wp_remote_retrieve_headers( $head );
                        $type    = isset( $headers['content-type'] ) ? strtolower( (string) $headers['content-type'] ) : '';
                        if ( '' === $type || 0 !== strpos( $type, 'image/' ) || 'image/svg+xml' === $type ) {
                            return 0;
                        }
                        $length = isset( $headers['content-length'] ) ? (int) $headers['content-length'] : 0;
                        if ( $length > 0 && $length > $max_bytes ) {
                            return 0;
                        }
                    }
                }
            }

            $tmp = wp_tempnam();
            if ( ! $tmp ) { return 0; }
            $ext = $type ? $resolve_ext( $type ) : '';
            $tmp_file = $tmp;
            if ( $ext ) {
                $tmp_file = $tmp . '.' . $ext;
            }
            $cleanup = array_unique( $tmp_file !== $tmp ? [ $tmp, $tmp_file ] : [ $tmp ] );

            $resp = wp_remote_get( $url, [
                'timeout'             => $timeout,
                'redirection'         => 2,
                'headers'             => [
                    'Accept'     => 'image/*',
                    'User-Agent' => $user_agent,
                ],
                'stream'              => true,
                'filename'            => $tmp_file,
                'limit_response_size' => $max_bytes,
            ] );
            if ( is_wp_error( $resp ) ) {
                foreach ( $cleanup as $file ) { if ( file_exists( $file ) ) { wp_delete_file( $file ); } }
                return 0;
            }
            $code = (int) wp_remote_retrieve_response_code( $resp );
            if ( 200 !== $code ) {
                foreach ( $cleanup as $file ) { if ( file_exists( $file ) ) { wp_delete_file( $file ); } }
                return 0;
            }

            if ( '' === $type ) {
                $type = strtolower( (string) wp_remote_retrieve_header( $resp, 'content-type' ) );
                if ( '' === $type || 0 !== strpos( $type, 'image/' ) || 'image/svg+xml' === $type ) {
                    foreach ( $cleanup as $file ) { if ( file_exists( $file ) ) { wp_delete_file( $file ); } }
                    return 0;
                }
            }

            if ( '' === $ext ) {
                $ext = $resolve_ext( $type );
                if ( $ext ) {
                    $new_tmp = $tmp . '.' . $ext;
                    if ( $tmp_file !== $new_tmp ) {
                        $moved = false;
                        if ( ! function_exists( 'WP_Filesystem' ) ) {
                            require_once ABSPATH . 'wp-admin/includes/file.php';
                        }
                        WP_Filesystem();
                        global $wp_filesystem;
                        if ( $wp_filesystem && method_exists( $wp_filesystem, 'move' ) ) {
                            $moved = (bool) $wp_filesystem->move( $tmp_file, $new_tmp, true );
                        } else {
                            // If we don't have a filesystem API move available, skip moving.
                            // Original behavior already tolerated a failed rename without breaking flow.
                            $moved = false;
                        }
                        if ( $moved ) {
                            $tmp_file = $new_tmp;
                            $cleanup   = array_unique( array_merge( $cleanup, [ $new_tmp ] ) );
                        }
                    }
                }
            }

            $path = wp_parse_url( (string) $url, PHP_URL_PATH );
            $basename = $path ? basename( $path ) : '';
            if ( ! $basename || '.' === $basename ) {
                $basename = 'avatar-' . $user_id . '.' . ( $ext ?: 'jpg' );
            }
        }

        $file_array = [
            'name'     => 'avatar-' . $user_id . '-' . sanitize_file_name( $basename ),
            'tmp_name' => $tmp_file,
        ];
        $id = media_handle_sideload( $file_array, 0 );
        if ( is_wp_error( $id ) ) {
            foreach ( $cleanup as $file ) { if ( file_exists( $file ) ) { wp_delete_file( $file ); } }
            return 0;
        }

        foreach ( $cleanup as $file ) { if ( file_exists( $file ) ) { wp_delete_file( $file ); } }
        return (int) $id;
    }
}
