<?php

use \SearchWPModalFormSettingsApi as SettingsApi;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dismissible admin notices.
 *
 * @since 0.5.0
 *
 * @example Dismissible - global:
 *          \SearchWPModalFormNotice::error(
 *               'Fatal error!',
 *               [
 *                   'dismiss' => \SearchWPModalFormNotice::DISMISS['global'],
 *                   'slug'    => 'fatal_error_3678975',
 *               ]
 *          );
 *
 * @example Dismissible - per user:
 *          \SearchWPModalFormNotice::warning(
 *               'Do something please.',
 *               [
 *                   'dismiss' => \SearchWPModalFormNotice::DISMISS['user'],
 *                   'slug'    => 'do_something_1238943',
 *               ]
 *          );
 *
 * @example Dismissible - global, add custom class to output and disable auto paragraph in text:
 *          \SearchWPModalFormNotice::error(
 *               'Fatal error!',
 *               [
 *                   'dismiss' => \SearchWPModalFormNotice::DISMISS['global'],
 *                   'slug'    => 'fatal_error_348975',
 *                   'autop'   => false,
 *                   'class'   => 'some-additional-class',
 *               ]
 *          );
 *
 * @example Not dismissible:
 *          \SearchWPModalFormNotice::success( 'Everything is good!' );
 */
class SearchWPModalFormNotice {

	/**
	 * Dismiss level.
	 *
	 * A number attended to use as the value of the $args['dismiss'] argument.
	 *
	 * DISMISS['none'] means that the notice is not dismissible.
	 * DISMISS['global'] means that the notice will have the dismiss button, and after clicking this button, the notice will be dismissed for all users.
	 * DISMISS['user'] means that the notice will have the dismiss button, and after clicking this button, the notice will be dismissed only for the current user.
	 *
	 * @since 0.5.0
	 */
	const DISMISS = [
		'none'   => 0,
		'global' => 1,
		'user'   => 2,
	];

	/**
	 * Added notices.
	 *
	 * @since 0.5.0
	 *
	 * @var array
	 */
	private $notices = [];

	/**
	 * Hooks.
	 *
	 * @since 0.5.0
	 */
	public function hooks() {

		add_action( 'admin_notices', [ $this, 'display' ] );
		add_action( 'wp_ajax_searchwp_modal_form_notice_dismiss', [ $this, 'dismiss_ajax' ] );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 0.5.0
	 */
	private function enqueues() {

		wp_enqueue_script(
			'searchwp-modal-form-admin-notices',
			SEARCHWP_MODAL_FORM_PLUGIN_URL . 'assets/js/admin/notices.js',
			[ 'jquery' ],
			SEARCHWP_MODAL_FORM_VERSION,
			true
		);

		wp_localize_script(
			'searchwp-modal-form-admin-notices',
			'searchwp_modal_form_admin_notices',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'searchwp-modal-form-admin' ),
			]
		);
	}

	/**
	 * Display the notices.
	 *
	 * @since 0.5.0
	 */
	public function display() {

		$dismissed_notices = $this->get_dismissed_notices();

		foreach ( $this->notices as $slug => $notice ) {
			if ( isset( $dismissed_notices[ $slug ] ) && ! empty( $dismissed_notices[ $slug ]['dismissed'] ) ) {
				unset( $this->notices[ $slug ] );
			}
		}

		$output = implode( '', $this->notices );

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Enqueue script only when it's needed.
		if ( strpos( $output, 'is-dismissible' ) !== false ) {
			$this->enqueues();
		}
	}

	/**
	 * Add notice to the registry.
	 *
	 * @since 0.5.0
	 *
	 * @param string $message Message to display.
	 * @param string $type    Type of the notice. Can be [ '' (default) | 'info' | 'error' | 'success' | 'warning' ].
	 * @param array  $args    The array of additional arguments. Please see the $defaults array below.
	 */
	public static function add( $message, $type = '', $args = [] ) {

		static $uniq_id = 0;

		$defaults = [
			'dismiss' => self::DISMISS['none'],
			// Dismissible level: one of the self::DISMISS_* const. By default notice is not dismissible.
			'slug'    => '',
			// Slug. Should be unique if dismissible is not equal self::DISMISS['none'].
			'autop'   => true,
			// `false` if not needed to pass message through wpautop().
			'class'   => '',
			// Additional CSS class.
		];

		$args = wp_parse_args( $args, $defaults );

		$dismissible = (int) $args['dismiss'];
		$dismissible = min( $dismissible, self::DISMISS['user'] );

		$class  = $dismissible > self::DISMISS['none'] ? ' is-dismissible' : '';
		$global = ( $dismissible === self::DISMISS['global'] ) ? 'global-' : '';
		$slug   = sanitize_key( $args['slug'] );

		++ $uniq_id;

		$uniq_id += ( $uniq_id === (int) $slug ) ? 1 : 0;

		$id      = 'searchwp-modal-form-notice-' . $global;
		$id     .= empty( $slug ) ? $uniq_id : $slug;
		$type    = ! empty( $type ) ? 'notice-' . esc_attr( sanitize_key( $type ) ) : '';
		$class   = empty( $args['class'] ) ? $class : $class . ' ' . esc_attr( sanitize_key( $args['class'] ) );
		$message = $args['autop'] ? wpautop( $message ) : $message;
		$notice  = sprintf(
			'<div class="notice searchwp-modal-form-notice %s%s" id="%s">%s</div>',
			esc_attr( $type ),
			esc_attr( $class ),
			esc_attr( $id ),
			$message
		);

		if ( empty( $slug ) ) {
			searchwp_modal_form()->get( 'Notice' )->notices[] = $notice;
		} else {
			searchwp_modal_form()->get( 'Notice' )->notices[ $slug ] = $notice;
		}
	}

	/**
	 * Add info notice.
	 *
	 * @since 0.5.0
	 *
	 * @param string $message Message to display.
	 * @param array  $args    Array of additional arguments. Details in the self::add() method.
	 */
	public static function info( $message, $args = [] ) {

		self::add( $message, 'info', $args );
	}

	/**
	 * Add error notice.
	 *
	 * @since 0.5.0
	 *
	 * @param string $message Message to display.
	 * @param array  $args    Array of additional arguments. Details in the self::add() method.
	 */
	public static function error( $message, $args = [] ) {

		self::add( $message, 'error', $args );
	}

	/**
	 * Add success notice.
	 *
	 * @since 0.5.0
	 *
	 * @param string $message Message to display.
	 * @param array  $args    Array of additional arguments. Details in the self::add() method.
	 */
	public static function success( $message, $args = [] ) {

		self::add( $message, 'success', $args );
	}

	/**
	 * Add warning notice.
	 *
	 * @param string $message Message to display.
	 * @param array  $args    Array of additional arguments. Details in the self::add() method.
	 *
	 * @since 0.5.0
	 *
	 */
	public static function warning( $message, $args = [] ) {

		self::add( $message, 'warning', $args );
	}

	/**
	 * AJAX routine that updates dismissed notices metadata.
	 *
	 * @since 0.5.0
	 */
	public function dismiss_ajax() {

		// Run a security check.
		check_ajax_referer( 'searchwp-modal-form-admin', 'nonce' );

		// Sanitize POST data.
		$post = array_map( 'sanitize_key', wp_unslash( $_POST ) );

		// Update notices meta data.
		if ( strpos( $post['id'], 'global-' ) !== false ) {

			// Check for permissions.
			if ( ! current_user_can( SettingsApi::get_capability() ) ) {
				wp_send_json_error();
			}

			$notices = $this->dismiss_global( $post['id'] );
			$level   = self::DISMISS['global'];

		} else {

			$notices = $this->dismiss_user( $post['id'] );
			$level   = self::DISMISS['user'];
		}

		/**
		 * Allows developers to apply additional logic to the dismissing notice process.
		 * Executes after updating option or user meta (according to the notice level).
		 *
		 * @since 0.5.0
		 *
		 * @param string  $notice_id Notice ID (slug).
		 * @param integer $level     Notice level.
		 * @param array   $notices   Dismissed notices.
		 */
		do_action( 'searchwp_modal_form_admin_notice_dismiss_ajax', $post['id'], $level, $notices );

		wp_send_json_success();
	}

	/**
	 * AJAX sub-routine that updates dismissed notices option.
	 *
	 * @since 0.5.0
	 *
	 * @param string $id Notice Id.
	 *
	 * @return array Notices.
	 */
	private function dismiss_global( $id ) {

		$id             = str_replace( 'global-', '', $id );
		$notices        = get_option( 'searchwp_modal_form_admin_notices', [] );
		$notices[ $id ] = [
			'time'      => time(),
			'dismissed' => true,
		];

		update_option( 'searchwp_modal_form_admin_notices', $notices, true );

		return $notices;
	}

	/**
	 * AJAX sub-routine that updates dismissed notices user meta.
	 *
	 * @since 0.5.0
	 *
	 * @param string $id Notice id.
	 *
	 * @return array Notices.
	 */
	private function dismiss_user( $id ) {

		$user_id        = get_current_user_id();
		$notices        = get_user_meta( $user_id, 'searchwp_modal_form_admin_notices', true );
		$notices        = ! is_array( $notices ) ? [] : $notices;
		$notices[ $id ] = [
			'time'      => time(),
			'dismissed' => true,
		];

		update_user_meta( $user_id, 'searchwp_modal_form_admin_notices', $notices );

		return $notices;
	}

	/**
	 * Get dismissed notices list.
	 *
	 * @since 0.5.0
	 *
	 * @return array Dismissed notices.
	 */
	private function get_dismissed_notices() {

		$notices = get_user_meta( get_current_user_id(), 'searchwp_modal_form_admin_notices', true );
		$notices = is_array( $notices ) ? $notices : [];

		return array_merge( $notices, (array) get_option( 'searchwp_modal_form_admin_notices', [] ) );
	}
}
