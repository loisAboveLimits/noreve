<?php
/**
 * @package Polylang-WC
 */

namespace WP_Syntex\Polylang_WC\REST\Translated;

use PLL_REST_API;

/**
 * Exposes the term language and translations in the REST API for WooCommerce taxonomies.
 *
 * @since 2.2.2
 */
class Term extends Abstract_Term {
	/**
	 * The taxonomy name.
	 *
	 * @var string
	 */
	private string $taxonomy_name;

	/**
	 * The taxonomy REST slug used in endpoints.
	 *
	 * @var string
	 */
	private $taxonomy_rest_slug;

	/**
	 * Constructor.
	 *
	 * @since 2.2.2
	 *
	 * @param PLL_REST_API $rest_api           Instance of `PLL_REST_API`.
	 * @param string       $taxonomy_name      The taxonomy name.
	 * @param string       $taxonomy_rest_slug The taxonomy REST slug used in endpoints.
	 */
	public function __construct( PLL_REST_API $rest_api, string $taxonomy_name, string $taxonomy_rest_slug ) {
		parent::__construct( $rest_api, $taxonomy_name );
		$this->taxonomy_name      = $taxonomy_name;
		$this->taxonomy_rest_slug = $taxonomy_rest_slug;
	}

	/**
	 * Checks if this handler should process the given batch route.
	 *
	 * Handles product taxonomies (e.g. categories, tags, brands) batch requests:
	 * - /wc/v2/products/{taxonomy}/batch
	 * - /wc/v3/products/{taxonomy}/batch
	 *
	 * @since 2.2.2
	 *
	 * @param string $route The REST route being requested.
	 * @return bool True if this is a batch request for product brands.
	 */
	protected function is_batch_route( string $route ): bool {
		return (bool) preg_match( "#/wc/v[23]/products/{$this->taxonomy_rest_slug}/batch#", $route );
	}

	/**
	 * Checks if the taxonomy is supported by this handler.
	 *
	 * @since 2.2.2
	 *
	 * @param string $taxonomy The taxonomy name.
	 * @return bool True if the taxonomy is supported, false otherwise.
	 */
	protected function is_supported_taxonomy( string $taxonomy ): bool {
		return $this->taxonomy_name === $taxonomy;
	}
}
