<?php
/**
 * @package Polylang-WC
 */

namespace WP_Syntex\Polylang_WC\REST\Translated;

use WP_Error;
use PLL_REST_API;
use WP_Syntex\Polylang_Pro\REST\Translated\Term as Polylang_Term;

/**
 * Base class for exposing term language and translations in the REST API for WooCommerce taxonomies.
 *
 * @since 2.2
 */
abstract class Abstract_Term extends Polylang_Term {
	use Batch;

	/**
	 * Constructor.
	 *
	 * @since 2.2
	 *
	 * @param PLL_REST_API $rest_api Instance of `PLL_REST_API`.
	 * @param string       $taxonomy Taxonomy name.
	 */
	public function __construct( PLL_REST_API $rest_api, string $taxonomy ) {
		parent::__construct( $rest_api, array( $taxonomy ) );

		add_filter( 'rest_pre_dispatch', array( $this, 'init_batch_lang_queue' ), 10, 3 );
		add_filter( 'pll_inserted_term_language', array( $this, 'filter_language_with_request' ) );
		add_action( "woocommerce_rest_insert_{$taxonomy}", array( $this, 'next_batch_item' ) );
		add_filter( 'woocommerce_rest_check_permissions', array( $this, 'check_permissions' ), 10, 4 );
	}

	/**
	 * Checks if the user has permission to set the language of an object.
	 *
	 * @since 2.2
	 *
	 * @param bool   $permission  True if the user has permission, false otherwise.
	 * @param string $context     The context of the request.
	 * @param int    $object_id   The ID of the object.
	 * @param string $object_type The type of the object.
	 * @return bool|WP_Error Original permission or `WP_Error` if the user cannot set the language.
	 */
	public function check_permissions( $permission, $context, $object_id, $object_type ) {
		if ( ! $permission ) {
			// Do not override the permission if it is already false.
			return $permission;
		}

		if ( ! $this->is_supported_taxonomy( $object_type ) ) {
			return $permission;
		}

		if ( 'batch' === $context || $this->is_batch_route( $this->request->get_route() ) ) {
			return $this->check_batch_languages_permissions( $this->request );
		}

		if ( 'create' !== $context && 'edit' !== $context ) {
			return $permission;
		}

		$language = $this->request->get_language();
		$error    = $this->get_language_with_permission(
			$language ? $language->slug : ''
		);
		if ( is_wp_error( $error ) ) {
			/*
			 * The hook `woocommerce_rest_check_permissions` expects a boolean,
			 * but to get a proper error message, we need to return a `WP_Error`.
			 * This can be done thanks to shallow type in `WC_REST_Terms_Controller::check_permissions()`.
			 */
			return $error;
		}

		return $permission;
	}

	/**
	 * Returns the taxonomy name for the current request.
	 *
	 * @since 2.2
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return bool True if the taxonomy is supported, false otherwise.
	 */
	abstract protected function is_supported_taxonomy( string $taxonomy ): bool;

	/**
	 * Checks if this handler should process the given batch route.
	 *
	 * @since 2.2
	 *
	 * @param string $route The REST route being requested.
	 * @return bool True if this is a batch request for WooCommerce taxonomies.
	 */
	abstract protected function is_batch_route( string $route ): bool;
}
