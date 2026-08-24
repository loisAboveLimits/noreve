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
<h4><?php echo esc_html__( 'Yahoo Getting Started Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><?php echo esc_html__( 'To enable Yahoo Login in VentraConnect Social Login, you need to create a Yahoo app first.', 'ventraconnect-social-login' ); ?></li>
    <li><a target="_blank" rel="noopener" href="https://developer.yahoo.com/apps/">https://developer.yahoo.com/apps/</a> <?php echo esc_html__( 'and sign in with your Yahoo account.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Create an App in the top-right corner.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Enter your Application Name.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Add a short Description for your app.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo sprintf(
        /* translators: 1: Link to the site homepage URL. */
        esc_html__( 'In the Home Page URL field, enter your website’s main URL: %1$s.', 'ventraconnect-social-login' ),
        '<a href="' . esc_url( $domain ) . '" target="_blank" rel="noopener">' . esc_html( $domain ) . '</a>'
    ); ?></li>
    <li><?php echo esc_html__( 'In the Redirect URI(s) field, copay and paste the redirect URL shown at the end of these steps', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Under OAuth Client Type, select Confidential Client.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the API Permissions section, select OpenID Connect Permissions and enable both Email and Profile.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Create App.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'At the top of the page, copy your Client ID and Client Secret — you’ll need these for VentraConnect.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In your WordPress dashboard, open VentraConnect → Providers → Yahoo.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Paste your Client ID and Client Secret, then click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify Settings to test your connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, users can log in or register on your site using their Yahoo account.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
