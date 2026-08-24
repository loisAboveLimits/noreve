<?php
/**
 * @package Polylang-WC
 */

namespace WP_Syntex\Polylang_WC\REST\Translated;

use PLL_REST_API;

/**
 * Exposes the term language in the REST API for the product attributes.
 *
 * Supports all product attribute terms (pa_*).
 *
 * @since 2.2
 */
class Attribute extends Abstract_Term {
	/**
	 * Constructor.
	 *
	 * @since 2.2
	 *
	 * @param PLL_REST_API $rest_api Instance of `PLL_REST_API`.
	 */
	public function __construct( PLL_REST_API $rest_api ) {
		parent::__construct( $rest_api, 'product_attribute_term' );

		// Register hooks for all existing attributes.
		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			$attr_name = wc_attribute_taxonomy_name( $tax->attribute_name );
			add_action( "woocommerce_rest_insert_{$attr_name}", array( $this, 'next_batch_item' ) );
		}
	}

	/**
	 * Returns the REST field type for a content type.
	 *
	 * @since 2.2
	 *
	 * @param string $type Taxonomy name.
	 * @return string REST API field type.
	 */
	protected function get_rest_field_type( $type ) {
		if ( $this->is_supported_taxonomy( $type ) ) {
			return 'product_attribute_term';
		}

		return $type;
	}

	/**
	 * Checks if this handler should process the given batch route.
	 *
	 * Handles WooCommerce product attribute batch requests:
	 * - /wc/v2/products/attributes/{id}/terms/batch
	 * - /wc/v3/products/attributes/{id}/terms/batch
	 *
	 * @since 2.2
	 *
	 * @param string $route The REST route being requested.
	 * @return bool True if this is a batch request for product attributes.
	 */
	protected function is_batch_route( string $route ): bool {
		return (bool) preg_match( '#/wc/v[23]/products/attributes/\d+/terms/batch#', $route );
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
		if ( ! str_starts_with( $taxonomy, 'pa_' ) ) {
			return false;
		}

		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			if ( wc_attribute_taxonomy_name( $tax->attribute_name ) === $taxonomy ) {
				return true;
			}
		}

		return false;
	}
}
