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
<h4><?php echo esc_html__( 'GitHub Getting Started Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><?php echo esc_html__( 'To enable GitHub Login in VentraConnect Social Login, you need to create a GitHub app first.', 'ventraconnect-social-login' ); ?></li>
    <li><a target="_blank" rel="noopener" href="https://github.com/settings/developers">https://github.com/settings/developers</a> <?php echo esc_html__( 'and sign in with your GitHub account.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Make sure the OAuth Apps tab is selected.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Register a new application.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the Application name field, enter a name for your app (this will be shown to users during login).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo sprintf(
        /* translators: 1: Link to the homepage URL. */
        esc_html__( 'In the Homepage URL field, enter your website’s main URL: %1$s.', 'ventraconnect-social-login' ),
        '<a href="' . esc_url( $domain ) . '" target="_blank" rel="noopener">' . esc_html( $domain ) . '</a>'
    ); ?></li>
    <li><?php echo esc_html__( 'In the Description field, briefly describe what your app does (e.g., “Allows users to log in using their GitHub account”).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the Authorization callback URL field, paste the redirect URL shown at the bottom of these steps.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Register application.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'On the next page, copy your Client ID and Client Secret (click Generate a new client secret if you don’t see one).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Go to your WordPress dashboard and open VentraConnect → Providers → GitHub.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Paste your Client ID and Client Secret, then click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify to test your connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, users can log in or register on your site using their GitHub account.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
