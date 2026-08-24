<?php
namespace VentraConnect\SocialLogin\Admin\IntegrationsPreview;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Orchestrates integrations preview tab rendering.
 */
class Tab {
	/**
	 * Render a preview tab for the given category when Pro is inactive.
	 *
	 * @param string                   $category
	 * @param array<string,mixed>      $settings  Should include optional 'ventraconnect_sl_integrations'
	 * @param array<string,mixed>      $creds
	 * @param string                   $globalTheme
	 */
	public function render( string $category, array $settings, array $creds, string $globalTheme ): void {
		$context = Context::build( $category, $settings, $creds, $globalTheme );
		if ( empty( $context ) ) {
			$this->render_upsell_card( $category );
			return;
		}
		$hook = 'ventraconnect_sl_render_integrations_preview_' . $category;
		if ( has_action( $hook ) ) {
			/**
			 * Allow Pro (or other extensions) to render full integrations UI.
			 *
			 * @since 1.2.0
			 * @param array<string,mixed> $context
			 */
			do_action( $hook, $context ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Dynamic integration preview hook is constructed immediately above with the ventraconnect_sl_ prefix.
			return;
		}
		Renderer::renderMarkup( $context );
	}

	/**
	 * Render fallback upsell card using blueprints data.
	 */
	private function render_upsell_card( string $category ): void {
		$blueprints = Blueprints::definitions();
		if ( empty( $blueprints[ $category ]['upsell'] ) ) {
			return;
		}
		$upsell = $blueprints[ $category ]['upsell'];
		$title = (string) ( $upsell['title'] ?? '' );
		$description = (string) ( $upsell['description'] ?? '' );
		$cta_label = (string) ( $upsell['cta_label'] ?? '' );
		$cta_url   = (string) ( $upsell['cta_url'] ?? '' );
		echo '<div class="wsc-card vcs-integrations-upsell">';
		if ( '' !== $title ) {
			echo '<h3>' . esc_html( $title ) . '</h3>';
		}
		if ( '' !== $description ) {
			echo '<p class="wsc-muted">' . esc_html( $description ) . '</p>';
		}
		if ( '' !== $cta_label && '' !== $cta_url ) {
			echo '<a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url( $cta_url ) . '">' . esc_html( $cta_label ) . '</a>';
		}
		echo '</div>';
	}
}
