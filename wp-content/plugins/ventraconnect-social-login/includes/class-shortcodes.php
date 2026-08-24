<?php
namespace VentraConnect\SocialLogin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Free shortcode: [ventraconnect_sl_social_login]
 * Usage:
 *  - [ventraconnect_sl_social_login providers="all"]
 *  - [ventraconnect_sl_social_login providers="google,facebook"]
 *  - [ventraconnect_sl_social_login redirect_to="/my-account/"]
 */
class Shortcodes
{
    /**
     * Redirect target for the current shortcode render.
     *
     * @var string
     */
    protected static $current_redirect_to = '';

    public static function init()
    {
        // Register on init so Free overrides any prior registration from Pro.
        add_action(
            'init',
            function () {
                add_shortcode(
                    'ventraconnect_sl_social_login',
                    array(__CLASS__, 'handle')
                );

                add_filter(
                    'ventraconnect_sl_oauth_state_extra',
                    array(__CLASS__, 'filter_state_extra'),
                    10,
                    3
                );
            },
            20
        );
    }

    /**
     * Render shortcode output.
     */
    public static function handle($atts = [])
    {
        // If user is logged in, render a contextual identity message instead of buttons
        if (is_user_logged_in()) {
            $uid = get_current_user_id();
            $provider = '';
            if ($uid > 0 && class_exists(__NAMESPACE__ . '\\User_Links')) {
                $provider = (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta($uid, User_Links::META_PRIMARY, true, '');
                $provider = sanitize_key($provider);
            }
            if ('' !== $provider) {
                // Prefer Woo helper renderer if available for consistent visuals
                if (class_exists('\\VentraConnect\\SocialLogin\\Modules\\WooCommerce\\VCS_WC') && method_exists('\\VentraConnect\\SocialLogin\\Modules\\WooCommerce\\VCS_WC', 'render_logged_in_via_provider')) {
                    return \VentraConnect\SocialLogin\Modules\WooCommerce\VCS_WC::render_logged_in_via_provider($provider);
                }
                // Fallback: simple message with optional icon URL from Buttons icon resolver
                $icon_url = '';
                if (class_exists(__NAMESPACE__ . '\\Buttons')) {
                    $src = Buttons::resolve_icon_source($provider, 'compact', 'light');
                    if (is_array($src) && !empty($src['url'])) {
                        $icon_url = (string) $src['url'];
                    }
                }
                $name = ucfirst($provider);
                $img = $icon_url !== '' ? '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($name) . '" class="vcs-provider-icon" /> ' : '';
                return '<div class="vcs-logged-in-msg">' . $img . '<span>' . esc_html__("You're logged in via", 'ventraconnect-social-login') . ' <strong>' . esc_html($name) . '</strong>.</span></div>';
            }
            return '<div class="vcs-logged-in-msg"><span>' . esc_html__("You're logged in.", 'ventraconnect-social-login') . '</span></div>';
        }

        $atts = shortcode_atts(
            array(
                'providers' => '',
                'context' => 'login', // default to core login context for Free-safe behaviour
                'redirect_to' => '',
                'show_labels' => 'true',
            ),
            $atts,
            'ventraconnect_sl_social_login'
        );

        // Reset redirect override at the start of each render to avoid leakage between shortcodes.
        self::$current_redirect_to = '';

        // Normalize context for downstream integrations (e.g. learndash_login).
        $context = sanitize_key((string) $atts['context']);
        if ( 'login' === $context && function_exists( 'is_checkout' ) && is_checkout() ) {
            $context = 'checkout';
        } elseif ( 'login' === $context && function_exists( 'is_account_page' ) && is_account_page() ) {
            $context = 'wc_login';
        }
        $atts['context'] = $context;

        $selected = array_filter(array_map('trim', explode(',', (string) $atts['providers'])));
        if ($selected && count($selected) === 1 && strtolower($selected[0]) === 'all') {
            $selected = []; // treat as no filter: use all active providers
        }

        $settings = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $enabled = (array) ($settings['providers'] ?? []); // free stores list of slugs
        // If a subset is requested, intersect with enabled list
        if (!empty($selected)) {
            $sel_lc = array_map('strtolower', $selected);
            $enabled = array_values(array_filter($enabled, function ($slug) use ($sel_lc) {
                return in_array(strtolower($slug), $sel_lc, true);
            }));
        }
        $pro_active = self::is_pro_active();
        $advanced_passkeys_available = self::advanced_passkeys_available();
        $integration_surface_reason = self::get_integration_owned_shortcode_surface_reason();
        $is_integration_surface = ('none' !== $integration_surface_reason);
        if ($is_integration_surface && !$pro_active && 'pmpro_page_login' !== $integration_surface_reason) {
            $enabled = array();
        } elseif ($is_integration_surface && $pro_active && !$advanced_passkeys_available) {
            $enabled = self::remove_passkey_from_providers($enabled);
        }
        if (empty($enabled)) {
            self::$current_redirect_to = '';
            return '';
        }
        // Build override settings for Buttons renderer
        $override = $settings;
        $override['providers'] = $enabled;
        $show_labels = filter_var($atts['show_labels'], FILTER_VALIDATE_BOOLEAN);
        if (!$show_labels) {
            $override['button_style'] = 'compact';
        }
        $redirect_raw = esc_url_raw((string) $atts['redirect_to']);
        if ('' !== $redirect_raw) {
            self::$current_redirect_to = $redirect_raw;
        }

        $buttons = new Buttons($override);
        ob_start();

        // Surface core "new account blocked" error above shortcode-based login blocks.
        $code = '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only error code from URL for display; no state change occurs.
        if ( isset( $_GET['ventraconnect_sl_err'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Verified above; logic requires reading param for display.
            $code = sanitize_text_field( wp_unslash( (string) $_GET['ventraconnect_sl_err'] ) );
        }

        if ('core_new_account_blocked' === $code) {
            echo '<div class="notice notice-error ventraconnect-sl-message">';
            echo '<p>' . esc_html__(
                'You can’t create a new account with social login on this screen. Please register using the site’s sign-up form first, then sign in with your social account.',
                'ventraconnect-social-login'
            ) . '</p>';
            echo '</div>';
        }

        $buttons->render($atts['context']);
        $html = (string) ob_get_clean();

        // Always clear after rendering.
        self::$current_redirect_to = '';

        return $html;
    }

    /**
     * Inject shortcode-level redirect_to into OAuth state extras.
     *
     * @param array  $extras   Current state extras.
     * @param string $provider Provider slug.
     * @param string $context  Render context.
     * @return array
     */
    public static function filter_state_extra($extras, $provider, $context)
    { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        if (!is_array($extras)) {
            $extras = array();
        }
        if (!empty(self::$current_redirect_to) && empty($extras['redirect_to'])) {
            $extras['redirect_to'] = self::$current_redirect_to;
        }
        return $extras;
    }

    /**
     * Determine whether Pro-bundled advanced passkey integrations are available.
     */
    private static function advanced_passkeys_available(): bool
    {
        if (!self::is_pro_active()) {
            return false;
        }

        if (!defined('VENTRACONNECT_PASSKEYS_CORE_SUPPORTED') || !VENTRACONNECT_PASSKEYS_CORE_SUPPORTED) {
            return false;
        }

        if (!class_exists('VentraConnect_SL_Passkeys_Core_Passkey_Repository', false)) {
            return false;
        }

        return defined('VENTRACONNECT_PRO_PASSKEYS_VERSION')
            || class_exists('\VentraConnect\SocialLogin\Pro\Passkeys\Loader', false);
    }

    /**
     * Determine whether Pro is active using the canonical runtime signal.
     */
    private static function is_pro_active(): bool
    {
        if (function_exists('\vcsl_is_pro_active')) {
            return (bool) \vcsl_is_pro_active();
        }

        return defined('VCS_PRO_ACTIVE') && VCS_PRO_ACTIVE === true;
    }

    /**
     * Remove passkey from a provider list while preserving all other providers.
     *
     * @param array $providers
     * @return array
     */
    private static function remove_passkey_from_providers(array $providers): array
    {
        return array_values(array_filter($providers, function ($provider) {
            return 'passkey' !== strtolower((string) $provider);
        }));
    }

    /**
     * Detect plugin-owned login/register/account/checkout/profile surfaces where
     * Free shortcode passkey fallback should not run without Pro advanced passkey integrations.
     */
    private static function is_integration_owned_shortcode_surface(): bool
    {
        return 'none' !== self::get_integration_owned_shortcode_surface_reason();
    }

    /**
     * Resolve the matched integration-owned shortcode surface reason, if any.
     */
    private static function get_integration_owned_shortcode_surface_reason(): string
    {
        if (is_admin()) {
            return 'none';
        }

        if (function_exists('is_account_page') && is_account_page()) {
            return 'woocommerce_account';
        }

        if (function_exists('is_checkout') && is_checkout()) {
            return 'woocommerce_checkout';
        }

        if (function_exists('llms_is_account_page') && llms_is_account_page()) {
            return 'lifterlms_account_conditional';
        }

        if (function_exists('llms_get_page_id')) {
            $llms_account_page_id = (int) llms_get_page_id('myaccount');
            if ($llms_account_page_id > 0 && get_queried_object_id() === $llms_account_page_id) {
                return 'lifterlms_myaccount_page_id';
            }
            $llms_checkout_page_id = (int) llms_get_page_id('checkout');
            if ($llms_checkout_page_id > 0 && get_queried_object_id() === $llms_checkout_page_id) {
                return 'lifterlms_checkout_page_id';
            }
        }

        if (function_exists('learn_press_is_profile') && learn_press_is_profile()) {
            return 'learnpress_profile_conditional';
        }

        if (function_exists('learn_press_is_checkout') && learn_press_is_checkout()) {
            return 'learnpress_checkout_conditional';
        }

        if (function_exists('learn_press_get_page_id')) {
            $learnpress_profile_page_id = absint(learn_press_get_page_id('profile'));
            if ($learnpress_profile_page_id > 0 && get_queried_object_id() === $learnpress_profile_page_id) {
                return 'learnpress_profile_page_id';
            }

            $learnpress_checkout_page_id = absint(learn_press_get_page_id('checkout'));
            if ($learnpress_checkout_page_id > 0 && get_queried_object_id() === $learnpress_checkout_page_id) {
                return 'learnpress_checkout_page_id';
            }
        }

        $post_id = get_queried_object_id();
        if ($post_id && function_exists('metadata_exists') && metadata_exists('post', $post_id, '_mepr_manual_login_form')) {
            return 'memberpress_manual_login_form_meta';
        }

        if (function_exists('pmpro_is_checkout') && pmpro_is_checkout()) {
            return 'pmpro_checkout';
        }

        if (function_exists('pmpro_url')) {
            foreach (array('account', 'billing', 'checkout', 'confirmation', 'invoice', 'levels', 'login', 'member_profile') as $pmpro_page) {
                $pmpro_url = (string) pmpro_url($pmpro_page);
                if ('' !== $pmpro_url) {
                    $pmpro_page_id = url_to_postid($pmpro_url);
                    if ($pmpro_page_id > 0 && get_queried_object_id() === $pmpro_page_id) {
                        return 'pmpro_page_' . $pmpro_page;
                    }
                }
            }
        }

        if (function_exists('bp_is_register_page') && bp_is_register_page()) {
            return 'buddypress_register';
        }

        if (function_exists('bp_is_user') && bp_is_user()) {
            return 'buddypress_user';
        }

        if (function_exists('um_get_core_page')) {
            foreach (array('login', 'register', 'account', 'user') as $um_page) {
                $um_url = (string) um_get_core_page($um_page);
                if ('' !== $um_url) {
                    $um_page_id = url_to_postid($um_url);
                    if ($um_page_id > 0 && get_queried_object_id() === $um_page_id) {
                        return 'ultimate_member_page_' . $um_page;
                    }
                }
            }
        }

        if ($post_id <= 0) {
            return 'none';
        }

        $post = get_post($post_id);
        $content = ($post instanceof \WP_Post) ? (string) $post->post_content : '';
        if ('' === $content) {
            return 'none';
        }

        $content_lc = strtolower($content);

        $pms_shortcode_reasons = array(
            'pms-login'            => 'pms_login_shortcode',
            'pms-register'         => 'pms_register_shortcode',
            'pms-account'          => 'pms_account_shortcode',
            'pms-recover-password' => 'pms_recover_password_shortcode',
            'pms-subscriptions'    => 'pms_subscriptions_shortcode',
        );

        foreach ($pms_shortcode_reasons as $shortcode_tag => $reason) {
            if (has_shortcode($content, $shortcode_tag)) {
                return $reason;
            }
        }

        $tutor_shortcode_reasons = array(
            'tutor_student_registration_form'    => 'tutor_student_registration_shortcode',
            'tutor_instructor_registration_form' => 'tutor_instructor_registration_shortcode',
            'tutor_dashboard'                    => 'tutor_dashboard_shortcode',
        );

        foreach ($tutor_shortcode_reasons as $shortcode_tag => $reason) {
            if (has_shortcode($content, $shortcode_tag)) {
                return $reason;
            }
        }

        $shortcodes = array(
            'lifterlms_my_account',
            'learndash_login',
            'ld_profile',
            'ld_registration',
            'mepr-login-form',
            'mepr-account-form',
            'mepr-membership-registration-form',
            'pmpro_account',
            'pmpro_billing',
            'pmpro_checkout',
            'pmpro_levels',
            'ultimatemember',
        );

        foreach ($shortcodes as $shortcode_tag) {
            if (shortcode_exists($shortcode_tag) && has_shortcode($content, $shortcode_tag)) {
                return $shortcode_tag . '_shortcode_detected';
            }
        }

        if (
            (shortcode_exists('lifterlms_login') && has_shortcode($content, 'lifterlms_login'))
            || false !== stripos($content, '[lifterlms_login')
        ) {
            return 'lifterlms_login_shortcode';
        }

        if (
            (shortcode_exists('lifterlms_registration') && has_shortcode($content, 'lifterlms_registration'))
            || false !== stripos($content, '[lifterlms_registration')
        ) {
            return 'lifterlms_registration_shortcode';
        }

        if (shortcode_exists('learn_press_checkout') && has_shortcode($content, 'learn_press_checkout')) {
            return 'learnpress_checkout_shortcode';
        }

        $learndash_markers = array(
            'learndash_login',
            'ld_profile',
            'ld_registration',
            'wp:learndash/',
            'learndash/login',
            'learndash-login',
            'learndash_profile',
            'learndash-profile',
            'ld-login',
            'ld-profile',
        );

        foreach ($learndash_markers as $marker) {
            if (false !== strpos($content_lc, strtolower($marker))) {
                return 'learndash_marker_' . sanitize_key($marker);
            }
        }

        return 'none';
    }

}
