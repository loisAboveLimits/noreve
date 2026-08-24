<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free native WordPress passkeys public frontend.
 *
 * Important:
 * - Owns native wp-login/wp-register passkeys plus Free-safe shared page contexts.
 * - Advanced Woo/LMS/membership placement is handled by Pro.
 * - Loaded only behind the PHP 8.2 passkeys support gate.
 */
class VentraConnect_SL_Passkeys_Public_Frontend {

	protected $webauthn_service;
	protected $email_verification;
	protected $redirect_resolver;
	protected $assets_registered = false;

	public function __construct( $webauthn_service = null, $email_verification = null, $redirect_resolver = null ) {
		$this->webauthn_service   = $webauthn_service instanceof VentraConnect_SL_Passkeys_Core_WebAuthn_Service ? $webauthn_service : new VentraConnect_SL_Passkeys_Core_WebAuthn_Service();
		$this->redirect_resolver  = $redirect_resolver instanceof VentraConnect_SL_Passkeys_Core_Redirect_Resolver ? $redirect_resolver : new VentraConnect_SL_Passkeys_Core_Redirect_Resolver();
		$this->email_verification = $email_verification instanceof VentraConnect_SL_Passkeys_Email_Verification ? $email_verification : new VentraConnect_SL_Passkeys_Email_Verification( $this->redirect_resolver );
	}

	public function register_hooks() {
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'login_body_class', array( $this, 'filter_login_body_class' ) );
		add_action( 'login_form_' . VentraConnect_SL_Passkeys_Email_Verification::LOGIN_ACTION, array( $this, 'render_verified_passkey_setup_screen' ) );
	}

	public function register_assets() {
		if ( $this->assets_registered ) {
			return;
		}

		wp_register_style(
			'ventraconnect-sl-passkeys-public',
			VENTRACONNECT_SL_PLUGIN_URL . 'assets/css/passkeys-public.css',
			array(),
			VENTRACONNECT_SL_VERSION
		);

		wp_register_script(
			'ventraconnect-sl-passkeys-public',
			VENTRACONNECT_SL_PLUGIN_URL . 'assets/js/passkeys-public.js',
			array(),
			VENTRACONNECT_SL_VERSION,
			true
		);

		wp_localize_script(
			'ventraconnect-sl-passkeys-public',
			'ventraConnectSlPasskeysPublic',
			$this->get_client_config()
		);

		$this->assets_registered = true;
	}

	public function enqueue_assets() {
		if ( ! $this->should_render_any_public_ui() ) {
			return;
		}

		$this->register_assets();
		wp_enqueue_style( 'ventraconnect-sl-passkeys-public' );
		wp_enqueue_script( 'ventraconnect-sl-passkeys-public' );

		$verified_page_styles = $this->get_verified_page_branding_css();
		if ( '' !== $verified_page_styles ) {
			wp_add_inline_style( 'ventraconnect-sl-passkeys-public', $verified_page_styles );
		}
	}

	public function render_login_button() {
		echo self::get_shared_button_markup( 'wp_login' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function render_register_button() {
		echo self::get_shared_button_markup( 'wp_register' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function render_verified_passkey_setup_screen() {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public verification-token lookup is sanitized here and validated by the passkey verification flow.
		$state = $this->email_verification->get_pending_verification( $token );
		$is_available = $this->webauthn_service->is_available();

		$this->register_assets();
		wp_enqueue_style( 'ventraconnect-sl-passkeys-public' );
		wp_enqueue_script( 'ventraconnect-sl-passkeys-public' );

		login_header( VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verified_title' ) );
		?>
		<div class="ventraconnect-sl-passkeys-verified-screen">
			<?php if ( is_wp_error( $state ) ) : ?>
				<div class="ventraconnect-sl-passkeys-verified-card">
					<p class="message"><?php echo esc_html( $state->get_error_message() ); ?></p>
					<p class="ventraconnect-sl-passkeys-verified-back">
						<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_back_to_login' ) ); ?></a>
					</p>
				</div>
			<?php elseif ( ! $is_available ) : ?>
				<div class="ventraconnect-sl-passkeys-verified-card">
					<p class="message"><?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_unsupported' ) ); ?></p>
					<p class="ventraconnect-sl-passkeys-verified-back">
						<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_back_to_login' ) ); ?></a>
					</p>
				</div>
			<?php else : ?>
				<div class="ventraconnect-sl-passkeys-verified-card">
					<div class="ventraconnect-sl-passkeys-verified-registration" data-context="verified_passkey_registration" data-verification-token="<?php echo esc_attr( $token ); ?>">
						<h2 class="ventraconnect-sl-passkeys-verified-title"><?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verified_title' ) ); ?></h2>
						<p class="ventraconnect-sl-passkeys-verified-intro"><?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verified_intro' ) ); ?></p>
						<button type="button" class="button button-primary button-large ventraconnect-sl-passkeys-verified-button">
							<?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verified_button' ) ); ?>
						</button>
						<div class="ventraconnect-sl-passkeys-public-status ventraconnect-sl-passkeys-verified-runtime-message" aria-live="polite" role="status" hidden="hidden"></div>
					</div>
					<p class="ventraconnect-sl-passkeys-verified-back">
						<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_back_to_login' ) ); ?></a>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		login_footer();
		exit;
	}

	public function filter_login_body_class( $classes ) {
		if ( ! is_array( $classes ) ) {
			$classes = array();
		}

		if ( $this->is_verified_passkey_login_action_request() ) {
			$classes[] = 'ventraconnect-sl-passkey-verify-screen';
		}

		return array_values( array_unique( array_map( 'sanitize_html_class', $classes ) ) );
	}

	public function get_client_config() {
		return array(
			'ajaxUrl'                                   => admin_url( 'admin-ajax.php' ),
			'discoverableAuthenticationOptionsAction'   => VentraConnect_SL_Passkeys_Public_Ajax::DISCOVERABLE_AUTHENTICATION_OPTIONS_ACTION,
			'discoverableAuthenticationOptionsNonce'    => wp_create_nonce( VentraConnect_SL_Passkeys_Public_Ajax::DISCOVERABLE_AUTHENTICATION_OPTIONS_NONCE_ACTION ),
			'discoverableVerifyAuthenticationAction'    => VentraConnect_SL_Passkeys_Public_Ajax::DISCOVERABLE_VERIFY_AUTHENTICATION_ACTION,
			'discoverableVerifyAuthenticationNonce'     => wp_create_nonce( VentraConnect_SL_Passkeys_Public_Ajax::DISCOVERABLE_VERIFY_AUTHENTICATION_NONCE_ACTION ),
			'startEmailVerificationAction'              => VentraConnect_SL_Passkeys_Public_Ajax::START_EMAIL_VERIFICATION_ACTION,
			'startEmailVerificationNonce'               => wp_create_nonce( VentraConnect_SL_Passkeys_Public_Ajax::START_EMAIL_VERIFICATION_NONCE_ACTION ),
			'verifiedRegistrationOptionsAction'         => VentraConnect_SL_Passkeys_Public_Ajax::VERIFIED_REGISTRATION_OPTIONS_ACTION,
			'verifiedRegistrationOptionsNonce'          => wp_create_nonce( VentraConnect_SL_Passkeys_Public_Ajax::VERIFIED_REGISTRATION_OPTIONS_NONCE_ACTION ),
			'verifiedVerifyRegistrationAction'          => VentraConnect_SL_Passkeys_Public_Ajax::VERIFIED_VERIFY_REGISTRATION_ACTION,
			'verifiedVerifyRegistrationNonce'           => wp_create_nonce( VentraConnect_SL_Passkeys_Public_Ajax::VERIFIED_VERIFY_REGISTRATION_NONCE_ACTION ),
			'unsupportedMessage'                        => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_unsupported' ),
			'discoverableAuthLoadingMessage'            => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_login_loading' ),
			'discoverableAuthPromptMessage'             => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_login_prompt' ),
			'discoverableAuthSuccessMessage'            => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_login_success' ),
			'discoverableAuthFailedMessage'             => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_login_failed' ),
			'discoverableVerifyFailedMessage'           => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verify_failed' ),
			'discoverableNoPasskeyMessage'              => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_no_passkey_found' ),
			'publicRegisterEmailRequiredMessage'        => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_email_required' ),
			'passkeyVerifyEmailToContinueMessage'       => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verify_email_to_continue' ),
			'passkeyVerifyEmailToAddMessage'            => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verify_email_to_add' ),
			'wpRegisterExistingUserMessage'             => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_existing_user_needs_verification' ),
			'wpRegisterExistingUserHasPasskeyMessage'   => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_existing_user_has_passkey' ),
			'duplicateEmailAccountExistsMessage'        => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_duplicate_email' ),
			'publicRegisterLoadingMessage'              => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_loading' ),
			'publicRegisterFailedMessage'               => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_failed' ),
			'publicRegisterCancelledMessage'            => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_cancelled' ),
			'passkeySetupCancelledOrTimeoutMessage'     => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_setup_cancelled_or_timeout' ),
			'passkeyVerificationLinkExpiredMessage'     => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verification_link_expired' ),
			'passkeyVerificationInvalidMessage'         => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verification_invalid' ),
			'verifiedPasskeyCreateLoadingMessage'       => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verified_loading' ),
			'verifiedPasskeyCreatePromptMessage'        => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verified_prompt' ),
			'verifiedPasskeyCreateSuccessMessage'       => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verified_success' ),
			'wpLoginNoPasskeyFoundMessage'              => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_no_passkey_found' ),
			'passkeyLoginFailedMessage'                 => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_login_failed' ),
			'passkeyVerificationFailedMessage'          => VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_verify_failed' ),
		);
	}

	public function should_render_any_public_ui() {
		return self::should_render_in_context( 'wp_login' ) || self::should_render_in_context( 'wp_register' ) || $this->is_verified_passkey_login_action_request();
	}

	public static function should_render_in_context( $context, $settings = null ) {
		$context = is_string( $context ) ? sanitize_key( $context ) : '';

		if ( is_user_logged_in() ) {
			return false;
		}

		if ( ! function_exists( 'ventraconnect_sl_is_passkey_method_enabled' ) || ! ventraconnect_sl_is_passkey_method_enabled() ) {
			return false;
		}

		if ( ! self::is_webauthn_runtime_available() ) {
			return false;
		}

		if ( ! class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
			return false;
		}

		$settings = is_array( $settings ) ? $settings : \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();

		if ( 'wp_login' === $context ) {
			if ( 'register' === self::get_current_login_action() || self::is_verified_passkey_action_request() ) {
				return false;
			}

			return ! empty( $settings['wp_login_enabled'] );
		}

		if ( 'wp_register' === $context ) {
			if ( ! get_option( 'users_can_register' ) || 'register' !== self::get_current_login_action() ) {
				return false;
			}

			return ! empty( $settings['wp_register_enabled'] );
		}

		if ( in_array( $context, self::get_free_shared_login_contexts(), true ) ) {
			return true;
		}

		return false;
	}

	public static function get_shared_button_default_label( $context ) {
		return 'wp_register' === $context
			? VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_button' )
			: VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_title' );
	}

	public static function get_shared_button_markup( $context, $style_variant = 'wide', $theme = 'light', $label = '' ) {
		$context = is_string( $context ) ? sanitize_key( $context ) : '';

		if ( ! self::should_render_in_context( $context ) ) {
			return '';
		}

		self::enqueue_shared_assets();

		$style_variant = in_array( $style_variant, array( 'wide', 'compact' ), true ) ? $style_variant : 'wide';
		$theme         = in_array( $theme, array( 'light', 'dark', 'minimal' ), true ) ? $theme : 'light';
		$label         = is_string( $label ) && '' !== trim( $label ) ? $label : self::get_shared_button_default_label( $context );
		$helper_text   = 'wp_register' === $context
			? self::get_public_register_helper_text()
			: self::get_public_login_helper_text();
		$wrapper_class = 'wp_register' === $context ? 'ventraconnect-sl-passkeys-wp-register' : 'ventraconnect-sl-passkeys-wp-login';
		$button_class  = 'wp_register' === $context ? 'ventraconnect-sl-passkeys-register-button' : 'ventraconnect-sl-passkeys-login-button';
		$status_class  = 'wp_register' === $context ? 'ventraconnect-sl-passkeys-wp-register-runtime-message' : 'ventraconnect-sl-passkeys-wp-login-runtime-message';
		$data_context  = 'wp_register' === $context ? 'wp_register_form' : 'wp_login';
		$redirect_url  = self::get_requested_redirect_url();
		$icon_markup   = self::get_button_icon_markup( $style_variant, $theme );
		$aria_label    = 'compact' === $style_variant ? $label : '';

		ob_start();
		?>
		<div class="ventraconnect-sl-passkeys-public ventraconnect-sl-passkeys-shared <?php echo esc_attr( $wrapper_class ); ?>" data-context="<?php echo esc_attr( $data_context ); ?>" data-redirect="<?php echo esc_attr( $redirect_url ); ?>">
			<button
				type="button"
				class="vcs-btn vcs-btn--<?php echo esc_attr( $style_variant ); ?> wsc-button wsc-button-passkey <?php echo esc_attr( $button_class ); ?>"
				data-provider="passkey"
				data-theme="<?php echo esc_attr( $theme ); ?>"
				<?php echo '' !== $aria_label ? 'aria-label="' . esc_attr( $aria_label ) . '"' : ''; ?>
			>
				<?php if ( '' !== $icon_markup ) : ?>
					<span class="vcs-btn__icon" aria-hidden="true"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<?php endif; ?>
				<span class="vcs-btn__label"><?php echo esc_html( $label ); ?></span>
			</button>
			<?php if ( '' !== $helper_text ) : ?>
				<p class="ventraconnect-sl-passkeys-public-intro"><?php echo esc_html( $helper_text ); ?></p>
			<?php endif; ?>
			<div class="ventraconnect-sl-passkeys-public-status <?php echo esc_attr( $status_class ); ?>" aria-live="polite" role="status" hidden="hidden"></div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	protected static function get_public_login_helper_text() {
		$settings = self::get_passkey_helper_settings();

		if ( empty( $settings['show_helper_text'] ) ) {
			return '';
		}

		if ( '' !== $settings['login_helper_text'] ) {
			return $settings['login_helper_text'];
		}

		if ( class_exists( 'VentraConnect_SL_Passkeys_Messages', false ) ) {
			$message = (string) VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_login_intro' );

			if ( '' !== $message ) {
				return $message;
			}
		}

		return __( 'Use a saved passkey from this device to sign in without a password.', 'ventraconnect-social-login' );
	}

	protected static function get_public_register_helper_text() {
		$settings = self::get_passkey_helper_settings();

		if ( empty( $settings['show_helper_text'] ) ) {
			return '';
		}

		if ( '' !== $settings['register_helper_text'] ) {
			return $settings['register_helper_text'];
		}

		if ( class_exists( 'VentraConnect_SL_Passkeys_Messages', false ) ) {
			$message = (string) VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_register_intro' );

			if ( '' !== $message ) {
				return $message;
			}
		}

		return __( 'Create a passkey for this account after verifying your email address.', 'ventraconnect-social-login' );
	}

	protected static function get_passkey_helper_settings() {
		$settings = array(
			'show_helper_text'     => true,
			'login_helper_text'    => '',
			'register_helper_text' => '',
		);

		if ( class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
			$all_settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
			$passkey      = isset( $all_settings['passkey'] ) && is_array( $all_settings['passkey'] )
				? (array) $all_settings['passkey']
				: array();

			$settings['show_helper_text'] = isset( $passkey['show_helper_text'] ) ? ! empty( $passkey['show_helper_text'] ) : true;
			$settings['login_helper_text'] = isset( $passkey['login_helper_text'] ) ? trim( (string) $passkey['login_helper_text'] ) : '';
			$settings['register_helper_text'] = isset( $passkey['register_helper_text'] ) ? trim( (string) $passkey['register_helper_text'] ) : '';
		}

		return $settings;
	}

	public function is_enabled() {
		if ( ! function_exists( 'ventraconnect_sl_is_passkey_supported' ) || ! ventraconnect_sl_is_passkey_supported() ) {
			return false;
		}

		if ( ! class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
			return false;
		}

		$settings  = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
		$providers = isset( $settings['providers'] ) && is_array( $settings['providers'] ) ? array_map( 'sanitize_key', $settings['providers'] ) : array();

		return in_array( 'passkey', $providers, true );
	}

	protected function should_render_login_button() {
		return self::should_render_in_context( 'wp_login' );
	}

	protected function should_render_register_button() {
		return self::should_render_in_context( 'wp_register' );
	}

	protected function is_verified_passkey_login_action_request() {
		return VentraConnect_SL_Passkeys_Email_Verification::LOGIN_ACTION === $this->get_login_action();
	}

	protected function get_or_separator_setting() {
		if ( ! class_exists( '\VentraConnect\SocialLogin\Admin\Settings\Persistence' ) ) {
			return 'none';
		}

		$settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
		$setting  = isset( $settings['passkey']['or_separator'] ) ? (string) $settings['passkey']['or_separator'] : 'none';

		return in_array( $setting, array( 'none', 'above', 'below', 'both' ), true ) ? $setting : 'none';
	}

	protected function should_render_or_separator( $position, $setting ) {
		$position = is_string( $position ) ? $position : '';
		$setting  = is_string( $setting ) ? $setting : 'none';

		if ( ! in_array( $position, array( 'above', 'below' ), true ) ) {
			return false;
		}

		if ( 'both' === $setting ) {
			return true;
		}

		return $position === $setting;
	}

	protected function render_or_separator( $position, $setting ) {
		if ( ! $this->should_render_or_separator( $position, $setting ) ) {
			return;
		}

		$label = VentraConnect_SL_Passkeys_Messages::get( 'public_passkey_or_separator' );
		if ( '' === $label ) {
			$label = 'OR';
		}
		?>
		<div class="ventraconnect-passkey-or-separator" aria-hidden="true">
			<span></span>
			<em><?php echo esc_html( $label ); ?></em>
			<span></span>
		</div>
		<?php
	}

	protected function get_login_action() {
		return self::get_current_login_action();
	}

	protected static function get_current_login_action() {
		return isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Passive login-context parameter is sanitized and used only for routing, without changing server state.
	}

	protected static function get_free_shared_login_contexts() {
		return array(
			'login',
			'shortcode',
			'widget',
			'block',
			'modal',
			'inline',
			'login_form',
		);
	}

	protected static function is_verified_passkey_action_request() {
		return VentraConnect_SL_Passkeys_Email_Verification::LOGIN_ACTION === self::get_current_login_action();
	}

	protected static function is_webauthn_runtime_available() {
		if ( ! class_exists( 'VentraConnect_SL_Passkeys_Core_WebAuthn_Service', false ) ) {
			return false;
		}

		$service = new VentraConnect_SL_Passkeys_Core_WebAuthn_Service();

		return $service->is_available();
	}

	protected static function get_requested_redirect_url() {
		if ( ! class_exists( 'VentraConnect_SL_Passkeys_Core_Redirect_Resolver', false ) ) {
			return '';
		}

		$redirect_url = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passive redirect context is sanitized here and then validated by Redirect_Resolver::normalize_requested_redirect().
		$resolver     = new VentraConnect_SL_Passkeys_Core_Redirect_Resolver();

		return $resolver->normalize_requested_redirect( $redirect_url, '' );
	}

	protected function get_verified_page_branding_css() {
		if ( ! $this->is_verified_passkey_login_action_request() ) {
			return '';
		}

		$branding     = $this->get_shared_branding_settings();
		if ( empty( $branding['enabled'] ) ) {
			return '';
		}

		$accent_color = ! empty( $branding['accent_color'] ) ? sanitize_hex_color( (string) $branding['accent_color'] ) : '';
		$logo_url     = ! empty( $branding['logo_url'] ) ? esc_url_raw( (string) $branding['logo_url'] ) : '';
		$css          = '';

		if ( $accent_color ) {
			$css .= 'body.ventraconnect-sl-passkey-verify-screen{--vcsl-passkey-accent:' . $accent_color . ';}';
		}

		if ( $logo_url ) {
			$css .= 'body.ventraconnect-sl-passkey-verify-screen.login h1 a{background-image:url("' . esc_url_raw( $logo_url ) . '");background-position:center;background-repeat:no-repeat;background-size:contain;width:min(100%,240px);height:72px;max-width:240px;}';
		}

		return $css;
	}

	protected function get_shared_branding_settings() {
		if (
			class_exists( '\VentraConnect\SocialLogin\Services\Passwordless_Email_Renderer' )
			&& method_exists( '\VentraConnect\SocialLogin\Services\Passwordless_Email_Renderer', 'get_shared_branding_settings' )
		) {
			return \VentraConnect\SocialLogin\Services\Passwordless_Email_Renderer::get_shared_branding_settings();
		}

		return array( 'enabled' => false );
	}

	protected static function enqueue_shared_assets() {
		static $assets_enqueued = false;

		if ( $assets_enqueued ) {
			return;
		}

		$frontend = new self();
		$frontend->register_assets();

		wp_enqueue_style( 'ventraconnect-sl-passkeys-public' );
		wp_enqueue_script( 'ventraconnect-sl-passkeys-public' );

		$assets_enqueued = true;
	}

	protected static function get_button_icon_markup( $style_variant, $theme ) {
		if ( ! class_exists( '\VentraConnect\SocialLogin\Buttons' ) || ! method_exists( '\VentraConnect\SocialLogin\Buttons', 'resolve_icon_source' ) ) {
			return '';
		}

		$icon_source = \VentraConnect\SocialLogin\Buttons::resolve_icon_source( 'passkey', $style_variant, $theme );
		$svg         = isset( $icon_source['svg'] ) ? trim( (string) $icon_source['svg'] ) : '';

		return $svg;
	}
}
