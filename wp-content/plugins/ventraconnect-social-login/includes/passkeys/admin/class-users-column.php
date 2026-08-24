<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Users_Column {

	protected $manage_panel;
	protected $user_passkeys_cache = array();

	public function __construct( $manage_panel = null ) {
		$this->manage_panel = $manage_panel instanceof VentraConnect_SL_Passkeys_Manage_Panel ? $manage_panel : new VentraConnect_SL_Passkeys_Manage_Panel();
	}

	public function register_hooks() {
		add_filter( 'manage_users_columns', array( $this, 'add_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'render_column' ), 10, 3 );
	}

	public function add_column( $columns ) {
		if ( ! is_array( $columns ) ) {
			return array();
		}

		$columns['ventraconnect_passkeys'] = esc_html__( 'Passkeys', 'ventraconnect-social-login' );

		return $columns;
	}

	public function render_column( $output, $column_name, $user_id ) {
		if ( 'ventraconnect_passkeys' !== $column_name ) {
			return $output;
		}

		$passkeys = $this->get_active_passkeys_for_user( $user_id );
		$count    = count( $passkeys );

		if ( 0 === $count ) {
			return esc_html( VentraConnect_SL_Passkeys_Messages::get( 'users_column_no' ) );
		}

		$summary = sprintf( VentraConnect_SL_Passkeys_Messages::get( 'users_column_yes_count' ), $count );
		$latest  = $this->get_latest_last_used_date( $passkeys );

		if ( '' !== $latest ) {
			$summary .= ' | ' . sprintf( VentraConnect_SL_Passkeys_Messages::get( 'users_column_last_used' ), $latest );
		}

		return esc_html( $summary );
	}

	protected function get_active_passkeys_for_user( $user_id ) {
		$user_id = absint( $user_id );

		if ( isset( $this->user_passkeys_cache[ $user_id ] ) ) {
			return $this->user_passkeys_cache[ $user_id ];
		}

		$this->user_passkeys_cache[ $user_id ] = $this->manage_panel->get_active_passkeys( $user_id );

		return $this->user_passkeys_cache[ $user_id ];
	}

	protected function get_latest_last_used_date( array $passkeys ) {
		$latest_timestamp = 0;

		foreach ( $passkeys as $passkey ) {
			if ( empty( $passkey->last_used_at ) ) {
				continue;
			}

			$timestamp = mysql2date( 'U', (string) $passkey->last_used_at, false );

			if ( $timestamp && $timestamp > $latest_timestamp ) {
				$latest_timestamp = (int) $timestamp;
			}
		}

		if ( $latest_timestamp <= 0 ) {
			return '';
		}

		return wp_date( get_option( 'date_format' ), $latest_timestamp );
	}
}
