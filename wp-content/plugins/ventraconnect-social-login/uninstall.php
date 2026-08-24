<?php
// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options (Free)
delete_option( 'ventraconnect_sl_settings' );
delete_option( 'ventraconnect_sl_provider_creds' );
delete_option( 'ventraconnect_sl_wc_settings' );
delete_option( 'ventraconnect_sl_comments_settings' );
delete_option( 'ventraconnect_sl_emails_settings' );
delete_option( 'ventraconnect_sl_sync_free' );
// In case Pro options were saved while testing
delete_option( 'ventraconnect_sl_sync_pro' );
delete_option( 'ventraconnect_sl_email_allow_relay_overwrite' );

global $wpdb;

// Remove transients related to OAuth state and tokens
$ventraconnect_sl_like_patterns = [
    '_transient_ventraconnect_sl_state_%',
    '_transient_timeout_ventraconnect_sl_state_%',
    '_transient_ventraconnect_sl_last_tiktok_token',
    '_transient_timeout_ventraconnect_sl_last_tiktok_token',
    '_transient_ventraconnect_sl_admin_notice',
    '_transient_timeout_ventraconnect_sl_admin_notice',
];

foreach ( $ventraconnect_sl_like_patterns as $ventraconnect_sl_pattern ) {
    // Remove plugin transients by prefix using the options API for PHPCS compliance.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe, one-time uninstall operation.
    $ventraconnect_sl_option_names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $ventraconnect_sl_pattern ) );
    foreach ( $ventraconnect_sl_option_names as $ventraconnect_sl_option_name ) {
        delete_option( $ventraconnect_sl_option_name );
        if ( is_multisite() ) {
            delete_site_option( $ventraconnect_sl_option_name );
        }
    }
}

// Optionally purge user meta on uninstall (disabled by default)
$ventraconnect_sl_purge_meta = defined( 'VCS_PURGE_USER_META_ON_UNINSTALL' ) && VCS_PURGE_USER_META_ON_UNINSTALL;
$ventraconnect_sl_purge_meta = apply_filters( 'ventraconnect_sl_purge_user_meta_on_uninstall', $ventraconnect_sl_purge_meta );
if ( $ventraconnect_sl_purge_meta ) {
    $ventraconnect_sl_meta_keys = [
        '_ventraconnect_sl_linked_providers',
        '_ventraconnect_sl_connections',
        '_ventraconnect_sl_primary_provider',
        'ventraconnect_sl_avatar_id',
        'ventraconnect_sl_avatar_source',
        'ventraconnect_sl_avatar_remote_hash',
        'ventraconnect_sl_profile_snapshot',
        'ventraconnect_sl_last_profile_sync',
        'ventraconnect_sl_last_sync_fields',
        'ventraconnect_sl_locale',
        'ventraconnect_sl_profile_url',
        'ventraconnect_sl_company',
        'ventraconnect_sl_headline',
        'ventraconnect_sl_website',
        'ventraconnect_sl_location_text',
        'ventraconnect_sl_email_overwrite_log',
        'ventraconnect_sl_consent_shown',
        'ventraconnect_sl_last_provider',
        'ventraconnect_sl_checkout_prefill',
    ];
    // PHPCS: Use delete_user_meta() for each user and meta key for full compliance.
    if ( count( $ventraconnect_sl_meta_keys ) > 0 ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe, one-time uninstall operation.
    $ventraconnect_sl_user_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users}" );
        foreach ( $ventraconnect_sl_user_ids as $ventraconnect_sl_user_id ) {
            foreach ( $ventraconnect_sl_meta_keys as $ventraconnect_sl_meta_key ) {
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $ventraconnect_sl_user_id, $ventraconnect_sl_meta_key );
            }
        }
    }
}
