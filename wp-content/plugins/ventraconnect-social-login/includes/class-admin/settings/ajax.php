<?php
namespace VentraConnect\SocialLogin\Admin\Settings;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX endpoints for Settings UI.
 *
 * Moved from Settings::ajax_save_provider_order() with no logic changes.
 */
class Ajax
{
    /**
     * Save providers order from sortable sidebar.
     */
    public function ajax_save_provider_order(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                array(
                    'code'    => 'forbidden',
                    'message' => __( 'You do not have permission to perform this action.', 'ventraconnect-social-login' ),
                ),
                403
            );
        }
        // Accept canonical nonce first, fall back to legacy for transition.
        $ok = check_ajax_referer('ventraconnect_sl_provider_order', 'nonce', false) || check_ajax_referer('vc_provider_order', 'nonce', false);
        if (!$ok) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }

        $providers = array();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
        $order = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array();
        if ( is_array( $order ) ) {
            $raw_order = $order;
            $providers = array();

            foreach ( (array) $raw_order as $provider_slug ) {
                $providers[] = sanitize_key( (string) $provider_slug );
            }
        }

        // Whitelist to known providers
        $known = ['passkey', 'magic_link', 'otp_email', 'google', 'facebook', 'amazon', 'twitter', 'microsoft', 'yahoo', 'wordpress', 'slack', 'discord', 'linkedin', 'spotify', 'line', 'tiktok', 'github', 'twitch', 'reddit'];
        $order = array_values(array_intersect($providers, $known));

        if (empty($order)) {
            wp_send_json_error(
                array(
                    'code'    => 'invalid_request',
                    'message' => __( 'No provider order data provided.', 'ventraconnect-social-login' ),
                ),
                400
            );
        }

        \VentraConnect\SocialLogin\Admin\Settings\Persistence::setProviderOrder($order);
        wp_send_json_success(['saved' => $order]);
    }

    /**
     * Save provider settings via AJAX (hybrid).
     * Accepts a subset of ventraconnect_sl_settings for a single provider pane.
     */
    public function ajax_save_provider_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                array(
                    'code'    => 'forbidden',
                    'message' => __( 'You do not have permission to perform this action.', 'ventraconnect-social-login' ),
                ),
                403
            );
        }
        // Require canonical admin nonce.
        if (!check_ajax_referer('ventraconnect_sl_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'ventraconnect-social-login')], 403);
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
        $provider_raw = isset( $_POST['provider'] ) ? wp_unslash( (string) $_POST['provider'] ) : '';
        $provider     = sanitize_key( $provider_raw );
        if ('' === $provider) {
            wp_send_json_error(
                array(
                    'code'    => 'invalid_request',
                    'message' => __('Invalid provider.', 'ventraconnect-social-login'),
                ),
                400
            );
        }

        // Whitelist
        $known = ['passkey', 'magic_link', 'otp_email', 'google', 'facebook', 'amazon', 'twitter', 'microsoft', 'yahoo', 'wordpress', 'slack', 'discord', 'linkedin', 'spotify', 'line', 'tiktok', 'github', 'twitch', 'reddit'];
        if (!in_array($provider, $known, true)) {
            wp_send_json_error(['message' => __('Unknown provider.', 'ventraconnect-social-login')], 400);
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- complex settings array sanitized later by Sanitizer.
        $raw_settings = isset( $_POST['ventraconnect_sl_settings'] ) ? wp_unslash( $_POST['ventraconnect_sl_settings'] ) : array();

        // Preserve nested structure; individual fields are sanitized below as needed.
        $incoming = is_array($raw_settings) ? $raw_settings : [];

        $current = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();

        // Providers list toggle for this provider
        $incoming_active = false;
        if (isset($incoming['providers']) && is_array($incoming['providers'])) {
            $incoming_providers = array_map('sanitize_key', (array) $incoming['providers']);
            $incoming_active = in_array($provider, $incoming_providers, true);
        }
        $current_providers = array_map('sanitize_key', (array) ($current['providers'] ?? []));
        if ($incoming_active) {
            if (!in_array($provider, $current_providers, true)) {
                $current_providers[] = $provider;
            }
        } else {
            $current_providers = array_values(array_diff($current_providers, [$provider]));
        }
        $current['providers'] = $current_providers;

        // Credentials
        if (isset($incoming['provider_creds'][$provider]) && is_array($incoming['provider_creds'][$provider])) {
            $cid = isset($incoming['provider_creds'][$provider]['client_id']) ? sanitize_text_field((string) $incoming['provider_creds'][$provider]['client_id']) : '';
            $cs = isset($incoming['provider_creds'][$provider]['client_secret']) ? sanitize_text_field((string) $incoming['provider_creds'][$provider]['client_secret']) : '';
            $current['provider_creds'][$provider] = ['client_id' => $cid, 'client_secret' => $cs];
        }

        // Theme override
        if (isset($incoming['provider_theme_override'][$provider])) {
            $override_value = (int) (!empty($incoming['provider_theme_override'][$provider]));
            $current['provider_theme_override'][$provider] = $override_value;
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::setProviderThemeOverride($provider, $override_value);
        }
        // Theme selection
        if (isset($incoming['provider_theme'][$provider])) {
            $theme = sanitize_key((string) $incoming['provider_theme'][$provider]);
            if (in_array($theme, ['light', 'dark', 'minimal'], true)) {
                $current['provider_theme'][$provider] = $theme;
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::setProviderTheme($provider, $theme);
            }
        }
        // Provider custom button text (wide-only label).
        if (isset($incoming['provider_text'][$provider])) {
            $raw  = (string) $incoming['provider_text'][$provider];
            $text = sanitize_text_field($raw);

            if (!isset($current['provider_text']) || !is_array($current['provider_text'])) {
                $current['provider_text'] = [];
            }

            $current['provider_text'][$provider] = $text;

            // Mirror the non-AJAX path.
            if (method_exists(\VentraConnect\SocialLogin\Admin\Settings\Persistence::class, 'setProviderText')) {
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::setProviderText($provider, $text);
            }
        }

        // Profile Sync (Free) for this provider
        if (isset($incoming['sync_free'][$provider]) && is_array($incoming['sync_free'][$provider])) {
            $vals = (array) $incoming['sync_free'][$provider];
            $slug = sanitize_key($provider);
            $synced_free = [
                'avatar'       => empty($vals['avatar']) ? 0 : 1,
                'display_name' => empty($vals['display_name']) ? 0 : 1,
                'first_last'   => empty($vals['first_last']) ? 0 : 1,
                'email'        => empty($vals['email']) ? 0 : 1,
            ];
            $all_free = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption('ventraconnect_sl_sync_free', []);
            $all_free[$slug] = $synced_free;
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateOption('ventraconnect_sl_sync_free', $all_free);
        }

        // Profile Sync (Pro) for this provider
        if (defined('VCS_PRO_ACTIVE') && VCS_PRO_ACTIVE && isset($incoming['sync_pro'][$provider]) && is_array($incoming['sync_pro'][$provider])) {
            $fields = (array) $incoming['sync_pro'][$provider];
            $slug = sanitize_key($provider);
            $valid = ['fill_blanks', 'overwrite', 'never'];
            $sanitized = [];
            foreach ($fields as $field => $policy) {
                $key = sanitize_key($field);
                $sanitized[$key] = in_array($policy, $valid, true) ? $policy : 'never';
            }
            $all_pro = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption('ventraconnect_sl_sync_pro', []);
            $all_pro[$slug] = $sanitized;
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateOption('ventraconnect_sl_sync_pro', $all_pro);
        }

        // OTP / Magic Link settings (namespaced under ventraconnect_sl_settings)
        if ('magic_link' === $provider && isset($incoming['magic_link']) && is_array($incoming['magic_link'])) {
            $curr = (array) ($current['magic_link'] ?? []);
            $curr['expiry'] = isset($incoming['magic_link']['expiry']) ? (int) $incoming['magic_link']['expiry'] : ($curr['expiry'] ?? 10);
            $curr['single_use'] = !empty($incoming['magic_link']['single_use']) ? 1 : 0;
            $curr['require_same_ip'] = !empty($incoming['magic_link']['require_same_ip']) ? 1 : 0;
            $curr['resend_throttle'] = isset($incoming['magic_link']['resend_throttle']) ? (int) $incoming['magic_link']['resend_throttle'] : ($curr['resend_throttle'] ?? 60);
            // Explicit 0/1 semantics for redirect_override; do not fall back to previous value.
            if (array_key_exists('redirect_override', $incoming['magic_link'])) {
                $curr['redirect_override'] = ((int) $incoming['magic_link']['redirect_override'] === 1) ? 1 : 0;
            } else {
                $curr['redirect_override'] = 0;
            }
            $curr['redirect_mode'] = isset($incoming['magic_link']['redirect_mode']) ? sanitize_key((string) $incoming['magic_link']['redirect_mode']) : ($curr['redirect_mode'] ?? 'same_page');
            $curr['redirect_url'] = isset($incoming['magic_link']['redirect_url']) ? esc_url_raw((string) $incoming['magic_link']['redirect_url']) : ($curr['redirect_url'] ?? '');
            $curr['email_sender'] = isset($incoming['magic_link']['email_sender']) ? sanitize_text_field((string) $incoming['magic_link']['email_sender']) : ($curr['email_sender'] ?? '');
            $curr['email_subject'] = isset($incoming['magic_link']['email_subject']) ? sanitize_text_field((string) $incoming['magic_link']['email_subject']) : ($curr['email_subject'] ?? '');
            $curr['email_body'] = isset($incoming['magic_link']['email_body']) ? wp_kses_post((string) $incoming['magic_link']['email_body']) : ($curr['email_body'] ?? '');
            $curr['registration_mode'] = isset($incoming['magic_link']['registration_mode']) && in_array($incoming['magic_link']['registration_mode'], ['login_and_register', 'login_only'], true) ? $incoming['magic_link']['registration_mode'] : ($curr['registration_mode'] ?? 'login_and_register');
            if (isset($incoming['magic_link']['or_separator'])) {
                $ml_or = (string) $incoming['magic_link']['or_separator'];
                $allowed_or = ['none', 'above', 'below', 'both'];
                if (!in_array($ml_or, $allowed_or, true)) {
                    $ml_or = 'none';
                }
                $curr['or_separator'] = $ml_or;
            }
            $current['magic_link'] = $curr;
        }
        if ('otp_email' === $provider && isset($incoming['otp_email']) && is_array($incoming['otp_email'])) {
            $curr = (array) ($current['otp_email'] ?? []);
            $curr['code_length'] = isset($incoming['otp_email']['code_length']) ? (int) $incoming['otp_email']['code_length'] : ($curr['code_length'] ?? 6);
            $curr['expiry'] = isset($incoming['otp_email']['expiry']) ? (int) $incoming['otp_email']['expiry'] : ($curr['expiry'] ?? 10);
            $curr['resend_throttle'] = isset($incoming['otp_email']['resend_throttle']) ? (int) $incoming['otp_email']['resend_throttle'] : ($curr['resend_throttle'] ?? 60);
            // Explicit 0/1 semantics for redirect_override; do not fall back to previous value when OFF.
            if (array_key_exists('redirect_override', $incoming['otp_email'])) {
                $curr['redirect_override'] = ((int) $incoming['otp_email']['redirect_override'] === 1) ? 1 : 0;
            }
            $curr['redirect_mode'] = isset($incoming['otp_email']['redirect_mode']) ? sanitize_key((string) $incoming['otp_email']['redirect_mode']) : ($curr['redirect_mode'] ?? 'same_page');
            $curr['redirect_url'] = isset($incoming['otp_email']['redirect_url']) ? esc_url_raw((string) $incoming['otp_email']['redirect_url']) : ($curr['redirect_url'] ?? '');
            $curr['email_sender'] = isset($incoming['otp_email']['email_sender']) ? sanitize_text_field((string) $incoming['otp_email']['email_sender']) : ($curr['email_sender'] ?? '');
            $curr['max_attempts'] = isset($incoming['otp_email']['max_attempts']) ? (int) $incoming['otp_email']['max_attempts'] : ($curr['max_attempts'] ?? 5);
            $curr['email_subject'] = isset($incoming['otp_email']['email_subject']) ? sanitize_text_field((string) $incoming['otp_email']['email_subject']) : ($curr['email_subject'] ?? '');
            $curr['email_body'] = isset($incoming['otp_email']['email_body']) ? wp_kses_post((string) $incoming['otp_email']['email_body']) : ($curr['email_body'] ?? '');
            $curr['registration_mode'] = isset($incoming['otp_email']['registration_mode']) && in_array($incoming['otp_email']['registration_mode'], ['login_and_register', 'login_only'], true) ? $incoming['otp_email']['registration_mode'] : ($curr['registration_mode'] ?? 'login_and_register');
            if (isset($incoming['otp_email']['or_separator'])) {
                $otp_or = (string) $incoming['otp_email']['or_separator'];
                $allowed_or = ['none', 'above', 'below', 'both'];
                if (!in_array($otp_or, $allowed_or, true)) {
                    $otp_or = 'none';
                }
                $curr['or_separator'] = $otp_or;
            }
            $current['otp_email'] = $curr;
        }

        if ('passkey' === $provider && isset($incoming['passkey']) && is_array($incoming['passkey'])) {
            $curr = (array) ($current['passkey'] ?? []);
            $curr['registration_mode'] = isset($incoming['passkey']['registration_mode']) && in_array($incoming['passkey']['registration_mode'], ['login_and_register', 'login_only'], true) ? $incoming['passkey']['registration_mode'] : ($curr['registration_mode'] ?? 'login_and_register');
            $curr['redirect_override'] = array_key_exists('redirect_override', $incoming['passkey']) && ((int) $incoming['passkey']['redirect_override'] === 1) ? 1 : 0;
            $curr['redirect_mode'] = isset($incoming['passkey']['redirect_mode']) ? sanitize_key((string) $incoming['passkey']['redirect_mode']) : ($curr['redirect_mode'] ?? 'same_page');
            if (!in_array($curr['redirect_mode'], ['same_page', 'referer', 'home', 'custom'], true)) {
                $curr['redirect_mode'] = 'same_page';
            }
            $curr['redirect_url'] = isset($incoming['passkey']['redirect_url']) ? esc_url_raw((string) $incoming['passkey']['redirect_url']) : ($curr['redirect_url'] ?? '');
            if (isset($incoming['passkey']['or_separator'])) {
                $passkey_or = (string) $incoming['passkey']['or_separator'];
                $allowed_or = ['none', 'above', 'below', 'both'];
                if (!in_array($passkey_or, $allowed_or, true)) {
                    $passkey_or = 'none';
                }
                $curr['or_separator'] = $passkey_or;
            }
            $curr['show_helper_text'] = array_key_exists('show_helper_text', $incoming['passkey']) && ((int) $incoming['passkey']['show_helper_text'] === 1) ? 1 : 0;
            $curr['login_helper_text'] = isset($incoming['passkey']['login_helper_text']) ? sanitize_text_field((string) $incoming['passkey']['login_helper_text']) : ($curr['login_helper_text'] ?? '');
            $curr['register_helper_text'] = isset($incoming['passkey']['register_helper_text']) ? sanitize_text_field((string) $incoming['passkey']['register_helper_text']) : ($curr['register_helper_text'] ?? '');
            $curr['floating_panel_enabled'] = array_key_exists('floating_panel_enabled', $incoming['passkey']) && ((int) $incoming['passkey']['floating_panel_enabled'] === 1) ? 1 : 0;
            $curr['floating_panel_pages'] = isset($incoming['passkey']['floating_panel_pages'])
                ? array_values(array_filter(array_map('absint', (array) $incoming['passkey']['floating_panel_pages'])))
                : array();
            $curr['floating_panel_title'] = isset($incoming['passkey']['floating_panel_title']) ? sanitize_text_field((string) $incoming['passkey']['floating_panel_title']) : ($curr['floating_panel_title'] ?? '');
            $curr['floating_panel_message'] = isset($incoming['passkey']['floating_panel_message']) ? sanitize_text_field((string) $incoming['passkey']['floating_panel_message']) : ($curr['floating_panel_message'] ?? '');
            $curr['floating_panel_button_text'] = isset($incoming['passkey']['floating_panel_button_text']) ? sanitize_text_field((string) $incoming['passkey']['floating_panel_button_text']) : ($curr['floating_panel_button_text'] ?? '');
            $curr['floating_panel_position'] = isset($incoming['passkey']['floating_panel_position']) ? sanitize_key((string) $incoming['passkey']['floating_panel_position']) : ($curr['floating_panel_position'] ?? 'bottom_right');
            if (!in_array($curr['floating_panel_position'], ['bottom_right', 'bottom_left'], true)) {
                $curr['floating_panel_position'] = 'bottom_right';
            }
            $curr['floating_panel_delay'] = isset($incoming['passkey']['floating_panel_delay']) ? absint($incoming['passkey']['floating_panel_delay']) : (isset($curr['floating_panel_delay']) ? absint($curr['floating_panel_delay']) : 3);
            $curr['floating_panel_delay'] = min(30, max(0, $curr['floating_panel_delay']));
            $floating_panel_snooze_raw = $incoming['passkey']['floating_panel_snooze_days'] ?? ($curr['floating_panel_snooze_days'] ?? 7);
            if (is_scalar($floating_panel_snooze_raw) && '' !== trim((string) $floating_panel_snooze_raw) && is_numeric($floating_panel_snooze_raw)) {
                $curr['floating_panel_snooze_days'] = (int) $floating_panel_snooze_raw;
                $curr['floating_panel_snooze_days'] = min(365, max(1, $curr['floating_panel_snooze_days']));
            } else {
                $curr['floating_panel_snooze_days'] = 7;
            }
            $current['passkey'] = $curr;
        }

        // Save to canonical option name; Persistence will handle legacy migration/fallbacks.
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateOption('ventraconnect_sl_settings', $current);

        // Compute a fresh Verify Settings URL for this provider so the
        // current page can update the verification link without reload.
        $verify_url = '';
        try {
            $prov = \VentraConnect\SocialLogin\OAuth::provider($provider);
            if ($prov) {
                $redirect = \VentraConnect\SocialLogin\OAuth::redirect_uri($provider);
                $return_url = admin_url('admin.php?page=ventraconnect-sl-settings&view=provider&provider=' . $provider);
                $state = \VentraConnect\SocialLogin\OAuth::generate_state(
                    $provider,
                    array(
                        'verify' => 1,
                        'return' => $return_url,
                    )
                );
                $auth_url = $prov->get_auth_url($state, is_string($redirect) ? $redirect : '');
                if (is_string($auth_url)) {
                    $verify_url = $auth_url;
                }
            }
        } catch (\Throwable $e) { // phpcs:ignore
            $verify_url = '';
        }

        wp_send_json_success(
            array(
                'message'    => __('Provider settings saved.', 'ventraconnect-social-login'),
                'verify_url' => $verify_url,
            )
        );
    }
}
