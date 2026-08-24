<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variables used only within this included provider profile-sync partial.
// Ensure capabilities class is available (autoloader does not map this path)
if ( ! class_exists( '\\VentraConnect\\SocialLogin\\Providers\\VCS_Provider_Capabilities', false ) ) {
    $cap_path = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ? VENTRACONNECT_SL_PLUGIN_DIR . 'includes/providers/capabilities.php' : __DIR__ . '/../../../providers/capabilities.php';
    if ( file_exists( $cap_path ) ) { require_once $cap_path; }
}
$caps = \VentraConnect\SocialLogin\Providers\VCS_Provider_Capabilities::get( $slug );
$free = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption( 'ventraconnect_sl_sync_free', [] );
$f    = (array) ( $free[ $slug ] ?? [ 'avatar'=>1,'display_name'=>1,'first_last'=>1,'email'=>1 ] );
$pro_active = defined( 'VCS_PRO_ACTIVE' ) && VCS_PRO_ACTIVE;
$pro = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption( 'ventraconnect_sl_sync_pro', [] );
$p   = (array) ( $pro[ $slug ] ?? [] );

echo '<div class="wsc-card" style="margin-top:12px;">';
echo '<h3>' . esc_html__( 'Profile Sync', 'ventraconnect-social-login' ) . '</h3>';
echo '<table class="form-table" role="presentation">';

// FREE
echo '<tr><th scope="row">' . esc_html__( 'Basic settings', 'ventraconnect-social-login' ) . '</th><td>';
echo '<label class="wsc-checkbox-inline" style="display:block;margin:4px 0;"><input type="checkbox" class="vcs-free-avatar-checkbox" name="ventraconnect_sl_settings[sync_free]['.esc_attr($slug).'][avatar]" value="1" '.checked( !empty($f['avatar']), true, false ).'> '.esc_html__( 'Sync avatar (download to Media Library)', 'ventraconnect-social-login' ).'</label>';
echo '<label class="wsc-checkbox-inline" style="display:block;margin:4px 0;"><input type="checkbox" name="ventraconnect_sl_settings[sync_free]['.esc_attr($slug).'][display_name]" value="1" '.checked( !empty($f['display_name']), true, false ).'> '.esc_html__( 'Sync display name (fill blanks only)', 'ventraconnect-social-login' ).'</label>';
echo '<label class="wsc-checkbox-inline" style="display:block;margin:4px 0;"><input type="checkbox" name="ventraconnect_sl_settings[sync_free]['.esc_attr($slug).'][first_last]" value="1" '.checked( !empty($f['first_last']), true, false ).'> '.esc_html__( 'Sync first & last name (fill blanks only)', 'ventraconnect-social-login' ).'</label>';
echo '<label class="wsc-checkbox-inline" style="display:block;margin:4px 0;"><input type="checkbox" name="ventraconnect_sl_settings[sync_free]['.esc_attr($slug).'][email]" value="1" '.checked( !empty($f['email']), true, false ).'> '.esc_html__( 'Sync email if empty', 'ventraconnect-social-login' ).'</label>';
echo '<p class="description wsc-small">' . esc_html__( 'Free sync only fills empty fields and never overwrites.', 'ventraconnect-social-login' ) . '</p>';
echo '</td></tr>';

// PRO
if ( $pro_active ) {
	echo '<tr><th scope="row">' . esc_html__( 'Pro settings', 'ventraconnect-social-login' ) . '</th><td>';
	$fields = [
		'display_name' => __( 'Name', 'ventraconnect-social-login' ),
		'first_name'   => __( 'First name', 'ventraconnect-social-login' ),
		'last_name'    => __( 'Last name', 'ventraconnect-social-login' ),
		'email'        => __( 'Email', 'ventraconnect-social-login' ),
		'avatar'       => __( 'Avatar', 'ventraconnect-social-login' ),
		'locale'       => __( 'Locale', 'ventraconnect-social-login' ),
		'profile_url'  => __( 'Profile URL', 'ventraconnect-social-login' ),
		'company'      => __( 'Company', 'ventraconnect-social-login' ),
		'headline'     => __( 'Headline', 'ventraconnect-social-login' ),
		'website'      => __( 'Website', 'ventraconnect-social-login' ),
		'location'     => __( 'Location', 'ventraconnect-social-login' ),
	];
	$policies = [
		'fill_blanks' => __( 'Fill blanks only', 'ventraconnect-social-login' ),
		'overwrite'   => __( 'Always overwrite', 'ventraconnect-social-login' ),
		'never'       => __( 'Never', 'ventraconnect-social-login' ),
	];
	foreach ( $fields as $key => $label ) {
		if ( empty( $caps[ $key ] ) ) {
			continue;
		}
		$val = isset( $p[ $key ] ) ? $p[ $key ] : 'never';
		echo '<div class="">';
		echo '<label style="display:block;margin:4px 0;width:400px;">' . esc_html( $label ) . ' ';
		echo '<select class="wsc-admin wsc-select" name="ventraconnect_sl_settings[sync_pro][' . esc_attr( $slug ) . '][' . esc_attr( $key ) . ']">';
		foreach ( $policies as $k => $lb ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $val, $k, false ) . '>' . esc_html( $lb ) . '</option>';
		}
		echo '</select>';
		echo '</label>';
		echo '</div>';
	}
	// Extra toggle
	$map = ! empty( $p['map_woo_billing'] );
	echo '<label class="wsc-checkbox-inline" style="display:block;margin:6px 0;"><input type="checkbox" name="ventraconnect_sl_settings[sync_pro][' . esc_attr( $slug ) . '][map_woo_billing]" value="1" ' . checked( $map, true, false ) . '> ' . esc_html__( 'Map to WooCommerce billing fields where applicable', 'ventraconnect-social-login' ) . '</label>';

	$bulk_nonce        = wp_create_nonce( 'ventraconnect_sl_resync_bulk' );
	$bulk_nonce_legacy = wp_create_nonce( 'ventraconnect_sl_resync_bulk_legacy' );
	echo '<div class="wsc-resync-tools" style="margin-top:12px;">';
	echo '<p><button type="button" class="button button-secondary vcs-resync-bulk" data-provider="' . esc_attr( $slug ) . '" data-nonce="' . esc_attr( $bulk_nonce ) . '" data-nonce-legacy="' . esc_attr( $bulk_nonce_legacy ) . '">' . esc_html__( 'Run bulk resync', 'ventraconnect-social-login' ) . '</button></p>';
	echo '<p class="description wsc-small">' . esc_html__( 'Runs a dry-run first to count affected accounts, then syncs in small batches with a cooldown.', 'ventraconnect-social-login' ) . '</p>';
	echo '<div class="vcs-resync-output" style="display:none;margin-top:8px;"><pre style="background:#f8fafc;border:1px solid #e2e8f0;padding:10px;line-height:1.4;"></pre></div>';
	echo '</div>';

	echo '<p class="description wsc-small" style="margin-top:6px;">' . esc_html__( 'When enabled, we sync selected profile fields from your social provider. You can change this anytime.', 'ventraconnect-social-login' ) . '</p>';

	echo '</td></tr>';

	// Explanatory note about how Free and Pro avatar sync interact.
	echo '<tr class="vcs-profile-sync-note"><td colspan="2"><p class="description wsc-small">' . esc_html__( 'Note: The Free \"Sync avatar\" checkbox controls whether avatars are downloaded to the Media Library. Pro policies apply only when the free option is disabled.', 'ventraconnect-social-login' ) . '</p></td></tr>';
} else {
	echo '<tr><th scope="row">' . esc_html__( 'Pro settings', 'ventraconnect-social-login' ) . '</th><td>';

	echo '<div class="wsc-admin" style="margin-top:12px;">';
	echo '<strong>' . esc_html__( 'Advanced profile sync (Pro)', 'ventraconnect-social-login' ) . '</strong>';
	echo '<p class="description wsc-small" style="margin-top:4px;">'
		. esc_html__( 'Control per-field update rules (always overwrite vs fill blanks only), map to WooCommerce billing fields, and run bulk syncs.', 'ventraconnect-social-login' )
		. '</p>';

	if ( class_exists( '\VentraConnect\SocialLogin\Pro_Gates' ) ) {
		echo '<p style="margin-top:4px;">'
			. wp_kses_post( \VentraConnect\SocialLogin\Pro_Gates::upsell_inline( __( 'Learn more about Pro →', 'ventraconnect-social-login' ) ) )
			. '</p>';
	}

	echo '</div>';

	echo '</td></tr>';
}

echo '</table>';
echo '</div>';
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
