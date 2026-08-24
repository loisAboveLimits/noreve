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
<h4><?php echo esc_html__( 'Facebook Getting Started Steps', 'ventraconnect-social-login' ); ?></h4>
<ol class="wsc-steps">
    <li><?php echo esc_html__( 'To enable Facebook Login in VentraConnect Social Login, you need to create a Facebook App first.', 'ventraconnect-social-login' ); ?></li>
    <li><a target="_blank" rel="noopener" href="https://developers.facebook.com/apps/">https://developers.facebook.com/apps/</a> <?php echo esc_html__( 'and log in with your Facebook account.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Create App, then choose Authenticate and request data from users with Facebook Login and click Next.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Enter your App Name and Contact Email, then click Next again.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'On the overview screen, click Go to Dashboard and complete any security check if prompted.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the left menu, click Use cases, find Authenticate and request data from users with Facebook Login, and click Customize.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Under Permissions, find email and click Add.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'From the left sidebar, go to Facebook Login → Settings.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In the Valid OAuth Redirect URIs field, paste the redirect URL shown in your VentraConnect Facebook settings, then click Save changes.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click the gear icon → App Settings → Basic.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo sprintf(
        /* translators: 1: Link to the main site domain. */
        esc_html__( 'In App Domains, enter your main domain: %1$s.', 'ventraconnect-social-login' ),
        '<a href="' . esc_url( $domain ) . '" target="_blank" rel="noopener">' . esc_html( $domain ) . '</a>'
    ); ?></li>
    <li><?php echo esc_html__( 'Add your Privacy Policy URL, Terms of Service URL, and Data Deletion Instructions URL (pointing to a page that explains how users can delete their accounts).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Choose an App Category and upload an App Icon.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Scroll down and click + Add Platform → Website, then add your site URL (the same one used in VentraConnect).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Save Changes.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'To make your app public, you must verify your business. Go to Review → Verification and complete Business Verification.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'After verification, go to Review → App Review, open your app, fill in any missing details, and submit for review.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once approved, open the Publish tab, answer the required Data Handling Questions, then click the Publish button to go live.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Finally, go back to App Settings → Basic and copy your App ID and App Secret (click Show to view it).', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'In your WordPress dashboard, open VentraConnect → Providers → Facebook.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Paste your App ID and App Secret in their fields and click Save.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Click Verify Settings in the Facebook settings tab to confirm the connection.', 'ventraconnect-social-login' ); ?></li>
    <li><?php echo esc_html__( 'Once verified, Facebook Login will be active on your site.', 'ventraconnect-social-login' ); ?></li>
</ol>
<p class="description wsc-small"><?php echo esc_html__( 'Valid OAuth Redirect URI', 'ventraconnect-social-login' ); ?>:</p>
<div class="wsc-row"><code id="wsc-redirect-provider"><?php echo esc_html( $redirect ); ?></code> <button type="button" class="button wsc-copy" data-copy="#wsc-redirect-provider" data-label="<?php echo esc_attr__( 'Copy', 'ventraconnect-social-login' ); ?>" data-copied-label="<?php echo esc_attr__( 'Copied', 'ventraconnect-social-login' ); ?>"><?php echo esc_html__( 'Copy', 'ventraconnect-social-login' ); ?></button></div>
