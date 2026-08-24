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
<h4><?php echo esc_html__( 'Microsoft Getting Started Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><?php echo esc_html__( 'To enable Microsoft Login in VentraConnect Social Login, you need to create an app in Microsoft Entra (Azure).', 'ventraconnect-social-login' ); ?></li>
    <li><a target="_blank" rel="noopener" href="https://entra.microsoft.com/">https://entra.microsoft.com/</a> <?php echo esc_html__( 'and sign in with your Microsoft account.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Navigate to Entra ID → App registrations, then click New registration.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Enter a name for your app.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Under Supported account types, choose who can use the login (e.g., single tenant, multi-tenant, or personal Microsoft accounts).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the Redirect URI field, paste the redirect URL shown at the bottom of these steps.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Register to create your app.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Copy the Application (Client) ID - this will be your Client ID.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'From the left menu, go to Certificates & Secrets, then click New client secret.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Add a description and expiration period, then click Add.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Copy the Client Secret value immediately (it won’t be shown again).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Go to API Permissions → Add a permission → Microsoft Graph → Delegated permissions, then select User.Read.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Grant admin consent if required.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In your WordPress dashboard, open VentraConnect → Providers → Microsoft.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Paste your Client ID and Client Secret, then click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify to test your connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, users can log in or register on your site using their Microsoft account.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
