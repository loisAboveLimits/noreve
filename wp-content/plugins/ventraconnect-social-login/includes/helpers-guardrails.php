<?php
namespace VentraConnect\SocialLogin\Helpers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Decide if we are allowed to auto-create a new user for a given method + context.
 *
 * @param string $method  'social' | 'magic_link' | 'otp_email'.
 * @param string $context Context string, e.g. 'wp_login', 'wc_login', etc.
 * @param array  $args    Optional: 'email', 'provider', 'profile'.
 *
 * @return bool
 */
function can_create_new_user_for_method( $method, $context = '', array $args = array() ) {
	$method = is_string( $method ) ? strtolower( $method ) : '';

	/**
	 * Allow add-ons to register additional authentication method keys for guardrail checks.
	 *
	 * Core VentraConnect ships with social, magic_link, and otp_email only. Add-ons can
	 * extend this allowlist so they can reuse the same guardrail entry point without
	 * changing any core account-creation behavior when no extension is active.
	 *
	 * @param array<int,string> $supported_methods Supported authentication method keys.
	 * @param string            $context           Context string such as wp_login or wc_login.
	 * @param array             $args              Optional guardrail context args.
	 */
	$supported_methods = apply_filters(
		'ventraconnect_sl_guardrail_methods',
		array( 'social', 'magic_link', 'otp_email' ),
		$context,
		$args
	);

	if ( ! in_array( $method, $supported_methods, true ) ) {
		$method = 'social';
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- ctx is a read-only context hint.
	if ( empty( $context ) && ! empty( $_REQUEST['ventraconnect_sl_ctx'] ) ) {
		$context = sanitize_text_field( wp_unslash( $_REQUEST['ventraconnect_sl_ctx'] ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$context = is_string( $context ) ? $context : '';

	$email    = isset( $args['email'] ) ? $args['email'] : '';
	$provider = isset( $args['provider'] ) ? $args['provider'] : '';
	$profile  = isset( $args['profile'] ) ? $args['profile'] : array();

	$settings = get_option( 'ventraconnect_sl_settings', array() );

	// Registration mode guardrails for Magic Link.
	if ( 'magic_link' === $method ) {
		$mode = isset( $settings['magic_link']['registration_mode'] )
			? $settings['magic_link']['registration_mode']
			: 'login_and_register';

		if ( 'login_only' === $mode ) {
			return (bool) apply_filters(
				'ventraconnect_sl_can_create_user',
				false,
				$context,
				$profile,
				$provider
			);
		}
	}

	// Registration mode guardrails for OTP Email.
	if ( 'otp_email' === $method ) {
		$mode = isset( $settings['otp_email']['registration_mode'] )
			? $settings['otp_email']['registration_mode']
			: 'login_and_register';

		if ( 'login_only' === $mode ) {
			return (bool) apply_filters(
				'ventraconnect_sl_can_create_user',
				false,
				$context,
				$profile,
				$provider
			);
		}
	}

	// Woo My Account guardrail (only for wc_login* contexts).
	if ( 0 === strpos( $context, 'wc_login' ) ) {
		$allow_new = true;

		if ( function_exists( '\\VentraConnect\\SocialLogin\\Modules\\WooCommerce\\ventraconnect_sl_wc_get_settings' ) ) {
			$wc_settings = call_user_func( '\\VentraConnect\\SocialLogin\\Modules\\WooCommerce\\ventraconnect_sl_wc_get_settings' );
			if ( is_array( $wc_settings ) && isset( $wc_settings['linking']['allow_new_account'] ) ) {
				$allow_new = (bool) $wc_settings['linking']['allow_new_account'];
			}
		}

		if ( ! $allow_new ) {
			return (bool) apply_filters(
				'ventraconnect_sl_can_create_user',
				false,
				$context,
				$profile,
				$provider
			);
		}
	}

	// LMS guardrails.
	$lms_map = array(
		'learndash_login'  => 'learndash',
		'learnpress_login' => 'learnpress',
		'lifterlms_login'  => 'lifterlms',
		'tutor_login'      => 'tutor_lms',
		'pms_login'        => 'pms',
		'pmpro_login'      => 'pmpro',
		'ultimate_member_login' => 'ultimate_member',
		'ultimate_member_login_after' => 'ultimate_member',
	);

	if ( isset( $lms_map[ $context ] ) && class_exists( '\VentraConnect\SocialLogin\Pro\Options\Integrations' ) ) {
		$slug         = $lms_map[ $context ];
		$integrations = \VentraConnect\SocialLogin\Pro\Options\Integrations::get();

		if ( isset( $integrations[ $slug ]['allow_auto_create_on_login'] )
			&& ! $integrations[ $slug ]['allow_auto_create_on_login'] ) {

			return (bool) apply_filters(
				'ventraconnect_sl_can_create_user',
				false,
				$context,
				$profile,
				$provider
			);
		}
	}

	// MemberPress guardrails.
	if ( 'memberpress_login' === $context && class_exists( '\VentraConnect\SocialLogin\Pro\Options\Integrations' ) ) {
		$integrations = \VentraConnect\SocialLogin\Pro\Options\Integrations::get();

		if ( isset( $integrations['memberpress']['allow_auto_create_on_login'] )
			&& ! $integrations['memberpress']['allow_auto_create_on_login'] ) {

			return (bool) apply_filters(
				'ventraconnect_sl_can_create_user',
				false,
				$context,
				$profile,
				$provider
			);
		}
	}

	// BuddyPress guardrails.
	if ( 'buddypress_login' === $context && class_exists( '\VentraConnect\SocialLogin\Pro\Options\Integrations' ) ) {
		$integrations = \VentraConnect\SocialLogin\Pro\Options\Integrations::get();

		if ( isset( $integrations['buddypress']['allow_auto_create_on_login'] )
			&& ! $integrations['buddypress']['allow_auto_create_on_login'] ) {

			return (bool) apply_filters(
				'ventraconnect_sl_can_create_user',
				false,
				$context,
				$profile,
				$provider
			);
		}
	}

	// Final success path: core guardrails are enforced via the filter.
	return (bool) apply_filters(
		'ventraconnect_sl_can_create_user',
		true,
		$context,
		$profile,
		$provider
	);
}

/**
 * Determine whether a given passwordless context belongs to a Pro-only integration
 * (WooCommerce, LMS, membership/community, etc.).
 *
 * Free is allowed on core WordPress surfaces (wp-login, generic pages, shortcodes),
 * but Pro is required for integration contexts like checkout, wc_login, learndash_login, etc.
 *
 * @param string $context
 * @return bool
 */
function is_pro_integration_context( string $context ): bool {
	$context = strtolower( trim( (string) $context ) );

	if ( '' === $context ) {
		return false;
	}

	// Core contexts – ALWAYS allowed in Free.
	$core_contexts = array(
		'login',
		'wp_login',
		'wp_register',
		'register',
		'comments',
		'wp_comments',
		'admin-test',
		'shortcode',
	);

	if ( in_array( $context, $core_contexts, true ) ) {
		return false;
	}

	// WooCommerce contexts.
	if ( 'checkout' === $context || 0 === strpos( $context, 'wc_' ) ) {
		return true;
	}

	// LMS contexts.
	if ( 0 === strpos( $context, 'learndash_' ) ) {
		return true;
	}
	if ( 0 === strpos( $context, 'learnpress_' ) ) {
		return true;
	}
	if ( 0 === strpos( $context, 'lifterlms_' ) ) {
		return true;
	}
	if ( 0 === strpos( $context, 'tutor_' ) ) {
		return true;
	}

	// Membership / community contexts.
	if ( 0 === strpos( $context, 'memberpress_' ) ) {
		return true;
	}
	if ( 0 === strpos( $context, 'pmpro_' ) ) {
		return true;
	}
	if ( 0 === strpos( $context, 'buddypress_' ) ) {
		return true;
	}
	if ( 0 === strpos( $context, 'ultimate_member_' ) || 0 === strpos( $context, 'ultimatemember_' ) ) {
		return true;
	}

	// Everything else is Free-safe.
	return false;
}

