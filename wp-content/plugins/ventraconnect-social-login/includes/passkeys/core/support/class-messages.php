<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Messages {

	public static function get( $key ) {
		$key      = is_string( $key ) ? sanitize_key( $key ) : '';
		$messages = self::get_default_messages();

		return isset( $messages[ $key ] ) ? (string) $messages[ $key ] : '';
	}

	public static function get_default_messages() {
		return array(
			'admin_profile_passkeys_title'          => __( 'Passkeys', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_intro'          => __( 'Manage the passkeys you use to sign in to this WordPress account.', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_summary_title'  => __( 'Active Passkeys', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_none'           => __( 'No active passkeys found.', 'ventraconnect-social-login' ),
			/* translators: %d: number of active passkeys. */
			'admin_profile_passkeys_count'          => __( 'Active passkeys: %d', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_view_only_note' => __( 'For security, users must add new passkeys from their own account.', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_runtime_note'   => __( 'Use your device passkey, Face ID, fingerprint, Windows Hello, or security key for future sign-ins.', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_list_title'     => __( 'Registered Passkeys', 'ventraconnect-social-login' ),
			/* translators: %s: passkey creation date. */
			'admin_profile_passkeys_created'        => __( 'Created: %s', 'ventraconnect-social-login' ),
			/* translators: %s: passkey last-used date. */
			'admin_profile_passkeys_last_used'      => __( 'Last used: %s', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_never'          => __( 'Never', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_unknown'        => __( 'Unknown', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_default_name'   => __( 'Passkey', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_add_button'     => __( 'Add Passkey', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_add_another_button' => __( 'Add another passkey', 'ventraconnect-social-login' ),
			'admin_profile_passkeys_remove_button'  => __( 'Remove', 'ventraconnect-social-login' ),
			'users_column_no'                       => __( 'No', 'ventraconnect-social-login' ),
			/* translators: %d: number of active passkeys. */
			'users_column_yes_count'                => __( 'Yes (%d)', 'ventraconnect-social-login' ),
			/* translators: %s: passkey last-used date. */
			'users_column_last_used'                => __( 'Last used %s', 'ventraconnect-social-login' ),
			'profile_passkey_unsupported'           => __( 'This browser or device does not support passkeys.', 'ventraconnect-social-login' ),
			'profile_passkey_add_loading'           => __( 'Preparing passkey registration...', 'ventraconnect-social-login' ),
			'profile_passkey_add_prompt'            => __( 'Follow your browser or device prompts to create a passkey.', 'ventraconnect-social-login' ),
			'profile_passkey_add_success_reload'    => __( 'Passkey registered successfully. Updating your passkey list...', 'ventraconnect-social-login' ),
			'profile_passkey_add_failed'            => __( 'Passkey creation was cancelled or failed.', 'ventraconnect-social-login' ),
			'profile_passkey_cancelled'             => __( 'Passkey setup was cancelled or timed out. Please try again when you are ready.', 'ventraconnect-social-login' ),
			'profile_passkey_remove_confirm'        => __( 'Remove this passkey?', 'ventraconnect-social-login' ),
			'profile_passkey_remove_loading'        => __( 'Removing passkey...', 'ventraconnect-social-login' ),
			'profile_passkey_remove_success_reload' => __( 'Passkey removed. Updating your passkey list...', 'ventraconnect-social-login' ),
			'profile_passkey_remove_failed'         => __( 'The passkey could not be removed.', 'ventraconnect-social-login' ),
			'public_passkey_title'                  => __( 'Continue with Passkey', 'ventraconnect-social-login' ),
			'public_passkey_login_intro'            => __( 'Use a saved passkey from this device to sign in without a password.', 'ventraconnect-social-login' ),
			'public_passkey_register_intro'         => __( 'Create a passkey for this account after verifying your email address.', 'ventraconnect-social-login' ),
			'public_passkey_register_button'        => __( 'Set up Passkey', 'ventraconnect-social-login' ),
			'public_passkey_or_separator'           => __( 'or', 'ventraconnect-social-login' ),
			'public_passkey_unsupported'            => __( 'This browser or device does not support passkeys.', 'ventraconnect-social-login' ),
			'public_passkey_login_loading'          => __( 'Preparing passkey login...', 'ventraconnect-social-login' ),
			'public_passkey_login_prompt'           => __( 'Follow your browser or device prompts to continue.', 'ventraconnect-social-login' ),
			'public_passkey_login_success'          => __( 'Passkey login successful. Redirecting...', 'ventraconnect-social-login' ),
			'public_passkey_login_failed'           => __( 'Passkey login failed. Please try again or use another sign-in method.', 'ventraconnect-social-login' ),
			'public_passkey_verify_failed'          => __( 'Passkey login could not be verified. Please try again or use another sign-in method.', 'ventraconnect-social-login' ),
			'public_passkey_no_passkey_found'       => __( 'No passkey was found or selected. Use another sign-in method, then add a passkey from your profile.', 'ventraconnect-social-login' ),
			'public_passkey_email_required'         => __( 'Please enter your email address.', 'ventraconnect-social-login' ),
			'public_passkey_verify_email_to_continue' => __( 'Check your email to continue setting up your passkey.', 'ventraconnect-social-login' ),
			'public_passkey_verify_email_to_add'    => __( 'Check your email to verify your account before adding a passkey.', 'ventraconnect-social-login' ),
			'public_passkey_existing_user_has_passkey' => __( 'An account already exists for this email and already has a passkey. Use Continue with Passkey on the login form to sign in.', 'ventraconnect-social-login' ),
			'public_passkey_existing_account_requires_login' => __( 'An account already exists with this email address. Please login with Magic Link or Email OTP, then create a passkey from your account.', 'ventraconnect-social-login' ),
			'public_passkey_existing_user_needs_verification' => __( 'An account already exists for this email. Use Magic Link, Email OTP, or another login method to sign in, then add a passkey from your profile.', 'ventraconnect-social-login' ),
			'public_passkey_duplicate_email'        => __( 'An account already exists for this email. Please sign in instead of creating another account.', 'ventraconnect-social-login' ),
			'public_passkey_register_loading'       => __( 'Preparing passkey setup...', 'ventraconnect-social-login' ),
			'public_passkey_register_failed'        => __( 'Passkey setup could not be completed. Please try again.', 'ventraconnect-social-login' ),
			'public_passkey_register_cancelled'     => __( 'Passkey setup was cancelled. No passkey was added. Please try again when you are ready.', 'ventraconnect-social-login' ),
			'public_passkey_setup_cancelled_or_timeout' => __( 'Passkey setup was cancelled or timed out. Please try again when you are ready.', 'ventraconnect-social-login' ),
			'public_passkey_email_subject'          => __( 'Confirm your email to create a passkey', 'ventraconnect-social-login' ),
			'public_passkey_email_intro'            => __( 'Someone requested to set up a passkey for this email address.', 'ventraconnect-social-login' ),
			'public_passkey_email_button'           => __( 'Continue setting up your passkey', 'ventraconnect-social-login' ),
			'public_passkey_email_expiry'           => __( 'This link expires in 30 minutes.', 'ventraconnect-social-login' ),
			'public_passkey_email_ignore'           => __( 'If you did not request this, you can ignore this email.', 'ventraconnect-social-login' ),
			'public_passkey_verification_link_expired' => __( 'This passkey verification link has expired. Please start again.', 'ventraconnect-social-login' ),
			'public_passkey_verification_invalid'   => __( 'This passkey verification link is invalid. Please start again.', 'ventraconnect-social-login' ),
			'public_passkey_verified_title'         => __( 'Create your passkey', 'ventraconnect-social-login' ),
			'public_passkey_verified_intro'         => __( 'Your email has been verified. Create a passkey to finish setting up your account.', 'ventraconnect-social-login' ),
			'public_passkey_verified_button'        => __( 'Create Passkey', 'ventraconnect-social-login' ),
			'public_passkey_verified_loading'       => __( 'Preparing passkey registration...', 'ventraconnect-social-login' ),
			'public_passkey_verified_prompt'        => __( 'Create a passkey for this account. Your device will save it securely so you can sign in without a password next time.', 'ventraconnect-social-login' ),
			'public_passkey_verified_success'       => __( 'Passkey registration successful. Redirecting...', 'ventraconnect-social-login' ),
			'public_passkey_back_to_login'          => __( 'Back to login', 'ventraconnect-social-login' ),
		);
	}
}
