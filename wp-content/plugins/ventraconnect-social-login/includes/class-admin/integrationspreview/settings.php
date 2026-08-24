<?php
namespace VentraConnect\SocialLogin\Admin\IntegrationsPreview;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Integrations preview settings/defaults (moved from Settings::*integration* methods).
 */
class Settings {
	/**
	 * Defaults for integrations preview.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function defaults(): array {
		return [
			'woo_memberships' => [
				'enabled'    => false,
				'places'     => [
					'restricted' => true,
					'my_account' => true,
				],
				'post_login'   => 'stay',
				'custom_url'   => '',
				'plan_page_id' => 0,
			],
			'pmpro' => [
				'enabled'    => false,
				'places'     => [
					'login'    => true,
					'checkout' => true,
				],
				'post_login' => 'stay',
				'custom_url' => '',
			],
			'ultimate_member' => [
				'enabled'   => false,
				'places'    => [
					'login'    => true,
					'register' => true,
				],
				'positions' => [
					'login'    => 'below_form',
					'register' => 'below_form',
				],
				'post_login_login'    => 'stay',
				'post_login_register' => 'stay',
				'custom_url_login'    => '',
				'custom_url_register' => '',
				'show_profile_links'  => true,
			],
			'buddypress' => [
				'enabled' => false,
				'places'  => [
					'login'    => true,
					'register' => true,
				],
			],
			'peepso' => [
				'enabled' => false,
				'places'  => [
					'login'    => true,
					'register' => true,
				],
			],
			'learndash' => [
				'enabled'    => false,
				'places'     => [
					'login'    => true,
					'register' => true,
				],
				'post_login' => 'stay',
				'custom_url' => '',
			],
			'tutor_lms' => [
				'enabled'    => false,
				'places'     => [
					'login'               => true,
					'student_register'    => true,
					'instructor_register' => true,
				],
				'post_login' => 'stay',
				'custom_url' => '',
			],
			'lifterlms' => [
				'enabled'    => false,
				'places'     => [
					'login'    => true,
					'register' => true,
					'account'  => true,
				],
				'post_login' => 'stay',
				'custom_url' => '',
			],
		];
	}

	/**
	 * Merge stored integration configuration with defaults for preview output.
	 *
	 * @param string                         $slug
	 * @param array<string,array>            $defaults
	 * @param array<string,mixed>            $stored
	 * @return array<string,mixed>
	 */
	public static function merge( string $slug, array $defaults, array $stored ): array {
		if ( empty( $defaults[ $slug ] ) || ! is_array( $defaults[ $slug ] ) ) {
			return [];
		}
		$base    = $defaults[ $slug ];
		$current = isset( $stored[ $slug ] ) && is_array( $stored[ $slug ] ) ? $stored[ $slug ] : [];

		if ( array_key_exists( 'enabled', $base ) ) {
			$base['enabled'] = ! empty( $current['enabled'] );
		}

		if ( isset( $base['places'] ) && is_array( $base['places'] ) ) {
			foreach ( $base['places'] as $place => $flag ) {
				$base['places'][ $place ] = isset( $current['places'][ $place ] ) ? (bool) $current['places'][ $place ] : (bool) $flag;
			}
		}

		if ( array_key_exists( 'post_login', $base ) ) {
			$base['post_login'] = isset( $current['post_login'] ) ? (string) $current['post_login'] : (string) $base['post_login'];
		}

		if ( array_key_exists( 'custom_url', $base ) ) {
			$base['custom_url'] = isset( $current['custom_url'] ) ? \esc_url_raw( $current['custom_url'] ) : (string) $base['custom_url'];
		}

		if ( array_key_exists( 'plan_page_id', $base ) ) {
			$base['plan_page_id'] = isset( $current['plan_page_id'] ) ? \absint( $current['plan_page_id'] ) : \absint( $base['plan_page_id'] );
		}

		return $base;
	}
}
