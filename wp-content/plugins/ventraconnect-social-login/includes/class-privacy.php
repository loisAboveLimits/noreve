<?php
namespace VentraConnect\SocialLogin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Privacy policy text + data exporter/eraser.
 */
class Privacy {
    public static function init() {
        // Policy content for admin Privacy Policy guide
        add_action( 'admin_init', [ __CLASS__, 'add_policy' ] );
        // Exporter and eraser hooks
        add_filter( 'wp_privacy_personal_data_exporters', [ __CLASS__, 'register_exporter' ] );
        add_filter( 'wp_privacy_personal_data_erasers', [ __CLASS__, 'register_eraser' ] );
    }

    public static function add_policy() {
        if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
            $content = __( 'VentraConnect Social Login lets users authenticate via third‑party providers (e.g., Google, Facebook). We request and store a minimal set of data returned by the provider: provider name, provider user ID, email (when available), display name and avatar URL. This information is stored in user meta and used only for account linking and login. You can unlink at any time from your account page. No credentials or access tokens are stored on the frontend. Site administrators can erase this data via the WordPress personal data eraser.', 'ventraconnect-social-login' );
            wp_add_privacy_policy_content( 'VentraConnect Social Login', wpautop( wp_kses_post( $content ) ) );
        }
    }

    public static function register_exporter( $exporters ) {
        $exporters['ventraconnect-social-login'] = [
            'exporter_friendly_name' => __( 'VentraConnect Social Login', 'ventraconnect-social-login' ),
            'callback'               => [ __CLASS__, 'exporter' ],
        ];
        return $exporters;
    }

    public static function exporter( $email_address, $page = 1 ) {
        $data = [];
        $user = get_user_by( 'email', $email_address );
        if ( $user ) {
            $user_id = $user->ID;
            $conns = ( new User_Links() )->get_connections( $user_id );
            if ( ! empty( $conns ) ) {
                $data[] = [
                    'group_id'    => 'ventraconnect-social-login',
                    'group_label' => __( 'VentraConnect Social Login', 'ventraconnect-social-login' ),
                    'item_id'     => 'ventraconnect-sl-connections-' . $user_id,
                    'data'        => [
                        [ 'name' => __( 'Connections', 'ventraconnect-social-login' ), 'value' => wp_json_encode( $conns ) ],
                        [ 'name' => __( 'Primary Provider', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, '_ventraconnect_sl_primary_provider', true, '' ) ],
                        [ 'name' => __( 'Local Avatar ID', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_avatar_id', true, '' ) ],
                        [ 'name' => __( 'Avatar Source URL', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_avatar_source', true, '' ) ],
                        [ 'name' => __( 'Profile Snapshot', 'ventraconnect-social-login' ), 'value' => wp_json_encode( \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_profile_snapshot', true, [] ) ) ],
                        [ 'name' => __( 'Last Profile Sync', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_last_profile_sync', true, '' ) ],
                        [ 'name' => __( 'Last Sync Summary', 'ventraconnect-social-login' ), 'value' => wp_json_encode( \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_last_sync_fields', true, [] ) ) ],
                        [ 'name' => __( 'Last Provider', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_last_provider', true, '' ) ],
                        [ 'name' => __( 'Locale', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_locale', true, '' ) ],
                        [ 'name' => __( 'Profile URL', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_profile_url', true, '' ) ],
                        [ 'name' => __( 'Company', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_company', true, '' ) ],
                        [ 'name' => __( 'Headline', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_headline', true, '' ) ],
                        [ 'name' => __( 'Website', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_website', true, '' ) ],
                        [ 'name' => __( 'Location', 'ventraconnect-social-login' ), 'value' => (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_location_text', true, '' ) ],
                        [ 'name' => __( 'Email Overwrite Log', 'ventraconnect-social-login' ), 'value' => wp_json_encode( \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_email_overwrite_log', true, [] ) ) ],
                    ],
                ];
            }
        }
        return [ 'data' => $data, 'done' => true ];
    }

    public static function register_eraser( $erasers ) {
        $erasers['ventraconnect-social-login'] = [
            'eraser_friendly_name' => __( 'VentraConnect Social Login', 'ventraconnect-social-login' ),
            'callback'             => [ __CLASS__, 'eraser' ],
        ];
        return $erasers;
    }

    public static function eraser( $email_address, $page = 1 ) {
        $user = get_user_by( 'email', $email_address );
        $items_removed = false;
        if ( $user ) {
            $user_id = $user->ID;
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, User_Links::META_CONNECTIONS );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, User_Links::META_CONNECTIONS_LEGACY );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, '_ventraconnect_sl_primary_provider' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_avatar_id' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_avatar_source' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_avatar_remote_hash' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_profile_snapshot' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_last_profile_sync' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_last_sync_fields' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_locale' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_profile_url' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_company' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_headline' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_website' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_location_text' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_email_overwrite_log' );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_consent_shown' );
            $items_removed = true;
        }
        return [ 'items_removed' => $items_removed, 'items_retained' => false, 'messages' => [], 'done' => true ];
    }
}
