<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_User_Profile {

	protected $manage_panel;

	public function __construct( $manage_panel = null ) {
		$this->manage_panel = $manage_panel instanceof VentraConnect_SL_Passkeys_Manage_Panel ? $manage_panel : new VentraConnect_SL_Passkeys_Manage_Panel();
	}

	public function register_hooks() {
		add_action( 'show_user_profile', array( $this, 'render_profile_passkeys_section' ) );
		add_action( 'edit_user_profile', array( $this, 'render_profile_passkeys_section' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_profile_assets' ) );
	}

	public function render_profile_passkeys_section( $user ) {
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$viewed_user_id  = absint( $user->ID );
		$current_user_id = get_current_user_id();

		if ( $viewed_user_id <= 0 || ! current_user_can( 'edit_user', $viewed_user_id ) ) {
			return;
		}

		$is_own_profile = $viewed_user_id === $current_user_id;

		echo '<h2>' . esc_html( VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_title' ) ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody><tr>';
		echo '<th scope="row">' . esc_html( VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_title' ) ) . '</th>';
		echo '<td>';

		if ( $is_own_profile ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Manage panel returns controlled admin HTML and handles escaping internally.
			echo $this->manage_panel->render(
				$viewed_user_id,
				array(
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Message is passed to Manage_Panel::render(), which escapes note content with esc_html() at output.
					'intro_note'        => VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_intro' ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Message is passed to Manage_Panel::render(), which escapes note content with esc_html() at output.
					'runtime_note'      => VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_runtime_note' ),
					'show_runtime_note' => true,
					'is_own_profile'    => true,
					'wrapper_class'     => 'ventraconnect-sl-passkeys-profile-own',
				)
			);
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Manage panel returns controlled admin HTML and handles escaping internally.
			echo $this->manage_panel->render(
				$viewed_user_id,
				array(
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Message is passed to Manage_Panel::render(), which escapes note content with esc_html() at output.
					'intro_note'      => VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_view_only_note' ),
					'show_view_only'  => false,
					'is_own_profile'  => false,
					'wrapper_class'   => 'ventraconnect-sl-passkeys-profile-view-only',
				)
			);
		}

		echo '</td></tr></tbody></table>';
	}

	public function enqueue_profile_assets( $hook_suffix ) {
		$hook_suffix     = is_string( $hook_suffix ) ? $hook_suffix : '';
		$current_user_id = get_current_user_id();
		$viewed_user_id  = 0;

		if ( 'profile.php' === $hook_suffix ) {
			$viewed_user_id = $current_user_id;
		} elseif (
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only standard user-edit screen parameter, normalized with absint() and restricted to the current user's own profile below.
			'user-edit.php' === $hook_suffix && isset( $_GET['user_id'] )
		) {
			$viewed_user_id = absint( wp_unslash( $_GET['user_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only standard user-edit screen parameter, normalized with absint() and restricted to the current user's own profile below.
		}

		if ( $current_user_id <= 0 || $viewed_user_id <= 0 || $viewed_user_id !== $current_user_id ) {
			return;
		}

		wp_enqueue_style(
			'ventraconnect-sl-passkeys-profile',
			VENTRACONNECT_SL_PLUGIN_URL . 'assets/css/passkeys-profile.css',
			array(),
			VENTRACONNECT_SL_VERSION
		);

		wp_enqueue_script(
			'ventraconnect-sl-passkeys-profile',
			VENTRACONNECT_SL_PLUGIN_URL . 'assets/js/passkeys-profile.js',
			array(),
			VENTRACONNECT_SL_VERSION,
			true
		);

		wp_localize_script(
			'ventraconnect-sl-passkeys-profile',
			'ventraConnectSlPasskeysProfile',
			array(
				'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
				'registrationOptionsAction' => VentraConnect_SL_Passkeys_Management_Ajax::REGISTRATION_OPTIONS_ACTION,
				'registrationOptionsNonce'  => wp_create_nonce( VentraConnect_SL_Passkeys_Management_Ajax::REGISTRATION_OPTIONS_NONCE_ACTION ),
				'verifyRegistrationAction'  => VentraConnect_SL_Passkeys_Management_Ajax::VERIFY_REGISTRATION_ACTION,
				'verifyRegistrationNonce'   => wp_create_nonce( VentraConnect_SL_Passkeys_Management_Ajax::VERIFY_REGISTRATION_NONCE_ACTION ),
				'removePasskeyAction'       => VentraConnect_SL_Passkeys_Management_Ajax::REMOVE_PASSKEY_ACTION,
				'removePasskeyNonce'        => wp_create_nonce( VentraConnect_SL_Passkeys_Management_Ajax::REMOVE_PASSKEY_NONCE_ACTION ),
				'unsupportedMessage'        => VentraConnect_SL_Passkeys_Messages::get( 'profile_passkey_unsupported' ),
				'addLoadingMessage'         => VentraConnect_SL_Passkeys_Messages::get( 'profile_passkey_add_loading' ),
				'addPromptMessage'          => VentraConnect_SL_Passkeys_Messages::get( 'profile_passkey_add_prompt' ),
				'addSuccessReloadMessage'   => VentraConnect_SL_Passkeys_Messages::get( 'profile_passkey_add_success_reload' ),
				'addFailedMessage'          => VentraConnect_SL_Passkeys_Messages::get( 'profile_passkey_add_failed' ),
				'addCancelledMessage'       => VentraConnect_SL_Passkeys_Messages::get( 'profile_passkey_cancelled' ),
				'removeConfirmMessage'      => VentraConnect_SL_Passkeys_Messages::get( 'profile_passkey_remove_confirm' ),
				'removeLoadingMessage'      => VentraConnect_SL_Passkeys_Messages::get( 'profile_passkey_remove_loading' ),
				'removeSuccessReloadMessage'=> VentraConnect_SL_Passkeys_Messages::get( 'profile_passkey_remove_success_reload' ),
				'removeFailedMessage'       => VentraConnect_SL_Passkeys_Messages::get( 'profile_passkey_remove_failed' ),
			)
		);
	}
}
