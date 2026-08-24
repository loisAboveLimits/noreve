<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Core_Log_Repository {

	public function add_log( array $data ) {
		global $wpdb;

		$event_type = sanitize_text_field( $data['event_type'] ?? '' );

		if ( '' === $event_type ) {
			return false;
		}

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom passkey log table write; caching is not applicable.
			VentraConnect_SL_Passkeys_Core_Database::get_logs_table(),
			array(
				'user_id'    => ! empty( $data['user_id'] ) ? absint( $data['user_id'] ) : null,
				'passkey_id' => ! empty( $data['passkey_id'] ) ? absint( $data['passkey_id'] ) : null,
				'event_type' => $event_type,
				'ip_address' => isset( $data['ip_address'] ) ? sanitize_text_field( $data['ip_address'] ) : null,
				'user_agent' => isset( $data['user_agent'] ) ? sanitize_textarea_field( $data['user_agent'] ) : null,
				'message'    => isset( $data['message'] ) ? sanitize_textarea_field( $data['message'] ) : null,
				'created_at' => current_time( 'mysql' ),
			),
			array(
				'%d',
				'%d',
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

	public function get_recent_logs( $limit = 50 ) {
		global $wpdb;

		$limit = absint( $limit );
		$limit = max( 1, min( 200, $limit ) );

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom passkey audit log read is capped and must remain fresh; caching is intentionally avoided.
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY created_at DESC LIMIT %d',
				VentraConnect_SL_Passkeys_Core_Database::get_logs_table(),
				$limit
			)
		);
	}
}
