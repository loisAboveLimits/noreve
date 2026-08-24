<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Normalise $domain so it's always a non-empty string.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- local template variable.
$vcsl_home_url = home_url();
$ventraconnect_sl_host     = wp_parse_url( $vcsl_home_url, PHP_URL_HOST );

// If wp_parse_url fails or returns something non-string, fall back to the raw home URL.
if ( ! is_string( $ventraconnect_sl_host ) ) {
    $ventraconnect_sl_host = $vcsl_home_url;
}

// This is what the rest of the template should use.
$domain = $ventraconnect_sl_host;

// Variables expected: $redirect
?>
<h4><?php echo esc_html__( 'Twitch Developer Console Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><a target="_blank" rel="noopener" href="https://dev.twitch.tv/console">Twitch Developer Console</a> <?php echo esc_html__( 'and sign in with your Twitch account.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Make sure your Twitch account is verified and has Two-Factor Authentication (2FA) enabled.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'If they are not enabled yet, open Twitch Settings > Security and Privacy to set them up.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the Developer Console, open the Applications tab.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click + Register Your Application on the right side.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Enter your App Name.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the OAuth Redirect URLs field, paste the redirect URL shown at the end of these steps.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Under Category, select Website Integration.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Complete the human verification test.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Create.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'On the Applications tab, find your app and click Manage.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click New Secret to generate your Client Secret.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Copy your Client ID and Client Secret - you will need them for VentraConnect.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In your WordPress dashboard, open VentraConnect > Providers > Twitch.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Paste your Client ID and Client Secret, then click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify to test your connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, users can log in or register on your site using their Twitch account.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
