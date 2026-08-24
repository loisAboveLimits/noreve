<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Manage_Panel {

	protected $passkey_repository;

	public function __construct( $passkey_repository = null ) {
		$this->passkey_repository = $passkey_repository instanceof VentraConnect_SL_Passkeys_Core_Passkey_Repository ? $passkey_repository : new VentraConnect_SL_Passkeys_Core_Passkey_Repository();
	}

	public function render( $user_id, array $args = array() ) {
		$user_id = absint( $user_id );

		if ( $user_id <= 0 ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'intro_note'       => '',
				'runtime_note'     => '',
				'show_runtime_note'=> false,
				'show_view_only'   => false,
				'is_own_profile'   => false,
				'wrapper_class'    => '',
			)
		);

		$passkeys      = $this->passkey_repository->get_user_passkeys( $user_id, true );
		$wrapper_class = trim( 'ventraconnect-sl-passkeys-manage ' . sanitize_html_class( (string) $args['wrapper_class'] ) );
		$is_own_profile = ! empty( $args['is_own_profile'] );
		$add_button_label = empty( $passkeys )
			? VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_add_button' )
			: VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_add_another_button' );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $wrapper_class ); ?>" data-context="wp_profile_passkeys">
			<?php if ( '' !== trim( (string) $args['intro_note'] ) ) : ?>
				<p class="description"><?php echo esc_html( (string) $args['intro_note'] ); ?></p>
			<?php endif; ?>

			<div class="ventraconnect-sl-passkeys-status" aria-live="polite" role="status" hidden="hidden"></div>
			<p><strong><?php echo esc_html( sprintf( VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_count' ), count( $passkeys ) ) ); ?></strong></p>

			<?php if ( $is_own_profile ) : ?>
				<p>
					<button type="button" class="button button-secondary ventraconnect-sl-passkeys-add-button">
						<?php echo esc_html( $add_button_label ); ?>
					</button>
				</p>
			<?php endif; ?>

			<?php if ( empty( $passkeys ) ) : ?>
				<p><?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_none' ) ); ?></p>
			<?php else : ?>
				<h3><?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_list_title' ) ); ?></h3>
				<table class="widefat striped" style="max-width: 720px;">
					<tbody>
						<?php foreach ( $passkeys as $passkey ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( $this->get_passkey_device_name( $passkey ) ); ?></strong><br>
									<span><?php echo esc_html( sprintf( VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_created' ), $this->format_passkey_date( $passkey->created_at ?? '' ) ) ); ?></span><br>
									<span><?php echo esc_html( sprintf( VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_last_used' ), $this->format_last_used_date( $passkey->last_used_at ?? '' ) ) ); ?></span>
								</td>
								<?php if ( $is_own_profile ) : ?>
									<td style="width: 140px;">
										<button
											type="button"
											class="button button-link-delete ventraconnect-sl-passkeys-remove-button"
											data-passkey-id="<?php echo esc_attr( (string) absint( $passkey->id ?? 0 ) ); ?>"
										>
											<?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_remove_button' ) ); ?>
										</button>
									</td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $args['show_view_only'] ) ) : ?>
				<p class="description"><?php echo esc_html( VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_view_only_note' ) ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $args['show_runtime_note'] ) && '' !== trim( (string) $args['runtime_note'] ) ) : ?>
				<p class="description"><?php echo esc_html( (string) $args['runtime_note'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	public function get_active_passkeys( $user_id ) {
		return $this->passkey_repository->get_user_passkeys( absint( $user_id ), true );
	}

	public function get_passkey_device_name( $passkey ) {
		$device_name = isset( $passkey->device_name ) ? trim( (string) $passkey->device_name ) : '';

		if ( '' !== $device_name ) {
			return $device_name;
		}

		return VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_default_name' );
	}

	public function format_passkey_date( $mysql_datetime ) {
		$mysql_datetime = is_string( $mysql_datetime ) ? trim( $mysql_datetime ) : '';

		if ( '' === $mysql_datetime ) {
			return VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_unknown' );
		}

		$timestamp = mysql2date( 'U', $mysql_datetime, false );

		if ( ! $timestamp ) {
			return VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_unknown' );
		}

		return wp_date( get_option( 'date_format' ), $timestamp );
	}

	public function format_last_used_date( $mysql_datetime ) {
		$mysql_datetime = is_string( $mysql_datetime ) ? trim( $mysql_datetime ) : '';

		if ( '' === $mysql_datetime ) {
			return VentraConnect_SL_Passkeys_Messages::get( 'admin_profile_passkeys_never' );
		}

		return $this->format_passkey_date( $mysql_datetime );
	}
}
