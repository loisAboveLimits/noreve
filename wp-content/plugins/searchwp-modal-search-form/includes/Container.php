<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SearchWPModalFormContainer.
 *
 * The SearchWP Modal Search Form classes container.
 *
 * @since 0.5.0
 */
class SearchWPModalFormContainer {

	/**
	 * Classes instances container.
	 *
	 * @since 0.5.0
	 *
	 * @var array
	 */
	private $instances;

	/**
	 * Include a file within includes dir.
	 *
	 * @since 0.5.0
	 *
	 * @param string $file_name File to include.
	 *
	 * @return $this
	 */
	public function incl( $file_name ) {

		include_once SEARCHWP_MODAL_FORM_PLUGIN_DIR . 'includes/' . $file_name;

		return $this;
	}

	/**
	 * Register a class to the container.
	 *
	 * @since 0.5.0
	 *
	 * @param string $class_name Class to register.
	 *
	 * @return mixed|stdClass
	 */
	public function register( $class_name ) {

		$prefixed_class = $this->prefix_class( $class_name );

		if ( class_exists( $prefixed_class ) ) {
			$this->instances[ $prefixed_class ] = new $prefixed_class();

			return $this->instances[ $prefixed_class ];
		}

		if ( ! class_exists( $class_name ) ) {
			return new stdClass();
		}

		$this->instances[ $class_name ] = new $class_name();

		return $this->instances[ $class_name ];
	}

	/**
	 * Get a class from the container.
	 *
	 * @since 0.5.0
	 *
	 * @param string $class_name Class to get.
	 *
	 * @return mixed|stdClass
	 */
	public function get( $class_name ) {

		$prefixed_class = $this->prefix_class( $class_name );

		if ( $this->has( $prefixed_class ) ) {
			return $this->instances[ $prefixed_class ];
		}

		return $this->has( $class_name ) ? $this->instances[ $class_name ] : new stdClass();
	}

	/**
	 * Check if a class is in the container.
	 *
	 * @since 0.5.0
	 *
	 * @param string $class_name Class to check.
	 *
	 * @return bool
	 */
	public function has( $class_name ) {

		return array_key_exists( $class_name, $this->instances );
	}

	/**
	 * Prefix the class name with a pseudo namespace.
	 *
	 * Allows using a shorter version of the class name
	 * with register() and get() container methods.
	 *
	 * @since 0.5.0
	 *
	 * @param string $class_name Potential class alias.
	 *
	 * @return string
	 */
	private function prefix_class( $class_name ) {

		return 'SearchWPModalForm' . $class_name;
	}
}
