<?php
namespace VentraConnect\SocialLogin\Services;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Minimal shared HTML renderer for passwordless emails.
 *
 * Keeps provider subject/body settings as the content source and only
 * upgrades the final HTML presentation for Magic Link and OTP emails.
 */
class Passwordless_Email_Renderer {
    /**
     * Expose the shared passwordless email branding settings for other Free UI
     * surfaces that need to mirror the same logo/accent choices.
     *
     * @return array<string,mixed>
     */
    public static function get_shared_branding_settings(): array {
        return self::get_branding_settings();
    }

    /**
     * Render a wrapped HTML email body for a passwordless mode.
     *
     * Returns an empty string on invalid input so callers can fall back to the
     * legacy wpautop() output path.
     *
     * @param string $mode         Passwordless mode: magic_link, otp_email, or passkey_verification.
     * @param string $body_template Stored email body template.
     * @param array  $replacements Placeholder replacements.
     * @return string
     */
    public static function render( string $mode, string $body_template, array $replacements ): string {
        if ( '' === $body_template || empty( $replacements ) ) {
            return '';
        }

        $action_token = self::get_action_token( $mode );
        if ( '' === $action_token || empty( $replacements[ $action_token ] ) || ! is_scalar( $replacements[ $action_token ] ) ) {
            return '';
        }

        $marker = '%%VENTRACONNECT_SL_ACTION%%';
        $body   = $body_template;

        if ( false !== strpos( $body_template, $action_token ) ) {
            $body = str_replace( $action_token, $marker, $body_template );
        }

        $content_replacements = $replacements;
        unset( $content_replacements[ $action_token ] );

        $resolved_content = strtr( $body, $content_replacements );
        $content_html     = wpautop( $resolved_content );
        $action_html      = self::render_action_html( $mode, (string) $replacements[ $action_token ] );

        if ( '' === $content_html || '' === $action_html ) {
            return '';
        }

        if ( false !== strpos( $content_html, $marker ) ) {
            $content_html = str_replace( $marker, $action_html, $content_html );
        } else {
            $content_html .= "\n" . $action_html;
        }

        $site_name = '';
        if ( isset( $replacements['{site_name}'] ) && is_scalar( $replacements['{site_name}'] ) ) {
            $site_name = (string) $replacements['{site_name}'];
        }

        return self::wrap_html( $content_html, $site_name );
    }

    /**
     * Get the primary action placeholder token for a mode.
     *
     * @param string $mode
     * @return string
     */
    private static function get_action_token( string $mode ): string {
        if ( 'magic_link' === $mode ) {
            return '{magic_link}';
        }

        if ( 'otp_email' === $mode ) {
            return '{otp_code}';
        }

        if ( 'passkey_verification' === $mode ) {
            return '{verification_link}';
        }

        return '';
    }

    /**
     * Render mode-specific action HTML.
     *
     * @param string $mode
     * @param string $action_value
     * @return string
     */
    private static function render_action_html( string $mode, string $action_value ): string {
        if ( 'magic_link' === $mode ) {
            $href = esc_url( $action_value );
            if ( '' === $href ) {
                return '';
            }
            $branding = self::get_branding_settings();
            $button_bg = ! empty( $branding['accent_color'] ) ? $branding['accent_color'] : '#1d4ed8';

            return '<div style="margin:24px 0;text-align:center;">'
                . '<a href="' . $href . '" style="display:inline-block;padding:12px 22px;background:' . esc_attr( $button_bg ) . ';color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">'
                . esc_html__( 'Sign in securely', 'ventraconnect-social-login' )
                . '</a>'
                . '</div>'
                . '<div style="margin:14px 0 0 0;text-align:center;font-size:13px;line-height:1.6;color:#4b5563;">'
                . esc_html__( 'If the button does not work, copy and paste this link into your browser:', 'ventraconnect-social-login' )
                . '</div>'
                . '<div style="margin:8px 0 0 0;text-align:center;font-size:13px;line-height:1.6;color:#1d4ed8;word-break:break-all;">'
                . esc_html( $href )
                . '</div>';
        }

        if ( 'otp_email' === $mode ) {
            $code = preg_replace( '/[^0-9A-Za-z\-]/', '', $action_value );
            if ( '' === $code ) {
                return '';
            }

            return '<p style="margin:24px 0 12px 0;text-align:center;">'
                . '<span style="display:inline-block;padding:14px 20px;border:1px solid #d1d5db;border-radius:8px;background:#f8fafc;color:#111827;font-size:28px;line-height:1.2;font-weight:700;letter-spacing:0.18em;">'
                . esc_html( $code )
                . '</span>'
                . '</p>';
        }

        if ( 'passkey_verification' === $mode ) {
            $href = esc_url( $action_value );
            if ( '' === $href ) {
                return '';
            }

            $branding  = self::get_branding_settings();
            $button_bg = ! empty( $branding['accent_color'] ) ? $branding['accent_color'] : '#1d4ed8';

            return '<div style="margin:24px 0;text-align:center;">'
                . '<a href="' . $href . '" style="display:inline-block;padding:12px 22px;background:' . esc_attr( $button_bg ) . ';color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">'
                . esc_html__( 'Continue setting up your passkey', 'ventraconnect-social-login' )
                . '</a>'
                . '</div>'
                . '<div style="margin:14px 0 0 0;text-align:center;font-size:13px;line-height:1.6;color:#4b5563;">'
                . esc_html__( 'If the button does not work, copy and paste this link into your browser:', 'ventraconnect-social-login' )
                . '</div>'
                . '<div style="margin:8px 0 0 0;text-align:center;font-size:13px;line-height:1.6;color:#1d4ed8;word-break:break-all;">'
                . esc_html( $href )
                . '</div>';
        }

        return '';
    }

    /**
     * Wrap the rendered content in a minimal shared HTML shell.
     *
     * @param string $content_html
     * @param string $site_name
     * @return string
     */
    private static function wrap_html( string $content_html, string $site_name ): string {
        $brand = '' !== $site_name ? $site_name : wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $branding = self::get_branding_settings();

        if ( empty( $branding['enabled'] ) ) {
            return '<!DOCTYPE html>'
                . '<html><head><meta http-equiv="Content-Type" content="text/html; charset=' . esc_attr( get_bloginfo( 'charset' ) ) . '"></head><body style="margin:0;padding:24px;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;color:#111827;">'
                . '<div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">'
                . '<div style="padding:32px 32px 8px 32px;">'
                . '<div style="margin:0 0 24px 0;font-size:18px;line-height:1.4;font-weight:700;color:#111827;">' . esc_html( $brand ) . '</div>'
                . '<div style="font-size:16px;line-height:1.7;color:#111827;">' . $content_html . '</div>'
                . '</div>'
                . '</div>'
                . '</body></html>';
        }

        $accent_color = ! empty( $branding['accent_color'] ) ? $branding['accent_color'] : '#1d4ed8';
        $logo_html    = '';
        if ( ! empty( $branding['logo_url'] ) ) {
            $logo_html = '<div style="margin:0 0 16px 0;"><img src="' . esc_url( $branding['logo_url'] ) . '" alt="' . esc_attr( $brand ) . '" style="max-width:220px;height:auto;border:0;display:block;"></div>';
        }
        $heading_html = '';
        if ( '' === $logo_html ) {
            $heading_html = '<div style="margin:0 0 24px 0;font-size:18px;line-height:1.4;font-weight:700;color:' . esc_attr( $accent_color ) . ';">' . esc_html( $brand ) . '</div>';
        }
        $footer_html = '';
        if ( ! empty( $branding['footer_text'] ) ) {
            $footer_html = '<div style="max-width:640px;margin:14px auto 0 auto;text-align:center;font-size:12px;line-height:1.6;color:#6b7280;">'
                . nl2br( esc_html( $branding['footer_text'] ) )
                . '</div>';
        }

        return '<!DOCTYPE html>'
            . '<html><head><meta http-equiv="Content-Type" content="text/html; charset=' . esc_attr( get_bloginfo( 'charset' ) ) . '"></head><body style="margin:0;padding:24px;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;color:#111827;">'
            . '<div style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-top:4px solid ' . esc_attr( $accent_color ) . ';border-radius:12px;overflow:hidden;">'
            . '<div style="padding:32px 32px 8px 32px;">'
            . $logo_html
            . $heading_html
            . '<div style="font-size:16px;line-height:1.7;color:#111827;">' . $content_html . '</div>'
            . '</div>'
            . '</div>'
            . $footer_html
            . '</body></html>';
    }

    /**
     * Read Pro-only branding settings for the passwordless wrapper.
     *
     * @return array<string,mixed>
     */
    private static function get_branding_settings(): array {
        if ( ! function_exists( '\vcsl_is_pro_active' ) || ! \vcsl_is_pro_active() ) {
            return array( 'enabled' => false );
        }

        $settings = get_option( 'ventraconnect_sl_settings', array() );
        if ( ! is_array( $settings ) || empty( $settings['passwordless_email_branding'] ) || ! is_array( $settings['passwordless_email_branding'] ) ) {
            return array( 'enabled' => false );
        }

        $branding = $settings['passwordless_email_branding'];
        $accent   = isset( $branding['accent_color'] ) ? sanitize_hex_color( (string) $branding['accent_color'] ) : '';

        return array(
            'enabled'      => ! empty( $branding['enabled'] ),
            'logo_url'     => isset( $branding['logo_url'] ) ? esc_url_raw( (string) $branding['logo_url'] ) : '',
            'accent_color' => $accent ? $accent : '',
            'footer_text'  => isset( $branding['footer_text'] ) ? sanitize_textarea_field( (string) $branding['footer_text'] ) : '',
        );
    }
}
