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
<h4><?php echo esc_html__( 'X (Twitter) Getting Started Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><?php echo esc_html__( 'To enable X (Twitter) Login in VentraConnect Social Login, you need to create a developer app first.', 'ventraconnect-social-login' ); ?></li>
    <li><a target="_blank" rel="noopener" href="https://developer.twitter.com/en/portal/dashboard">https://developer.twitter.com/en/portal/dashboard</a> <?php echo esc_html__( 'and register for a Developer Account (approval may take a few days).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'After approval, open Projects & Apps → Overview and click Create App.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In your new app settings, enable User Authentication.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Choose App Type: Web App and set permissions according to your needs (e.g., Read/Write).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the Callback URI / Redirect URL field, paste the redirect URL shown at the bottoms of these steps.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo sprintf(
        /* translators: 1: Link to the website URL. */
        esc_html__( 'Enter your Website URL: %1$s, then click Save.', 'ventraconnect-social-login' ),
        '<a href="' . esc_url( $domain ) . '" target="_blank" rel="noopener">' . esc_html( $domain ) . '</a>'
    ); ?></li>
    <li><?php echo esc_html__( 'Copy your Client ID (API Key) and Client Secret (API Secret Key).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Go back to your WordPress dashboard and open VentraConnect → Providers → X (Twitter).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Paste your Client ID and Client Secret, then click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify settings to test your connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, X (Twitter) Login will be active on your site — users can log in or register using their X account.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
