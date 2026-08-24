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
<h4><?php echo esc_html__( 'Reddit App Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><a target="_blank" rel="noopener" href="https://www.reddit.com/prefs/apps">Reddit App Preferences page</a> <?php echo esc_html__( 'and sign in with your Reddit account.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Scroll down and click “are you a developer? create an app…”.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Select the web app option.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Enter a Name for your app.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( '(Optional) Add a Description and an About URL for your app.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the Redirect URI field, paste the redirect URL shown at the end of these steps.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Complete the human verification test.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Create app.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Copy your Client ID (displayed just below your app name and type).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Copy your Client Secret (shown next to “secret”).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In your WordPress dashboard, open VentraConnect > Providers > Reddit.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Paste your Client ID and Client Secret, then click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify Settings to test your connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, users can log in or register on your site using their Reddit account.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
