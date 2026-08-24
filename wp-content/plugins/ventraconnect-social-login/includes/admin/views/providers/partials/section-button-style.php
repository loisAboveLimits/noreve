<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'ventraconnect_sl_allowed_svg_tags' ) ) {
	function ventraconnect_sl_allowed_svg_tags() {
		$allowed = wp_kses_allowed_html( 'post' );

		$common = [
			'id' => true,
			'class' => true,
			'style' => true,
			'fill' => true,
			'fill-opacity' => true,
			'stroke' => true,
			'stroke-width' => true,
			'stroke-linecap' => true,
			'stroke-linejoin' => true,
			'stroke-miterlimit' => true,
			'stroke-dasharray' => true,
			'stroke-dashoffset' => true,
			'opacity' => true,
			'transform' => true,
			'clip-path' => true,
			'mask' => true,
			'filter' => true,
		];

		$allowed['svg'] = array_merge($common, [
			'xmlns' => true,
			'xmlns:xlink' => true,
			'width' => true,
			'height' => true,
			'viewBox' => true,
			'viewbox' => true,
			'role' => true,
			'aria-hidden' => true,
			'focusable' => true,
		]);

		// Many vendor SVGs use <style> + classes.
		$allowed['style'] = [
			'type' => true,
			'media' => true,
		];

		$allowed['g'] = $common;

		$allowed['path'] = array_merge($common, [
			'd' => true,
			'fill-rule' => true,
			'clip-rule' => true,
		]);

		$allowed['circle'] = array_merge($common, [
			'cx' => true,
			'cy' => true,
			'r'  => true,
		]);

		$allowed['ellipse'] = array_merge($common, [
			'cx' => true,
			'cy' => true,
			'rx' => true,
			'ry' => true,
		]);

		$allowed['rect'] = array_merge($common, [
			'x' => true,
			'y' => true,
			'width' => true,
			'height' => true,
			'rx' => true,
			'ry' => true,
		]);

		$allowed['polygon'] = array_merge($common, [
			'points' => true,
		]);

		$allowed['polyline'] = array_merge($common, [
			'points' => true,
		]);

		$allowed['line'] = array_merge($common, [
			'x1' => true,
			'y1' => true,
			'x2' => true,
			'y2' => true,
		]);

		// Definitions / advanced stuff frequently used by exported SVGs.
		$allowed['defs'] = [ 'id' => true ];
		$allowed['title'] = [];
		$allowed['desc']  = [];

		$allowed['clipPath'] = [
			'id' => true,
			'clipPathUnits' => true,
		];

		$allowed['mask'] = [
			'id' => true,
			'maskUnits' => true,
			'maskContentUnits' => true,
			'x' => true,
			'y' => true,
			'width' => true,
			'height' => true,
		];

		$allowed['linearGradient'] = [
			'id' => true,
			'x1' => true,
			'y1' => true,
			'x2' => true,
			'y2' => true,
			'gradientUnits' => true,
			'gradientTransform' => true,
		];

		$allowed['radialGradient'] = [
			'id' => true,
			'cx' => true,
			'cy' => true,
			'r' => true,
			'fx' => true,
			'fy' => true,
			'gradientUnits' => true,
			'gradientTransform' => true,
		];

		$allowed['stop'] = [
			'offset' => true,
			'stop-color' => true,
			'stop-opacity' => true,
			'style' => true,
		];

		$allowed['use'] = [
			'id' => true,
			'href' => true,
			'xlink:href' => true,
			'x' => true,
			'y' => true,
			'width' => true,
			'height' => true,
		];

		return $allowed;
	}
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variables used only within this included provider button-style partial.
$pro_active = \VentraConnect\SocialLogin\Pro_Gates::is_pro();
$settings   = (array) \VentraConnect\SocialLogin\Admin\Settings\Persistence::getSettings();

$global = in_array(
	( $settings['button_style'] ?? ( \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption( 'ventraconnect_sl_button_style', 'wide' ) ) ),
	[ 'wide', 'compact' ],
	true
) ? ( $settings['button_style'] ?? \VentraConnect\SocialLogin\Admin\Settings\Persistence::getOption( 'ventraconnect_sl_button_style', 'wide' ) ) : 'wide';

$theme_opt     = 'ventraconnect_sl_provider_' . $slug . '_theme';
$override_opt  = 'ventraconnect_sl_provider_' . $slug . '_theme_override';
$text_opt      = 'ventraconnect_sl_provider_' . $slug . '_text';

$theme_raw     = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderTheme( $slug, '' );
$global_theme  = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getGlobalTheme( 'light' );
$override_raw  = \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderThemeOverride( $slug, 0 );

// Pro can actually enable overrides; Free always follows global.
$override_enabled = $pro_active ? (bool) $override_raw : false;

$theme_saved = in_array( $theme_raw, [ 'light', 'dark', 'minimal' ], true ) ? $theme_raw : 'light';
// IMPORTANT: In Free, still use the GLOBAL theme so preview matches actual output.
$theme_val   = $override_enabled ? $theme_saved : $global_theme;

/* translators: 1: Provider label. */
$text_def = sprintf( __( 'Continue with %1$s', 'ventraconnect-social-login' ), $label );
$text_val = $pro_active
	? \VentraConnect\SocialLogin\Admin\Settings\Persistence::getProviderText( $slug, $text_def )
	: $text_def;

$force_wide = in_array( $slug, [ 'magic_link', 'otp_email' ], true );
if ( $force_wide ) {
	$global = 'wide';
}

$is_wide    = ( 'wide' === $global ) || $force_wide;
$is_compact = ( ! $force_wide && 'compact' === $global );

echo '<div class="wsc-card" style="margin-top:12px;">';
echo '<h3>' . esc_html__( 'Button Style', 'ventraconnect-social-login' ) . '</h3>';
echo '<table class="form-table" role="presentation">';

// --------------------
// FREE: show summary (no “broken/locked” form controls)
// PRO: keep the full original controls
// --------------------

if ( ! $pro_active ) {

	$layout_label = $is_compact ? esc_html__( 'Compact', 'ventraconnect-social-login' ) : esc_html__( 'Wide', 'ventraconnect-social-login' );
	$theme_label  = ucfirst( (string) $global_theme );

	$settings_url = admin_url( 'admin.php?page=ventraconnect-sl-settings&tab=general' );

	echo '<tr><th scope="row">' . esc_html__( 'Current style', 'ventraconnect-social-login' ) . '</th><td>';

	echo '<p class="wsc-small" style="margin:0 0 8px;">' .
		esc_html__( 'This provider uses the global button layout and theme from the Settings tab.', 'ventraconnect-social-login' ) .
	'</p>';

	echo '<p class="wsc-small" style="margin:0 0 10px;">' .
		sprintf(
			/* translators: 1: Layout label. 2: Theme label. */
			esc_html__( 'Button layout: %1$s (global) • Theme: %2$s (global)', 'ventraconnect-social-login' ),
			esc_html( $layout_label ),
			esc_html( $theme_label )
		) .
	'</p>';

	echo '<p class="wsc-small" style="margin:0;">' .
		esc_html__( 'To change how all buttons look, update the global style in Settings.', 'ventraconnect-social-login' ) .
		' <a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Go to Settings', 'ventraconnect-social-login' ) . '</a>' .
	'</p>';

	echo '<hr style="margin:14px 0;">';

	echo '<p class="wsc-small" style="margin:0 0 6px;">' . esc_html__( 'Per-provider styles (Pro)', 'ventraconnect-social-login' ) . '</p>';
	echo '<ul class="wsc-small" style="margin:0 0 10px 18px; list-style:disc;">';
	echo '<li>' . esc_html__( 'Use different themes per provider.', 'ventraconnect-social-login' ) . '</li>';
	echo '<li>' . esc_html__( 'Customize button text for this provider.', 'ventraconnect-social-login' ) . '</li>';
	echo '</ul>';

	if ( class_exists( '\VentraConnect\SocialLogin\Pro_Gates' ) ) {
		echo '<p class="wsc-small" style="margin:0;">' .
			wp_kses_post(
				\VentraConnect\SocialLogin\Pro_Gates::upsell_inline(
					__( 'Unlock per-provider styles with Pro →', 'ventraconnect-social-login' )
				)
			) .
		'</p>';
	}

	echo '</td></tr>';

} else {

	// --------------------
	// PRO: ORIGINAL CONTROLS (unchanged)
	// --------------------

	// Override toggle
	echo '<tr><th scope="row">' . esc_html__( 'Override Global Theme', 'ventraconnect-social-login' ) . '</th><td>';
	echo '<input type="hidden" name="ventraconnect_sl_settings[provider_theme_override][' . esc_attr( $slug ) . ']" value="0">';
	$toggle_classes = 'wsc-switch';
	echo '<label class="' . esc_attr( $toggle_classes ) . '">';
	echo '<input type="checkbox" class="wsc-switch-input vcs-provider-theme-override" name="ventraconnect_sl_settings[provider_theme_override][' . esc_attr( $slug ) . ']" value="1" data-provider="' . esc_attr( $slug ) . '" data-global-theme="' . esc_attr( $global_theme ) . '" data-saved-theme="' . esc_attr( $theme_saved ) . '"';
	if ( $override_enabled ) {
		echo ' checked="checked"';
	}
	echo '>';
	echo '<span class="wsc-switch-ui" aria-hidden="true"></span>';
	echo '<span class="screen-reader-text">' . esc_html__( 'Allow custom theme for this provider', 'ventraconnect-social-login' ) . '</span>';
	echo '</label>';

	$global_theme_label = ucfirst( $global_theme );
	$desc = sprintf(
		/* translators: 1: Global theme label. */
		esc_html__( 'When disabled, this provider follows the global theme (%1$s).', 'ventraconnect-social-login' ),
		esc_html( $global_theme_label )
	);
	echo '<p class="description wsc-small" style="margin-top:8px;">' . wp_kses_post( $desc ) . '</p>';
	echo '</td></tr>';

	// Theme control
	echo '<tr><th scope="row">' . esc_html__( 'Theme', 'ventraconnect-social-login' ) . '</th><td>';
	$can_customize_theme = $override_enabled;
	$disabled = $can_customize_theme ? '' : ' disabled';
	echo '<fieldset>';
	foreach ( [ 'light' => __( 'Light', 'ventraconnect-social-login' ), 'dark' => __( 'Dark', 'ventraconnect-social-login' ), 'minimal' => __( 'Minimal', 'ventraconnect-social-login' ) ] as $val => $label_opt ) {
		echo '<label class="wsc-radio-simple" style="margin-right:12px;">';
		$checked = $override_enabled ? checked( $theme_saved, $val, false ) : checked( $theme_val, $val, false );
		echo '<input type="radio" class="vcs-provider-theme" name="ventraconnect_sl_settings[provider_theme][' . esc_attr( $slug ) . ']" value="' . esc_attr( $val ) . '"';
		if ( ! empty( $checked ) )  { echo ' checked="checked"'; }
		if ( ! empty( $disabled ) ) { echo ' disabled'; }
		echo ' data-provider="' . esc_attr( $slug ) . '"> <span class="wsc-radio-simple__label">' . esc_html( $label_opt ) . '</span>';
		echo '</label>';
	}
	echo '</fieldset>';

	if ( $force_wide ) {
		echo '<p class="description wsc-small" style="color:#6b7280;">' . esc_html__( 'This sign-in method always uses the wide button layout so people can read the call-to-action. Global compact settings are ignored for this provider.', 'ventraconnect-social-login' ) . '</p>';
	} elseif ( $is_wide ) {
		echo '<p class="description wsc-small" aria-disabled="true" style="color:#6b7280;">' . esc_html__( 'Compact options are disabled because Wide is selected globally. Change style in the Settings tab.', 'ventraconnect-social-login' ) . '</p>';
	} else {
		echo '<p class="description wsc-small" aria-disabled="true" style="color:#6b7280;">' . esc_html__( 'Wide options are disabled because Compact is selected globally.', 'ventraconnect-social-login' ) . '</p>';
	}
	echo '</td></tr>';

	// Custom text (Pro, Wide only)
	echo '<tr><th scope="row">' . esc_html__( 'Custom Text (Wide only)', 'ventraconnect-social-login' ) . '</th><td>';
	$text_disabled = $is_wide ? '' : ' disabled';
	$title = $is_compact ? esc_attr__( 'Custom text is for Wide style.', 'ventraconnect-social-login' ) : '';
	echo '<input type="text" class="regular-text vcs-provider-text" name="ventraconnect_sl_settings[provider_text][' . esc_attr( $slug ) . ']" value="' . esc_attr( $text_val ) . '" placeholder="' . esc_attr( $text_def ) . '"';
	if ( $text_disabled ) { echo ' disabled'; }
	if ( $title ) { echo ' title="' . esc_attr( $title ) . '"'; }
	echo ' data-provider="' . esc_attr( $slug ) . '">';
	echo '</td></tr>';
}

// --------------------
// PREVIEW (keep logic; only change SVG sanitization so Free doesn’t strip icons)
// --------------------

echo '<tr><th scope="row">' . esc_html__( 'Preview', 'ventraconnect-social-login' ) . '</th><td>';

$class = $is_wide ? 'vcs-btn vcs-btn--wide' : 'vcs-btn vcs-btn--compact';
$token_class = '';
if ( 'magic_link' === $slug ) {
	$token_class = ' vcs-btn--magic-link';
} elseif ( 'otp_email' === $slug ) {
	$token_class = ' vcs-btn--otp';
}
$class .= $token_class;

$aria  = ( $is_compact && ! $force_wide ) ? ' aria-label="' . esc_attr( $text_val ) . '"' : '';

// Resolve icon using the same lookup as the frontend render
$style_variant = $is_wide ? 'wide' : 'compact';
$svg = '';
$icon_variants = [];

if ( class_exists( '\\VentraConnect\\SocialLogin\\Buttons' ) && method_exists( '\\VentraConnect\\SocialLogin\\Buttons', 'resolve_icon_source' ) ) {
	foreach ( [ 'light', 'dark', 'minimal' ] as $icon_theme ) {
		$icon_source = \VentraConnect\SocialLogin\Buttons::resolve_icon_source( $slug, $style_variant, $icon_theme );
		$icon_variants[ $icon_theme ] = is_array( $icon_source ) ? ( $icon_source['svg'] ?? '' ) : '';
	}
	$svg = $icon_variants[ $theme_val ] ?? '';
}

if ( '' === $svg ) {
	$svg_path = defined( 'VENTRACONNECT_SL_PLUGIN_DIR' ) ? VENTRACONNECT_SL_PLUGIN_DIR . 'assets/img/provider-icons/' . $slug . '.svg' : '';
	$svg = ( $svg_path && file_exists( $svg_path ) ) ? file_get_contents( $svg_path ) : '';
}

$icon_data_attrs = '';
foreach ( [ 'light', 'dark', 'minimal' ] as $icon_theme ) {
	if ( ! isset( $icon_variants[ $icon_theme ] ) ) { continue; }
	$encoded = base64_encode( $icon_variants[ $icon_theme ] ?? '' );
	$icon_data_attrs .= ' data-icon-' . esc_attr( $icon_theme ) . '="' . esc_attr( $encoded ) . '"';
}

// Allow safe inline SVG in admin preview (Free doesn’t have Pro’s SVG KSES allowances).
$allowed_svg = [
	'svg' => [
		'xmlns' => true, 'width' => true, 'height' => true, 'viewbox' => true, 'viewBox' => true,
		'fill' => true, 'class' => true, 'role' => true, 'aria-hidden' => true, 'focusable' => true,
	],
	'g' => [ 'fill' => true, 'stroke' => true, 'transform' => true, 'opacity' => true, 'clip-path' => true ],
	'path' => [
		'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true,
		'fill-rule' => true, 'clip-rule' => true, 'opacity' => true, 'transform' => true,
	],
	'circle' => [ 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true ],
	'rect' => [ 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true ],
	'polygon' => [ 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true ],
	'line' => [ 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true ],
	'defs' => [],
	'clipPath' => [ 'id' => true ],
	'use' => [ 'href' => true, 'xlink:href' => true ],
	'title' => [],
];

$wrapper = 'wsc-buttons wsc-style-' . ( $is_wide ? 'wide' : 'compact' ) . ' vcs-admin-provider-preview';
echo '<div class="' . esc_attr( $wrapper ) . '">';

echo '<a class="' . esc_attr( $class ) . '" data-provider="' . esc_attr( $slug ) . '" data-theme="' . esc_attr( $theme_val ) . '" href="#" onclick="return false;"';
echo wp_kses_data( ventraconnect_sl_attr_string( $aria, [ 'aria-label','aria-hidden','role' ] ) );
echo wp_kses_data( ventraconnect_sl_attr_string( $icon_data_attrs, [
	'id','class','style','aria-label','aria-hidden','role',
	'data-provider','data-action','data-icon','data-variant',
	'data-icon-light','data-icon-dark','data-icon-minimal'
] ) );
echo '>';

echo '<span class="vcs-btn__icon" aria-hidden="true">' . wp_kses( $svg, $allowed_svg ) . '</span>';

echo '<span class="vcs-btn__label">' . esc_html( $text_val ) . '</span>';

echo '</a>';
echo '</div>';

if ( $pro_active ) {
	echo '<p class="description wsc-small" style="margin-top:6px;">' . esc_html__( 'Preview updates live when you change theme or text.', 'ventraconnect-social-login' ) . '</p>';
} else {
	echo '<p class="description wsc-small" style="margin-top:6px;">' . esc_html__( 'Preview shows how this provider’s button looks with your current global style.', 'ventraconnect-social-login' ) . '</p>';
}

echo '</td></tr>';

echo '</table>';
echo '</div>';
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
