<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free native WordPress passkeys public AJAX controller.
 *
 * Important:
 * - Native wp-login/wp-register only.
 * - No Woo/LMS/membership contexts or public email-only auth.
 * - Loaded only behind the PHP 8.2 passkeys support gate.
 */
class VentraConnect_SL_Passkeys_Public_Ajax {

	const DISCOVERABLE_AUTHENTICATION_OPTIONS_ACTION = 'ventraconnect_sl_passkeys_discoverable_authentication_options';
	const DISCOVERABLE_AUTHENTICATION_OPTIONS_NONCE_ACTION = 'ventraconnect_sl_passkeys_discoverable_authentication_options';
	const DISCOVERABLE_VERIFY_AUTHENTICATION_ACTION = 'ventraconnect_sl_passkeys_discoverable_verify_authentication';
	const DISCOVERABLE_VERIFY_AUTHENTICATION_NONCE_ACTION = 'ventraconnect_sl_passkeys_discoverable_verify_authentication';
	const START_EMAIL_VERIFICATION_ACTION = 'ventraconnect_sl_passkeys_start_email_verification';
	const START_EMAIL_VERIFICATION_NONCE_ACTION = 'ventraconnect_sl_passkeys_start_email_verification';
	const VERIFIED_REGISTRATION_OPTIONS_ACTION = 'ventraconnect_sl_passkeys_verified_registration_options';
	const VERIFIED_REGISTRATION_OPTIONS_NONCE_ACTION = 'ventraconnect_sl_passkeys_verified_registration_options';
	const VERIFIED_VERIFY_REGISTRATION_ACTION = 'ventraconnect_sl_passkeys_verified_verify_registration';
	const VERIFIED_VERIFY_REGISTRATION_NONCE_ACTION = 'ventraconnect_sl_passkeys_verified_verify_registration';

	protected $webauthn_service;
	protected $passkey_repository;
	protected $log_repository;
	protected $challenge_service;
	protected $redirect_resolver;
	protected $email_verification;

	public function __construct( $webauthn_service = null, $passkey_repository = null, $log_repository = null, $challenge_service = null, $redirect_resolver = null, $email_verification = null ) {
		$this->webauthn_service   = $webauthn_service instanceof VentraConnect_SL_Passkeys_Core_WebAuthn_Service ? $webauthn_service : new VentraConnect_SL_Passkeys_Core_WebAuthn_Service();
		$this->passkey_repository = $passkey_repository instanceof VentraConnect_SL_Passkeys_Core_Passkey_Repository ? $passkey_repository : new VentraConnect_SL_Passkeys_Core_Passkey_Repository();
		$this->log_repository     = $log_repository instanceof VentraConnect_SL_Passkeys_Core_Log_Repository ? $log_repository : new VentraConnect_SL_Passkeys_Core_Log_Repository();
		$this->challenge_service  = $challenge_service instanceof VentraConnect_SL_Passkeys_Core_Challenge_Service ? $challenge_service : new VentraConnect_SL_Passkeys_Core_Challenge_Service();
		$this->redirect_resolver  = $redirect_resolver instanceof VentraConnect_SL_Passkeys_Core_Redirect_Resolver ? $redirect_resolver : new VentraConnect_SL_Passkeys_Core_Redirect_Resolver();
		$this->email_verification = $email_verification instanceof VentraConnect_SL_Passkeys_Email_Verification ? $email_verification : new VentraConnect_SL_Passkeys_Email_Verification( $this->redirect_resolver );
	}

	public function register_hooks() {
		add_action( 'wp_ajax_' . self::DISCOVERABLE_AUTHENTICATION_OPTIONS_ACTION, array( $this, 'handle_discoverable_authentication_options' ) );
		add_action( 'wp_ajax_nopriv_' . self::DISCOVERABLE_AUTHENTICATION_OPTIONS_ACTION, array( $this, 'handle_discoverable_authentication_options' ) );
		add_action( 'wp_ajax_' . self::DISCOVERABLE_VERIFY_AUTHENTICATION_ACTION, array( $this, 'handle_discoverable_verify_authentication' ) );
		add_action( 'wp_ajax_nopriv_' . self::DISCOVERABLE_VERIFY_AUTHENTICATION_ACTION, array( $this, 'handle_discoverable_verify_authentication' ) );
		add_action( 'wp_ajax_' . self::START_EMAIL_VERIFICATION_ACTION, array( $this, 'handle_start_email_verification' ) );
		add_action( 'wp_ajax_nopriv_' . self::START_EMAIL_VERIFICATION_ACTION, array( $this, 'handle_start_email_verification' ) );
		add_action( 'wp_ajax_' . self::VERIFIED_REGISTRATION_OPTIONS_ACTION, array( $this, 'handle_verified_registration_options' ) );
		add_action( 'wp_ajax_nopriv_' . self::VERIFIED_REGISTRATION_OPTIONS_ACTION, array( $this, 'handle_verified_registration_options' ) );
		add_action( 'wp_ajax_' . self::VERIFIED_VERIFY_REGISTRATION_ACTION, array( $this, 'handle_verified_verify_registration' ) );
		add_action( 'wp_ajax_nopriv_' . self::VERIFIED_VERIFY_REGISTRATION_ACTION, array( $this, 'handle_verified_verify_registration' ) );
	}

	public function handle_discoverable_authentication_options() {
		$this->verify_nonce_or_error( self::DISCOVERABLE_AUTHENTICATION_OPTIONS_NONCE_ACTION );
		$this->require_public_login_enabled();

		if ( $this->is_discoverable_authentication_options_rate_limited() ) {
			wp_send_json_error(
				array(
					'code'    => 'rate_limited',
					'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_login_failed' ),
				),
				429
			);
		}

		$options = $this->webauthn_service->generate_discoverable_authentication_options();

		if ( is_wp_error( $options ) ) {
			wp_send_json_error(
				array(
					'code'    => sanitize_key( $options->get_error_code() ),
					'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_login_failed' ),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				'options' => $options,
			)
		);
	}

	public function handle_discoverable_verify_authentication() {
		$this->verify_nonce_or_error( self::DISCOVERABLE_VERIFY_AUTHENTICATION_NONCE_ACTION );
		$this->require_public_login_enabled();

		$assertion_json = isset( $_POST['assertion'] ) ? wp_unslash( $_POST['assertion'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified immediately above; raw WebAuthn assertion JSON is decoded and structurally validated below.
		$context        = $this->redirect_resolver->sanitize_context( isset( $_POST['context'] ) ? wp_unslash( $_POST['context'] ) : 'wp_login' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified immediately above; context is sanitized through Redirect_Resolver::sanitize_context().
		$redirect_url   = isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified immediately above; redirect is sanitized here and validated by the redirect resolver.

		if ( ! is_string( $assertion_json ) || '' === $assertion_json ) {
			wp_send_json_error(
				array(
					'code'    => 'missing_assertion',
					'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verify_failed' ),
				),
				400
			);
		}

		$assertion = json_decode( $assertion_json, true );

		if ( ! is_array( $assertion ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_assertion_json',
					'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verify_failed' ),
				),
				400
			);
		}

		$result = $this->webauthn_service->verify_discoverable_authentication_response( $assertion );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'code'    => sanitize_key( $result->get_error_code() ),
					'message' => $result->get_error_code() === 'passkey_not_found'
						? VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_no_passkey_found' )
						: VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verify_failed' ),
				),
				400
			);
		}

		$user_id = isset( $result['user_id'] ) ? absint( $result['user_id'] ) : 0;
		$user    = $user_id > 0 ? get_user_by( 'id', $user_id ) : false;

		if ( ! $user instanceof WP_User ) {
			wp_send_json_error(
				array(
					'code'    => 'user_not_found',
					'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verify_failed' ),
				),
				400
			);
		}

		/**
		 * Allow integration layers to capture guest checkout/session state before the public
		 * passkey login mutates the current authenticated user context.
		 *
		 * @param int    $user_id      Authenticated user ID.
		 * @param string $context      Public passkey login context.
		 * @param string $redirect_url Requested redirect URL.
		 */
		do_action( 'ventraconnect_sl_passkeys_before_public_login', $user_id, $context, $redirect_url );

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WordPress login lifecycle hook invoked after passkey authentication succeeds.
		$this->emit_passkey_analytics_login_success( $user_id, $context, false );

		/**
		 * Allow integration layers to restore checkout/session state after the public passkey
		 * login succeeds and WordPress auth cookies are set.
		 *
		 * @param int    $user_id      Authenticated user ID.
		 * @param string $context      Public passkey login context.
		 * @param string $redirect_url Requested redirect URL.
		 */
		do_action( 'ventraconnect_sl_passkeys_after_public_login', $user_id, $context, $redirect_url );

		$this->challenge_service->cleanup_expired();

		wp_send_json_success(
			array(
				'message'      => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_login_success' ),
				'redirect_url' => $this->redirect_resolver->resolve( $user_id, (string) $redirect_url, $context ),
				'user_id'      => $user_id,
				'passkey_id'   => isset( $result['passkey_id'] ) ? absint( $result['passkey_id'] ) : 0,
			)
		);
	}

	public function handle_start_email_verification() {
		$this->verify_nonce_or_error( self::START_EMAIL_VERIFICATION_NONCE_ACTION );
		$this->require_public_registration_enabled();

		$email        = isset( $_POST['email'] ) ? strtolower( sanitize_email( trim( wp_unslash( (string) $_POST['email'] ) ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified immediately above.
		$display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified immediately above.
		$username     = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ), true ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified immediately above.
		$redirect_url = isset( $_POST['redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified immediately above; redirect is sanitized here and validated by the redirect resolver.

		if ( '' === $email || ! is_email( $email ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_email',
					'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_email_required' ),
				),
				400
			);
		}

		if ( $this->is_start_email_verification_rate_limited( $email ) ) {
			wp_send_json_error(
				array(
					'code'    => 'rate_limited',
					'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_failed' ),
				),
				429
			);
		}

		$existing_user_id = email_exists( $email );
		$existing_user_id = $existing_user_id ? absint( $existing_user_id ) : 0;

		if ( $existing_user_id > 0 ) {
			$this->send_start_email_verification_generic_success( $email );
		}

		if ( $this->has_start_email_verification_send_lock( $email ) ) {
			$this->send_start_email_verification_generic_success( $email );
		}

		$this->set_start_email_verification_send_lock( $email );

		$state = $this->email_verification->create_pending_verification(
			array(
				'email'            => $email,
				'context'          => 'wp_register_form',
				'action'           => 'create_new_user',
				'existing_user_id' => 0,
				'display_name'     => $display_name,
				'username'         => $username,
				'redirect_url'     => $redirect_url,
			)
		);

		if ( is_wp_error( $state ) || ! $this->email_verification->send_verification_email( $state ) ) {
			$this->clear_start_email_verification_send_lock( $email );
			wp_send_json_error(
				array(
					'code'    => 'verification_email_failed',
					'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_failed' ),
				),
				400
			);
		}

		$this->send_start_email_verification_generic_success( $email );
	}

	public function handle_verified_registration_options() {
		$this->verify_nonce_or_error( self::VERIFIED_REGISTRATION_OPTIONS_NONCE_ACTION );
		$this->require_public_registration_enabled();

		$token = isset( $_POST['verification_token'] ) ? sanitize_text_field( wp_unslash( $_POST['verification_token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified immediately above; token is sanitized and validated by the email-verification flow.
		$state = $this->email_verification->get_pending_verification( $token );

		if ( is_wp_error( $state ) ) {
			wp_send_json_error(
				array(
					'code'    => sanitize_key( $state->get_error_code() ),
					'message' => $state->get_error_message(),
				),
				400
			);
		}

		$exclude_user_id = isset( $state['existing_user_id'] ) ? absint( $state['existing_user_id'] ) : 0;
		$options         = $this->webauthn_service->generate_registration_options_for_identity(
			(string) $state['email'],
			$this->get_verified_registration_display_name( $state ),
			$this->email_verification->get_virtual_user_handle( $token ),
			$exclude_user_id
		);

		if ( is_wp_error( $options ) ) {
			wp_send_json_error(
				array(
					'code'    => sanitize_key( $options->get_error_code() ),
					'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_failed' ),
				),
				400
			);
		}

		if ( ! empty( $options['publicKey']['challenge'] ) && is_string( $options['publicKey']['challenge'] ) ) {
			$this->email_verification->bind_challenge_to_token( $token, $options['publicKey']['challenge'] );
		}

		wp_send_json_success(
			array(
				'options' => $options,
			)
		);
	}

	public function handle_verified_verify_registration() {
		$this->verify_nonce_or_error( self::VERIFIED_VERIFY_REGISTRATION_NONCE_ACTION );
		$this->require_public_registration_enabled();

		$token           = isset( $_POST['verification_token'] ) ? sanitize_text_field( wp_unslash( $_POST['verification_token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified immediately above; token is sanitized and validated by the email-verification flow.
		$credential_json = isset( $_POST['credential'] ) ? wp_unslash( $_POST['credential'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is verified immediately above; raw WebAuthn credential JSON is decoded and structurally validated below.
		$state           = $this->email_verification->get_pending_verification( $token );

		if ( is_wp_error( $state ) ) {
			wp_send_json_error(
				array(
					'code'    => sanitize_key( $state->get_error_code() ),
					'message' => $state->get_error_message(),
				),
				400
			);
		}

		if ( ! is_string( $credential_json ) || '' === $credential_json ) {
			$this->send_verified_registration_error();
		}

		$credential = json_decode( $credential_json, true );

		if ( ! is_array( $credential ) ) {
			$this->send_verified_registration_error();
		}

		$challenge = $this->webauthn_service->extract_registration_challenge_from_response( $credential );

		if ( is_wp_error( $challenge ) || ! $this->email_verification->is_challenge_bound_to_token( $token, (string) $challenge ) ) {
			$this->send_verified_registration_error();
		}

		$validated_passkey = $this->webauthn_service->validate_registration_response_for_identity(
			$credential,
			(string) $state['email'],
			$this->get_verified_registration_display_name( $state ),
			$this->email_verification->get_virtual_user_handle( $token ),
			isset( $state['existing_user_id'] ) ? absint( $state['existing_user_id'] ) : 0
		);

		if ( is_wp_error( $validated_passkey ) ) {
			$this->send_verified_registration_error();
		}

		$target_user_data = $this->resolve_verified_registration_target_user( $state );

		if ( is_wp_error( $target_user_data ) ) {
			wp_send_json_error(
				array(
					'code'    => sanitize_key( $target_user_data->get_error_code() ),
					'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_failed' ),
				),
				400
			);
		}

		$user_id                     = absint( $target_user_data['user_id'] );
		$created_new_user            = ! empty( $target_user_data['created_new_user'] );
		$validated_passkey['user_id'] = $user_id;
		$passkey_id                  = $this->passkey_repository->create_passkey( $validated_passkey );

		if ( false === $passkey_id ) {
			if ( $created_new_user ) {
				$this->delete_newly_created_verified_user( $user_id );
			}

			$this->send_verified_registration_error();
		}

		$this->log_repository->add_log(
			array(
				'event_type' => 'registration_success',
				'user_id'    => $user_id,
				'passkey_id' => $passkey_id,
				'message'    => 'Native public passkey registration completed successfully.',
			)
		);

		$this->email_verification->clear_bound_challenge( $token );
		$this->email_verification->consume_pending_verification( $token );
		$this->challenge_service->cleanup_expired();

		$user = get_user_by( 'id', $user_id );

		if ( ! $user instanceof WP_User ) {
			$this->send_verified_registration_error();
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WordPress login lifecycle hook invoked after passkey authentication succeeds.
		$this->emit_passkey_analytics_login_success( $user_id, 'wp_register_form', true );

		wp_send_json_success(
			array(
				'message'      => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verified_success' ),
				'redirect_url' => $this->email_verification->get_success_redirect_url( $state, $user_id ),
				'user_id'      => $user_id,
				'passkey_id'   => absint( $passkey_id ),
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

	/**
	 * Emit the shared VentraConnect login success hook for passkey analytics.
	 *
	 * Important:
	 * - Keep the payload minimal and non-sensitive.
	 * - Match the hook shape expected by Pro reports.
	 *
	 * @param int    $user_id     Authenticated user ID.
	 * @param string $context     Passkey context slug.
	 * @param bool   $is_new_user Whether this login followed new-account creation.
	 * @return void
	 */
	protected function emit_passkey_analytics_login_success( $user_id, $context, $is_new_user = false ) {
		$user_id = absint( $user_id );
		$context = sanitize_key( (string) $context );

		if ( $user_id <= 0 ) {
			return;
		}

		do_action(
			'ventraconnect_sl_login_success',
			$user_id,
			'passkey',
			array(
				'provider' => 'passkey',
				'method'   => 'passkey',
				'context'  => '' !== $context ? $context : 'wp_login',
			),
			(bool) $is_new_user
		);
	}

	protected function require_public_login_enabled() {
		if ( $this->is_public_login_enabled() ) {
			return;
		}

		wp_send_json_error(
			array(
				'code'    => 'passkeys_public_disabled',
				'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_login_failed' ),
			),
			403
		);
	}

	protected function require_public_registration_enabled() {
		if ( $this->is_public_registration_enabled() ) {
			return;
		}

		wp_send_json_error(
			array(
				'code'    => 'passkeys_registration_disabled',
				'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_failed' ),
			),
			403
		);
	}

	protected function is_passkey_public_runtime_enabled() {
		if ( ! defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) || ! VENTRACONNECT_PASSKEYS_CORE_SUPPORTED ) {
			return false;
		}

		if ( ! function_exists( 'ventraconnect_sl_is_passkey_method_enabled' ) || ! ventraconnect_sl_is_passkey_method_enabled() ) {
			return false;
		}

		return $this->webauthn_service->is_available();
	}

	protected function is_public_login_enabled() {
		if ( ! $this->is_passkey_public_runtime_enabled() ) {
			return false;
		}

		if ( ! class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
			return false;
		}

		$settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();

		return ! empty( $settings['wp_login_enabled'] );
	}

	protected function is_public_registration_enabled() {
		if ( ! $this->is_passkey_public_runtime_enabled() ) {
			return false;
		}

		if ( ! get_option( 'users_can_register' ) ) {
			return false;
		}

		if ( ! class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
			return false;
		}

		$settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();

		if ( empty( $settings['wp_register_enabled'] ) ) {
			return false;
		}

		return true;
	}

	protected function get_existing_user_by_email( $email ) {
		$email = sanitize_email( $email );

		if ( '' === $email || ! is_email( $email ) || ! email_exists( $email ) ) {
			return null;
		}

		$user = get_user_by( 'email', $email );

		return $user instanceof WP_User ? $user : null;
	}

	protected function get_verified_registration_display_name( array $state ) {
		return $this->email_verification->get_registration_display_name( $state );
	}

	protected function resolve_verified_registration_target_user( array $state ) {
		$action           = isset( $state['action'] ) ? sanitize_key( (string) $state['action'] ) : '';
		$email            = isset( $state['email'] ) ? sanitize_email( (string) $state['email'] ) : '';
		$context          = isset( $state['context'] ) ? sanitize_key( (string) $state['context'] ) : '';
		$existing_user_id = isset( $state['existing_user_id'] ) ? absint( $state['existing_user_id'] ) : 0;

		if ( 'add_passkey_to_existing_user' === $action ) {
			$user = get_user_by( 'id', $existing_user_id );

			if ( ! $user instanceof WP_User || sanitize_email( (string) $user->user_email ) !== $email ) {
				return new WP_Error( 'existing_user_not_available', __( 'This account is no longer available.', 'ventraconnect-social-login' ) );
			}

			return array(
				'user_id'          => $user->ID,
				'created_new_user' => false,
			);
		}

		$existing_user = $this->get_existing_user_by_email( $email );

		if ( $existing_user instanceof WP_User ) {
			return new WP_Error( 'duplicate_email_account_exists', __( 'An account already exists for this email.', 'ventraconnect-social-login' ) );
		}

		$user_id = $this->create_verified_registration_user(
			$email,
			$this->get_verified_registration_display_name( $state ),
			$context,
			isset( $state['username'] ) ? (string) $state['username'] : ''
		);

		if ( $user_id <= 0 ) {
			return new WP_Error( 'verified_user_creation_failed', __( 'The account could not be created.', 'ventraconnect-social-login' ) );
		}

		return array(
			'user_id'          => $user_id,
			'created_new_user' => true,
		);
	}

	protected function create_verified_registration_user( $email, $display_name = '', $context = 'wp_register_form', $username = '' ) {
		$email        = sanitize_email( $email );
		$display_name = sanitize_text_field( $display_name );
		$username     = sanitize_user( $username, true );
		$context      = sanitize_key( $context );

		if ( '' === $email || ! is_email( $email ) || $this->get_existing_user_by_email( $email ) instanceof WP_User ) {
			return 0;
		}

		if ( '' !== $username && ! validate_username( $username ) ) {
			return 0;
		}

		$login = '' !== $username ? $this->generate_unique_login_from_username( $username ) : $this->generate_unique_login_from_email( $email );

		if ( '' === $login ) {
			return 0;
		}

		if ( '' === $display_name ) {
			$display_name = sanitize_text_field( current( explode( '@', $email ) ) );
		}

		$default_role = get_option( 'default_role', 'subscriber' );
		$default_role = is_string( $default_role ) ? sanitize_key( $default_role ) : 'subscriber';

		$user_id = wp_insert_user(
			array(
				'user_login'   => $login,
				'user_pass'    => ventraconnect_sl_generate_internal_account_password(),
				'user_email'   => $email,
				'display_name' => '' !== $display_name ? $display_name : $login,
				'role'         => '' !== $default_role ? $default_role : 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) || ! $user_id ) {
			return 0;
		}

		$created_user = get_user_by( 'id', $user_id );

		if ( ! $created_user instanceof WP_User || sanitize_email( (string) $created_user->user_email ) !== $email ) {
			$this->delete_newly_created_verified_user( $user_id );
			return 0;
		}

		$existing_user = $this->get_existing_user_by_email( $email );

		if ( $existing_user instanceof WP_User && (int) $existing_user->ID !== (int) $user_id ) {
			$this->delete_newly_created_verified_user( $user_id );
			return 0;
		}

		ventraconnect_sl_mark_passwordless_account_created( (int) $user_id, 'passkey', $context );

		return absint( $user_id );
	}

	protected function delete_newly_created_verified_user( $user_id ) {
		$user_id = absint( $user_id );

		if ( $user_id <= 0 ) {
			return;
		}

		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		if ( function_exists( 'wp_delete_user' ) ) {
			wp_delete_user( $user_id );
		}
	}

	protected function generate_unique_login_from_email( $email ) {
		$base = sanitize_user( current( explode( '@', (string) $email ) ), true );

		if ( '' === $base ) {
			$base = 'passkey_user';
		}

		$login = $base;
		$index = 2;

		while ( username_exists( $login ) ) {
			$login = $base . '_' . $index;
			$index++;
		}

		return $login;
	}

	protected function generate_unique_login_from_username( $username ) {
		$base = sanitize_user( (string) $username, true );

		if ( '' === $base ) {
			return '';
		}

		$login = $base;
		$index = 2;

		while ( username_exists( $login ) ) {
			$login = $base . '_' . $index;
			$index++;
		}

		return $login;
	}

	protected function mask_email_hint( $email ) {
		$email = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			return '';
		}

		$parts  = explode( '@', $email, 2 );
		$local  = $parts[0];
		$domain = $parts[1];

		if ( strlen( $local ) <= 2 ) {
			$masked_local = substr( $local, 0, 1 ) . '*';
		} else {
			$masked_local = substr( $local, 0, 1 ) . str_repeat( '*', max( 1, strlen( $local ) - 2 ) ) . substr( $local, -1 );
		}

		return $masked_local . '@' . $domain;
	}

	protected function send_start_email_verification_generic_success( $email ) {
		wp_send_json_success(
			array(
				'message'    => __( 'If this email can create a passkey, we will send instructions to continue.', 'ventraconnect-social-login' ),
				'email_hint' => $this->mask_email_hint( $email ),
			)
		);
	}

	protected function is_discoverable_authentication_options_rate_limited() {
		$request_ip    = $this->get_request_ip();
		$transient_key = 'vcpk_free_disc_auth_' . md5( $request_ip );
		$attempt_count = (int) get_transient( $transient_key );
		$attempt_count++;

		set_transient( $transient_key, $attempt_count, 10 * MINUTE_IN_SECONDS );

		return $attempt_count > 10;
	}

	protected function is_start_email_verification_rate_limited( $email ) {
		$request_ip    = $this->get_request_ip();
		$normalized    = strtolower( sanitize_email( $email ) );
		$transient_key = 'vcpk_free_email_verify_' . md5( $request_ip . '|' . $normalized );
		$attempt_count = (int) get_transient( $transient_key );

		$attempt_count++;

		set_transient( $transient_key, $attempt_count, 15 * MINUTE_IN_SECONDS );

		return $attempt_count > 5;
	}

	protected function has_start_email_verification_send_lock( $email ) {
		return false !== get_transient( $this->get_start_email_verification_send_lock_key( $email ) );
	}

	protected function set_start_email_verification_send_lock( $email ) {
		set_transient( $this->get_start_email_verification_send_lock_key( $email ), 1, MINUTE_IN_SECONDS );
	}

	protected function clear_start_email_verification_send_lock( $email ) {
		delete_transient( $this->get_start_email_verification_send_lock_key( $email ) );
	}

	protected function get_start_email_verification_send_lock_key( $email ) {
		return 'ventraconnect_passkey_email_send_lock_' . md5( strtolower( sanitize_email( $email ) ) );
	}

	protected function get_request_ip() {
		if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}

	protected function send_verified_registration_error() {
		wp_send_json_error(
			array(
				'code'    => 'verified_registration_failed',
				'message' => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_failed' ),
			),
			400
		);
	}
}
