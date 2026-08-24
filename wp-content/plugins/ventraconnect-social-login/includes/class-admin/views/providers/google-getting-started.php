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
<h4><?php echo esc_html__( 'Google Getting Started Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><?php echo esc_html__( 'To enable Google Login in VentraConnect, you need to create a Google App first.', 'ventraconnect-social-login' ); ?></li>
    <li><a target="_blank" rel="noopener" href="https://console.developers.google.com/apis/">https://console.developers.google.com/apis/</a> <?php echo esc_html__( 'and sign in with your Google account.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Select a project → New Project, name it, and click Create.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Make sure your new project is selected in the top bar.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'From the left menu, go to APIs & Services → OAuth consent screen and click Get started.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Enter your App name and Support email, then choose External as the user type.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo sprintf(
        /* translators: 1: Link to the main site domain. */
        esc_html__( 'Add your contact email, click Next, then under Authorized domains, click Add Domain and enter your main domain: %1$s.', 'ventraconnect-social-login' ),
        '<a href="' . esc_url( $domain ) . '" target="_blank" rel="noopener">' . esc_html( $domain ) . '</a>'
    ); ?></li>
    <li><?php echo esc_html__( 'Add your Home, Privacy Policy, and Terms of Service links, then click Save and Continue.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Go to Credentials → Create Credentials → OAuth Client ID.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Choose Web application, give it a name, and under Authorized redirect URIs, paste the redirect URL shown in your VentraConnect Google settings.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Create, then copy your Client ID and Client Secret.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In your WordPress dashboard, open VentraConnect → Providers → Google, and paste your Client ID and Client Secret into their fields, then click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'If your app is in Testing mode, only test users can log in. To make it live, open the OAuth consent screen, click Publish App, and confirm.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify Settings in the Google settings tab to confirm the connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, Google Login will be ready to use on your site.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
