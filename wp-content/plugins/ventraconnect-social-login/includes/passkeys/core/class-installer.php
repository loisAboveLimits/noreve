<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VentraConnect_SL_Passkeys_Core_Installer {

	const DB_VERSION = '0.1.1';

	public static function maybe_upgrade() {
		$current_version = (string) get_option( 'ventraconnect_passkeys_db_version', '0.0.0' );

		if ( version_compare( $current_version, self::DB_VERSION, '>=' ) ) {
			return;
		}

		self::create_tables();
		self::update_db_version();
	}

	protected static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate  = $wpdb->get_charset_collate();
		$passkeys_table   = VentraConnect_SL_Passkeys_Core_Database::get_passkeys_table();
		$challenges_table = VentraConnect_SL_Passkeys_Core_Database::get_challenges_table();
		$logs_table       = VentraConnect_SL_Passkeys_Core_Database::get_logs_table();

		$passkeys_sql = "CREATE TABLE {$passkeys_table} (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
user_id bigint(20) unsigned NOT NULL,
credential_id varchar(255) NOT NULL,
credential_type varchar(50) NOT NULL DEFAULT 'public-key',
public_key longtext NOT NULL,
sign_count bigint(20) unsigned NOT NULL DEFAULT 0,
user_handle varchar(255) DEFAULT NULL,
aaguid varchar(100) DEFAULT NULL,
attestation_type varchar(100) DEFAULT NULL,
trust_path longtext DEFAULT NULL,
transports text DEFAULT NULL,
backup_eligible tinyint(1) DEFAULT NULL,
backup_status tinyint(1) DEFAULT NULL,
uv_initialized tinyint(1) DEFAULT NULL,
device_name varchar(190) DEFAULT NULL,
is_active tinyint(1) NOT NULL DEFAULT 1,
last_used_at datetime DEFAULT NULL,
created_at datetime NOT NULL,
updated_at datetime NOT NULL,
PRIMARY KEY  (id),
UNIQUE KEY credential_id (credential_id),
KEY user_id (user_id),
KEY is_active (is_active)
) {$charset_collate};";

		$challenges_sql = "CREATE TABLE {$challenges_table} (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
user_id bigint(20) unsigned DEFAULT NULL,
challenge_type varchar(50) NOT NULL,
challenge longtext NOT NULL,
fingerprint varchar(255) DEFAULT NULL,
ip_address varchar(100) DEFAULT NULL,
user_agent text DEFAULT NULL,
expires_at datetime NOT NULL,
used_at datetime DEFAULT NULL,
created_at datetime NOT NULL,
PRIMARY KEY  (id),
KEY user_id (user_id),
KEY challenge_type (challenge_type),
KEY expires_at (expires_at)
) {$charset_collate};";

		$logs_sql = "CREATE TABLE {$logs_table} (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
user_id bigint(20) unsigned DEFAULT NULL,
passkey_id bigint(20) unsigned DEFAULT NULL,
event_type varchar(100) NOT NULL,
ip_address varchar(100) DEFAULT NULL,
user_agent text DEFAULT NULL,
message text DEFAULT NULL,
created_at datetime NOT NULL,
PRIMARY KEY  (id),
KEY user_id (user_id),
KEY passkey_id (passkey_id),
KEY event_type (event_type),
KEY created_at (created_at)
) {$charset_collate};";

		dbDelta( $passkeys_sql );
		dbDelta( $challenges_sql );
		dbDelta( $logs_sql );
	}

	protected static function update_db_version() {
		if ( false === get_option( 'ventraconnect_passkeys_db_version', false ) ) {
			add_option( 'ventraconnect_passkeys_db_version', self::DB_VERSION, '', 'no' );
		} else {
			update_option( 'ventraconnect_passkeys_db_version', self::DB_VERSION );
		}
	}
}
