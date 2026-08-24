<?php
namespace VentraConnect\SocialLogin\Admin\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Persistence helpers for options used by the Settings UI.
 *
 * Provides a thin abstraction around get_option/update_option for
 * settings and related mirror options to keep logic centralized.
 */
class Persistence
{
    /**
     * Internal default settings (read-time only; never written).
     * Mirrors install-time defaults and common keys to avoid undefined indexes.
     *
     * @return array<string,mixed>
     */
    private static function getDefaultSettings(): array
    {
        return [
            'wp_login_enabled'                  => true,
            'wp_register_enabled'               => true,
            'comments_enabled'                  => false,
            'allow_core_login_account_creation' => true,
            'passwordless_mode'                 => 'off',
            'providers'                         => [],
            'button_style'                      => 'wide',
            'use_popup_oauth'                   => false,
            'debug_mode'                        => false,
            'integration_debug'                 => false,
            'prevent_external_override'         => true,
            'redirect_default_login'            => '',
            'redirect_default_register'         => '',
            'redirect_blacklist'                => [],
            'privacy_notice_ack'                => false,
            'passwordless_email_branding'      => [
                'enabled'      => 0,
                'logo_url'     => '',
                'accent_color' => '',
                'footer_text'  => '',
            ],
            // Token providers (defaults safe even if Pro inactive)
            'magic_link' => [
                'expiry' => 10,
                'single_use' => 0,
                'require_same_ip' => 0,
                'resend_throttle' => 60,
                'redirect_override' => 0,
                'redirect_mode' => 'same_page',
                'redirect_url' => '',
                'email_sender' => '',
                'email_subject' => '',
                'email_body' => '',
                'registration_mode' => 'login_and_register',
                'or_separator' => 'none',
            ],
            'otp_email' => [
                'code_length' => 6,
                'expiry' => 10,
                'resend_throttle' => 60,
                'max_attempts' => 5,
                'redirect_override' => 0,
                'redirect_mode' => 'same_page',
                'redirect_url' => '',
                'email_sender' => '',
                'email_subject' => '',
                'email_body' => '',
                'registration_mode' => 'login_and_register',
                'or_separator' => 'none',
            ],
            'passkey' => [
                'registration_mode' => 'login_and_register',
                'redirect_override' => 0,
                'redirect_mode' => 'same_page',
                'redirect_url' => '',
                'or_separator' => 'none',
                'show_helper_text' => 1,
                'login_helper_text' => '',
                'register_helper_text' => '',
                'floating_panel_enabled' => 0,
                'floating_panel_pages' => [],
                'floating_panel_title' => '',
                'floating_panel_message' => '',
                'floating_panel_button_text' => '',
                'floating_panel_position' => 'bottom_right',
                'floating_panel_delay' => 3,
                'floating_panel_snooze_days' => 7,
            ],
        ];
    }

    /**
     * Deep merge stored settings into defaults (stored wins per key).
     *
     * @param array<string,mixed> $defaults Base defaults.
     * @param array<string,mixed> $stored   Stored settings from the database.
     * @return array<string,mixed>
     */
    private static function deep_merge_settings(array $defaults, array $stored): array
    {
        foreach ($stored as $key => $value) {
            if (is_array($value) && isset($defaults[$key]) && is_array($defaults[$key])) {
                $defaults[$key] = self::deep_merge_settings($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }
        return $defaults;
    }
    /**
     * Fetch the main settings array.
     *
     * @return array<string,mixed>
     */
    public static function getSettings(): array
    {
        $defaults = self::getDefaultSettings();
        $stored = get_option('ventraconnect_sl_settings', []);

        if (!is_array($stored)) {
            $stored = [];
        }

        return self::deep_merge_settings($defaults, $stored);
    }

    /**
     * Save the main settings array.
     *
     * @param array<string,mixed> $settings
     */
    public static function saveSettings(array $settings): void
    {
        update_option('ventraconnect_sl_settings', $settings);
    }

    /**
     * Button style mirror (compat with external consumers/tests).
     */
    public static function setButtonStyle(string $style): void
    {
        if (false === get_option('ventraconnect_sl_button_style', false)) {
            add_option('ventraconnect_sl_button_style', $style, '', 'no');
        }
        update_option('ventraconnect_sl_button_style', $style);
    }

    /**
     * Global theme mirror (non-autoloaded).
     */
    public static function setGlobalTheme(string $theme): void
    {
        if (false === get_option('ventraconnect_sl_global_theme', false)) {
            add_option('ventraconnect_sl_global_theme', $theme, '', 'no');
        }
        update_option('ventraconnect_sl_global_theme', $theme);
    }

    public static function getGlobalTheme(string $fallback = 'light'): string
    {
        $val = self::getOption('ventraconnect_sl_global_theme', $fallback);
        return is_string($val) ? $val : $fallback;
    }

    /**
     * Per-provider mirrors: theme, override flag, and text.
     */
    public static function setProviderTheme(string $slug, string $theme): void
    {
        $key = 'ventraconnect_sl_provider_' . sanitize_key($slug) . '_theme';
        if (false === get_option($key, false)) {
            add_option($key, $theme, '', 'no');
        }
        update_option($key, $theme);
    }

    public static function setProviderThemeOverride(string $slug, int $value): void
    {
        $key = 'ventraconnect_sl_provider_' . sanitize_key($slug) . '_theme_override';
        if (false === get_option($key, false)) {
            add_option($key, $value, '', 'no');
        }
        update_option($key, $value);
    }

    public static function setProviderText(string $slug, string $text): void
    {
        $key = 'ventraconnect_sl_provider_' . sanitize_key($slug) . '_text';
        if (false === get_option($key, false)) {
            add_option($key, $text, '', 'no');
        }
        update_option($key, $text);
    }

    /**
     * Helper getters for frequent reads with baked-in defaults.
     */
    public static function getProviderTheme(string $slug, string $fallback = 'inherit'): string
    {
        $key = 'ventraconnect_sl_provider_' . sanitize_key($slug) . '_theme';
        $val = self::getOption($key, $fallback);
        return is_string($val) ? $val : $fallback;
    }

    public static function getProviderThemeOverride(string $slug, int $fallback = 0): int
    {
        $key = 'ventraconnect_sl_provider_' . sanitize_key($slug) . '_theme_override';
        $val = self::getOption($key, $fallback);
        return is_numeric($val) ? (int) $val : (int) $fallback;
    }

    public static function getProviderText(string $slug, string $fallback = ''): string
    {
        $key = 'ventraconnect_sl_provider_' . sanitize_key($slug) . '_text';
        $val = self::getOption($key, $fallback);
        return is_string($val) ? $val : $fallback;
    }

    /**
     * Provider credentials are stored separately in a non-autoloaded option.
     *
     * @return array<string,array<string,string>>
     */
    public static function getProviderCreds(): array
    {
        $existing = self::getOption('ventraconnect_sl_provider_creds', false);
        if (false === $existing) {
            add_option('ventraconnect_sl_provider_creds', [], '', 'no');
            $existing = [];
        }
        return (array) $existing;
    }

    /**
     * Persist provider credentials array as-is.
     *
     * @param array<string,array<string,string>> $creds
     */
    public static function saveProviderCreds(array $creds): void
    {
        update_option('ventraconnect_sl_provider_creds', $creds);
    }

    /**
     * Generic wrappers (used for ancillary settings)
     */
    /**
     * Get saved provider order for the Providers dashboard.
     *
     * @return array<int,string>
     */
    public static function getProviderOrder(): array
    {
        $val = get_option('ventraconnect_sl_provider_order', null);
        if ($val === null || $val === false) {
            $val = get_option('vc_provider_order', []);
        }
        return (array) $val;
    }

    /**
     * Persist provider order array as-is.
     *
     * @param array<int,string> $order
     */
    public static function setProviderOrder(array $order): void
    {
        update_option('ventraconnect_sl_provider_order', array_values($order));
    }
    public static function updateOption(string $key, $value): void
    { // phpcs:ignore
        update_option($key, $value);
    }

    public static function getOption(string $key, $default = null)
    { // phpcs:ignore
        return get_option($key, $default);
    }

    public static function getTransient(string $key, $default = null)
    { // phpcs:ignore
        $val = get_transient($key);
        return $val === false ? $default : $val;
    }

    public static function setTransient(string $key, $value, int $expiration = 0): void
    {
        set_transient($key, $value, $expiration);
    }

    public static function deleteTransient(string $key): void
    {
        delete_transient($key);
    }

    public static function getUserMeta(int $user_id, string $key, $single = true, $default = null)
    { // phpcs:ignore
        if (metadata_exists('user', $user_id, $key)) {
            return get_user_meta($user_id, $key, $single);
        }
        return $default;
    }

    public static function updateUserMeta(int $user_id, string $key, $value): void
    { // phpcs:ignore
        update_user_meta($user_id, $key, $value);
    }

    public static function deleteUserMeta(int $user_id, string $key): void
    {
        delete_user_meta($user_id, $key);
    }
}
