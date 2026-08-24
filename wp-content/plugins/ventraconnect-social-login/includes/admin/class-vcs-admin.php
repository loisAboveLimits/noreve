<?php
namespace VentraConnect\SocialLogin\Admin;

if (!defined('ABSPATH')) {
    exit;
}

use VentraConnect\SocialLogin\Pro_Gates;
use VentraConnect\SocialLogin\Admin\Pro_Preview;
use VentraConnect\SocialLogin\Admin\Settings\FieldsRenderer;

class VCS_Admin
{
    public static function init()
    {
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function register_settings()
    {
        // Register canonical option names.
        register_setting('ventraconnect_sl_wc_settings_group', 'ventraconnect_sl_wc_settings', [__CLASS__, 'sanitize']);
        register_setting('ventraconnect_sl_comments_settings_group', 'ventraconnect_sl_comments_settings', [__CLASS__, 'sanitize_comments']);
        // Pro-only Emails & Notifications tab settings (safe to register always).
        register_setting('ventraconnect_sl_emails_settings_group', 'ventraconnect_sl_emails_settings', [__CLASS__, 'sanitize_emails']);
    }

    public static function sanitize($input)
    {
        if (function_exists('\VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_sanitize_settings')) {
            return \VentraConnect\SocialLogin\Modules\WooCommerce\ventraconnect_sl_wc_sanitize_settings((array) $input);
        }
        /**
         * Allow Pro add-on to provide sanitization logic.
         *
         * @since 1.2.0
         */
        $filtered = apply_filters('ventraconnect_sl_woocommerce_sanitize_settings', null, $input);
        if (is_array($filtered)) {
            return $filtered;
        }
        return [];
    }

    public static function render_wc_tab()
    {
        $is_pro = function_exists('ventraconnect_sl_is_pro_active') && ventraconnect_sl_is_pro_active();
        $renderer = new FieldsRenderer();

        if (!$is_pro) {
            $state = [
                'preview_only' => true,
                'settings' => [],
            ];
            Pro_Preview::render(
                [
                    'title' => __('Unlock WooCommerce Integrations', 'ventraconnect-social-login'),
                    'description' => __(
                        'Pair VentraConnect Social Login with WooCommerce checkout, cart, login, and account flows for seamless purchases.',
                        'ventraconnect-social-login'
                    ),
                    'upgrade_url' => 'https://wpventra.com/pricing/',
                ],
                function () use ($renderer, $state) {
                    $renderer->renderWooCommerceTab($state);
                }
            );
            return;
        }

        $settings = get_option('ventraconnect_sl_wc_settings', []);
        $state = [
            'preview_only' => false,
            'settings' => is_array($settings) ? $settings : [],
        ];
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('ventraconnect_sl_wc_settings_group');
            $renderer->renderWooCommerceTab($state);
            ?>
            <div class="wsc-admin wsc-savebar">
                <?php submit_button(); ?>
            </div>
            <?php
            ?>
        </form>
        <?php
    }

    public static function sanitize_comments($input)
    {
        if (function_exists('\VentraConnect\SocialLogin\Modules\Comments\ventraconnect_sl_comments_sanitize_settings')) {
            return \VentraConnect\SocialLogin\Modules\Comments\ventraconnect_sl_comments_sanitize_settings((array) $input);
        }
        $filtered = apply_filters('ventraconnect_sl_comments_sanitize_settings', null, $input);
        if (is_array($filtered)) {
            return $filtered;
        }
        return [];
    }

    public static function render_comments_tab()
    {
        $is_pro = function_exists('ventraconnect_sl_is_pro_active') && ventraconnect_sl_is_pro_active();
        $renderer = new FieldsRenderer();

        if (!$is_pro) {
            $state = [
                'preview_only' => true,
                'settings' => [],
            ];
            Pro_Preview::render(
                [
                    'title' => __('Unlock Comments Login', 'ventraconnect-social-login'),
                    'description' => __(
                        'Let visitors log in with social accounts before commenting, and control placements and helper messages on the comment form.',
                        'ventraconnect-social-login'
                    ),
                    'upgrade_url' => 'https://wpventra.com/pricing/',
                ],
                function () use ($renderer, $state) {
                    $renderer->renderCommentsTab($state);
                }
            );
            return;
        }

        $settings = get_option('ventraconnect_sl_comments_settings', []);
        $state = [
            'preview_only' => false,
            'settings' => is_array($settings) ? $settings : [],
        ];
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('ventraconnect_sl_comments_settings_group');
            $renderer->renderCommentsTab($state);
            ?>
            <div class="wsc-admin wsc-savebar">
                <?php submit_button(); ?>
            </div>
            <?php
            ?>
        </form>
        <?php
    }

    public static function sanitize_emails($input)
    {
        // Allow Pro module to provide sanitization helpers
        if (function_exists('\VentraConnect\SocialLogin\Pro\Emails\ventraconnect_sl_emails_sanitize_settings')) {
            return \VentraConnect\SocialLogin\Pro\Emails\ventraconnect_sl_emails_sanitize_settings((array) $input);
        }
        /**
         * Allow Pro add-on to provide sanitization logic via filter.
         *
         * @since 1.2.0
         */
        $filtered = apply_filters('ventraconnect_sl_emails_sanitize_settings', null, $input);
        if (is_array($filtered)) {
            return $filtered;
        }
        return [];
    }

    public static function render_emails_tab()
    {
        $is_pro = function_exists('ventraconnect_sl_is_pro_active') && ventraconnect_sl_is_pro_active();
        $renderer = new FieldsRenderer();

        if (!$is_pro) {
            $state = [
                'preview_only' => true,
                'settings' => [],
            ];
            Pro_Preview::render(
                [
                    'title' => __('Unlock Emails & Notifications', 'ventraconnect-social-login'),
                    'description' => __(
                        'Send admin and welcome emails for social registrations, and control missing email handling with a dedicated "Finish Signup" flow.',
                        'ventraconnect-social-login'
                    ),
                    'upgrade_url' => 'https://wpventra.com/pricing/',
                ],
                function () use ($renderer, $state) {
                    $renderer->renderEmailsTab($state);
                }
            );
            return;
        }

        $settings = get_option('ventraconnect_sl_emails_settings', []);
        $state = [
            'preview_only' => false,
            'settings' => is_array($settings) ? $settings : [],
        ];
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('ventraconnect_sl_emails_settings_group');
            $renderer->renderEmailsTab($state);
            ?>
            <div class="wsc-admin wsc-savebar">
                <?php submit_button(); ?>
            </div>
            <?php
            ?>
        </form>
        <?php
    }

    /**
     * Coerce Emails option for missing-email handling to '' or 'ask_user'.
     *
     * @param mixed  $value New value
     * @param mixed  $old   Old value
     * @param string $name  Option name
     * @return array
     */
    public static function filter_emails_option($value, $old, $name)
    {
        $out = is_array($value) ? $value : [];
        if (isset($_POST['ventraconnect_sl_emails_settings'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $raw_settings = wp_unslash($_POST['ventraconnect_sl_emails_settings']); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $settings = is_array($raw_settings) ? array_map('sanitize_text_field', $raw_settings) : [];
            $raw = isset($settings['handle_missing_email_action']) ? (string) $settings['handle_missing_email_action'] : '';
            $out['handle_missing_email_action'] = ('ask_user' === $raw) ? 'ask_user' : '';
        }
        return $out;
    }

    /**
     * Accept legacy WooCommerce settings POST payload and return a value suitable for
     * saving by the Settings API. This mirrors the approach used for Emails.
     *
     * @param mixed  $value New value
     * @param mixed  $old   Old value
     * @param string $name  Option name
     * @return mixed
     */
    public static function filter_wc_option($value, $old, $name)
    {
        if (isset($_POST['ventraconnect_sl_wc_settings'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $raw_settings = wp_unslash($_POST['ventraconnect_sl_wc_settings']); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $settings = is_array($raw_settings) ? array_map('sanitize_text_field', $raw_settings) : [];
            return $settings;
        }
        return $value;
    }

    /**
     * Accept legacy Comments settings POST payload and return a value suitable for saving.
     *
     * @param mixed  $value New value
     * @param mixed  $old   Old value
     * @param string $name  Option name
     * @return mixed
     */
    public static function filter_comments_option($value, $old, $name)
    {
        if (isset($_POST['ventraconnect_sl_comments_settings'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $raw_settings = wp_unslash($_POST['ventraconnect_sl_comments_settings']); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $settings = is_array($raw_settings) ? array_map('sanitize_text_field', $raw_settings) : [];
            return $settings;
        }
        return $value;
    }

    /**
     * Upsell helper used across admin tabs.
     */
    private static function render_upsell_card(string $title, string $description, string $url): void
    {
        echo '<div class="wsc-card ventraconnect-sl-integrations-upsell">';
        if ('' !== $title) {
            echo '<h3>' . esc_html($title) . '</h3>';
        }
        if ('' !== $description) {
            echo '<p class="wsc-muted">' . esc_html($description) . '</p>';
        }
        if ('' !== $url) {
            echo '<a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url($url) . '">' . esc_html__('Upgrade to Pro', 'ventraconnect-social-login') . '</a>';
        }
        echo '</div>';
    }

    private static function render_wc_preview(): void
    {
        echo '<div class="wsc-card">';
        echo '<h3>' . esc_html__('WooCommerce Checkout Buttons', 'ventraconnect-social-login') . '</h3>';
        echo '<p class="wsc-muted">' . esc_html__('Add VentraConnect login methods to WooCommerce checkout, cart, login, and account areas with the Pro version.', 'ventraconnect-social-login') . '</p>';
        echo '<ul class="wsc-list">';
        echo '<li>' . esc_html__('Choose placements for checkout, registration, and account forms.', 'ventraconnect-social-login') . '</li>';
        echo '<li>' . esc_html__('Auto-link accounts by email and control post-login redirects.', 'ventraconnect-social-login') . '</li>';
        echo '<li>' . esc_html__('Display tailored helper messages during checkout.', 'ventraconnect-social-login') . '</li>';
        echo '</ul>';
        echo '<p><a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url(Pro_Gates::upsell_link('woocommerce-tab')) . '">' . esc_html__('Upgrade to Pro', 'ventraconnect-social-login') . '</a></p>';
        echo '</div>';
    }

    private static function render_comments_preview(): void
    {
        echo '<div class="wsc-card">';
        echo '<h3>' . esc_html__('Comment Form Buttons', 'ventraconnect-social-login') . '</h3>';
        echo '<p class="wsc-muted">' . esc_html__('Encourage discussion by letting visitors sign in with Google, Facebook, and other providers right from the comment form.', 'ventraconnect-social-login') . '</p>';
        echo '<ul class="wsc-list">';
        echo '<li>' . esc_html__('Place buttons above or below the comment form.', 'ventraconnect-social-login') . '</li>';
        echo '<li>' . esc_html__('Control anonymous posting and native login visibility.', 'ventraconnect-social-login') . '</li>';
        echo '<li>' . esc_html__('Show provider badges beside commenter avatars.', 'ventraconnect-social-login') . '</li>';
        echo '</ul>';
        echo '<p><a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url(Pro_Gates::upsell_link('comments-tab')) . '">' . esc_html__('Upgrade to Pro', 'ventraconnect-social-login') . '</a></p>';
        echo '</div>';
    }

    /**
     * Handle manual flush of rewrite rules from the Emails tab.
     */
    public static function handle_emails_flush()
    {
        $flag = isset($_GET['ventraconnect_sl_flush_emails_rewrites']) ? sanitize_text_field(wp_unslash((string) $_GET['ventraconnect_sl_flush_emails_rewrites'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ('1' !== $flag) {
            return;
        }
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash((string) $_GET['_wpnonce'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $verified = wp_verify_nonce($nonce, 'ventraconnect_sl_flush_emails_rewrites');
        if (!current_user_can('manage_options') || !$verified) {
            return;
        }
        if (function_exists('flush_rewrite_rules')) {
            flush_rewrite_rules(false);
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateOption('ventraconnect_sl_emails_rewrite_version', 1);
        }
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::setTransient('ventraconnect_sl_admin_notice', [
            'type' => 'success',
            'message' => __('Rewrite rules refreshed for the Finish Signup endpoint.', 'ventraconnect-social-login'),
        ], MINUTE_IN_SECONDS * 2);
        // Redirect back without query args
        $url = remove_query_arg(['ventraconnect_sl_flush_emails_rewrites', '_wpnonce']);
        wp_safe_redirect($url ? $url : admin_url('admin.php?page=ventraconnect-sl-settings'));
        exit;
    }
}
