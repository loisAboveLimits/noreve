<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Normalise $domain so it's always a non-empty string.
$ventraconnect_sl_home_url = home_url();
$ventraconnect_sl_host     = wp_parse_url( $ventraconnect_sl_home_url, PHP_URL_HOST );

// If wp_parse_url fails or returns something non-string, fall back to the raw home URL.
if ( ! is_string( $ventraconnect_sl_host ) ) {
    $ventraconnect_sl_host = $ventraconnect_sl_home_url;
}

// This is what the rest of the template should use.
$domain = $ventraconnect_sl_host;

// Variables expected: $redirect, $domain
?>
<h4><?php echo esc_html__( 'TikTok for Developers Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><a target="_blank" rel="noopener" href="https://developers.tiktok.com/portal/">Go to the TikTok Developer Portal</a> and sign in with your TikTok account.</li>
    <li>Click Developer Portal → Manage apps on the top right.</li>
    <li>Click the red Connect an app button.</li>
    <li>In the popup, select An individual developer (myself), enter your App Name, and click Confirm.</li>
    <li>Create and test your app in Sandbox Mode first. TikTok requires a short demo of the login process for app approval. Once verified, switch your app to Production Mode.</li>
    <li>In Basic Information, upload your App Icon, confirm your App Name, choose a Category, and add a short Description.</li>
    <li>Add your Terms of Service URL and Privacy Policy URL, then click Verify URL properties to confirm ownership.</li>
    <li>Under Platforms, enable Web, and in the Web Redirect URL field, copy and paste the redirect URL shown at the end of these steps (This is required for TikTok to send users back after login.)<br><strong>Domain:</strong> <a href="<?php echo esc_url( $domain ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $domain ); ?></a></li>
    <li>Click Verify URL properties again for the redirect URL, then click Save.</li>
    <li>Click + Add products, choose Login Kit, and press Done.</li>
    <li>Go to Login Kit → Web and make sure the same Redirect URL is listed there.</li>
    <li>In App Review, describe how TikTok login will be used for your site (e.g., “We use TikTok Login to let users register or sign in easily”).</li>
    <li>Click Save, then click Submit for review in the top-right corner.</li>
    <li>After approval (can take a few days), your app will show Live status.</li>
    <li>In your app dashboard, click the eye icon next to Client Key and Client Secret to reveal them.</li>
    <li>In WordPress, go to VentraConnect → Providers → TikTok, paste your Client Key and Client Secret, then click Save.</li>
    <li>Click Verify to test your connection.</li>
    <li>Once verified, users can log in or register on your site using their TikTok account.</li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
