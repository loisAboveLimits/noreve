<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Core_Passkey_Repository {

	public function create_passkey( array $data ) {
		global $wpdb;

		$user_id       = absint( $data['user_id'] ?? 0 );
		$credential_id = sanitize_text_field( $data['credential_id'] ?? '' );
		$public_key    = sanitize_textarea_field( $data['public_key'] ?? '' );

		if ( ! $user_id || '' === $credential_id || '' === $public_key ) {
			return false;
		}

		$now             = current_time( 'mysql' );
		$transports      = null;
		$trust_path      = null;
		$credential_type = isset( $data['credential_type'] ) ? sanitize_text_field( $data['credential_type'] ) : 'public-key';

		if ( isset( $data['transports'] ) ) {
			$transports = is_array( $data['transports'] )
				? wp_json_encode( array_map( 'sanitize_text_field', $data['transports'] ) )
				: sanitize_textarea_field( $data['transports'] );
		}

		if ( isset( $data['trust_path'] ) ) {
			$trust_path = is_array( $data['trust_path'] )
				? wp_json_encode( $data['trust_path'] )
				: sanitize_textarea_field( $data['trust_path'] );
		}

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom passkey table write; caching is not applicable.
			VentraConnect_SL_Passkeys_Core_Database::get_passkeys_table(),
			array(
				'user_id'          => $user_id,
				'credential_id'    => $credential_id,
				'credential_type'  => '' !== $credential_type ? $credential_type : 'public-key',
				'public_key'       => $public_key,
				'sign_count'       => absint( $data['sign_count'] ?? 0 ),
				'user_handle'      => isset( $data['user_handle'] ) ? sanitize_text_field( $data['user_handle'] ) : null,
				'aaguid'           => isset( $data['aaguid'] ) ? sanitize_text_field( $data['aaguid'] ) : null,
				'attestation_type' => isset( $data['attestation_type'] ) ? sanitize_text_field( $data['attestation_type'] ) : null,
				'trust_path'       => $trust_path,
				'transports'       => $transports,
				'backup_eligible'  => $this->normalize_bool_or_null( $data['backup_eligible'] ?? null ),
				'backup_status'    => $this->normalize_bool_or_null( $data['backup_status'] ?? null ),
				'uv_initialized'   => $this->normalize_bool_or_null( $data['uv_initialized'] ?? null ),
				'device_name'      => isset( $data['device_name'] ) ? sanitize_text_field( $data['device_name'] ) : null,
				'is_active'        => isset( $data['is_active'] ) ? absint( (bool) $data['is_active'] ) : 1,
				'created_at'       => $now,
				'updated_at'       => $now,
			),
			array(
				'%d','%s','%s','%s','%d','%s','%s','%s','%s','%s','%d','%d','%d','%s','%d','%s','%s',
			)
		);

		return false === $inserted ? false : (int) $wpdb->insert_id;
	}

	public function get_passkey_by_id( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return null;
		}

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom passkey table requires fresh credential state; caching is intentionally avoided.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d LIMIT 1',
				VentraConnect_SL_Passkeys_Core_Database::get_passkeys_table(),
				$id
			)
		);
	}

	public function get_passkey_by_credential_id( $credential_id ) {
		global $wpdb;

		$credential_id = $this->normalize_base64url_string( $credential_id );
		if ( '' === $credential_id ) {
			return null;
		}

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom passkey table requires fresh credential state; caching is intentionally avoided.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE credential_id = %s AND is_active = 1 LIMIT 1',
				VentraConnect_SL_Passkeys_Core_Database::get_passkeys_table(),
				$credential_id
			)
		);
	}

	public function get_user_passkeys( $user_id, $active_only = true ) {
		global $wpdb;

		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return array();
		}

		if ( $active_only ) {
			return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom passkey table requires fresh credential state; caching is intentionally avoided.
				$wpdb->prepare(
					'SELECT * FROM %i WHERE user_id = %d AND is_active = 1 ORDER BY created_at DESC',
					VentraConnect_SL_Passkeys_Core_Database::get_passkeys_table(),
					$user_id
				)
			);
		}

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom passkey table requires fresh credential state; caching is intentionally avoided.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE user_id = %d ORDER BY created_at DESC',
				VentraConnect_SL_Passkeys_Core_Database::get_passkeys_table(),
				$user_id
			)
		);
	}

	public function update_passkey_last_used( $id, $sign_count = null ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return false;
		}

		$data   = array(
			'last_used_at' => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
		);
		$format = array( '%s', '%s' );

		if ( null !== $sign_count ) {
			$data['sign_count'] = absint( $sign_count );
			$format[]           = '%d';
		}

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom passkey table write; caching is not applicable.
			VentraConnect_SL_Passkeys_Core_Database::get_passkeys_table(),
			$data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		return false !== $updated;
	}

	public function update_authentication_metadata( $id, $user_id, $sign_count, $backup_eligible = null, $backup_status = null, $uv_initialized = null ) {
		global $wpdb;

		$id      = absint( $id );
		$user_id = absint( $user_id );

		if ( ! $id || ! $user_id ) {
			return false;
		}

		$data = array(
			'sign_count'   => absint( $sign_count ),
			'last_used_at' => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
		);
		$format = array( '%d', '%s', '%s' );

		if ( null !== $backup_eligible ) {
			$data['backup_eligible'] = $this->normalize_bool_or_null( $backup_eligible );
			$format[]                = '%d';
		}
		if ( null !== $backup_status ) {
			$data['backup_status'] = $this->normalize_bool_or_null( $backup_status );
			$format[]              = '%d';
		}
		if ( null !== $uv_initialized ) {
			$data['uv_initialized'] = $this->normalize_bool_or_null( $uv_initialized );
			$format[]               = '%d';
		}

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom passkey table write; caching is not applicable.
			VentraConnect_SL_Passkeys_Core_Database::get_passkeys_table(),
			$data,
			array( 'id' => $id, 'user_id' => $user_id ),
			$format,
			array( '%d', '%d' )
		);

		return false !== $updated;
	}

	public function update_device_name( $id, $user_id, $device_name ) {
		global $wpdb;

		$id          = absint( $id );
		$user_id     = absint( $user_id );
		$device_name = sanitize_text_field( $device_name );

		if ( ! $id || ! $user_id || '' === $device_name ) {
			return false;
		}

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom passkey table write; caching is not applicable.
			VentraConnect_SL_Passkeys_Core_Database::get_passkeys_table(),
			array(
				'device_name' => $device_name,
				'updated_at'  => current_time( 'mysql' ),
			),
			array(
				'id'      => $id,
				'user_id' => $user_id,
			),
			array( '%s', '%s' ),
			array( '%d', '%d' )
		);

		return false !== $updated;
	}

	public function deactivate_passkey( $id, $user_id ) {
		global $wpdb;

		$id      = absint( $id );
		$user_id = absint( $user_id );

		if ( ! $id || ! $user_id ) {
			return false;
		}

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom passkey table write; caching is not applicable.
			VentraConnect_SL_Passkeys_Core_Database::get_passkeys_table(),
			array(
				'is_active'  => 0,
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'id'      => $id,
				'user_id' => $user_id,
			),
			array( '%d', '%s' ),
			array( '%d', '%d' )
		);

		return false !== $updated;
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

	protected function normalize_bool_or_null( $value ) {
		if ( null === $value || '' === $value ) {
			return null;
		}

		return absint( (bool) $value );
	}
}
