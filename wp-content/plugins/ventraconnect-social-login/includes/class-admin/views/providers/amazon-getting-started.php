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
<h4><?php echo esc_html__( 'Amazon Getting Started Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><?php echo esc_html__( 'To enable Amazon Login in VentraConnect Social Login, you need to create an Amazon app first.', 'ventraconnect-social-login' ); ?></li>
    <li><a target="_blank" rel="noopener" href="https://developer.amazon.com/lwa/sp/overview.html">https://developer.amazon.com/lwa/sp/overview.html</a> <?php echo esc_html__( 'and sign in with your Amazon account.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'If you don’t have a Security Profile yet, click the orange Create a New Security Profile button on the left side.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Fill in the following fields:', 'ventraconnect-social-login' ); ?></li>
    <ul>
        <li><?php echo esc_html__( 'Security Profile Name', 'ventraconnect-social-login' ); ?></li>
        <li><?php echo esc_html__( 'Security Profile Description', 'ventraconnect-social-login' ); ?></li>
        <li><?php echo esc_html__( 'Consent Privacy Notice URL (link to your site’s privacy policy)', 'ventraconnect-social-login' ); ?></li>
    </ul>
    <li><?php echo esc_html__( 'Click Save once all fields are filled.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'On the right side, under Manage, hover over the gear icon and select Web Settings.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Edit.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo sprintf(
        /* translators: 1: Link to the site homepage URL. */
        esc_html__( 'In Allowed Origins, enter your website homepage URL: %1$s.', 'ventraconnect-social-login' ),
        '<a href="' . esc_url( $domain ) . '" target="_blank" rel="noopener">' . esc_html( $domain ) . '</a>'
    ); ?></li>
    <li><?php echo esc_html__( 'In Allowed Return URLs, paste the redirect URL shown in your VentraConnect → Providers → Amazon settings.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Save to apply the changes.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Under the Web Settings section, locate your Client ID and Client Secret — you’ll need these for VentraConnect.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In your WordPress dashboard, open VentraConnect → Providers → Amazon.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Paste your Client ID and Client Secret, then click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify Settings to test your connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, users can log in or register on your site using their Amazon account.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
<p class="description wsc-small" style="margin-top:8px;"><?php echo esc_html__( 'Allowed Origins:', 'ventraconnect-social-login' ); ?></p>
<div class="wsc-row"><code><?php echo esc_html( $domain ); ?></code></div>
