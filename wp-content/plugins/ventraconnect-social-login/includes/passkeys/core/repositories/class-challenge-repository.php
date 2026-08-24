<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Core_Challenge_Repository {

	public function create_challenge( array $data ) {
		global $wpdb;

		$challenge_type = sanitize_text_field( $data['challenge_type'] ?? '' );
		$challenge      = sanitize_textarea_field( $data['challenge'] ?? '' );
		$expires_at     = sanitize_text_field( $data['expires_at'] ?? '' );

		if ( '' === $challenge_type || '' === $challenge || '' === $expires_at ) {
			return false;
		}

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom passkey challenge table write; caching is not applicable.
			VentraConnect_SL_Passkeys_Core_Database::get_challenges_table(),
			array(
				'user_id'        => ! empty( $data['user_id'] ) ? absint( $data['user_id'] ) : null,
				'challenge_type' => $challenge_type,
				'challenge'      => $challenge,
				'fingerprint'    => isset( $data['fingerprint'] ) ? sanitize_text_field( $data['fingerprint'] ) : null,
				'ip_address'     => isset( $data['ip_address'] ) ? sanitize_text_field( $data['ip_address'] ) : null,
				'user_agent'     => isset( $data['user_agent'] ) ? sanitize_textarea_field( $data['user_agent'] ) : null,
				'expires_at'     => $expires_at,
				'created_at'     => current_time( 'mysql' ),
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	public function get_valid_challenge( $challenge, $challenge_type ) {
		global $wpdb;

		$challenge      = sanitize_textarea_field( $challenge );
		$challenge_type = sanitize_text_field( $challenge_type );

		if ( '' === $challenge || '' === $challenge_type ) {
			return null;
		}

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Passkey challenge validation requires fresh one-time challenge state; caching is intentionally avoided.
			$wpdb->prepare(
				'SELECT * FROM %i WHERE challenge = %s AND challenge_type = %s AND used_at IS NULL AND expires_at > %s LIMIT 1',
				VentraConnect_SL_Passkeys_Core_Database::get_challenges_table(),
				$challenge,
				$challenge_type,
				current_time( 'mysql' )
			)
		);
	}

	public function mark_challenge_used( $id ) {
		global $wpdb;

		$id = absint( $id );

		if ( ! $id ) {
			return false;
		}

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Passkey challenge state update must be immediate; caching is intentionally avoided.
			VentraConnect_SL_Passkeys_Core_Database::get_challenges_table(),
			array(
				'used_at' => current_time( 'mysql' ),
			),
			array(
				'id' => $id,
			),
			array(
				'%s',
			),
			array(
				'%d',
			)
		);

		return false !== $updated;
	}

	public function delete_expired_challenges() {
		global $wpdb;

		return $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Passkey challenge cleanup writes must be immediate; caching is not applicable.
			$wpdb->prepare(
				'DELETE FROM %i WHERE expires_at < %s',
				VentraConnect_SL_Passkeys_Core_Database::get_challenges_table(),
				current_time( 'mysql' )
			)
		);
	}

	public function delete_unused_challenges_for_user( $user_id ) {
		global $wpdb;

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return false;
		}

		return $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Passkey challenge cleanup writes must be immediate; caching is not applicable.
			$wpdb->prepare(
				'DELETE FROM %i WHERE user_id = %d AND used_at IS NULL AND challenge_type IN (%s, %s)',
				VentraConnect_SL_Passkeys_Core_Database::get_challenges_table(),
				$user_id,
				VentraConnect_SL_Passkeys_Core_Challenge_Service::TYPE_REGISTRATION,
				VentraConnect_SL_Passkeys_Core_Challenge_Service::TYPE_AUTHENTICATION
			)
		);
	}
}
