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
<h4><?php echo esc_html__( 'Slack OpenID Connect Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li>To enable Slack Login in VentraConnect Social Login, you need to create a Slack app first.</li>
    <li><a target="_blank" rel="noopener" href="https://api.slack.com/apps">Go to the Slack Developer Portal</a> and sign in with your Slack account.</li>
    <li>Click Create New App. (If you don’t see this button, you may need to create or join a workspace first.)</li>
    <li>Choose From scratch.</li>
    <li>Enter your App Name, select your Workspace, and click Create App.</li>
    <li>Under the Add features and functionality section, click Permissions.</li>
    <li>In the Redirect URLs field, copy and paste the redirect URL shown at the end of these steps<br><strong>Domain:</strong> <a href="<?php echo esc_url( $domain ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $domain ); ?></a></li>
    <li>Click Save URLs.</li>
</ol>
<ul style="margin-top:6px;margin-bottom:6px;padding-left:4em;">
    <li><strong>openid</strong></li>
    <li><strong>profile</strong></li>
    <li><strong>email</strong></li>
    </ul>
<ol start="10" class="wsc-steps">
    <li>From the left sidebar, go to Settings → Basic Information.</li>
    <li>Under Install your app, click Install to Workspace, then click Allow when prompted.</li>
    <li>Under Manage Distribution, click Distribute App.</li>
    <li>Scroll down to the Remove Hard-Coded Information section, tick I’ve reviewed and removed any hard-coded information, and click Activate Public Distribution.</li>
    <li>Go back to Settings → Basic Information.</li>
    <li>In the App Credentials section, copy your Client ID and Client Secret — you’ll need these for VentraConnect.</li>
    <li>In your WordPress dashboard, open VentraConnect → Providers → Slack.</li>
    <li>Paste your Client ID and Client Secret, then click Save.</li>
    <li>Click Verify to test your connection.</li>
    <li>Once verified, users can log in or register on your site using their Slack account.</li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
