<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Core_Database {

	public static function get_passkeys_table() {
		global $wpdb;

		return $wpdb->prefix . 'ventraconnect_passkeys';
	}

	public static function get_challenges_table() {
		global $wpdb;

		return $wpdb->prefix . 'ventraconnect_passkey_challenges';
	}

	public static function get_logs_table() {
		global $wpdb;

		return $wpdb->prefix . 'ventraconnect_passkey_logs';
	}

	public static function table_exists( $table_name ) {
		global $wpdb;

		$found_table = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Passkey table existence check must reflect current schema state; caching is intentionally avoided.
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		return $table_name === $found_table;
	}
}
