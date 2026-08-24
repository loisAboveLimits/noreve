<?php
namespace VentraConnect\SocialLogin\Admin\IntegrationsPreview;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Build context for integrations preview (moved from Settings::build_integrations_preview_context).
 */
class Context {
	/**
	 * @param string                   $category
	 * @param array<string,mixed>      $settings  Main settings with optional 'ventraconnect_sl_integrations' key
	 * @param array<string,mixed>      $creds
	 * @param string                   $globalTheme
	 * @return array<string,mixed>
	 */
	public static function build( string $category, array $settings, array $creds, string $globalTheme ): array { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
		$blueprints = Blueprints::definitions();
		if ( empty( $blueprints[ $category ] ) ) {
			return [];
		}
		$group    = $blueprints[ $category ];
		$defaults = Settings::defaults();
		$stored   = (array) ( $settings['ventraconnect_sl_integrations'] ?? [] );
		$items    = [];

		foreach ( (array) ( $group['integrations'] ?? [] ) as $slug => $meta ) {
			$merged = Settings::merge( (string) $slug, $defaults, $stored );
			if ( empty( $merged ) ) {
				continue;
			}
			$items[ $slug ] = [
				'slug'        => $slug,
				'settings'    => $merged,
				'meta'        => [
					'name'        => (string) ( $meta['name'] ?? ucfirst( (string) $slug ) ),
					'description' => (string) ( $meta['description'] ?? '' ),
					'places'      => (array) ( $meta['places'] ?? [] ),
				],
				'detected'    => [ 'detected' => false, 'version' => '' ],
				'status_text' => esc_html__( 'Requires Pro', 'ventraconnect-social-login' ),
			];
		}

		if ( empty( $items ) ) {
			return [];
		}

		return [
			'title'           => (string) ( $group['title'] ?? '' ),
			'description'     => (string) ( $group['description'] ?? '' ),
			'option_group'    => (string) ( $group['option_group'] ?? 'ventraconnect_sl_integrations_preview' ),
			'option_key'      => 'ventraconnect_sl_integrations',
			'items'           => $items,
			'post_login_note' => (string) ( $group['post_login_note'] ?? '' ),
			'tab_slug'        => (string) ( $group['tab_slug'] ?? $category ),
			'locked'          => true,
			'locked_note'     => esc_html__( 'Upgrade to Pro to edit these settings.', 'ventraconnect-social-login' ),
			'upsell'          => (array) ( $group['upsell'] ?? [] ),
		];
	}
}
