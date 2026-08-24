<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Core_Challenge_Service {

	const TYPE_REGISTRATION   = 'registration';
	const TYPE_AUTHENTICATION = 'authentication';
	const DEFAULT_EXPIRY_SECONDS = 300;

	protected $repository;

	public function __construct( $repository = null ) {
		$this->repository = $repository instanceof VentraConnect_SL_Passkeys_Core_Challenge_Repository ? $repository : new VentraConnect_SL_Passkeys_Core_Challenge_Repository();
	}

	public function create_registration_challenge( $user_id = null ) {
		$user_id = null === $user_id ? null : absint( $user_id );

		if ( null !== $user_id && ! $user_id ) {
			return false;
		}

		return $this->create_challenge_record( self::TYPE_REGISTRATION, $user_id );
	}

	public function create_authentication_challenge( $user_id = null ) {
		$user_id = null === $user_id ? null : absint( $user_id );

		if ( null !== $user_id && ! $user_id ) {
			return false;
		}

		return $this->create_challenge_record( self::TYPE_AUTHENTICATION, $user_id );
	}

	public function validate_challenge( $challenge, $type ) {
		$challenge = sanitize_text_field( $challenge );
		$type      = sanitize_text_field( $type );

		if ( '' === $challenge || ! in_array( $type, array( self::TYPE_REGISTRATION, self::TYPE_AUTHENTICATION ), true ) ) {
			return null;
		}

		return $this->repository->get_valid_challenge( $challenge, $type );
	}

	public function mark_used( $challenge_id ) {
		return $this->repository->mark_challenge_used( absint( $challenge_id ) );
	}

	public function cleanup_expired() {
		return $this->repository->delete_expired_challenges();
	}

	public function cleanup_unused_challenges_for_user( $user_id ) {
		return $this->repository->delete_unused_challenges_for_user( absint( $user_id ) );
	}

	protected function create_challenge_record( $type, $user_id ) {
		$challenge = $this->generate_challenge();

		if ( '' === $challenge ) {
			return false;
		}

		$inserted = $this->repository->create_challenge(
			array(
				'challenge_type' => $type,
				'challenge'      => $challenge,
				'expires_at'     => gmdate( 'Y-m-d H:i:s', time() + self::DEFAULT_EXPIRY_SECONDS ),
				'user_id'        => $user_id,
				'fingerprint'    => $this->get_request_fingerprint(),
				'ip_address'     => $this->get_request_ip(),
				'user_agent'     => $this->get_request_user_agent(),
			)
		);

		if ( false === $inserted ) {
			return false;
		}

		return $challenge;
	}

	protected function generate_challenge() {
		try {
			if ( function_exists( 'random_bytes' ) ) {
				$bytes = random_bytes( 32 );

				return rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' );
			}
		} catch ( Exception $exception ) {
			// Fall back to WordPress-generated random data below.
		}

		return rtrim( strtr( base64_encode( wp_generate_password( 64, true, true ) ), '+/', '-_' ), '=' );
	}

	protected function get_request_ip() {
		$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return '' !== $ip_address ? $ip_address : '';
	}

	protected function get_request_user_agent() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_textarea_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		if ( strlen( $user_agent ) > 500 ) {
			$user_agent = substr( $user_agent, 0, 500 );
		}

		return '' !== $user_agent ? $user_agent : '';
	}

	protected function get_request_fingerprint() {
		return wp_hash( $this->get_request_ip() . '|' . $this->get_request_user_agent() );
	}
}
