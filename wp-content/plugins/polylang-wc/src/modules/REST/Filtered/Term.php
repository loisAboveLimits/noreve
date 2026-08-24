<?php
/**
 * @package Polylang-WC
 */

namespace WP_Syntex\Polylang_WC\REST\Filtered;

use PLL_REST_API;
use WP_Syntex\Polylang_Pro\REST\Filtered\Term as Base_Term;

/**
 * Filters terms by language in the REST API.
 *
 * @since 2.2.2
 */
class Term extends Base_Term {
	/**
	 * Constructor.
	 *
	 * @since 2.2.2
	 *
	 * @param PLL_REST_API $rest_api Instance of `PLL_REST_API`.
	 * @param string       $taxonomy The taxonomy name.
	 */
	public function __construct( PLL_REST_API $rest_api, string $taxonomy ) {
		parent::__construct( $rest_api, array( $taxonomy ) );
	}
}
