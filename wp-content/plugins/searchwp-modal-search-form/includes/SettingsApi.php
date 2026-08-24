<?php

use \SearchWPModalFormUtils as Utils;
use \SearchWPModalFormSettings as Settings;
use \SearchWPModalFormNotice as Notice;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SearchWPModalFormSettings_Api.
 *
 * The SearchWP Modal Search Form settings API.
 *
 * @since 0.5.0
 */
class SearchWPModalFormSettingsApi {

	/**
	 * WP option name to save settings.
	 *
	 * @since 0.5.0
	 */
	const OPTION_NAME = 'searchwp_modal_form_settings';

	/**
	 * Capability requirement for managing settings.
	 *
	 * @since 0.5.0
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Init hook callback.
	 *
	 * @since 0.5.0
	 */
	public function init() {

		if ( Utils::is_settings_page() ) {
			$this->save_settings();
		}
	}

	/**
	 * Save settings.
	 *
	 * @since 0.5.0
	 */
	private function save_settings() {

		if ( ! $this->current_user_can_save() ) {
			return;
		}

		$fields   = $this->get_registered_settings();
		$settings = get_option( self::OPTION_NAME, [] );

		foreach ( $fields as $slug => $field ) {

			if ( empty( $field['type'] ) || $field['type'] === 'content' ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
			$value = isset( $_POST[ $slug ] ) ? trim( wp_unslash( $_POST[ $slug ] ) ) : false;

			$value = $this->sanitize_setting( $value, $field['type'] );

			// Add to settings.
			$settings[ $slug ] = $value;
		}

        update_option( self::OPTION_NAME, $settings );

		Notice::success( esc_html__( 'Settings were successfully saved.', 'searchwp-modal-search-form' ) );
	}

	/**
	 * Get settings capability.
	 *
	 * @since 0.5.0
	 *
	 * @return string
	 */
	public static function get_capability() {

		return (string) apply_filters( 'searchwp_modal_form_settings_capability', self::CAPABILITY );
	}

	/**
	 * Check if the current user can save settings.
	 *
	 * @since 0.5.0
	 *
	 * @return bool
	 */
	private function current_user_can_save() {

		// Check nonce and other various security checks.
		if ( ! isset( $_POST['searchwp-modal-form-settings-submit'] ) ) {
			return false;
		}

		if ( empty( $_POST['nonce'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'searchwp-modal-form-settings-nonce' ) ) {
			return false;
		}

		$capability = self::get_capability();

		if ( ! current_user_can( $capability ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the value of a specific SearchWP setting.
	 *
	 * @since 0.5.0
	 *
	 * @param string $slug    Setting slug.
	 * @param mixed  $default Default setting value.
	 *
	 * @return mixed
	 */
	public static function get( $slug, $default_value = null ) {

		$slug     = sanitize_key( $slug );
		$settings = get_option( self::OPTION_NAME );

		if ( $default_value === null ) {
			$registered    = self::get_registered_settings();
			$default_value = isset( $registered[ $slug ]['default'] ) ? $registered[ $slug ]['default'] : $default_value;
		}

		return isset( $settings[ $slug ] ) ? wp_unslash( $settings[ $slug ] ) : $default_value;
	}

	/**
	 * Return all the default registered settings fields.
	 *
	 * @since 0.5.0
	 *
	 * @return array
	 */
	public static function get_registered_settings() {

		$defaults = [
			'general-heading'      => [
				'slug'    => 'general-heading',
				'content' => '<h3>' . esc_html__( 'General', 'searchwp-modal-search-form' ) . '</h3>',
				'type'    => 'content',
				'class'   => [ 'section-heading' ],
			],
			'enable-modal-form'    => [
				'slug'       => 'enable-modal-form',
				'name'       => esc_html__( 'Enable Modal Form', 'searchwp-modal-search-form' ),
				'desc'       => esc_html__( 'Check this option to enable the Modal Search Form.', 'searchwp-modal-search-form' ),
				'type'       => 'checkbox',
				'default'    => true,
				'desc_after' => Settings::get_dyk_block_output(),
			],
			'modal-heading'        => [
				'slug'    => 'modal-heading',
				'content' => '<h3>' . esc_html__( 'Modal', 'searchwp-modal-search-form' ) . '</h3>',
				'type'    => 'content',
				'class'   => [ 'section-heading' ],
			],
		];

		if ( Utils::is_searchwp_active() || Utils::is_live_search_active() ) {
			$defaults['search-form'] = [
				'slug'    => 'search-form',
				'name'    => esc_html__( 'Search Form', 'searchwp-modal-search-form' ),
				'desc'    => esc_html__( 'Select which Search form to use for the modal search.', 'searchwp-modal-search-form' ),
				'type'    => 'select',
				'default' => '',
				'options' => self::get_search_form_options(),
			];
		}

		$defaults = array_merge(
			$defaults,
			[
				'include-frontend-css' => [
					'slug'    => 'include-frontend-css',
					'name'    => esc_html__( 'Include Styling', 'searchwp-modal-search-form' ),
					'desc'    => esc_html__( 'Determines which CSS files to load and use for the site. "Positioning and visual styling" is recommended, unless you are experienced with CSS or instructed by support to change settings.', 'searchwp-modal-search-form' ),
					'type'    => 'select',
					'default' => 'all',
					'options' => [
						'all'      => esc_html__( 'Positioning and visual styling', 'searchwp-modal-search-form' ),
						'position' => esc_html__( 'Positioning styling only', 'searchwp-modal-search-form' ),
						'none'     => esc_html__( 'No styling', 'searchwp-modal-search-form' ),
					],
				],
				'modal-fullscreen'     => [
					'slug' => 'modal-fullscreen',
					'name' => esc_html__( 'Full Screen Mode', 'searchwp-modal-search-form' ),
					'desc' => esc_html__( 'Check this option to make the modal cover the entire screen when open. This option is great to provide a distraction free search experience for your users.', 'searchwp-modal-search-form' ),
					'type' => 'checkbox',
				],
				'modal-disable-scroll' => [
					'slug' => 'modal-disable-scroll',
					'name' => esc_html__( 'Disable Scroll', 'searchwp-modal-search-form' ),
					'desc' => esc_html__( 'Check this option to disable background scrolling of the page when the modal is open.', 'searchwp-modal-search-form' ),
					'type' => 'checkbox',
				],
				'misc-heading'         => [
					'slug'    => 'misc-heading',
					'content' => '<h3>' . esc_html__( 'Misc', 'searchwp-modal-search-form' ) . '</h3>',
					'type'    => 'content',
					'class'   => [ 'section-heading' ],
				],
				'hide-announcements'   => [
					'slug' => 'hide-announcements',
					'name' => esc_html__( 'Hide Announcements', 'searchwp-modal-search-form' ),
					'desc' => esc_html__( 'Check this option to hide plugin announcements and update details.', 'searchwp-modal-search-form' ),
					'type' => 'checkbox',
				],
			]
		);

		return apply_filters( 'searchwp_modal_form_settings_defaults', $defaults );
	}

	/**
	 * Save settings.
	 *
	 * @since 0.5.0
	 *
	 * @param mixed  $value      Value to sanitize.
	 * @param string $field_type Field type.
	 *
	 * @return bool|string
	 */
	private function sanitize_setting( $value, $field_type ) {

        switch ( $field_type ) {
            case 'checkbox':
                $value = (bool) $value;
                break;

            case 'select':
            default:
                $value = sanitize_text_field( $value );
                break;
        }

        return $value;
	}

	/**
	 * Get list of available SearchWP forms.
	 *
	 * @since 0.5.0
	 *
	 * @return array
	 */
	private static function get_search_form_options() {

		if ( Utils::is_searchwp_active() ) {
			$forms = \SearchWP\Forms\Storage::get_all();
		} elseif ( Utils::is_live_search_active() ) {
			$forms = \SearchWP_Live_Search_Storage::get_all();
		}

		$options = [ 0 => esc_html__( 'Site Default', 'searchwp-modal-search-form' ) ];

		if ( ! empty( $forms ) ) {
			foreach ( $forms as $id => $form ) {
				/* translators: %s: Form ID. */
				$options[ $id ] = ! empty( $form['title'] ) ? $form['title'] : sprintf( esc_html__( 'Form %d', 'searchwp-modal-search-form' ), $id );
			}
		}

		return $options;
	}
}
