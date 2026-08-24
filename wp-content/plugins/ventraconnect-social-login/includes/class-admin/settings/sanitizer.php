<?php
namespace VentraConnect\SocialLogin\Admin\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pure sanitizer for settings payloads.
 *
 * IMPORTANT: This class must not perform writes. It only returns the
 * sanitized settings array, based on incoming input and existing values.
 */
class Sanitizer
{
    /**
     * Sanitize all settings. No writes here.
     *
     * @param array<string,mixed> $input    Raw incoming settings
     * @param array<string,mixed> $existing Existing stored settings (ventraconnect_sl_settings)
     * @return array<string,mixed>          Sanitized settings array to be stored
     */
    public static function sanitizeAll(array $input, array $existing): array
    {
        // Start from existing to preserve unspecified values
        $out = (array) $existing;

        // Providers list
        if (isset($input['providers'])) {
            $providers = array_values(array_filter(array_map('sanitize_text_field', (array) $input['providers'])));
            $out['providers'] = $providers;
        }

        // Toggles
        foreach (['wp_login_enabled', 'wp_register_enabled', 'comments_enabled', 'privacy_notice_ack', 'debug_mode', 'use_popup_oauth', 'integration_debug', 'allow_core_login_account_creation'] as $flag) {
            if (array_key_exists($flag, $input)) {
                $out[$flag] = !empty($input[$flag]);
            }
        }

        // Passwordless mode (off, recommended, strict).
$raw_mode       = isset( $input['passwordless_mode'] ) ? (string) $input['passwordless_mode'] : '';
$allowed_modes  = array( 'off', 'recommended', 'strict' );

if ( $raw_mode !== '' ) {
    if ( ! in_array( $raw_mode, $allowed_modes, true ) ) {
        $raw_mode = 'off';
    }
    $out['passwordless_mode'] = $raw_mode;

} elseif ( array_key_exists( 'passwordless_mode', $existing ) ) {
    // Preserve existing mode when not submitted.
    $out['passwordless_mode'] = (string) $existing['passwordless_mode'];

} else {
    // First install / missing everywhere.
    $out['passwordless_mode'] = 'off';
}

        // Button style
        if (array_key_exists('button_style', $input)) {
            $style = in_array($input['button_style'], ['wide', 'compact'], true) ? $input['button_style'] : 'wide';
            $out['button_style'] = $style;
        }

        // Redirects
        if (array_key_exists('redirect_default_login', $input)) {
            $out['redirect_default_login'] = esc_url_raw($input['redirect_default_login']);
        }
        if (array_key_exists('redirect_default_register', $input)) {
            $out['redirect_default_register'] = esc_url_raw($input['redirect_default_register']);
        }
        if (array_key_exists('redirect_blacklist', $input)) {
            $lines = preg_split("/\r?\n/", (string) $input['redirect_blacklist']);
            $lines = array_filter(array_map('trim', (array) $lines));
            $out['redirect_blacklist'] = array_values($lines);
        }

        if (isset($input['passwordless_email_branding']) && is_array($input['passwordless_email_branding'])) {
            $out['passwordless_email_branding'] = [
                'enabled' => !empty($input['passwordless_email_branding']['enabled']) ? 1 : 0,
                'logo_url' => esc_url_raw($input['passwordless_email_branding']['logo_url'] ?? ''),
                'accent_color' => sanitize_hex_color($input['passwordless_email_branding']['accent_color'] ?? '') ?: '',
                'footer_text' => sanitize_textarea_field($input['passwordless_email_branding']['footer_text'] ?? ''),
            ];
        }

        // Provider Theme / Text mirrors (ensure they persist in main array too)
        if (isset($input['provider_theme']) && is_array($input['provider_theme'])) {
            $e_themes = (array) ($out['provider_theme'] ?? []);
            foreach ($input['provider_theme'] as $slug => $theme) {
                $slug = sanitize_key($slug);
                if (in_array($theme, ['light', 'dark', 'minimal'], true)) {
                    $e_themes[$slug] = $theme;
                }
            }
            $out['provider_theme'] = $e_themes;
        }

        if (isset($input['provider_theme_override']) && is_array($input['provider_theme_override'])) {
            $e_ov = (array) ($out['provider_theme_override'] ?? []);
            foreach ($input['provider_theme_override'] as $slug => $flag) {
                $e_ov[sanitize_key($slug)] = empty($flag) ? 0 : 1;
            }
            $out['provider_theme_override'] = $e_ov;
        }

        if (isset($input['provider_text']) && is_array($input['provider_text'])) {
            $e_txt = (array) ($out['provider_text'] ?? []);
            foreach ($input['provider_text'] as $slug => $text) {
                $e_txt[sanitize_key($slug)] = sanitize_text_field($text);
            }
            $out['provider_text'] = $e_txt;
        }

        // Pro token providers (mirrored under ventraconnect_sl_settings)
        if (isset($input['magic_link']) && is_array($input['magic_link'])) {
            $ml_mode = sanitize_key($input['magic_link']['redirect_mode'] ?? 'same_page');
            $allowed_ml_modes = ['same_page', 'referer', 'home', 'custom'];
            if (!in_array($ml_mode, $allowed_ml_modes, true)) {
                $ml_mode = 'same_page';
            }
            $ml_reg_mode = isset($input['magic_link']['registration_mode']) ? (string) $input['magic_link']['registration_mode'] : 'login_and_register';
            $allowed_reg_modes = ['login_and_register', 'login_only'];
            if (!in_array($ml_reg_mode, $allowed_reg_modes, true)) {
                $ml_reg_mode = 'login_and_register';
            }
            $ml_or = isset($input['magic_link']['or_separator']) ? (string) $input['magic_link']['or_separator'] : 'none';
            $allowed_or = ['none', 'above', 'below', 'both'];
            if (!in_array($ml_or, $allowed_or, true)) {
                $ml_or = 'none';
            }
            $out['magic_link'] = [
                'expiry' => max(1, (int) ($input['magic_link']['expiry'] ?? 10)),
                'single_use' => !empty($input['magic_link']['single_use']) ? 1 : 0,
                'require_same_ip' => !empty($input['magic_link']['require_same_ip']) ? 1 : 0,
                'resend_throttle' => max(0, (int) ($input['magic_link']['resend_throttle'] ?? 60)),
                'redirect_override' => (
                    isset($input['magic_link']['redirect_override'])
                    && (int) $input['magic_link']['redirect_override'] === 1
                ) ? 1 : 0,
                'redirect_mode' => $ml_mode,
                'redirect_url' => esc_url_raw($input['magic_link']['redirect_url'] ?? ''),
                'email_sender' => sanitize_text_field($input['magic_link']['email_sender'] ?? ''),
                'email_subject' => sanitize_text_field($input['magic_link']['email_subject'] ?? ''),
                'email_body' => (string) ($input['magic_link']['email_body'] ?? ''),
                'registration_mode' => $ml_reg_mode,
                'or_separator' => $ml_or,
            ];
        }
        if (isset($input['otp_email']) && is_array($input['otp_email'])) {
            $otp_mode = sanitize_key($input['otp_email']['redirect_mode'] ?? 'same_page');
            $allowed_otp_modes = ['same_page', 'referer', 'home', 'custom'];
            if (!in_array($otp_mode, $allowed_otp_modes, true)) {
                $otp_mode = 'same_page';
            }
            $otp_reg_mode = isset($input['otp_email']['registration_mode']) ? (string) $input['otp_email']['registration_mode'] : 'login_and_register';
            $allowed_reg_modes = ['login_and_register', 'login_only'];
            if (!in_array($otp_reg_mode, $allowed_reg_modes, true)) {
                $otp_reg_mode = 'login_and_register';
            }
            $otp_or = isset($input['otp_email']['or_separator']) ? (string) $input['otp_email']['or_separator'] : 'none';
            $allowed_or = ['none', 'above', 'below', 'both'];
            if (!in_array($otp_or, $allowed_or, true)) {
                $otp_or = 'none';
            }
            $out['otp_email'] = [
                'code_length' => min(8, max(6, (int) ($input['otp_email']['code_length'] ?? 6))),
                'expiry' => max(1, (int) ($input['otp_email']['expiry'] ?? 10)),
                'resend_throttle' => max(0, (int) ($input['otp_email']['resend_throttle'] ?? 60)),
                'max_attempts' => max(1, (int) ($input['otp_email']['max_attempts'] ?? 5)),
                'redirect_override' => (
                    isset($input['otp_email']['redirect_override'])
                    && (int) $input['otp_email']['redirect_override'] === 1
                ) ? 1 : 0,
                'redirect_mode' => $otp_mode,
                'redirect_url' => esc_url_raw($input['otp_email']['redirect_url'] ?? ''),
                'email_sender' => sanitize_text_field($input['otp_email']['email_sender'] ?? ''),
                'email_subject' => sanitize_text_field($input['otp_email']['email_subject'] ?? ''),
                'email_body' => (string) ($input['otp_email']['email_body'] ?? ''),
                'registration_mode' => $otp_reg_mode,
                'or_separator' => $otp_or,
            ];
        }

        if (isset($input['passkey']) && is_array($input['passkey'])) {
            $passkey_mode = sanitize_key($input['passkey']['redirect_mode'] ?? 'same_page');
            $allowed_passkey_modes = ['same_page', 'referer', 'home', 'custom'];
            if (!in_array($passkey_mode, $allowed_passkey_modes, true)) {
                $passkey_mode = 'same_page';
            }

            $passkey_reg_mode = isset($input['passkey']['registration_mode']) ? (string) $input['passkey']['registration_mode'] : 'login_and_register';
            $allowed_reg_modes = ['login_and_register', 'login_only'];
            if (!in_array($passkey_reg_mode, $allowed_reg_modes, true)) {
                $passkey_reg_mode = 'login_and_register';
            }

            $passkey_or = isset($input['passkey']['or_separator']) ? (string) $input['passkey']['or_separator'] : 'none';
            $allowed_or = ['none', 'above', 'below', 'both'];
            if (!in_array($passkey_or, $allowed_or, true)) {
                $passkey_or = 'none';
            }

            $helper_settings = [
                'show_helper_text' => (
                    isset($input['passkey']['show_helper_text'])
                    && (int) $input['passkey']['show_helper_text'] === 1
                ) ? 1 : 0,
                'login_helper_text' => sanitize_text_field($input['passkey']['login_helper_text'] ?? ''),
                'register_helper_text' => sanitize_text_field($input['passkey']['register_helper_text'] ?? ''),
            ];

            $floating_panel_position = isset($input['passkey']['floating_panel_position']) ? (string) $input['passkey']['floating_panel_position'] : 'bottom_right';
            if (!in_array($floating_panel_position, ['bottom_right', 'bottom_left'], true)) {
                $floating_panel_position = 'bottom_right';
            }

            $floating_panel_delay = isset($input['passkey']['floating_panel_delay']) ? absint($input['passkey']['floating_panel_delay']) : 3;
            $floating_panel_delay = min(30, max(0, $floating_panel_delay));
            $floating_panel_snooze_raw = $input['passkey']['floating_panel_snooze_days'] ?? null;
            if (is_scalar($floating_panel_snooze_raw) && '' !== trim((string) $floating_panel_snooze_raw) && is_numeric($floating_panel_snooze_raw)) {
                $floating_panel_snooze_days = (int) $floating_panel_snooze_raw;
                $floating_panel_snooze_days = min(365, max(1, $floating_panel_snooze_days));
            } else {
                $floating_panel_snooze_days = 7;
            }

            $floating_panel_pages = [];
            if (isset($input['passkey']['floating_panel_pages'])) {
                $floating_panel_pages = array_values(
                    array_filter(
                        array_map('absint', (array) $input['passkey']['floating_panel_pages'])
                    )
                );
            }

            $out['passkey'] = [
                'registration_mode' => $passkey_reg_mode,
                'redirect_override' => (
                    isset($input['passkey']['redirect_override'])
                    && (int) $input['passkey']['redirect_override'] === 1
                ) ? 1 : 0,
                'redirect_mode' => $passkey_mode,
                'redirect_url' => esc_url_raw($input['passkey']['redirect_url'] ?? ''),
                'or_separator' => $passkey_or,
                'show_helper_text' => $helper_settings['show_helper_text'],
                'login_helper_text' => $helper_settings['login_helper_text'],
                'register_helper_text' => $helper_settings['register_helper_text'],
                'floating_panel_enabled' => (
                    isset($input['passkey']['floating_panel_enabled'])
                    && (int) $input['passkey']['floating_panel_enabled'] === 1
                ) ? 1 : 0,
                'floating_panel_pages' => $floating_panel_pages,
                'floating_panel_title' => sanitize_text_field($input['passkey']['floating_panel_title'] ?? ''),
                'floating_panel_message' => sanitize_text_field($input['passkey']['floating_panel_message'] ?? ''),
                'floating_panel_button_text' => sanitize_text_field($input['passkey']['floating_panel_button_text'] ?? ''),
                'floating_panel_position' => $floating_panel_position,
                'floating_panel_delay' => $floating_panel_delay,
                'floating_panel_snooze_days' => $floating_panel_snooze_days,
            ];
        }

        return $out;
    }
}
