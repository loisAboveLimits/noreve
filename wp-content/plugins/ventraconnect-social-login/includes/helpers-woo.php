<?php
namespace VentraConnect\SocialLogin\Helpers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Determine if a new account may be created in the current context based on Woo settings.
 * - Only constrains creation on WooCommerce login surfaces (ventraconnect_sl_ctx starts with 'wc_login').
 * - Defaults to allowed outside Woo or when Woo is not active.
 */
function can_create_new_account(): bool {
	
	$sl_ctx = '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ctx is a read-only context hint from WooCommerce login/checkout forms.
	if ( isset( $_REQUEST['ventraconnect_sl_ctx'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- ctx is a read-only context hint from WooCommerce login/checkout forms.
		$sl_ctx = sanitize_text_field( wp_unslash( (string) $_REQUEST['ventraconnect_sl_ctx'] ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$ctx       = $sl_ctx;
	$is_woo_ctx = ( '' !== $ctx ) && ( 0 === strpos( $ctx, 'wc_' ) || 'checkout' === $ctx );

	if ( ! $is_woo_ctx || ! class_exists( '\\WooCommerce' ) ) {
		return true; // Not a Woo flow – unrestricted.
	}

	// Only gate "allow_new_account" on Woo login contexts (e.g. wc_login, wc_login_form).
	$is_login_ctx = ( 0 === strpos( $ctx, 'wc_login' ) );
	if ( ! $is_login_ctx ) {
		// Checkout, register, account, and other Woo contexts are never blocked by this toggle.
		return true;
	}

	$allow_new = true;

	if ( function_exists( '\\VentraConnect\\SocialLogin\\Modules\\WooCommerce\\ventraconnect_sl_wc_get_settings' ) ) {
		$wc_settings = call_user_func( '\\VentraConnect\\SocialLogin\\Modules\\WooCommerce\\ventraconnect_sl_wc_get_settings' );
		if ( is_array( $wc_settings ) && isset( $wc_settings['linking']['allow_new_account'] ) ) {
			$allow_new = (bool) $wc_settings['linking']['allow_new_account'];
		}
	}

	return $allow_new;
}

