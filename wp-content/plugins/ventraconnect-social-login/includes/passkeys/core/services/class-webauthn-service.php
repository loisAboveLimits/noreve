<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free WebAuthn service for native WordPress passkeys.
 *
 * Important:
 * - Native WordPress profile management and public wp-login/wp-register flows only.
 * - No Woo/LMS/membership integration logic belongs here.
 * - This file is only loaded behind the PHP 8.2 passkeys support gate.
 */
class VentraConnect_SL_Passkeys_Core_WebAuthn_Service {

	protected $dependencies;
	protected $vendor_loader;
	protected $challenge_service;
	protected $passkey_repository;
	protected $log_repository;

	public function __construct( $dependencies = null, $vendor_loader = null, $challenge_service = null, $passkey_repository = null, $log_repository = null ) {
		$this->vendor_loader      = $vendor_loader instanceof VentraConnect_SL_Passkeys_Core_Vendor_Loader ? $vendor_loader : new VentraConnect_SL_Passkeys_Core_Vendor_Loader();
		$this->dependencies       = $dependencies instanceof VentraConnect_SL_Passkeys_Core_WebAuthn_Dependencies ? $dependencies : new VentraConnect_SL_Passkeys_Core_WebAuthn_Dependencies( $this->vendor_loader );
		$this->challenge_service  = $challenge_service instanceof VentraConnect_SL_Passkeys_Core_Challenge_Service ? $challenge_service : new VentraConnect_SL_Passkeys_Core_Challenge_Service();
		$this->passkey_repository = $passkey_repository instanceof VentraConnect_SL_Passkeys_Core_Passkey_Repository ? $passkey_repository : new VentraConnect_SL_Passkeys_Core_Passkey_Repository();
		$this->log_repository     = $log_repository instanceof VentraConnect_SL_Passkeys_Core_Log_Repository ? $log_repository : new VentraConnect_SL_Passkeys_Core_Log_Repository();
	}

	public function is_available() {
		return $this->dependencies->is_ready_for_webauthn();
	}

	public function get_relying_party_name() {
		$rp_name = (string) get_bloginfo( 'name' );
		$rp_name = trim( $rp_name );

		if ( '' === $rp_name ) {
			$rp_name = 'VentraConnect';
		}

		return (string) apply_filters( 'ventraconnect_passkeys_relying_party_name', $rp_name );
	}

	public function get_relying_party_id() {
		$home_url = home_url();
		$host     = wp_parse_url( $home_url, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return '';
		}

		$host = strtolower( trim( $host ) );

		return (string) apply_filters( 'ventraconnect_passkeys_relying_party_id', $host );
	}

	public function get_origin() {
		$home_url = home_url();
		$scheme   = wp_parse_url( $home_url, PHP_URL_SCHEME );
		$host     = wp_parse_url( $home_url, PHP_URL_HOST );
		$port     = wp_parse_url( $home_url, PHP_URL_PORT );

		if ( ! is_string( $scheme ) || '' === $scheme || ! is_string( $host ) || '' === $host ) {
			return '';
		}

		$origin = strtolower( $scheme ) . '://' . strtolower( $host );

		if ( ! empty( $port ) ) {
			$origin .= ':' . absint( $port );
		}

		return (string) apply_filters( 'ventraconnect_passkeys_origin', $origin );
	}

	public function get_configuration_summary() {
		$health_status = $this->dependencies->get_health_status();

		return array(
			'available'        => $this->is_available(),
			'rp_name'          => $this->get_relying_party_name(),
			'rp_id'            => $this->get_relying_party_id(),
			'origin'           => $this->get_origin(),
			'php_version'      => PHP_VERSION,
			'vendor_loaded'    => ! empty( $health_status['vendor_loaded'] ),
			'library_detected' => ! empty( $health_status['expected_class_exists'] ),
		);
	}

	public function generate_registration_options( $user_id ) {
		if ( ! $this->is_available() ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_webauthn_unavailable',
				__( 'WebAuthn dependencies are not available yet.', 'ventraconnect-social-login' )
			);
		}

		$user_id = absint( $user_id );

		if ( $user_id <= 0 ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_invalid_user_id',
				__( 'A valid user ID is required for registration options.', 'ventraconnect-social-login' )
			);
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_user_not_found',
				__( 'The requested user could not be found.', 'ventraconnect-social-login' )
			);
		}

		$challenge = $this->challenge_service->create_registration_challenge( $user->ID );

		if ( false === $challenge ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_registration_challenge_failed',
				__( 'Unable to create a registration challenge.', 'ventraconnect-social-login' )
			);
		}

		if ( '' === $this->get_relying_party_name() || '' === $this->get_relying_party_id() || '' === $this->get_origin() ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_invalid_relying_party',
				__( 'WebAuthn relying party configuration is incomplete.', 'ventraconnect-social-login' )
			);
		}

		try {
			$options = $this->build_registration_creation_options( $user, $challenge );
		} catch ( Throwable $throwable ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_registration_options_error',
				$throwable->getMessage()
			);
		}

		return $this->creation_options_to_array( $options );
	}

	public function generate_registration_options_for_identity( $user_name, $display_name, $user_handle, $exclude_user_id = 0 ) {
		if ( ! $this->is_available() ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_webauthn_unavailable',
				__( 'WebAuthn dependencies are not available yet.', 'ventraconnect-social-login' )
			);
		}

		$user_name    = is_string( $user_name ) ? trim( $user_name ) : '';
		$display_name = is_string( $display_name ) ? trim( $display_name ) : '';
		$user_handle  = is_string( $user_handle ) ? trim( $user_handle ) : '';

		if ( '' === $user_name || '' === $display_name || '' === $user_handle ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_invalid_registration_identity',
				__( 'A verified registration identity is required.', 'ventraconnect-social-login' )
			);
		}

		$challenge = $this->challenge_service->create_registration_challenge( null );

		if ( false === $challenge ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_registration_challenge_failed',
				__( 'Unable to create a registration challenge.', 'ventraconnect-social-login' )
			);
		}

		try {
			$options = $this->build_registration_creation_options_for_identity( $user_name, $display_name, $user_handle, (string) $challenge, absint( $exclude_user_id ) );
		} catch ( Throwable $throwable ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_registration_options_error',
				$throwable->getMessage()
			);
		}

		return $this->creation_options_to_array( $options );
	}

	public function verify_registration_response( $user_id, array $response ) {
		if ( ! $this->is_available() ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_webauthn_unavailable',
				__( 'WebAuthn dependencies are not available yet.', 'ventraconnect-social-login' )
			);
		}

		$user_id = absint( $user_id );

		if ( $user_id <= 0 ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_invalid_user_id',
				__( 'A valid user ID is required for registration verification.', 'ventraconnect-social-login' )
			);
		}

		$user = get_user_by( 'id', $user_id );

		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_user_not_found',
				__( 'The requested user could not be found.', 'ventraconnect-social-login' )
			);
		}

		$shape = $this->get_registration_response_shape( $response );

		if (
			empty( $shape['has_id'] ) ||
			empty( $shape['has_raw_id'] ) ||
			empty( $shape['has_client_data_json'] ) ||
			empty( $shape['has_attestation_object'] ) ||
			empty( $shape['has_type'] )
		) {
			return new WP_Error(
				'invalid_credential_shape',
				__( 'The registration credential payload is incomplete.', 'ventraconnect-social-login' ),
				$shape
			);
		}

		if ( 'public-key' !== $shape['type'] ) {
			return new WP_Error(
				'invalid_credential_type',
				__( 'The registration credential type is invalid.', 'ventraconnect-social-login' ),
				$shape
			);
		}

		$client_data_json = $this->base64url_decode( $shape['client_data_json'] );
		if ( false === $client_data_json ) {
			return new WP_Error(
				'invalid_client_data_json',
				__( 'The registration client data could not be decoded.', 'ventraconnect-social-login' ),
				$shape
			);
		}

		$client_data = json_decode( $client_data_json, true );
		if ( ! is_array( $client_data ) ) {
			return new WP_Error(
				'invalid_client_data_json',
				__( 'The registration client data is not valid JSON.', 'ventraconnect-social-login' ),
				$shape
			);
		}

		$attestation_object = $this->base64url_decode( $shape['attestation_object'] );
		if ( false === $attestation_object ) {
			return new WP_Error(
				'invalid_attestation_object',
				__( 'The registration attestation object could not be decoded.', 'ventraconnect-social-login' ),
				$shape
			);
		}

		$client_data_validation = $this->validate_registration_client_data( $client_data );
		if ( is_wp_error( $client_data_validation ) ) {
			return $client_data_validation;
		}

		$challenge_row = $this->challenge_service->validate_challenge(
			$client_data_validation['challenge'],
			VentraConnect_SL_Passkeys_Core_Challenge_Service::TYPE_REGISTRATION
		);

		if ( ! is_object( $challenge_row ) ) {
			return new WP_Error(
				'invalid_registration_challenge',
				__( 'The registration challenge could not be found or is no longer valid.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => false,
					'origin_match'    => true,
				)
			);
		}

		if ( absint( $challenge_row->user_id ) !== $user->ID ) {
			return new WP_Error(
				'invalid_registration_challenge',
				__( 'The registration challenge does not belong to the current user.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				)
			);
		}

		try {
			$attestation_support_manager = $this->get_attestation_statement_support_manager();
			$serializer                  = ( new \Webauthn\Denormalizer\WebauthnSerializerFactory( $attestation_support_manager ) )->create();
			$public_key_credential       = $serializer->denormalize( $response, \Webauthn\PublicKeyCredential::class, 'json' );
			$creation_options_payload    = $this->creation_options_to_array( $this->build_registration_creation_options( $user, $client_data_validation['challenge'] ) );
			$creation_options_object     = $serializer->denormalize(
				$creation_options_payload['publicKey'],
				\Webauthn\PublicKeyCredentialCreationOptions::class,
				'json'
			);
			$ceremony_factory = new \Webauthn\CeremonyStep\CeremonyStepManagerFactory();

			$ceremony_factory->setAllowedOrigins( array( $this->get_origin() ) );
			$ceremony_factory->setAttestationStatementSupportManager( $attestation_support_manager );

			$validator         = \Webauthn\AuthenticatorAttestationResponseValidator::create( $ceremony_factory->creationCeremony() );
			$credential_record = $validator->check(
				$public_key_credential->response,
				$creation_options_object,
				$this->get_relying_party_id()
			);
		} catch ( Throwable $throwable ) {
			return new WP_Error(
				'registration_verification_failed',
				$throwable->getMessage(),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				)
			);
		}

		$payload       = $this->normalize_verified_registration_payload( $credential_record, $response );
		$credential_id = $payload['credential_id'];

		if ( null !== $this->passkey_repository->get_passkey_by_credential_id( $credential_id ) ) {
			return new WP_Error(
				'credential_already_registered',
				__( 'This passkey is already registered.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				)
			);
		}

		$passkey_id = $this->passkey_repository->create_passkey(
			array_merge(
				$payload,
				array(
					'user_id' => $user->ID,
				)
			)
		);

		if ( false === $passkey_id ) {
			return new WP_Error(
				'passkey_save_failed',
				__( 'The verified passkey could not be saved.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				)
			);
		}

		if ( ! $this->challenge_service->mark_used( absint( $challenge_row->id ) ) ) {
			$this->passkey_repository->deactivate_passkey( $passkey_id, $user->ID );

			return new WP_Error(
				'challenge_mark_used_failed',
				__( 'The registration challenge could not be finalized.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				)
			);
		}

		$this->log_repository->add_log(
			array(
				'event_type' => 'registration_success',
				'user_id'    => $user->ID,
				'passkey_id' => $passkey_id,
				'message'    => 'Passkey registration completed successfully.',
			)
		);

		return array(
			'passkey_id'  => absint( $passkey_id ),
			'device_name' => $payload['device_name'],
			'created_at'  => current_time( 'mysql' ),
		);
	}

	public function validate_registration_response_for_identity( array $response, $user_name, $display_name, $user_handle, $exclude_user_id = 0 ) {
		if ( ! $this->is_available() ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_webauthn_unavailable',
				__( 'WebAuthn dependencies are not available yet.', 'ventraconnect-social-login' )
			);
		}

		$user_name    = is_string( $user_name ) ? trim( $user_name ) : '';
		$display_name = is_string( $display_name ) ? trim( $display_name ) : '';
		$user_handle  = is_string( $user_handle ) ? trim( $user_handle ) : '';

		if ( '' === $user_name || '' === $display_name || '' === $user_handle ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_invalid_registration_identity',
				__( 'A verified registration identity is required.', 'ventraconnect-social-login' )
			);
		}

		$shape = $this->get_registration_response_shape( $response );

		if (
			empty( $shape['has_id'] ) ||
			empty( $shape['has_raw_id'] ) ||
			empty( $shape['has_client_data_json'] ) ||
			empty( $shape['has_attestation_object'] ) ||
			empty( $shape['has_type'] )
		) {
			return new WP_Error( 'invalid_credential_shape', __( 'The registration credential payload is incomplete.', 'ventraconnect-social-login' ), $shape );
		}

		if ( 'public-key' !== $shape['type'] ) {
			return new WP_Error( 'invalid_credential_type', __( 'The registration credential type is invalid.', 'ventraconnect-social-login' ), $shape );
		}

		$client_data_json = $this->base64url_decode( $shape['client_data_json'] );

		if ( false === $client_data_json ) {
			return new WP_Error( 'invalid_client_data_json', __( 'The registration client data could not be decoded.', 'ventraconnect-social-login' ), $shape );
		}

		$client_data = json_decode( $client_data_json, true );

		if ( ! is_array( $client_data ) ) {
			return new WP_Error( 'invalid_client_data_json', __( 'The registration client data is not valid JSON.', 'ventraconnect-social-login' ), $shape );
		}

		$client_data_validation = $this->validate_registration_client_data( $client_data );

		if ( is_wp_error( $client_data_validation ) ) {
			return $client_data_validation;
		}

		$challenge_row = $this->challenge_service->validate_challenge(
			$client_data_validation['challenge'],
			VentraConnect_SL_Passkeys_Core_Challenge_Service::TYPE_REGISTRATION
		);

		if ( ! is_object( $challenge_row ) ) {
			return new WP_Error( 'invalid_registration_challenge', __( 'The registration challenge could not be found or is no longer valid.', 'ventraconnect-social-login' ) );
		}

		try {
			$attestation_support_manager = $this->get_attestation_statement_support_manager();
			$serializer                  = ( new \Webauthn\Denormalizer\WebauthnSerializerFactory( $attestation_support_manager ) )->create();
			$public_key_credential       = $serializer->denormalize( $response, \Webauthn\PublicKeyCredential::class, 'json' );
			$creation_options_payload    = $this->creation_options_to_array(
				$this->build_registration_creation_options_for_identity(
					$user_name,
					$display_name,
					$user_handle,
					(string) $client_data_validation['challenge'],
					absint( $exclude_user_id )
				)
			);
			$creation_options_object     = $serializer->denormalize(
				$creation_options_payload['publicKey'],
				\Webauthn\PublicKeyCredentialCreationOptions::class,
				'json'
			);
			$ceremony_factory            = new \Webauthn\CeremonyStep\CeremonyStepManagerFactory();

			$ceremony_factory->setAllowedOrigins( array( $this->get_origin() ) );
			$ceremony_factory->setAttestationStatementSupportManager( $attestation_support_manager );

			$validator         = \Webauthn\AuthenticatorAttestationResponseValidator::create( $ceremony_factory->creationCeremony() );
			$credential_record = $validator->check(
				$public_key_credential->response,
				$creation_options_object,
				$this->get_relying_party_id()
			);
		} catch ( Throwable $throwable ) {
			return new WP_Error( 'registration_verification_failed', $throwable->getMessage() );
		}

		$credential_id = $this->base64url_encode( $credential_record->publicKeyCredentialId );

		if ( null !== $this->passkey_repository->get_passkey_by_credential_id( $credential_id ) ) {
			return new WP_Error( 'credential_already_registered', __( 'This passkey is already registered.', 'ventraconnect-social-login' ) );
		}

		if ( ! $this->challenge_service->mark_used( absint( $challenge_row->id ?? 0 ) ) ) {
			return new WP_Error( 'challenge_mark_used_failed', __( 'The registration challenge could not be finalized.', 'ventraconnect-social-login' ) );
		}

		return $this->normalize_verified_registration_payload( $credential_record, $response );
	}

	public function extract_registration_challenge_from_response( array $response ) {
		$shape = $this->get_registration_response_shape( $response );

		if ( empty( $shape['has_client_data_json'] ) ) {
			return new WP_Error( 'invalid_client_data_json', __( 'The registration client data could not be decoded.', 'ventraconnect-social-login' ) );
		}

		$client_data_json = $this->base64url_decode( $shape['client_data_json'] );

		if ( false === $client_data_json ) {
			return new WP_Error( 'invalid_client_data_json', __( 'The registration client data could not be decoded.', 'ventraconnect-social-login' ) );
		}

		$client_data = json_decode( $client_data_json, true );

		if ( ! is_array( $client_data ) ) {
			return new WP_Error( 'invalid_client_data_json', __( 'The registration client data is not valid JSON.', 'ventraconnect-social-login' ) );
		}

		$validation = $this->validate_registration_client_data( $client_data );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		return isset( $validation['challenge'] ) ? (string) $validation['challenge'] : '';
	}

	public function generate_authentication_options( $user_id = null ) {
		if ( ! $this->is_available() ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_webauthn_unavailable',
				__( 'WebAuthn dependencies are not available yet.', 'ventraconnect-social-login' )
			);
		}

		$user_id = null === $user_id ? null : absint( $user_id );

		if ( null === $user_id || $user_id <= 0 ) {
			return new WP_Error(
				'user_required_for_authentication_options',
				__( 'User is required for authentication options.', 'ventraconnect-social-login' )
			);
		}

		$user      = get_user_by( 'id', $user_id );
		$challenge = $this->challenge_service->create_authentication_challenge( $user_id );

		if ( ! $user instanceof WP_User ) {
			return new WP_Error( 'ventraconnect_sl_passkeys_user_not_found', __( 'The requested user could not be found.', 'ventraconnect-social-login' ) );
		}

		if ( '' === $this->get_relying_party_id() || '' === $this->get_origin() ) {
			return new WP_Error( 'ventraconnect_sl_passkeys_invalid_relying_party', __( 'WebAuthn relying party configuration is incomplete.', 'ventraconnect-social-login' ) );
		}

		if ( false === $challenge ) {
			return new WP_Error( 'ventraconnect_sl_passkeys_authentication_challenge_failed', __( 'Unable to create an authentication challenge.', 'ventraconnect-social-login' ) );
		}

		try {
			$options = $this->build_authentication_request_options( $user, $challenge );
		} catch ( Throwable $throwable ) {
			return new WP_Error( 'ventraconnect_sl_passkeys_authentication_options_error', $throwable->getMessage() );
		}

		return $this->request_options_to_array( $options );
	}

	public function generate_discoverable_authentication_options() {
		if ( ! $this->is_available() ) {
			return new WP_Error(
				'ventraconnect_sl_passkeys_webauthn_unavailable',
				__( 'WebAuthn dependencies are not available yet.', 'ventraconnect-social-login' )
			);
		}

		$rp_id     = $this->get_relying_party_id();
		$origin    = $this->get_origin();
		$challenge = $this->challenge_service->create_authentication_challenge( null );

		if ( '' === $rp_id || '' === $origin ) {
			return new WP_Error( 'ventraconnect_sl_passkeys_invalid_relying_party', __( 'WebAuthn relying party configuration is incomplete.', 'ventraconnect-social-login' ) );
		}

		if ( false === $challenge ) {
			return new WP_Error( 'ventraconnect_sl_passkeys_authentication_challenge_failed', __( 'Unable to create an authentication challenge.', 'ventraconnect-social-login' ) );
		}

		try {
			$options = $this->build_discoverable_authentication_request_options( $challenge );
		} catch ( Throwable $throwable ) {
			return new WP_Error( 'ventraconnect_sl_passkeys_authentication_options_error', $throwable->getMessage() );
		}

		return $this->request_options_to_array( $options );
	}

	public function verify_authentication_response( array $response, $user_id = null ) {
		if ( null === $user_id ) {
			return new WP_Error(
				'user_required_for_authentication_verification',
				__( 'User is required for authentication verification.', 'ventraconnect-social-login' )
			);
		}

		return $this->perform_authentication_verification( $response, absint( $user_id ), false );
	}

	public function verify_public_authentication_response( array $response ) {
		return $this->perform_authentication_verification( $response, null, true );
	}

	public function verify_discoverable_authentication_response( array $response ) {
		return $this->perform_authentication_verification( $response, null, true );
	}

	public function get_dependencies() {
		return $this->dependencies;
	}

	public function get_vendor_loader() {
		return $this->vendor_loader;
	}

	public function get_challenge_service() {
		return $this->challenge_service;
	}

	public function get_passkey_repository() {
		return $this->passkey_repository;
	}

	public function get_log_repository() {
		return $this->log_repository;
	}

	protected function normalize_user_handle( $user_id ) {
		return 'wp-user-' . absint( $user_id );
	}

	protected function get_existing_credential_descriptors_for_user( $user_id ) {
		$passkeys    = $this->passkey_repository->get_user_passkeys( absint( $user_id ) );
		$descriptors = array();

		foreach ( $passkeys as $passkey ) {
			$credential_id = isset( $passkey->credential_id ) ? (string) $passkey->credential_id : '';

			if ( '' === $credential_id ) {
				continue;
			}

			$decoded_credential_id = $this->base64url_decode( $credential_id );

			if ( false === $decoded_credential_id || '' === $decoded_credential_id ) {
				continue;
			}

			$transports = array();
			if ( isset( $passkey->transports ) && '' !== (string) $passkey->transports ) {
				$decoded_transports = json_decode( (string) $passkey->transports, true );
				if ( is_array( $decoded_transports ) ) {
					$transports = array_values(
						array_filter(
							array_map( 'sanitize_text_field', $decoded_transports ),
							static function( $transport ) {
								return '' !== $transport;
							}
						)
					);
				}
			}

			$descriptors[] = \Webauthn\PublicKeyCredentialDescriptor::create(
				\Webauthn\PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
				$decoded_credential_id,
				$transports
			);
		}

		return $descriptors;
	}

	protected function creation_options_to_array( $options ) {
		$public_key = array(
			'challenge'              => (string) $options->challenge,
			'rp'                     => array(
				'name' => (string) $options->rp->name,
				'id'   => (string) $options->rp->id,
			),
			'user'                   => array(
				'id'          => rtrim( strtr( base64_encode( $options->user->id ), '+/', '-_' ), '=' ),
				'name'        => (string) $options->user->name,
				'displayName' => (string) $options->user->displayName,
			),
			'pubKeyCredParams'       => array(),
			'timeout'                => $options->timeout,
			'authenticatorSelection' => null,
			'attestation'            => $options->attestation,
			'excludeCredentials'     => array(),
		);

		foreach ( $options->pubKeyCredParams as $parameter ) {
			$public_key['pubKeyCredParams'][] = array(
				'type' => (string) $parameter->type,
				'alg'  => (int) $parameter->alg,
			);
		}

		if ( $options->authenticatorSelection instanceof \Webauthn\AuthenticatorSelectionCriteria ) {
			$public_key['authenticatorSelection'] = array_filter(
				array(
					'authenticatorAttachment' => $options->authenticatorSelection->authenticatorAttachment,
					'residentKey'             => $options->authenticatorSelection->residentKey,
					'requireResidentKey'      => $options->authenticatorSelection->requireResidentKey,
					'userVerification'        => $options->authenticatorSelection->userVerification,
				),
				static function( $value ) {
					return null !== $value;
				}
			);

			if (
				isset( $public_key['authenticatorSelection']['residentKey'] ) &&
				\Webauthn\AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED === $public_key['authenticatorSelection']['residentKey'] &&
				! isset( $public_key['authenticatorSelection']['requireResidentKey'] )
			) {
				$public_key['authenticatorSelection']['requireResidentKey'] = true;
			}
		}

		foreach ( $options->excludeCredentials as $descriptor ) {
			$credential = array(
				'type' => (string) $descriptor->type,
				'id'   => $this->base64url_encode( (string) $descriptor->id ),
			);

			if ( ! empty( $descriptor->transports ) ) {
				$credential['transports'] = array_values( $descriptor->transports );
			}

			$public_key['excludeCredentials'][] = $credential;
		}

		return array(
			'publicKey' => array_filter(
				$public_key,
				static function( $value ) {
					return null !== $value;
				}
			),
		);
	}

	protected function request_options_to_array( $options ) {
		$public_key = array(
			'challenge'        => (string) $options->challenge,
			'rpId'             => (string) $options->rpId,
			'allowCredentials' => array(),
			'userVerification' => $options->userVerification,
			'timeout'          => $options->timeout,
		);

		foreach ( $options->allowCredentials as $descriptor ) {
			$credential = array(
				'type' => (string) $descriptor->type,
				'id'   => $this->base64url_encode( (string) $descriptor->id ),
			);

			if ( ! empty( $descriptor->transports ) ) {
				$credential['transports'] = array_values( $descriptor->transports );
			}

			$public_key['allowCredentials'][] = $credential;
		}

		return array(
			'publicKey' => array_filter(
				$public_key,
				static function( $value ) {
					return null !== $value;
				}
			),
		);
	}

	protected function build_registration_creation_options( WP_User $user, $challenge ) {
		$user_name         = is_email( $user->user_email ) ? $user->user_email : $user->user_login;
		$user_display_name = '' !== (string) $user->display_name ? (string) $user->display_name : $user->user_login;

		$rp_entity = \Webauthn\PublicKeyCredentialRpEntity::create(
			$this->get_relying_party_name(),
			$this->get_relying_party_id()
		);

		$user_entity = \Webauthn\PublicKeyCredentialUserEntity::create(
			(string) $user_name,
			$this->normalize_user_handle( $user->ID ),
			(string) $user_display_name
		);

		$authenticator_selection = \Webauthn\AuthenticatorSelectionCriteria::create(
			\Webauthn\AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
			\Webauthn\AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
			\Webauthn\AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
		);

		return \Webauthn\PublicKeyCredentialCreationOptions::create(
			$rp_entity,
			$user_entity,
			(string) $challenge,
			array(
				\Webauthn\PublicKeyCredentialParameters::createPk( -7 ),
				\Webauthn\PublicKeyCredentialParameters::createPk( -257 ),
			),
			$authenticator_selection,
			\Webauthn\PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
			$this->get_existing_credential_descriptors_for_user( $user->ID ),
			60000
		);
	}

	protected function build_registration_creation_options_for_identity( $user_name, $display_name, $user_handle, $challenge, $exclude_user_id = 0 ) {
		$rp_entity = \Webauthn\PublicKeyCredentialRpEntity::create(
			$this->get_relying_party_name(),
			$this->get_relying_party_id()
		);

		$user_entity = \Webauthn\PublicKeyCredentialUserEntity::create(
			(string) $user_name,
			(string) $user_handle,
			(string) $display_name
		);

		$authenticator_selection = \Webauthn\AuthenticatorSelectionCriteria::create(
			\Webauthn\AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
			\Webauthn\AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
			\Webauthn\AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
		);

		return \Webauthn\PublicKeyCredentialCreationOptions::create(
			$rp_entity,
			$user_entity,
			(string) $challenge,
			array(
				\Webauthn\PublicKeyCredentialParameters::createPk( -7 ),
				\Webauthn\PublicKeyCredentialParameters::createPk( -257 ),
			),
			$authenticator_selection,
			\Webauthn\PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
			$exclude_user_id > 0 ? $this->get_allow_credentials_for_user( $exclude_user_id ) : array(),
			60000
		);
	}

	protected function get_allow_credentials_for_user( $user_id ) {
		return $this->get_existing_credential_descriptors_for_user( $user_id );
	}

	protected function build_authentication_request_options( WP_User $user, $challenge ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return \Webauthn\PublicKeyCredentialRequestOptions::create(
			(string) $challenge,
			$this->get_relying_party_id(),
			$this->get_allow_credentials_for_user( $user->ID ),
			\Webauthn\PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
			60000
		);
	}

	protected function build_discoverable_authentication_request_options( $challenge ) {
		return \Webauthn\PublicKeyCredentialRequestOptions::create(
			(string) $challenge,
			$this->get_relying_party_id(),
			array(),
			\Webauthn\PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
			60000
		);
	}

	protected function base64url_decode( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( '' === $value || ! preg_match( '/^[A-Za-z0-9\-_]+$/', $value ) ) {
			return false;
		}

		$normalized = strtr( $value, '-_', '+/' );
		$padding    = strlen( $normalized ) % 4;

		if ( $padding > 0 ) {
			$normalized .= str_repeat( '=', 4 - $padding );
		}

		return base64_decode( $normalized, true );
	}

	protected function base64url_encode( $value ) {
		return rtrim( strtr( base64_encode( (string) $value ), '+/', '-_' ), '=' );
	}

	protected function normalize_base64url_string( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( '' === $value ) {
			return '';
		}

		$value = sanitize_text_field( $value );
		$value = str_replace( array( '+', '/' ), array( '-', '_' ), $value );

		return rtrim( $value, '=' );
	}

	protected function validate_registration_client_data( array $client_data ) {
		$challenge       = isset( $client_data['challenge'] ) ? sanitize_text_field( (string) $client_data['challenge'] ) : '';
		$type            = isset( $client_data['type'] ) ? sanitize_text_field( (string) $client_data['type'] ) : '';
		$origin          = isset( $client_data['origin'] ) ? esc_url_raw( (string) $client_data['origin'] ) : '';
		$expected_origin = $this->get_origin();
		$origin_match    = '' !== $origin && '' !== $expected_origin && strtolower( $origin ) === strtolower( $expected_origin );

		if ( '' === $challenge ) {
			return new WP_Error(
				'invalid_registration_challenge',
				__( 'The registration challenge is missing from the client data.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => false,
					'origin_match'    => $origin_match,
				)
			);
		}

		if ( 'webauthn.create' !== $type ) {
			return new WP_Error(
				'invalid_client_data_type',
				__( 'The registration client data type is invalid.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => false,
					'origin_match'    => $origin_match,
				)
			);
		}

		if ( ! $origin_match ) {
			return new WP_Error(
				'invalid_registration_origin',
				__( 'The registration origin is invalid.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => false,
					'origin_match'    => false,
				)
			);
		}

		return array(
			'challenge' => $challenge,
			'origin'    => $origin,
			'type'      => $type,
		);
	}

	protected function validate_authentication_client_data( array $client_data ) {
		$challenge       = isset( $client_data['challenge'] ) ? sanitize_text_field( (string) $client_data['challenge'] ) : '';
		$type            = isset( $client_data['type'] ) ? sanitize_text_field( (string) $client_data['type'] ) : '';
		$origin          = isset( $client_data['origin'] ) ? esc_url_raw( (string) $client_data['origin'] ) : '';
		$expected_origin = $this->get_origin();
		$origin_match    = '' !== $origin && '' !== $expected_origin && strtolower( $origin ) === strtolower( $expected_origin );

		if ( '' === $challenge ) {
			return new WP_Error(
				'invalid_authentication_challenge',
				__( 'The authentication challenge is missing from the client data.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => false,
					'origin_match'    => $origin_match,
				)
			);
		}

		if ( 'webauthn.get' !== $type ) {
			return new WP_Error(
				'invalid_client_data_type',
				__( 'The authentication client data type is invalid.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => false,
					'origin_match'    => $origin_match,
				)
			);
		}

		if ( ! $origin_match ) {
			return new WP_Error(
				'invalid_authentication_origin',
				__( 'The authentication origin is invalid.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => false,
					'origin_match'    => false,
				)
			);
		}

		return array(
			'challenge' => $challenge,
			'origin'    => $origin,
			'type'      => $type,
		);
	}

	protected function extract_transports( array $response ) {
		$response_data = isset( $response['response'] ) && is_array( $response['response'] ) ? $response['response'] : array();
		$transports    = isset( $response_data['transports'] ) && is_array( $response_data['transports'] ) ? $response_data['transports'] : array();

		return array_values(
			array_filter(
				array_map( 'sanitize_text_field', $transports ),
				static function( $transport ) {
					return '' !== $transport;
				}
			)
		);
	}

	protected function get_default_device_name( $authenticator_attachment ) {
		return 'platform' === sanitize_text_field( $authenticator_attachment )
			? __( 'Windows Hello / Platform Passkey', 'ventraconnect-social-login' )
			: __( 'Passkey', 'ventraconnect-social-login' );
	}

	protected function normalize_trust_path_for_storage( $trust_path ) {
		if ( null === $trust_path ) {
			return null;
		}

		try {
			$attestation_support_manager = $this->get_attestation_statement_support_manager();
			$serializer                  = ( new \Webauthn\Denormalizer\WebauthnSerializerFactory( $attestation_support_manager ) )->create();
			$normalized                  = $serializer->normalize( $trust_path, 'json' );
		} catch ( Throwable $throwable ) {
			return null;
		}

		return is_array( $normalized ) ? wp_json_encode( $normalized ) : null;
	}

	protected function bool_or_null( $value ) {
		if ( null === $value ) {
			return null;
		}

		return $value ? 1 : 0;
	}

	protected function normalize_verified_registration_payload( $credential_record, array $response ) {
		$authenticator_attachment = isset( $response['authenticatorAttachment'] ) ? sanitize_text_field( (string) $response['authenticatorAttachment'] ) : '';
		$device_name              = $this->get_default_device_name( $authenticator_attachment );

		return array(
			'credential_id'    => $this->base64url_encode( $credential_record->publicKeyCredentialId ),
			'credential_type'  => sanitize_text_field( $credential_record->type ),
			'public_key'       => $this->base64url_encode( $credential_record->credentialPublicKey ),
			'sign_count'       => absint( $credential_record->counter ),
			'user_handle'      => sanitize_text_field( $credential_record->userHandle ),
			'aaguid'           => sanitize_text_field( (string) $credential_record->aaguid ),
			'attestation_type' => sanitize_text_field( $credential_record->attestationType ),
			'trust_path'       => $this->normalize_trust_path_for_storage( $credential_record->trustPath ),
			'transports'       => $this->extract_transports( $response ),
			'backup_eligible'  => $this->bool_or_null( $credential_record->backupEligible ),
			'backup_status'    => $this->bool_or_null( $credential_record->backupStatus ),
			'uv_initialized'   => $this->bool_or_null( $credential_record->uvInitialized ),
			'device_name'      => $device_name,
			'is_active'        => 1,
		);
	}

	protected function get_registration_response_shape( array $response ) {
		$response_data      = isset( $response['response'] ) && is_array( $response['response'] ) ? $response['response'] : array();
		$credential_id      = isset( $response['id'] ) && is_string( $response['id'] ) ? trim( $response['id'] ) : '';
		$credential_raw_id  = isset( $response['rawId'] ) && is_string( $response['rawId'] ) ? trim( $response['rawId'] ) : '';
		$credential_type    = isset( $response['type'] ) && is_string( $response['type'] ) ? trim( $response['type'] ) : '';
		$client_data_json   = isset( $response_data['clientDataJSON'] ) && is_string( $response_data['clientDataJSON'] ) ? trim( $response_data['clientDataJSON'] ) : '';
		$attestation_object = isset( $response_data['attestationObject'] ) && is_string( $response_data['attestationObject'] ) ? trim( $response_data['attestationObject'] ) : '';

		return array(
			'has_id'                 => '' !== $credential_id,
			'has_raw_id'             => '' !== $credential_raw_id,
			'has_type'               => '' !== $credential_type,
			'has_client_data_json'   => '' !== $client_data_json,
			'has_attestation_object' => '' !== $attestation_object,
			'type'                   => $credential_type,
			'client_data_json'       => $client_data_json,
			'attestation_object'     => $attestation_object,
		);
	}

	protected function get_authentication_response_shape( array $response ) {
		$response_data      = isset( $response['response'] ) && is_array( $response['response'] ) ? $response['response'] : array();
		$credential_id      = isset( $response['id'] ) && is_string( $response['id'] ) ? trim( $response['id'] ) : '';
		$credential_raw_id  = isset( $response['rawId'] ) && is_string( $response['rawId'] ) ? trim( $response['rawId'] ) : '';
		$credential_type    = isset( $response['type'] ) && is_string( $response['type'] ) ? trim( $response['type'] ) : '';
		$client_data_json   = isset( $response_data['clientDataJSON'] ) && is_string( $response_data['clientDataJSON'] ) ? trim( $response_data['clientDataJSON'] ) : '';
		$authenticator_data = isset( $response_data['authenticatorData'] ) && is_string( $response_data['authenticatorData'] ) ? trim( $response_data['authenticatorData'] ) : '';
		$signature          = isset( $response_data['signature'] ) && is_string( $response_data['signature'] ) ? trim( $response_data['signature'] ) : '';
		$user_handle        = isset( $response_data['userHandle'] ) && is_string( $response_data['userHandle'] ) ? trim( $response_data['userHandle'] ) : '';

		return array(
			'has_id'                 => '' !== $credential_id,
			'has_raw_id'             => '' !== $credential_raw_id,
			'has_type'               => '' !== $credential_type,
			'has_client_data_json'   => '' !== $client_data_json,
			'has_authenticator_data' => '' !== $authenticator_data,
			'has_signature'          => '' !== $signature,
			'has_user_handle'        => '' !== $user_handle,
			'id'                     => $credential_id,
			'raw_id'                 => $credential_raw_id,
			'type'                   => $credential_type,
			'client_data_json'       => $client_data_json,
			'authenticator_data'     => $authenticator_data,
			'signature'              => $signature,
			'user_handle'            => $user_handle,
		);
	}

	protected function reconstruct_credential_record_from_passkey( $passkey, $serializer ) {
		$trust_path = array();

		if ( isset( $passkey->trust_path ) && '' !== (string) $passkey->trust_path ) {
			$decoded_trust_path = json_decode( (string) $passkey->trust_path, true );

			if ( is_array( $decoded_trust_path ) ) {
				$trust_path = $decoded_trust_path;
			}
		}

		$payload = array(
			'publicKeyCredentialId' => isset( $passkey->credential_id ) ? (string) $passkey->credential_id : '',
			'type'                  => isset( $passkey->credential_type ) && '' !== (string) $passkey->credential_type ? (string) $passkey->credential_type : 'public-key',
			'transports'            => $this->decode_transports_for_record( $passkey->transports ?? '' ),
			'attestationType'       => isset( $passkey->attestation_type ) ? (string) $passkey->attestation_type : '',
			'trustPath'             => $trust_path,
			'aaguid'                => isset( $passkey->aaguid ) ? (string) $passkey->aaguid : '00000000-0000-0000-0000-000000000000',
			'credentialPublicKey'   => isset( $passkey->public_key ) ? (string) $passkey->public_key : '',
			'userHandle'            => isset( $passkey->user_handle ) ? $this->base64url_encode( (string) $passkey->user_handle ) : '',
			'counter'               => isset( $passkey->sign_count ) ? absint( $passkey->sign_count ) : 0,
			'backupEligible'        => $this->nullable_bool_from_db( $passkey->backup_eligible ?? null ),
			'backupStatus'          => $this->nullable_bool_from_db( $passkey->backup_status ?? null ),
			'uvInitialized'         => $this->nullable_bool_from_db( $passkey->uv_initialized ?? null ),
		);

		return $serializer->denormalize(
			$payload,
			\Webauthn\CredentialRecord::class,
			'json'
		);
	}

	protected function decode_transports_for_record( $transports_json ) {
		$transports_json = is_string( $transports_json ) ? $transports_json : '';

		if ( '' === $transports_json ) {
			return array();
		}

		$decoded = json_decode( $transports_json, true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'sanitize_text_field', $decoded ),
				static function( $transport ) {
					return '' !== $transport;
				}
			)
		);
	}

	protected function nullable_bool_from_db( $value ) {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return (bool) absint( $value );
	}

	protected function build_authentication_error( $code, $message, array $details = array(), $user_id = null, $passkey_id = null, $public = false ) {
		if ( null !== $user_id ) {
			$details['user_id'] = absint( $user_id );
		}

		if ( null !== $passkey_id ) {
			$details['passkey_id'] = absint( $passkey_id );
		}

		if ( $public ) {
			$details['public'] = true;
		}

		return new WP_Error( sanitize_key( $code ), (string) $message, $details );
	}

	protected function perform_authentication_verification( array $response, $expected_user_id = null, $public = false ) {
		if ( ! $this->is_available() ) {
			return $this->build_authentication_error(
				'ventraconnect_sl_passkeys_webauthn_unavailable',
				__( 'WebAuthn dependencies are not available yet.', 'ventraconnect-social-login' ),
				array(),
				null,
				null,
				$public
			);
		}

		$expected_user_id = null !== $expected_user_id ? absint( $expected_user_id ) : null;

		if ( ! $public && ( null === $expected_user_id || $expected_user_id <= 0 ) ) {
			return $this->build_authentication_error(
				'ventraconnect_sl_passkeys_invalid_user_id',
				__( 'A valid user ID is required for authentication verification.', 'ventraconnect-social-login' ),
				array(),
				$expected_user_id,
				null,
				$public
			);
		}

		$shape = $this->get_authentication_response_shape( $response );

		if (
			empty( $shape['has_id'] ) ||
			empty( $shape['has_raw_id'] ) ||
			empty( $shape['has_client_data_json'] ) ||
			empty( $shape['has_authenticator_data'] ) ||
			empty( $shape['has_signature'] ) ||
			empty( $shape['has_type'] )
		) {
			return $this->build_authentication_error( 'invalid_assertion_shape', __( 'The authentication assertion payload is incomplete.', 'ventraconnect-social-login' ), $shape, $expected_user_id, null, $public );
		}

		if ( 'public-key' !== $shape['type'] ) {
			return $this->build_authentication_error( 'invalid_assertion_type', __( 'The authentication assertion type is invalid.', 'ventraconnect-social-login' ), $shape, $expected_user_id, null, $public );
		}

		$client_data_json = $this->base64url_decode( $shape['client_data_json'] );

		if ( false === $client_data_json ) {
			return $this->build_authentication_error( 'invalid_client_data_json', __( 'The authentication client data could not be decoded.', 'ventraconnect-social-login' ), $shape, $expected_user_id, null, $public );
		}

		if ( false === $this->base64url_decode( $shape['authenticator_data'] ) ) {
			return $this->build_authentication_error( 'invalid_authenticator_data', __( 'The authentication authenticator data could not be decoded.', 'ventraconnect-social-login' ), $shape, $expected_user_id, null, $public );
		}

		if ( false === $this->base64url_decode( $shape['signature'] ) ) {
			return $this->build_authentication_error( 'invalid_signature', __( 'The authentication signature could not be decoded.', 'ventraconnect-social-login' ), $shape, $expected_user_id, null, $public );
		}

		if ( ! empty( $shape['user_handle'] ) && false === $this->base64url_decode( $shape['user_handle'] ) ) {
			return $this->build_authentication_error( 'invalid_user_handle', __( 'The authentication user handle could not be decoded.', 'ventraconnect-social-login' ), $shape, $expected_user_id, null, $public );
		}

		$client_data = json_decode( $client_data_json, true );

		if ( ! is_array( $client_data ) ) {
			return $this->build_authentication_error( 'invalid_client_data_json', __( 'The authentication client data is not valid JSON.', 'ventraconnect-social-login' ), $shape, $expected_user_id, null, $public );
		}

		$client_data_validation = $this->validate_authentication_client_data( $client_data );

		if ( is_wp_error( $client_data_validation ) ) {
			return $this->build_authentication_error(
				$client_data_validation->get_error_code(),
				$client_data_validation->get_error_message(),
				is_array( $client_data_validation->get_error_data() ) ? $client_data_validation->get_error_data() : array(),
				$expected_user_id,
				null,
				$public
			);
		}

		$challenge_row = $this->challenge_service->validate_challenge(
			$client_data_validation['challenge'],
			VentraConnect_SL_Passkeys_Core_Challenge_Service::TYPE_AUTHENTICATION
		);

		if ( ! is_object( $challenge_row ) ) {
			return $this->build_authentication_error(
				'invalid_authentication_challenge',
				__( 'The authentication challenge could not be found or is no longer valid.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => false,
					'origin_match'    => true,
				),
				$expected_user_id,
				null,
				$public
			);
		}

		$normalized_raw_id = $this->normalize_base64url_string( $response['rawId'] ?? '' );
		$normalized_id     = $this->normalize_base64url_string( $response['id'] ?? '' );
		$passkey           = null;

		if ( '' !== $normalized_raw_id ) {
			$passkey = $this->passkey_repository->get_passkey_by_credential_id( $normalized_raw_id );
		}

		if ( ! is_object( $passkey ) && '' !== $normalized_id && $normalized_id !== $normalized_raw_id ) {
			$passkey = $this->passkey_repository->get_passkey_by_credential_id( $normalized_id );
		}

		if ( ! is_object( $passkey ) ) {
			return $this->build_authentication_error(
				'passkey_not_found',
				__( 'The requested passkey could not be found.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				),
				$expected_user_id,
				null,
				$public
			);
		}

		$user_id = absint( $passkey->user_id ?? 0 );
		$user    = $user_id > 0 ? get_user_by( 'id', $user_id ) : false;

		if ( ! $user instanceof WP_User ) {
			return $this->build_authentication_error(
				'ventraconnect_sl_passkeys_user_not_found',
				__( 'The requested user could not be found.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				),
				$user_id,
				absint( $passkey->id ?? 0 ),
				$public
			);
		}

		$challenge_user_id = absint( $challenge_row->user_id ?? 0 );

		if ( $challenge_user_id > 0 && $challenge_user_id !== $user_id ) {
			return $this->build_authentication_error(
				'passkey_user_mismatch',
				__( 'The requested passkey does not belong to the expected user.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				),
				$user_id,
				absint( $passkey->id ?? 0 ),
				$public
			);
		}

		try {
			$attestation_support_manager = $this->get_attestation_statement_support_manager();
			$serializer                  = ( new \Webauthn\Denormalizer\WebauthnSerializerFactory( $attestation_support_manager ) )->create();
			$public_key_credential       = $serializer->denormalize( $response, \Webauthn\PublicKeyCredential::class, 'json' );
			$request_options_payload     = $this->request_options_to_array(
				$public ? $this->build_discoverable_authentication_request_options( $client_data_validation['challenge'] ) : $this->build_authentication_request_options( $user, $client_data_validation['challenge'] )
			);
			$request_options_object      = $serializer->denormalize(
				$request_options_payload['publicKey'],
				\Webauthn\PublicKeyCredentialRequestOptions::class,
				'json'
			);
			$credential_record           = $this->reconstruct_credential_record_from_passkey( $passkey, $serializer );
			$ceremony_factory            = new \Webauthn\CeremonyStep\CeremonyStepManagerFactory();

			$ceremony_factory->setAllowedOrigins( array( $this->get_origin() ) );

			$validator       = \Webauthn\AuthenticatorAssertionResponseValidator::create( $ceremony_factory->requestCeremony() );
			$verified_record = $validator->check(
				$credential_record,
				$public_key_credential->response,
				$request_options_object,
				$this->get_relying_party_id(),
				$credential_record->userHandle
			);
		} catch ( Throwable $throwable ) {
			return $this->build_authentication_error(
				'authentication_verification_failed',
				$throwable->getMessage(),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				),
				$user_id,
				absint( $passkey->id ?? 0 ),
				$public
			);
		}

		if ( ! $this->challenge_service->mark_used( absint( $challenge_row->id ?? 0 ) ) ) {
			return $this->build_authentication_error(
				'challenge_mark_used_failed',
				__( 'The authentication challenge could not be finalized.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				),
				$user_id,
				absint( $passkey->id ?? 0 ),
				$public
			);
		}

		$updated = $this->passkey_repository->update_authentication_metadata(
			absint( $passkey->id ?? 0 ),
			$user_id,
			absint( $verified_record->counter ),
			$this->bool_or_null( $verified_record->backupEligible ),
			$this->bool_or_null( $verified_record->backupStatus ),
			$this->bool_or_null( $verified_record->uvInitialized )
		);

		if ( ! $updated ) {
			return $this->build_authentication_error(
				'passkey_update_failed',
				__( 'The verified passkey usage metadata could not be updated.', 'ventraconnect-social-login' ),
				array(
					'challenge_found' => true,
					'origin_match'    => true,
				),
				$user_id,
				absint( $passkey->id ?? 0 ),
				$public
			);
		}

		$last_used_at = current_time( 'mysql' );

		$this->log_repository->add_log(
			array(
				'event_type' => $public ? 'discoverable_authentication_success' : 'authentication_success',
				'user_id'    => $user_id,
				'passkey_id' => absint( $passkey->id ?? 0 ),
				'message'    => $public ? 'Discoverable passkey authentication verified successfully.' : 'Passkey authentication verified successfully.',
			)
		);

		return array(
			'user_id'      => $user_id,
			'passkey_id'   => absint( $passkey->id ?? 0 ),
			'last_used_at' => $last_used_at,
		);
	}

	protected function get_attestation_statement_support_manager() {
		$algorithm_manager = \Cose\Algorithm\Manager::create()->add(
			\Cose\Algorithm\Signature\ECDSA\ES256::create(),
			\Cose\Algorithm\Signature\RSA\RS256::create()
		);

		return new \Webauthn\AttestationStatement\AttestationStatementSupportManager(
			array(
				new \Webauthn\AttestationStatement\NoneAttestationStatementSupport(),
				new \Webauthn\AttestationStatement\PackedAttestationStatementSupport( $algorithm_manager ),
				new \Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport(),
				new \Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport(),
				new \Webauthn\AttestationStatement\AppleAttestationStatementSupport(),
				new \Webauthn\AttestationStatement\TPMAttestationStatementSupport(),
			)
		);
	}
}
