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
<h4><?php echo esc_html__( 'LinkedIn Getting Started Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><?php echo esc_html__( 'To enable LinkedIn Login in VentraConnect Social Login, you need to create a LinkedIn app first.', 'ventraconnect-social-login' ); ?></li>
    <li><a target="_blank" rel="noopener" href="https://www.linkedin.com/developers/">https://www.linkedin.com/developers/</a> <?php echo esc_html__( 'and sign in with your LinkedIn account.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Create App.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Enter your App Name in the “App name” field.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the LinkedIn Page field, select your company page. If you don’t have one, create a new LinkedIn Page.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Add your Privacy Policy URL and upload an App Logo.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Read and agree to the API Terms of Use, then click Create App.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'You’ll now be on the Products tab. Find Sign In with LinkedIn using OpenID Connect and click Request Access.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the modal that appears, tick I have read and agree to these terms, then click Request access.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Go to the Auth tab.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Under OAuth 2.0 settings, paste the redirect URL shown in your VentraConnect → Providers → LinkedIn settings into the Authorized redirect URLs field.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Update to save the changes.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Under the Authentication Keys section on the same page, copy your Client ID and Client Secret.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In your WordPress dashboard, open VentraConnect → Providers → LinkedIn.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Paste your Client ID and Client Secret, then click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify Settings to test your connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, users can log in or register on your site using their LinkedIn account.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
