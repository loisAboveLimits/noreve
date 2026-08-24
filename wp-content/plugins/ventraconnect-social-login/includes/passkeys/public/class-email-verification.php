<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free native WordPress passkey email-verification flow.
 *
 * Important:
 * - Native wp-login/wp-register only.
 * - No integration contexts are supported here.
 * - Loaded only behind the PHP 8.2 passkeys support gate.
 */
class VentraConnect_SL_Passkeys_Email_Verification {

	const LOGIN_ACTION = 'ventraconnect_sl_passkey_verify';
	const TRANSIENT_PREFIX = 'ventraconnect_sl_passkeys_email_verify_';
	const CHALLENGE_TRANSIENT_PREFIX = 'ventraconnect_sl_passkeys_verify_challenge_';
	const EXPIRY_SECONDS = 1800;

	protected $allowed_contexts = array( 'wp_register_form' );
	protected $redirect_resolver;

	public function __construct( $redirect_resolver = null ) {
		$this->redirect_resolver = $redirect_resolver instanceof VentraConnect_SL_Passkeys_Core_Redirect_Resolver ? $redirect_resolver : new VentraConnect_SL_Passkeys_Core_Redirect_Resolver();
	}

	public function create_pending_verification( array $args ) {
		$email            = isset( $args['email'] ) ? sanitize_email( (string) $args['email'] ) : '';
		$context          = isset( $args['context'] ) ? sanitize_key( (string) $args['context'] ) : '';
		$action           = isset( $args['action'] ) ? sanitize_key( (string) $args['action'] ) : '';
		$existing_user_id = isset( $args['existing_user_id'] ) ? absint( $args['existing_user_id'] ) : 0;
		$display_name     = isset( $args['display_name'] ) ? sanitize_text_field( (string) $args['display_name'] ) : '';
		$username         = isset( $args['username'] ) ? sanitize_user( (string) $args['username'], true ) : '';
		// Preserve the original requested register redirect when present, but do not
		// invent a homepage fallback here. Final redirect priority belongs to the
		// shared Free redirect engine after verification succeeds.
		$redirect_url     = isset( $args['redirect_url'] ) ? $this->sanitize_local_redirect( (string) $args['redirect_url'], '' ) : '';
		$token            = $this->generate_token();

		if ( '' === $email || ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'A valid email address is required.', 'ventraconnect-social-login' ) );
		}

		if ( ! in_array( $context, $this->allowed_contexts, true ) ) {
			return new WP_Error( 'invalid_context', __( 'This verification request is not supported.', 'ventraconnect-social-login' ) );
		}

		if ( ! in_array( $action, array( 'create_new_user', 'add_passkey_to_existing_user' ), true ) ) {
			return new WP_Error( 'invalid_action', __( 'This verification request is not supported.', 'ventraconnect-social-login' ) );
		}

		if ( '' === $token ) {
			return new WP_Error( 'token_generation_failed', __( 'A verification token could not be created.', 'ventraconnect-social-login' ) );
		}

		$created_at = time();
		$expires_at = $created_at + self::EXPIRY_SECONDS;
		$state      = array(
			'email'               => $email,
			'context'             => $context,
			'action'              => $action,
			'existing_user_id'    => $existing_user_id,
			'display_name'        => $display_name,
			'username'            => $username,
			'created_at'          => $created_at,
			'expires_at'          => $expires_at,
			'verification_status' => 'pending',
			'consumed'            => false,
			'redirect_context'    => $context,
			'redirect_url'        => $redirect_url,
		);

		set_transient( $this->get_transient_key( $token ), $state, self::EXPIRY_SECONDS );
		$state['token'] = $token;

		return $state;
	}

	public function get_pending_verification( $token ) {
		$token = $this->sanitize_token( $token );

		if ( '' === $token ) {
			return new WP_Error( 'invalid_token', VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verification_invalid' ) );
		}

		$state = get_transient( $this->get_transient_key( $token ) );

		if ( ! is_array( $state ) ) {
			return new WP_Error( 'invalid_token', VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verification_invalid' ) );
		}

		if ( ! empty( $state['consumed'] ) ) {
			return new WP_Error( 'consumed_token', VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verification_invalid' ) );
		}

		$expires_at = isset( $state['expires_at'] ) ? absint( $state['expires_at'] ) : 0;

		if ( $expires_at <= 0 || $expires_at < time() ) {
			delete_transient( $this->get_transient_key( $token ) );

			return new WP_Error( 'expired_token', VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verification_link_expired' ) );
		}

		$state['token'] = $token;

		return $state;
	}

	public function consume_pending_verification( $token ) {
		$token = $this->sanitize_token( $token );

		if ( '' === $token ) {
			return;
		}

		delete_transient( $this->get_transient_key( $token ) );
	}

	public function bind_challenge_to_token( $token, $challenge ) {
		$token     = $this->sanitize_token( $token );
		$challenge = $this->sanitize_challenge( $challenge );

		if ( '' === $token || '' === $challenge ) {
			return;
		}

		set_transient(
			$this->get_challenge_transient_key( $token ),
			hash( 'sha256', $challenge ),
			VentraConnect_SL_Passkeys_Core_Challenge_Service::DEFAULT_EXPIRY_SECONDS
		);
	}

	public function is_challenge_bound_to_token( $token, $challenge ) {
		$token          = $this->sanitize_token( $token );
		$challenge      = $this->sanitize_challenge( $challenge );
		$stored_binding = get_transient( $this->get_challenge_transient_key( $token ) );

		if ( '' === $token || '' === $challenge || ! is_string( $stored_binding ) || '' === $stored_binding ) {
			return false;
		}

		return hash_equals( $stored_binding, hash( 'sha256', $challenge ) );
	}

	public function clear_bound_challenge( $token ) {
		$token = $this->sanitize_token( $token );

		if ( '' === $token ) {
			return;
		}

		delete_transient( $this->get_challenge_transient_key( $token ) );
	}

	public function send_verification_email( array $state ) {
		$token = isset( $state['token'] ) ? $this->sanitize_token( (string) $state['token'] ) : '';
		$email = isset( $state['email'] ) ? sanitize_email( (string) $state['email'] ) : '';

		if ( '' === $token || '' === $email || ! is_email( $email ) ) {
			return false;
		}

		$verify_url = $this->get_verification_url( $token );
		$site_name  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject    = VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_email_subject' );
		$body_lines = array(
			sprintf(
				/* translators: %s: site name */
				__( 'Site: %s', 'ventraconnect-social-login' ),
				$site_name
			),
			'',
			VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_email_intro' ),
			'',
			VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_email_button' ) . ':',
			$verify_url,
			'',
			VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_email_expiry' ),
			VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_email_ignore' ),
		);

		$body_template = implode(
			"\n\n",
			array(
				VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_email_intro' ),
				'{verification_link}',
				VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_email_expiry' ),
				VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_email_ignore' ),
			)
		);
		$replacements = array(
			'{verification_link}' => $verify_url,
			'{user_email}'        => $email,
			'{expires_in}'        => '30',
		);
		$body_html = '';

		if ( class_exists( '\VentraConnect\SocialLogin\Services\Passwordless_Email_Renderer' ) ) {
			$body_html = \VentraConnect\SocialLogin\Services\Passwordless_Email_Renderer::render(
				'passkey_verification',
				$body_template,
				$replacements
			);
		}

		if ( is_string( $body_html ) && '' !== trim( $body_html ) ) {
			return (bool) wp_mail(
				$email,
				$subject,
				$body_html,
				array( 'Content-Type: text/html; charset=UTF-8' )
			);
		}

		return (bool) wp_mail(
			$email,
			$subject,
			implode( "\n", array_map( 'wp_strip_all_tags', $body_lines ) )
		);
	}

	public function get_verification_url( $token ) {
		$token = $this->sanitize_token( $token );

		return esc_url_raw(
			add_query_arg(
				array(
					'action' => self::LOGIN_ACTION,
					'token'  => rawurlencode( $token ),
				),
				wp_login_url()
			)
		);
	}

	public function get_virtual_user_handle( $token ) {
		$token = $this->sanitize_token( $token );

		return 'pending-passkey-' . substr( hash( 'sha256', $token ), 0, 32 );
	}

	public function get_success_redirect_url( array $state, $user_id = 0 ) {
		// Verified passkey registration should follow the shared register redirect
		// branch, not the initial form-render branch.
		$context      = 'verified_passkey_registration';
		$stored_url   = isset( $state['redirect_url'] ) ? (string) $state['redirect_url'] : '';
		$redirect_url = $this->sanitize_local_redirect( $stored_url, '' );

		return $this->redirect_resolver->resolve( absint( $user_id ), $redirect_url, $context );
	}

	public function get_registration_display_name( array $state ) {
		$display_name = isset( $state['display_name'] ) ? trim( (string) $state['display_name'] ) : '';
		$username     = isset( $state['username'] ) ? trim( (string) $state['username'] ) : '';
		$email        = isset( $state['email'] ) ? sanitize_email( (string) $state['email'] ) : '';

		if ( '' !== $display_name ) {
			return sanitize_text_field( $display_name );
		}

		if ( '' !== $username ) {
			return sanitize_text_field( $username );
		}

		if ( is_email( $email ) ) {
			$parts = explode( '@', $email );

			return sanitize_text_field( (string) $parts[0] );
		}

		return __( 'Passkey User', 'ventraconnect-social-login' );
	}

	protected function sanitize_token( $token ) {
		$token = is_string( $token ) ? trim( $token ) : '';

		return preg_match( '/^[A-Fa-f0-9]{64}$/', $token ) ? strtolower( $token ) : '';
	}

	protected function generate_token() {
		try {
			return bin2hex( random_bytes( 32 ) );
		} catch ( Exception $exception ) {
			return '';
		}
	}

	protected function get_transient_key( $token ) {
		return self::TRANSIENT_PREFIX . hash( 'sha256', $token );
	}

	protected function get_challenge_transient_key( $token ) {
		return self::CHALLENGE_TRANSIENT_PREFIX . hash( 'sha256', $token );
	}

	protected function sanitize_local_redirect( $redirect, $fallback ) {
		return $this->redirect_resolver->normalize_requested_redirect( $redirect, $fallback );
	}

	protected function sanitize_challenge( $challenge ) {
		$challenge = is_string( $challenge ) ? trim( $challenge ) : '';

		return preg_match( '/^[A-Za-z0-9\-_]+$/', $challenge ) ? $challenge : '';
	}
}
