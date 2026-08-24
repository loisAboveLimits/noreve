<?php
namespace VentraConnect\SocialLogin\Providers;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class VCS_Provider_Data {
    public static function normalize( string $slug, array $profile ): array {
        $slug = strtolower( $slug );
        $raw  = is_array( $profile['raw'] ?? null ) ? $profile['raw'] : [];

        $id = (string) ( $profile['id'] ?? $raw['sub'] ?? $raw['id'] ?? '' );
        $email = sanitize_email( (string) ( $profile['email'] ?? $raw['email'] ?? '' ) );
        $email_verified = (bool) (
            $profile['email_verified'] ??
            $raw['email_verified'] ??
            $raw['verified_email'] ??
            $raw['email_verified_default'] ??
            false
        );

        $locale = (string) ( $profile['locale'] ?? $raw['locale'] ?? $raw['lang'] ?? $raw['language'] ?? '' );
        $profile_url = (string) ( $profile['profile_url'] ?? $raw['profile'] ?? $raw['profile_URL'] ?? $raw['html_url'] ?? $raw['link'] ?? $raw['url'] ?? '' );
        $company = (string) ( $profile['company'] ?? $raw['company'] ?? '' );
        $headline = (string) ( $profile['headline'] ?? $raw['headline'] ?? '' );
        $website = (string) ( $profile['website'] ?? $raw['website'] ?? $raw['blog'] ?? '' );
        $location = (string) ( $profile['location'] ?? $raw['location'] ?? '' );

        $avatar_url = (string) ( $profile['avatar_url'] ?? $profile['avatar'] ?? $raw['avatar'] ?? $raw['picture'] ?? $raw['avatar_url'] ?? $raw['avatar_URL'] ?? '' );
        if ( 0 === strpos( $avatar_url, 'data:' ) ) {
            $avatar_url = $avatar_url;
        } else {
            $avatar_url = esc_url_raw( $avatar_url );
        }

        $name_block = is_array( $raw['name'] ?? null ) ? (array) $raw['name'] : [];
        $name_block_display = '';
        if ( $name_block ) {
            $name_block_display = trim( (string) ( $name_block['first'] ?? '' ) . ' ' . (string) ( $name_block['last'] ?? '' ) );
            if ( ! $name_block_display && ! empty( $name_block['display_name'] ) ) {
                $name_block_display = (string) $name_block['display_name'];
            }
        }
        $given_raw  = $profile['given_name'] ?? $profile['first_name'] ?? $raw['given_name'] ?? $raw['first_name'] ?? $raw['firstName'] ?? ( $name_block['first'] ?? '' );
        $family_raw = $profile['family_name'] ?? $profile['last_name'] ?? $raw['family_name'] ?? $raw['last_name'] ?? $raw['lastName'] ?? ( $name_block['last'] ?? '' );
        $display_raw = $profile['name'] ?? $profile['display_name'] ?? ( $name_block_display ?: ( $raw['display_name'] ?? '' ) ) ?? $raw['username'] ?? '';

        $name_parts = self::split_name( $given_raw, $family_raw, $display_raw );

        $flags = [];
        if ( $email && ! $email_verified ) {
            $flags[] = 'email_unverified';
        }

        $snapshot = [
            'id'            => $id,
            'provider'      => $slug,
            'email'         => $email,
            'email_verified'=> $email_verified,
            'display_name'  => $name_parts['display_name'],
            'first_name'    => $name_parts['first_name'],
            'last_name'     => $name_parts['last_name'],
            'avatar_url'    => $avatar_url,
            'locale'        => $locale,
            'profile_url'   => $profile_url,
            'headline'      => $headline,
            'company'       => $company,
            'website'       => $website,
            'location'      => $location,
            'flags'         => $flags,
        ];
        if ( ! empty( $profile['avatar_blob'] ) && is_array( $profile['avatar_blob'] ) ) {
            $snapshot['avatar_blob'] = $profile['avatar_blob'];
        }

        return apply_filters( 'ventraconnect_sl_normalized_profile', $snapshot, $slug, $profile, $raw );
    }

    private static function split_name( $given, $family, $display ): array {
        $given = sanitize_text_field( (string) $given );
        $family = sanitize_text_field( (string) $family );
        $display = trim( sanitize_text_field( (string) $display ) );

        if ( $given || $family ) {
            $first = $given ?: ( $display ? self::first_part( $display ) : '' );
            $last  = $family ?: ( $display ? self::last_part( $display ) : '' );
        } elseif ( $display ) {
            $first = self::first_part( $display, true );
            $last  = self::last_part( $display );
        } else {
            $first = ''; $last = '';
        }

        $display_name = $display;
        if ( ! $display_name ) {
            $display_name = trim( $first . ' ' . $last );
        }

        return [
            'first_name'   => $first,
            'last_name'    => $last,
            'display_name' => $display_name ?: ( $first ?: $last ),
        ];
    }

    private static function first_part( string $name, bool $include_first_token_only = false ): string {
        $tokens = preg_split( '/\s+/', trim( $name ), -1, PREG_SPLIT_NO_EMPTY );
        if ( empty( $tokens ) ) { return ''; }
        if ( $include_first_token_only ) {
            return sanitize_text_field( $tokens[0] );
        }
        if ( count( $tokens ) === 1 ) {
            return sanitize_text_field( $tokens[0] );
        }
        array_pop( $tokens );
        return sanitize_text_field( implode( ' ', $tokens ) );
    }

    private static function last_part( string $name ): string {
        $tokens = preg_split( '/\s+/', trim( $name ), -1, PREG_SPLIT_NO_EMPTY );
        if ( empty( $tokens ) ) { return ''; }
        return sanitize_text_field( $tokens[ count( $tokens ) - 1 ] );
    }
}
