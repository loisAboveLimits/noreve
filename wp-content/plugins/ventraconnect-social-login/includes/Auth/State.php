<?php
namespace VentraConnect\SocialLogin\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'VENTRACONNECT_PRO' ) || ! VENTRACONNECT_PRO ) { return; }

use function hash_equals;
use function hash_hmac;
use function wp_generate_uuid4;
use function wp_json_encode;
use function wp_salt;

const STATE_TTL = 900; // 15 minutes.

/**
 * Build a signed state string using nonce + HMAC.
 *
 * @param array<string,mixed> $data
 */
function ventraconnect_sl_build_signed_state( array $data ): string {
    $payload = $data;
    $payload['nonce'] = $payload['nonce'] ?? wp_generate_uuid4();
    $payload['ts']    = $payload['ts'] ?? time();
    $payload['ver']   = 1;

    $json = wp_json_encode( $payload );
    if ( ! is_string( $json ) || '' === $json ) {
        return '';
    }

    $encoded = rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
    $sig     = hash_hmac( 'sha256', $encoded, state_secret(), true );
    $digest  = rtrim( strtr( base64_encode( $sig ), '+/', '-_' ), '=' );

    // One-time nonce storage (use Persistence helper to support canonical + legacy).
    \VentraConnect\SocialLogin\Admin\Settings\Persistence::setTransient( nonce_key( (string) $payload['nonce'] ), 1, STATE_TTL );

    return $encoded . '.' . $digest;
}

/**
 * Parse, verify, and consume a signed state string.
 *
 * @return array<string,mixed>
 */
function ventraconnect_sl_parse_and_verify_state( string $state ): array {
    if ( '' === $state ) {
        return [];
    }
    $pieces = explode( '.', $state );
    if ( 2 !== count( $pieces ) ) {
        return [];
    }
    list( $encoded, $digest ) = $pieces;

    $raw_sig = base64_decode( strtr( $digest, '-_', '+/' ), true );
    if ( false === $raw_sig ) {
        return [];
    }

    $expected = hash_hmac( 'sha256', $encoded, state_secret(), true );
    if ( ! hash_equals( $expected, $raw_sig ) ) {
        return [];
    }

    $json = base64_decode( strtr( $encoded, '-_', '+/' ), true );
    if ( false === $json || '' === $json ) {
        return [];
    }
    $data = json_decode( $json, true );
    if ( ! is_array( $data ) ) {
        return [];
    }

    $nonce = isset( $data['nonce'] ) ? (string) $data['nonce'] : '';
    if ( '' === $nonce ) {
        return [];
    }
    $ts = isset( $data['ts'] ) ? (int) $data['ts'] : 0;
    if ( $ts <= 0 || ( time() - $ts ) > STATE_TTL ) {
        return [];
    }

    $nonce_key = nonce_key( $nonce );
    if ( ! \VentraConnect\SocialLogin\Admin\Settings\Persistence::getTransient( $nonce_key ) ) {
        return [];
    }
    \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteTransient( $nonce_key );

    return $data;
}

/**
 * Derive HMAC secret.
 */
function state_secret(): string {
    return wp_salt( 'ventraconnect_sl_state' );
}

/**
 * Build nonce transient key.
 */
function nonce_key( string $nonce ): string {
    return 'ventraconnect_sl_state_' . md5( $nonce );
}
