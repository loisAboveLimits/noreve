<?php
namespace VentraConnect\SocialLogin\Helpers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Compute the final redirect URL for a Magic Link login based on
 * the provider's redirect override settings.
 *
 * When override is disabled or no valid URL can be computed, this
 * returns an empty string so callers can fall back to core logic.
 *
 * @param int   $user_id  Logged-in user ID (currently unused, reserved for future).
 * @param array $settings Magic Link settings array from ventraconnect_sl_settings['magic_link'].
 * @param array $request  Request snapshot (typically $_REQUEST).
 * @param array $state    State array passed to RedirectResolver::compute().
 *
 * @return string Redirect URL or empty string when no override applies.
 */
function compute_magic_link_redirect_for_user( int $user_id, array $settings, array $request = array(), array $state = array() ): string {
	// Explicit flag: if override is off, fall back to default behaviour.
	if ( empty( $settings['redirect_override'] ) ) {
		return '';
	}

	$mode          = isset( $settings['redirect_mode'] ) ? (string) $settings['redirect_mode'] : 'same_page';
	$allowed_modes = array( 'same_page', 'referer', 'home', 'custom' );
	if ( ! in_array( $mode, $allowed_modes, true ) ) {
		$mode = 'same_page';
	}

	$raw_redirect = '';

	// Prefer an explicit redirect stored in state, then the request.
	if ( ! empty( $state['redirect_to'] ) ) {
		$raw_redirect = esc_url_raw( (string) $state['redirect_to'] );
	} elseif ( ! empty( $request['redirect_to'] ) ) {
		$raw_redirect = esc_url_raw( (string) $request['redirect_to'] );
	}

	$decided = '';

	if ( 'same_page' === $mode ) {
		// Requested page: use the requested redirect if non-empty,
		// otherwise fall back to referer or homepage.
		if ( '' !== $raw_redirect ) {
			$decided = $raw_redirect;
		} else {
			$ref     = wp_get_referer();
			$decided = $ref ? $ref : home_url( '/' );
		}
	} elseif ( 'referer' === $mode ) {
		// Always prefer referer, then homepage.
		$ref     = wp_get_referer();
		$decided = $ref ? $ref : home_url( '/' );
	} elseif ( 'home' === $mode ) {
		// Always send to homepage.
		$decided = home_url( '/' );
	} elseif ( 'custom' === $mode ) {
		$custom  = isset( $settings['redirect_url'] ) ? (string) $settings['redirect_url'] : '';
		$decided = '' !== $custom ? $custom : home_url( '/' );
	}

	// Validate and normalize the decided URL, with a safe default.
	$decided = wp_validate_redirect( $decided, home_url( '/' ) );

	if ( '' === $decided ) {
		return '';
	}

	// Final guard: never send users to admin-ajax.php.
	if ( false !== strpos( $decided, '/wp-admin/admin-ajax.php' ) ) {
		$decided = home_url( '/' );
	}

	return $decided;
}

/**
 * Compute the final redirect URL for an OTP Email login based on
 * the provider's redirect override settings.
 *
 * When override is disabled or no valid URL can be computed, this
 * returns an empty string so callers can fall back to core logic.
 *
 * @param int   $user_id  Logged-in user ID (currently unused, reserved for future).
 * @param array $settings OTP settings array from ventraconnect_sl_settings['otp_email'].
 * @param array $request  Request snapshot (typically $_REQUEST).
 * @param array $state    State array passed to RedirectResolver::compute().
 *
 * @return string Redirect URL or empty string when no override applies.
 */
function compute_otp_redirect_for_user( int $user_id, array $settings, array $request = array(), array $state = array() ): string {
	// Explicit flag: if override is off, fall back to default behaviour.
	if ( empty( $settings['redirect_override'] ) ) {
		return '';
	}

	$mode          = isset( $settings['redirect_mode'] ) ? (string) $settings['redirect_mode'] : 'same_page';
	$allowed_modes = array( 'same_page', 'referer', 'home', 'custom' );
	if ( ! in_array( $mode, $allowed_modes, true ) ) {
		$mode = 'same_page';
	}

	$raw_redirect = '';

	// For OTP, rely on the requested redirect from either state or the request.
	if ( ! empty( $state['redirect_to'] ) ) {
		$raw_redirect = esc_url_raw( (string) $state['redirect_to'] );
	} elseif ( ! empty( $request['redirect_to'] ) ) {
		$raw_redirect = esc_url_raw( (string) $request['redirect_to'] );
	}

	$decided = '';

	if ( 'same_page' === $mode ) {
		// Requested page: use the requested redirect if non-empty,
		// otherwise fall back to referer or homepage.
		if ( '' !== $raw_redirect ) {
			$decided = $raw_redirect;
		} else {
			$ref     = wp_get_referer();
			$decided = $ref ? $ref : home_url( '/' );
		}
	} elseif ( 'referer' === $mode ) {
		// Always prefer referer, then homepage.
		$ref     = wp_get_referer();
		$decided = $ref ? $ref : home_url( '/' );
	} elseif ( 'home' === $mode ) {
		// Always send to homepage.
		$decided = home_url( '/' );
	} elseif ( 'custom' === $mode ) {
		$custom  = isset( $settings['redirect_url'] ) ? (string) $settings['redirect_url'] : '';
		$decided = '' !== $custom ? $custom : home_url( '/' );
	}

	// Validate and normalize the decided URL, with a safe default.
	$decided = wp_validate_redirect( $decided, home_url( '/' ) );

	if ( '' === $decided ) {
		return '';
	}

	// Final guard: never send users to admin-ajax.php.
	if ( false !== strpos( $decided, '/wp-admin/admin-ajax.php' ) ) {
		$decided = home_url( '/' );
	}

	return $decided;
}

/**
 * Compute the final redirect URL for a Passkey login/registration flow based on
 * the provider's redirect override settings.
 *
 * When override is disabled or no valid URL can be computed, this
 * returns an empty string so callers can fall back to core logic.
 *
 * @param int   $user_id  Logged-in user ID (currently unused, reserved for future).
 * @param array $settings Passkey settings array from ventraconnect_sl_settings['passkey'].
 * @param array $request  Request snapshot (typically $_REQUEST).
 * @param array $state    State array passed to RedirectResolver::compute().
 *
 * @return string Redirect URL or empty string when no override applies.
 */
function compute_passkey_redirect_for_user( int $user_id, array $settings, array $request = array(), array $state = array() ): string {
	// Explicit flag: if override is off, fall back to default behaviour.
	if ( empty( $settings['redirect_override'] ) ) {
		return '';
	}

	$mode          = isset( $settings['redirect_mode'] ) ? (string) $settings['redirect_mode'] : 'same_page';
	$allowed_modes = array( 'same_page', 'referer', 'home', 'custom' );
	if ( ! in_array( $mode, $allowed_modes, true ) ) {
		$mode = 'same_page';
	}

	$raw_redirect = '';

	// Prefer an explicit redirect stored in state, then the request.
	if ( ! empty( $state['redirect_to'] ) ) {
		$raw_redirect = esc_url_raw( (string) $state['redirect_to'] );
	} elseif ( ! empty( $request['redirect_to'] ) ) {
		$raw_redirect = esc_url_raw( (string) $request['redirect_to'] );
	}

	$decided = '';

	if ( 'same_page' === $mode ) {
		if ( '' !== $raw_redirect ) {
			$decided = $raw_redirect;
		} else {
			$ref     = wp_get_referer();
			$decided = $ref ? $ref : home_url( '/' );
		}
	} elseif ( 'referer' === $mode ) {
		$ref     = wp_get_referer();
		$decided = $ref ? $ref : home_url( '/' );
	} elseif ( 'home' === $mode ) {
		$decided = home_url( '/' );
	} elseif ( 'custom' === $mode ) {
		$custom  = isset( $settings['redirect_url'] ) ? (string) $settings['redirect_url'] : '';
		$decided = '' !== $custom ? $custom : home_url( '/' );
	}

	// Validate and normalize the decided URL, with a safe default.
	$decided = wp_validate_redirect( $decided, home_url( '/' ) );

	if ( '' === $decided ) {
		return '';
	}

	// Final guard: never send users to admin-ajax.php.
	if ( false !== strpos( $decided, '/wp-admin/admin-ajax.php' ) ) {
		$decided = home_url( '/' );
	}

	return $decided;
}

