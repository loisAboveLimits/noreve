<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free native logged-in passkey management AJAX controller.
 *
 * Important:
 * - Logged-in profile management only.
 * - No public/discoverable login actions are registered here.
 * - This file is loaded only behind the PHP 8.2 support gate.
 */
class VentraConnect_SL_Passkeys_Management_Ajax {

	const REGISTRATION_OPTIONS_ACTION = 'ventraconnect_sl_passkeys_registration_options';
	const REGISTRATION_OPTIONS_NONCE_ACTION = 'ventraconnect_sl_passkeys_registration_options';
	const VERIFY_REGISTRATION_ACTION = 'ventraconnect_sl_passkeys_verify_registration';
	const VERIFY_REGISTRATION_NONCE_ACTION = 'ventraconnect_sl_passkeys_verify_registration';
	const REMOVE_PASSKEY_ACTION = 'ventraconnect_sl_passkeys_remove_passkey';
	const REMOVE_PASSKEY_NONCE_ACTION = 'ventraconnect_sl_passkeys_remove_passkey';

	protected $webauthn_service;
	protected $passkey_repository;
	protected $log_repository;
	protected $challenge_service;

	public function __construct( $webauthn_service = null, $passkey_repository = null, $log_repository = null, $challenge_service = null ) {
		$this->webauthn_service   = $webauthn_service instanceof VentraConnect_SL_Passkeys_Core_WebAuthn_Service ? $webauthn_service : new VentraConnect_SL_Passkeys_Core_WebAuthn_Service();
		$this->passkey_repository = $passkey_repository instanceof VentraConnect_SL_Passkeys_Core_Passkey_Repository ? $passkey_repository : new VentraConnect_SL_Passkeys_Core_Passkey_Repository();
		$this->log_repository     = $log_repository instanceof VentraConnect_SL_Passkeys_Core_Log_Repository ? $log_repository : new VentraConnect_SL_Passkeys_Core_Log_Repository();
		$this->challenge_service  = $challenge_service instanceof VentraConnect_SL_Passkeys_Core_Challenge_Service ? $challenge_service : new VentraConnect_SL_Passkeys_Core_Challenge_Service();
	}

	public function register_hooks() {
		add_action( 'wp_ajax_' . self::REGISTRATION_OPTIONS_ACTION, array( $this, 'handle_registration_options' ) );
		add_action( 'wp_ajax_' . self::VERIFY_REGISTRATION_ACTION, array( $this, 'handle_verify_registration' ) );
		add_action( 'wp_ajax_' . self::REMOVE_PASSKEY_ACTION, array( $this, 'handle_remove_passkey' ) );
	}

	public function handle_registration_options() {
		$this->verify_nonce_or_error( self::REGISTRATION_OPTIONS_NONCE_ACTION );
		$this->require_logged_in_user();

		$options = $this->webauthn_service->generate_registration_options( get_current_user_id() );

		if ( is_wp_error( $options ) ) {
			$error_data = array(
				'code'    => $options->get_error_code(),
				'message' => $options->get_error_message(),
			);

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$error_data['diagnostics'] = $this->webauthn_service->get_dependencies()->get_health_status();
			}

			wp_send_json_error( $error_data, 400 );
		}

		wp_send_json_success(
			array(
				'options' => $options,
			)
		);
	}

	public function handle_verify_registration() {
		$this->verify_nonce_or_error( self::VERIFY_REGISTRATION_NONCE_ACTION );
		$this->require_logged_in_user();

		$credential_json = isset( $_POST['credential'] ) ? wp_unslash( $_POST['credential'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification occurs immediately above via verify_nonce_or_error(); raw WebAuthn JSON is decoded and structurally validated below.

		if ( ! is_string( $credential_json ) || '' === $credential_json ) {
			wp_send_json_error(
				array(
					'code'    => 'missing_credential',
					'message' => __( 'A registration credential is required.', 'ventraconnect-social-login' ),
				),
				400
			);
		}

		$credential = json_decode( $credential_json, true );

		if ( ! is_array( $credential ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_credential_json',
					'message' => __( 'The registration credential payload is invalid.', 'ventraconnect-social-login' ),
				),
				400
			);
		}

		$result = $this->webauthn_service->verify_registration_response( get_current_user_id(), $credential );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
					'details' => is_array( $result->get_error_data() ) ? $result->get_error_data() : array(),
				),
				400
			);
		}

		$this->challenge_service->cleanup_expired();

		wp_send_json_success(
			array(
				'message' => __( 'Passkey registered successfully.', 'ventraconnect-social-login' ),
				'passkey' => $result,
			)
		);
	}

	public function handle_remove_passkey() {
		$this->verify_nonce_or_error( self::REMOVE_PASSKEY_NONCE_ACTION );
		$this->require_logged_in_user();

		$passkey_id = isset( $_POST['passkey_id'] ) ? absint( wp_unslash( $_POST['passkey_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification occurs immediately above via verify_nonce_or_error().

		if ( $passkey_id <= 0 ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_passkey',
					'message' => __( 'A valid passkey is required.', 'ventraconnect-social-login' ),
				),
				400
			);
		}

		$current_user_id = get_current_user_id();
		$passkey         = $this->passkey_repository->get_passkey_by_id( $passkey_id );

		if ( ! is_object( $passkey ) || absint( $passkey->user_id ) !== $current_user_id ) {
			wp_send_json_error(
				array(
					'code'    => 'not_allowed',
					'message' => __( 'You can only remove passkeys that belong to your own account.', 'ventraconnect-social-login' ),
				),
				403
			);
		}

		$removed = $this->passkey_repository->deactivate_passkey( $passkey_id, $current_user_id );

		if ( ! $removed ) {
			wp_send_json_error(
				array(
					'code'    => 'remove_failed',
					'message' => __( 'The passkey could not be removed.', 'ventraconnect-social-login' ),
				),
				400
			);
		}

		$this->challenge_service->cleanup_unused_challenges_for_user( $current_user_id );
		$this->challenge_service->cleanup_expired();

		$this->log_repository->add_log(
			array(
				'event_type' => 'passkey_deactivated',
				'user_id'    => $current_user_id,
				'passkey_id' => $passkey_id,
				'message'    => 'Passkey deactivated by the user.',
			)
		);

		wp_send_json_success(
			array(
				'message'    => __( 'Passkey removed from this site. If your device still offers it, remove it from your device passkey settings too.', 'ventraconnect-social-login' ),
				'passkey_id' => $passkey_id,
			)
		);
	}

	protected function verify_nonce_or_error( $nonce_action ) {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_nonce',
					'message' => __( 'Security check failed.', 'ventraconnect-social-login' ),
				),
				403
			);
		}
	}

	protected function require_logged_in_user() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'code'    => 'not_logged_in',
					'message' => __( 'You must be logged in to manage passkeys.', 'ventraconnect-social-login' ),
				),
				401
			);
		}
	}
}
