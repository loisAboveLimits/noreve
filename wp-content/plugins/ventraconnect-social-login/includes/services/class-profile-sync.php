<?php
namespace VentraConnect\SocialLogin\Services;

use VentraConnect\SocialLogin\Providers\VCS_Provider_Data;
use VentraConnect\SocialLogin\Providers\VCS_Provider_Capabilities;
use VentraConnect\SocialLogin\User_Links;

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( '\VentraConnect\SocialLogin\Providers\VCS_Provider_Data', false ) ) {
    $ventraconnect_sl_normalizer_path = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ? VENTRACONNECT_SL_PLUGIN_DIR . 'includes/providers/normalizer.php' : __DIR__ . '/../providers/normalizer.php';
    if ( file_exists( $ventraconnect_sl_normalizer_path ) ) {
        require_once $ventraconnect_sl_normalizer_path;
    }
}
if ( ! class_exists( '\VentraConnect\SocialLogin\Providers\VCS_Provider_Capabilities', false ) ) {
    $ventraconnect_sl_capabilities_path = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ? VENTRACONNECT_SL_PLUGIN_DIR . 'includes/providers/capabilities.php' : __DIR__ . '/../providers/capabilities.php';
    if ( file_exists( $ventraconnect_sl_capabilities_path ) ) {
        require_once $ventraconnect_sl_capabilities_path;
    }
}
if ( ! class_exists( '\VentraConnect\SocialLogin\Services\Avatar', false ) ) {
    $ventraconnect_sl_avatar_path = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ? VENTRACONNECT_SL_PLUGIN_DIR . 'includes/services/class-avatar.php' : __DIR__ . '/class-avatar.php';
    if ( file_exists( $ventraconnect_sl_avatar_path ) ) {
        require_once $ventraconnect_sl_avatar_path;
    }
}

class Profile_Sync {
    const META_SNAPSHOT           = 'ventraconnect_sl_profile_snapshot';
    const META_AVATAR_ID          = 'ventraconnect_sl_avatar_id';
    const META_AVATAR_SOURCE      = 'ventraconnect_sl_avatar_source';
    const META_AVATAR_HASH        = 'ventraconnect_sl_avatar_remote_hash';
    const META_EMAIL_LOG          = 'ventraconnect_sl_email_overwrite_log';
    const META_LAST_SYNC_TIME     = 'ventraconnect_sl_last_profile_sync';
    const META_LAST_SYNC_FIELDS   = 'ventraconnect_sl_last_sync_fields';
    const META_SYNCED_ONCE        = 'ventraconnect_sl_synced_once';
    const META_SOCIAL_VERIFIED    = 'ventraconnect_sl_social_verified';
    const META_NAME_USER_MODIFIED = 'ventraconnect_sl_name_user_modified';
    const META_SYNC_ERROR_LOG     = 'ventraconnect_sl_profile_sync_error_log';

    public static function init() {
        add_action( 'ventraconnect_sl_login_success', [ __CLASS__, 'sync_on_login' ], 20, 4 );
        add_filter( 'get_avatar', [ __CLASS__, 'filter_avatar' ], 10, 6 );
        // Track when a user intentionally edits profile name fields to guard against future overwrites.
        add_action( 'profile_update', [ __CLASS__, 'on_profile_update' ], 10, 2 );
        if ( is_admin() ) {
            add_action( 'wp_ajax_ventraconnect_sl_profile_resync', [ __CLASS__, 'ajax_resync_user' ] );
            add_action( 'wp_ajax_ventraconnect_sl_profile_resync_bulk', [ __CLASS__, 'ajax_resync_bulk' ] );
        }
    }

    public static function sync_on_login( $user_id, $provider, $profile = [], $is_new_user = false ) {
        $user_id  = (int) $user_id;
        $provider = strtolower( (string) $provider );
        if ( $user_id <= 0 || '' === $provider ) {
            return;
        }

        $raw   = (array) $profile;
        $is_resync = false;
        if ( isset( $raw['__resync'] ) ) {
            $is_resync = (bool) $raw['__resync'];
            unset( $raw['__resync'] );
        }
        $data  = VCS_Provider_Data::normalize( $provider, $raw );
        $data  = self::apply_provider_fallbacks( $user_id, $provider, $data );
        $is_new_user = (bool) $is_new_user;
        $caps  = VCS_Provider_Capabilities::get( $provider );
        $sl_flag = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_SYNCED_ONCE, true, '' );
        $first_sync = ! (bool) $sl_flag;
        $updated_fields = [];
        $names_user_modified = (bool) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta(
            $user_id,
            self::META_NAME_USER_MODIFIED,
            true,
            ''
        );

        // Remove any existing profile snapshot (canonical only).
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_profile_snapshot' );
        self::store_snapshot( $user_id, $provider, $data );

        $pro_policies = [];
        if ( defined( 'VCS_PRO_ACTIVE' ) && VCS_PRO_ACTIVE ) {
            $pro_options = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption( 'ventraconnect_sl_sync_pro', [] );
            $pro_policies = (array) ( $pro_options[ $provider ] ?? [] );
        }

        $updated_fields = array_merge(
            $updated_fields,
            self::sync_free_fields( $user_id, $provider, $caps, $data, $first_sync, $pro_policies, $is_new_user, $names_user_modified )
        );

        if ( defined( 'VCS_PRO_ACTIVE' ) && VCS_PRO_ACTIVE ) {
            $updated_fields = array_merge(
                $updated_fields,
                self::sync_pro_fields( $user_id, $provider, $caps, $data, $pro_policies, $is_new_user, $names_user_modified )
            );
        }

        self::woo_store_prefill_bundle( $user_id, $data );

        \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_LAST_SYNC_TIME, current_time( 'mysql' ) );
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_SYNCED_ONCE, 1 );

        $fields = array_values( array_unique( array_filter( $updated_fields ) ) );
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_LAST_SYNC_FIELDS, [
            'provider' => $provider,
            'fields'   => $fields,
        ] );

        // Mark users with a provider-verified email as "verified social users"
        // for downstream features like auto-approving comments.
        $email = isset( $data['email'] ) ? sanitize_email( (string) $data['email'] ) : '';
        $email_is_valid = ( $email && is_email( $email ) );
        $email_verified = ! empty( $data['email_verified'] );
        if ( $email_is_valid && $email_verified ) {
            // Persist canonical-only flag.
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_SOCIAL_VERIFIED, '1' );
        } else {
            // Remove canonical meta when not verified.
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, self::META_SOCIAL_VERIFIED );
        }

        // Consent notice removed: no transient set and no one-time notice displayed after login.
    }

    public static function filter_avatar( $avatar, $id_or_email, $size, $default, $alt, $args ) {
        $user_id = 0;
        if ( is_numeric( $id_or_email ) ) { $user_id = (int) $id_or_email; }
        elseif ( is_object( $id_or_email ) && isset( $id_or_email->user_id ) ) { $user_id = (int) $id_or_email->user_id; }
        elseif ( $id_or_email instanceof \WP_User ) { $user_id = (int) $id_or_email->ID; }
        elseif ( $id_or_email instanceof \WP_Comment ) { $user_id = (int) $id_or_email->user_id; }
        if ( ! $user_id ) { return $avatar; }
        $aid = (int) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_AVATAR_ID, true, 0 );
        if ( $aid ) {
            $url = wp_get_attachment_image_url( $aid, [ $size, $size ] ) ?: wp_get_attachment_url( $aid );
            if ( $url ) {
                $img = '<img alt="' . esc_attr( $alt ) . '" src="' . esc_url( $url ) . '" class="avatar avatar-' . (int) $size . ' photo" height="' . (int) $size . '" width="' . (int) $size . '" />';
                return $img;
            }
        }
        $remote = esc_url_raw( (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_AVATAR_SOURCE, true, '' ) );
        if ( $remote ) {
            $img = '<img alt="' . esc_attr( $alt ) . '" src="' . esc_url( $remote ) . '" class="avatar avatar-' . (int) $size . ' photo" height="' . (int) $size . '" width="' . (int) $size . '" />';
            return $img;
        }
        $placeholder = defined( 'VENTRACONNECT_SL_PLUGIN_URL' ) ? VENTRACONNECT_SL_PLUGIN_URL . 'assets/img/provider-icons/avatar-placeholder.svg' : '';
        $placeholder = apply_filters( 'ventraconnect_sl_avatar_placeholder_url', $placeholder, $user_id, $size, $default, $alt, $args );
        if ( $placeholder ) {
            $img = '<img alt="' . esc_attr( $alt ) . '" src="' . esc_url( $placeholder ) . '" class="avatar avatar-' . (int) $size . ' photo avatar-placeholder" height="' . (int) $size . '" width="' . (int) $size . '" />';
            return $img;
        }
        return $avatar;
    }

    private static function apply_provider_fallbacks( int $user_id, string $provider, array $data ): array {
        return $data;
    }

    private static function store_snapshot( int $user_id, string $provider, array $data ): void {
        $avatar_input = (string) ( $data['avatar_url'] ?? '' );
        $avatar_url   = ( 0 === strpos( $avatar_input, 'data:' ) ) ? $avatar_input : esc_url_raw( $avatar_input );
        $snapshot = [
            'provider'      => $provider,
            'id'            => (string) ( $data['id'] ?? '' ),
            'email'         => (string) ( $data['email'] ?? '' ),
            'email_verified'=> (bool) ( $data['email_verified'] ?? false ),
            'email_flags'   => (array) ( $data['flags'] ?? [] ),
            'display_name'  => (string) ( $data['display_name'] ?? '' ),
            'first_name'    => (string) ( $data['first_name'] ?? '' ),
            'last_name'     => (string) ( $data['last_name'] ?? '' ),
            'avatar_url'    => $avatar_url,
            'avatar_hash'   => self::hash_remote( $avatar_url ),
            'locale'        => (string) ( $data['locale'] ?? '' ),
            'synced_at'     => current_time( 'mysql' ),
        ];
        if ( ! empty( $data['avatar_blob'] ) && is_array( $data['avatar_blob'] ) ) {
            $snapshot['avatar_blob'] = $data['avatar_blob'];
        }
        // Persist canonical snapshot and last-provider.
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_SNAPSHOT, $snapshot );
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, 'ventraconnect_sl_last_provider', $provider );
    }

    private static function sync_free_fields( int $user_id, string $provider, array $caps, array $data, bool $first_sync, array $pro_policies = [], bool $is_new_user = false, bool $names_user_modified = false ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        $free = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption( 'ventraconnect_sl_sync_free', [] );
        $defaults = [
            'avatar'       => 1,
            'display_name' => 1,
            'first_last'   => 1,
            'email'        => 1,
        ];
        $opts = (array) ( $free[ $provider ] ?? [] );
        $opts = $opts + $defaults;

        $updated = [];

        // Free-level display name: if enabled, fill blanks only (free behavior) regardless of Pro policy.
        if ( ! empty( $opts['display_name'] ) && ! empty( $caps['name'] ) ) {
            $ud = get_userdata( $user_id );
            $current = $ud ? $ud->display_name : '';
            $target  = (string) ( $data['display_name'] ?? '' );
            if ( $target && ( '' === $current || ( $ud && $current === $ud->user_login ) ) ) {
                $result = wp_update_user( [ 'ID' => $user_id, 'display_name' => $target ] );
                if ( is_wp_error( $result ) ) {
                    self::log_profile_sync_error( $user_id, 'free_display_name', $result );
                } else {
                    $updated[] = 'display_name';
                }
            }
        }

        // Free-level first/last name: fill blanks only when enabled, irrespective of Pro policy.
        if ( ! empty( $opts['first_last'] ) && ( ! empty( $caps['first_name'] ) || ! empty( $caps['last_name'] ) ) ) {
            $first = (string) ( $data['first_name'] ?? '' );
            $last  = (string) ( $data['last_name'] ?? '' );
            if ( $first && ( $first_sync || '' === get_user_meta( $user_id, 'first_name', true ) ) ) {
                update_user_meta( $user_id, 'first_name', $first );
                $updated[] = 'first_name';
            }
            if ( $last && ( $first_sync || '' === get_user_meta( $user_id, 'last_name', true ) ) ) {
                update_user_meta( $user_id, 'last_name', $last );
                $updated[] = 'last_name';
            }
        }

        // Free-level email sync: fill blanks only when enabled, irrespective of Pro policy.
        if ( ! empty( $opts['email'] ) && ! empty( $caps['email'] ) ) {
            // Email may only be set at account creation; never change it on later logins.
            if ( self::can_sync_email_for_user( $user_id, $is_new_user ) ) {
                $ud = get_userdata( $user_id );
                $target = sanitize_email( (string) ( $data['email'] ?? '' ) );
                if ( $target && $ud && '' === $ud->user_email ) {
                    $result = wp_update_user( [ 'ID' => $user_id, 'user_email' => $target ] );
                    if ( is_wp_error( $result ) ) {
                        self::log_profile_sync_error( $user_id, 'free_email', $result );
                    } else {
                        $updated[] = 'email';
                    }
                }
            }
        }

        if ( ! empty( $opts['avatar'] ) && ! empty( $caps['avatar'] ) ) {
            // Free-level avatar toggle always permits a fill_blanks sync. Pro policies apply only when free toggle is off.
            $updated = array_merge( $updated, self::handle_avatar( $user_id, $caps, $data, 'fill_blanks' ) );
        } else {
            if ( empty( $opts['avatar'] ) ) {
                // error_log( sprintf( '[VCS][ProfileSync] Skipping avatar: disabled in settings for user %d, provider=%s', $user_id, (string) ( $data['provider'] ?? '' ) ) ); // Commented out for production per WPCS.
            } elseif ( empty( $caps['avatar'] ) ) {
                // error_log( sprintf( '[VCS][ProfileSync] Skipping avatar: provider lacks avatar capability for user %d, provider=%s', $user_id, (string) ( $data['provider'] ?? '' ) ) ); // Commented out for production per WPCS.
            }
        }

        return $updated;
    }

    private static function sync_pro_fields( int $user_id, string $provider, array $caps, array $data, array $policies, bool $is_new_user = false, bool $names_user_modified = false ): array {
        $updated = [];

        foreach ( [ 'display_name', 'first_name', 'last_name' ] as $field ) {
            $cap_key = ( 'display_name' === $field ) ? 'name' : $field;
            if ( empty( $caps[ $cap_key ] ) ) {
                continue;
            }
            $policy = self::policy( $policies, $field );
            $value  = (string) ( $data[ $field ] ?? '' );
            if ( 'never' === $policy || '' === $value ) {
                continue;
            }
            if ( 'display_name' === $field ) {
                $ud = get_userdata( $user_id );
                $current = $ud ? $ud->display_name : '';
                // When a user has manually edited their name, treat overwrite policies as fill_blanks for names.
                if ( $names_user_modified && '' !== $current && 'overwrite' === $policy ) {
                    $policy = 'fill_blanks';
                }
                if ( 'overwrite' === $policy || '' === $current || ( $ud && $current === $ud->user_login ) ) {
                    $result = wp_update_user( [ 'ID' => $user_id, 'display_name' => $value ] );
                    if ( is_wp_error( $result ) ) {
                        self::log_profile_sync_error( $user_id, 'pro_display_name', $result );
                    } else {
                        $updated[] = 'display_name';
                    }
                }
            } else {
                $current = (string) get_user_meta( $user_id, $field, true );
                if ( $names_user_modified && '' !== $current && 'overwrite' === $policy ) {
                    $policy = 'fill_blanks';
                }
                if ( 'overwrite' === $policy || '' === $current ) {
                    update_user_meta( $user_id, $field, $value );
                    $updated[] = $field;
                }
            }
        }

        if ( ! empty( $caps['email'] ) && self::can_sync_email_for_user( $user_id, $is_new_user ) ) {
            $policy = self::policy( $policies, 'email' );
            $value  = sanitize_email( (string) ( $data['email'] ?? '' ) );
            if ( $value && 'never' !== $policy ) {
                $did_update = self::maybe_update_email( $user_id, $provider, $value, $data, $policy );
                if ( $did_update ) {
                    $updated[] = 'email';
                }
            }
        }

        if ( ! empty( $caps['avatar'] ) ) {
            $avatar_policy = self::policy( $policies, 'avatar' );
            $updated = array_merge( $updated, self::handle_avatar( $user_id, $caps, $data, $avatar_policy ) );
        }

        $meta_fields = [
            'locale'      => 'ventraconnect_sl_locale',
            'profile_url' => 'ventraconnect_sl_profile_url',
            'company'     => 'ventraconnect_sl_company',
            'headline'    => 'ventraconnect_sl_headline',
            'website'     => 'ventraconnect_sl_website',
            'location'    => 'ventraconnect_sl_location_text',
        ];
        foreach ( $meta_fields as $field => $meta_key ) {
            if ( empty( $caps[ $field ] ) ) {
                continue;
            }
            $policy = self::policy( $policies, $field );
            if ( 'never' === $policy ) {
                continue;
            }
            $value = (string) ( $data[ $field ] ?? '' );
            if ( '' === $value ) {
                continue;
            }
            $current = (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, $meta_key, true, '' );
            if ( 'overwrite' === $policy || '' === $current ) {
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, $meta_key, $value );
                $updated[] = $field;
            }
        }

        if ( ! empty( $policies['map_woo_billing'] ) ) {
            $first = (string) get_user_meta( $user_id, 'first_name', true );
            $last  = (string) get_user_meta( $user_id, 'last_name', true );
            $email = (string) get_userdata( $user_id )->user_email;
            if ( $first ) { update_user_meta( $user_id, 'billing_first_name', $first ); }
            if ( $last ) { update_user_meta( $user_id, 'billing_last_name', $last ); }
            if ( $email ) { update_user_meta( $user_id, 'billing_email', $email ); }
        }

        return $updated;
    }

    private static function policy( array $policies, string $field ): string {
        $policy = isset( $policies[ $field ] ) ? (string) $policies[ $field ] : 'never';
        if ( ! in_array( $policy, [ 'never', 'fill_blanks', 'overwrite' ], true ) ) {
            $policy = 'never';
        }
        return $policy;
    }

    private static function maybe_update_email( int $user_id, string $provider, string $new_email, array $data, string $policy ): bool {
        $ud = get_userdata( $user_id );
        if ( ! $ud ) {
            return false;
        }

        if ( 'fill_blanks' === $policy && '' !== $ud->user_email ) {
            return false;
        }
        if ( empty( $data['email_verified'] ) ) {
            self::log_email_skip( $user_id, $provider, 'not_verified' );
            return false;
        }
        if ( self::is_private_relay_flagged( $data ) ) {
            if ( 'overwrite' === $policy ) {
                $allow_relay = get_option( 'ventraconnect_sl_email_allow_relay_overwrite', [] );
                if ( empty( $allow_relay ) ) {
                    $allow_relay = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption( 'ventraconnect_sl_email_allow_relay_overwrite', [] );
                }
                $allow = isset( $allow_relay[ $provider ] ) && (bool) $allow_relay[ $provider ];
                if ( ! $allow && ! self::is_private_relay_email( $ud->user_email ) ) {
                    self::log_email_skip( $user_id, $provider, 'relay_guard' );
                    return false;
                }
            } else {
                self::log_email_skip( $user_id, $provider, 'relay_guard' );
                return false;
            }
        }

        if ( 'overwrite' === $policy && $ud->user_email && strtolower( $ud->user_email ) !== strtolower( $new_email ) ) {
            $links = new User_Links();
            $connections = $links->get_connections( $user_id );
            foreach ( $connections as $connection ) {
                $slug = strtolower( $connection['provider'] ?? '' );
                if ( ! $slug || $slug === $provider ) {
                    continue;
                }
                $linked_email = strtolower( (string) ( $connection['email'] ?? '' ) );
                if ( $linked_email && $linked_email === strtolower( $ud->user_email ) ) {
                    self::log_email_skip( $user_id, $provider, 'cross_link' );
                    return false;
                }
            }
        }

        $result = wp_update_user( [ 'ID' => $user_id, 'user_email' => $new_email ] );
        if ( is_wp_error( $result ) ) {
            self::log_profile_sync_error( $user_id, 'pro_email', $result );
            return false;
        }
        return true;
    }

    private static function handle_avatar( int $user_id, array $caps, array $data, string $policy ): array {
        if ( empty( $caps['avatar'] ) || 'never' === $policy ) {
            return [];
        }
        $avatar_input = (string) ( $data['avatar_url'] ?? '' );
        $url = ( 0 === strpos( $avatar_input, 'data:' ) ) ? $avatar_input : esc_url_raw( $avatar_input );
        $provider_slug = strtolower( (string) ( $data['provider'] ?? '' ) );
        if ( $url && 'microsoft' === $provider_slug && false !== stripos( $url, 'graph.microsoft.com' ) ) {
            $url = '';
        }
        if ( ! $url && ! empty( $data['avatar_blob'] ) && is_array( $data['avatar_blob'] ) && ! empty( $data['avatar_blob']['data'] ) ) {
            $blob_type = ! empty( $data['avatar_blob']['type'] ) ? $data['avatar_blob']['type'] : 'image/jpeg';
            $url = 'data:' . $blob_type . ';base64,' . $data['avatar_blob']['data'];
        }
        $updated = [];

        if ( $url && 0 !== strpos( $url, 'data:' ) ) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_AVATAR_SOURCE, $url );
        } else {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, self::META_AVATAR_SOURCE );
        }

        if ( ! $url ) {
            return $updated;
        }

        $existing_id = (int) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_AVATAR_ID, true, 0 );
        $stored_hash = (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_AVATAR_HASH, true, '' );
        $new_hash    = self::hash_remote( $url );
        if ( ! $url ) {
            return $updated;
        }

        if ( 'fill_blanks' === $policy && $existing_id ) {
            // error_log( sprintf( '[VCS][ProfileSync] Skipping avatar download: fill_blanks policy and existing avatar ID %d for user %d', $existing_id, $user_id ) ); // Commented out for production per WPCS.
            return $updated;
        }

        if ( 'overwrite' === $policy && $stored_hash && $stored_hash === $new_hash && $existing_id ) {
            return $updated;
        }

        $id = Avatar::download_and_attach( $url, $user_id );
        if ( ! $id ) {
            // error_log( sprintf( '[VCS][ProfileSync] Avatar download failed for user %d, provider=%s, url=%s', $user_id, (string) ( $data['provider'] ?? '' ), $url ) ); // Commented out for production per WPCS.
        }
        if ( $id ) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_AVATAR_ID, $id );
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, self::META_AVATAR_SOURCE );
            if ( $new_hash ) {
                \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_AVATAR_HASH, $new_hash );
            }
            $updated[] = 'avatar';
        }

        return $updated;
    }

    private static function log_email_skip( int $user_id, string $provider, string $reason ): void {
        $log = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_EMAIL_LOG, true, [] );
        $log[] = [
            'provider' => $provider,
            'reason'   => $reason,
            'time'     => current_time( 'mysql' ),
        ];
        if ( count( $log ) > 5 ) {
            $log = array_slice( $log, -5 );
        }
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, self::META_EMAIL_LOG, $log );
        /**
         * Fires when an email overwrite is skipped.
         *
         * @param int    $user_id  User ID.
         * @param string $provider Provider slug.
         * @param string $reason   Reason code.
         */
        do_action( 'ventraconnect_sl_email_overwrite_skipped', $user_id, $provider, $reason );
        if ( apply_filters( 'ventraconnect_sl_log_email_overwrite_skip', true, $user_id, $provider, $reason ) ) {
            // error_log( sprintf( '[VCS] Email overwrite skipped for user %d via %s: %s', $user_id, $provider, $reason ) ); // Commented out for production per WPCS.
        }
      }

    /**
     * Determine whether profile sync is allowed to touch user_email.
     *
     * Email is intentionally immutable after account creation; only new-account
     * flows are allowed to set user_email. This must be checked before any
     * call that attempts to write user_email from provider data.
     *
     * @param int  $user_id     User ID.
     * @param bool $is_new_user Whether this sync is for a newly created user.
     * @return bool
     */
    private static function can_sync_email_for_user( int $user_id, bool $is_new_user ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        if ( ! $is_new_user ) {
            return false;
        }
        return true;
    }

    /**
     * Log profile sync failures when updating core user fields.
     *
     * This is intentionally debug-focused: logs are written only when the
     * plugin's debug mode is enabled and WP_DEBUG is true, and a compact
     * history is stored in user meta for inspection.
     *
     * @param int       $user_id User ID.
     * @param string    $context Short context string (e.g. 'free_email').
     * @param \WP_Error $error   Error from wp_update_user/wp_insert_user.
     */
    private static function log_profile_sync_error( int $user_id, string $context, \WP_Error $error ): void {
        if ( $user_id <= 0 || ! $error instanceof \WP_Error ) {
            return;
        }

        $settings = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();
        $debug_enabled = ! empty( $settings['debug_mode'] );

        if ( $debug_enabled && defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Runs only when debug flags are enabled.
            error_log(
                sprintf(
                    '[VCS][ProfileSync] %s failed for user %d: %s (%s)',
                    $context,
                    $user_id,
                    $error->get_error_message(),
                    $error->get_error_code()
                )
            );
        }

        $log = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta(
            $user_id,
            self::META_SYNC_ERROR_LOG,
            true,
            []
        );
        $log[] = [
            'context' => $context,
            'code'    => $error->get_error_code(),
            'message' => $error->get_error_message(),
            'time'    => current_time( 'mysql' ),
        ];
        if ( count( $log ) > 5 ) {
            $log = array_slice( $log, -5 );
        }
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta(
            $user_id,
            self::META_SYNC_ERROR_LOG,
            $log
        );
    }
  
      private static function woo_store_prefill_bundle( int $user_id, array $data ): void {
        $wc = function_exists( 'WC' ) ? WC() : null;
        $bundle = [
            'first_name'    => (string) ( $data['first_name'] ?? '' ),
            'last_name'     => (string) ( $data['last_name'] ?? '' ),
            'email'         => (string) ( $data['email'] ?? '' ),
            'country_guess' => self::guess_country( (string) ( $data['locale'] ?? '' ) ),
        ];
        $bundle = array_filter( $bundle );
        if ( empty( $bundle ) ) {
            \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_checkout_prefill' );
            return;
        }
        if ( $wc && isset( $wc->session ) && $wc->session ) {
            // Set canonical session key.
            $wc->session->set( 'ventraconnect_sl_checkout_prefill', $bundle );
        }
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta( $user_id, 'ventraconnect_sl_checkout_prefill', $bundle );
        \VentraConnect\SocialLogin\Admin\Settings\Persistence::deleteUserMeta( $user_id, 'ventraconnect_sl_checkout_prefill' );
    }

      private static function guess_country( string $locale ): string {
        if ( '' === $locale || false === strpos( $locale, '_' ) ) {
            return '';
        }
        $parts = explode( '_', $locale );
        $last  = strtoupper( array_pop( $parts ) );
        if ( strlen( $last ) === 2 ) {
            return $last;
        }
        return '';
    }

      private static function hash_remote( string $url ): string {
          if ( '' === $url ) {
              return '';
          }
          return substr( md5( $url ), 0, 12 );
      }
 
      /**
       * When a user updates their profile, record that name fields were edited
       * so that future profile syncs avoid overwriting human-edited names.
       *
       * @param int      $user_id       User ID.
       * @param \WP_User $old_user_data User object before the update.
       */
      public static function on_profile_update( $user_id, $old_user_data ): void {
          $user_id = (int) $user_id;
          if ( $user_id <= 0 || ! $old_user_data instanceof \WP_User ) {
              return;
          }

          $new_user = get_userdata( $user_id );
          if ( ! $new_user ) {
              return;
          }

          $old_display = (string) $old_user_data->display_name;
          $new_display = (string) $new_user->display_name;

          // WP_User exposes first_name/last_name via magic properties backed by user meta.
          $old_first = (string) $old_user_data->first_name;
          $old_last  = (string) $old_user_data->last_name;
          $new_first = (string) get_user_meta( $user_id, 'first_name', true );
          $new_last  = (string) get_user_meta( $user_id, 'last_name', true );

          if ( $old_display !== $new_display || $old_first !== $new_first || $old_last !== $new_last ) {
              \VentraConnect\SocialLogin\Admin\Settings\Persistence::updateUserMeta(
                  $user_id,
                  self::META_NAME_USER_MODIFIED,
                  1
              );
          }
      }

      public static function resync_user( int $user_id, string $provider = '' ): bool {
        $user_id = (int) $user_id;
        if ( $user_id <= 0 ) {
            return false;
        }
        $snapshot = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_SNAPSHOT, true, [] );
        if ( ! is_array( $snapshot ) || empty( $snapshot ) ) {
            return false;
        }
        if ( ! is_array( $snapshot ) ) {
            return false;
        }
        $target_provider = $provider ? strtolower( $provider ) : strtolower( (string) ( $snapshot['provider'] ?? '' ) );
        if ( '' === $target_provider ) {
            return false;
        }
        $payload = [
            '__resync'        => true,
            'id'              => $snapshot['id'] ?? '',
            'email'           => $snapshot['email'] ?? '',
            'email_verified'  => ! empty( $snapshot['email_verified'] ),
            'name'            => $snapshot['display_name'] ?? '',
            'display_name'    => $snapshot['display_name'] ?? '',
            'first_name'      => $snapshot['first_name'] ?? '',
            'last_name'       => $snapshot['last_name'] ?? '',
            'avatar_url'      => $snapshot['avatar_url'] ?? '',
            'locale'          => $snapshot['locale'] ?? '',
        ];
          if ( ! empty( $snapshot['avatar_blob'] ) && is_array( $snapshot['avatar_blob'] ) ) {
              $payload['avatar_blob'] = $snapshot['avatar_blob'];
          }
          self::sync_on_login( $user_id, $target_provider, $payload, false );
          return true;
      }

    public static function ajax_resync_user() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array(
                    'code'    => 'forbidden',
                    'message' => __( 'You do not have permission to perform this action.', 'ventraconnect-social-login' ),
                ),
                403
            );
        }

        $user_id = 0;
        if ( isset( $_POST['user_id'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- We must read user_id before check_ajax_referer() because the nonce action is per-user (ventraconnect_sl_resync_user_{$user_id}). The value is immediately sanitized with absint(), and no other processing occurs before the nonce check.
            $user_id = absint( wp_unslash( (string) $_POST['user_id'] ) );
        }
        if ( empty( $user_id ) ) {
            wp_send_json_error(
                array(
                    'code'    => 'invalid_request',
                    'message' => __( 'Invalid user ID.', 'ventraconnect-social-login' ),
                )
            );
        }
        check_ajax_referer( 'ventraconnect_sl_resync_user_' . $user_id, 'nonce' );
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to resync this user.', 'ventraconnect-social-login' ) ] );
        }
        $provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
        $result = self::resync_user( $user_id, $provider );
        if ( ! $result ) {
            wp_send_json_error( [ 'message' => __( 'No stored profile snapshot available for resync.', 'ventraconnect-social-login' ) ] );
        }
        $summary = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_LAST_SYNC_FIELDS, true, [] );
        $time    = (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, self::META_LAST_SYNC_TIME, true, '' );
        wp_send_json_success( [
            'user_id'  => $user_id,
            'provider' => $provider ?: (string) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getUserMeta( $user_id, 'ventraconnect_sl_last_provider', true, '' ),
            'fields'   => (array) ( $summary['fields'] ?? [] ),
            'synced_at'=> $time,
        ] );
    }

    public static function ajax_resync_bulk() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                array(
                    'code'    => 'forbidden',
                    'message' => __( 'You do not have permission to perform this action.', 'ventraconnect-social-login' ),
                ),
                403
            );
        }
        check_ajax_referer( 'ventraconnect_sl_resync_bulk', 'nonce' );
        $provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
        $dry_run  = ! empty( $_POST['dry_run'] );
        $batch    = isset( $_POST['batch'] ) ? (int) $_POST['batch'] : 20;
        if ( $batch <= 0 ) { $batch = 20; }
        $batch = min( $batch, 100 );

    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for user sync, only runs in admin/bulk actions, not on every page load.
    $meta_query = [
            'relation' => 'OR',
            [
                'key'     => self::META_SNAPSHOT,
                'compare' => 'EXISTS',
            ],
        ];
        if ( $provider ) {
            $meta_query[] = [
                'key'   => 'ventraconnect_sl_last_provider',
                'value' => $provider,
            ];
        }

        if ( $dry_run ) {
            $query = new \WP_User_Query( [
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for user sync, only runs in admin/bulk actions, not on every page load.
                'meta_query'  => $meta_query,
                'fields'      => 'ID',
                'number'      => 1,
                'count_total' => true,
            ] );
            wp_send_json_success( [
                'total'    => (int) $query->get_total(),
                'provider' => $provider,
            ] );
        }

        $cooldown_key = 'ventraconnect_sl_resync_cd_' . md5( $provider ?: 'all' );
        if ( \VentraConnect\SocialLogin\Admin\Settings\Persistence::getTransient( $cooldown_key ) ) {
            wp_send_json_error( [ 'message' => __( 'Bulk resync is cooling down. Please try again shortly.', 'ventraconnect-social-login' ) ] );
        }

        $query = new \WP_User_Query( [
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for user sync, only runs in admin/bulk actions, not on every page load.
            'meta_query'  => $meta_query,
            'fields'      => 'ID',
            'number'      => $batch,
            'orderby'     => 'ID',
            'order'       => 'ASC',
            'count_total' => true,
        ] );
        $ids = (array) $query->get_results();
        if ( empty( $ids ) ) {
            wp_send_json_success( [
                'processed' => 0,
                'provider'  => $provider,
            ] );
        }

        $processed = 0;
        $skipped   = 0;
        foreach ( $ids as $id ) {
            if ( self::resync_user( (int) $id, $provider ) ) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        \VentraConnect\SocialLogin\Admin\Settings\Persistence::setTransient( $cooldown_key, time(), 30 );

        wp_send_json_success( [
            'processed' => $processed,
            'skipped'   => $skipped,
            'provider'  => $provider,
            'batch'     => $batch,
            'remaining' => max( 0, (int) $query->get_total() - ( $processed + $skipped ) ),
        ] );
    }
}

// Auto-initialize on load
\VentraConnect\SocialLogin\Services\Profile_Sync::init();
