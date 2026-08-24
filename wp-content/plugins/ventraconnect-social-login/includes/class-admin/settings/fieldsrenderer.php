<?php
namespace VentraConnect\SocialLogin\Admin\Settings;

use VentraConnect\SocialLogin\Passwordless;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Fields/HTML renderer for settings UI.
 */
class FieldsRenderer {
	/**
	 * Build the admin preview for the floating passkey setup panel.
	 *
	 * @param array<string,mixed> $args Preview arguments.
	 * @return string
	 */
	private function renderPasskeyFloatingPanelPreview( array $args = array() ): string {
		$title = isset( $args['title'] ) && '' !== trim( (string) $args['title'] )
			? trim( (string) $args['title'] )
			: __( 'Sign in faster next time', 'ventraconnect-social-login' );
		$message = isset( $args['message'] ) && '' !== trim( (string) $args['message'] )
			? trim( (string) $args['message'] )
			: __( 'Set up Passkey to use Face ID, fingerprint, or a security key instead of passwords.', 'ventraconnect-social-login' );
		$button_text = isset( $args['button_text'] ) && '' !== trim( (string) $args['button_text'] )
			? trim( (string) $args['button_text'] )
			: __( 'Set up Passkey', 'ventraconnect-social-login' );
		$position = isset( $args['position'] ) && in_array( (string) $args['position'], array( 'bottom_right', 'bottom_left' ), true )
			? (string) $args['position']
			: 'bottom_right';
		$is_locked = ! empty( $args['locked'] );
		$position_class = 'bottom_left' === $position
			? 'ventraconnect-passkeys-floating-panel--bottom-left'
			: 'ventraconnect-passkeys-floating-panel--bottom-right';
		$canvas_class = 'bottom_left' === $position
			? 'vcs-passkey-panel-preview__canvas--bottom-left'
			: 'vcs-passkey-panel-preview__canvas--bottom-right';

		ob_start();
		?>
		<div class="vcs-passkey-panel-preview<?php echo $is_locked ? ' is-locked' : ' is-unlocked'; ?>">
			<div class="vcs-passkey-panel-preview__intro">
				<div class="vcs-passkey-panel-preview__eyebrow"><?php echo esc_html__( 'Preview', 'ventraconnect-social-login' ); ?></div>
				<p class="vcs-passkey-panel-preview__description"><?php echo esc_html__( 'This is how the floating passkey setup panel appears to logged-in users who have not added a passkey yet.', 'ventraconnect-social-login' ); ?></p>
				<?php if ( $is_locked ) : ?>
					<p class="vcs-passkey-panel-preview__availability"><?php echo esc_html__( 'Available in Pro — prompt logged-in users to secure their account with a passkey after checkout, account login, or selected pages.', 'ventraconnect-social-login' ); ?></p>
				<?php endif; ?>
			</div>
			<div class="vcs-passkey-panel-preview__canvas <?php echo esc_attr( $canvas_class ); ?>">
				<?php if ( $is_locked ) : ?>
					<span class="vcs-passkey-panel-preview__badge"><?php echo esc_html__( 'Pro', 'ventraconnect-social-login' ); ?></span>
				<?php endif; ?>
				<div class="ventraconnect-passkeys-floating-panel <?php echo esc_attr( $position_class ); ?> is-visible" data-ventraconnect-passkeys-floating-panel-preview="1">
					<div class="ventraconnect-passkeys-manage ventraconnect-passkeys-floating-panel__manage" data-context="floating_setup_panel_preview">
						<div class="ventraconnect-passkeys-floating-panel__icon" aria-hidden="true">
							<?php
							$preview_icon_markup = $this->getPasskeyFloatingPanelPreviewIcon();
							if ( '' !== $preview_icon_markup ) :
								?>
								<span class="ventraconnect-passkeys-floating-panel__icon-svg"><?php echo $preview_icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php endif; ?>
						</div>
						<div class="ventraconnect-passkeys-floating-panel__content">
							<h3 class="ventraconnect-passkeys-floating-panel__title"><?php echo esc_html( $title ); ?></h3>
							<p class="ventraconnect-passkeys-floating-panel__message"><?php echo esc_html( $message ); ?></p>
							<div class="ventraconnect-passkeys-status ventraconnect-passkeys-floating-panel__status" aria-live="polite" role="status" hidden="hidden"></div>
							<div class="ventraconnect-passkeys-floating-panel__actions">
								<?php echo $this->renderPasskeyFloatingPanelPreviewButton( $button_text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<button type="button" class="button button-link ventraconnect-passkeys-floating-panel__dismiss" disabled="disabled" aria-disabled="true">
									<?php echo esc_html__( 'Not now', 'ventraconnect-social-login' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Icon markup for the floating passkey panel admin preview.
	 *
	 * @return string
	 */
	private function getPasskeyFloatingPanelPreviewIcon(): string {
		if ( ! class_exists( '\VentraConnect\SocialLogin\Buttons' ) || ! method_exists( '\VentraConnect\SocialLogin\Buttons', 'resolve_icon_source' ) ) {
			return '';
		}

		$icon_source = \VentraConnect\SocialLogin\Buttons::resolve_icon_source( 'passkey', 'compact', 'dark' );

		return isset( $icon_source['svg'] ) ? trim( (string) $icon_source['svg'] ) : '';
	}

	/**
	 * Render the preview button using the same structure and classes as the frontend floating panel.
	 *
	 * @param string $label Button label.
	 * @return string
	 */
	private function renderPasskeyFloatingPanelPreviewButton( string $label ): string {
		$icon_markup  = $this->getPasskeyFloatingPanelPreviewIcon();
		$button_class = 'vcs-btn vcs-btn--compact wsc-button wsc-button-passkey ventraconnect-passkeys-unified-button ventraconnect-passkeys-unified-button--full ventraconnect-passkeys-add-button ventraconnect-passkeys-floating-panel__add-button';

		ob_start();
		?>
		<button
			type="button"
			class="<?php echo esc_attr( $button_class ); ?>"
			data-provider="passkey"
			data-theme="dark"
			disabled="disabled"
			aria-disabled="true"
			tabindex="-1"
		>
			<?php if ( '' !== $icon_markup ) : ?>
				<span class="vcs-btn__icon" aria-hidden="true"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php endif; ?>
			<span class="vcs-btn__label"><?php echo esc_html( $label ); ?></span>
		</button>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render the pinned Overview pane inside the providers tab.
	 *
	 * @param array<string,mixed> $state
	 */
	public function renderOverviewTab( array $state ): void {
		$enabled        = (array) ( $state['enabled'] ?? array() );
		$provider_order = (array) ( $state['provider_order'] ?? array() );
		$enabled_slugs = array();
		foreach ( $enabled as $key => $value ) {
			if ( is_int( $key ) && is_string( $value ) && '' !== $value ) {
				$enabled_slugs[] = sanitize_key( $value );
				continue;
			}
			if ( is_string( $key ) && '' !== $key && ! empty( $value ) ) {
				$enabled_slugs[] = sanitize_key( $key );
			}
		}
		$enabled_slugs = array_values( array_unique( array_filter( $enabled_slugs ) ) );

		$social_provider_map = array(
			'google'    => __( 'Google', 'ventraconnect-social-login' ),
			'facebook'  => __( 'Facebook', 'ventraconnect-social-login' ),
			'amazon'    => __( 'Amazon', 'ventraconnect-social-login' ),
			'twitter'   => __( 'X', 'ventraconnect-social-login' ),
			'twitch'    => __( 'Twitch', 'ventraconnect-social-login' ),
			'reddit'    => __( 'Reddit', 'ventraconnect-social-login' ),
			'microsoft' => __( 'Microsoft', 'ventraconnect-social-login' ),
			'yahoo'     => __( 'Yahoo', 'ventraconnect-social-login' ),
			'wordpress' => __( 'WordPress.com', 'ventraconnect-social-login' ),
			'slack'     => __( 'Slack', 'ventraconnect-social-login' ),
			'discord'   => __( 'Discord', 'ventraconnect-social-login' ),
			'linkedin'  => __( 'LinkedIn', 'ventraconnect-social-login' ),
			'spotify'   => __( 'Spotify', 'ventraconnect-social-login' ),
			'line'      => __( 'LINE', 'ventraconnect-social-login' ),
			'tiktok'    => __( 'TikTok', 'ventraconnect-social-login' ),
			'github'    => __( 'GitHub', 'ventraconnect-social-login' ),
		);
		$all_social_slugs = array_keys( $social_provider_map );
		$default_provider_order = array(
			'passkey',
			'magic_link',
			'otp_email',
			'google',
			'facebook',
			'amazon',
			'twitter',
			'twitch',
			'reddit',
			'microsoft',
			'yahoo',
			'wordpress',
			'slack',
			'discord',
			'linkedin',
			'spotify',
			'line',
			'tiktok',
			'github',
		);
		$provider_label_map = array_merge(
			array(
				'passkey'    => __( 'Passkey', 'ventraconnect-social-login' ),
				'magic_link' => __( 'Magic Link', 'ventraconnect-social-login' ),
				'otp_email'  => __( 'Email OTP', 'ventraconnect-social-login' ),
			),
			$social_provider_map
		);
		$ordered_provider_slugs = array_values(
			array_unique(
				array_merge(
					array_values( array_intersect( $provider_order, $default_provider_order ) ),
					$default_provider_order
				)
			)
		);
		$ordered_social_slugs = array_values(
			array_unique(
				array_merge(
					array_values( array_intersect( $provider_order, $all_social_slugs ) ),
					$all_social_slugs
				)
			)
		);
		$active_social_slugs = array_values( array_intersect( $ordered_social_slugs, $enabled_slugs ) );
		$active_social_count = count( $active_social_slugs );
		$social_target_slug  = ! empty( $active_social_slugs )
			? (string) reset( $active_social_slugs )
			: ( ! empty( $ordered_social_slugs ) ? (string) reset( $ordered_social_slugs ) : 'google' );
		$any_active_methods  = ! empty( $enabled_slugs );

		$magic_link_active = in_array( 'magic_link', $enabled_slugs, true );
		$otp_email_active  = in_array( 'otp_email', $enabled_slugs, true );
		$passkey_active    = in_array( 'passkey', $enabled_slugs, true );
		$passkey_supported = function_exists( 'ventraconnect_sl_is_passkey_supported' )
			? (bool) ventraconnect_sl_is_passkey_supported()
			: ( defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) && VENTRACONNECT_PASSKEYS_CORE_SUPPORTED );
		$passkey_status_label = ! $passkey_supported
			? __( 'Requires PHP 8.2+', 'ventraconnect-social-login' )
			: ( $passkey_active ? __( 'Active', 'ventraconnect-social-login' ) : __( 'Inactive', 'ventraconnect-social-login' ) );
		$passkey_status_type = ! $passkey_supported ? 'warning' : ( $passkey_active ? 'success' : 'info' );
		$active_social_labels = array();
		foreach ( $active_social_slugs as $active_social_slug ) {
			$active_social_labels[] = isset( $social_provider_map[ $active_social_slug ] )
				? $social_provider_map[ $active_social_slug ]
				: ucfirst( (string) $active_social_slug );
		}
		$social_description = sprintf(
			/* translators: %d: active social providers count. */
			__( '%d of 15+ providers active.', 'ventraconnect-social-login' ),
			$active_social_count
		);
		$social_detail = ! empty( $active_social_labels ) ? implode( ', ', $active_social_labels ) : '';
		$icon_base_url = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/' : '';
		$icon_base_dir = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ? VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' : '';
		$get_overview_icon = static function ( string $slug ) use ( $icon_base_dir, $icon_base_url ): string {
			$slug = sanitize_key( $slug );
			if ( '' !== $slug && '' !== $icon_base_dir && '' !== $icon_base_url ) {
				$icon_path = $icon_base_dir . $slug . '.svg';
				if ( file_exists( $icon_path ) ) {
					return sprintf(
						'<span class="wsc-overview-card__icon" aria-hidden="true"><img src="%1$s" alt="" /></span>',
						esc_url( $icon_base_url . $slug . '.svg' )
					);
				}
			}

			$dashicon = 'dashicons-share-alt2';
			if ( 'magic_link' === $slug ) {
				$dashicon = 'dashicons-admin-links';
			} elseif ( 'otp_email' === $slug ) {
				$dashicon = 'dashicons-email-alt';
			} elseif ( 'passkey' === $slug ) {
				$dashicon = 'dashicons-lock';
			}

			return sprintf(
				'<span class="wsc-overview-card__icon wsc-overview-card__icon--dashicon" aria-hidden="true"><span class="dashicons %1$s"></span></span>',
				esc_attr( $dashicon )
			);
		};
		$order_rows = array();
		foreach ( $ordered_provider_slugs as $slug ) {
			$label = isset( $provider_label_map[ $slug ] ) ? $provider_label_map[ $slug ] : ucfirst( str_replace( array( '_', '-' ), ' ', $slug ) );
			if ( 'passkey' === $slug ) {
				$order_rows[] = array(
					'label'       => $label,
					'status'      => $passkey_status_label,
					'status_type' => $passkey_status_type,
				);
				continue;
			}

			$is_active = in_array( $slug, $enabled_slugs, true );
			$order_rows[] = array(
				'label'       => $label,
				'status'      => $is_active ? __( 'Active', 'ventraconnect-social-login' ) : __( 'Inactive', 'ventraconnect-social-login' ),
				'status_type' => $is_active ? 'success' : 'info',
			);
		}

		$cards = array(
			array(
				'overview_key' => 'magic_link',
				'title'       => __( 'Magic Link', 'ventraconnect-social-login' ),
				'status'      => $magic_link_active ? __( 'Active', 'ventraconnect-social-login' ) : __( 'Inactive', 'ventraconnect-social-login' ),
				'status_type' => $magic_link_active ? 'success' : 'info',
				'description' => $magic_link_active
					? __( 'Passwordless login via a one-click email link. Send to any email, no password needed.', 'ventraconnect-social-login' )
					: __( 'Let users sign in with a secure email link. Enable it from the settings below when you are ready.', 'ventraconnect-social-login' ),
				'detail'      => '',
				'icon_html'   => $get_overview_icon( 'magic_link' ),
				'button_text' => __( 'Configure', 'ventraconnect-social-login' ),
				'target'      => 'magic_link',
			),
			array(
				'overview_key' => 'otp_email',
				'title'       => __( 'Email OTP', 'ventraconnect-social-login' ),
				'status'      => $otp_email_active ? __( 'Active', 'ventraconnect-social-login' ) : __( 'Inactive', 'ventraconnect-social-login' ),
				'status_type' => $otp_email_active ? 'success' : 'info',
				'description' => $otp_email_active
					? __( 'Login with a one-time code sent to email. Works alongside or instead of passwords.', 'ventraconnect-social-login' )
					: __( 'Let users sign in with a one-time code sent to their email. Configure it below when you are ready.', 'ventraconnect-social-login' ),
				'detail'      => '',
				'icon_html'   => $get_overview_icon( 'otp_email' ),
				'button_text' => __( 'Configure', 'ventraconnect-social-login' ),
				'target'      => 'otp_email',
			),
			array(
				'overview_key' => 'passkey',
				'title'       => __( 'Passkeys / Fingerprint & Face ID', 'ventraconnect-social-login' ),
				'status'      => $passkey_status_label,
				'status_type' => $passkey_status_type,
				'description' => $passkey_supported
					? ( $passkey_active
						? __( 'Biometric and device-based login. Fingerprint, Face ID, or security key.', 'ventraconnect-social-login' )
						: __( 'Add secure device-based login with native passkeys. Configure it below when you are ready.', 'ventraconnect-social-login' ) )
					: __( 'Available on PHP 8.2+', 'ventraconnect-social-login' ),
				'detail'      => '',
				'icon_html'   => $get_overview_icon( 'passkey' ),
				'button_text' => $passkey_supported
					? __( 'Configure', 'ventraconnect-social-login' )
					: __( 'View requirements', 'ventraconnect-social-login' ),
				'target'      => 'passkey',
			),
			array(
				'overview_key' => 'social',
				'title'       => __( 'Social Login', 'ventraconnect-social-login' ),
				'status'      => sprintf(
					/* translators: %d: active social providers count. */
					_n( '%d active', '%d active', $active_social_count, 'ventraconnect-social-login' ),
					$active_social_count
				),
				'status_type' => ! empty( $active_social_slugs ) ? 'success' : 'info',
				'description' => $active_social_count
					? $social_description
					: __( 'No social providers are active yet. Start with Google, or enable any provider below. You can drag providers to control button order.', 'ventraconnect-social-login' ),
				'detail'      => $social_detail,
				'icon_html'   => $get_overview_icon( 'social' ),
				'button_text' => $active_social_count
					? __( 'View all', 'ventraconnect-social-login' )
					: __( 'Set up providers', 'ventraconnect-social-login' ),
				'target'      => $social_target_slug,
			),
		);
		?>
		<div class="wsc-overview"
			data-passkey-supported="<?php echo esc_attr( $passkey_supported ? '1' : '0' ); ?>"
			data-label-active="<?php echo esc_attr__( 'Active', 'ventraconnect-social-login' ); ?>"
			data-label-inactive="<?php echo esc_attr__( 'Inactive', 'ventraconnect-social-login' ); ?>"
			data-label-passkey-php="<?php echo esc_attr__( 'Requires PHP 8.2+', 'ventraconnect-social-login' ); ?>"
			data-passkey-description-supported="<?php echo esc_attr__( 'Biometric and device-based login. Fingerprint, Face ID, or security key.', 'ventraconnect-social-login' ); ?>"
			data-passkey-description-inactive="<?php echo esc_attr__( 'Add secure device-based login with native passkeys. Configure it below when you are ready.', 'ventraconnect-social-login' ); ?>"
			data-passkey-description-unsupported="<?php echo esc_attr__( 'Available on PHP 8.2+', 'ventraconnect-social-login' ); ?>"
			data-passkey-detail-unsupported="<?php echo esc_attr__( 'Upgrade your server PHP version to enable native passkey login.', 'ventraconnect-social-login' ); ?>"
			<?php /* translators: %d: active social providers count. */ ?>
			data-social-active-template="<?php echo esc_attr__( '%d active', 'ventraconnect-social-login' ); ?>"
			<?php /* translators: %d: active social providers count. */ ?>
			data-social-description-template="<?php echo esc_attr__( '%d of 15+ providers active.', 'ventraconnect-social-login' ); ?>"
			data-social-available-template="<?php echo esc_attr__( 'No social providers are active yet. Start with Google, or enable any provider below. You can drag providers to control button order.', 'ventraconnect-social-login' ); ?>"
			data-social-button-inactive="<?php echo esc_attr__( 'Set up providers', 'ventraconnect-social-login' ); ?>"
			data-social-button-active="<?php echo esc_attr__( 'View all', 'ventraconnect-social-login' ); ?>"
			data-magic-link-description-active="<?php echo esc_attr__( 'Passwordless login via a one-click email link. Send to any email, no password needed.', 'ventraconnect-social-login' ); ?>"
			data-magic-link-description-inactive="<?php echo esc_attr__( 'Let users sign in with a secure email link. Enable it from the settings below when you are ready.', 'ventraconnect-social-login' ); ?>"
			data-otp-description-active="<?php echo esc_attr__( 'Login with a one-time code sent to email. Works alongside or instead of passwords.', 'ventraconnect-social-login' ); ?>"
			data-otp-description-inactive="<?php echo esc_attr__( 'Let users sign in with a one-time code sent to their email. Configure it below when you are ready.', 'ventraconnect-social-login' ); ?>"
			data-no-active-message="<?php echo esc_attr__( 'No active login methods yet. Enable one from the Login Methods list.', 'ventraconnect-social-login' ); ?>"
			data-passkey-note=""
			data-inactive-note="<?php echo esc_attr__( 'Inactive methods are still available in the Login Methods list.', 'ventraconnect-social-login' ); ?>">
			<?php if ( ! $any_active_methods ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'Welcome to VentraConnect - enable your first login method to get started.', 'ventraconnect-social-login' ); ?></p>
				</div>
			<?php endif; ?>

			<?php
			$active_order_rows = array_values(
				array_filter(
					$order_rows,
					static function ( $row ) {
						return isset( $row['status'] ) && __( 'Active', 'ventraconnect-social-login' ) === $row['status'];
					}
				)
			);
			$passkey_unavailable_note = $passkey_active && ! $passkey_supported;
			$general_settings_base    = admin_url( 'admin.php?page=ventraconnect-sl-settings&tab=general' );
			$button_layout_url        = $general_settings_base . '#vcsl-button-layout-theme';
			$shortcode_url            = $general_settings_base . '#vcsl-shortcodes-template-usage';
			$redirects_url            = $general_settings_base . '#vcsl-redirects-security';
			$email_branding_url       = $general_settings_base . '#vcsl-email-branding';
			$pro_active_overview      = \VentraConnect\SocialLogin\Pro_Gates::is_pro();
			?>

			<div class="wsc-overview-intro">
				<p class="wsc-muted"><?php esc_html_e( 'Enable any combination of login methods - Social Login, Magic Link, Email OTP and Passkey work independently and stack together on your login form.', 'ventraconnect-social-login' ); ?></p>
			</div>

			<div class="wsc-overview-dashboard">
				<div class="wsc-overview-method-grid">
					<?php foreach ( $cards as $card ) : ?>
						<div class="wsc-overview-card" data-overview-card="<?php echo esc_attr( sanitize_key( (string) ( $card['overview_key'] ?? $card['target'] ) ) ); ?>">
							<div class="wsc-overview-card__header">
								<div class="wsc-overview-card__title-wrap">
									<?php echo wp_kses_post( $card['icon_html'] ); ?>
									<h3 class="wsc-overview-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
								</div>
								<span class="wsc-pill wsc-pill-sm <?php echo esc_attr( 'wsc-pill-soft-' . $card['status_type'] ); ?>" data-overview-status>
									<?php echo esc_html( $card['status'] ); ?>
								</span>
							</div>
							<p class="wsc-overview-card__description" data-overview-description><?php echo esc_html( $card['description'] ); ?></p>
							<?php if ( ! empty( $card['detail'] ) || 'social' === ( $card['overview_key'] ?? '' ) || ( 'passkey' === ( $card['overview_key'] ?? '' ) && ! $passkey_supported ) ) : ?>
								<p class="wsc-overview-card__detail"<?php echo ! empty( $card['detail'] ) ? '' : ' hidden'; ?> data-overview-detail><?php echo esc_html( (string) $card['detail'] ); ?></p>
							<?php endif; ?>
							<p class="wsc-overview-card__footer">
								<a class="button button-secondary wsc-overview-switch"
								   href="<?php echo esc_url( admin_url( 'admin.php?page=ventraconnect-sl-settings&tab=providers&provider=' . rawurlencode( (string) $card['target'] ) ) ); ?>"
								   data-provider-target="<?php echo esc_attr( (string) $card['target'] ); ?>">
									<?php echo esc_html( $card['button_text'] ); ?>
								</a>
							</p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="wsc-overview-lower-grid">
				<div class="wsc-card wsc-overview-order-card">
					<div class="wsc-section-header">
						<h2 class="wsc-section-header__title"><?php esc_html_e( 'Current active button order', 'ventraconnect-social-login' ); ?></h2>
						<p class="wsc-section-header__description"><?php esc_html_e( 'Active methods appear on your login forms in this order.', 'ventraconnect-social-login' ); ?></p>
					</div>
					<div class="wsc-card__body">
						<div class="wsc-overview-order-body" data-overview-order-body>
							<?php if ( ! empty( $active_order_rows ) ) : ?>
								<ol class="wsc-overview-order-list">
									<?php foreach ( $active_order_rows as $index => $row ) : ?>
										<li class="wsc-overview-order-item">
											<span class="wsc-overview-order-item__position"><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
											<span class="wsc-overview-order-item__label"><?php echo esc_html( $row['label'] ); ?></span>
											<span class="wsc-pill wsc-pill-sm wsc-pill-soft-success"><?php esc_html_e( 'Active', 'ventraconnect-social-login' ); ?></span>
										</li>
									<?php endforeach; ?>
								</ol>
							<?php else : ?>
								<p class="wsc-muted"><?php esc_html_e( 'No active login methods yet. Enable one from the Login Methods list.', 'ventraconnect-social-login' ); ?></p>
							<?php endif; ?>
						</div>
						<p class="wsc-input-hint wsc-overview-order-note"<?php echo $passkey_unavailable_note ? '' : ' hidden'; ?> data-overview-passkey-note></p>
						<p class="wsc-input-hint wsc-overview-order-note" data-overview-inactive-note><?php esc_html_e( 'Inactive methods are still available in the Login Methods list.', 'ventraconnect-social-login' ); ?></p>
					</div>
				</div>

				<div class="wsc-card wsc-overview-section">
					<div class="wsc-section-header">
						<h2 class="wsc-section-header__title"><?php esc_html_e( 'Recommended setup shortcuts', 'ventraconnect-social-login' ); ?></h2>
						<p class="wsc-section-header__description"><?php esc_html_e( 'Useful settings to review after enabling your login methods.', 'ventraconnect-social-login' ); ?></p>
					</div>
					<div class="wsc-card__body">
						<div class="wsc-overview-shortcuts">
							<div class="wsc-overview-shortcut">
								<div class="wsc-overview-shortcut__content">
									<strong><?php esc_html_e( 'Button layout & theme', 'ventraconnect-social-login' ); ?></strong>
									<p><?php esc_html_e( 'Choose wide or compact buttons and set the global button theme.', 'ventraconnect-social-login' ); ?></p>
								</div>
								<div class="wsc-overview-shortcut__action">
									<a class="button button-secondary" href="<?php echo esc_url( $button_layout_url ); ?>"><?php esc_html_e( 'Open Settings', 'ventraconnect-social-login' ); ?></a>
								</div>
							</div>
							<div class="wsc-overview-shortcut">
								<div class="wsc-overview-shortcut__content">
									<strong><?php esc_html_e( 'Shortcodes & template usage', 'ventraconnect-social-login' ); ?></strong>
									<p><?php esc_html_e( 'Use shortcodes on normal WordPress pages and templates. Integration-owned forms use Pro placement settings.', 'ventraconnect-social-login' ); ?></p>
								</div>
								<div class="wsc-overview-shortcut__action">
									<a class="button button-secondary" href="<?php echo esc_url( $shortcode_url ); ?>"><?php esc_html_e( 'View shortcode', 'ventraconnect-social-login' ); ?></a>
								</div>
							</div>
							<div class="wsc-overview-shortcut">
								<div class="wsc-overview-shortcut__content">
									<strong><?php esc_html_e( 'Redirects & security', 'ventraconnect-social-login' ); ?></strong>
									<p><?php esc_html_e( 'Control where users go after login or registration and protect redirect behavior.', 'ventraconnect-social-login' ); ?></p>
								</div>
								<div class="wsc-overview-shortcut__action">
									<a class="button button-secondary" href="<?php echo esc_url( $redirects_url ); ?>"><?php esc_html_e( 'Configure redirects', 'ventraconnect-social-login' ); ?></a>
								</div>
							</div>
							<div class="wsc-overview-shortcut">
								<div class="wsc-overview-shortcut__content">
									<strong><?php esc_html_e( 'Email branding', 'ventraconnect-social-login' ); ?></strong>
									<p><?php esc_html_e( 'Customize branding for Magic Link, Email OTP, and Passkey verification emails.', 'ventraconnect-social-login' ); ?></p>
								</div>
								<div class="wsc-overview-shortcut__action">
									<?php if ( $pro_active_overview ) : ?>
										<a class="button button-secondary" href="<?php echo esc_url( $email_branding_url ); ?>"><?php esc_html_e( 'Customize emails', 'ventraconnect-social-login' ); ?></a>
									<?php else : ?>
										<span class="wsc-badge wsc-badge-status--info wsc-overview-shortcut__badge"><?php esc_html_e( 'Pro', 'ventraconnect-social-login' ); ?></span>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Pro upsell card for a provider.
	 * Copy/markup moved verbatim from Settings::render_provider_upsell_card().
	 *
	 * @param array $copy ['title' => string, 'description' => string, 'cta_url' => string]
	 */
	public function render_provider_upsell_card( array $copy ): void {
		if ( empty( $copy ) ) {
			return;
		}
		echo '<div class="wsc-card vcs-integrations-upsell">';
		if ( ! empty( $copy['title'] ) ) {
			echo '<h3>' . esc_html( $copy['title'] ) . '</h3>';
		}
		if ( ! empty( $copy['description'] ) ) {
			echo '<p class="wsc-muted">' . esc_html( $copy['description'] ) . '</p>';
		}
		if ( ! empty( $copy['cta_url'] ) ) {
			echo '<a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url( $copy['cta_url'] ) . '">' . esc_html__( 'Upgrade to Pro', 'ventraconnect-social-login' ) . '</a>';
		}
		echo '</div>';
	}
	/**
	 * Render the General tab markup.
	 *
	 * Contract:
	 * - $state contains all data needed by the markup (no option lookups here)
	 * - $isPro() callable returns true when Pro is active
	 * - Echoes markup 1:1 to preserve IDs/classes/strings
	 *
	 * @param array<string,mixed> $state
	 * @param callable            $isPro
	 * @param bool                $is_active Whether to mark the General tab active
	 */
	public function renderGeneralTab( array $state, callable $isPro, bool $is_active = true ): void {
		$preview_only = ! empty( $state['preview_only'] );
		if ( $preview_only ) {
			ob_start();
		}
		// Local vars expected by the template (match original variable names)
		$settings = (array) ( $state['settings'] ?? [] );
		$enabled  = (array) ( $state['enabled'] ?? [] );
		$creds    = (array) ( $state['creds'] ?? [] );
		$has_creds = (bool) ( $state['has_creds'] ?? false );
		$debug_mode = (bool) ( $state['debug_mode'] ?? false );
		$prevent_external = (bool) ( $state['prevent_external'] ?? false );
		$redirect_default_login    = isset( $state['redirect_default_login'] ) ? (string) $state['redirect_default_login'] : '';
		$redirect_default_register = isset( $state['redirect_default_register'] ) ? (string) $state['redirect_default_register'] : '';
		$redirect_blacklist = (array) ( $state['redirect_blacklist'] ?? [] );
		$button_style = (string) ( $state['button_style'] ?? 'wide' );
		$global_theme = (string) ( $state['global_theme'] ?? 'light' );
		$redirect_google    = isset( $state['redirect_google'] ) ? (string) $state['redirect_google'] : '';
		$redirect_facebook  = isset( $state['redirect_facebook'] ) ? (string) $state['redirect_facebook'] : '';
		$redirect_github    = isset( $state['redirect_github'] ) ? (string) $state['redirect_github'] : '';
		$redirect_discord   = isset( $state['redirect_discord'] ) ? (string) $state['redirect_discord'] : '';
		$redirect_wordpress = isset( $state['redirect_wordpress'] ) ? (string) $state['redirect_wordpress'] : '';
		$redirect_slack     = isset( $state['redirect_slack'] ) ? (string) $state['redirect_slack'] : '';
		$passwordless_email_branding = isset( $settings['passwordless_email_branding'] ) && is_array( $settings['passwordless_email_branding'] )
			? $settings['passwordless_email_branding']
			: array();
		$branding_enabled      = ! empty( $passwordless_email_branding['enabled'] );
		$branding_logo_url     = isset( $passwordless_email_branding['logo_url'] ) ? (string) $passwordless_email_branding['logo_url'] : '';
		$branding_accent_color = isset( $passwordless_email_branding['accent_color'] ) ? (string) $passwordless_email_branding['accent_color'] : '';
		$branding_footer_text  = isset( $passwordless_email_branding['footer_text'] ) ? (string) $passwordless_email_branding['footer_text'] : '';
        
         // --------------- STATUS COMPUTATION (for Login status card) ---------------
        $pro_active = (bool) $isPro();
		$branding_disabled_attr = $pro_active ? '' : ' disabled="disabled"';

        // Map known provider slugs to human labels; fall back to prettified slugs.
        $provider_label_map = array(
            'google'       => __( 'Google', 'ventraconnect-social-login' ),
            'facebook'     => __( 'Facebook', 'ventraconnect-social-login' ),
            'twitter'      => __( 'X', 'ventraconnect-social-login' ),
            'x'            => __( 'X', 'ventraconnect-social-login' ),
            'microsoft'    => __( 'Microsoft', 'ventraconnect-social-login' ),
            'linkedin'     => __( 'LinkedIn', 'ventraconnect-social-login' ),
            'amazon'       => __( 'Amazon', 'ventraconnect-social-login' ),
            'github'       => __( 'GitHub', 'ventraconnect-social-login' ),
            'wordpress'    => __( 'WordPress.com', 'ventraconnect-social-login' ),
            'yahoo'        => __( 'Yahoo', 'ventraconnect-social-login' ),
            'discord'      => __( 'Discord', 'ventraconnect-social-login' ),
            'tiktok'       => __( 'TikTok', 'ventraconnect-social-login' ),
            'slack'        => __( 'Slack', 'ventraconnect-social-login' ),
            'spotify'      => __( 'Spotify', 'ventraconnect-social-login' ),
            'line'         => __( 'LINE', 'ventraconnect-social-login' ),
            'twitch'       => __( 'Twitch', 'ventraconnect-social-login' ),
            'reddit'       => __( 'Reddit', 'ventraconnect-social-login' ),
        );

        // Build a list of enabled provider slugs from $enabled.
        $active_provider_slugs_all = array();

        foreach ( $enabled as $key => $value ) {
            // Shape A: ['google', 'facebook', 'twitter', ...]
            if ( is_int( $key ) && is_string( $value ) && $value !== '' ) {
                $active_provider_slugs_all[] = $value;
                continue;
            }

            // Shape B: ['google' => 1, 'facebook' => 0, ...]
            if ( is_string( $key ) && $key !== '' && ! empty( $value ) ) {
                $active_provider_slugs_all[] = $key;
            }
        }

        // Normalise and de-duplicate.
        $active_provider_slugs_all = array_values( array_unique( $active_provider_slugs_all ) );

        // Pro-only providers we *never* want to show inside the Social Login list.
        $pro_only_slugs = array(
            'otp',
            'otp_email',
            'otp-email',
            'magic_link',
            'magic-link',
            'passkey',
        );

        // Social Login list = enabled providers minus Magic Link / OTP.
        $social_provider_slugs = array_values(
            array_diff( $active_provider_slugs_all, $pro_only_slugs )
        );

        // Human-readable Social Login text.
        $active_provider_labels = array();

        foreach ( $social_provider_slugs as $slug ) {
            $label = $provider_label_map[ $slug ] ?? ucwords( str_replace( array( '-', '_' ), ' ', (string) $slug ) );
            $active_provider_labels[] = $label;
        }

        if ( empty( $active_provider_labels ) ) {
            $social_login_status_text = __( 'No providers active – set up Social Login.', 'ventraconnect-social-login' );
        } else {
            $social_login_status_text = implode( ', ', $active_provider_labels );
        }

        // Magic Link & OTP status flags (for Pro view).
        $magic_link_enabled = in_array( 'magic_link', $active_provider_slugs_all, true )
            || in_array( 'magic-link', $active_provider_slugs_all, true );

        $otp_enabled = in_array( 'otp_email', $active_provider_slugs_all, true )
            || in_array( 'otp-email', $active_provider_slugs_all, true )
            || in_array( 'otp', $active_provider_slugs_all, true );

        $passkey_supported = function_exists( 'ventraconnect_sl_is_passkey_supported' )
            ? (bool) ventraconnect_sl_is_passkey_supported()
            : ( defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) && VENTRACONNECT_PASSKEYS_CORE_SUPPORTED );

        $passkey_enabled = in_array( 'passkey', $active_provider_slugs_all, true );
        $passkey_status_text = __( 'Disabled', 'ventraconnect-social-login' );
        $passkey_icon_state  = 'inactive';
        $passkey_icon_variant = 'inactive';

        if ( ! $passkey_supported ) {
            $passkey_status_text = __( 'Requires PHP 8.2+', 'ventraconnect-social-login' );
            $passkey_icon_state  = 'warning';
            $passkey_icon_variant = 'warning';
        } elseif ( $passkey_enabled ) {
            $passkey_status_text = __( 'Active', 'ventraconnect-social-login' );
            $passkey_icon_state  = 'active';
            $passkey_icon_variant = 'active';
        }

        // Passwordless mode label (Off / Recommended / Strict).
        $passwordless_mode_label = __( 'Off', 'ventraconnect-social-login' );

        if ( class_exists( '\\VentraConnect\\SocialLogin\\Passwordless' ) ) {
            $mode = 'off';

            if ( method_exists( '\\VentraConnect\\SocialLogin\\Passwordless', 'get_mode' ) ) {
                $mode = (string) \VentraConnect\SocialLogin\Passwordless::get_mode();
            }

            switch ( $mode ) {
                case 'strict':
                    $passwordless_mode_label = __( 'Strict', 'ventraconnect-social-login' );
                    break;
                case 'recommended':
                    $passwordless_mode_label = __( 'Recommended', 'ventraconnect-social-login' );
                    break;
                default:
                    $passwordless_mode_label = __( 'Off', 'ventraconnect-social-login' );
                    break;
            }
        }

        // WooCommerce presence + high-level status.
        $has_woocommerce  = class_exists( 'WooCommerce' );
        $woo_status_label = __( 'Not installed', 'ventraconnect-social-login' );

        if ( $has_woocommerce ) {
            $woo_status_label = __( 'Installed – see WooCommerce tab', 'ventraconnect-social-login' );

            if (
                class_exists( '\\VentraConnect\\SocialLogin\\Passwordless' ) &&
                method_exists( '\\VentraConnect\\SocialLogin\\Passwordless', 'get_login_context_status' )
            ) {
                $contexts = \VentraConnect\SocialLogin\Passwordless::get_login_context_status();
                $woo_ctx  = $contexts['woo_login'] ?? null;

                if ( is_array( $woo_ctx ) ) {
                    if ( ! empty( $woo_ctx['enabled'] ) ) {
                        $woo_status_label = __( 'Active', 'ventraconnect-social-login' );
                    } elseif ( empty( $woo_ctx['available'] ) ) {
                        $woo_status_label = __( 'Not available', 'ventraconnect-social-login' );
                    } else {
                        $woo_status_label = __( 'Installed – not configured', 'ventraconnect-social-login' );
                    }
                }
            }
        }
        // ------------- END STATUS COMPUTATION -------------
		?>

	<div id="wsc-tab-general" class="wsc-tab<?php echo $is_active ? ' is-active' : ''; ?>">
        <div class="wsc-grid">

            <?php
            // ---------- GENERAL SETTINGS / SETUP CARD ----------
            ?>
            <div class="wsc-card">
                <div class="wsc-section-header">
                    <h2 class="wsc-section-header__title">
                        <?php echo esc_html__( 'General Settings', 'ventraconnect-social-login' ); ?>
                    </h2>
                    <p class="wsc-section-header__description">
                        <?php echo esc_html__( 'Configure global settings for social login including display options, redirects, and authentication behavior.', 'ventraconnect-social-login' ); ?>
                    </p>
                </div>

                <?php
                // 1. Show on WordPress login/register.
                ?>
                <div class="wsc-card wsc-card--row" style="margin-top: 20px;">
                    <div class="wsc-card__content">
                        <div class="wsc-card__title">
                            <?php echo esc_html__( 'Show on WordPress login/register', 'ventraconnect-social-login' ); ?>
                        </div>
                        <p class="wsc-card__description">
                            <?php echo esc_html__( 'Display social login buttons on the standard WordPress login and registration pages.', 'ventraconnect-social-login' ); ?>
                        </p>
                    </div>
                    <div class="wsc-card__control">
                        <div style="display:flex;align-items:flex-end;gap:24px;flex-wrap:wrap;">
                            <div>
                                <span class="wsc-small" style="display:block;margin-bottom:4px;">
                                    <?php echo esc_html__( 'Login form', 'ventraconnect-social-login' ); ?>
                                </span>
                                <input type="hidden" name="ventraconnect_sl_settings[wp_login_enabled]" value="0">
                                <label class="wsc-switch">
                                    <input type="checkbox"
                                           class="wsc-switch-input"
                                           name="ventraconnect_sl_settings[wp_login_enabled]"
                                           value="1" <?php checked( ! empty( $settings['wp_login_enabled'] ) ); ?>>
                                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                                    <span class="screen-reader-text">
                                        <?php echo esc_html__( 'Enable on login form', 'ventraconnect-social-login' ); ?>
                                    </span>
                                </label>
                            </div>
                            <div>
                                <span class="wsc-small" style="display:block;margin-bottom:4px;">
                                    <?php echo esc_html__( 'Register form', 'ventraconnect-social-login' ); ?>
                                </span>
                                <input type="hidden" name="ventraconnect_sl_settings[wp_register_enabled]" value="0">
                                <label class="wsc-switch">
                                    <input type="checkbox"
                                           class="wsc-switch-input"
                                           name="ventraconnect_sl_settings[wp_register_enabled]"
                                           value="1" <?php checked( ! empty( $settings['wp_register_enabled'] ) ); ?>>
                                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                                    <span class="screen-reader-text">
                                        <?php echo esc_html__( 'Enable on register form', 'ventraconnect-social-login' ); ?>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                // 2. Allow new accounts from default login forms.
                $allow_core_login_checked = isset( $settings['allow_core_login_account_creation'] )
                    ? ! empty( $settings['allow_core_login_account_creation'] )
                    : true;
                ?>
                <div class="wsc-card wsc-card--row" style="margin-top: 16px;">
                    <div class="wsc-card__content">
                        <div class="wsc-card__title">
                            <?php echo esc_html__( 'Allow new accounts from default login forms', 'ventraconnect-social-login' ); ?>
                        </div>
                        <p class="wsc-card__description">
                            <?php
                            echo esc_html__(
                                'When disabled, VentraConnect Methods on standard login forms (wp-login and theme login widgets/shortcodes) can only sign in existing users. New accounts must be created via your registration or checkout forms.',
                                'ventraconnect-social-login'
                            );
                            ?>
                        </p>
                    </div>
                    <div class="wsc-card__control">
                        <label class="wsc-switch">
                            <input type="hidden" name="ventraconnect_sl_settings[allow_core_login_account_creation]" value="0">
                            <input
                                type="checkbox"
                                class="wsc-switch-input"
                                name="ventraconnect_sl_settings[allow_core_login_account_creation]"
                                value="1"
                                <?php checked( $allow_core_login_checked ); ?>
                            >
                            <span class="wsc-switch-ui" aria-hidden="true"></span>
                            <span class="screen-reader-text">
                                <?php echo esc_html__( 'Allow new accounts from default login forms', 'ventraconnect-social-login' ); ?>
                            </span>
                        </label>
                    </div>
                </div>

                <?php
                // 3. Use popup for OAuth.
                ?>
                <div class="wsc-card wsc-card--row" style="margin-top: 16px;">
                    <div class="wsc-card__content">
                        <div class="wsc-card__title">
                            <?php echo esc_html__( 'Use popup for OAuth', 'ventraconnect-social-login' ); ?>
                        </div>
                        <p class="wsc-card__description">
                            <?php
                            echo esc_html__(
                                'Open provider authorization in a popup window (recommended). When enabled, provider auth will open in a centered popup and close after authentication. Useful for preserving the current page context.',
                                'ventraconnect-social-login'
                            );
                            ?>
                        </p>
                    </div>
                    <div class="wsc-card__control">
                        <label class="wsc-switch">
                            <input type="hidden" name="ventraconnect_sl_settings[use_popup_oauth]" value="0">
                            <input type="checkbox"
                                   class="wsc-switch-input"
                                   name="ventraconnect_sl_settings[use_popup_oauth]"
                                   value="1" <?php checked( ! empty( $settings['use_popup_oauth'] ) ); ?>>
                            <span class="wsc-switch-ui" aria-hidden="true"></span>
                            <span class="screen-reader-text">
                                <?php echo esc_html__( 'Use popup for OAuth', 'ventraconnect-social-login' ); ?>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- /General Settings card -->
<?php
// ---------- LOGIN STATUS + UPSELL CARD ----------
?>
<div class="wsc-login-status">
    <div class="wsc-login-status__header">
        <h2 class="wsc-login-status__title">
            <?php echo esc_html__( 'Login status', 'ventraconnect-social-login' ); ?>
        </h2>
        <p class="wsc-login-status__description">
            <?php echo esc_html__( 'See which login methods are active and what\'s available on your site.', 'ventraconnect-social-login' ); ?>
        </p>
    </div>

<div class="wsc-login-status__body">
    <?php
    // Determine if we have any active providers from the status text.
    // We set the "no providers" message earlier, so key off that.
    $social_inactive_prefix = __( 'No providers active', 'ventraconnect-social-login' );
    $social_has_active      = true;

    if (
        '' === trim( (string) $social_login_status_text ) ||
        0 === strpos( $social_login_status_text, $social_inactive_prefix )
    ) {
        $social_has_active = false;
    }

    $social_icon_state  = $social_has_active ? 'active' : 'warning';
    $social_icon_symbol = $social_has_active ? '✓' : '!';
    $social_config_url  = admin_url( 'admin.php?page=ventraconnect-sl-settings&tab=providers' );
    ?>

    <!-- Social Login -->
    <div class="wsc-login-status-item">
        <div class="wsc-login-status-item__icon wsc-login-status-item__icon--<?php echo esc_attr( $social_icon_state ); ?>">
            <?php echo esc_html( $social_icon_symbol ); ?>
        </div>
        <div class="wsc-login-status-item__content">
            <div class="wsc-login-status-item__label">
                <?php echo esc_html__( 'Social Login', 'ventraconnect-social-login' ); ?>
            </div>

            <?php if ( $social_has_active ) : ?>
                <div class="wsc-login-status-item__value">
                    <?php
                    printf(
                        /* translators: %s: comma-separated list of active providers. */
                        esc_html__( 'Active: %s', 'ventraconnect-social-login' ),
                        esc_html( $social_login_status_text )
                    );
                    ?>
                </div>
            <?php else : ?>
                <div class="wsc-login-status-item__value">
                    <?php echo esc_html( $social_login_status_text ); ?>
                    <a href="<?php echo esc_url( $social_config_url ); ?>" class="wsc-login-status-item__link">
                        <?php echo esc_html__( 'Configure in Social Login tab', 'ventraconnect-social-login' ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( ! $pro_active ) : ?>

        <!-- FREE: Magic Link & OTP are real, Woo + Passwordless are Pro-only -->

        <!-- Magic Link -->
        <div class="wsc-login-status-item">
            <div class="wsc-login-status-item__icon wsc-login-status-item__icon--<?php echo $magic_link_enabled ? 'active' : 'inactive'; ?>">
                <?php echo $magic_link_enabled ? '✓' : '○'; ?>
            </div>
            <div class="wsc-login-status-item__content">
                <div class="wsc-login-status-item__label">
                    <?php echo esc_html__( 'Magic Link', 'ventraconnect-social-login' ); ?>
                </div>
                <div class="wsc-login-status-item__value">
                    <?php
                    echo esc_html(
                        $magic_link_enabled
                            ? __( 'Active', 'ventraconnect-social-login' )
                            : __( 'Disabled', 'ventraconnect-social-login' )
                    );
                    ?>
                </div>
            </div>
        </div>

        <!-- OTP Email -->
        <div class="wsc-login-status-item">
            <div class="wsc-login-status-item__icon wsc-login-status-item__icon--<?php echo $otp_enabled ? 'active' : 'inactive'; ?>">
                <?php echo $otp_enabled ? '✓' : '○'; ?>
            </div>
            <div class="wsc-login-status-item__content">
                <div class="wsc-login-status-item__label">
                    <?php echo esc_html__( 'OTP Email', 'ventraconnect-social-login' ); ?>
                </div>
                <div class="wsc-login-status-item__value">
                    <?php
                    echo esc_html(
                        $otp_enabled
                            ? __( 'Active', 'ventraconnect-social-login' )
                            : __( 'Disabled', 'ventraconnect-social-login' )
                    );
                    ?>
                </div>
            </div>
        </div>

        <!-- Passkey -->
        <div class="wsc-login-status-item">
            <div class="wsc-login-status-item__icon wsc-login-status-item__icon--<?php echo esc_attr( $passkey_icon_state ); ?>">
                <?php if ( 'active' === $passkey_icon_variant ) : ?>
                    <span aria-hidden="true">&#10003;</span>
                <?php elseif ( 'warning' === $passkey_icon_variant ) : ?>
                    <span aria-hidden="true">!</span>
                <?php else : ?>
                    <span aria-hidden="true">&#9675;</span>
                <?php endif; ?>
            </div>
            <div class="wsc-login-status-item__content">
                <div class="wsc-login-status-item__label">
                    <?php echo esc_html__( 'Passkey', 'ventraconnect-social-login' ); ?>
                </div>
                <div class="wsc-login-status-item__value">
                    <?php echo esc_html( $passkey_status_text ); ?>
                </div>
            </div>
        </div>

        <!-- WooCommerce login (still Pro in Free) -->
        <?php if ( $has_woocommerce ) : ?>
            <div class="wsc-login-status-item">
                <div class="wsc-login-status-item__icon wsc-login-status-item__icon--inactive">○</div>
                <div class="wsc-login-status-item__content">
                    <div class="wsc-login-status-item__label">
                        <?php echo esc_html__( 'WooCommerce login', 'ventraconnect-social-login' ); ?>
                    </div>
                    <div class="wsc-login-status-item__value">
                        <span class="wsc-badge wsc-badge-status--info" style="margin-left:0;padding:1px 5px;font-size:10px;border:1px solid #cee5ff;color:#6173ae;">
                            <?php echo esc_html__( 'Pro', 'ventraconnect-social-login' ); ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Passwordless mode (global mode is still Pro) -->
        <div class="wsc-login-status-item">
            <div class="wsc-login-status-item__icon wsc-login-status-item__icon--inactive">○</div>
            <div class="wsc-login-status-item__content">
                <div class="wsc-login-status-item__label">
                    <?php echo esc_html__( 'Password Phaseout', 'ventraconnect-social-login' ); ?>
                </div>
                <div class="wsc-login-status-item__value">
                    <span class="wsc-badge wsc-badge-status--info" style="margin-left:0;padding:1px 5px;font-size:10px;border:1px solid #cee5ff;color:#6173ae;">
                        <?php echo esc_html__( 'Pro', 'ventraconnect-social-login' ); ?>
                    </span>
                </div>
            </div>
        </div>

    <?php else : ?>

        <!-- PRO: show real state instead of "Pro feature" -->

        <!-- Magic Link -->
        <div class="wsc-login-status-item">
            <div class="wsc-login-status-item__icon wsc-login-status-item__icon--<?php echo $magic_link_enabled ? 'active' : 'inactive'; ?>">
                <?php echo $magic_link_enabled ? '✓' : '○'; ?>
            </div>
            <div class="wsc-login-status-item__content">
                <div class="wsc-login-status-item__label">
                    <?php echo esc_html__( 'Magic Link', 'ventraconnect-social-login' ); ?>
                </div>
                <div class="wsc-login-status-item__value">
                    <?php
                    echo esc_html(
                        $magic_link_enabled
                            ? __( 'Active', 'ventraconnect-social-login' )
                            : __( 'Disabled', 'ventraconnect-social-login' )
                    );
                    ?>
                </div>
            </div>
        </div>

        <!-- OTP Email -->
        <div class="wsc-login-status-item">
            <div class="wsc-login-status-item__icon wsc-login-status-item__icon--<?php echo $otp_enabled ? 'active' : 'inactive'; ?>">
                <?php echo $otp_enabled ? '✓' : '○'; ?>
            </div>
            <div class="wsc-login-status-item__content">
                <div class="wsc-login-status-item__label">
                    <?php echo esc_html__( 'OTP Email', 'ventraconnect-social-login' ); ?>
                </div>
                <div class="wsc-login-status-item__value">
                    <?php
                    echo esc_html(
                        $otp_enabled
                            ? __( 'Active', 'ventraconnect-social-login' )
                            : __( 'Disabled', 'ventraconnect-social-login' )
                    );
                    ?>
                </div>
            </div>
        </div>

        <!-- Passkey -->
        <div class="wsc-login-status-item">
            <div class="wsc-login-status-item__icon wsc-login-status-item__icon--<?php echo esc_attr( $passkey_icon_state ); ?>">
                <?php if ( 'active' === $passkey_icon_variant ) : ?>
                    <span aria-hidden="true">&#10003;</span>
                <?php elseif ( 'warning' === $passkey_icon_variant ) : ?>
                    <span aria-hidden="true">!</span>
                <?php else : ?>
                    <span aria-hidden="true">&#9675;</span>
                <?php endif; ?>
            </div>
            <div class="wsc-login-status-item__content">
                <div class="wsc-login-status-item__label">
                    <?php echo esc_html__( 'Passkey', 'ventraconnect-social-login' ); ?>
                </div>
                <div class="wsc-login-status-item__value">
                    <?php echo esc_html( $passkey_status_text ); ?>
                </div>
            </div>
        </div>

        <!-- WooCommerce login -->
        <?php if ( $has_woocommerce ) : ?>
            <?php
            // Map Woo status label to icon style + symbol.
            $woo_icon_state  = 'inactive';
            $woo_icon_symbol = '○';

            if ( $woo_status_label === __( 'Active', 'ventraconnect-social-login' ) ) {
                $woo_icon_state  = 'active';
                $woo_icon_symbol = '✓';
            } elseif ( $woo_status_label === __( 'Installed – not configured', 'ventraconnect-social-login' ) ) {
                $woo_icon_state  = 'warning';
                $woo_icon_symbol = '!';
            }
            ?>
            <div class="wsc-login-status-item">
                <div class="wsc-login-status-item__icon wsc-login-status-item__icon--<?php echo esc_attr( $woo_icon_state ); ?>">
                    <?php echo esc_html( $woo_icon_symbol ); ?>
                </div>
                <div class="wsc-login-status-item__content">
                    <div class="wsc-login-status-item__label">
                        <?php echo esc_html__( 'WooCommerce login', 'ventraconnect-social-login' ); ?>
                    </div>
                    <div class="wsc-login-status-item__value">
                        <?php echo esc_html( $woo_status_label ); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Passwordless mode -->
        <div class="wsc-login-status-item">
            <div class="wsc-login-status-item__icon wsc-login-status-item__icon--<?php echo ( $passwordless_mode_label === 'Off' ) ? 'inactive' : 'active'; ?>">
                <?php echo ( $passwordless_mode_label === 'Off' ) ? '○' : '✓'; ?>
            </div>
            <div class="wsc-login-status-item__content">
                <div class="wsc-login-status-item__label">
                    <?php echo esc_html__( 'Password Phaseout', 'ventraconnect-social-login' ); ?>
                </div>
                <div class="wsc-login-status-item__value">
                    <?php echo esc_html( $passwordless_mode_label ); ?>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div><!-- /.wsc-login-status -->
        </div></div>
<div class="wsc-grid">
            <?php
// ---------- BUTTON STYLE CARD ----------
?>
<div id="vcsl-button-layout-theme" class="wsc-card">
    <div class="wsc-section-header">
        <h2 class="wsc-section-header__title">
            <?php echo esc_html__( 'Button layout & theme', 'ventraconnect-social-login' ); ?>
        </h2>
        <p class="wsc-section-header__description">
            <?php echo esc_html__( 'Choose between full-width labeled buttons or compact icon buttons, and set the global theme.', 'ventraconnect-social-login' ); ?>
        </p>
    </div>

    <div class="wsc-card--row wsc-card--row-split" style="margin-top: 20px; align-items:flex-start;">
        <div class="wsc-card__content">
            <div class="wsc-card__title">
                <?php echo esc_html__( 'Choose button layout', 'ventraconnect-social-login' ); ?>
            </div>
            <p class="wsc-card__description">
                <?php echo esc_html__( 'Wide (labels) or compact icons for all providers.', 'ventraconnect-social-login' ); ?>
            </p>

            <div class="vcs-layout-options">
                <label class="vcs-layout-option">
                    <input
                        type="radio"
                        name="ventraconnect_sl_settings[button_style]"
                        value="wide"
                        <?php checked( $button_style, 'wide' ); ?>
                    >
                    <span class="vcs-layout-option__text">
                        <span class="vcs-layout-option__label">
                            <?php echo esc_html__( 'Wide (labels)', 'ventraconnect-social-login' ); ?>
                        </span>
                        <span class="vcs-layout-option__hint">
                            <?php echo esc_html__( 'Best for login forms & checkout', 'ventraconnect-social-login' ); ?>
                        </span>
                    </span>
                </label>

                <label class="vcs-layout-option">
                    <input
                        type="radio"
                        name="ventraconnect_sl_settings[button_style]"
                        value="compact"
                        <?php checked( $button_style, 'compact' ); ?>
                    >
                    <span class="vcs-layout-option__text">
                        <span class="vcs-layout-option__label">
                            <?php echo esc_html__( 'Compact (icons)', 'ventraconnect-social-login' ); ?>
                        </span>
                        <span class="vcs-layout-option__hint">
                            <?php echo esc_html__( 'Best for tight headers / nav bars', 'ventraconnect-social-login' ); ?>
                        </span>
                    </span>
                </label>
            </div>
        </div>

        <div class="wsc-card__control wsc-card__control--preview" style="width: 321px; margin-top: 4px;">
            <div class="wsc-preview-group" style="
    padding: 10px;
    padding-bottom: 20px;
    background: #ffffff;
    border-radius: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
">
				<div class="live-preview">LIVE PREVIEW</div>
                <div class="wsc-preview-group__wide wsc-preview-inline wsc-preview-wide vcs-layout-preview" data-layout="wide">
                    <?php
                    // Wide style preview (no labels/hints here, just the buttons).
                    $preview_providers = array(
                        'google'   => 'Google',
                        'facebook' => 'Facebook',
                        'twitter'  => 'X',
                    );
                    $style_variant     = 'wide';
                    foreach ( $preview_providers as $slug => $label ) {
                        $icon_variants = array();
                        if ( class_exists( '\\VentraConnect\\SocialLogin\\Buttons' ) && method_exists( '\\VentraConnect\\SocialLogin\\Buttons', 'resolve_icon_source' ) ) {
                            foreach ( array( 'light', 'dark', 'minimal' ) as $icon_theme ) {
                                $icon_source                  = \VentraConnect\SocialLogin\Buttons::resolve_icon_source( $slug, $style_variant, $icon_theme );
                                $icon_variants[ $icon_theme ] = is_array( $icon_source ) ? ( $icon_source['svg'] ?? '' ) : '';
                            }
                        }
                        if ( empty( $icon_variants['light'] ) && defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ) {
                            $svg_path = VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $slug . '.svg';
                            $svg      = ( $svg_path && file_exists( $svg_path ) ) ? file_get_contents( $svg_path ) : '';
                            foreach ( array( 'light', 'dark', 'minimal' ) as $icon_theme ) {
                                if ( empty( $icon_variants[ $icon_theme ] ) ) {
                                    $icon_variants[ $icon_theme ] = $svg;
                                }
                            }
                        }
                        $svg_current     = $icon_variants[ $global_theme ] ?? ( reset( $icon_variants ) ?: '' );
                        $icon_data_attrs = '';
                        foreach ( array( 'light', 'dark', 'minimal' ) as $icon_theme ) {
                            $encoded          = base64_encode( $icon_variants[ $icon_theme ] ?? '' );
                            $icon_data_attrs .= ' data-icon-' . esc_attr( $icon_theme ) . '="' . esc_attr( $encoded ) . '"';
                        }
                        echo '<span class="wsc-buttons wsc-style-wide vcs-admin-general-preview">';
                        echo '<a class="vcs-btn vcs-btn--wide wsc-button wsc-button-' . esc_attr( $slug ) . '" href="#" onclick="return false;" data-provider="' . esc_attr( $slug ) . '" data-theme="' . esc_attr( $global_theme ) . '"';
                        echo wp_kses_data(
                            ventraconnect_sl_attr_string(
                                $icon_data_attrs,
                                array(
                                    'id',
                                    'class',
                                    'style',
                                    'aria-label',
                                    'aria-hidden',
                                    'role',
                                    'data-provider',
                                    'data-action',
                                    'data-icon',
                                    'data-icon-light',
                                    'data-icon-dark',
                                    'data-icon-minimal',
                                    'data-variant',
                                )
                            )
                        );
                        echo '>';
                        echo '<span class="vcs-btn__icon" aria-hidden="true">' . wp_kses_post( $svg_current ) . '</span>';
                        echo '<span class="vcs-btn__label">' . esc_html(
                            sprintf(
                                /* translators: 1: Provider label. */
                                __( 'Continue with %1$s', 'ventraconnect-social-login' ),
                                $label
                            )
                        ) . '</span>';
                        echo '</a>';
                        echo '</span>';
                    }
                    ?>
                </div>

                <div class="wsc-preview-group__compact wsc-preview-inline wsc-preview-compact vcs-layout-preview" data-layout="compact" style="margin-left: 145px; display: none;">
                    <?php
                    // Compact style preview.
                    $preview_providers = array(
                        'google'   => 'Google',
                        'facebook' => 'Facebook',
                        'twitter'  => 'X',
                    );
                    $style_variant     = 'compact';
                    foreach ( $preview_providers as $slug => $label ) {
                        $icon_variants = array();
                        if ( class_exists( '\\VentraConnect\\SocialLogin\\Buttons' ) && method_exists( '\\VentraConnect\\SocialLogin\\Buttons', 'resolve_icon_source' ) ) {
                            foreach ( array( 'light', 'dark', 'minimal' ) as $icon_theme ) {
                                $icon_source                  = \VentraConnect\SocialLogin\Buttons::resolve_icon_source( $slug, $style_variant, $icon_theme );
                                $icon_variants[ $icon_theme ] = is_array( $icon_source ) ? ( $icon_source['svg'] ?? '' ) : '';
                            }
                        }
                        if ( empty( $icon_variants['light'] ) && defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ) {
                            $svg_path = VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $slug . '.svg';
                            $svg      = ( $svg_path && file_exists( $svg_path ) ) ? file_get_contents( $svg_path ) : '';
                            foreach ( array( 'light', 'dark', 'minimal' ) as $icon_theme ) {
                                if ( empty( $icon_variants[ $icon_theme ] ) ) {
                                    $icon_variants[ $icon_theme ] = $svg;
                                }
                            }
                        }
                        $svg_current     = $icon_variants[ $global_theme ] ?? ( reset( $icon_variants ) ?: '' );
                        $icon_data_attrs = '';
                        foreach ( array( 'light', 'dark', 'minimal' ) as $icon_theme ) {
                            $encoded          = base64_encode( $icon_variants[ $icon_theme ] ?? '' );
                            $icon_data_attrs .= ' data-icon-' . esc_attr( $icon_theme ) . '="' . esc_attr( $encoded ) . '"';
                        }
                        echo '<span class="wsc-buttons wsc-style-compact vcs-admin-general-preview">';
                        echo '<a class="vcs-btn vcs-btn--compact wsc-button wsc-button-' . esc_attr( $slug ) . '" href="#" onclick="return false;" data-provider="' . esc_attr( $slug ) . '" data-theme="' . esc_attr( $global_theme ) . '"';
                        echo wp_kses_data(
                            ventraconnect_sl_attr_string(
                                $icon_data_attrs,
                                array(
                                    'id',
                                    'class',
                                    'style',
                                    'aria-label',
                                    'aria-hidden',
                                    'role',
                                    'data-provider',
                                    'data-action',
                                    'data-icon',
                                    'data-icon-light',
                                    'data-icon-dark',
                                    'data-icon-minimal',
                                    'data-variant',
                                )
                            )
                        );
                        echo ' aria-label="' . esc_attr(
                            sprintf(
                                /* translators: 1: Provider label. */
                                __( 'Continue with %1$s', 'ventraconnect-social-login' ),
                                $label
                            )
                        ) . '">';
                        echo '<span class="vcs-btn__icon" aria-hidden="true">' . wp_kses_post( $svg_current ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inline SVG preview markup is restricted with wp_kses_post().
                        echo '<span class="vcs-btn__label" style="display:none">' . esc_html(
                            sprintf(
                                /* translators: 1: Provider label. */
                                __( 'Continue with %1$s', 'ventraconnect-social-login' ),
                                $label
                            )
                        ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Label text is escaped with esc_html() before output.
                        echo '</a>';
                        echo '</span>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:24px;">
        <div class="wsc-card__title" style="font-size:var(--wsc-text-base);margin-bottom:8px;">
            <?php echo esc_html__( 'Global theme', 'ventraconnect-social-login' ); ?>
        </div>
        <fieldset style="display:flex;flex-wrap:wrap;gap:12px;">
            <?php foreach ( array( 'light' => __( 'Light', 'ventraconnect-social-login' ), 'dark' => __( 'Dark', 'ventraconnect-social-login' ), 'minimal' => __( 'Minimal', 'ventraconnect-social-login' ) ) as $val => $lab ) : ?>
                <label class="ventraconnect-theme-options">
                    <input type="radio"
                           name="ventraconnect_sl_settings[global_theme]"
                           value="<?php echo esc_attr( $val ); ?>" <?php checked( $global_theme, $val ); ?>>
                    <?php echo esc_html( $lab ); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <p class="wsc-input-hint" style="margin-top:6px;">
            <?php echo esc_html__( 'Applies to all providers unless overridden per provider.', 'ventraconnect-social-login' ); ?>
        </p>
        <p class="wsc-input-hint" style="margin-top:4px;color:#6b7280;">
            <?php echo esc_html__( 'Applies to all providers unless overridden per provider. Additional themes and per-provider overrides are available in Pro.', 'ventraconnect-social-login' ); ?>
        </p>
    </div>

    <script type="text/javascript">
(function () {
    function decodeIconPayload(encoded) {
        if (!encoded) return '';
        try { return decodeURIComponent(escape(window.atob(encoded))); }
        catch (e) {
            try { return window.atob(encoded); }
            catch (err) { return ''; }
        }
    }

    function updateGeneralPreviews(theme) {
        document.querySelectorAll('.vcs-admin-general-preview a').forEach(function (a) {
            if (!a) return;
            a.setAttribute('data-theme', theme);
            var iconWrap = a.querySelector('.vcs-btn__icon');
            if (!iconWrap) return;
            var encoded = a.getAttribute('data-icon-' + theme) || '';
            var decoded = decodeIconPayload(encoded) || '';
            if (decoded) iconWrap.innerHTML = decoded;
        });
    }

    // --- NEW: toggle wide vs compact previews ---
    function updateLayoutPreviews() {
        var selected = 'wide';
        var checked = document.querySelector('input[name="ventraconnect_sl_settings[button_style]"]:checked');
        if (checked && checked.value) {
            selected = checked.value;
        }

        document.querySelectorAll('.vcs-layout-preview').forEach(function (el) {
            var layout = el.getAttribute('data-layout');
            // show only the selected layout
            el.style.display = (layout === selected) ? '' : 'none';
        });
    }

    var layoutInputs = document.querySelectorAll('input[name="ventraconnect_sl_settings[button_style]"]');
    layoutInputs.forEach(function (input) {
        input.addEventListener('change', updateLayoutPreviews);
    });

    // --- existing theme radio wiring ---
    var themeInputs = document.querySelectorAll('input[name="ventraconnect_sl_settings[global_theme]"]');
    themeInputs.forEach(function (r) {
        r.addEventListener('change', function () {
            if (this.checked) updateGeneralPreviews(this.value);
        });
    });
    var initialTheme = Array.prototype.find.call(themeInputs, function (input) { return input.checked; });
    if (initialTheme) {
        updateGeneralPreviews(initialTheme.value);
    }

    // Initial layout state on page load
    updateLayoutPreviews();
})();
</script>
</div><!-- /Button Style card -->

				</div>
                <div class="wsc-grid">
<div id="vcsl-email-branding" class="wsc-card">
    <div class="wsc-section-header">
        <h2 class="wsc-section-header__title">
            <?php echo esc_html__( 'Passwordless Email Branding', 'ventraconnect-social-login' ); ?>
            <span class="wsc-badge wsc-badge-status--info" style="margin-left:8px;padding:1px 5px;font-size:10px;border:1px solid #cee5ff;color:#6173ae;"><?php echo esc_html__( 'Pro', 'ventraconnect-social-login' ); ?></span>
        </h2>
        <p class="wsc-section-header__description">
            <?php echo esc_html__( 'Customize the shared email wrapper for Magic Link, Email OTP, and Passkey verification emails.', 'ventraconnect-social-login' ); ?>
        </p>
    </div>
    <?php if ( ! $pro_active ) : ?>
        <p class="wsc-input-hint" style="margin:0 0 16px 0;">
            <?php echo esc_html__( 'VentraConnect already includes polished default passwordless emails. Upgrade to Pro to add your logo, accent color, and footer text.', 'ventraconnect-social-login' ); ?>
        </p>
    <?php endif; ?>
    <div class="vcs-branding-controls<?php echo $pro_active ? '' : ' is-locked'; ?>"<?php echo $pro_active ? '' : ' style="opacity:.68;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:16px;"'; ?>>

    <div class="wsc-card wsc-card--row" style="margin-top:20px;">
        <div class="wsc-card__content">
            <div class="wsc-card__title"><?php echo esc_html__( 'Enable branded wrapper', 'ventraconnect-social-login' ); ?></div>
            <p class="wsc-card__description"><?php echo esc_html__( 'When enabled, the shared passwordless email wrapper can show a logo, accent color, and footer text.', 'ventraconnect-social-login' ); ?></p>
        </div>
        <div class="wsc-card__control">
            <?php if ( $pro_active ) : ?>
            <input type="hidden" name="ventraconnect_sl_settings[passwordless_email_branding][enabled]" value="0">
            <?php endif; ?>
            <label class="wsc-switch">
                <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_settings[passwordless_email_branding][enabled]" value="1" <?php checked( $branding_enabled ); ?><?php echo $branding_disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static disabled attribute fragment from boolean Pro state only. ?>>
                <span class="wsc-switch-ui" aria-hidden="true"></span>
                <span class="screen-reader-text"><?php echo esc_html__( 'Enable branded wrapper', 'ventraconnect-social-login' ); ?></span>
            </label>
        </div>
    </div>

    <div class="wsc-field wsc-field--vertical" style="margin-top:16px;">
        <label class="wsc-field__label"><?php echo esc_html__( 'Logo', 'ventraconnect-social-login' ); ?></label>
        <div class="wsc-field__control">
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="url" class="wsc-input vcs-branding-logo-url" name="ventraconnect_sl_settings[passwordless_email_branding][logo_url]" value="<?php echo esc_attr( $branding_logo_url ); ?>" placeholder="https://example.com/logo.png" style="flex:1 1 360px;"<?php echo $branding_disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static disabled attribute fragment from boolean Pro state only. ?>>
                <button type="button" class="button vcs-branding-logo-upload"<?php echo $branding_disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static disabled attribute fragment from boolean Pro state only. ?>><?php echo esc_html__( 'Select logo', 'ventraconnect-social-login' ); ?></button>
            </div>
            <p class="wsc-input-hint" style="margin-top:6px;"><?php echo esc_html__( 'Choose an image from the Media Library or paste an image URL. Recommended: use a logo around 200–300px wide with a transparent or white background.', 'ventraconnect-social-login' ); ?></p>
        </div>
    </div>

    <div class="wsc-field wsc-field--vertical" style="margin-top:16px;">
        <label class="wsc-field__label"><?php echo esc_html__( 'Accent color', 'ventraconnect-social-login' ); ?></label>
        <div class="wsc-field__control">
            <input type="text" class="wsc-input vcs-color-picker" name="ventraconnect_sl_settings[passwordless_email_branding][accent_color]" value="<?php echo esc_attr( $branding_accent_color ); ?>" data-default-color="#1d4ed8"<?php echo $branding_disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static disabled attribute fragment from boolean Pro state only. ?>>
        </div>
    </div>

    <div class="wsc-field wsc-field--vertical" style="margin-top:16px;">
        <label class="wsc-field__label"><?php echo esc_html__( 'Footer text', 'ventraconnect-social-login' ); ?></label>
        <div class="wsc-field__control">
            <textarea class="wsc-textarea" rows="3" name="ventraconnect_sl_settings[passwordless_email_branding][footer_text]" placeholder="<?php echo esc_attr__( 'Need help? Contact support@example.com. If you did not request this sign-in email, you can safely ignore it.', 'ventraconnect-social-login' ); ?>"<?php echo $branding_disabled_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static disabled attribute fragment from boolean Pro state only. ?>><?php echo esc_textarea( $branding_footer_text ); ?></textarea>
            <p class="wsc-input-hint" style="margin-top:6px;"><?php echo esc_html__( 'Good footer text is short and trust-focused, such as support, legal, or security guidance.', 'ventraconnect-social-login' ); ?></p>
        </div>
    </div>
</div>
</div>
            </div>
<div class="wsc-grid">
            <?php
            // ---------- REDIRECTS / PRIVACY / SHORTCODES / DEBUG CARD ----------
            ?>
            <div id="vcsl-redirects-security" class="wsc-card">
                <div class="wsc-section-header">
                    <h2 class="wsc-section-header__title">
                        <?php echo esc_html__( 'Redirects & Security', 'ventraconnect-social-login' ); ?>
                    </h2>
                    <p class="wsc-section-header__description">
                        <?php echo esc_html__( 'Control where users land after social login and block unsafe redirect targets.', 'ventraconnect-social-login' ); ?>
                    </p>
                </div>

                <!-- Prevent external redirect overrides -->
                <div class="wsc-card wsc-card--row" style="margin-top:20px;">
                    <div class="wsc-card__content">
                        <div class="wsc-card__title">
                            <?php echo esc_html__( 'Prevent external redirect overrides', 'ventraconnect-social-login' ); ?>
                        </div>
                        <p class="wsc-card__description">
                            <?php echo esc_html__( 'Ignore redirect URLs passed in the request and keep users on safe, whitelisted URLs after login. Helps prevent open-redirect issues.', 'ventraconnect-social-login' ); ?>
                        </p>
                    </div>
                    <div class="wsc-card__control">
                        <label class="wsc-switch">
                            <input type="checkbox"
                                   class="wsc-switch-input"
                                   name="ventraconnect_sl_settings[prevent_external_override]"
                                   value="1" <?php checked( $prevent_external ); ?>>
                            <span class="wsc-switch-ui" aria-hidden="true"></span>
                            <span class="screen-reader-text">
                                <?php echo esc_html__( 'Disable external redirects', 'ventraconnect-social-login' ); ?>
                            </span>
                        </label>
                    </div>
                </div>

         <!-- Default redirect URL -->
<div class="wsc-card wsc-card--row" style="margin-top:16px;align-items:flex-start;">
    <div class="wsc-card__content">
        <div class="wsc-card__title">
            <?php echo esc_html__( 'Default redirect URL', 'ventraconnect-social-login' ); ?>
        </div>
        <p class="wsc-card__description">
            <?php echo esc_html__( 'If empty, VentraConnect falls back to the original page, referrer, or the WordPress dashboard.', 'ventraconnect-social-login' ); ?>
        </p>
    </div>
    <div class="wsc-card__control">
        <div class="wsc-form-stack" style="min-width: 400px;">
            <div class="wsc-form-group">
                <label class="wsc-form-label">
                    <?php echo esc_html__( 'for Login', 'ventraconnect-social-login' ); ?>
                </label>
                <input type="url"
                       class="wsc-input"
                       name="ventraconnect_sl_settings[redirect_default_login]"
                       value="<?php echo esc_attr( $redirect_default_login ); ?>"
                       placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>">
            </div>
            <div class="wsc-form-group">
                <label class="wsc-form-label">
                    <?php echo esc_html__( 'for Register', 'ventraconnect-social-login' ); ?>
                </label>
                <input type="url"
                       class="wsc-input"
                       name="ventraconnect_sl_settings[redirect_default_register]"
                       value="<?php echo esc_attr( $redirect_default_register ); ?>"
                       placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>">
            </div>
            <p class="wsc-input-hint">
                <?php echo esc_html__( 'If empty, falls back to the referer URL or admin dashboard.', 'ventraconnect-social-login' ); ?>
            </p>
        </div>
    </div>
</div>

<!-- Blacklisted redirects -->
<div class="wsc-card wsc-card--row" style="margin-top:16px;align-items:flex-start;">
    <div class="wsc-card__content">
        <div class="wsc-card__title">
            <?php echo esc_html__( 'Blacklisted redirects', 'ventraconnect-social-login' ); ?>
        </div>
        <p class="wsc-card__description">
            <?php echo esc_html__( 'Use this to block unsafe destinations (e.g. external spam URLs) even if they’re passed via redirect parameters.', 'ventraconnect-social-login' ); ?>
        </p>
    </div>
    <div class="wsc-card__control">
        <div class="wsc-form-stack" style="min-width: 400px;">
            <div class="wsc-form-group">
                <label class="wsc-form-label">
                    <?php echo esc_html__( 'Blocked URL patterns', 'ventraconnect-social-login' ); ?>
                </label>
                <textarea class="wsc-input"
                          rows="4"
                          name="ventraconnect_sl_settings[redirect_blacklist]"
                          style="font-family: 'Courier New', monospace; font-size: 13px;"
                          placeholder="/wp-admin&#10;?preview=&#10;example.com/unwanted"><?php echo esc_textarea( implode( "\n", $redirect_blacklist ) ); ?></textarea>
            </div>
            <p class="wsc-input-hint">
                <?php echo esc_html__( 'Block matching patterns (one per line, case-insensitive).', 'ventraconnect-social-login' ); ?>
            </p>
        </div>
    </div>
</div>
</div>
</div>
<div class="wsc-grid">
<div class="wsc-card">
                <div class="wsc-section-header">
                    <h2 class="wsc-section-header__title">
                        <?php echo esc_html__( 'Privacy & Data Consent', 'ventraconnect-social-login' ); ?>
                    </h2>
                    <p class="wsc-section-header__description">
                        <?php echo esc_html__( 'Confirm that your privacy policy covers social login and the data shared by providers.', 'ventraconnect-social-login' ); ?>
                    </p>
                </div>
<!-- Privacy -->
<div class="wsc-card wsc-card--row" style="margin-top:16px;align-items:flex-start;">
    <div class="wsc-card__control" style="width: 100%;">
        <label class="wsc-checkbox-inline" style="display: flex; align-items: flex-start; gap: 10px;">
            <input type="checkbox"
                   name="ventraconnect_sl_settings[privacy_notice_ack]"
                   value="1" <?php checked( ! empty( $settings['privacy_notice_ack'] ) ); ?>>
            <span class="wsc-checkbox-inline__label" style="flex: 1;">
                <?php echo esc_html__( 'I’ve updated my privacy policy to cover social login.', 'ventraconnect-social-login' ); ?>
            </span>
        </label>
        <p class="wsc-input-hint" style="margin-top: 8px;">
            <?php
            $privacy = __( 'This plugin requests basic profile data (email, name, avatar, URL). Users can unlink access from their provider account page.', 'ventraconnect-social-login' );
            echo wp_kses_post( apply_filters( 'ventraconnect_sl_privacy_notice_html', esc_html( $privacy ) ) );
            ?>
        </p>
    </div>
</div>
</div>
</div>
<div class="wsc-grid">
	<div id="vcsl-shortcodes-template-usage" class="wsc-card">
                <div class="wsc-section-header">
                    <h2 class="wsc-section-header__title">
                        <?php echo esc_html__( 'Shortcodes & Template Usage', 'ventraconnect-social-login' ); ?>
                    </h2>
                    <p class="wsc-section-header__description">
                        <?php echo esc_html__( 'Use these shortcodes on normal WordPress pages, widgets, and templates.', 'ventraconnect-social-login' ); ?>
                    </p>
                </div>
<!-- Shortcodes -->
<div class="wsc-card wsc-card--row" style="margin-top:16px;align-items:flex-start;">
    <div class="wsc-card__control" style="width: 100%;">
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div>
                <label class="wsc-form-label" style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    <?php echo esc_html__( 'Main shortcode', 'ventraconnect-social-login' ); ?>
                </label>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input id="vcs-shortcode-main"
                           type="text"
                           class="wsc-input"
                           readonly
                           value='[ventraconnect_sl_social_login providers="all"]'
                           style="flex:1; font-family: 'Courier New', monospace; font-size: 13px;">
                    <button type="button"
                            class="button"
                            data-copy="#vcs-shortcode-main"
                            data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>"
                            data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"
                            style="flex-shrink: 0;">
                        <?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?>
                    </button>
                </div>
            </div>
            <p class="wsc-input-hint" style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                <?php
                echo esc_html__(
                    "Use the shortcode on normal WordPress pages and templates. LMS, membership, community, and WooCommerce login/register/account/checkout forms use Pro integration placement settings. Advanced passkey placement on those integration-owned surfaces is included in Pro.",
                    'ventraconnect-social-login'
                );
                ?>
            </p>
            <div style="display:flex;flex-direction:column;gap:6px;padding:12px;background:#f8fafc;border-radius:6px;">
                <code style="font-size:12px;color:#475569;">[ventraconnect_sl_social_login providers="all"]</code>
                <code style="font-size:12px;color:#475569;">[ventraconnect_sl_social_login providers="google,facebook"]</code>
                <code style="font-size:12px;color:#475569;">[ventraconnect_sl_social_login providers="passkey"]</code>
                <code style="font-size:12px;color:#475569;">[ventraconnect_sl_social_login providers="magic_link, otp_email"]</code>
            </div>
        </div>
    </div>
</div>
			</div>
			</div>
<script type="text/javascript">
(function () {
    function copyText(text) {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.pointerEvents = 'none';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();

            try {
                var success = document.execCommand('copy');
                document.body.removeChild(textarea);
                if (success) {
                    resolve();
                } else {
                    reject(new Error('Copy command failed'));
                }
            } catch (error) {
                document.body.removeChild(textarea);
                reject(error);
            }
        });
    }

    function wireCopyButton(button) {
        if (!button || button.dataset.copyBound === '1') {
            return;
        }

        button.dataset.copyBound = '1';

        button.addEventListener('click', function () {
            var targetSelector = button.getAttribute('data-copy');
            if (!targetSelector) {
                return;
            }

            var target = document.querySelector(targetSelector);
            if (!target) {
                return;
            }

            var text = '';
            if ('value' in target && typeof target.value === 'string') {
                text = target.value;
            } else {
                text = target.textContent || '';
            }

            var defaultLabel = button.getAttribute('data-label') || button.textContent;
            var copiedLabel = button.getAttribute('data-copied-label') || defaultLabel;

            copyText(text).then(function () {
                button.textContent = copiedLabel;
                window.setTimeout(function () {
                    button.textContent = defaultLabel;
                }, 1600);
            }).catch(function () {
                button.textContent = defaultLabel;
            });
        });
    }

    document.querySelectorAll('[data-copy]').forEach(wireCopyButton);
})();
</script>
<div class="wsc-grid">
	<div class="wsc-card">
				<div class="wsc-section-header">
					<h2 class="wsc-section-header__title">
						<?php echo esc_html__( 'Debug & Diagnostics', 'ventraconnect-social-login' ); ?>
					</h2>
					<p class="wsc-section-header__description">
						<?php echo esc_html__( 'Enable logging and integration debug helpers while troubleshooting.', 'ventraconnect-social-login' ); ?>
					</p>
				</div>
<!-- Debug -->
<div class="wsc-card wsc-card--row" style="margin-top:20px;border-top:1px solid #edf2f7;padding-top:16px;align-items:flex-start;">

    <div class="wsc-card__control" style="width: 100%;">
        <div style="display:flex;flex-direction:column;gap:16px;">
            <div>
                <label class="wsc-checkbox-inline" style="display: flex; align-items: flex-start; gap: 10px;">
                    <input type="checkbox"
                           name="ventraconnect_sl_settings[debug_mode]"
                           value="1" <?php checked( $debug_mode ); ?>>
                    <span style="flex: 1;">
                        <span style="display: block; font-weight: 600; color: #1a202c; margin-bottom: 4px;">
                            <?php echo esc_html__( 'Debug mode', 'ventraconnect-social-login' ); ?>
                        </span>
                        <span style="display: block; font-size: 12px; color: #64748b; line-height: 1.5;">
                            <?php echo esc_html__( 'Logs OAuth errors to the PHP error log and surfaces extra details in admin notices.', 'ventraconnect-social-login' ); ?>
                        </span>
                    </span>
                </label>
            </div>

            <?php if ( $isPro() ) : ?>
                <div>
                    <?php $integration_debug = ! empty( $settings['integration_debug'] ); ?>
                    <label class="wsc-checkbox-inline" style="display: flex; align-items: flex-start; gap: 10px;">
                        <input type="checkbox"
                               name="ventraconnect_sl_settings[integration_debug]"
                               value="1" <?php checked( $integration_debug ); ?>>
                        <span style="flex: 1;">
                            <span style="display: block; font-weight: 600; color: #1a202c; margin-bottom: 4px;">
                                <?php echo esc_html__( 'Integration debug (Pro)', 'ventraconnect-social-login' ); ?>
                            </span>
                            <span style="display: block; font-size: 12px; color: #64748b; line-height: 1.5;">
                                <?php echo esc_html__( 'Records each integration hook to the debug log and displays a placement badge for administrators on the front-end.', 'ventraconnect-social-login' ); ?>
                            </span>
                        </span>
                    </label>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
			</div>


        
							</div>


    </div><!-- /#wsc-tab-general -->
    <?php
    if ( $preview_only ) {
        $html = (string) ob_get_clean();
        echo wp_kses_post( $this->apply_preview_filters( $html ) );
    }
}

	/**
	 * Render the Password Phaseout tab.
	 *
	 * @param array $state Current settings state.
	 */
	public function renderPasswordlessTab( array $state, bool $is_active = false ): void {
		$preview_only = ! empty( $state['preview_only'] );
		if ( $preview_only ) {
			ob_start();
		}

		$settings = isset( $state['settings'] ) && is_array( $state['settings'] ) ? $state['settings'] : array();
		$passwordless_mode = isset( $settings['passwordless_mode'] ) ? (string) $settings['passwordless_mode'] : 'off';
		if ( ! in_array( $passwordless_mode, array( 'off', 'recommended', 'strict' ), true ) ) {
			$passwordless_mode = 'off';
		}
		?>
		<div id="wsc-tab-passwordless" class="wsc-tab<?php echo $is_active ? ' is-active' : ''; ?>">
			<div class="wsc-card">
				<h3><?php echo esc_html__( 'Password Phaseout', 'ventraconnect-social-login' ); ?></h3>
				<p class="wsc-muted">
					<?php echo esc_html__( 'Configure how VentraConnect phases out password login across your site. In Recommended mode, customers sign in with Social Login, magic links, one-time codes, or passkeys. Password login is blocked for normal users but still available to site administrators as a fallback.', 'ventraconnect-social-login' ); ?>
				</p>
                <div class="wsc-help-intro">
    <h3>How Password Phaseout works</h3>
    <p>
        VentraConnect helps you reduce password dependency by letting customers sign in without a password using Social Login, Magic Link, one-time codes (OTP), or passkeys:
    </p>
    <ul class="wsc-list-arrow">
        <li>We add passwordless buttons to supported login and registration forms.</li>
        <li>Depending on the mode you choose, password fields may stay visible or be hidden entirely.</li>
        <li>Site administrators can still use their username and password to access wp-admin unless you deliberately remove that fallback.</li>
    </ul>
    <p>
        Use the options below to decide how aggressively you want to phase out password login.
    </p>
</div>
				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<div class="vcs-passwordless-woo-callout">
						<p><strong><?php echo esc_html__( 'WooCommerce & Password Phaseout', 'ventraconnect-social-login' ); ?></strong></p>
						<p><?php echo esc_html__( 'When Password Phaseout is enabled, customers will sign in using Social Login, email magic links, one-time codes, or passkeys instead of a password.', 'ventraconnect-social-login' ); ?></p>
						<p><?php echo esc_html__( 'WooCommerce new account emails or custom templates may still mention setting or changing a password. We recommend reviewing those email templates so the wording matches your passwordless login flow.', 'ventraconnect-social-login' ); ?></p>
					</div>
				<?php endif; ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row" style="width: 140px;"><?php echo esc_html__( 'Password Phaseout', 'ventraconnect-social-login' ); ?></th>
						<td>
							<div class="vcsl-passwordless-layout">
								<div class="vcsl-passwordless-layout__main">
									<p class="pl-description">
										<?php esc_html_e( 'Step 1. Choose how to phase out password login', 'ventraconnect-social-login' ); ?>
									</p>
  							<?php
  							// Show only errors for this settings group inline on the Passwordless tab,
  							// without using core .notice boxes (to avoid being moved to the top by JS).
  							if ( function_exists( 'get_settings_errors' ) ) {
  								$errors = get_settings_errors( 'ventraconnect_sl_settings' );
  								if ( ! empty( $errors ) ) {
  									echo '<div class="vcsl-passwordless-errors description">';
  									foreach ( $errors as $err ) {
  										$type    = isset( $err['type'] ) ? $err['type'] : 'error';
  										$message = isset( $err['message'] ) ? (string) $err['message'] : '';
  										if ( '' === $message ) {
  											continue;
  										}

  										$class = ( 'error' === $type ) ? 'vcsl-error' : 'vcsl-warning';

  										echo '<p class="' . esc_attr( $class ) . '">';
  										echo esc_html( $message );
  										echo '</p>';
  									}
  									echo '</div>';
  								}
  							}
  							?>
  							<fieldset>
    <div class="wsc-radio-group">
        <label class="wsc-radio-row" style="width:500px;">
            <input type="radio" name="ventraconnect_sl_settings[passwordless_mode]" value="off" <?php checked( $passwordless_mode, 'off' ); ?>>
            <div class="wsc-radio-content">
                <div class="wsc-radio-title"><?php echo esc_html__( 'Off', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-radio-hint"><?php echo esc_html__( 'Password phaseout is optional', 'ventraconnect-social-login' ); ?></p>
                    <ul class="wsc-list-gradient" style="margin-top: 12px;">
        <li>Standard username + password stays visible and works normally on all forms.</li>
        <li>Social Login, Magic Link, and OTP buttons still appear wherever you’ve enabled them.</li>
        <li>No logins are blocked or redirected into passwordless – users can choose passwords or passwordless freely.</li>
    </ul>
            </div>
        </label>
        
        <label class="wsc-radio-row" style="width:500px;">
            <input type="radio" name="ventraconnect_sl_settings[passwordless_mode]" value="recommended" <?php checked( $passwordless_mode, 'recommended' ); ?>>
            <div class="wsc-radio-content">
                <div class="wsc-radio-title"><?php echo esc_html__( 'Recommended', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-radio-hint"><?php echo esc_html__( 'Passwordless methods are primary; passwords allowed for admins only', 'ventraconnect-social-login' ); ?></p>
                    <ul class="wsc-list-gradient" style="margin-top: 12px;">
        <li>On supported forms, users see both normal login fields and passwordless buttons (Social, Magic Link, OTP).</li>
        <li>For normal users, password logins are blocked at the server: they’ll see a clear “password login is disabled” message and are asked to use passwordless instead.</li>
        <li>Site admins (manage_options / super admins) can still log in with username + password on /wp-login.php.</li>
        <li>Best balance between security and “I don’t want to lock myself out”.</li>
    </ul>
            </div>
        </label>
        
        <label class="wsc-radio-row" style="width:500px;">
            <input type="radio" name="ventraconnect_sl_settings[passwordless_mode]" value="strict" <?php checked( $passwordless_mode, 'strict' ); ?>>
            <div class="wsc-radio-content">
                <div class="wsc-radio-title"><?php echo esc_html__( 'Strict', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-radio-hint"><?php echo esc_html__( 'Phase out password login completely on supported forms. Maximum security, no password fields shown.', 'ventraconnect-social-login' ); ?></p>
                    <ul class="wsc-list-gradient" style="margin-top: 12px;">
        <li>On supported login/registration/checkout forms, password fields are hidden – users only see passwordless options (Social, Magic Link, OTP).</li>
        <li>For normal users there is no working password path; password logins are fully blocked.</li>
        <li>Site owners still have an internal “break glass” way to temporarily re-enable passwords if something goes wrong (see emergency note below).</li>
        <li>This is the maximum-security mode: if all passwordless methods fail, users won’t be able to log in until you use the emergency path or change the mode.</li>
    </ul>
            </div>
        </label>
        <div class="wsc-notice wsc-notice--info">
            <div class="wsc-notice__icon">ℹ️</div>
            <div class="wsc-notice__content">
      
      <details class="wsc-help-emergency">
  <summary class="wsc-notice__title">Emergency access for site owners</summary>
  <p><strong>Optional safety net.</strong> You only need this if you misconfigure Password Phaseout and lock yourself out.</p>

    <p>
        If you accidentally misconfigure Password Phaseout and lock yourself out, you can temporarily allow normal
        password logins again:
    </p>

    <ol>
        <li>Open your <code>wp-config.php</code> file.</li>
        <li>
            Add this line near the other <code>define()</code> statements:
            <pre><code>define( 'VENTRACONNECT_ALLOW_PASSWORD', true );</code></pre>
        </li>
        <li>Log in with your normal username and password.</li>
        <li>Fix your Password Phaseout settings.</li>
        <li>Remove that line from <code>wp-config.php</code> when you’re done.</li>
    </ol>

    <p class="description">
        This override restores normal password logins for everyone while it’s active. Only someone with access to wp-config.php can turn it on, and it does not add any special emergency login URL for normal users.
    </p>
</details>
    </div>
        </div>
    </div>
</fieldset>
								</div>

								<aside class="vcsl-passwordless-layout__side">
	<p class="pl-description">
		<?php esc_html_e( 'Step 2. Check readiness', 'ventraconnect-social-login' ); ?>
	</p>
	<?php
	// Status box for passwordless configuration.
	// Fetch readiness data from Pro when available; otherwise use fallback values
	if ( class_exists( '\\VentraConnect\\SocialLogin\\Passwordless' ) ) {
		// Pro active: use real readiness data
		$methods  = Passwordless::get_enabled_methods();
		$contexts = Passwordless::get_login_context_status();
	} else {
		// Free mode: fallback/mock data for preview
		$methods = array(
			'social'        => false,
			'social_count'  => 0,
			'magic_link'    => false,
			'otp'           => false,
			'passkey'       => false,
			'enabled_count' => 0,
		);

		$contexts = array(
			'wp_login' => array(
				'label'     => __( 'WordPress login', 'ventraconnect-social-login' ),
				'available' => true,
				'enabled'   => false,
			),
		);
	}

		$enabled_methods = 0;
		foreach ( array( 'social', 'magic_link', 'otp', 'passkey' ) as $key ) {
			if ( ! empty( $methods[ $key ] ) ) {
				$enabled_methods++;
			}
		}

		$recommended_ready = ( $enabled_methods >= 1 );
		$strict_ready      = ( $enabled_methods >= 2 );

		echo '<div class="wsc-readiness-panel">';

		echo '<div class="wsc-readiness-panel__header">';
		echo '<h2 class="wsc-readiness-panel__title">' . esc_html__( 'Password Phaseout Readiness', 'ventraconnect-social-login' ) . '</h2>';

		echo '<div class="wsc-readiness-panel__status">';
		
		// Recommended mode status
		echo '<div class="wsc-readiness-panel__status-item">';
		echo '<span class="wsc-readiness-panel__status-label">' . esc_html__( 'Recommended mode:', 'ventraconnect-social-login' ) . '</span>';
		echo '<span class="wsc-readiness-panel__status-value">';
		if ( $recommended_ready ) {
			echo esc_html__( 'Ready to enable (at least one passwordless method is active).', 'ventraconnect-social-login' );
		} else {
			echo esc_html__( 'Not ready yet – enable at least one passwordless method.', 'ventraconnect-social-login' );
		}
		echo '</span>';
		echo '</div>';

		// Strict mode status
		echo '<div class="wsc-readiness-panel__status-item">';
		echo '<span class="wsc-readiness-panel__status-label">' . esc_html__( 'Strict mode:', 'ventraconnect-social-login' ) . '</span>';
		echo '<span class="wsc-readiness-panel__status-value">';
		if ( $strict_ready ) {
			echo esc_html__( 'Ready to enable (multiple passwordless methods are active).', 'ventraconnect-social-login' );
		} else {
			echo esc_html__( 'Not ready yet – enable at least two passwordless methods.', 'ventraconnect-social-login' );
		}
		echo '</span>';
		echo '</div>';

		echo '</div>'; // .wsc-readiness-panel__status
		echo '</div>'; // .wsc-readiness-panel__header

		// Available methods section
		echo '<div class="wsc-readiness-panel__section">';
		echo '<h3 class="wsc-readiness-panel__section-title">' . esc_html__( 'Available methods:', 'ventraconnect-social-login' ) . '</h3>';
		echo '<ul class="wsc-status-list">';

		// Social Login
		if ( isset( $methods['social'], $methods['social_count'] ) ) {
			$social_count = (int) $methods['social_count'];
			$social_label = sprintf(
				/* translators: 1: number of active social providers. */
				esc_html__( 'Social Login', 'ventraconnect-social-login' )
			);
			$social_badge = sprintf(
				/* translators: 1: number of active social providers. */
				esc_html__( '%d active provider(s)', 'ventraconnect-social-login' ),
				$social_count
			);
			
			$item_class = $methods['social'] ? 'wsc-status-item wsc-status-item--active' : 'wsc-status-item wsc-status-item--disabled';
			$icon_class = $methods['social'] ? 'wsc-status-item__icon wsc-status-item__icon--success' : 'wsc-status-item__icon wsc-status-item__icon--error';
			$icon = $methods['social'] ? '✓' : '✕';
			$badge_text = $methods['social'] ? $social_badge : esc_html__( 'disabled', 'ventraconnect-social-login' );
			
			echo '<li class="' . esc_attr( $item_class ) . '">';
			echo '<span class="' . esc_attr( $icon_class ) . '">' . esc_html( $icon ) . '</span>';
			echo '<span class="wsc-status-item__text">' . esc_html( $social_label ) . '</span>';
			echo '<span class="wsc-status-item__badge">' . esc_html( $badge_text ) . '</span>';
			echo '</li>';
		}

		// Magic Link
		if ( isset( $methods['magic_link'] ) ) {
			$item_class = $methods['magic_link'] ? 'wsc-status-item wsc-status-item--active' : 'wsc-status-item wsc-status-item--disabled';
			$icon_class = $methods['magic_link'] ? 'wsc-status-item__icon wsc-status-item__icon--success' : 'wsc-status-item__icon wsc-status-item__icon--error';
			$icon = $methods['magic_link'] ? '✓' : '✕';
			$badge_text = $methods['magic_link'] ? esc_html__( 'enabled', 'ventraconnect-social-login' ) : esc_html__( 'disabled', 'ventraconnect-social-login' );
			
			echo '<li class="' . esc_attr( $item_class ) . '">';
			echo '<span class="' . esc_attr( $icon_class ) . '">' . esc_html( $icon ) . '</span>';
			echo '<span class="wsc-status-item__text">' . esc_html__( 'Magic Link', 'ventraconnect-social-login' ) . '</span>';
			echo '<span class="wsc-status-item__badge">' . esc_html( $badge_text ) . '</span>';
			echo '</li>';
		}

		// OTP Email
		if ( isset( $methods['otp'] ) ) {
			$item_class = $methods['otp'] ? 'wsc-status-item wsc-status-item--active' : 'wsc-status-item wsc-status-item--disabled';
			$icon_class = $methods['otp'] ? 'wsc-status-item__icon wsc-status-item__icon--success' : 'wsc-status-item__icon wsc-status-item__icon--error';
			$icon = $methods['otp'] ? '✓' : '✕';
			$badge_text = $methods['otp'] ? esc_html__( 'enabled', 'ventraconnect-social-login' ) : esc_html__( 'disabled', 'ventraconnect-social-login' );
			
			echo '<li class="' . esc_attr( $item_class ) . '">';
			echo '<span class="' . esc_attr( $icon_class ) . '">' . esc_html( $icon ) . '</span>';
			echo '<span class="wsc-status-item__text">' . esc_html__( 'OTP Email', 'ventraconnect-social-login' ) . '</span>';
			echo '<span class="wsc-status-item__badge">' . esc_html( $badge_text ) . '</span>';
			echo '</li>';
		}

		// Passkey
		if ( isset( $methods['passkey'] ) ) {
			$item_class = $methods['passkey'] ? 'wsc-status-item wsc-status-item--active' : 'wsc-status-item wsc-status-item--disabled';
			$icon_class = '';
			$icon = '';
			$icon_class = $methods['passkey'] ? 'wsc-status-item__icon wsc-status-item__icon--success' : 'wsc-status-item__icon wsc-status-item__icon--error';
			$icon = $methods['passkey'] ? 'âœ“' : 'âœ•';
			$icon_class = '';
			$icon = '';
			$badge_text = $methods['passkey'] ? esc_html__( 'enabled', 'ventraconnect-social-login' ) : esc_html__( 'disabled', 'ventraconnect-social-login' );

			echo '<li class="' . esc_attr( $item_class ) . '">';
			echo '<span class="' . esc_attr( $icon_class ) . '">' . esc_html( $icon ) . '</span>';
			echo '<span class="wsc-status-item__text">' . esc_html__( 'Passkey', 'ventraconnect-social-login' ) . '</span>';
			echo '<span class="wsc-status-item__badge">' . esc_html( $badge_text ) . '</span>';
			echo '</li>';
		}

		echo '</ul>';
		echo '</div>'; // .wsc-readiness-panel__section

		// Active contexts section
		echo '<div class="wsc-readiness-panel__section">';
		echo '<h3 class="wsc-readiness-panel__section-title">' . esc_html__( 'Passwordless login methods are active on:', 'ventraconnect-social-login' ) . '</h3>';
		echo '<ul class="wsc-status-list">';

		if ( ! empty( $contexts ) && is_array( $contexts ) ) {
			foreach ( $contexts as $slug => $status ) {
				$label     = isset( $status['label'] ) ? (string) $status['label'] : (string) $slug;
				$available = ! empty( $status['available'] );
				$enabled   = ! empty( $status['enabled'] );

				if ( ! $available ) {
					$item_class = 'wsc-status-item wsc-status-item--disabled';
					$icon_class = 'wsc-status-item__icon wsc-status-item__icon--error';
					$icon = '✕';
					$badge_text = esc_html__( 'not available', 'ventraconnect-social-login' );
				} elseif ( $enabled ) {
					$item_class = 'wsc-status-item wsc-status-item--active';
					$icon_class = 'wsc-status-item__icon wsc-status-item__icon--success';
					$icon = '✓';
					$badge_text = esc_html__( 'active', 'ventraconnect-social-login' );
				} else {
					$item_class = 'wsc-status-item wsc-status-item--disabled';
					$icon_class = 'wsc-status-item__icon wsc-status-item__icon--error';
					$icon = '⚠';
					$badge_text = esc_html__( 'available but not enabled', 'ventraconnect-social-login' );
				}

				echo '<li class="' . esc_attr( $item_class ) . '">';
				echo '<span class="' . esc_attr( $icon_class ) . '">' . esc_html( $icon ) . '</span>';
				echo '<span class="wsc-status-item__text">' . esc_html( $label ) . '</span>';
				echo '<span class="wsc-status-item__badge">' . esc_html( $badge_text ) . '</span>';
				echo '</li>';
			}
		}

		echo '</ul>';
		echo '</div>'; // .wsc-readiness-panel__section

		echo '</div>'; // .wsc-readiness-panel
	?>
</aside>
							</div>
  						</td>
  					</tr>
  				</table>
				
				
			</div>
		</div>
		<?php
		if ( $preview_only ) {
			$html = (string) ob_get_clean();
			echo wp_kses_post( $this->apply_preview_filters( $html ) );
		}
	}

	/**
	 * Render Provider basics: Getting Started + Button Style + Credentials.
	 * Markup-only: expects pre-assembled data, no conditional logic here.
	 *
	 * @param string   $slug       Provider slug (e.g., google, facebook)
	 * @param array    $provider   Meta like label, redirect, id/secret labels, and prebuilt getting_started_html
	 * @param array    $creds      Full provider creds array (will read $creds[$slug])
	 * @param array    $themes     Per-provider theme values (unused here but reserved)
	 * @param array    $overrides  Per-provider theme override flags (unused here but reserved)
	 * @param array    $texts      Per-provider button text (unused here but reserved)
	 * @param callable $isPro      Callable to check Pro (unused here but reserved)
	 */
	public function renderProviderConfigBasics( string $slug, array $provider, array $creds, array $themes, array $overrides, array $texts, callable $isPro ): void { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
		$preview_only = ! empty( $provider['preview_only'] );
		if ( $preview_only ) {
			ob_start();
		}
		$label       = (string) ( $provider['label'] ?? ucfirst( $slug ) );
		$id_label    = (string) ( $provider['id_label'] ?? __( 'Client ID', 'ventraconnect-social-login' ) );
		$sec_label   = (string) ( $provider['secret_label'] ?? __( 'Client Secret', 'ventraconnect-social-login' ) );
		$getting_html = (string) ( $provider['getting_started_html'] ?? '' );
		?>
		<div class="wsc-card">
			<h3><?php echo esc_html__( 'Getting Started', 'ventraconnect-social-login' ); ?></h3>
			<div class="wsc-help">
				<?php echo wp_kses_post( $getting_html ); ?>
			</div>
		</div>
		<?php include VENTRACONNECT_SL_PLUGIN_DIR . 'includes/admin/views/providers/partials/section-button-style.php'; ?>
		<div class="wsc-card" style="margin-top:12px;">
			<h3><?php echo esc_html__( 'Credentials', 'ventraconnect-social-login' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php echo esc_html( $label . ' ' . $id_label ); ?></th>
					<td><input type="text" class="regular-text" name="ventraconnect_sl_settings[provider_creds][<?php echo esc_attr( $slug ); ?>][client_id]" value="<?php echo esc_attr( $creds[ $slug ]['client_id'] ?? '' ); ?>"/></td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html( $label . ' ' . $sec_label ); ?></th>
					<td><input type="text" class="regular-text" name="ventraconnect_sl_settings[provider_creds][<?php echo esc_attr( $slug ); ?>][client_secret]" value="<?php echo esc_attr( $creds[ $slug ]['client_secret'] ?? '' ); ?>"/></td>
				</tr>
			</table>
			<p class="wsc-admin wsc-savebar" style="margin-top:8px;">
				<div class="wsc-savebar">
				<?php submit_button( __( 'Save Changes', 'ventraconnect-social-login' ), 'primary', 'submit', false ); ?>
	</div>
			</p>
		</div>
		<?php
		if ( $preview_only ) {
			$html = (string) ob_get_clean();
			echo wp_kses_post( $this->apply_preview_filters( $html ) );
		}
	}

	/**
	 * Render Emails & Notifications tab layout (preview-capable).
	 *
	 * @param array<string,mixed> $state
	 */
	public function renderEmailsTab( array $state ): void {
		$preview_only = ! empty( $state['preview_only'] );
		if ( $preview_only ) {
			ob_start();
		}

		$settings = (array) ( $state['settings'] ?? [] );

		$opt = is_array( $settings ) ? $settings : [];
		$is_disabled = ! empty( $state['ventraconnect_sl_disable'] );

		$send_admin_email_for_social      = array_key_exists( 'send_admin_email_for_social', $opt ) ? (bool) $opt['send_admin_email_for_social'] : true;
		$use_wp_new_user_email_for_social = ! empty( $opt['use_wp_new_user_email_for_social'] );

		$send_welcome_email_on_link = ! empty( $opt['send_welcome_email_on_link'] );
		$linked_email_subject       = isset( $opt['linked_email_subject'] ) ? (string) $opt['linked_email_subject'] : __( 'Linked account on {site_name}', 'ventraconnect-social-login' );
		$linked_email_body          = isset( $opt['linked_email_body'] ) ? (string) $opt['linked_email_body'] : (
			'You just connected {provider_name} to your account on {site_name}. If this wasn\'t you, contact us at {admin_email}'
		);

		$handle_missing_email_action = isset( $opt['handle_missing_email_action'] ) ? (string) $opt['handle_missing_email_action'] : '';
		$missing_email_title         = isset( $opt['missing_email_title'] ) ? (string) $opt['missing_email_title'] : __( 'Finish your signup', 'ventraconnect-social-login' );
		$missing_email_message       = isset( $opt['missing_email_message'] ) ? (string) $opt['missing_email_message'] : __( 'We couldn’t get your email from {provider_name}. Please enter your email to complete your login.', 'ventraconnect-social-login' );
		?>

		<div class="wsc-card">
    <div class="wsc-card__header">
        <h3 class="wsc-card__title"><?php echo esc_html__( 'Core Notifications', 'ventraconnect-social-login' ); ?></h3>
        <p class="wsc-card__description"><?php echo esc_html__( 'Choose which core emails to send when a user registers via social login.', 'ventraconnect-social-login' ); ?></p>
    </div>

    <div class="wsc-card__body">
        <div class="wsc-card wsc-card--row" style="margin-bottom: 16px;">
            <div class="wsc-card__content">
                <div class="wsc-card__title"><?php echo esc_html__( 'Send admin notification for new social users', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-card__description"><?php echo esc_html__( 'Notify site administrators when a new user registers via social login.', 'ventraconnect-social-login' ); ?></p>
            </div>
            <div class="wsc-card__control">
                <input type="hidden" name="ventraconnect_sl_emails_settings[send_admin_email_for_social]" value="0"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                <label class="wsc-switch">
                    <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_emails_settings[send_admin_email_for_social]" value="1" <?php checked( $send_admin_email_for_social ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label"><?php echo esc_html__( 'Enable admin notifications', 'ventraconnect-social-login' ); ?></span>
                </label>
            </div>
        </div>

        <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
                <div class="wsc-card__title"><?php echo esc_html__( 'Use default WordPress "New User" email', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-card__description"><?php echo esc_html__( 'Send the standard WordPress new user email to customers who register via social login.', 'ventraconnect-social-login' ); ?></p>
            </div>
            <div class="wsc-card__control">
                <input type="hidden" name="ventraconnect_sl_emails_settings[use_wp_new_user_email_for_social]" value="0"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                <label class="wsc-switch">
                    <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_emails_settings[use_wp_new_user_email_for_social]" value="1" <?php checked( $use_wp_new_user_email_for_social ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label"><?php echo esc_html__( 'Enable WordPress email', 'ventraconnect-social-login' ); ?></span>
                </label>
            </div>
        </div>
    </div>
</div>

		<div class="wsc-card">
    <div class="wsc-card__header">
        <h3 class="wsc-card__title">
            <?php echo esc_html__( 'Linked Account Notifications', 'ventraconnect-social-login' ); ?>
            
        </h3>
        <p class="wsc-card__description"><?php echo esc_html__( 'Notify users and customize the email when a new provider is linked to their account.', 'ventraconnect-social-login' ); ?></p>
    </div>

    <div class="wsc-card__body">
        <!-- Toggle Setting -->
        <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
                <div class="wsc-card__title"><?php echo esc_html__( 'Send email when user links a new social account', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-card__description"><?php echo esc_html__( 'Automatically notify users via email when they connect a new social provider to their account.', 'ventraconnect-social-login' ); ?></p>
            </div>
            <div class="wsc-card__control">
                <input type="hidden" name="ventraconnect_sl_emails_settings[send_welcome_email_on_link]" value="0"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                <label class="wsc-switch">
                    <input type="checkbox" class="wsc-switch-input" id="vcsl_linked_email_toggle" name="ventraconnect_sl_emails_settings[send_welcome_email_on_link]" value="1" <?php checked( $send_welcome_email_on_link ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label"><?php echo esc_html__( 'Enable notifications', 'ventraconnect-social-login' ); ?></span>
                </label>
            </div>
        </div>

        <div class="vcsl-linked-email-fields">
            <!-- Email Subject -->
            <div class="wsc-field wsc-field--vertical">
                <label class="wsc-field__label"><?php echo esc_html__( 'Subject', 'ventraconnect-social-login' ); ?></label>
                <div class="wsc-field__control">
                    <input type="text" class="wsc-input" id="vcsl_linked_email_subject" name="ventraconnect_sl_emails_settings[linked_email_subject]" placeholder="<?php echo esc_attr__( 'Linked account on {site_name}', 'ventraconnect-social-login' ); ?>" value="<?php echo esc_attr( $linked_email_subject ); ?>"<?php disabled( ! $send_welcome_email_on_link ); echo $is_disabled ? ' disabled="disabled"' : ''; ?> />
                </div>
            </div>

            <!-- Email Body -->
            <div class="wsc-field wsc-field--vertical">
                <label class="wsc-field__label"><?php echo esc_html__( 'Body', 'ventraconnect-social-login' ); ?></label>
                <div class="wsc-field__control">
                    <textarea class="wsc-textarea" id="vcsl_linked_email_body"  name="ventraconnect_sl_emails_settings[linked_email_body]" placeholder="<?php echo esc_attr__( 'You just connected {provider_name} to your account on {site_name}. If this wasn\'t you, contact us at {admin_email}', 'ventraconnect-social-login' ); ?>" rows="6"<?php disabled( ! $send_welcome_email_on_link ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>><?php echo esc_textarea( $linked_email_body ); ?></textarea>
                    <span class="wsc-input-hint"><?php echo esc_html__( 'Available tags: {site_name}, {user_login}, {user_email}, {display_name}, {provider_name}, {admin_email}', 'ventraconnect-social-login' ); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="wsc-card">
    <div class="wsc-card__header">
        <h3 class="wsc-card__title">
            <?php echo esc_html__( 'Handling Missing Emails', 'ventraconnect-social-login' ); ?>
            
        </h3>
        <p class="wsc-card__description"><?php echo esc_html__( 'Control what happens when a provider does not return an email address.', 'ventraconnect-social-login' ); ?></p>
    </div>

    <div class="wsc-card__body">
        <!-- Toggle Setting -->
        <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
                <div class="wsc-card__title"><?php echo esc_html__( 'Ask user (recommended)', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-card__description"><?php echo esc_html__( 'Prompt users to provide their email address when the social provider does not return one.', 'ventraconnect-social-login' ); ?></p>
            </div>
            <div class="wsc-card__control">
                <input type="hidden" name="ventraconnect_sl_emails_settings[handle_missing_email_action]" value=""<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                <label class="wsc-switch">
                    <input type="checkbox" class="wsc-switch-input" id="vcsl_missing_email_toggle" name="ventraconnect_sl_emails_settings[handle_missing_email_action]" value="ask_user" <?php checked( 'ask_user', $handle_missing_email_action ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label"><?php echo esc_html__( 'Enable email prompt', 'ventraconnect-social-login' ); ?></span>
                </label>
            </div>
        </div>

        <div class="vcsl-missing-email-fields">
            <!-- Finish Signup Title -->
            <div class="wsc-field wsc-field--vertical">
                <label class="wsc-field__label"><?php echo esc_html__( 'Finish Signup Title', 'ventraconnect-social-login' ); ?></label>
                <div class="wsc-field__control">
                    <input type="text" class="wsc-input" id="vcsl_missing_email_title" name="ventraconnect_sl_emails_settings[missing_email_title]" placeholder="<?php echo esc_attr__( 'Finish your signup', 'ventraconnect-social-login' ); ?>" value="<?php echo esc_attr( $missing_email_title ); ?>"<?php disabled( 'ask_user' !== $handle_missing_email_action ); echo $is_disabled ? ' disabled="disabled"' : ''; ?> />
                </div>
            </div>

            <!-- Finish Signup Message -->
            <div class="wsc-field wsc-field--vertical">
                <label class="wsc-field__label"><?php echo esc_html__( 'Finish Signup Message', 'ventraconnect-social-login' ); ?></label>
                <div class="wsc-field__control">
                    <textarea class="wsc-textarea" id="vcsl_missing_email_message" rows="4" placeholder="<?php echo esc_attr__( 'We couldn’t get your email from {provider_name}. Please enter your email to complete your login.', 'ventraconnect-social-login' ); ?>" name="ventraconnect_sl_emails_settings[missing_email_message]"<?php disabled( 'ask_user' !== $handle_missing_email_action ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>><?php echo esc_textarea( $missing_email_message ); ?></textarea>
                    <span class="wsc-input-hint"><?php echo esc_html__( 'You can use {provider_name} in the message.', 'ventraconnect-social-login' ); ?></span>
                </div>
            </div>
        </div>

        <!-- Important Note -->
        <div class="wsc-notice wsc-notice--info">
            <div class="wsc-notice__icon">ℹ️</div>
            <div class="wsc-notice__content">
                <p class="wsc-notice__text">
                    <?php
                    echo wp_kses_post(
                        __( 'Note: If you\'re using <strong>"Ask user for email"</strong>, please re-save your permalinks under <strong>Settings → Permalinks</strong> to register the new <code>/social-login/complete-email/</code> route.', 'ventraconnect-social-login' )
                    );
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>
		<?php
		if ( $preview_only ) {
			$html = (string) ob_get_clean();
			echo wp_kses_post( $this->apply_preview_filters( $html ) );
		}
	}

/**
 * Render WooCommerce Integrations tab layout (preview-capable).
 *
 * @param array<string,mixed> $state
 */
  public function renderWooCommerceTab( array $state ): void {
      $preview_only = ! empty( $state['preview_only'] );
      if ( $preview_only ) {
          ob_start();
      }
  
      $settings = (array) ( $state['settings'] ?? array() );
      $opt      = is_array( $settings ) ? $settings : array();
  
      $enabled    = ! empty( $opt['enabled'] );
      $placements = isset( $opt['placements'] ) && is_array( $opt['placements'] ) ? $opt['placements'] : array();
      $redirect   = isset( $opt['redirect'] ) && is_array( $opt['redirect'] ) ? $opt['redirect'] : array();
      $linking    = isset( $opt['linking'] ) && is_array( $opt['linking'] ) ? $opt['linking'] : array();
      $opts_extra = isset( $opt['options'] ) && is_array( $opt['options'] ) ? $opt['options'] : array();
      $passkeys   = isset( $opt['passkeys'] ) && is_array( $opt['passkeys'] ) ? $opt['passkeys'] : array();
  
        $vcsl_woo_uses_checkout_block = false;
        if ( function_exists( 'wc_get_page_id' ) ) {
            $checkout_page_id = wc_get_page_id( 'checkout' );
            if ( $checkout_page_id && $checkout_page_id > 0 ) {
                $content = get_post_field( 'post_content', $checkout_page_id );
                if ( is_string( $content ) ) {
                    $uses_block     = ( false !== strpos( $content, '<!-- wp:woocommerce/checkout' ) );
                    $uses_shortcode = ( false !== strpos( $content, '[woocommerce_checkout' ) );
                    if ( $uses_block && ! $uses_shortcode ) {
                        $vcsl_woo_uses_checkout_block = true;
                    }
                }
            }
        }

        $wc_plugin_active = class_exists( 'WooCommerce' ) || function_exists( 'WC' );
        ?>
    
        <!-- Enable WooCommerce -->
	 <div class="section-header"><h1>WooCommerce Integration</h1>
  	<p>Configure social login for WooCommerce pages including checkout, login, and account areas.</p>
  	</div>
          <?php if ( ! $wc_plugin_active ) : ?>
              <div class="wsc-notice wsc-notice--info">
                  <div class="wsc-notice__icon">ℹ️</div>
                  <div class="wsc-notice__content">
                      <p class="wsc-notice__text">
                          <?php echo esc_html__( 'WooCommerce is not installed or active. These settings are disabled.', 'ventraconnect-social-login' ); ?>
                      </p>
                  </div>
              </div>
          <?php endif; ?>
  	 <div class="wsc-card">
      <?php if ( ! $wc_plugin_active ) : ?>
      <fieldset disabled="disabled">
      <?php endif; ?>
    <div class="wsc-card wsc-card--row">
        <div class="wsc-card__content">
            <div class="wsc-card__title">
                <?php echo esc_html__( 'Enable WooCommerce Social Login', 'ventraconnect-social-login' ); ?>
            </div>
            <p class="wsc-card__description">
                <?php
                echo esc_html__(
                    'Allow customers to log in or register using their social accounts on WooCommerce pages (checkout, cart, and My Account).',
                    'ventraconnect-social-login'
                );
                ?>
            </p>
        </div>
        <div class="wsc-card__control">
            <label class="wsc-switch">
                <input type="hidden" name="ventraconnect_sl_wc_settings[enabled]" value="0">
                <input type="checkbox"
                       class="wsc-switch-input"
                       name="ventraconnect_sl_wc_settings[enabled]"
                       value="1" <?php checked( $enabled ); ?>>
                <span class="wsc-switch-ui" aria-hidden="true"></span>
                <span class="wsc-switch__label">
                    <?php echo esc_html__( 'Enable WooCommerce Social Login', 'ventraconnect-social-login' ); ?>
                </span>
            </label>
      </div>
  		</div>

    <?php if ( $vcsl_woo_uses_checkout_block ) : ?>
        <div class="notice notice-warning inline" style="margin-top:10px;margin-bottom:10px;">
            <p>
                <?php
                echo esc_html__(
                    'Heads up: WooCommerce Checkout Block detected. Social login at checkout currently works only with the classic checkout shortcode ([woocommerce_checkout]). To show social login on checkout, switch your checkout page to use the classic shortcode, or keep using account/social login on the My Account and login forms. Checkout Block support is planned for a future update.',
                    'ventraconnect-social-login'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- New account emails -->
    <div class="wsc-card wsc-card--row">
        <div class="wsc-card__content">
            <div class="wsc-card__title">
                <?php echo esc_html__( 'New account emails for social signups', 'ventraconnect-social-login' ); ?>
            </div>
            <?php
            $email_mode = isset( $opt['email_mode'] ) ? (string) $opt['email_mode'] : 'woo_default';
            $desc       = __(
                'When enabled, new accounts created via social login on WooCommerce pages will receive the standard WooCommerce "Customer new account" email.',
                'ventraconnect-social-login'
            );
            ?>
            <p class="wsc-card__description"><?php echo esc_html( $desc ); ?></p>
        </div>
        <div class="wsc-card__control">
            <label class="wsc-switch">
                <input type="hidden" name="ventraconnect_sl_wc_settings[email_mode]" value="none">
                <input type="checkbox"
                       class="wsc-switch-input"
                       name="ventraconnect_sl_wc_settings[email_mode]"
                       value="woo_default" <?php checked( $email_mode, 'woo_default' ); ?>>
                <span class="wsc-switch-ui" aria-hidden="true"></span>
                <span class="wsc-switch__label">
                    <?php echo esc_html__( 'Send WooCommerce "Customer new account" email for social signups', 'ventraconnect-social-login' ); ?>
                </span>
            </label>
        </div>
    </div>
		</div>
    <!-- Login & registration placement -->
    <div class="wsc-card">
        <h3 class="wsc-card__title">
            <?php echo esc_html__( 'Login & Registration placement', 'ventraconnect-social-login' ); ?>
        </h3>
        <p class="wsc-card__description" style="margin-bottom:16px;">
            <?php echo esc_html__( 'Choose where to display social login on WooCommerce login and registration screens.', 'ventraconnect-social-login' ); ?>
        </p>

        <h4><?php echo esc_html__( 'Login form', 'ventraconnect-social-login' ); ?></h4>
        <?php
        $login_val  = $placements['login_form'] ?? 'none';
        $login_opts = array(
            'none'                         => array(
                'label' => __( 'None', 'ventraconnect-social-login' ),
                'hint'  => __( 'Do not show social login on the WooCommerce login page.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_login_form_start' => array(
                'label' => __( 'Above the login form', 'ventraconnect-social-login' ),
                'hint'  => __( 'Renders before the WooCommerce login form fields.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_login_form_end'   => array(
                'label' => __( 'Below the login form', 'ventraconnect-social-login' ),
                'hint'  => __( 'Renders after the WooCommerce login form fields.', 'ventraconnect-social-login' ),
            ),
        );
        ?>
        <div class="wsc-radio-group">
            <?php foreach ( $login_opts as $k => $meta ) : ?>
                <label class="wsc-radio-row">
                    <input type="radio"
                           name="ventraconnect_sl_wc_settings[placements][login_form]"
                           value="<?php echo esc_attr( $k ); ?>" <?php checked( $login_val, $k ); ?>>
                    <div class="wsc-radio-content">
                        <div class="wsc-radio-title"><?php echo esc_html( $meta['label'] ); ?></div>
                        <p class="wsc-radio-hint"><?php echo esc_html( $meta['hint'] ); ?></p>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <h4 style="margin-top:16px;"><?php echo esc_html__( 'Register form', 'ventraconnect-social-login' ); ?></h4>
        <?php
        $reg_val  = $placements['register_form'] ?? 'none';
        $reg_opts = array(
            'none'                            => array(
                'label' => __( 'None', 'ventraconnect-social-login' ),
                'hint'  => __( 'Do not show social login on the WooCommerce register form.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_register_form_start' => array(
                'label' => __( 'Above the register form', 'ventraconnect-social-login' ),
                'hint'  => __( 'Renders before the WooCommerce register form fields.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_register_form_end'   => array(
                'label' => __( 'Below the register form', 'ventraconnect-social-login' ),
                'hint'  => __( 'Renders after the WooCommerce register form fields.', 'ventraconnect-social-login' ),
            ),
        );
        ?>
        <div class="wsc-radio-group">
            <?php foreach ( $reg_opts as $k => $meta ) : ?>
                <label class="wsc-radio-row">
                    <input type="radio"
                           name="ventraconnect_sl_wc_settings[placements][register_form]"
                           value="<?php echo esc_attr( $k ); ?>" <?php checked( $reg_val, $k ); ?>>
                    <div class="wsc-radio-content">
                        <div class="wsc-radio-title"><?php echo esc_html( $meta['label'] ); ?></div>
                        <p class="wsc-radio-hint"><?php echo esc_html( $meta['hint'] ); ?></p>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Checkout & Account placement -->
    <div class="wsc-card">
        <h3 class="wsc-card__title">
            <?php echo esc_html__( 'Checkout & Account placement', 'ventraconnect-social-login' ); ?>
        </h3>
        <?php
        $checkout_desc = __( 'Choose where social login appears on the checkout page.', 'ventraconnect-social-login' );
        if ( $vcsl_woo_uses_checkout_block ) {
            $checkout_desc .= ' ' . __( 'Available for classic checkout shortcode [woocommerce_checkout] only (the WooCommerce Checkout Block is not yet supported).', 'ventraconnect-social-login' );
        }
        ?>

        <h4><?php echo esc_html__( 'Checkout form', 'ventraconnect-social-login' ); ?></h4>
        <p class="wsc-card__description" style="margin-bottom:16px;"><?php echo esc_html( $checkout_desc ); ?></p>
        <?php
        $checkout_val  = $placements['checkout_form'] ?? 'none';
        $checkout_opts = array(
            'none'                                   => array(
                'label' => __( 'None', 'ventraconnect-social-login' ),
                'hint'  => __( 'Do not show social login on the checkout page.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_before_checkout_form'       => array(
                'label' => __( 'Above the checkout form (very top)', 'ventraconnect-social-login' ),
                'hint'  => __( 'Renders above the entire checkout form.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_checkout_before_customer_details' => array(
                'label' => __( 'Above customer details (inside the form)', 'ventraconnect-social-login' ),
                'hint'  => __( 'Renders inside the checkout form, before customer details.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_checkout_after_customer_details'  => array(
                'label' => __( 'Between customer details and order summary', 'ventraconnect-social-login' ),
                'hint'  => __( 'Renders between customer details and the order summary section.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_review_order_before_payment'      => array(
                'label' => __( 'Above payment methods', 'ventraconnect-social-login' ),
                'hint'  => __( 'Renders above the payment methods section.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_review_order_before_submit'       => array(
                'label' => __( 'Above the “Place order” button', 'ventraconnect-social-login' ),
                'hint'  => __( 'Renders just before the Place order button.', 'ventraconnect-social-login' ),
            ),
        );
        ?>
        <div class="wsc-radio-group">
            <?php foreach ( $checkout_opts as $k => $meta ) : ?>
                <label class="wsc-radio-row">
                    <input type="radio"
                           name="ventraconnect_sl_wc_settings[placements][checkout_form]"
                           value="<?php echo esc_attr( $k ); ?>" <?php checked( $checkout_val, $k ); ?>>
                    <div class="wsc-radio-content">
                        <div class="wsc-radio-title"><?php echo esc_html( $meta['label'] ); ?></div>
                        <p class="wsc-radio-hint"><?php echo esc_html( $meta['hint'] ); ?></p>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>

        <h4 style="margin-top:16px;"><?php echo esc_html__( 'Account details', 'ventraconnect-social-login' ); ?></h4>
        <?php
        $account_val  = $placements['account_details'] ?? 'none';
        $account_opts = array(
            'none'                                => array(
                'label' => __( 'None', 'ventraconnect-social-login' ),
                'hint'  => __( 'Do not show social login on the Edit Account form.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_edit_account_form_start' => array(
                'label' => __( 'Above the account form', 'ventraconnect-social-login' ),
                'hint'  => __( 'Top of the Edit Account form.', 'ventraconnect-social-login' ),
            ),
            'woocommerce_edit_account_form_end'   => array(
                'label' => __( 'Below the account form', 'ventraconnect-social-login' ),
                'hint'  => __( 'Bottom of the Edit Account form.', 'ventraconnect-social-login' ),
            ),
        );
        ?>
        <div class="wsc-radio-group">
            <?php foreach ( $account_opts as $k => $meta ) : ?>
                <label class="wsc-radio-row">
                    <input type="radio"
                           name="ventraconnect_sl_wc_settings[placements][account_details]"
                           value="<?php echo esc_attr( $k ); ?>" <?php checked( $account_val, $k ); ?>>
                    <div class="wsc-radio-content">
                        <div class="wsc-radio-title"><?php echo esc_html( $meta['label'] ); ?></div>
                        <p class="wsc-radio-hint"><?php echo esc_html( $meta['hint'] ); ?></p>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Passkey Thank You prompt -->
    <div class="wsc-card">
        <h3 class="wsc-card__title">
            <?php echo esc_html__( 'Passkey Thank You prompt', 'ventraconnect-social-login' ); ?>
        </h3>
        <p class="wsc-card__description" style="margin-bottom:16px;">
            <?php echo esc_html__( 'Show a post-purchase passkey setup prompt on the WooCommerce Thank You page for logged-in customers.', 'ventraconnect-social-login' ); ?>
        </p>

        <div class="wsc-card wsc-card--row" style="margin-top:16px;">
            <div class="wsc-card__content">
                <div class="wsc-card__title">
                    <?php echo esc_html__( 'Show passkey setup prompt on the Thank You page', 'ventraconnect-social-login' ); ?>
                </div>
                <p class="wsc-card__description">
                    <?php echo esc_html__( 'Show a “Secure your account with a passkey” prompt after checkout for logged-in customers who do not have a passkey.', 'ventraconnect-social-login' ); ?>
                </p>
            </div>
            <div class="wsc-card__control">
                <label class="wsc-switch">
                    <input type="hidden" name="ventraconnect_sl_wc_settings[passkeys][thankyou_prompt_enabled]" value="0">
                    <input type="checkbox"
                           class="wsc-switch-input"
                           name="ventraconnect_sl_wc_settings[passkeys][thankyou_prompt_enabled]"
                           value="1" <?php checked( ! isset( $passkeys['thankyou_prompt_enabled'] ) || ! empty( $passkeys['thankyou_prompt_enabled'] ) ); ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label">
                        <?php echo esc_html__( 'Show passkey setup prompt on the Thank You page', 'ventraconnect-social-login' ); ?>
                    </span>
                </label>
            </div>
        </div>

        <div class="wsc-card wsc-card--row" style="margin-top:16px;">
            <div class="wsc-card__content">
                <div class="wsc-card__title">
                    <?php echo esc_html__( 'Also show on Thank You page for users who already have a passkey', 'ventraconnect-social-login' ); ?>
                </div>
                <p class="wsc-card__description">
                    <?php echo esc_html__( 'Usually this should stay off to avoid prompting customers who have already secured their account.', 'ventraconnect-social-login' ); ?>
                </p>
            </div>
            <div class="wsc-card__control">
                <label class="wsc-switch">
                    <input type="hidden" name="ventraconnect_sl_wc_settings[passkeys][thankyou_prompt_has_passkey_enabled]" value="0">
                    <input type="checkbox"
                           class="wsc-switch-input"
                           name="ventraconnect_sl_wc_settings[passkeys][thankyou_prompt_has_passkey_enabled]"
                           value="1" <?php checked( ! empty( $passkeys['thankyou_prompt_has_passkey_enabled'] ) ); ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label">
                        <?php echo esc_html__( 'Also show for customers who already have a passkey', 'ventraconnect-social-login' ); ?>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <!-- Display & helper text -->
    <div class="wsc-card">
        <h3 class="wsc-card__title">
            <?php echo esc_html__( 'Display & helper text', 'ventraconnect-social-login' ); ?>
        </h3>

        <div class="wsc-card wsc-card--row" style="margin-top:16px; margin-bottom:16px;">
            <div class="wsc-card__content">
                <div class="wsc-card__title">
                    <?php echo esc_html__( 'Hide social login buttons for logged-in customers', 'ventraconnect-social-login' ); ?>
                </div>
                <p class="wsc-card__description">
                    <?php echo esc_html__( 'When enabled, social login buttons will not be displayed if the user is already logged in.', 'ventraconnect-social-login' ); ?>
                </p>
            </div>
            <div class="wsc-card__control">
                <label class="wsc-switch">
                    <input type="hidden" name="ventraconnect_sl_wc_settings[options][hide_when_logged_in]" value="0">
                    <input type="checkbox"
                           class="wsc-switch-input"
                           name="ventraconnect_sl_wc_settings[options][hide_when_logged_in]"
                           value="1" <?php checked( ! empty( $opts_extra['hide_when_logged_in'] ) ); ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label">
                        <?php echo esc_html__( 'Hide social login when logged in', 'ventraconnect-social-login' ); ?>
                    </span>
                </label>
            </div>
        </div>

        <div>
            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:13px;">
                <?php echo esc_html__( 'Helper message', 'ventraconnect-social-login' ); ?>
            </label>
            <input type="text"
                   class="wsc-input"
                   name="ventraconnect_sl_wc_settings[options][helper_message]"
                   value="<?php echo esc_attr( $opts_extra['helper_message'] ?? __( 'Login with your favorite platform to checkout.', 'ventraconnect-social-login' ) ); ?>">
            <span class="wsc-input-hint">
                <?php echo esc_html__( 'This message appears above social login buttons to guide customers.', 'ventraconnect-social-login' ); ?>
            </span>
        </div>
    </div>

    <!-- Redirect after login -->
    <div class="wsc-card">
        <h3 class="wsc-card__title">
            <?php echo esc_html__( 'Redirect after login', 'ventraconnect-social-login' ); ?>
        </h3>

        <?php
        $redirect_mode  = $redirect['mode'] ?? 'same_page';
        $redirect_modes = array(
            'same_page' => __( 'Stay on the same page', 'ventraconnect-social-login' ),
            'my_account'=> __( 'My Account page', 'ventraconnect-social-login' ),
            'checkout'  => __( 'Checkout page', 'ventraconnect-social-login' ),
            'cart'      => __( 'Cart page', 'ventraconnect-social-login' ),
            'shop'      => __( 'Shop page', 'ventraconnect-social-login' ),
            'custom'    => __( 'Custom URL', 'ventraconnect-social-login' ),
        );
        ?>

        <div style="margin-bottom:16px;">
            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:13px;">
                <?php echo esc_html__( 'Redirect customer after login', 'ventraconnect-social-login' ); ?>
            </label>
            <select
                name="ventraconnect_sl_wc_settings[redirect][mode]"
                class="wsc-select"
            >
                <?php foreach ( $redirect_modes as $mode => $label ) : ?>
                    <option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $redirect_mode, $mode ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="display:block; margin-bottom:8px; font-weight:600; font-size:13px;">
                <?php echo esc_html__( 'Custom URL', 'ventraconnect-social-login' ); ?>
            </label>
            <input type="url"
                   class="wsc-input"
                   name="ventraconnect_sl_wc_settings[redirect][custom_url]"
                   value="<?php echo esc_attr( $redirect['custom_url'] ?? '' ); ?>">
            <span class="wsc-input-hint">
                <?php echo esc_html__( 'Select where customers are redirected after successful login.', 'ventraconnect-social-login' ); ?>
            </span>
        </div>
    </div>

    <!-- Account linking rules -->
    <div class="wsc-card">
        <h3 class="wsc-card__title">
            <?php echo esc_html__( 'Account linking rules', 'ventraconnect-social-login' ); ?>
        </h3>
        <?php
        $linking_toggles = array(
            array(
                'key'   => 'allow_new_account',
                'title' => __( 'Allow new account creation from WooCommerce login page', 'ventraconnect-social-login' ),
                'help'  => __( 'When disabled, VentraConnect methods on the WooCommerce login page can only sign in or link existing customers. New customers can still create an account through registration or checkout, then use VentraConnect methods to log in.', 'ventraconnect-social-login' ),
            ),
            array(
                'key'   => 'prevent_unlink',
                'title' => __( 'Prevent unlinking', 'ventraconnect-social-login' ),
                'help'  => __( 'Disallow customers from disconnecting their linked social login if it is tied to an active WooCommerce account.', 'ventraconnect-social-login' ),
            ),
        );
        foreach ( $linking_toggles as $toggle ) :
            $key        = $toggle['key'];
            $title      = $toggle['title'];
            $help       = $toggle['help'];
            $val_toggle = ! empty( $linking[ $key ] );
            ?>
            <div class="wsc-card wsc-card--row" style="margin-top:16px;">
                <div class="wsc-card__content">
                    <div class="wsc-card__title"><?php echo esc_html( $title ); ?></div>
                    <p class="wsc-card__description"><?php echo esc_html( $help ); ?></p>
                </div>
                <div class="wsc-card__control">
                    <label class="wsc-switch">
                        <input type="hidden" name="ventraconnect_sl_wc_settings[linking][<?php echo esc_attr( $key ); ?>]" value="0">
                        <input type="checkbox"
                               class="wsc-switch-input"
                               name="ventraconnect_sl_wc_settings[linking][<?php echo esc_attr( $key ); ?>]"
                               value="1" <?php checked( $val_toggle ); ?>>
                        <span class="wsc-switch-ui" aria-hidden="true"></span>
                        <span class="wsc-switch__label">
                            <?php echo esc_html( $title ); ?>
                        </span>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

      <?php if ( ! $wc_plugin_active ) : ?>
      </fieldset>
      <?php endif; ?>
  
      <?php
      if ( $preview_only ) {
          $html = (string) ob_get_clean();
          echo wp_kses_post( $this->apply_preview_filters( $html ) );
    }
}


	/**
	 * Render Comments tab layout (preview-capable).
	 *
	 * @param array<string,mixed> $state
	 */
	public function renderCommentsTab( array $state ): void {
		$preview_only = ! empty( $state['preview_only'] );
		if ( $preview_only ) {
			ob_start();
		}

		$settings = (array) ( $state['settings'] ?? [] );
		$opt      = is_array( $settings ) ? $settings : [];

  		$is_disabled             = ! empty( $state['ventraconnect_sl_disable'] );
  		$enabled                 = ! empty( $opt['enabled'] );
  		$locations               = isset( $opt['locations'] ) && is_array( $opt['locations'] ) ? $opt['locations'] : [];
  		$style                   = isset( $opt['style'] ) && is_array( $opt['style'] ) ? $opt['style'] : [];
  		$access                  = isset( $opt['access'] ) && is_array( $opt['access'] ) ? $opt['access'] : [];
  		$redirect                = isset( $opt['redirect'] ) && is_array( $opt['redirect'] ) ? $opt['redirect'] : [];
  		$profile                 = isset( $opt['profile'] ) && is_array( $opt['profile'] ) ? $opt['profile'] : [];
  		$message                 = isset( $opt['message'] ) ? (string) $opt['message'] : '';
  		$auto_approve_verified   = ! empty( $opt['auto_approve_verified'] );
		?>
<div class="wsc-card">
    <div class="wsc-card__header">
        <h3 class="wsc-card__title"><?php echo esc_html__( 'Enable Social Login for Comments', 'ventraconnect-social-login' ); ?></h3>
        <p class="wsc-card__description"><?php echo esc_html__( 'Allow visitors to log in with their social accounts before posting a comment.', 'ventraconnect-social-login' ); ?></p>
    </div>

    <div class="wsc-card__body">
        <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
                <div class="wsc-card__title"><?php echo esc_html__( 'Social login on comment forms', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-card__description"><?php echo esc_html__( 'Display social login buttons above the comment form to let visitors authenticate before commenting.', 'ventraconnect-social-login' ); ?></p>
            </div>
            <div class="wsc-card__control">
                <input type="hidden" name="ventraconnect_sl_comments_settings[enabled]" value="0"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                <label class="wsc-switch">
                    <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_comments_settings[enabled]" value="1" <?php checked( $enabled ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label"><?php echo esc_html__( 'Enable Social Login for Comments', 'ventraconnect-social-login' ); ?></span>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="wsc-card">
    <div class="wsc-card__header">
        <h3 class="wsc-card__title"><?php echo esc_html__( 'Show social login buttons on', 'ventraconnect-social-login' ); ?></h3>
        <p class="wsc-card__description"><?php echo esc_html__( 'Choose where to display the social login buttons in comment forms.', 'ventraconnect-social-login' ); ?></p>
    </div>

    <div class="wsc-card__body">
        <div class="wsc-checkbox-group">
            <?php
            $loc_opts = [
                'above_form' => __( 'Above the comment form', 'ventraconnect-social-login' ),
                'below_form' => __( 'Below the comment form', 'ventraconnect-social-login' ),
            ];
            foreach ( $loc_opts as $key => $label ) :
                $checked = in_array( $key, $locations, true );
                ?>
                <label class="wsc-checkbox-row">
                    <input type="checkbox" name="ventraconnect_sl_comments_settings[locations][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                    <div class="wsc-checkbox-content">
                        <div class="wsc-checkbox-title"><?php echo esc_html( $label ); ?></div>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>

		<div class="wsc-card" id="vcs-comments-helper-message-card" style="<?php echo $enabled ? 'display:block;' : 'display:none;'; ?>">
			<h3><?php echo esc_html__( 'Helper Message', 'ventraconnect-social-login' ); ?></h3>
			<p class="description wsc-small"><?php echo esc_html__( 'Optional text displayed above the social login buttons on the comment form when users are not logged in.', 'ventraconnect-social-login' ); ?></p>
			<?php
			if ( function_exists( 'wp_editor' ) ) {
				wp_editor(
					$message,
					'ventraconnect_sl_comments_settings_message',
					[
						'textarea_name' => 'ventraconnect_sl_comments_settings[message]',
						'textarea_rows' => 5,
						'media_buttons' => false,
						'teeny'         => false,
						'tinymce'       => [
							'toolbar1'        => 'formatselect | bold italic underline | forecolor | fontsizeselect | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
							'toolbar2'        => '',
							'block_formats'   => 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4',
							'fontsize_formats'=> '12px 14px 16px 18px 24px 36px',
							'height'          => 120,
						],
						'quicktags'     => true,
						'disabled'      => $is_disabled,
					]
				);
			} else {
				echo '<textarea class="large-text" name="ventraconnect_sl_comments_settings[message]" rows="3"' . ( $is_disabled ? ' disabled="disabled"' : '' ) . '>' . esc_textarea( $message ) . '</textarea>';
			}
			?>
		</div>

<div class="wsc-card">
    <div class="wsc-card__header">
        <h3 class="wsc-card__title"><?php echo esc_html__( 'Button Style', 'ventraconnect-social-login' ); ?></h3>
        <p class="wsc-card__description"><?php echo esc_html__( 'Control how social buttons appear on comments form.', 'ventraconnect-social-login' ); ?></p>
    </div>

    <div class="wsc-card__body">
        <div class="wsc-card--row">
            <div class="wsc-card__content">
                <div class="wsc-card__title"><?php echo esc_html__( 'Use compact icons only (inline)', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-card__description"><?php echo esc_html__( 'Display social login buttons as compact icons in a single row instead of full-width buttons.', 'ventraconnect-social-login' ); ?></p>
            </div>
            <div class="wsc-card__control">
                <input type="hidden" name="ventraconnect_sl_comments_settings[style][compact_icons]" value="0"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                <label class="wsc-switch">
                    <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_comments_settings[style][compact_icons]" value="1" <?php checked( ! empty( $style['compact_icons'] ) ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label"><?php echo esc_html__( 'Enable compact icons', 'ventraconnect-social-login' ); ?></span>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="wsc-card">
    <div class="wsc-card__header">
        <h3 class="wsc-card__title"><?php echo esc_html__( 'Access Control', 'ventraconnect-social-login' ); ?></h3>
        <p class="wsc-card__description"><?php echo esc_html__( 'Control who can post comments and how they authenticate.', 'ventraconnect-social-login' ); ?></p>
    </div>

    <div class="wsc-card__body">
        <div class="wsc-card--row">
            <div class="wsc-card__content">
                <div class="wsc-card__title"><?php echo esc_html__( 'Require login to comment', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-card__description"><?php echo esc_html__( 'Force all visitors to authenticate via social login before commenting.', 'ventraconnect-social-login' ); ?></p>
            </div>
            <div class="wsc-card__control">
                <input type="hidden" name="ventraconnect_sl_comments_settings[access][require_login]" value="0"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                <label class="wsc-switch">
                    <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_comments_settings[access][require_login]" value="1" <?php checked( ! empty( $access['require_login'] ) ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label"><?php echo esc_html__( 'Require authentication', 'ventraconnect-social-login' ); ?></span>
                </label>
            </div>
        </div>
    </div>
</div>


<div class="wsc-card">
    <div class="wsc-card__header">
        <h3 class="wsc-card__title"><?php echo esc_html__( 'Redirect After Login', 'ventraconnect-social-login' ); ?></h3>
        <p class="wsc-card__description"><?php echo esc_html__( 'Where should users be redirected after logging in via social login on comments?', 'ventraconnect-social-login' ); ?></p>
    </div>

    <div class="wsc-card__body">
        <!-- Redirect Mode -->
        <div class="wsc-field wsc-field--vertical">
            <label class="wsc-field__label"><?php echo esc_html__( 'Redirect mode', 'ventraconnect-social-login' ); ?></label>
            <div class="wsc-field__control">
                <select class="wsc-select wsc-select--md" name="ventraconnect_sl_comments_settings[redirect][mode]"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                    <?php
                    $modes = [
                        'same_page' => __( 'Same page (default)', 'ventraconnect-social-login' ),
                        'thank_you' => __( 'Thank-you page', 'ventraconnect-social-login' ),
                        'custom'    => __( 'Custom URL', 'ventraconnect-social-login' ),
                    ];
                    $sel = isset( $redirect['mode'] ) ? $redirect['mode'] : 'same_page';
                    foreach ( $modes as $k => $label ) :
                        ?>
                        <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $sel, $k ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Custom URL -->
        <div class="wsc-field wsc-field--vertical">
            <label class="wsc-field__label"><?php echo esc_html__( 'Custom URL', 'ventraconnect-social-login' ); ?></label>
            <div class="wsc-field__control">
                <input type="url" class="wsc-input wsc-input--md" name="ventraconnect_sl_comments_settings[redirect][custom_url]" value="<?php echo esc_attr( $redirect['custom_url'] ?? '' ); ?>"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?> placeholder="https://example.com/thank-you" />
                <span class="wsc-input-hint"><?php echo esc_html__( 'Only used when "Custom URL" mode is selected above.', 'ventraconnect-social-login' ); ?></span>
            </div>
        </div>
    </div>
</div>

<div class="wsc-card">
    <div class="wsc-card__header">
        <h3 class="wsc-card__title"><?php echo esc_html__( 'User Profile Linking', 'ventraconnect-social-login' ); ?></h3>
        <p class="wsc-card__description"><?php echo esc_html__( 'Customize how social login information appears alongside user comments.', 'ventraconnect-social-login' ); ?></p>
    </div>

    <div class="wsc-card__body">
        <div class="wsc-card--row">
            <div class="wsc-card__content">
                <div class="wsc-card__title"><?php echo esc_html__( 'Show provider badge next to comment', 'ventraconnect-social-login' ); ?></div>
                <p class="wsc-card__description"><?php echo esc_html__( 'Display a small provider icon next to users avatar if they commented using social login.', 'ventraconnect-social-login' ); ?></p>
            </div>
            <div class="wsc-card__control">
                <input type="hidden" name="ventraconnect_sl_comments_settings[profile][show_provider_badge]" value="0"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                <label class="wsc-switch">
                    <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_comments_settings[profile][show_provider_badge]" value="1" <?php checked( ! empty( $profile['show_provider_badge'] ) ); echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
                    <span class="wsc-switch-ui" aria-hidden="true"></span>
                    <span class="wsc-switch__label"><?php echo esc_html__( 'Show provider badge', 'ventraconnect-social-login' ); ?></span>
                </label>
            </div>
        </div>
    </div>
</div>
		<?php
		if ( $preview_only ) {
			$html = (string) ob_get_clean();
			echo wp_kses_post( $this->apply_preview_filters( $html ) );
		}
	}

	/**
	 * Render Community & Memberships tab layout (preview-capable).
	 *
	 * @param array<string,mixed> $state
	 */
	public function renderMembershipsTab( array $state ): void {
		$preview_only = ! empty( $state['preview_only'] );
		if ( $preview_only ) {
			ob_start();
		}

		?>
<div class="vcs-accordion">
   <details class="vcs-accordion__item is-inactive">
      <summary class="vcs-accordion__summary">
         <div><span class="vcs-accordion__title">Paid Memberships Pro</span><span class="vcs-accordion__description">Add login buttons to PMPro checkout, login, and profile templates.</span></div>
         <span class="vcs-pill is-missing">Not installed</span>
      </summary>
      <div class="vcs-accordion__content is-disabled">
         <div class="vcs-disabled-note">Activate Paid Memberships Pro to edit these settings.</div>
         <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
               <div class="wsc-card__title">Enable login methods for this integration</div>
               <p class="wsc-card__description">Allow users to log in or register using enabled VentraConnect methods on this integration.</p>
            </div>
            <div class="wsc-card__control"><input type="hidden" name="ventraconnect_sl_integrations[pmpro][enabled]" value="0"><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" value="1" name="ventraconnect_sl_integrations[pmpro][enabled]" disabled="disabled"><span class="wsc-switch-ui" aria-hidden="true"></span><span class="wsc-switch__label">Enable integration</span></label></div>
         </div>
         <div class="vcs-field">
            <span class="vcs-field__label">Render buttons on</span>
            <div class="vcs-checkbox-grid"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[pmpro][places][login]" value="1"><input type="checkbox" value="1" checked="checked" name="ventraconnect_sl_integrations[pmpro][places][login]" disabled="disabled"><span>Login form</span></label><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[pmpro][places][checkout_before]" value="1"><input type="checkbox" value="1" checked="checked" name="ventraconnect_sl_integrations[pmpro][places][checkout_before]" disabled="disabled"><span>Before checkout form</span></label><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[pmpro][places][checkout_after]" value="0"><input type="checkbox" value="1" name="ventraconnect_sl_integrations[pmpro][places][checkout_after]" disabled="disabled"><span>After checkout form</span></label></div>
         </div>
         <input type="hidden" name="ventraconnect_sl_integrations[pmpro][post_login]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[pmpro][custom_url]" value="">
         <div class="vcs-field">
            <label class="vcs-field__label" for="pmpro-post-login">Post-login behavior</label>
            <select class="vcs-post-login" data-target="#pmpro-custom-url" id="pmpro-post-login" name="ventraconnect_sl_integrations[pmpro][post_login]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="pmpro-custom-url" name="ventraconnect_sl_integrations[pmpro][custom_url]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where members land after signing in with social login.</p>
         </div>
      </div>
   </details>
   <details class="vcs-accordion__item is-inactive">
      <summary class="vcs-accordion__summary">
         <div><span class="vcs-accordion__title">MemberPress</span><span class="vcs-accordion__description">Streamline MemberPress login and checkout with social buttons.</span></div>
         <span class="vcs-pill is-missing">Not installed</span>
      </summary>
      <div class="vcs-accordion__content is-disabled">
         <div class="vcs-disabled-note">Activate MemberPress to edit these settings.</div>
         <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
               <div class="wsc-card__title">Enable login methods for this integration</div>
               <p class="wsc-card__description">Allow users to log in or register using enabled VentraConnect methods on this integration.</p>
            </div>
            <div class="wsc-card__control"><input type="hidden" name="ventraconnect_sl_integrations[memberpress][enabled]" value="0"><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" value="1" name="ventraconnect_sl_integrations[memberpress][enabled]" disabled="disabled"><span class="wsc-switch-ui" aria-hidden="true"></span><span class="wsc-switch__label">Enable integration</span></label></div>
         </div>
         <div class="vcs-field vcs-field--lifterlms-placements">
            <span class="vcs-field__label">Render buttons on</span>
            <table class="vcs-lms-placements-table" role="presentation">
               <thead>
                  <tr>
                     <th class="vcs-lms-placements-table__col-form">Form</th>
                     <th class="vcs-lms-placements-table__col-enable">Enable</th>
                     <th class="vcs-lms-placements-table__col-placement">Placement</th>
                  </tr>
               </thead>
               <tbody>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--login">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Login form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[memberpress][places][login]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[memberpress][places][login]" value="1" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[memberpress][positions][login]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[memberpress][positions][login]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--checkout">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Signup / Checkout form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[memberpress][places][checkout]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[memberpress][places][checkout]" value="1" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[memberpress][positions][checkout]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[memberpress][positions][checkout]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
               </tbody>
            </table>
            <p class="description">Choose which MemberPress forms should display social login buttons and where they appear within each form.</p>
            <p class="description">Manual placement (optional): <code>[ventraconnect_sl_social_login context="memberpress_login"]</code></p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[memberpress][allow_auto_create_on_login]" value="1" disabled="disabled"><span>Allow new accounts from MemberPress login form</span></label>
            <p class="description">If disabled, social login on the MemberPress login form only works for existing members. New users will need to complete a MemberPress signup or checkout before using social login.</p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[memberpress][show_profile_links]" value="1" disabled="disabled"><span>Show social account connections in MemberPress account</span></label>
            <p class="description">Adds a \"Social accounts\" section to the MemberPress account page so members can link or unlink their social logins.</p>
         </div>
         <input type="hidden" name="ventraconnect_sl_integrations[memberpress][post_login_login]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[memberpress][custom_url_login]" value="">
         <div class="vcs-field">
            <label class="vcs-field__label" for="memberpress-post-login-login">Post-login behavior (Login form)</label>
            <select class="vcs-post-login" data-target="#memberpress-custom-url-login" id="memberpress-post-login-login" name="ventraconnect_sl_integrations[memberpress][post_login_login]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="memberpress-custom-url-login" name="ventraconnect_sl_integrations[memberpress][custom_url_login]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where members land after signing in with social login.</p>
         </div>
         <input type="hidden" name="ventraconnect_sl_integrations[memberpress][post_login_checkout]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[memberpress][custom_url_checkout]" value="">
         <div class="vcs-field">
            <label class="vcs-field__label" for="memberpress-post-login-checkout">Post-login behavior (Signup/Checkout)</label>
            <select class="vcs-post-login" data-target="#memberpress-custom-url-checkout" id="memberpress-post-login-checkout" name="ventraconnect_sl_integrations[memberpress][post_login_checkout]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="memberpress-custom-url-checkout" name="ventraconnect_sl_integrations[memberpress][custom_url_checkout]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where members land after signing in via the signup/checkout form.</p>
         </div>
      </div>
   </details>
   <details class="vcs-accordion__item is-inactive">
      <summary class="vcs-accordion__summary">
         <div><span class="vcs-accordion__title">Paid Membership Subscriptions</span><span class="vcs-accordion__description">Add social login and passwordless access to Paid Membership Subscriptions login, registration, and account pages.</span></div>
         <span class="vcs-pill is-missing">Not installed</span>
      </summary>
      <div class="vcs-accordion__content is-disabled">
         <div class="vcs-disabled-note">Upgrade to Pro to enable Paid Membership Subscriptions integration controls.</div>
         <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
               <div class="wsc-card__title">Enable login methods for this integration</div>
               <p class="wsc-card__description">Allow users to log in or register using enabled VentraConnect methods on this integration.</p>
            </div>
            <div class="wsc-card__control"><input type="hidden" name="ventraconnect_sl_integrations[pms][enabled]" value="0"><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" value="1" name="ventraconnect_sl_integrations[pms][enabled]" disabled="disabled"><span class="wsc-switch-ui" aria-hidden="true"></span><span class="wsc-switch__label">Enable integration</span></label></div>
         </div>
         <div class="vcs-field">
            <p class="description">PMS shortcode-owned pages are recognized. Dedicated placement and guardrail controls will be added in a later phase.</p>
         </div>
      </div>
   </details>
   <details class="vcs-accordion__item is-inactive">
      <summary class="vcs-accordion__summary">
         <div><span class="vcs-accordion__title">Ultimate Member</span><span class="vcs-accordion__description">Surface social login on Ultimate Member registration and login forms.</span></div>
         <span class="vcs-pill is-missing">Not installed</span>
      </summary>
      <div class="vcs-accordion__content is-disabled">
         <div class="vcs-disabled-note">Activate Ultimate Member to edit these settings.</div>
         <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
               <div class="wsc-card__title">Enable login methods for this integration</div>
               <p class="wsc-card__description">Allow users to log in or register using enabled VentraConnect methods on this integration.</p>
            </div>
            <div class="wsc-card__control"><input type="hidden" name="ventraconnect_sl_integrations[ultimate_member][enabled]" value="0"><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" value="1" name="ventraconnect_sl_integrations[ultimate_member][enabled]" disabled="disabled"><span class="wsc-switch-ui" aria-hidden="true"></span><span class="wsc-switch__label">Enable integration</span></label></div>
         </div>
         <div class="vcs-field vcs-field--ultimate-member-placements">
            <span class="vcs-field__label">Render buttons on</span>
            <table class="vcs-lms-placements-table" role="presentation">
               <thead>
                  <tr>
                     <th class="vcs-lms-placements-table__col-form">Form</th>
                     <th class="vcs-lms-placements-table__col-enable">Enable</th>
                     <th class="vcs-lms-placements-table__col-placement">Placement</th>
                  </tr>
               </thead>
               <tbody>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--login">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Login form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[ultimate_member][places][login]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[ultimate_member][places][login]" value="1" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[ultimate_member][positions][login]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[ultimate_member][positions][login]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--register">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Register form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[ultimate_member][places][register]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[ultimate_member][places][register]" value="1" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[ultimate_member][positions][register]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[ultimate_member][positions][register]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
               </tbody>
            </table>
            <p class="description">Choose where Ultimate Member forms should display social login buttons and whether they appear above or below each form.</p>
            <p class="description">Manual placement (optional): <code>[ventraconnect_sl_social_login context="ultimate_member_login"]</code></p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[ultimate_member][show_profile_links]" value="1" disabled="disabled"><span>Show social account connections in Ultimate Member account</span></label>
            <p class="description">Prepare an Ultimate Member account section for managing linked social providers (no effect until enabled in a later version).</p>
         </div>
         <input type="hidden" name="ventraconnect_sl_integrations[ultimate_member][post_login_login]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[ultimate_member][custom_url_login]" value="">
         <div class="vcs-field">
            <label class="vcs-field__label" for="ultimate_member-post-login-login">Post-login behavior (Ultimate Member login form)</label>
            <select class="vcs-post-login" data-target="#ultimate_member-custom-url-login" id="ultimate_member-post-login-login" name="ventraconnect_sl_integrations[ultimate_member][post_login_login]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="ultimate_member-custom-url-login" name="ventraconnect_sl_integrations[ultimate_member][custom_url_login]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where members land after signing in with social login.</p>
         </div>
         <input type="hidden" name="ventraconnect_sl_integrations[ultimate_member][post_login_register]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[ultimate_member][custom_url_register]" value="">
         <div class="vcs-field">
            <label class="vcs-field__label" for="ultimate_member-post-login-register">Post-login behavior (Ultimate Member register form)</label>
            <select class="vcs-post-login" data-target="#ultimate_member-custom-url-register" id="ultimate_member-post-login-register" name="ventraconnect_sl_integrations[ultimate_member][post_login_register]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="ultimate_member-custom-url-register" name="ventraconnect_sl_integrations[ultimate_member][custom_url_register]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where members land after signing in with social login.</p>
         </div>
      </div>
   </details>
   <details class="vcs-accordion__item is-inactive">
      <summary class="vcs-accordion__summary">
         <div><span class="vcs-accordion__title">BuddyPress</span><span class="vcs-accordion__description">Speed up BuddyPress community signups and profile access.</span></div>
         <span class="vcs-pill is-missing">Not installed</span>
      </summary>
      <div class="vcs-accordion__content is-disabled">
         <div class="vcs-disabled-note">Activate BuddyPress to edit these settings.</div>
         <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
               <div class="wsc-card__title">Enable login methods for this integration</div>
               <p class="wsc-card__description">Allow users to log in or register using enabled VentraConnect methods on this integration.</p>
            </div>
            <div class="wsc-card__control"><input type="hidden" name="ventraconnect_sl_integrations[buddypress][enabled]" value="0"><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" value="1" name="ventraconnect_sl_integrations[buddypress][enabled]" disabled="disabled"><span class="wsc-switch-ui" aria-hidden="true"></span><span class="wsc-switch__label">Enable integration</span></label></div>
         </div>
         <div class="vcs-field vcs-field--buddypress-placements">
            <span class="vcs-field__label">Render buttons on</span>
            <table class="vcs-lms-placements-table" role="presentation">
               <thead>
                  <tr>
                     <th class="vcs-lms-placements-table__col-form">Location</th>
                     <th class="vcs-lms-placements-table__col-enable">Enable</th>
                     <th class="vcs-lms-placements-table__col-placement">Placement</th>
                  </tr>
               </thead>
               <tbody>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--login">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Sidebar login widget buttons</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[buddypress][places][login]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[buddypress][places][login]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons in the BuddyPress login widget</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[buddypress][login_position]" value="above" checked="checked" disabled="disabled"><span>Above login form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[buddypress][login_position]" value="below" disabled="disabled"><span>Below login form</span></label></div>
                     </td>
                  </tr>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--register">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Register page buttons</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[buddypress][places][register]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[buddypress][places][register]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on the BuddyPress register page</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[buddypress][register_position]" value="above" checked="checked" disabled="disabled"><span>Above submit buttons</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[buddypress][register_position]" value="below" disabled="disabled"><span>Below submit buttons</span></label></div>
                     </td>
                  </tr>
               </tbody>
            </table>
            <p class="description">Control where BuddyPress login widgets and the registration page display social login buttons.</p>
            <p class="description">Manual placement (optional): <code>[ventraconnect_sl_social_login context="buddypress_login"]</code></p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[buddypress][show_profile_links]" value="1" checked="checked" disabled="disabled"><span>Show social account link/unlink in BuddyPress profile settings</span></label>
            <p class="description">Adds a "Social accounts" settings tab so members can link or unlink their social logins from their BuddyPress/BuddyBoss profile.</p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[buddypress][allow_auto_create_on_login]" value="1" disabled="disabled"><span>Allow new accounts from BuddyPress login widget</span></label>
            <p class="description">If disabled, social login in the BuddyPress sidebar login widget only works for existing users. New members will need to register via the BuddyPress registration page first.</p>
         </div>
         <div class="vcs-field">
            <span class="vcs-field__label">After social login via BuddyPress…</span>
            <select name="ventraconnect_sl_integrations[buddypress][bp_redirect_mode]" disabled="disabled">
               <option value="stay">Stay on the same page</option>
               <option value="profile">Go to BuddyPress profile page</option>
               <option value="custom" selected="selected">Go to this URL</option>
            </select>
            <input type="text" class="regular-text" name="ventraconnect_sl_integrations[buddypress][bp_redirect_url]" value="https://ventraconnect.wpventra.com/members/" disabled="disabled">
            <p class="description">Used for both the BuddyPress login widget and the BuddyPress registration form. When set to “stay”, members remain on the same page after social login. “Profile page” sends them to their BuddyPress profile. “Custom URL” always redirects to the URL above.</p>
         </div>
      </div>
   </details>
</div>
		<?php
		if ( $preview_only ) {
			$html = (string) ob_get_clean();
			echo wp_kses_post( $this->apply_preview_filters( $html ) );
		}
	}

	/**
	 * Render Courses & LMS tab layout (preview-capable).
	 *
	 * @param array<string,mixed> $state
	 */
	public function renderLmsTab( array $state ): void {
		$preview_only = ! empty( $state['preview_only'] );
		if ( $preview_only ) {
			ob_start();
		}

		?>
<div class="vcs-accordion">
   <details class="vcs-accordion__item is-inactive">
      <summary class="vcs-accordion__summary">
         <div><span class="vcs-accordion__title">LearnPress</span><span class="vcs-accordion__description">Place social login buttons on LearnPress login, registration, and checkout forms.</span></div>
         <span class="vcs-pill is-missing">Not installed</span>
      </summary>
      <div class="vcs-accordion__content is-disabled">
         <div class="vcs-disabled-note">Activate LearnPress to edit these settings.</div>
         <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
               <div class="wsc-card__title">Enable login methods for this integration</div>
               <p class="wsc-card__description">Allow users to log in or register using enabled VentraConnect methods on this integration.</p>
            </div>
            <div class="wsc-card__control"><input type="hidden" name="ventraconnect_sl_integrations[learnpress][enabled]" value="0"><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" value="1" name="ventraconnect_sl_integrations[learnpress][enabled]" disabled="disabled"><span class="wsc-switch-ui" aria-hidden="true"></span><span class="wsc-switch__label">Enable integration</span></label></div>
         </div>
         <div class="vcs-field vcs-field--lifterlms-placements">
            <span class="vcs-field__label">Render buttons on</span>
            <table class="vcs-lms-placements-table" role="presentation">
               <thead>
                  <tr>
                     <th class="vcs-lms-placements-table__col-form">Form</th>
                     <th class="vcs-lms-placements-table__col-enable">Enable</th>
                     <th class="vcs-lms-placements-table__col-placement">Placement</th>
                  </tr>
               </thead>
               <tbody>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--login">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Login form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[learnpress][places][login]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[learnpress][places][login]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[learnpress][positions][login]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[learnpress][positions][login]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--register">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Register form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[learnpress][places][register]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[learnpress][places][register]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[learnpress][positions][register]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[learnpress][positions][register]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--checkout">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Checkout form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[learnpress][places][checkout]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[learnpress][places][checkout]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement"><span class="vcs-lms-placements-table__fixed-note">Above form (fixed)</span></td>
                  </tr>
               </tbody>
            </table>
            <p class="description">Choose which LearnPress forms should display social login buttons and where they appear within each form.</p>
            <p class="description">Manual placement (optional): <code>[ventraconnect_sl_social_login context="learnpress_login"]</code></p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[learnpress][allow_auto_create_on_login]" value="1" disabled="disabled"><span>Allow new accounts from LearnPress login form</span></label>
            <p class="description">If disabled, social login on the LearnPress login form only works for existing students. New users will need to register via the LearnPress registration or checkout flow.</p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[learnpress][show_profile_links]" value="1" disabled="disabled"><span>Show social account connections in LearnPress profile</span></label>
            <p class="description">Adds a \"Social accounts\" section to the LearnPress user profile so learners can link or unlink their social logins.</p>
         </div>
         <input type="hidden" name="ventraconnect_sl_integrations[learnpress][post_login]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[learnpress][custom_url]" value="">
         <div class="vcs-field">
            <label class="vcs-field__label" for="learnpress-post-login">Post-login behavior</label>
            <select class="vcs-post-login" data-target="#learnpress-custom-url" id="learnpress-post-login" name="ventraconnect_sl_integrations[learnpress][post_login]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="learnpress-custom-url" name="ventraconnect_sl_integrations[learnpress][custom_url]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where learners land after a successful social login.</p>
         </div>
      </div>
  </details>
  <details class="vcs-accordion__item is-inactive">
      <summary class="vcs-accordion__summary">
         <div><span class="vcs-accordion__title">Tutor LMS</span><span class="vcs-accordion__description">Add social login and passwordless access to Tutor LMS login, student registration, instructor registration, and dashboard pages.</span></div>
         <span class="vcs-pill is-missing">Not installed</span>
      </summary>
      <div class="vcs-accordion__content is-disabled">
         <div class="vcs-disabled-note">Activate Tutor LMS to edit these settings.</div>
         <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
               <div class="wsc-card__title">Enable login methods for this integration</div>
               <p class="wsc-card__description">Allow users to log in or register using enabled VentraConnect methods on this integration.</p>
            </div>
            <div class="wsc-card__control"><input type="hidden" name="ventraconnect_sl_integrations[tutor_lms][enabled]" value="0"><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" value="1" name="ventraconnect_sl_integrations[tutor_lms][enabled]" disabled="disabled"><span class="wsc-switch-ui" aria-hidden="true"></span><span class="wsc-switch__label">Enable integration</span></label></div>
         </div>
         <div class="vcs-field vcs-field--lifterlms-placements">
            <span class="vcs-field__label">Render buttons on</span>
            <table class="vcs-lms-placements-table" role="presentation">
               <thead>
                  <tr>
                     <th class="vcs-lms-placements-table__col-form">Form</th>
                     <th class="vcs-lms-placements-table__col-enable">Enable</th>
                     <th class="vcs-lms-placements-table__col-placement">Placement</th>
                  </tr>
               </thead>
               <tbody>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--login">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Login form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[tutor_lms][places][login]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[tutor_lms][places][login]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[tutor_lms][positions][login]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[tutor_lms][positions][login]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--student_register">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Student registration form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[tutor_lms][places][student_register]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[tutor_lms][places][student_register]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[tutor_lms][positions][student_register]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[tutor_lms][positions][student_register]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--instructor_register">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Instructor registration form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[tutor_lms][places][instructor_register]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[tutor_lms][places][instructor_register]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[tutor_lms][positions][instructor_register]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[tutor_lms][positions][instructor_register]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
               </tbody>
            </table>
            <p class="description">Choose where Tutor LMS login, student registration, and instructor registration forms should display social login buttons.</p>
            <p class="description">Manual placement (optional): <code>[ventraconnect_sl_social_login context="tutor_login"]</code></p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[tutor_lms][allow_auto_create_on_login]" value="1" disabled="disabled"><span>Allow new account creation from Tutor LMS login</span></label>
            <p class="description">When disabled, social login on the Tutor LMS login form can only sign in or link existing users. New users must register through the Tutor LMS registration flow.</p>
         </div>
         <input type="hidden" name="ventraconnect_sl_integrations[tutor_lms][post_login_login]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[tutor_lms][custom_url_login]" value=""><input type="hidden" name="ventraconnect_sl_integrations[tutor_lms][post_login_student_register]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[tutor_lms][custom_url_student_register]" value=""><input type="hidden" name="ventraconnect_sl_integrations[tutor_lms][post_login_instructor_register]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[tutor_lms][custom_url_instructor_register]" value="">
         <div class="vcs-field">
            <label class="vcs-field__label" for="tutor-lms-post-login-login">Post-login behavior (Login form)</label>
            <select class="vcs-post-login" data-target="#tutor-lms-custom-url-login" id="tutor-lms-post-login-login" name="ventraconnect_sl_integrations[tutor_lms][post_login_login]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="tutor-lms-custom-url-login" name="ventraconnect_sl_integrations[tutor_lms][custom_url_login]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where learners land after a successful social login.</p>
         </div>
         <div class="vcs-field">
            <label class="vcs-field__label" for="tutor-lms-post-login-student-register">Post-login behavior (Student registration form)</label>
            <select class="vcs-post-login" data-target="#tutor-lms-custom-url-student-register" id="tutor-lms-post-login-student-register" name="ventraconnect_sl_integrations[tutor_lms][post_login_student_register]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="tutor-lms-custom-url-student-register" name="ventraconnect_sl_integrations[tutor_lms][custom_url_student_register]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where learners land after signing in via the Tutor LMS student registration form.</p>
         </div>
         <div class="vcs-field">
            <label class="vcs-field__label" for="tutor-lms-post-login-instructor-register">Post-login behavior (Instructor registration form)</label>
            <select class="vcs-post-login" data-target="#tutor-lms-custom-url-instructor-register" id="tutor-lms-post-login-instructor-register" name="ventraconnect_sl_integrations[tutor_lms][post_login_instructor_register]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="tutor-lms-custom-url-instructor-register" name="ventraconnect_sl_integrations[tutor_lms][custom_url_instructor_register]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where learners land after signing in via the Tutor LMS instructor registration form.</p>
         </div>
      </div>
   </details>
   <details class="vcs-accordion__item is-inactive">
      <summary class="vcs-accordion__summary">
         <div><span class="vcs-accordion__title">LearnDash</span><span class="vcs-accordion__description">Simplify enrollment and course access with LearnDash-ready social login buttons.</span></div>
         <span class="vcs-pill is-missing">Not installed</span>
      </summary>
      <div class="vcs-accordion__content is-disabled">
         <div class="vcs-disabled-note">Activate LearnDash to edit these settings.</div>
         <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
               <div class="wsc-card__title">Enable login methods for this integration</div>
               <p class="wsc-card__description">Allow users to log in or register using enabled VentraConnect methods on this integration.</p>
            </div>
            <div class="wsc-card__control"><input type="hidden" name="ventraconnect_sl_integrations[learndash][enabled]" value="0"><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" value="1" name="ventraconnect_sl_integrations[learndash][enabled]" disabled="disabled"><span class="wsc-switch-ui" aria-hidden="true"></span><span class="wsc-switch__label">Enable integration</span></label></div>
         </div>
         <div class="vcs-field vcs-field--lifterlms-placements">
            <span class="vcs-field__label">Render buttons on</span>
            <table class="vcs-lms-placements-table" role="presentation">
               <thead>
                  <tr>
                     <th class="vcs-lms-placements-table__col-form">Form</th>
                     <th class="vcs-lms-placements-table__col-enable">Enable</th>
                     <th class="vcs-lms-placements-table__col-placement">Placement</th>
                  </tr>
               </thead>
               <tbody>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--login">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Login form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[learndash][places][login]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[learndash][places][login]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[learndash][positions][login]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[learndash][positions][login]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--register">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Register form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[learndash][places][register]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[learndash][places][register]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[learndash][positions][register]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[learndash][positions][register]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
               </tbody>
            </table>
            <p class="description">Choose which LearnDash forms should display social login buttons and where they appear within each form.</p>
            <p class="description">Manual placement (optional): <code>[ventraconnect_sl_social_login context="learndash_login"]</code></p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[learndash][allow_auto_create_on_login]" value="1" disabled="disabled"><span>Allow new accounts from LearnDash login form</span></label>
            <p class="description">If disabled, social login on the LearnDash login form only works for existing students. New users will need to register via a LearnDash registration or checkout flow.</p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[learndash][show_profile_links]" value="1" disabled="disabled"><span>Show social account connections in LearnDash profile</span></label>
            <p class="description">Adds a \"Social accounts\" section to the LearnDash profile area so learners can link or unlink their social logins.</p>
         </div>
         <input type="hidden" name="ventraconnect_sl_integrations[learndash][post_login]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[learndash][custom_url]" value="">
         <div class="vcs-field">
            <label class="vcs-field__label" for="learndash-post-login">Post-login behavior</label>
            <select class="vcs-post-login" data-target="#learndash-custom-url" id="learndash-post-login" name="ventraconnect_sl_integrations[learndash][post_login]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="learndash-custom-url" name="ventraconnect_sl_integrations[learndash][custom_url]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where learners land after a successful social login.</p>
         </div>
      </div>
   </details>
   <details class="vcs-accordion__item is-inactive">
      <summary class="vcs-accordion__summary">
         <div><span class="vcs-accordion__title">LifterLMS</span><span class="vcs-accordion__description">Place buttons on LifterLMS login, registration, and purchase forms.</span></div>
         <span class="vcs-pill is-missing">Not installed</span>
      </summary>
      <div class="vcs-accordion__content is-disabled">
         <div class="vcs-disabled-note">Activate LifterLMS to edit these settings.</div>
         <div class="wsc-card wsc-card--row">
            <div class="wsc-card__content">
               <div class="wsc-card__title">Enable login methods for this integration</div>
               <p class="wsc-card__description">Allow users to log in or register using enabled VentraConnect methods on this integration.</p>
            </div>
            <div class="wsc-card__control"><input type="hidden" name="ventraconnect_sl_integrations[lifterlms][enabled]" value="0"><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" value="1" name="ventraconnect_sl_integrations[lifterlms][enabled]" disabled="disabled"><span class="wsc-switch-ui" aria-hidden="true"></span><span class="wsc-switch__label">Enable integration</span></label></div>
         </div>
         <div class="vcs-field vcs-field--lifterlms-placements">
            <span class="vcs-field__label">Render buttons on</span>
            <table class="vcs-lms-placements-table" role="presentation">
               <thead>
                  <tr>
                     <th class="vcs-lms-placements-table__col-form">Form</th>
                     <th class="vcs-lms-placements-table__col-enable">Enable</th>
                     <th class="vcs-lms-placements-table__col-placement">Placement</th>
                  </tr>
               </thead>
               <tbody>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--login">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Login form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[lifterlms][places][login]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[lifterlms][places][login]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[lifterlms][positions][login]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[lifterlms][positions][login]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--register">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Register form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[lifterlms][places][register]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[lifterlms][places][register]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[lifterlms][positions][register]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[lifterlms][positions][register]" value="inside_form" disabled="disabled"><span>Inside form (below fields)</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[lifterlms][positions][register]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
                  <tr class="vcs-lms-placements-table__row vcs-lms-placements-table__row--purchase">
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-form"><span class="vcs-lms-placements-table__form-label">Purchase form</span></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-enable"><label class="wsc-checkbox-inline"><input type="hidden" name="ventraconnect_sl_integrations[lifterlms][places][purchase]" value="0"><input type="checkbox" name="ventraconnect_sl_integrations[lifterlms][places][purchase]" value="1" checked="checked" disabled="disabled"><span class="screen-reader-text">Enable buttons on this form</span></label></td>
                     <td class="vcs-lms-placements-table__cell vcs-lms-placements-table__cell-placement">
                        <div class="vcs-lms-placements-table__placements"><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[lifterlms][positions][purchase]" value="above_form" disabled="disabled"><span>Above form</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[lifterlms][positions][purchase]" value="inside_form" disabled="disabled"><span>Inside form (below fields)</span></label><label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_integrations[lifterlms][positions][purchase]" value="below_form" checked="checked" disabled="disabled"><span>Below form</span></label></div>
                     </td>
                  </tr>
               </tbody>
            </table>
            <p class="description">Choose which LifterLMS forms should display social login buttons and where they appear within each form.</p>
            <p class="description">Manual placement (optional): <code>[ventraconnect_sl_social_login context="lifterlms_login"]</code></p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[lifterlms][allow_auto_create_on_login]" value="1" disabled="disabled"><span>Allow new accounts from Lifter login form</span></label>
            <p class="description">If disabled, social login on the Lifter login page only works for existing students. New users will be redirected to the registration/checkout flow.</p>
         </div>
         <div class="vcs-field">
            <label class="wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_integrations[lifterlms][show_profile_links]" value="1" disabled="disabled"><span>Show social account connections in Lifter student profile</span></label>
            <p class="description">Adds a \"Social accounts\" section to the Lifter student dashboard so users can link or unlink their social logins.</p>
         </div>
         <input type="hidden" name="ventraconnect_sl_integrations[lifterlms][post_login]" value="stay"><input type="hidden" name="ventraconnect_sl_integrations[lifterlms][custom_url]" value="">
         <div class="vcs-field">
            <label class="vcs-field__label" for="lifterlms-post-login">Post-login behavior</label>
            <select class="vcs-post-login" data-target="#lifterlms-custom-url" id="lifterlms-post-login" name="ventraconnect_sl_integrations[lifterlms][post_login]" disabled="disabled">
               <option value="stay" selected="selected">Stay on page</option>
               <option value="account">Go to My Account</option>
               <option value="custom">Custom URL</option>
            </select>
            <input type="url" class="regular-text vcs-custom-url" id="lifterlms-custom-url" name="ventraconnect_sl_integrations[lifterlms][custom_url]" value="" placeholder="https://" style="display:none;" disabled="disabled">
            <p class="description">Choose where learners land after a successful social login.</p>
         </div>
      </div>
   </details>
</div>
		<?php
		if ( $preview_only ) {
			$html = (string) ob_get_clean();
			echo wp_kses_post( $this->apply_preview_filters( $html ) );
		}
	}

	public function renderDiagnosticsTab( array $state ): void {
		$report = [
			'snapshot'     => [],
			'checks'       => [],
			'events'       => [],
			'support_blob' => '',
		];

		if ( class_exists( '\VentraConnect\SocialLogin\Diagnostics\Report' ) ) {
			$report = \VentraConnect\SocialLogin\Diagnostics\Report::build_full_report( 10, null );
		}

		$snapshot     = isset( $report['snapshot'] ) && is_array( $report['snapshot'] ) ? $report['snapshot'] : [];
		$checks       = isset( $report['checks'] ) && is_array( $report['checks'] ) ? $report['checks'] : [];
		$events       = isset( $report['events'] ) && is_array( $report['events'] ) ? $report['events'] : [];
		$support_blob = isset( $report['support_blob'] ) && is_string( $report['support_blob'] ) ? $report['support_blob'] : '';

		if ( '' === $support_blob && ! empty( $snapshot ) && class_exists( '\VentraConnect\SocialLogin\Diagnostics\Export' ) ) {
			$support_blob = \VentraConnect\SocialLogin\Diagnostics\Export::build_support_blob( $snapshot, $events );
		}

		// Pro: augment diagnostics blob with Pro details (passwordless + integrations).
		if (
			function_exists( 'vcsl_is_pro_active' )
			&& vcsl_is_pro_active()
			&& class_exists( '\VentraConnect\SocialLogin\Pro\Diagnostics\Hooks' )
			&& method_exists( '\VentraConnect\SocialLogin\Pro\Diagnostics\Hooks', 'augment_support_blob' )
		) {
			$support_blob = \VentraConnect\SocialLogin\Pro\Diagnostics\Hooks::augment_support_blob(
				$support_blob,
				$snapshot,
				$events
			);
		}

		$env          = isset( $snapshot['env'] ) && is_array( $snapshot['env'] ) ? $snapshot['env'] : [];
		$health       = isset( $snapshot['plugin_health'] ) && is_array( $snapshot['plugin_health'] ) ? $snapshot['plugin_health'] : [];
		$providers    = isset( $snapshot['providers'] ) && is_array( $snapshot['providers'] ) ? $snapshot['providers'] : [];
		$integrations = isset( $snapshot['integrations'] ) && is_array( $snapshot['integrations'] ) ? $snapshot['integrations'] : [];
		$debug        = isset( $snapshot['debug'] ) && is_array( $snapshot['debug'] ) ? $snapshot['debug'] : [];

		$env_site_url    = isset( $env['site_url'] ) ? (string) $env['site_url'] : '';
		$env_wp_version  = isset( $env['wp_version'] ) ? (string) $env['wp_version'] : '';
		$env_php         = isset( $env['php_version'] ) ? (string) $env['php_version'] : '';
		$env_plugin_ver  = isset( $env['plugin_version'] ) ? (string) $env['plugin_version'] : '';
		$env_pro_version = isset( $env['pro_version'] ) ? (string) $env['pro_version'] : '';
		$env_pro_active  = ! empty( $env['pro_active'] );
		$env_advanced_passkeys_available = array_key_exists( 'advanced_passkeys_available', $env )
			? ! empty( $env['advanced_passkeys_available'] )
			: ! empty( $env['passkeys_addon_active'] );
		$env_advanced_passkeys_version   = isset( $env['advanced_passkeys_version'] )
			? (string) $env['advanced_passkeys_version']
			: ( isset( $env['passkeys_addon_version'] ) ? (string) $env['passkeys_addon_version'] : 'n/a' );
		$env_advanced_passkeys_source    = isset( $env['advanced_passkeys_source'] ) ? (string) $env['advanced_passkeys_source'] : '';
		$env_standalone_passkeys_local_active = ! empty( $env['standalone_passkeys_local_active'] );
		$env_standalone_passkeys_local_version = isset( $env['standalone_passkeys_local_version'] ) ? (string) $env['standalone_passkeys_local_version'] : '';
		$env_https       = ! empty( $env['https'] );
		$passkeys_supported_integrations = [
			'woocommerce',
			'learndash',
			'learnpress',
			'lifterlms',
			'memberpress',
			'pmpro',
			'ultimate_member',
			'buddypress',
		];

		$health_status = isset( $health['status'] ) ? (string) $health['status'] : 'ok';

		$health_checks = isset( $health['checks'] ) && is_array( $health['checks'] ) ? $health['checks'] : [];

		$status_notes = [];

		if ( array_key_exists( 'php_version_ok', $health_checks ) && ! $health_checks['php_version_ok'] ) {
			$status_notes[] = esc_html__(
				'PHP version is below the minimum supported by this plugin.',
				'ventraconnect-social-login'
			);
		}

		if ( array_key_exists( 'wp_version_ok', $health_checks ) && ! $health_checks['wp_version_ok'] ) {
			$status_notes[] = esc_html__(
				'WordPress version is below the minimum supported by this plugin.',
				'ventraconnect-social-login'
			);
		}

		if ( array_key_exists( 'https_ok', $health_checks ) && ! $health_checks['https_ok'] ) {
			$status_notes[] = esc_html__(
				'HTTPS is disabled. Many OAuth providers require HTTPS for live apps.',
				'ventraconnect-social-login'
			);
		}

		if ( array_key_exists( 'rest_api_ok', $health_checks ) && ! $health_checks['rest_api_ok'] ) {
			$status_notes[] = esc_html__(
				'The WordPress REST API could not be reached from this site.',
				'ventraconnect-social-login'
			);
		}

		$pro_active     = function_exists( 'vcsl_is_pro_active' ) && vcsl_is_pro_active();

		$providers_count = isset( $providers['configured_count'] ) ? (int) $providers['configured_count'] : 0;
		$providers_slugs = isset( $providers['configured_slugs'] ) && is_array( $providers['configured_slugs'] ) ? $providers['configured_slugs'] : [];

		$woo          = isset( $integrations['woocommerce'] ) && is_array( $integrations['woocommerce'] ) ? $integrations['woocommerce'] : [];
		$woo_active   = ! empty( $woo['active'] );
		$woo_login_on = ! empty( $woo['login_context_on'] );

		$passwordless      = isset( $integrations['passwordless'] ) && is_array( $integrations['passwordless'] ) ? $integrations['passwordless'] : [];
		$passwordless_mode = isset( $passwordless['mode'] ) ? (string) $passwordless['mode'] : 'off';

		$ml    = isset( $integrations['magic_link'] ) && is_array( $integrations['magic_link'] ) ? $integrations['magic_link'] : [];
		$ml_on = ! empty( $ml['enabled'] );

		$otp    = isset( $integrations['otp'] ) && is_array( $integrations['otp'] ) ? $integrations['otp'] : [];
		$otp_on = ! empty( $otp['enabled'] );

		$debug_mode        = ! empty( $debug['debug_mode'] );
		$integration_debug = ! empty( $debug['integration_debug'] );
		$passkeys_diag     = isset( $snapshot['passkeys'] ) && is_array( $snapshot['passkeys'] ) ? $snapshot['passkeys'] : [];
		$passwordless_diag = isset( $snapshot['passwordless_accounts'] ) && is_array( $snapshot['passwordless_accounts'] ) ? $snapshot['passwordless_accounts'] : [];
		$passkey_tables    = isset( $passkeys_diag['tables'] ) && is_array( $passkeys_diag['tables'] ) ? $passkeys_diag['tables'] : [];
		$passkey_counts    = isset( $passkeys_diag['counts'] ) && is_array( $passkeys_diag['counts'] ) ? $passkeys_diag['counts'] : [];
		$passwordless_counts = isset( $passwordless_diag['counts'] ) && is_array( $passwordless_diag['counts'] ) ? $passwordless_diag['counts'] : [];
		$passkey_runtime_intentionally_unsupported = ! empty( $passkeys_diag['passkey_runtime_intentionally_unsupported'] );
		$passkey_method_enabled                    = ! empty( $passkeys_diag['passkey_method_enabled'] );
		$passkey_wp_login_enabled                  = ! empty( $passkeys_diag['wp_login_enabled'] );
		$passkey_wp_register_enabled               = ! empty( $passkeys_diag['wp_register_enabled'] );
		$passkey_users_can_register                = ! empty( $passkeys_diag['users_can_register'] );
		$passkey_db_version                        = isset( $passkeys_diag['passkey_db_version'] ) ? (string) $passkeys_diag['passkey_db_version'] : '';
		$passkey_vendor_autoload_exists            = ! empty( $passkeys_diag['free_vendor_autoload_exists'] );
		$passkey_tables_present                    = ! empty( $passkey_tables )
			&& ! empty( $passkey_tables['passkeys']['exists'] )
			&& ! empty( $passkey_tables['challenges']['exists'] )
			&& ! empty( $passkey_tables['logs']['exists'] );
		$passkey_supported_for_auth_stack         = ! empty( $passkeys_diag['passkey_supported_helper_result'] ) || ! empty( $passkeys_diag['passkey_core_supported'] );
		$passwordless_total_accounts               = isset( $passwordless_counts['passwordless_accounts'] ) ? (int) $passwordless_counts['passwordless_accounts'] : 0;
		$passwordless_without_user_password        = isset( $passwordless_counts['passwordless_without_user_password'] ) ? (int) $passwordless_counts['passwordless_without_user_password'] : 0;
		$passkey_check_keys                        = [
			'passkey_php_support_gate'               => __( 'PHP support gate', 'ventraconnect-social-login' ),
			'passkey_method_status'                  => __( 'Passkey method status', 'ventraconnect-social-login' ),
			'passkey_db_tables'                      => __( 'Passkey DB tables', 'ventraconnect-social-login' ),
			'passkey_vendor_autoload'                => __( 'Vendor autoload', 'ventraconnect-social-login' ),
			'passwordless_internal_password_helper'  => __( 'Internal password helper', 'ventraconnect-social-login' ),
			'passwordless_account_marker_helper'     => __( 'Account marker helper', 'ventraconnect-social-login' ),
			'passwordless_password_reset_hook'       => __( 'Password reset hook', 'ventraconnect-social-login' ),
			'passkeys_addon_status'                  => __( 'Pro Advanced Passkeys', 'ventraconnect-social-login' ),
			'passkeys_free_core_bridge'              => __( 'Free core bridge', 'ventraconnect-social-login' ),
			'passkeys_extension_mode'                => __( 'Extension mode', 'ventraconnect-social-login' ),
			'passkeys_repository_bridge'             => __( 'Repository bridge', 'ventraconnect-social-login' ),
			'passkeys_integration_class'             => __( 'Integration class', 'ventraconnect-social-login' ),
		];
		$passkey_native_login_register_label       = '';
		$passwordless_account_hardening_ok         = ! empty( $passwordless_diag['generate_internal_password_helper_exists'] ) && ! empty( $passwordless_diag['mark_passwordless_account_helper_exists'] );
		$passwordless_password_reset_tracking_ok   = ! empty( $passwordless_diag['mark_password_set_helper_exists'] ) && ! empty( $passwordless_diag['after_password_reset_hook_registered'] );
		$passkey_diag_warnings                     = [];

		if ( ! $passkey_method_enabled && ( $passkey_wp_login_enabled || $passkey_wp_register_enabled ) ) {
			$passkey_native_login_register_label = __( 'Configured, inactive while method disabled', 'ventraconnect-social-login' );
		} else {
			$native_states = [];
			$native_states[] = sprintf(
				/* translators: %s: enabled/disabled status. */
				__( 'Login %s', 'ventraconnect-social-login' ),
				$passkey_wp_login_enabled ? __( 'enabled', 'ventraconnect-social-login' ) : __( 'disabled', 'ventraconnect-social-login' )
			);
			$native_states[] = sprintf(
				/* translators: %s: enabled/disabled status. */
				__( 'Register %s', 'ventraconnect-social-login' ),
				$passkey_wp_register_enabled ? __( 'enabled', 'ventraconnect-social-login' ) : __( 'disabled', 'ventraconnect-social-login' )
			);
			$passkey_native_login_register_label = implode( ' / ', $native_states );
		}

		foreach ( $passkey_check_keys as $check_key => $check_label ) {
			$check = isset( $checks[ $check_key ] ) && is_array( $checks[ $check_key ] ) ? $checks[ $check_key ] : [];
			if ( empty( $check ) || ! empty( $check['ok'] ) ) {
				continue;
			}

			$passkey_diag_warnings[] = [
				'label'  => $check_label,
				'detail' => isset( $check['detail'] ) ? (string) $check['detail'] : '',
			];
		}

		// Structured health messages from snapshot, if available.
		$health_messages  = isset( $health['messages'] ) && is_array( $health['messages'] ) ? $health['messages'] : [];
		$health_errors    = isset( $health_messages['errors'] ) && is_array( $health_messages['errors'] ) ? $health_messages['errors'] : [];
		$health_warnings  = isset( $health_messages['warnings'] ) && is_array( $health_messages['warnings'] ) ? $health_messages['warnings'] : [];

		$diag_nonce = wp_create_nonce( 'ventraconnect_sl_site_diagnostics' );

		$health_label = __( 'OK', 'ventraconnect-social-login' );
		$health_class = 'wsc-badge-status--success';
		if ( 'warning' === $health_status ) {
			$health_label = __( 'Warnings', 'ventraconnect-social-login' );
			$health_class = 'wsc-badge-status--warning';
		} elseif ( 'error' === $health_status ) {
			$health_label = __( 'Errors', 'ventraconnect-social-login' );
			$health_class = 'wsc-badge-status--error';
		}

		?>
		<div class="wsc-diag-root wsc-layout wsc-layout--two-column" data-wsc-diag-nonce="<?php echo esc_attr( $diag_nonce ); ?>">
			<div class="wsc-layout__primary">
				<div class="wsc-card">
	<div class="wsc-card__header">
		<h2 class="wsc-card__title">
			<?php echo esc_html__( 'Health overview', 'ventraconnect-social-login' ); ?>
		</h2>
		<p class="wsc-card__description">
			<?php echo esc_html__( 'Snapshot of your environment and authentication stack.', 'ventraconnect-social-login' ); ?>
		</p>
	</div>
	<div class="wsc-card__body">
		<div class="wsc-health-grid">
			<!-- Left Column: Environment -->
			<div class="wsc-health-section">
				<h3 class="wsc-health-section__title"><?php echo esc_html__( 'Environment', 'ventraconnect-social-login' ); ?></h3>
				<div class="wsc-health-list">
					<div class="wsc-health-item">
						<span class="wsc-health-item__label"><?php echo esc_html__( 'Site URL', 'ventraconnect-social-login' ); ?></span>
						<span class="wsc-health-item__value"><?php echo esc_html( $env_site_url ); ?></span>
					</div>
					<div class="wsc-health-item">
						<span class="wsc-health-item__label"><?php echo esc_html__( 'WordPress', 'ventraconnect-social-login' ); ?></span>
						<span class="wsc-health-item__value"><?php echo esc_html( $env_wp_version ); ?></span>
					</div>
					<div class="wsc-health-item">
						<span class="wsc-health-item__label"><?php echo esc_html__( 'PHP', 'ventraconnect-social-login' ); ?></span>
						<span class="wsc-health-item__value"><?php echo esc_html( $env_php ); ?></span>
					</div>
					<div class="wsc-health-item">
						<span class="wsc-health-item__label"><?php echo esc_html__( 'Plugin version', 'ventraconnect-social-login' ); ?></span>
						<span class="wsc-health-item__value"><?php echo esc_html( $env_plugin_ver ); ?></span>
					</div>
					<div class="wsc-health-item">
						<span class="wsc-health-item__label"><?php echo esc_html__( 'Pro add-on', 'ventraconnect-social-login' ); ?></span>
						<span class="wsc-health-item__value">
							<?php
							if ( $env_pro_active ) {
								echo esc_html(
									sprintf(
										/* translators: %s: Pro plugin version. */
										__( 'Active (v%s)', 'ventraconnect-social-login' ),
										$env_pro_version !== '' ? $env_pro_version : 'n/a'
									)
								);
							} else {
								echo esc_html__( 'Not active', 'ventraconnect-social-login' );
							}
							?>
						</span>
					</div>
					<div class="wsc-health-item">
						<span class="wsc-health-item__label"><?php echo esc_html__( 'Advanced passkey integrations', 'ventraconnect-social-login' ); ?></span>
						<span class="wsc-health-item__value">
							<?php
							if ( $env_advanced_passkeys_available ) {
								if ( 'n/a' !== $env_advanced_passkeys_version && '' !== $env_advanced_passkeys_version ) {
									echo esc_html(
										sprintf(
											/* translators: %s: bundled Pro advanced passkeys version. */
											__( 'Included in Pro (v%s)', 'ventraconnect-social-login' ),
											$env_advanced_passkeys_version
										)
									);
								} else {
									echo esc_html__( 'Included in Pro', 'ventraconnect-social-login' );
								}
							} elseif ( $env_pro_active ) {
								echo esc_html__( 'Pro active; Free passkey core unavailable', 'ventraconnect-social-login' );
							} else {
								echo esc_html__( 'Available in Pro', 'ventraconnect-social-login' );
							}

							if ( $env_standalone_passkeys_local_active ) {
								$standalone_note = $env_standalone_passkeys_local_version !== ''
									? sprintf(
										/* translators: %s: locally active standalone passkeys plugin version. */
										__( ' Local standalone detected (v%s).', 'ventraconnect-social-login' ),
										$env_standalone_passkeys_local_version
									)
									: __( ' Local standalone detected.', 'ventraconnect-social-login' );
								echo esc_html( $standalone_note );
							} elseif ( 'standalone' === $env_advanced_passkeys_source ) {
								echo esc_html__( ' Local standalone source detected.', 'ventraconnect-social-login' );
							}
							?>
						</span>
					</div>
					<div class="wsc-health-item">
						<span class="wsc-health-item__label"><?php echo esc_html__( 'HTTPS', 'ventraconnect-social-login' ); ?></span>
						<span class="wsc-health-item__value">
							<?php if ( $env_https ) : ?>
								<span class="wsc-pill wsc-pill-soft-success wsc-pill-sm"><?php echo esc_html__( 'On', 'ventraconnect-social-login' ); ?></span>
							<?php else : ?>
								<span class="wsc-pill wsc-pill-soft-error wsc-pill-sm"><?php echo esc_html__( 'Off', 'ventraconnect-social-login' ); ?></span>
							<?php endif; ?>
						</span>
					</div>
				</div>
			</div>

			<!-- Right Column: Status, Auth Stack, Integrations -->
			<div class="wsc-health-section">
				<h3 class="wsc-health-section__title">
					<?php echo esc_html__( 'Status', 'ventraconnect-social-login' ); ?>
				</h3>

				<div style="margin-bottom: 20px;">
					<span class="wsc-badge <?php echo esc_attr( $health_class ); ?>">
						<?php echo esc_html( $health_label ); ?>
					</span>
				</div>

				<?php
				// Prefer explicit snapshot messages; fall back to legacy notes.
				if ( ! empty( $health_errors ) ) :
					?>
					<ul class="wsc-status-list wsc-status-list--error">
						<?php foreach ( $health_errors as $msg ) : ?>
							<li><?php echo esc_html( $msg ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php elseif ( ! empty( $health_warnings ) ) : ?>
					<ul class="wsc-status-list wsc-status-list--warning">
						<?php foreach ( $health_warnings as $msg ) : ?>
							<li><?php echo esc_html( $msg ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php elseif ( ! empty( $status_notes ) ) : ?>
					<ul class="wsc-status-list wsc-status-list--info">
						<?php foreach ( $status_notes as $note ) : ?>
							<li><?php echo esc_html( $note ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p class="wsc-muted">
						<?php esc_html_e( 'No issues detected.', 'ventraconnect-social-login' ); ?>
					</p>
				<?php endif; ?>

				<h3 class="wsc-health-section__title" style="margin-top: 24px;">
					<?php echo esc_html__( 'Auth stack', 'ventraconnect-social-login' ); ?>
				</h3>

				<div class="wsc-health-list">
					<!-- Social Login -->
					<div class="wsc-health-item">
						<span class="wsc-health-item__label">
							<?php echo esc_html__( 'Social Login', 'ventraconnect-social-login' ); ?>
						</span>
						<span class="wsc-health-item__value">
							<?php
							if ( ! empty( $providers_slugs ) ) {
								echo '<strong>' . esc_html( $providers_count ) . '</strong> <span style="color: #94a3b8;">(' . esc_html( implode( ', ', array_map( 'strval', $providers_slugs ) ) ) . ')</span>';
							} else {
								echo esc_html( $providers_count );
							}
							?>
						</span>
					</div>

					<!-- Passkey -->
					<div class="wsc-health-item">
						<span class="wsc-health-item__label">
							<?php echo esc_html__( 'Passkey', 'ventraconnect-social-login' ); ?>
						</span>
						<span class="wsc-health-item__value">
							<?php if ( $passkey_runtime_intentionally_unsupported ) : ?>
								<span class="wsc-pill wsc-pill-soft-warning wsc-pill-sm">
									<?php echo esc_html__( 'Unsupported', 'ventraconnect-social-login' ); ?>
								</span>
								<span class="wsc-muted"><?php echo esc_html__( 'Requires PHP 8.2+', 'ventraconnect-social-login' ); ?></span>
							<?php elseif ( $passkey_method_enabled && $passkey_supported_for_auth_stack ) : ?>
								<span class="wsc-pill wsc-pill-soft-success wsc-pill-sm">
									<?php echo esc_html__( 'Enabled', 'ventraconnect-social-login' ); ?>
								</span>
							<?php else : ?>
								<span class="wsc-pill wsc-pill-soft-error wsc-pill-sm">
									<?php echo esc_html__( 'Disabled', 'ventraconnect-social-login' ); ?>
								</span>
							<?php endif; ?>
						</span>
					</div>

					<!-- Magic Link (real status in Free + Pro) -->
					<div class="wsc-health-item">
						<span class="wsc-health-item__label">
							<?php echo esc_html__( 'Magic Link', 'ventraconnect-social-login' ); ?>
						</span>
						<span class="wsc-health-item__value">
							<?php if ( $ml_on ) : ?>
								<span class="wsc-pill wsc-pill-soft-success wsc-pill-sm">
									<?php echo esc_html__( 'Enabled', 'ventraconnect-social-login' ); ?>
								</span>
							<?php else : ?>
								<span class="wsc-pill wsc-pill-soft-error wsc-pill-sm">
									<?php echo esc_html__( 'Disabled', 'ventraconnect-social-login' ); ?>
								</span>
							<?php endif; ?>
						</span>
					</div>

					<!-- Email OTP (real status in Free + Pro) -->
					<div class="wsc-health-item">
						<span class="wsc-health-item__label">
							<?php echo esc_html__( 'Email OTP', 'ventraconnect-social-login' ); ?>
						</span>
						<span class="wsc-health-item__value">
							<?php if ( $otp_on ) : ?>
								<span class="wsc-pill wsc-pill-soft-success wsc-pill-sm">
									<?php echo esc_html__( 'Enabled', 'ventraconnect-social-login' ); ?>
								</span>
							<?php else : ?>
								<span class="wsc-pill wsc-pill-soft-error wsc-pill-sm">
									<?php echo esc_html__( 'Disabled', 'ventraconnect-social-login' ); ?>
								</span>
							<?php endif; ?>
						</span>
					</div>

					<!-- Password Phaseout (still Pro-only) -->
					<div class="wsc-health-item">
						<span class="wsc-health-item__label">
							<?php echo esc_html__( 'Password Phaseout', 'ventraconnect-social-login' ); ?>
						</span>
						<span class="wsc-health-item__value">
							<?php if ( $pro_active ) : ?>
								<?php echo esc_html( ucfirst( $passwordless_mode ) ); ?>
							<?php else : ?>
								<span class="wsc-pill wsc-pill-soft-info wsc-pill-sm">
									<?php esc_html_e( 'Pro', 'ventraconnect-social-login' ); ?>
								</span>
								<span class="wsc-muted"></span>
							<?php endif; ?>
						</span>
					</div>
				</div>

				<h3 class="wsc-health-section__title" style="margin-top: 24px;">
					<?php echo esc_html__( 'Integrations & debug', 'ventraconnect-social-login' ); ?>
				</h3>

				<div class="wsc-health-list">
					<!-- WooCommerce integration -->
					<div class="wsc-health-item">
						<span class="wsc-health-item__label">
							<?php echo esc_html__( 'WooCommerce integration', 'ventraconnect-social-login' ); ?>
						</span>
  						<span class="wsc-health-item__value">
							<?php
							$wc_plugin_active = class_exists( 'WooCommerce' ) || function_exists( 'WC' );
							$wc_vc_enabled    = false;
							$pro_active       = function_exists( 'vcsl_is_pro_active' ) && vcsl_is_pro_active();

							// If Pro is NOT active: always show Pro pill and stop.
							if ( ! $pro_active ) {
								?>
								<span class="wsc-pill wsc-pill-soft-info wsc-pill-sm">
									<?php esc_html_e( 'Pro', 'ventraconnect-social-login' ); ?>
								</span>
								<?php
							} else {
								// Pro active: keep full logic.
								if ( $wc_plugin_active ) {
									if ( function_exists( '\VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings' ) ) {
										$wc_settings = \VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_get_settings();
									} else {
										$wc_settings = (array) get_option( 'ventraconnect_sl_wc_settings', [] );
									}

									if ( ! empty( $wc_settings['enabled'] ) ) {
										$wc_vc_enabled = true;
									}
								}

								if ( ! $wc_plugin_active ) {
									echo esc_html__( 'WooCommerce not installed / inactive', 'ventraconnect-social-login' );
								} else {
									if ( $wc_vc_enabled ) {
										echo esc_html__( 'VentraConnect Woo integration: Enabled', 'ventraconnect-social-login' );
									} else {
										echo esc_html__( 'VentraConnect Woo integration: Disabled', 'ventraconnect-social-login' );
									}
								}
							}
							?>
  						</span>
					</div>

					<!-- Debug mode -->
					<div class="wsc-health-item">
						<span class="wsc-health-item__label">
							<?php echo esc_html__( 'Debug mode', 'ventraconnect-social-login' ); ?>
						</span>
						<span class="wsc-health-item__value">
							<?php if ( $debug_mode ) : ?>
								<span class="wsc-pill wsc-pill-soft-warning wsc-pill-sm">
									<?php echo esc_html__( 'On', 'ventraconnect-social-login' ); ?>
								</span>
							<?php else : ?>
								<span class="wsc-pill wsc-pill-outline wsc-pill-sm">
									<?php echo esc_html__( 'Off', 'ventraconnect-social-login' ); ?>
								</span>
							<?php endif; ?>
						</span>
					</div>

					<!-- Integration debug -->
					<div class="wsc-health-item">
						<span class="wsc-health-item__label">
							<?php echo esc_html__( 'Integration debug', 'ventraconnect-social-login' ); ?>
						</span>
						<span class="wsc-health-item__value">
							<?php if ( $integration_debug ) : ?>
								<span class="wsc-pill wsc-pill-soft-warning wsc-pill-sm">
									<?php echo esc_html__( 'On', 'ventraconnect-social-login' ); ?>
								</span>
							<?php else : ?>
								<span class="wsc-pill wsc-pill-outline wsc-pill-sm">
									<?php echo esc_html__( 'Off', 'ventraconnect-social-login' ); ?>
								</span>
							<?php endif; ?>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

				<div class="wsc-card" style="margin-top:20px;">
					<div class="wsc-section-header">
						<h2 class="wsc-section-header__title">
							<?php echo esc_html__( 'Passkeys & Passwordless Accounts', 'ventraconnect-social-login' ); ?>
						</h2>
						<p class="wsc-section-header__description">
							<?php echo esc_html__( 'Free passkey core status and passwordless account hardening readiness.', 'ventraconnect-social-login' ); ?>
						</p>
					</div>
					<div class="wsc-card__body">
						<div class="wsc-health-grid">
							<div class="wsc-health-section">
								<h3 class="wsc-health-section__title"><?php echo esc_html__( 'Passkeys', 'ventraconnect-social-login' ); ?></h3>
								<div class="wsc-health-list">
									<div class="wsc-health-item">
										<span class="wsc-health-item__label"><?php echo esc_html__( 'PHP support', 'ventraconnect-social-login' ); ?></span>
										<span class="wsc-health-item__value">
											<?php if ( $passkey_runtime_intentionally_unsupported ) : ?>
												<span class="wsc-pill wsc-pill-soft-info wsc-pill-sm"><?php echo esc_html__( 'Intentionally disabled', 'ventraconnect-social-login' ); ?></span>
											<?php elseif ( ! empty( $passkeys_diag['passkey_supported_helper_result'] ) || ! empty( $passkeys_diag['passkey_core_supported'] ) ) : ?>
												<span class="wsc-pill wsc-pill-soft-success wsc-pill-sm"><?php echo esc_html__( 'Supported', 'ventraconnect-social-login' ); ?></span>
											<?php else : ?>
												<span class="wsc-pill wsc-pill-soft-error wsc-pill-sm"><?php echo esc_html__( 'Not supported', 'ventraconnect-social-login' ); ?></span>
											<?php endif; ?>
										</span>
									</div>
									<div class="wsc-health-item">
										<span class="wsc-health-item__label"><?php echo esc_html__( 'Passkey method', 'ventraconnect-social-login' ); ?></span>
										<span class="wsc-health-item__value"><?php echo esc_html( $passkey_method_enabled ? __( 'Enabled', 'ventraconnect-social-login' ) : __( 'Disabled', 'ventraconnect-social-login' ) ); ?></span>
									</div>
									<div class="wsc-health-item">
										<span class="wsc-health-item__label"><?php echo esc_html__( 'Native login/register', 'ventraconnect-social-login' ); ?></span>
										<span class="wsc-health-item__value"><?php echo esc_html( $passkey_native_login_register_label ); ?></span>
									</div>
									<div class="wsc-health-item">
										<span class="wsc-health-item__label"><?php echo esc_html__( 'Database', 'ventraconnect-social-login' ); ?></span>
										<span class="wsc-health-item__value">
											<?php
											echo esc_html(
												sprintf(
													/* translators: 1: db version or not set, 2: table presence status. */
													__( 'Version %1$s, Tables %2$s', 'ventraconnect-social-login' ),
													'' !== $passkey_db_version ? $passkey_db_version : __( 'not set', 'ventraconnect-social-login' ),
													$passkey_tables_present ? __( 'present', 'ventraconnect-social-login' ) : __( 'missing or incomplete', 'ventraconnect-social-login' )
												)
											);
											?>
										</span>
									</div>
									<?php if ( $passkey_runtime_intentionally_unsupported ) : ?>
										<div class="wsc-health-item">
											<span class="wsc-health-item__label"><?php echo esc_html__( 'Runtime status', 'ventraconnect-social-login' ); ?></span>
											<span class="wsc-health-item__value"><?php echo esc_html__( 'Intentionally disabled on PHP below 8.2', 'ventraconnect-social-login' ); ?></span>
										</div>
									<?php endif; ?>
								</div>
							</div>
							<div class="wsc-health-section">
								<h3 class="wsc-health-section__title"><?php echo esc_html__( 'Passwordless accounts', 'ventraconnect-social-login' ); ?></h3>
								<div class="wsc-health-list">
									<div class="wsc-health-item">
										<span class="wsc-health-item__label"><?php echo esc_html__( 'Account hardening', 'ventraconnect-social-login' ); ?></span>
										<span class="wsc-health-item__value"><?php echo esc_html( $passwordless_account_hardening_ok ? __( 'Ready', 'ventraconnect-social-login' ) : __( 'Needs attention', 'ventraconnect-social-login' ) ); ?></span>
									</div>
									<div class="wsc-health-item">
										<span class="wsc-health-item__label"><?php echo esc_html__( 'Password reset tracking', 'ventraconnect-social-login' ); ?></span>
										<span class="wsc-health-item__value"><?php echo esc_html( $passwordless_password_reset_tracking_ok ? __( 'Tracked', 'ventraconnect-social-login' ) : __( 'Needs attention', 'ventraconnect-social-login' ) ); ?></span>
									</div>
									<div class="wsc-health-item">
										<span class="wsc-health-item__label"><?php echo esc_html__( 'Passwordless-created users', 'ventraconnect-social-login' ); ?></span>
										<span class="wsc-health-item__value"><?php echo esc_html( $passwordless_total_accounts ); ?></span>
									</div>
									<div class="wsc-health-item">
										<span class="wsc-health-item__label"><?php echo esc_html__( 'Without user-set password', 'ventraconnect-social-login' ); ?></span>
										<span class="wsc-health-item__value"><?php echo esc_html( $passwordless_without_user_password ); ?></span>
									</div>
								</div>
							</div>
						</div>
						<?php if ( ! empty( $passkey_check_keys ) ) : ?>
							<h3 class="wsc-health-section__title" style="margin-top:24px;"><?php echo esc_html__( 'Diagnostic checks', 'ventraconnect-social-login' ); ?></h3>
							<?php if ( ! empty( $passkey_diag_warnings ) ) : ?>
								<ul class="wsc-list wsc-list--compact">
									<?php foreach ( $passkey_diag_warnings as $warning ) : ?>
										<li class="wsc-list__item">
											<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
												<strong><?php echo esc_html( $warning['label'] ); ?></strong>
												<span class="wsc-pill wsc-pill-soft-warning wsc-pill-sm">
													<?php echo esc_html__( 'Attention', 'ventraconnect-social-login' ); ?>
												</span>
											</div>
											<?php if ( '' !== $warning['detail'] ) : ?>
												<div class="wsc-muted" style="margin-top:4px;"><?php echo esc_html( $warning['detail'] ); ?></div>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<p class="wsc-muted"><?php echo esc_html__( 'All passkey and passwordless account checks passed.', 'ventraconnect-social-login' ); ?></p>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>

				<div class="wsc-card" style="margin-top:20px;">
					<div class="wsc-section-header">
						<h2 class="wsc-section-header__title">
							<?php echo esc_html__( 'Tools & self-tests', 'ventraconnect-social-login' ); ?>
						</h2>
						<p class="wsc-section-header__description">
							<?php echo esc_html__( 'Run quick checks for external HTTP, options storage, and mail availability.', 'ventraconnect-social-login' ); ?>
						</p>
					</div>
					<div class="wsc-card__body">
						<p>
							<button type="button" class="button button-primary wsc-diag-run-site">
								<?php echo esc_html__( 'Run site diagnostics', 'ventraconnect-social-login' ); ?>
							</button>
						</p>
						<p class="description">
							<?php echo esc_html__( 'This runs non-destructive tests for external HTTP requests, options write/read, and wp_mail() availability.', 'ventraconnect-social-login' ); ?>
						</p>
						<div class="wsc-diag-results" aria-live="polite"></div>
						<p class="description" style="margin-top:16px;">
							<?php
							echo esc_html__( 'For per-provider checks (Google, Facebook, etc.), use the “Run Diagnostics” button in each provider’s Verification panel.', 'ventraconnect-social-login' );
							?>
						</p>

						
					</div>
				</div>

                

				<?php
				$pro_active_diag = function_exists( 'vcsl_is_pro_active' ) ? vcsl_is_pro_active() : false;

				// Build a fresh integrations snapshot for server-side render; falls back to the
				// earlier snapshot if Health is not available.
				$integrations_snapshot = [];
				if ( class_exists( '\VentraConnect\SocialLogin\Diagnostics\Health' ) ) {
					$live_snapshot = \VentraConnect\SocialLogin\Diagnostics\Health::get_snapshot();
					if ( isset( $live_snapshot['integrations'] ) && is_array( $live_snapshot['integrations'] ) ) {
						$integrations_snapshot = $live_snapshot['integrations'];
					}
				}
				$integrations_for_tables = ! empty( $integrations_snapshot ) && is_array( $integrations_snapshot ) ? $integrations_snapshot : $integrations;
				?>
				<div class="wsc-card" style="margin-top:20px;">
					<div class="wsc-section-header">
						<h2 class="wsc-section-header__title">
							<?php echo esc_html__( 'Integrations', 'ventraconnect-social-login' ); ?>
						</h2>
						<p class="wsc-section-header__description">
							<?php echo esc_html__( 'Detected integrations and versions for supported plugins.', 'ventraconnect-social-login' ); ?>
						</p>
					</div>
					<div class="wsc-card__body">
						<?php if ( ! $pro_active_diag ) : ?>
							<div class="wsc-card wsc-card--subtle" style="margin-top:0;">
								<div class="wsc-card__header" style="display:flex;align-items:center;justify-content:space-between;">
									<h3 class="wsc-card__title" style="margin:0;">
										<?php echo esc_html__( 'Integrations overview (Pro)', 'ventraconnect-social-login' ); ?>
									</h3>
									
								</div>
								<p class="wsc-card__description">
									<?php echo esc_html__( 'View integration status for community, membership, and LMS plugins, including versions and VentraConnect connectivity.', 'ventraconnect-social-login' ); ?>
								</p>
								<p>
								</p>
							</div>
						<?php else : ?>
							<div class="wsc-diag-integrations" aria-live="polite">
								<?php
								$rendered_any_group = false;
								$groups             = [
									'community_memberships' => esc_html__( 'Community & Memberships', 'ventraconnect-social-login' ),
									'courses_lms'           => esc_html__( 'Courses & LMS', 'ventraconnect-social-login' ),
								];

								foreach ( $groups as $group_key => $group_label ) {
									$group_data = isset( $integrations_for_tables[ $group_key ] ) && is_array( $integrations_for_tables[ $group_key ] )
										? $integrations_for_tables[ $group_key ]
										: [];

									$plugins = isset( $group_data['plugins'] ) && is_array( $group_data['plugins'] )
										? $group_data['plugins']
										: [];

									$summary = isset( $group_data['summary'] ) && is_array( $group_data['summary'] )
										? $group_data['summary']
										: [];

									if ( empty( $plugins ) && empty( $summary ) ) {
										continue;
									}

									$rendered_any_group = true;

									$installed_count = isset( $summary['installed_count'] ) ? (int) $summary['installed_count'] : 0;
									$active_count    = isset( $summary['active_count'] ) ? (int) $summary['active_count'] : 0;
									$vc_enabled_cnt  = isset( $summary['vc_enabled_count'] ) ? (int) $summary['vc_enabled_count'] : 0;
									?>
									<h3 class="wsc-diag-integrations__heading"><?php echo esc_html( $group_label ); ?></h3>
									<p class="description">
                                    <?php
                                    echo esc_html(
                                        sprintf(
                                            /* translators: 1: Installed count, 2: Active count, 3: VC enabled count. */
                                            __( 'Installed: %1$d • Active: %2$d • VC enabled: %3$d', 'ventraconnect-social-login' ),
                                            (int) $installed_count,
                                            (int) $active_count,
                                            (int) $vc_enabled_cnt
                                        )
                                    );
                                    ?>
									</p>
									<?php if ( empty( $plugins ) ) : ?>
										<p class="description">
											<?php esc_html_e( 'No supported integrations detected for this group.', 'ventraconnect-social-login' ); ?>
										</p>
									<?php else : ?>
										<table class="widefat striped wsc-diag-integrations-table">
											<thead>
												<tr>
													<th><?php esc_html_e( 'Integration', 'ventraconnect-social-login' ); ?></th>
													<th><?php esc_html_e( 'Version', 'ventraconnect-social-login' ); ?></th>
													<th><?php esc_html_e( 'Installed', 'ventraconnect-social-login' ); ?></th>
													<th><?php esc_html_e( 'Active', 'ventraconnect-social-login' ); ?></th>
													<th><?php esc_html_e( 'VC Integration', 'ventraconnect-social-login' ); ?></th>
													<th><?php esc_html_e( 'Passkeys', 'ventraconnect-social-login' ); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $plugins as $slug => $plugin ) : ?>
													<?php
													$plugin             = is_array( $plugin ) ? $plugin : [];
													$label              = isset( $plugin['label'] ) ? (string) $plugin['label'] : (string) $slug;
													$installed          = ! empty( $plugin['installed'] );
													$active             = ! empty( $plugin['plugin_active'] );
													$vc_enabled         = ! empty( $plugin['vc_enabled'] );
													$version_raw        = isset( $plugin['version'] ) ? (string) $plugin['version'] : '';
													$version_display    = '' !== $version_raw ? $version_raw : __( 'unknown', 'ventraconnect-social-login' );
													$vc_integration_txt = ( $active && $vc_enabled ) ? __( 'On', 'ventraconnect-social-login' ) : __( 'Off', 'ventraconnect-social-login' );
													$passkeys_supported = in_array( (string) $slug, $passkeys_supported_integrations, true );
													$passkeys_status    = __( 'Not supported', 'ventraconnect-social-login' );
													if ( $passkeys_supported ) {
														if ( ! $installed ) {
															$passkeys_status = __( 'Not installed', 'ventraconnect-social-login' );
														} elseif ( ! $active ) {
															$passkeys_status = __( 'Integration inactive', 'ventraconnect-social-login' );
														} elseif ( ! $env_advanced_passkeys_available ) {
															$passkeys_status = $env_pro_active
																? __( 'Free core unavailable', 'ventraconnect-social-login' )
																: __( 'Available in Pro', 'ventraconnect-social-login' );
														} else {
															$passkeys_status = __( 'Supported', 'ventraconnect-social-login' );
														}
													}
													?>
													<tr>
														<td><?php echo esc_html( $label ); ?></td>
														<td><?php echo esc_html( $version_display ); ?></td>
														<td><?php echo esc_html( $installed ? __( 'Yes', 'ventraconnect-social-login' ) : __( 'No', 'ventraconnect-social-login' ) ); ?></td>
														<td><?php echo esc_html( $active ? __( 'Yes', 'ventraconnect-social-login' ) : __( 'No', 'ventraconnect-social-login' ) ); ?></td>
														<td><?php echo esc_html( $vc_integration_txt ); ?></td>
														<td><?php echo esc_html( $passkeys_status ); ?></td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									<?php endif; ?>
								<?php } ?>
								<?php if ( ! $rendered_any_group ) : ?>
									<p class="description">
										<?php esc_html_e( 'No supported integrations detected (or Pro is not active).', 'ventraconnect-social-login' ); ?>
									</p>
								<?php endif; ?>
							</div>
							<p class="description" style="margin-top:8px;">
								<?php echo esc_html__( 'Run site diagnostics to refresh integration status.', 'ventraconnect-social-login' ); ?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</div>


			<div class="wsc-layout__secondary">
				<div class="wsc-card">
					<div class="wsc-section-header">
						<h2 class="wsc-section-header__title">
							<?php echo esc_html__( 'Logs & export', 'ventraconnect-social-login' ); ?>
						</h2>
						<p class="wsc-section-header__description">
							<?php echo esc_html__( 'Preview recent diagnostic events and copy a support-ready diagnostics blob.', 'ventraconnect-social-login' ); ?>
						</p>
					</div>
					<div class="wsc-card__body">
						<h3 class="wsc-muted"><?php echo esc_html__( 'Recent events', 'ventraconnect-social-login' ); ?></h3>
						<div class="wsc-diag-events">
						<?php if ( ! empty( $events ) ) : ?>
							<ul class="wsc-list wsc-list--compact">
								<?php
								$shown = 0;
								foreach ( $events as $event ) :
									if ( ! is_array( $event ) ) {
										continue;
									}
									$timestamp = isset( $event['timestamp'] ) ? (string) $event['timestamp'] : ( ( isset( $event['time'] ) ? (string) $event['time'] : '' ) );
									$context   = isset( $event['context'] ) ? (string) $event['context'] : ( ( isset( $event['provider'] ) ? (string) $event['provider'] : '' ) );
									$message   = isset( $event['message'] ) ? (string) $event['message'] : ( ( isset( $event['detail'] ) ? (string) $event['detail'] : '' ) );
									?>
									<li class="wsc-list__item">
										<div class="wsc-muted" style="font-size:11px;">
											<?php echo esc_html( $timestamp ); ?>
											<?php if ( $context ) : ?>
												&mdash; <?php echo esc_html( $context ); ?>
											<?php endif; ?>
										</div>
										<div>
											<?php echo esc_html( $message ); ?>
										</div>
									</li>
									<?php
									$shown++;
									if ( $shown >= 5 ) {
										break;
									}
								endforeach;
								?>
							</ul>
						<?php else : ?>
							<p class="description">
								<?php echo esc_html__( 'No diagnostic events are available yet. Detailed logs may be provided by the Pro add-on.', 'ventraconnect-social-login' ); ?>
							</p>
						<?php endif; ?>
						</div>

						<h3 class="wsc-muted" style="margin-top:16px;"><?php echo esc_html__( 'Copy diagnostics for support', 'ventraconnect-social-login' ); ?></h3>
						<p class="description">
							<?php echo esc_html__( 'Copy this diagnostics snapshot into your support ticket to help us troubleshoot faster.', 'ventraconnect-social-login' ); ?>
						</p>
						<textarea class="wsc-diag-support-blob" readonly rows="10" style="width:100%;max-width:100%;"><?php echo esc_textarea( $support_blob ); ?></textarea>
						<p style="margin-top:8px;">
							<button type="button" class="button wsc-diag-copy-support">
								<?php echo esc_html__( 'Copy diagnostics', 'ventraconnect-social-login' ); ?>
							</button>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	public function renderProviderEmbed( array $state, callable $isPro ): void {
    $preview_only = ! empty( $state['preview_only'] );
    $slug         = isset( $state['slug'] ) ? (string) $state['slug'] : '';

    if ( '' === $slug ) {
        return;
    }

    if ( $preview_only ) {
        ob_start();
    }

    $settings          = (array) ( $state['settings'] ?? [] );
    $creds             = (array) ( $state['creds'] ?? [] );
    $label             = isset( $state['label'] ) ? (string) $state['label'] : ucfirst( $slug );
    $redirect          = isset( $state['redirect'] ) ? (string) $state['redirect'] : '';
    $rid               = isset( $state['redirect_id'] ) ? (string) $state['redirect_id'] : ( 'wsc-redirect-' . sanitize_html_class( $slug ) );
    $enabled_providers = (array) ( $state['enabled'] ?? ( $settings['providers'] ?? [] ) );
    $admin_email       = isset( $state['admin_email'] ) ? (string) $state['admin_email'] : '';
    $test_url          = isset( $state['verify_url'] ) ? (string) $state['verify_url'] : '';
    $diag_nonce        = isset( $state['diag_nonce'] ) ? (string) $state['diag_nonce'] : '';
    $diag_url          = isset( $state['diag_url'] ) ? (string) $state['diag_url'] : '';
    $upsell_copy       = (array) ( $state['upsell_copy'] ?? [] );
    $pro               = (bool) call_user_func( $isPro );
    $diag_url_attr     = esc_url( $diag_url );
    $is_passwordless   = in_array( $slug, [ 'magic_link', 'otp_email', 'passkey' ], true );

    if ( ! $preview_only ) {
        $pro_class = $pro ? 'vcsl-pro-active' : 'vcsl-free';
        echo '<script>document.documentElement.classList.add(' . wp_json_encode( $pro_class ) . ');</script>';
    }

    // Passwordless methods keep their dedicated config card instead of OAuth credentials UI.
    if ( in_array( $slug, [ 'magic_link', 'otp_email', 'passkey' ], true ) ) {
        $disabled              = '';
        $settings_all          = $settings;
        $conf                  = (array) ( $settings_all[ $slug ] ?? [] );
        $php_supports_passkeys = function_exists( 'ventraconnect_sl_is_passkey_supported' )
            ? ventraconnect_sl_is_passkey_supported()
            : ( defined( 'VENTRACONNECT_PASSKEYS_CORE_SUPPORTED' ) && VENTRACONNECT_PASSKEYS_CORE_SUPPORTED );

        echo '<div class="wsc-card" style="margin-top:12px;">';
        echo '<h3>' . esc_html__( 'Settings', 'ventraconnect-social-login' ) . '</h3>';
        echo '<table class="form-table" role="presentation">';

        // Active toggle
        $is_checked = in_array( $slug, $enabled_providers, true );
        echo '<tr><th scope="row">' . esc_html__( 'Active', 'ventraconnect-social-login' ) . '</th><td><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_settings[providers][]" value="' . esc_attr( $slug ) . '" ' . checked( $is_checked, true, false ) . ( ( 'passkey' === $slug && ! $php_supports_passkeys ) ? ' disabled' : '' ) . '><span class="wsc-switch-ui" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Enable provider', 'ventraconnect-social-login' ) . '</span></label></td></tr>';

        if ( 'passkey' === $slug ) {
            $disabled = '';
            $redirect_options = [
                'same_page' => __( 'Return to requested page (default)', 'ventraconnect-social-login' ),
                'referer'   => __( 'Go back to previous page (referer)', 'ventraconnect-social-login' ),
                'home'      => __( 'Send to homepage', 'ventraconnect-social-login' ),
                'custom'    => __( 'Send to custom URL', 'ventraconnect-social-login' ),
            ];

            $passkey_override = ! empty( $conf['redirect_override'] );
            $passkey_mode     = isset( $conf['redirect_mode'] ) ? (string) $conf['redirect_mode'] : 'same_page';
            if ( ! in_array( $passkey_mode, [ 'same_page', 'referer', 'home', 'custom' ], true ) ) {
                $passkey_mode = 'same_page';
            }
            $passkey_url = (string) ( $conf['redirect_url'] ?? '' );
            $passkey_or  = isset( $conf['or_separator'] ) ? (string) $conf['or_separator'] : 'none';
            if ( ! in_array( $passkey_or, [ 'none', 'above', 'below', 'both' ], true ) ) {
                $passkey_or = 'none';
            }
            $passkey_show_helper_text    = isset( $conf['show_helper_text'] ) ? ! empty( $conf['show_helper_text'] ) : true;
            $passkey_login_helper_text   = isset( $conf['login_helper_text'] ) ? (string) $conf['login_helper_text'] : '';
            $passkey_register_helper_text = isset( $conf['register_helper_text'] ) ? (string) $conf['register_helper_text'] : '';
            $floating_panel_enabled      = isset( $conf['floating_panel_enabled'] ) ? ! empty( $conf['floating_panel_enabled'] ) : false;
            $floating_panel_pages        = isset( $conf['floating_panel_pages'] ) && is_array( $conf['floating_panel_pages'] ) ? array_values( array_filter( array_map( 'absint', $conf['floating_panel_pages'] ) ) ) : array();
            $floating_panel_title        = isset( $conf['floating_panel_title'] ) ? (string) $conf['floating_panel_title'] : '';
            $floating_panel_message      = isset( $conf['floating_panel_message'] ) ? (string) $conf['floating_panel_message'] : '';
            $floating_panel_button_text  = isset( $conf['floating_panel_button_text'] ) ? (string) $conf['floating_panel_button_text'] : '';
            $floating_panel_position     = isset( $conf['floating_panel_position'] ) ? (string) $conf['floating_panel_position'] : 'bottom_right';
            $floating_panel_delay        = isset( $conf['floating_panel_delay'] ) ? absint( $conf['floating_panel_delay'] ) : 3;
            $floating_panel_snooze_raw   = $conf['floating_panel_snooze_days'] ?? null;
            $floating_panel_delay        = min( 30, max( 0, $floating_panel_delay ) );
            if ( is_scalar( $floating_panel_snooze_raw ) && '' !== trim( (string) $floating_panel_snooze_raw ) && is_numeric( $floating_panel_snooze_raw ) ) {
                $floating_panel_snooze_days = (int) $floating_panel_snooze_raw;
                $floating_panel_snooze_days = min( 365, max( 1, $floating_panel_snooze_days ) );
            } else {
                $floating_panel_snooze_days = 7;
            }
            if ( ! in_array( $floating_panel_position, array( 'bottom_right', 'bottom_left' ), true ) ) {
                $floating_panel_position = 'bottom_right';
            }
            $advanced_passkeys_available = $php_supports_passkeys
                && function_exists( 'vcsl_is_pro_active' )
                && vcsl_is_pro_active()
                && (
                    defined( 'VENTRACONNECT_PRO_PASSKEYS_VERSION' )
                    || class_exists( '\VentraConnect\SocialLogin\Pro\Passkeys\Loader', false )
                );
            $pro_includes_passkeys = function_exists( 'vcsl_is_pro_active' )
                && vcsl_is_pro_active();
            $floating_panel_preview = $this->renderPasskeyFloatingPanelPreview(
                array(
                    'title'       => $floating_panel_title,
                    'message'     => $floating_panel_message,
                    'button_text' => $floating_panel_button_text,
                    'position'    => $floating_panel_position,
                    'locked'      => ! $advanced_passkeys_available,
                )
            );
            $published_pages = get_pages(
                array(
                    'sort_column' => 'post_title',
                    'sort_order'  => 'ASC',
                    'post_status' => 'publish',
                )
            );
            $page_picker_pages = array();
            foreach ( $published_pages as $published_page ) {
                $page_id    = absint( $published_page->ID );
                $page_title = get_the_title( $published_page );
                $page_slug  = isset( $published_page->post_name ) ? (string) $published_page->post_name : '';

                $page_picker_pages[] = array(
                    'id'    => $page_id,
                    'title' => $page_title,
                    'slug'  => $page_slug,
                );
            }

            if ( ! $php_supports_passkeys ) {
                echo '<tr><th scope="row">' . esc_html__( 'Passkey status', 'ventraconnect-social-login' ) . '</th><td><div class="notice notice-warning inline"><p>' . esc_html__( 'Passkeys require PHP 8.2 or higher. Update PHP to enable passkey login and registration.', 'ventraconnect-social-login' ) . '</p></div></td></tr>';
                $disabled = 'disabled';
            } else {
                echo '<tr><th scope="row">' . esc_html__( 'Passkey status', 'ventraconnect-social-login' ) . '</th><td><div class="notice notice-info inline"><p>' . esc_html__( 'Passkeys are available for native WordPress login and registration screens.', 'ventraconnect-social-login' ) . '</p></div></td></tr>';
            }

            echo '<tr><th scope="row">' . esc_html__( 'Recommended fallback methods', 'ventraconnect-social-login' ) . '</th><td><div class="notice notice-info inline"><p><strong>' . esc_html__( 'Recommended fallback methods', 'ventraconnect-social-login' ) . '</strong></p><p>' . esc_html__( 'For the best login experience across all devices, enable Email OTP or Magic Link alongside Passkeys.', 'ventraconnect-social-login' ) . '</p><p>' . esc_html__( 'Passkeys provide the most secure and seamless sign-in on supported devices, while Email OTP and Magic Link act as reliable fallback methods for users on older devices, restricted browsers, or situations where their passkey is not available.', 'ventraconnect-social-login' ) . '</p><p>' . esc_html__( 'This combination helps ensure users can still access their account even when passkey sign-in is not possible.', 'ventraconnect-social-login' ) . '</p></div></td></tr>';

            echo '<tr><th scope="row">' . esc_html__( 'Native WordPress behavior', 'ventraconnect-social-login' ) . '</th><td><p class="description" style="margin:0;">' . esc_html__( 'Passkey registration is available on the WordPress registration page. The login page signs in users who already have a passkey.', 'ventraconnect-social-login' ) . '</p></td></tr>';

            echo '<tr><th scope="row">' . esc_html__( 'OR separator', 'ventraconnect-social-login' ) . '</th><td><p class="description" style="margin:0 0 8px;">' . esc_html__( 'Choose whether to show an “OR” separator around the Passkey option on supported WordPress login and registration screens.', 'ventraconnect-social-login' ) . '</p>';
            echo '<label for="ventraconnect-sl-passkey-or-separator" class="screen-reader-text">' . esc_html__( 'OR separator', 'ventraconnect-social-login' ) . '</label>';
            echo '<select id="ventraconnect-sl-passkey-or-separator" name="ventraconnect_sl_settings[passkey][or_separator]" class="wsc-select" ' . ( ! empty( $disabled ) ? 'disabled' : '' ) . '>';
            echo '<option value="none"' . selected( $passkey_or, 'none', false ) . '>' . esc_html__( 'None', 'ventraconnect-social-login' ) . '</option>';
            echo '<option value="above"' . selected( $passkey_or, 'above', false ) . '>' . esc_html__( 'Above button', 'ventraconnect-social-login' ) . '</option>';
            echo '<option value="below"' . selected( $passkey_or, 'below', false ) . '>' . esc_html__( 'Below button', 'ventraconnect-social-login' ) . '</option>';
            echo '<option value="both"' . selected( $passkey_or, 'both', false ) . '>' . esc_html__( 'Above and below', 'ventraconnect-social-login' ) . '</option>';
            echo '</select>';
            echo '</td></tr>';

            ob_start();
            ?>
            <tr>
                <th scope="row"><?php echo esc_html__( 'Helper messages', 'ventraconnect-social-login' ); ?></th>
                <td>
                    <div class="description wsc-small" style="margin-bottom:8px;"><?php echo esc_html__( 'Display a short note below Passkey buttons to help users understand what Passkeys do.', 'ventraconnect-social-login' ); ?></div>
                    <label class="wsc-switch" style="margin-bottom:8px;">
                        <input type="hidden" name="ventraconnect_sl_settings[passkey][show_helper_text]" value="0">
                        <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_settings[passkey][show_helper_text]" value="1" <?php checked( $passkey_show_helper_text ); ?><?php echo $disabled ? ' disabled' : ''; ?>>
                        <span class="wsc-switch-ui" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php echo esc_html__( 'Show helper text below Passkey buttons.', 'ventraconnect-social-login' ); ?></span>
                    </label>
                    <div class="description wsc-small" style="margin-bottom:12px;"><?php echo esc_html__( 'Show helper text below Passkey buttons.', 'ventraconnect-social-login' ); ?></div>
                    <div style="margin-bottom:12px; max-width:560px;">
                        <label for="ventraconnect-sl-passkey-login-helper" style="display:block; font-weight:600; margin-bottom:4px;"><?php echo esc_html__( 'Login helper text', 'ventraconnect-social-login' ); ?></label>
                        <input
                            id="ventraconnect-sl-passkey-login-helper"
                            type="text"
                            class="wsc-admin wsc-input regular-text"
                            name="ventraconnect_sl_settings[passkey][login_helper_text]"
                            value="<?php echo esc_attr( $passkey_login_helper_text ); ?>"
                            placeholder="<?php echo esc_attr__( 'Use a saved passkey from this device to sign in without a password.', 'ventraconnect-social-login' ); ?>"
                            <?php echo $disabled ? 'disabled' : ''; ?>
                        >
                    </div>
                    <div style="max-width:560px;">
                        <label for="ventraconnect-sl-passkey-register-helper" style="display:block; font-weight:600; margin-bottom:4px;"><?php echo esc_html__( 'Register helper text', 'ventraconnect-social-login' ); ?></label>
                        <input
                            id="ventraconnect-sl-passkey-register-helper"
                            type="text"
                            class="wsc-admin wsc-input regular-text"
                            name="ventraconnect_sl_settings[passkey][register_helper_text]"
                            value="<?php echo esc_attr( $passkey_register_helper_text ); ?>"
                            placeholder="<?php echo esc_attr__( 'Create a passkey for this account after verifying your email address.', 'ventraconnect-social-login' ); ?>"
                            <?php echo $disabled ? 'disabled' : ''; ?>
                        >
                    </div>
                </td>
            </tr>
            <?php
            $allowed_helper_html = [
                'tr'    => [ 'class' => true, 'style' => true ],
                'th'    => [ 'scope' => true, 'class' => true, 'style' => true ],
                'td'    => [ 'class' => true, 'style' => true, 'colspan' => true ],
                'div'   => [ 'class' => true, 'style' => true ],
                'label' => [ 'for' => true, 'class' => true, 'style' => true ],
                'input' => [ 'type' => true, 'name' => true, 'value' => true, 'class' => true, 'id' => true, 'checked' => true, 'disabled' => true, 'placeholder' => true ],
                'span'  => [ 'class' => true, 'aria-hidden' => true ],
            ];
            echo wp_kses( (string) ob_get_clean(), $allowed_helper_html );

            ob_start();
            ?>
            <tr>
                <th scope="row"><?php echo esc_html__( 'Redirect behavior', 'ventraconnect-social-login' ); ?></th>
                <td>
                    <div class="description wsc-small" style="margin-bottom:8px;"><?php echo esc_html__( 'By default, passkeys follow your global redirect settings. Enable override to customize where users go after passkey login or registration.', 'ventraconnect-social-login' ); ?></div>
                    <label class="wsc-switch" style="margin-bottom:8px;">
                        <input type="hidden" name="ventraconnect_sl_settings[passkey][redirect_override]" value="0">
                        <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_settings[passkey][redirect_override]" value="1" <?php checked( $passkey_override ); ?><?php echo $disabled ? ' disabled' : ''; ?>>
                        <span class="wsc-switch-ui" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php echo esc_html__( 'Enable redirect override', 'ventraconnect-social-login' ); ?></span>
                    </label>
                    <div class="description wsc-small" style="margin-bottom:8px;"><?php echo esc_html__( 'Enable override to use the redirect options below for passkey login and registration.', 'ventraconnect-social-login' ); ?></div>
                    <fieldset style="margin:0 0 8px 0;">
                        <?php foreach ( $redirect_options as $mode_val => $mode_label ) : ?>
                            <label class="wsc-admin wsc-radio-simple" style="display:block;margin:4px 0;">
                                <input type="radio" name="ventraconnect_sl_settings[passkey][redirect_mode]" value="<?php echo esc_attr( $mode_val ); ?>" <?php checked( $passkey_mode, $mode_val ); ?><?php echo $disabled ? ' disabled' : ''; ?>>
                                <?php echo esc_html( $mode_label ); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <div style="margin-top:6px; width:500px;">
                        <label><?php echo esc_html__( 'Custom URL', 'ventraconnect-social-login' ); ?>
                            <input type="url" class="wsc-admin wsc-input" name="ventraconnect_sl_settings[passkey][redirect_url]" value="<?php echo esc_attr( $passkey_url ); ?>" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" <?php echo $disabled ? 'disabled' : ''; ?>>
                        </label>
                        <p class="description wsc-small" style="margin-top:4px;"><?php echo esc_html__( 'Only used when "Send to custom URL" is selected.', 'ventraconnect-social-login' ); ?></p>
                    </div>
                </td>
            </tr>
            <?php
            $allowed_admin_html = [
                'tr'       => [ 'class' => true, 'style' => true ],
                'th'       => [ 'scope' => true, 'class' => true, 'style' => true ],
                'td'       => [ 'class' => true, 'style' => true, 'colspan' => true ],
                'label'    => [ 'for' => true, 'class' => true, 'style' => true ],
                'input'    => [ 'type' => true, 'name' => true, 'value' => true, 'class' => true, 'id' => true, 'checked' => true, 'disabled' => true, 'placeholder' => true, 'min' => true, 'max' => true ],
                'span'     => [ 'class' => true, 'aria-hidden' => true ],
                'div'      => [ 'class' => true, 'style' => true ],
                'fieldset' => [ 'class' => true, 'style' => true ],
                'p'        => [ 'class' => true, 'style' => true ],
            ];
            echo wp_kses( (string) ob_get_clean(), $allowed_admin_html );

            if ( $advanced_passkeys_available ) {
                echo '<tr><th scope="row">' . esc_html__( 'Advanced integrations', 'ventraconnect-social-login' ) . '</th><td><div class="notice notice-success inline"><p>' . esc_html__( 'Pro includes Passkeys. Advanced passkey integrations are available for supported WooCommerce, LMS, membership, and community flows.', 'ventraconnect-social-login' ) . '</p></div></td></tr>';
            } else {
                $advanced_integrations_copy = $pro_includes_passkeys
                    ? __( 'Advanced passkey integrations are included in Pro. Update PHP to 8.2 or higher to enable them.', 'ventraconnect-social-login' )
                    : __( 'Available in Pro. Advanced passkey integrations are included in Pro for supported WooCommerce, LMS, membership, and community flows.', 'ventraconnect-social-login' );
                echo '<tr><th scope="row">' . esc_html__( 'Advanced integrations', 'ventraconnect-social-login' ) . '</th><td><div class="notice notice-info inline"><p>' . esc_html( $advanced_integrations_copy ) . '</p></div></td></tr>';
            }

            if ( $advanced_passkeys_available ) {
                ob_start();
                ?>
                <tr>
                    <th scope="row"><?php echo esc_html__( 'Floating Passkey Setup Panel', 'ventraconnect-social-login' ); ?></th>
                    <td>
                        <div class="description wsc-small" style="margin-bottom:8px;"><?php echo esc_html__( 'Prompt logged-in users to add a passkey on selected pages such as My Account, Dashboard, Membership Account, or Thank You pages.', 'ventraconnect-social-login' ); ?></div>
                        <label class="wsc-switch" style="margin-bottom:8px;">
                            <input type="hidden" name="ventraconnect_sl_settings[passkey][floating_panel_enabled]" value="0">
                            <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_settings[passkey][floating_panel_enabled]" value="1" <?php checked( $floating_panel_enabled ); ?><?php echo $disabled ? ' disabled' : ''; ?>>
                            <span class="wsc-switch-ui" aria-hidden="true"></span>
                            <span class="screen-reader-text"><?php echo esc_html__( 'Enable floating setup panel', 'ventraconnect-social-login' ); ?></span>
                        </label>
                        <div class="description wsc-small" style="margin-bottom:12px;"><?php echo esc_html__( 'Show this setup panel to logged-in users who do not have a passkey yet. If they click “Not now”, the panel is hidden for the snooze period below.', 'ventraconnect-social-login' ); ?></div>
                        <div style="margin-bottom:12px; max-width:560px;">
                            <label for="ventraconnect-sl-passkey-floating-pages-search" style="display:block; font-weight:600; margin-bottom:4px;"><?php echo esc_html__( 'Display on pages', 'ventraconnect-social-login' ); ?></label>
                            <div class="vcs-page-picker" data-vcs-page-picker id="vcs-passkey-floating-page-picker" data-field-name="ventraconnect_sl_settings[passkey][floating_panel_pages][]">
                                <input
                                    id="ventraconnect-sl-passkey-floating-pages-search"
                                    type="search"
                                    class="wsc-admin wsc-input vcs-page-picker__search"
                                    data-vcs-page-picker-search="1"
                                    data-vcs-page-picker-target="vcs-passkey-floating-page-picker"
                                    placeholder="<?php echo esc_attr__( 'Search pages...', 'ventraconnect-social-login' ); ?>"
                                    aria-label="<?php echo esc_attr__( 'Search pages', 'ventraconnect-social-login' ); ?>"
                                    <?php echo $disabled ? 'disabled' : ''; ?>
                                >
                                <script type="application/json" class="vcs-page-picker__data" data-vcs-page-picker-data><?php echo wp_json_encode( $page_picker_pages ); ?></script>
                                <div class="vcs-page-picker__section">
                                    <div class="vcs-page-picker__section-title"><?php echo esc_html__( 'Selected pages', 'ventraconnect-social-login' ); ?></div>
                                    <div class="vcs-page-picker__selected" data-vcs-page-picker-selected>
                                        <?php foreach ( $page_picker_pages as $page_picker_page ) : ?>
                                            <?php if ( ! in_array( (int) $page_picker_page['id'], $floating_panel_pages, true ) ) : ?>
                                                <?php continue; ?>
                                            <?php endif; ?>
                                            <?php
                                            $page_picker_id_label = sprintf(
                                                /* translators: %d: WordPress page ID. */
                                                __( 'ID: %d', 'ventraconnect-social-login' ),
                                                (int) $page_picker_page['id']
                                            );
                                            ?>
                                            <div class="vcs-page-picker__selected-item" data-vcs-page-picker-selected-item data-page-id="<?php echo esc_attr( (string) $page_picker_page['id'] ); ?>">
                                                <div class="vcs-page-picker__selected-content">
                                                    <span class="vcs-page-picker__title"><?php echo esc_html( $page_picker_page['title'] ); ?></span>
                                                    <span class="vcs-page-picker__meta"><?php echo esc_html( $page_picker_id_label ); ?></span>
                                                </div>
                                                <button type="button" class="button button-secondary button-small vcs-page-picker__action" data-vcs-page-picker-remove data-page-id="<?php echo esc_attr( (string) $page_picker_page['id'] ); ?>" <?php echo $disabled ? 'disabled' : ''; ?>><?php echo esc_html__( 'Remove', 'ventraconnect-social-login' ); ?></button>
                                                <input type="hidden" name="ventraconnect_sl_settings[passkey][floating_panel_pages][]" value="<?php echo esc_attr( (string) $page_picker_page['id'] ); ?>" data-vcs-page-picker-hidden-input>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description wsc-small vcs-page-picker__empty" data-vcs-page-picker-empty-selected><?php echo esc_html__( 'No pages selected. The floating panel will not appear until you select at least one page.', 'ventraconnect-social-login' ); ?></p>
                                </div>
                                <div class="vcs-page-picker__section">
                                    <div class="vcs-page-picker__section-title"><?php echo esc_html__( 'Search results', 'ventraconnect-social-login' ); ?></div>
                                    <div class="vcs-page-picker__results" data-vcs-page-picker-results></div>
                                    <p class="description wsc-small vcs-page-picker__empty" data-vcs-page-picker-empty-results hidden><?php echo esc_html__( 'No matching pages found.', 'ventraconnect-social-login' ); ?></p>
                                </div>
                            </div>
                            <p class="description wsc-small" style="margin-top:4px;"><?php echo esc_html__( 'If no pages are selected, the floating panel will not appear.', 'ventraconnect-social-login' ); ?></p>
                        </div>
                        <div style="margin-bottom:12px; max-width:560px;">
                            <label for="ventraconnect-sl-passkey-floating-title" style="display:block; font-weight:600; margin-bottom:4px;"><?php echo esc_html__( 'Panel title', 'ventraconnect-social-login' ); ?></label>
                            <input
                                id="ventraconnect-sl-passkey-floating-title"
                                type="text"
                                class="wsc-admin wsc-input regular-text"
                                name="ventraconnect_sl_settings[passkey][floating_panel_title]"
                                value="<?php echo esc_attr( $floating_panel_title ); ?>"
                                placeholder="<?php echo esc_attr__( 'Secure your account with a Passkey', 'ventraconnect-social-login' ); ?>"
                                <?php echo $disabled ? 'disabled' : ''; ?>
                            >
                        </div>
                        <div style="margin-bottom:12px; max-width:560px;">
                            <label for="ventraconnect-sl-passkey-floating-message" style="display:block; font-weight:600; margin-bottom:4px;"><?php echo esc_html__( 'Panel message', 'ventraconnect-social-login' ); ?></label>
                            <input
                                id="ventraconnect-sl-passkey-floating-message"
                                type="text"
                                class="wsc-admin wsc-input regular-text"
                                name="ventraconnect_sl_settings[passkey][floating_panel_message]"
                                value="<?php echo esc_attr( $floating_panel_message ); ?>"
                                placeholder="<?php echo esc_attr__( 'Add a passkey so you can sign in next time with your fingerprint, Face ID, or security key.', 'ventraconnect-social-login' ); ?>"
                                <?php echo $disabled ? 'disabled' : ''; ?>
                            >
                        </div>
                        <div style="margin-bottom:12px; max-width:560px;">
                            <label for="ventraconnect-sl-passkey-floating-button-text" style="display:block; font-weight:600; margin-bottom:4px;"><?php echo esc_html__( 'Button text', 'ventraconnect-social-login' ); ?></label>
                            <input
                                id="ventraconnect-sl-passkey-floating-button-text"
                                type="text"
                                class="wsc-admin wsc-input regular-text"
                                name="ventraconnect_sl_settings[passkey][floating_panel_button_text]"
                                value="<?php echo esc_attr( $floating_panel_button_text ); ?>"
                                placeholder="<?php echo esc_attr__( 'Add Passkey', 'ventraconnect-social-login' ); ?>"
                                <?php echo $disabled ? 'disabled' : ''; ?>
                            >
                        </div>
                        <div style="margin-bottom:12px; max-width:560px;">
                            <label for="ventraconnect-sl-passkey-floating-position" style="display:block; font-weight:600; margin-bottom:4px;"><?php echo esc_html__( 'Position', 'ventraconnect-social-login' ); ?></label>
                            <select id="ventraconnect-sl-passkey-floating-position" class="wsc-select" name="ventraconnect_sl_settings[passkey][floating_panel_position]" <?php echo $disabled ? 'disabled' : ''; ?>>
                                <option value="bottom_right"<?php selected( $floating_panel_position, 'bottom_right' ); ?>><?php echo esc_html__( 'Bottom right', 'ventraconnect-social-login' ); ?></option>
                                <option value="bottom_left"<?php selected( $floating_panel_position, 'bottom_left' ); ?>><?php echo esc_html__( 'Bottom left', 'ventraconnect-social-login' ); ?></option>
                            </select>
                        </div>
                        <div style="max-width:240px;">
                            <label for="ventraconnect-sl-passkey-floating-delay" style="display:block; font-weight:600; margin-bottom:4px;"><?php echo esc_html__( 'Display delay (seconds)', 'ventraconnect-social-login' ); ?></label>
                            <input
                                id="ventraconnect-sl-passkey-floating-delay"
                                type="number"
                                min="0"
                                max="30"
                                class="wsc-admin wsc-input small-text"
                                name="ventraconnect_sl_settings[passkey][floating_panel_delay]"
                                value="<?php echo esc_attr( (string) $floating_panel_delay ); ?>"
                                <?php echo $disabled ? 'disabled' : ''; ?>
                            >
                        </div>
                        <div style="margin-top:12px; max-width:240px;">
                            <label for="ventraconnect-sl-passkey-floating-snooze-days" style="display:block; font-weight:600; margin-bottom:4px;"><?php echo esc_html__( 'Snooze after “Not now”', 'ventraconnect-social-login' ); ?></label>
                            <input
                                id="ventraconnect-sl-passkey-floating-snooze-days"
                                type="number"
                                min="1"
                                max="365"
                                step="1"
                                class="wsc-admin wsc-input small-text"
                                name="ventraconnect_sl_settings[passkey][floating_panel_snooze_days]"
                                value="<?php echo esc_attr( (string) $floating_panel_snooze_days ); ?>"
                                <?php echo $disabled ? 'disabled' : ''; ?>
                            >
                            <p class="description wsc-small" style="margin-top:4px;"><?php echo esc_html__( 'When a user clicks ‘Not now’, hide the floating setup panel for this many days on that browser.', 'ventraconnect-social-login' ); ?></p>
                        </div>
                        <div style="margin-top:20px; max-width:720px;">
                            <?php echo wp_kses( $floating_panel_preview, array(
                                'div'    => array( 'class' => true, 'style' => true, 'data-context' => true, 'data-ventraconnect-passkeys-context' => true, 'data-ventraconnect-passkeys-floating-panel-preview' => true, 'aria-live' => true, 'role' => true, 'hidden' => true ),
                                'span'   => array( 'class' => true, 'aria-hidden' => true, 'focusable' => true, 'style' => true ),
                                'p'      => array( 'class' => true ),
                                'h3'     => array( 'class' => true ),
                                'button' => array( 'type' => true, 'class' => true, 'disabled' => true, 'aria-disabled' => true, 'tabindex' => true, 'data-provider' => true, 'data-theme' => true ),
                                'svg'    => array( 'viewBox' => true, 'aria-hidden' => true, 'focusable' => true, 'width' => true, 'height' => true, 'xmlns' => true, 'fill' => true, 'stroke' => true, 'class' => true ),
                                'path'   => array( 'd' => true, 'fill' => true, 'stroke' => true ),
                            ) ); ?>
                        </div>
                    </td>
                </tr>
                <?php
                $floating_panel_allowed_html = $allowed_helper_html;
                $floating_panel_allowed_html['select'] = array( 'id' => true, 'class' => true, 'name' => true, 'multiple' => true, 'size' => true, 'disabled' => true );
                $floating_panel_allowed_html['option'] = array( 'value' => true, 'selected' => true );
                $floating_panel_allowed_html['p'] = array( 'class' => true, 'style' => true, 'hidden' => true, 'data-vcs-page-picker-empty-selected' => true, 'data-vcs-page-picker-empty-results' => true );
                $floating_panel_allowed_html['div'] = array( 'class' => true, 'style' => true, 'data-vcs-page-picker' => true, 'id' => true, 'data-field-name' => true, 'data-vcs-page-picker-selected' => true, 'data-vcs-page-picker-results' => true, 'data-vcs-page-picker-selected-item' => true, 'data-page-id' => true );
                $floating_panel_allowed_html['label'] = array( 'for' => true, 'class' => true, 'style' => true );
                $floating_panel_allowed_html['input'] = array( 'type' => true, 'name' => true, 'value' => true, 'class' => true, 'id' => true, 'checked' => true, 'disabled' => true, 'placeholder' => true, 'data-vcs-page-picker-search' => true, 'data-vcs-page-picker-target' => true, 'aria-label' => true, 'min' => true, 'max' => true, 'step' => true, 'data-vcs-page-picker-hidden-input' => true );
                $floating_panel_allowed_html['span'] = array( 'class' => true, 'aria-hidden' => true, 'focusable' => true, 'style' => true );
                $floating_panel_allowed_html['button'] = array( 'type' => true, 'class' => true, 'disabled' => true, 'aria-disabled' => true, 'tabindex' => true, 'data-provider' => true, 'data-theme' => true, 'data-vcs-page-picker-add' => true, 'data-vcs-page-picker-remove' => true, 'data-page-id' => true );
                $floating_panel_allowed_html['h3'] = array( 'class' => true );
                $floating_panel_allowed_html['svg'] = array( 'viewBox' => true, 'aria-hidden' => true, 'focusable' => true, 'width' => true, 'height' => true, 'xmlns' => true, 'fill' => true, 'stroke' => true, 'class' => true );
                $floating_panel_allowed_html['path'] = array( 'd' => true, 'fill' => true, 'stroke' => true );
                $floating_panel_allowed_html['script'] = array( 'type' => true, 'class' => true, 'data-vcs-page-picker-data' => true );
                echo wp_kses( (string) ob_get_clean(), $floating_panel_allowed_html );
            } else {
                echo '<tr><th scope="row">' . esc_html__( 'Floating Passkey Setup Panel', 'ventraconnect-social-login' ) . '</th><td><div class="notice notice-info inline"><p>' . esc_html__( 'Available in Pro. Advanced passkey integrations are included in Pro, including this floating setup panel for supported account and post-login flows.', 'ventraconnect-social-login' ) . '</p></div><div style="margin-top:16px; max-width:720px;">' . wp_kses( $floating_panel_preview, array(
                    'div'    => array( 'class' => true, 'style' => true, 'data-context' => true, 'data-ventraconnect-passkeys-context' => true, 'data-ventraconnect-passkeys-floating-panel-preview' => true, 'aria-live' => true, 'role' => true, 'hidden' => true ),
                    'span'   => array( 'class' => true, 'aria-hidden' => true, 'focusable' => true, 'style' => true ),
                    'p'      => array( 'class' => true ),
                    'h3'     => array( 'class' => true ),
                    'button' => array( 'type' => true, 'class' => true, 'disabled' => true, 'aria-disabled' => true, 'tabindex' => true, 'data-provider' => true, 'data-theme' => true ),
                    'svg'    => array( 'viewBox' => true, 'aria-hidden' => true, 'focusable' => true, 'width' => true, 'height' => true, 'xmlns' => true, 'fill' => true, 'stroke' => true, 'class' => true ),
                    'path'   => array( 'd' => true, 'fill' => true, 'stroke' => true ),
                ) ) . '</div></td></tr>';
            }

        } elseif ( 'magic_link' === $slug ) {
            // === MAGIC LINK FIELDS (unchanged) ===
            $ml_registration_mode = isset( $conf['registration_mode'] ) ? (string) $conf['registration_mode'] : 'login_and_register';
            if ( ! in_array( $ml_registration_mode, [ 'login_and_register', 'login_only' ], true ) ) {
                $ml_registration_mode = 'login_and_register';
            }

            $redirect_options = [
                'same_page' => __( 'Return to requested page (default)', 'ventraconnect-social-login' ),
                'referer'   => __( 'Go back to previous page (referer)', 'ventraconnect-social-login' ),
                'home'      => __( 'Send to homepage', 'ventraconnect-social-login' ),
                'custom'    => __( 'Send to custom URL', 'ventraconnect-social-login' ),
            ];

            $ml_override = ! empty( $conf['redirect_override'] );
            $ml_mode     = $conf['redirect_mode'] ?? 'same_page';
            $ml_url      = (string) ( $conf['redirect_url'] ?? '' );

            echo '<tr><th scope="row">' . esc_html__( 'Expiry (minutes)', 'ventraconnect-social-login' ) . '</th><td><input type="number" min="1" class="wsc-admin wsc-number-text" name="ventraconnect_sl_settings[magic_link][expiry]" value="' . esc_attr( (int) ( $conf['expiry'] ?? 10 ) ) . '"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '></td></tr>';

            echo '<tr><th scope="row">' . esc_html__( 'Single-use', 'ventraconnect-social-login' ) . '</th><td><label class="wsc-admin wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_settings[magic_link][single_use]" value="1" ' . ( ! empty( $conf['single_use'] ) ? 'checked' : '' );
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '> ' . esc_html__( 'Invalidate after first use', 'ventraconnect-social-login' ) . '</label></td></tr>';

            echo '<tr><th scope="row">' . esc_html__( 'Restrict Login from Same IP', 'ventraconnect-social-login' ) . '</th><td><label class="wsc-admin wsc-checkbox-inline"><input type="checkbox" name="ventraconnect_sl_settings[magic_link][require_same_ip]" value="1" ' . ( ! empty( $conf['require_same_ip'] ) ? 'checked' : '' );
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '> ' . esc_html__( 'Require the same IP that requested the magic link to consume it', 'ventraconnect-social-login' ) . '</label></td></tr>';

            echo '<tr><th scope="row">' . esc_html__( 'Resend throttle (sec)', 'ventraconnect-social-login' ) . '</th><td><input type="number" min="0" class="wsc-admin wsc-number-text" name="ventraconnect_sl_settings[magic_link][resend_throttle]" value="' . esc_attr( (int) ( $conf['resend_throttle'] ?? 60 ) ) . '"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '><p class="description wsc-small" style="margin-top:6px;">' . esc_html__( 'By default, redirects follow your global settings (General, WooCommerce, Comments). Use the override below to customize this provider.', 'ventraconnect-social-login' ) . '</p></td></tr>';

            echo '<tr><th scope="row">' . esc_html__( 'Registration mode', 'ventraconnect-social-login' ) . '</th><td><fieldset>';
            echo '<label class="wsc-admin wsc-radio-simple"><input type="radio"  name="ventraconnect_sl_settings[magic_link][registration_mode]" value="login_and_register" ' . checked( $ml_registration_mode, 'login_and_register', false ) . ( ! empty( $disabled ) ? ' disabled' : '' ) . '> ' . esc_html__( 'Login & Register (default)', 'ventraconnect-social-login' ) . '</label><br>';
            echo '<label class="wsc-admin wsc-radio-simple"><input type="radio"  name="ventraconnect_sl_settings[magic_link][registration_mode]" value="login_only" ' . checked( $ml_registration_mode, 'login_only', false ) . ( ! empty( $disabled ) ? ' disabled' : '' ) . '> ' . esc_html__( 'Login only (existing users)', 'ventraconnect-social-login' ) . '</label>';
            echo '</fieldset></td></tr>';

            $ml_or = isset( $conf['or_separator'] ) ? (string) $conf['or_separator'] : 'none';
            if ( ! in_array( $ml_or, [ 'none', 'above', 'below', 'both' ], true ) ) {
                $ml_or = 'none';
            }
            echo '<tr><th scope="row">' . esc_html__( 'OR separator', 'ventraconnect-social-login' ) . '</th><td>';
            echo '<select name="ventraconnect_sl_settings[magic_link][or_separator]" class="wsc-select" ' . ( ! empty( $disabled ) ? 'disabled' : '' ) . '>';
            echo '<option value="none"' . selected( $ml_or, 'none', false ) . '>' . esc_html__( 'None', 'ventraconnect-social-login' ) . '</option>';
            echo '<option value="above"' . selected( $ml_or, 'above', false ) . '>' . esc_html__( 'Above button', 'ventraconnect-social-login' ) . '</option>';
            echo '<option value="below"' . selected( $ml_or, 'below', false ) . '>' . esc_html__( 'Below button', 'ventraconnect-social-login' ) . '</option>';
            echo '<option value="both"' . selected( $ml_or, 'both', false ) . '>' . esc_html__( 'Above and below', 'ventraconnect-social-login' ) . '</option>';
            echo '</select>';
            echo '</td></tr>';

            ob_start();
            ?>
            <tr>
                <th scope="row"><?php echo esc_html__( 'Redirect override', 'ventraconnect-social-login' ); ?></th>
                <td>
                    <label class="wsc-switch" style="margin-bottom:8px;">
                        <input type="hidden" name="ventraconnect_sl_settings[magic_link][redirect_override]" value="0">
                        <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_settings[magic_link][redirect_override]" value="1" <?php checked( $ml_override ); ?><?php echo $disabled ? ' disabled' : ''; ?>>
                        <span class="wsc-switch-ui" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php echo esc_html__( 'Enable redirect override', 'ventraconnect-social-login' ); ?></span>
                    </label>
                    <div class="description wsc-small" style="margin-bottom:8px;"><?php echo esc_html__( 'When enabled, Magic Link logins bypass global and WooCommerce rules and use the options below.', 'ventraconnect-social-login' ); ?></div>
                    <fieldset style="margin:0 0 8px 0;">
                        <?php foreach ( $redirect_options as $mode_val => $mode_label ) : ?>
                            <label class="wsc-admin wsc-radio-simple" style="display:block;margin:4px 0;">
                                <input type="radio"  name="ventraconnect_sl_settings[magic_link][redirect_mode]" value="<?php echo esc_attr( $mode_val ); ?>" <?php checked( $ml_mode, $mode_val ); ?><?php echo $disabled ? ' disabled' : ''; ?>>
                                <?php echo esc_html( $mode_label ); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <div style="margin-top:6px; width:500px;">
                        <label><?php echo esc_html__( 'Custom URL', 'ventraconnect-social-login' ); ?>
                            <input type="url" class="wsc-admin wsc-input" name="ventraconnect_sl_settings[magic_link][redirect_url]" value="<?php echo esc_attr( $ml_url ); ?>" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" <?php echo $disabled ? 'disabled' : ''; ?>>
                        </label>
                        <p class="description wsc-small" style="margin-top:4px;"><?php echo esc_html__( 'Only used when "Send to custom URL" is selected.', 'ventraconnect-social-login' ); ?></p>
                    </div>
                </td>
            </tr>
            <?php
            $allowed_admin_html = [
                'tr'       => [ 'class' => true, 'style' => true ],
                'th'       => [ 'scope' => true, 'class' => true, 'style' => true ],
                'td'       => [ 'class' => true, 'style' => true, 'colspan' => true ],
                'label'    => [ 'for' => true, 'class' => true, 'style' => true ],
                'input'    => [ 'type' => true, 'name' => true, 'value' => true, 'class' => true, 'id' => true, 'checked' => true, 'disabled' => true, 'placeholder' => true, 'min' => true, 'max' => true ],
                'span'     => [ 'class' => true, 'aria-hidden' => true ],
                'div'      => [ 'class' => true, 'style' => true ],
                'fieldset' => [ 'class' => true, 'style' => true ],
                'p'        => [ 'class' => true, 'style' => true ],
            ];
            echo wp_kses( (string) ob_get_clean(), $allowed_admin_html );

            echo '<tr><th scope="row">' . esc_html__( 'Email sender', 'ventraconnect-social-login' ) . '</th><td><input type="text" class="wsc-admin wsc-input" style="width:500px;" name="ventraconnect_sl_settings[magic_link][email_sender]" value="' . esc_attr( (string) ( $conf['email_sender'] ?? '' ) ) . '" placeholder="' . esc_attr__( 'e.g. Example Store Login', 'ventraconnect-social-login' ) . '"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '><p class="description wsc-small">' . esc_html__( 'Optional. Overrides the "From" name for emails sent by this provider. Leave blank to use the site default.', 'ventraconnect-social-login' ) . '</p></td></tr>';

            $magic_subject_default = (string) ( $conf['email_subject'] ?? '' );
            if ( '' === $magic_subject_default ) {
                $magic_subject_default = __( 'Your secure sign-in link for {site_name}', 'ventraconnect-social-login' );
            }

            echo '<tr><th scope="row">' . esc_html__( 'Email subject', 'ventraconnect-social-login' ) . '</th><td><input type="text" class="wsc-admin wsc-input" style="width:500px;" name="ventraconnect_sl_settings[magic_link][email_subject]" value="' . esc_attr( $magic_subject_default ) . '"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '><p class="description wsc-small">' . esc_html__( 'Tags: {magic_link}, {site_name}, {user_email}', 'ventraconnect-social-login' ) . '</p></td></tr>';

            $magic_body_default = (string) ( $conf['email_body'] ?? '' );
            if ( '' === $magic_body_default ) {
                $magic_body_default = __( "Hi,\n\nUse this secure link to sign in to {site_name}:\n\n{magic_link}\n\nThis link expires in {expires_in} minutes.\nIf you didn’t request this email, you can safely ignore it.\n\nThanks,\n{site_name}\n", 'ventraconnect-social-login' );
            }
            echo '<tr><th scope="row">' . esc_html__( 'Email body', 'ventraconnect-social-login' ) . '</th><td><textarea class="wsc-admin wsc-textarea" rows="5" name="ventraconnect_sl_settings[magic_link][email_body]"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '>' . esc_textarea( $magic_body_default ) . '</textarea><p class="description wsc-small">' . esc_html__( 'Tags: {magic_link}, {site_name}, {user_email}', 'ventraconnect-social-login' ) . '</p></td></tr>';

        } else {
            // === OTP EMAIL FIELDS (unchanged) ===
            $otp_registration_mode = isset( $conf['registration_mode'] ) ? (string) $conf['registration_mode'] : 'login_and_register';
            if ( ! in_array( $otp_registration_mode, [ 'login_and_register', 'login_only' ], true ) ) {
                $otp_registration_mode = 'login_and_register';
            }

            $redirect_options = [
                'same_page' => __( 'Return to requested page (default)', 'ventraconnect-social-login' ),
                'referer'   => __( 'Go back to previous page (referer)', 'ventraconnect-social-login' ),
                'home'      => __( 'Send to homepage', 'ventraconnect-social-login' ),
                'custom'    => __( 'Send to custom URL', 'ventraconnect-social-login' ),
            ];

            $otp_override = ! empty( $conf['redirect_override'] );
            $otp_mode     = $conf['redirect_mode'] ?? 'same_page';
            $otp_url      = (string) ( $conf['redirect_url'] ?? '' );

            echo '<tr><th scope="row">' . esc_html__( 'Code length (6-8)', 'ventraconnect-social-login' ) . '</th><td><input type="number" min="6" max="8" class="wsc-admin wsc-number-text" name="ventraconnect_sl_settings[otp_email][code_length]" value="' . esc_attr( min( 8, max( 6, (int) ( $conf['code_length'] ?? 6 ) ) ) ) . '"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '></td></tr>';

            echo '<tr><th scope="row">' . esc_html__( 'Expiry (minutes)', 'ventraconnect-social-login' ) . '</th><td><input type="number" min="1" class="wsc-admin wsc-number-text" name="ventraconnect_sl_settings[otp_email][expiry]" value="' . esc_attr( (int) ( $conf['expiry'] ?? 10 ) ) . '"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '></td></tr>';

            echo '<tr><th scope="row">' . esc_html__( 'Resend throttle (sec)', 'ventraconnect-social-login' ) . '</th><td><input type="number" min="0" class="wsc-admin wsc-number-text" name="ventraconnect_sl_settings[otp_email][resend_throttle]" value="' . esc_attr( (int) ( $conf['resend_throttle'] ?? 60 ) ) . '"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '><p class="description wsc-small" style="margin-top:6px;">' . esc_html__( 'By default, redirects follow your global settings (General, WooCommerce, Comments). Use the override below to customize this provider.', 'ventraconnect-social-login' ) . '</p></td></tr>';

            echo '<tr><th scope="row">' . esc_html__( 'Registration mode', 'ventraconnect-social-login' ) . '</th><td><fieldset>';
            echo '<label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_settings[otp_email][registration_mode]" value="login_and_register" ' . checked( $otp_registration_mode, 'login_and_register', false ) . ( ! empty( $disabled ) ? ' disabled' : '' ) . '> ' . esc_html__( 'Login & Register (default)', 'ventraconnect-social-login' ) . '</label><br>';
            echo '<label class="wsc-admin wsc-radio-simple"><input type="radio" name="ventraconnect_sl_settings[otp_email][registration_mode]" value="login_only" ' . checked( $otp_registration_mode, 'login_only', false ) . ( ! empty( $disabled ) ? ' disabled' : '' ) . '> ' . esc_html__( 'Login only (existing users)', 'ventraconnect-social-login' ) . '</label>';
            echo '</fieldset></td></tr>';

            ob_start();
            ?>
            <tr>
                <th scope="row"><?php echo esc_html__( 'Redirect override', 'ventraconnect-social-login' ); ?></th>
                <td>
                    <label class="wsc-switch" style="margin-bottom:8px;">
                        <input type="hidden" name="ventraconnect_sl_settings[otp_email][redirect_override]" value="0">
                        <input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_settings[otp_email][redirect_override]" value="1" <?php checked( $otp_override ); ?><?php echo $disabled ? ' disabled' : ''; ?>>
                        <span class="wsc-switch-ui" aria-hidden="true"></span>
                        <span class="screen-reader-text"><?php echo esc_html__( 'Enable redirect override', 'ventraconnect-social-login' ); ?></span>
                    </label>
                    <div class="description wsc-small" style="margin-bottom:8px;"><?php echo esc_html__( 'When enabled, OTP logins bypass global and WooCommerce rules and use the options below.', 'ventraconnect-social-login' ); ?></div>
                    <fieldset style="margin:0 0 8px 0;">
                        <?php foreach ( $redirect_options as $mode_val => $mode_label ) : ?>
                            <label class="wsc-admin wsc-radio-simple" style="display:block;margin:4px 0;">
                                <input type="radio" name="ventraconnect_sl_settings[otp_email][redirect_mode]" value="<?php echo esc_attr( $mode_val ); ?>" <?php checked( $otp_mode, $mode_val ); ?><?php echo $disabled ? ' disabled' : ''; ?>>
                                <?php echo esc_html( $mode_label ); ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <div style="margin-top:6px; width:500px;">
                        <label><?php echo esc_html__( 'Custom URL', 'ventraconnect-social-login' ); ?>
                            <input class="wsc-input" style="width:500px;" type="url" class="regular-text" name="ventraconnect_sl_settings[otp_email][redirect_url]" value="<?php echo esc_attr( $otp_url ); ?>" placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" <?php echo $disabled ? 'disabled' : ''; ?>>
                        </label>
                        <p class="description wsc-small" style="margin-top:4px;"><?php echo esc_html__( 'Only used when "Send to custom URL" is selected.', 'ventraconnect-social-login' ) . '</p>'; ?>
                    </div>
                </td>
            </tr>
            <?php
            $allowed_admin_html = [
                'tr'       => [ 'class' => true, 'style' => true ],
                'th'       => [ 'scope' => true, 'class' => true, 'style' => true ],
                'td'       => [ 'class' => true, 'style' => true, 'colspan' => true ],
                'label'    => [ 'for' => true, 'class' => true, 'style' => true ],
                'input'    => [ 'type' => true, 'name' => true, 'value' => true, 'class' => true, 'id' => true, 'checked' => true, 'disabled' => true, 'placeholder' => true, 'min' => true, 'max' => true ],
                'span'     => [ 'class' => true, 'aria-hidden' => true ],
                'div'      => [ 'class' => true, 'style' => true ],
                'fieldset' => [ 'class' => true, 'style' => true ],
                'p'        => [ 'class' => true, 'style' => true ],
            ];
            echo wp_kses( (string) ob_get_clean(), $allowed_admin_html );

            echo '<tr><th scope="row">' . esc_html__( 'Email sender', 'ventraconnect-social-login' ) . '</th><td><input type="text" class="wsc-input" style="width:500px;" name="ventraconnect_sl_settings[otp_email][email_sender]" value="' . esc_attr( (string) ( $conf['email_sender'] ?? '' ) ) . '" placeholder="' . esc_attr__( 'e.g. Example Store Login', 'ventraconnect-social-login' ) . '"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '><p class="description wsc-small">' . esc_html__( 'Optional. Overrides the "From" name for emails sent by this provider. Leave blank to use the site default.', 'ventraconnect-social-login' ) . '</p></td></tr>';

            echo '<tr><th scope="row">' . esc_html__( 'Max attempts', 'ventraconnect-social-login' ) . '</th><td><input type="number" min="1" class="wsc-number-text" name="ventraconnect_sl_settings[otp_email][max_attempts]" value="' . esc_attr( (int) ( $conf['max_attempts'] ?? 5 ) ) . '"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '></td></tr>';

            $otp_or = isset( $conf['or_separator'] ) ? (string) $conf['or_separator'] : 'none';
            if ( ! in_array( $otp_or, [ 'none', 'above', 'below', 'both' ], true ) ) {
                $otp_or = 'none';
            }
            echo '<tr><th scope="row">' . esc_html__( 'OR separator', 'ventraconnect-social-login' ) . '</th><td>';
            echo '<select name="ventraconnect_sl_settings[otp_email][or_separator]" class="wsc-select" ' . ( ! empty( $disabled ) ? 'disabled' : '' ) . '>';
            echo '<option value="none"' . selected( $otp_or, 'none', false ) . '>' . esc_html__( 'None', 'ventraconnect-social-login' ) . '</option>';
            echo '<option value="above"' . selected( $otp_or, 'above', false ) . '>' . esc_html__( 'Above button', 'ventraconnect-social-login' ) . '</option>';
            echo '<option value="below"' . selected( $otp_or, 'below', false ) . '>' . esc_html__( 'Below button', 'ventraconnect-social-login' ) . '</option>';
            echo '<option value="both"' . selected( $otp_or, 'both', false ) . '>' . esc_html__( 'Above and below', 'ventraconnect-social-login' ) . '</option>';
            echo '</select>';
            echo '</td></tr>';

            $otp_subject_default = (string) ( $conf['email_subject'] ?? '' );
            if ( '' === $otp_subject_default ) {
                $otp_subject_default = __( 'Your {site_name} login code: {otp_code}', 'ventraconnect-social-login' );
            }

            echo '<tr><th scope="row">' . esc_html__( 'Email subject', 'ventraconnect-social-login' ) . '</th><td><input type="text" class="wsc-input" style="width:500px;" name="ventraconnect_sl_settings[otp_email][email_subject]" value="' . esc_attr( $otp_subject_default ) . '"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '><p class="description wsc-small">' . esc_html__( 'Tags: {otp_code}, {expires_in}, {site_name}, {user_email}', 'ventraconnect-social-login' ) . '</p></td></tr>';

            $otp_body_default = (string) ( $conf['email_body'] ?? '' );
            if ( '' === $otp_body_default ) {
                $otp_body_default = __( "Hi,\n\nYour one-time login code for {site_name} is:\n\n{otp_code}\n\nThis code expires in {expires_in} minutes.\nIf you didn’t request this code, you can safely ignore this email.\n\nThanks,\n{site_name}\n", 'ventraconnect-social-login' );
            }
            echo '<tr><th scope="row">' . esc_html__( 'Email body', 'ventraconnect-social-login' ) . '</th><td><textarea class="wsc-textarea" rows="5" name="ventraconnect_sl_settings[otp_email][email_body]"';
            if ( ! empty( $disabled ) ) { echo ' ' . esc_attr( 'disabled' ); }
            echo '>' . esc_textarea( $otp_body_default ) . '</textarea><p class="description wsc-small">' . esc_html__( 'Tags: {otp_code}, {expires_in}, {site_name}, {user_email}', 'ventraconnect-social-login' ) . '</p></td></tr>';
        }

        echo '</table>';

        // Button style section for visual consistency.
        // Controls Pro CTA visibility inside Button Style card.
        $show_pro_cta_for_button_style = ( ! $pro && ! $is_passwordless );
        include VENTRACONNECT_SL_PLUGIN_DIR . 'includes/admin/views/providers/partials/section-button-style.php';

        // Always-active save bar for Magic Link / OTP – no Pro gating here.
        echo '<div class="wsc-admin wsc-savebar"><p style="margin-top:8px;">';
        echo '<button type="submit" class="button button-primary wsc-save-provider wsc-save-provider-ajax" data-provider="' . esc_attr( $slug ) . '">' . esc_html__( 'Save Changes', 'ventraconnect-social-login' ) . '</button> ';
        echo '<span class="wsc-provider-save-status" aria-live="polite"></span> ';

        if ( 'magic_link' === $slug ) {
            echo '<button type="button" class="button" id="vcs-test-magic" data-email="' . esc_attr( $admin_email ) . '">' . esc_html__( 'Send test email', 'ventraconnect-social-login' ) . '</button>';
        } elseif ( 'otp_email' === $slug ) {
            echo '<button type="button" class="button" id="vcs-test-otp" data-email="' . esc_attr( $admin_email ) . '">' . esc_html__( 'Send test email', 'ventraconnect-social-login' ) . '</button>';
        }

        echo '</p></div>';
        echo '</div>';

        if ( $preview_only ) {
            $html = (string) ob_get_clean();
            echo wp_kses_post( $this->apply_preview_filters( $html ) );
        }

        return;
    }

	// === OAUTH PROVIDERS: Getting Started card via external views ===
	$help_id = 'wsc-help-' . sanitize_html_class( $slug );
	echo '<div class="wsc-card" style="margin-top:12px;">';
	echo '<h3 class="wsc-card__title">';
	echo esc_html__( 'Getting Started', 'ventraconnect-social-login' );
	echo '  <button type="button"'
		. ' class="button-link wsc-help-toggle"'
		. ' aria-expanded="false"'
		. ' aria-controls="' . esc_attr( $help_id ) . '"'
		. ' data-label-show="' . esc_attr__( 'Show setup guide', 'ventraconnect-social-login' ) . '"'
		. ' data-label-hide="' . esc_attr__( 'Hide setup guide', 'ventraconnect-social-login' ) . '">';
	echo esc_html__( 'Show setup guide', 'ventraconnect-social-login' );
	echo '</button>';
	echo '</h3>';

	echo '<div id="' . esc_attr( $help_id ) . '" class="wsc-help" hidden>';

    // Try to load per-provider Getting Started view. Adjust path if your files live under class-admin.
    $view_paths = [
        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/admin/views/providers/' . $slug . '-getting-started.php',
        VENTRACONNECT_SL_PLUGIN_DIR . 'includes/class-admin/views/providers/' . $slug . '-getting-started.php',
    ];

    $view_loaded = false;

    foreach ( $view_paths as $view_path ) {
        if ( file_exists( $view_path ) ) {
            // Give the view everything it is likely to need.
            $provider_slug     = $slug;
            $provider_label    = $label;
            $provider_redirect = $redirect;
            $provider_redirect_id = $rid;

            /** @psalm-suppress UnresolvableInclude */
            include $view_path;
            $view_loaded = true;
            break;
        }
    }

    if ( ! $view_loaded ) {
        // Fallback generic steps.
        echo '<ol class="wsc-steps">';
        echo '<li>' . esc_html__( 'Create an app on the provider dashboard.', 'ventraconnect-social-login' ) . '</li>';
        echo '<li>' . esc_html__( 'Add the Redirect URI below to allowed/callback URLs.', 'ventraconnect-social-login' ) . '</li>';
        echo '<li>' . esc_html__( 'Copy Client ID and Secret into the Credentials section.', 'ventraconnect-social-login' ) . '</li>';
        echo '</ol>';
        echo '<p class="description wsc-small">' . esc_html__( 'Redirect URI', 'ventraconnect-social-login' ) . ':</p>';
        echo '<div class="wsc-row"><code id="' . esc_attr( $rid ) . '">' . esc_html( $redirect ) . '</code> <button type="button" class="button wsc-copy" data-copy="#' . esc_attr( $rid ) . '" data-label="' . esc_attr__( 'Copy', 'ventraconnect-social-login' ) . '" data-copied-label="' . esc_attr__( 'Copied', 'ventraconnect-social-login' ) . '">' . esc_html__( 'Copy', 'ventraconnect-social-login' ) . '</button></div>';
    }

    echo '</div>'; // .wsc-help
    echo '</div>'; // .wsc-card

    // === Credentials card (unchanged) ===
    echo '<div class="wsc-card" style="margin-top:12px;">';
    echo '<h3>' . esc_html__( 'Credentials', 'ventraconnect-social-login' ) . '</h3>';
    echo '<table class="form-table" role="presentation">';

    $is_checked = in_array( $slug, $enabled_providers, true );
    echo '<tr><th scope="row">' . esc_html__( 'Active', 'ventraconnect-social-login' ) . '</th><td><label class="wsc-switch"><input type="checkbox" class="wsc-switch-input" name="ventraconnect_sl_settings[providers][]" value="' . esc_attr( $slug ) . '" ' . checked( $is_checked, true, false ) . '><span class="wsc-switch-ui" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Enable provider', 'ventraconnect-social-login' ) . '</span></label></td></tr>';

    $id_label  = ( 'facebook' === $slug ) ? __( 'App ID', 'ventraconnect-social-login' ) : __( 'Client ID', 'ventraconnect-social-login' );
    $sec_label = ( 'facebook' === $slug ) ? __( 'App Secret', 'ventraconnect-social-login' ) : __( 'Client Secret', 'ventraconnect-social-login' );

    $cid = $creds[ $slug ]['client_id'] ?? '';
    $cs  = $creds[ $slug ]['client_secret'] ?? '';

    echo '<tr><th scope="row">' . esc_html( $label . ' ' . $id_label ) . '</th><td><input type="text" class="wsc-admin wsc-input" style="width:500px;" name="ventraconnect_sl_settings[provider_creds][' . esc_attr( $slug ) . '][client_id]" value="' . esc_attr( $cid ) . '"></td></tr>';

    $secret_help        = '';
    $secret_placeholder = '';

    if ( 'microsoft' === $slug ) {
        $secret_placeholder = esc_attr__( 'Paste the Client Secret value (not the Secret ID)', 'ventraconnect-social-login' );
        $secret_help        = esc_html__( 'In Azure → Certificates & secrets copy the Client Secret value shown when you create it. The shorter Secret ID will not authenticate.', 'ventraconnect-social-login' );
    }

    echo '<tr><th scope="row">' . esc_html( $label . ' ' . $sec_label ) . '</th><td><input type="text" class="wsc-admin wsc-input" style="width:500px;" name="ventraconnect_sl_settings[provider_creds][' . esc_attr( $slug ) . '][client_secret]" value="' . esc_attr( $cs ) . '"' . ( $secret_placeholder ? ' placeholder="' . esc_attr( $secret_placeholder ) . '"' : '' ) . '>';
    if ( $secret_help ) {
        echo '<p class="description wsc-small">' . wp_kses_post( $secret_help ) . '</p>';
    }
    echo '</td></tr>';

    echo '</table>';

    echo '<div style="width: 778px;text-align: end;padding-top: 16px;">';
    echo '<button type="submit" class="button button-primary wsc-save-provider wsc-save-provider-ajax" data-provider="' . esc_attr( $slug ) . '">' . esc_html__( 'Save Changes', 'ventraconnect-social-login' ) . '</button> ';
    echo '</div>';
    echo '</div>'; // Credentials card

    // === Verification card (unchanged) ===
    echo '<div class="wsc-card" style="margin-top:12px;">';
    echo '<h3>' . esc_html__( 'Verification', 'ventraconnect-social-login' ) . '</h3>';
    echo '<p class="wsc-muted">' . esc_html__( 'Open the provider login to verify your credentials or run quick diagnostics.', 'ventraconnect-social-login' ) . '</p>';
    echo '<p>';

    if ( $test_url ) {
        echo '<a class="button button-primary wsc-verify-provider" href="' . esc_url( $test_url ) . '" data-provider="' . esc_attr( $slug ) . '">' . esc_html__( 'Verify Settings', 'ventraconnect-social-login' ) . '</a> ';
    } else {
        echo '<button type="button" class="button" disabled>' . esc_html__( 'Verify Settings', 'ventraconnect-social-login' ) . '</button> ';
        echo '<span class="description wsc-small">' . esc_html__( 'Enter Client ID and Secret above to enable testing.', 'ventraconnect-social-login' ) . '</span> ';
    }

    echo '<button type="button" class="button wsc-run-diag" data-provider="' . esc_attr( $slug ) . '" data-nonce="' . esc_attr( $diag_nonce ) . '" data-url="' . esc_attr( $diag_url_attr ) . '">' . esc_html__( 'Run Diagnostics', 'ventraconnect-social-login' ) . '</button>';
    echo '</p>';

    echo '<div class="wsc-diag-results" aria-live="polite" style="display:none; margin-top:10px;"><pre class="wsc-diag-pre" style="background:#fff;border:1px solid #ccd0d4;padding:12px;line-height:1.5;white-space:pre-wrap;"></pre></div>';
    echo '<p class="description wsc-small">' . esc_html__( 'Tip: Your site should use HTTPS for best compatibility with OAuth providers.', 'ventraconnect-social-login' ) . '</p>';
    echo '</div>'; // Verification card

    // Per-provider button style + profile sync (unchanged)
    include VENTRACONNECT_SL_PLUGIN_DIR . 'includes/admin/views/providers/partials/section-button-style.php';
    include VENTRACONNECT_SL_PLUGIN_DIR . 'includes/admin/views/providers/partials/section-profile-sync.php';

    echo '<div class="wsc-admin wsc-savebar" style="margin-top:8px;">';
    echo '<button type="submit" class="button button-primary wsc-save-provider wsc-save-provider-ajax" data-provider="' . esc_attr( $slug ) . '">' . esc_html__( 'Save Changes', 'ventraconnect-social-login' ) . '</button> ';
    echo '<span class="wsc-provider-save-status" aria-live="polite"></span>';
    echo '</div>';

    if ( $preview_only ) {
        $html = (string) ob_get_clean();
        echo wp_kses_post( $this->apply_preview_filters( $html ) );
    }
}

	/**
	 * Apply preview-only transformations to field HTML: strip names and disable controls.
	 *
	 * @param string $html Raw rendered HTML.
	 * @return string
	 */
	private function apply_preview_filters( string $html ): string {
		$html = preg_replace( '/\sname="[^"]*"/', '', $html );
		if ( null === $html ) {
			$html = '';
		}
		$html = preg_replace_callback(
			'/<(input|select|textarea)([^>]*)>/i',
			static function ( $matches ) {
				$tag   = $matches[1];
				$attrs = $matches[2];
				if ( false === stripos( $attrs, 'disabled' ) ) {
					$attrs .= ' disabled="disabled"';
				}
				return '<' . $tag . $attrs . '>';
			},
			$html
		);
		if ( null === $html ) {
			$html = '';
		}
		// Remove checked/selected so previews don’t look “active”.
		$html = preg_replace( '/\schecked(="checked")?/i', '', $html );
		if ( null === $html ) {
			$html = '';
		}

		$html = preg_replace( '/\sselected(="selected")?/i', '', $html );
		if ( null === $html ) {
			$html = '';
		}
		return $html;
	}
	/**
	 * Render Provider advanced sections (after Credentials): Verification/Test/Diagnostics etc.
	 * Expects all URLs/flags prebuilt in $computed by the caller.
	 *
	 * @param string   $slug
	 * @param array    $provider  Meta and labels (kept for parity/extension)
	 * @param array    $creds
	 * @param array    $themes
	 * @param array    $overrides
	 * @param array    $texts
	 * @param array    $computed  ['test_url'=>string,'diag_url'=>string]
	 * @param callable $isPro
	 */
	public function renderProviderConfigAdvanced( string $slug, array $provider, array $creds, array $themes, array $overrides, array $texts, array $computed, callable $isPro ): void {
		$test_url = isset( $computed['test_url'] ) ? (string) $computed['test_url'] : '';
		$diag_url = isset( $computed['diag_url'] ) ? (string) $computed['diag_url'] : '';
		?>
		<div class="wsc-card" style="margin-top:12px;">
			<h3><?php echo esc_html__( 'Verification', 'ventraconnect-social-login' ); ?></h3>
			<p class="wsc-muted"><?php echo esc_html__( 'Open the provider login to verify your credentials or run quick diagnostics.', 'ventraconnect-social-login' ); ?></p>
			<p>
				<?php if ( ! empty( $test_url ) ) : ?>
					<a class="button button-primary wsc-verify-provider" href="<?php echo esc_url( $test_url ); ?>" data-provider="<?php echo esc_attr( $computed['slug'] ?? '' ); ?>"><?php echo esc_html__( 'Verify Settings', 'ventraconnect-social-login' ); ?></a>
				<?php else : ?>
					<button type="button" class="button" disabled><?php echo esc_html__( 'Verify Settings', 'ventraconnect-social-login' ); ?></button>
					<span class="description wsc-small"><?php echo esc_html__( 'Enter Client ID and Secret above to enable testing.', 'ventraconnect-social-login' ); ?></span>
				<?php endif; ?>
				<button type="button" class="button wsc-run-diag" data-url="<?php echo esc_attr( $diag_url ); ?>"><?php echo esc_html__( 'Run Diagnostics', 'ventraconnect-social-login' ); ?></button>
			</p>
			<div class="wsc-diag-results" style="display:none;margin-top:10px;"><pre class="wsc-diag-pre" style="background:#fff;border:1px solid #ccd0d4;padding:12px;line-height:1.5;white-space:pre-wrap;"></pre></div>
			<p class="description wsc-small"><?php echo esc_html__( 'Tip: Your site should use HTTPS for best compatibility with OAuth providers.', 'ventraconnect-social-login' ); ?></p>
		</div>
		<?php
	}

	/**
     * Analytics / Logs preview for the free plugin.
     *
     * This is a static copy of the Pro UI with example numbers,
     * used inside Pro_Preview on the Analytics tab.
     */
    public function renderAnalyticsPreview(): void {
        ?>
        <div class="wsc-analytics">

            <?php // 1. FILTER FORM (static, disabled) ?>
            <div class="wsc-analytics-filter">
                <form class="wsc-analytics-filter__form">
                    <label class="wsc-analytics-filter__label" for="vcsl_analytics_range_days">
                        <?php esc_html_e( 'Timeframe', 'ventraconnect-social-login' ); ?>
                    </label>
                    <select
                        class="wsc-select wsc-select--md"
                        id="vcsl_analytics_range_days"
                        disabled="disabled"
                    >
                        <option value="30" selected="selected">
                            <?php esc_html_e( 'Last 30 days', 'ventraconnect-social-login' ); ?>
                        </option>
                    </select>
                    <button type="button" class="wsc-btn wsc-btn-primary" disabled="disabled">
                        <?php esc_html_e( 'Apply', 'ventraconnect-social-login' ); ?>
                    </button>
                </form>
            </div>

            <?php // 2. STATS GRID (static sample numbers) ?>
            <div class="wsc-stats-grid">
                <div class="wsc-stat-card">
                    <div class="wsc-stat-card__header">
                        <?php esc_html_e( 'Total Logins', 'ventraconnect-social-login' ); ?>
                    </div>
                    <div class="wsc-stat-card__value">1,234</div>
                    <div class="wsc-stat-card__subtitle">
                        <?php esc_html_e( 'Last 30 days', 'ventraconnect-social-login' ); ?>
                    </div>
                </div>

                <div class="wsc-stat-card">
                    <div class="wsc-stat-card__header">
                        <?php esc_html_e( 'New Users', 'ventraconnect-social-login' ); ?>
                    </div>
                    <div class="wsc-stat-card__value">567</div>
                    <div class="wsc-stat-card__subtitle">
                        <?php esc_html_e( 'Last 30 days', 'ventraconnect-social-login' ); ?>
                    </div>
                </div>

                <div class="wsc-stat-card wsc-stat-card--featured">
                    <div class="wsc-stat-card__header">
                        <?php esc_html_e( 'Top Provider', 'ventraconnect-social-login' ); ?>
                    </div>
                    <div class="wsc-stat-card__value">Google</div>
                    <div class="wsc-stat-card__subtitle">
                        <?php esc_html_e( 'Last 30 days · 800 logins', 'ventraconnect-social-login' ); ?>
                    </div>
                </div>
            </div>

            <?php // 3. LOGINS BY METHOD TABLE (static rows) ?>
            <div class="wsc-analytics-section">
                <h2 class="wsc-analytics-section__title">
                    <?php esc_html_e( 'Logins by method', 'ventraconnect-social-login' ); ?>
                </h2>

                <table class="wsc-analytics-table">
                    <thead>
                    <tr>
                        <th><?php esc_html_e( 'Method', 'ventraconnect-social-login' ); ?></th>
                        <th style="text-align: right;">
                            <?php esc_html_e( 'Logins', 'ventraconnect-social-login' ); ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>
                            <div class="wsc-provider-cell">
                                <div class="wsc-provider-icon">🔐</div>
                                <span><?php esc_html_e( 'Social Login', 'ventraconnect-social-login' ); ?></span>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span class="wsc-count-badge">850</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="wsc-provider-cell">
                                <div class="wsc-provider-icon">✉</div>
                                <span><?php esc_html_e( 'Magic Link', 'ventraconnect-social-login' ); ?></span>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span class="wsc-count-badge">250</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="wsc-provider-cell">
                                <div class="wsc-provider-icon">🔢</div>
                                <span><?php esc_html_e( 'OTP Email', 'ventraconnect-social-login' ); ?></span>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span class="wsc-count-badge">134</span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <?php // 4. PROVIDER BREAKDOWN TABLE (static rows) ?>
            <div class="wsc-analytics-section">
                <h2 class="wsc-analytics-section__title">
                    <?php esc_html_e( 'Provider breakdown', 'ventraconnect-social-login' ); ?>
                </h2>

                <table class="wsc-analytics-table">
                    <thead>
                    <tr>
                        <th><?php esc_html_e( 'Provider', 'ventraconnect-social-login' ); ?></th>
                        <th style="text-align: right;">
                            <?php esc_html_e( 'Logins', 'ventraconnect-social-login' ); ?>
                        </th>
                        <th style="text-align: right;">
                            <?php esc_html_e( 'New users', 'ventraconnect-social-login' ); ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>
                            <div class="wsc-provider-cell">
                                <div class="wsc-provider-icon">G</div>
                                <span>Google</span>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span class="wsc-count-badge">400</span>
                        </td>
                        <td style="text-align: right;">
                            <span class="wsc-count-badge">120</span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="wsc-provider-cell">
                                <div class="wsc-provider-icon">f</div>
                                <span>Facebook</span>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span class="wsc-count-badge">250</span>
                        </td>
                        <td style="text-align: right;">
                            <span class="wsc-count-badge">90</span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <?php // 5. DEBUG LOG TABLE (static sample events) ?>
            <div class="wsc-analytics-section" style="margin-top: 24px;">
                <h2 class="wsc-analytics-section__title">
                    <?php esc_html_e( 'Debug log (last 500 events)', 'ventraconnect-social-login' ); ?>
                </h2>

                <table class="wsc-analytics-table">
                    <thead>
                    <tr>
                        <th><?php esc_html_e( 'Time', 'ventraconnect-social-login' ); ?></th>
                        <th><?php esc_html_e( 'Event', 'ventraconnect-social-login' ); ?></th>
                        <th><?php esc_html_e( 'Provider', 'ventraconnect-social-login' ); ?></th>
                        <th><?php esc_html_e( 'User', 'ventraconnect-social-login' ); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>2024-05-20 14:30:05</td>
                        <td>login_success</td>
                        <td>google</td>
                        <td>123</td>
                    </tr>
                    <tr>
                        <td>2024-05-21 09:12:44</td>
                        <td>login_blocked</td>
                        <td>facebook</td>
                        <td>456</td>
                    </tr>
                    </tbody>
                </table>
            </div>

        </div>
        <?php
    }
}
