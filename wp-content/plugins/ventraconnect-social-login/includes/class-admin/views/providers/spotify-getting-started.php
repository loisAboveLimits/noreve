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

// Variables expected: $redirect, $domain
?>
<h4><?php echo esc_html__( 'Spotify Getting Started Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li>Go to the <a target="_blank" rel="noopener" href="https://developer.spotify.com/dashboard">Spotify Developer Dashboard</a> and sign in with your Spotify account.</li>
    <li>Click the purple <strong>Create App</strong> button.</li>
    <li>Enter your App Name and App Description — these appear to users on the authorization screen.</li>
    <li>In the Website field, enter your website’s homepage URL: <a href="<?php echo esc_url( $domain ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $domain ); ?></a></li>
    <li>In the Redirect URI field, paste the redirect URL shown at the end of these steps.</li>
    <li>Under <strong>Which API/SDKs are you planning to use?</strong>, select <strong>Web API</strong>.</li>
    <li>Read and agree to the Developer Terms of Service, then click <strong>Save</strong>.</li>
    <li>You’ll be redirected to your app overview page. Click <strong>Settings</strong>.</li>
    <li>Copy your <strong>Client ID</strong> and click <strong>View client secret</strong> to reveal and copy your <strong>Client Secret</strong>.</li>
    <li>Go to your WordPress dashboard and open <strong>VentraConnect → Providers → Spotify</strong>.</li>
    <li>Paste your Client ID and Client Secret, then click <strong>Save</strong>.</li>
    <li>Click <strong>Verify Settings</strong> to test your connection.</li>
    <li><em>(Optional)</em> Your app starts in Development Mode, which allows up to 25 Spotify accounts for testing.<br>To make it public, go to <strong>Extension Requests → Start</strong> in the Spotify Developer Dashboard and request <strong>Extended Quota Mode</strong>.<br>Once approved, anyone with a Spotify account can log in using your app.</li>
    <li>Once verified, users can log in or register on your site using their Spotify account.</li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
