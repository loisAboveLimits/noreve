<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Core_Redirect_Resolver {

	public function resolve( $user_id, $requested_redirect = '', $context = '' ) {
		$user_id            = absint( $user_id );
		$requested_redirect = is_string( $requested_redirect ) ? trim( $requested_redirect ) : '';
		$context            = $this->sanitize_context( $context );
		$default_redirect   = $this->get_default_redirect( $user_id );
		$requested_redirect = $this->normalize_requested_redirect( $requested_redirect, '' );

		if ( class_exists( '\VentraConnect\SocialLogin\RedirectResolver' ) && method_exists( '\VentraConnect\SocialLogin\RedirectResolver', 'compute' ) ) {
			$state = array(
				'context'              => $context,
				'ventraconnect_sl_ctx' => $context,
				'redirect_to'          => $requested_redirect,
			);

			$resolved = \VentraConnect\SocialLogin\RedirectResolver::compute(
				$user_id,
				'passkey',
				$state,
				array()
			);

			return $this->sanitize_local_redirect( $resolved, $default_redirect );
		}

		$resolved = '' !== $requested_redirect ? $requested_redirect : $default_redirect;

		return $this->sanitize_local_redirect( $resolved, $default_redirect );
	}

	public function sanitize_context( $context ) {
		$context = is_string( $context ) ? sanitize_key( $context ) : '';

		return '' !== $context ? $context : 'wp_login';
	}

	public function normalize_requested_redirect( $requested_redirect, $default_redirect = '' ) {
		$requested_redirect = is_string( $requested_redirect ) ? trim( $requested_redirect ) : '';
		$default_redirect   = is_string( $default_redirect ) ? trim( $default_redirect ) : '';

		return $this->sanitize_local_redirect( $requested_redirect, $default_redirect );
	}

	protected function get_default_redirect( $user_id ) {
		return user_can( $user_id, 'manage_options' ) ? admin_url() : home_url( '/' );
	}

	protected function sanitize_local_redirect( $requested_redirect, $default_redirect ) {
		$requested_redirect = is_string( $requested_redirect ) ? trim( $requested_redirect ) : '';
		$default_redirect   = is_string( $default_redirect ) ? trim( $default_redirect ) : '';
		$default_redirect   = '' !== $default_redirect ? esc_url_raw( $default_redirect ) : '';

		if ( '' === $requested_redirect ) {
			return $default_redirect;
		}

		if ( 0 === strpos( $requested_redirect, '/' ) ) {
			$requested_redirect = home_url( $requested_redirect );
		}

		if ( class_exists( '\VentraConnect\SocialLogin\RedirectResolver' ) && method_exists( '\VentraConnect\SocialLogin\RedirectResolver', 'sanitize' ) ) {
			$requested_redirect = \VentraConnect\SocialLogin\RedirectResolver::sanitize( $requested_redirect );
		}

		return esc_url_raw( wp_validate_redirect( $requested_redirect, $default_redirect ) );
	}
}
