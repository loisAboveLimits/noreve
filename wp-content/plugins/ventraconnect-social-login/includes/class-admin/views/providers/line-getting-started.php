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
<h4><?php echo esc_html__( 'LINE Getting Started Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><?php echo esc_html__( 'To enable LINE Login in VentraConnect Social Login, you need to create a LINE app first.', 'ventraconnect-social-login' ); ?></li>
    <li><a target="_blank" rel="noopener" href="https://developers.line.biz/console/">LINE Developers Console</a> <?php echo esc_html__( 'and sign in with your LINE Business account.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Create a new provider.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Enter your Provider Name and click Create.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Under the Channels section, click Create a LINE Login channel.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Make sure LINE Login is selected as the Channel type.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'For Provider, select the provider you just created.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Choose your Region.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Add your Channel Icon, Channel Name, and Channel Description, these appear on the consent screen when users log in.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Under App types, select Web app.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Read and agree to the LINE Developers Agreement, then click Create.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Scroll down to the OpenID Connect section and click Apply next to Email address permission.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Fill out the short form and click Submit.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Scroll up and open the LINE Login section.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the Callback URL field, paste the redirect URL shown at the end of these steps.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Under your app name, click the Developing button and select Publish to make your channel live.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Go to the Basic settings tab and copy your Channel ID and Channel Secret.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo sprintf(
        /* translators: 1: Link to the site URL. */
        esc_html__( 'If LINE prompts for your site URL, enter %1$s.', 'ventraconnect-social-login' ),
        '<a href="' . esc_url( $domain ) . '" target="_blank" rel="noopener">' . esc_html( $domain ) . '</a>'
    ); ?></li>
    <li><?php echo esc_html__( 'In your WordPress dashboard, open VentraConnect > Providers > LINE.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Paste your Channel ID and Channel Secret, then click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify Settings to test your connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, users can log in or register on your site using their LINE account.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
