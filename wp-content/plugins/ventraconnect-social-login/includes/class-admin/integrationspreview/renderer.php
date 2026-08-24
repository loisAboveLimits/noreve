<?php
namespace VentraConnect\SocialLogin\Admin\IntegrationsPreview;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Echo the integrations preview markup (moved from Settings::render_integrations_preview_markup).
 */
class Renderer {
	/**
	 * @param array<string,mixed> $context
	 */
	public static function renderMarkup( array $context ): void {
		$title       = isset( $context['title'] ) ? (string) $context['title'] : '';
		$description = isset( $context['description'] ) ? (string) $context['description'] : '';
		$items       = isset( $context['items'] ) && is_array( $context['items'] ) ? $context['items'] : [];
		$locked_note = isset( $context['locked_note'] ) ? (string) $context['locked_note'] : esc_html__( 'Unlock with Pro to edit these integrations.', 'ventraconnect-social-login' );
		$upsell      = isset( $context['upsell'] ) && is_array( $context['upsell'] ) ? $context['upsell'] : [];

		echo '<div class="wsc-card wsc-locked">';
		if ( $title ) {
			echo '<h3>' . esc_html( $title ) . ' <span class="wsc-pill">' . esc_html__( 'Pro', 'ventraconnect-social-login' ) . '</span></h3>';
		}
		if ( $description ) {
			echo '<p class="wsc-muted">' . esc_html( $description ) . '</p>';
		}
		if ( ! empty( $items ) ) {
			echo '<ul class="wsc-integration-preview">';
			foreach ( $items as $slug => $item ) {
				$meta = isset( $item['meta'] ) && is_array( $item['meta'] ) ? $item['meta'] : [];
				$name = isset( $meta['name'] ) ? (string) $meta['name'] : ucfirst( (string) $slug );
				$desc = isset( $meta['description'] ) ? (string) $meta['description'] : '';
				$places = isset( $meta['places'] ) && is_array( $meta['places'] ) ? $meta['places'] : [];
				echo '<li>';
				echo '<strong>' . esc_html( $name ) . '</strong>';
				if ( $desc ) {
					echo '<p class="wsc-small">' . esc_html( $desc ) . '</p>';
				}
				if ( ! empty( $places ) ) {
					echo '<p class="wsc-tiny wsc-muted">';
					echo esc_html__( 'Supports:', 'ventraconnect-social-login' ) . ' ';
					echo esc_html( implode( ', ', array_map( 'ucfirst', array_keys( $places ) ) ) );
					echo '</p>';
				}
				echo '</li>';
			}
			echo '</ul>';
		}
		echo '<p class="wsc-muted">' . esc_html( $locked_note ) . '</p>';

		$link = isset( $upsell['link'] ) ? esc_url( (string) $upsell['link'] ) : \VentraConnect\SocialLogin\Pro_Gates::upsell_link( 'integrations-preview' );
		$cta  = isset( $upsell['cta'] ) ? (string) $upsell['cta'] : esc_html__( 'Upgrade to Pro', 'ventraconnect-social-login' );
		echo '<p><a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url( $link ) . '">' . esc_html( $cta ) . '</a></p>';
		echo '</div>';
	}
}
