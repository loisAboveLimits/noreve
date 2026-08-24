<?php
/**
 * @package Polylang-WC
 */

/**
 * Duplicates the product translations when duplicating a product.
 *
 * @since 1.0
 */
class PLLWC_Admin_Product_Duplicate {
	/**
	 * Product language data store.
	 *
	 * @var PLLWC_Product_Language_CPT
	 */
	protected $data_store;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		$this->data_store = PLLWC_Data_Store::load( 'product_language' );

		add_filter( 'woocommerce_duplicate_product_exclude_children', '__return_true' );
		add_action( 'admin_action_duplicate_product', array( $this, 'duplicate_product_action' ), 5 ); // Before WooCommerce.
		add_action( 'woocommerce_product_duplicate', array( $this, 'product_duplicate' ), 10, 2 );
	}

	/**
	 * Removes the taxonomy terms language check when duplicating products.
	 * This is necessary because duplicate products are assigned the default language at creation.
	 * Hooked to the acton 'admin_action_duplicate_product'.
	 *
	 * @since 0.9.3
	 *
	 * @return void
	 */
	public function duplicate_product_action() {
		remove_action( 'set_object_terms', array( PLL()->posts, 'set_object_terms' ) );
	}

	/**
	 * Fires the duplication of duplicated product translations.
	 *
	 * We are obliged to copy the whole logic of WC_Admin_Duplicate_Product::product_duplicate()
	 * otherwise we can't avoid that WC creates a new sku before the language is assigned.
	 * Code last checked: WC 4.0
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/13262
	 * @since 0.7
	 *
	 * @param WC_Product $duplicate Duplicated product.
	 * @param WC_Product $product   Original product.
	 * @return void
	 */
	public function product_duplicate( $duplicate, $product ) {
		// Get the original translations.
		$tr_ids = $this->data_store->get_translations( $product->get_id() );

		$meta_to_exclude = (array) array_filter(
			apply_filters(
				'woocommerce_duplicate_product_exclude_meta',
				array(),
				array_map(
					function ( $datum ) {
						return $datum->key;
					},
					$product->get_meta_data()
				)
			)
		);

		// First set the language of the product duplicated by WooCommerce.
		$lang = $this->data_store->get_language( $product->get_id() );
		$new_tr_ids = array( $lang => $duplicate->get_id() );
		$this->data_store->set_language( $new_tr_ids[ $lang ], $lang );

		// Duplicate the translations.
		foreach ( $tr_ids as $lang => $tr_id ) {
			if ( $product->get_id() !== $tr_id && $tr_product = wc_get_product( $tr_id ) ) {
				$tr_duplicate = clone $tr_product;

				$tr_duplicate->set_id( 0 );
				/* translators: %s contains the name of the original product. */
				$tr_duplicate->set_name( sprintf( __( '%s (Copy)', 'polylang-wc' ), $tr_duplicate->get_name() ) );
				$tr_duplicate->set_total_sales( 0 );
				$tr_duplicate->set_status( 'draft' );
				$tr_duplicate->set_date_created( null );
				$tr_duplicate->set_slug( '' );
				$tr_duplicate->set_rating_counts( array() );
				$tr_duplicate->set_average_rating( 0 );
				$tr_duplicate->set_review_count( 0 );

				foreach ( $meta_to_exclude as $meta_key ) {
					$tr_duplicate->delete_meta_data( $meta_key );
				}

				// Clear the product unique ID (since WC 9.2.0).
				if ( method_exists( 'WC_Product', 'set_global_unique_id' ) ) {
					$tr_duplicate->set_global_unique_id( '' );
				}

				do_action( 'woocommerce_product_duplicate_before_save', $tr_duplicate, $tr_product );

				$tr_duplicate->save();
				$new_tr_ids[ $lang ] = $tr_duplicate->get_id();

				$this->data_store->set_language( $new_tr_ids[ $lang ], $lang );

				// Set the SKU only now that the language is known.
				if ( '' !== $duplicate->get_sku( 'edit' ) ) {
					$tr_duplicate->set_sku( $duplicate->get_sku( 'edit' ) );
					$tr_duplicate->save();
				}
			}
		}

		// Link duplicated translations together.
		$this->data_store->save_translations( $new_tr_ids );

		// Handle variations.
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				$tr_ids = $this->data_store->get_translations( $child_id );

				if ( $tr_ids && $child = wc_get_product( $child_id ) ) {
					$new_child_tr_ids   = array();
					$tr_child_duplicates = array();

					$sku = $this->generate_unique_sku( $child );

					/*
					 * We do 2 separate loops because we need to set all sku in the translations group
					 * before saving the variations to DB, otherwise we get an Invalid or duplicated SKU exception.
					 * We use the fact that wc_product_has_unique_sku checks for existing sku in DB.
					 * Same for `global_unique_id`.
					 */
					foreach ( $tr_ids as $lang => $tr_id ) {
						$tr_child = wc_get_product( $tr_id );

						if ( ! $tr_child instanceof WC_Product ) {
							continue;
						}

						$tr_child->read_meta_data();
						$tr_child_duplicates[ $lang ] = clone $tr_child;
						$tr_child_duplicates[ $lang ]->set_parent_id( $this->data_store->get( $duplicate->get_id(), $lang ) );
						$tr_child_duplicates[ $lang ]->set_id( 0 );
						$tr_child_duplicates[ $lang ]->set_date_created( null );

						if ( '' !== $child->get_sku( 'edit' ) ) {
							$tr_child_duplicates[ $lang ]->set_sku( $sku );
						}

						// Clear the product unique ID (since WC 9.2.0).
						if ( method_exists( 'WC_Product', 'set_global_unique_id' ) ) {
							$tr_child_duplicates[ $lang ]->set_global_unique_id( '' );
						}

						$this->generate_unique_slug( $tr_child_duplicates[ $lang ] );

						foreach ( $meta_to_exclude as $meta_key ) {
							$tr_child_duplicates[ $lang ]->delete_meta_data( $meta_key );
						}

						do_action( 'woocommerce_product_duplicate_before_save', $tr_child_duplicates[ $lang ], $tr_child );
					}

					foreach ( $tr_child_duplicates as $lang => $child ) {
						$child->save();
						$new_child_tr_ids[ $lang ] = $child->get_id();
						$this->data_store->set_language( $child->get_id(), $lang );
					}

					$this->data_store->save_translations( $new_child_tr_ids );
				}
			}
		}
	}

	/**
	 * Generates a unique slug for a given product. We do this so that we can override the
	 * behavior of wp_unique_post_slug(). The normal slug generation will run single
	 * select queries on every non-unique slug, resulting in very bad performance.
	 * This is an exact copy of WC_Admin_Duplicate_Product::generate_unique_slug()
	 * Code last checked: WC 4.0
	 *
	 * @since 1.5
	 *
	 * @param WC_Product $product The product to generate a slug for.
	 * @return void
	 */
	private function generate_unique_slug( $product ) {
		global $wpdb;
		// We want to remove the suffix from the slug so that we can find the maximum suffix using this root slug.
		// This will allow us to find the next-highest suffix that is unique. While this does not support gap
		// filling, this shouldn't matter for our use-case.
		$root_slug = preg_replace( '/-[0-9]+$/', '', $product->get_slug() );
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT post_name FROM $wpdb->posts WHERE post_name LIKE %s AND post_type IN ( 'product', 'product_variation' )", $root_slug . '%' )
		);
		// The slug is already unique!
		if ( empty( $results ) ) {
			return;
		}
		// Find the maximum suffix so we can ensure uniqueness.
		$max_suffix = 1;
		foreach ( $results as $result ) {
			// Pull a numerical suffix off the slug after the last hyphen.
			$suffix = intval( substr( $result->post_name, strrpos( $result->post_name, '-' ) + 1 ) );
			if ( $suffix > $max_suffix ) {
				$max_suffix = $suffix;
			}
		}
		$product->set_slug( $root_slug . '-' . ( $max_suffix + 1 ) );
	}

	/**
	 * Generates a unique SKU for a given product variation.
	 * This is an exact copy of WC_Admin_Duplicate_Product::generate_unique_sku() from WC 10.5
	 * but returns a string instead of modifying the product.
	 * Code last checked: WC 10.5
	 *
	 * @see https://github.com/woocommerce/woocommerce/pull/62628
	 * @since 2.2.2
	 *
	 * @param WC_Product $product The product to generate a sku for.
	 * @return string The generated unique SKU.
	 */
	private function generate_unique_sku( WC_Product $product ): string {
		global $wpdb;

		// We want to remove the suffix from the sku so that we can find the maximum suffix using this root sku.
		// This will allow us to find the next-highest suffix that is unique. While this does not support gap
		// filling, this shouldn't matter for our use-case.
		$root_sku = preg_replace( '/-[0-9]+$/', '', $product->get_sku() );

		// If the parent product has no SKU, return the current SKU unchanged (matches WC behavior).
		if ( ! $root_sku ) {
			return $product->get_sku();
		}

		$existing_skus = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT lookup.sku
					FROM {$wpdb->posts} as posts
					INNER JOIN {$wpdb->wc_product_meta_lookup} AS lookup ON posts.ID = lookup.product_id
					WHERE posts.post_type IN ( 'product', 'product_variation' )
					AND lookup.sku LIKE %s",
				$wpdb->esc_like( $root_sku ) . '%'
			)
		);

		// The sku is already unique!
		if ( empty( $existing_skus ) ) {
			return $root_sku;
		}

		// Find the maximum suffix so we can ensure uniqueness.
		$max_suffix = 0;
		foreach ( $existing_skus as $existing_sku ) {
			// Pull a numerical suffix off the sku after the last hyphen.
			$suffix = intval( substr( $existing_sku, strrpos( $existing_sku, '-', -1 ) + 1 ) );
			if ( $suffix > $max_suffix ) {
				$max_suffix = $suffix;
			}
		}

		// We set a limit of SKUs to try in order to avoid infinite loops.
		$limit      = $max_suffix + 100;
		$product_id = $product->get_id();

		while ( $max_suffix < $limit ) {
			$new_sku = $root_sku . '-' . ( $max_suffix + 1 );

			/**
			 * Gives plugins an opportunity to verify SKU uniqueness themselves. Filter added to keep backwards
			 * compatibility with `wc_product_has_unique_sku()`.
			 * See: https://github.com/woocommerce/woocommerce/pull/62628
			 *
			 * @since 10.5.0
			 *
			 * @param bool|null $has_unique_sku Set to a boolean value to short-circuit the default SKU check.
			 * @param int $product_id The ID of the current product.
			 * @param string $sku The SKU to check for uniqueness.
			 */
			$pre_has_unique_sku = apply_filters( 'wc_product_pre_has_unique_sku', true, $product_id, $new_sku );

			if ( $pre_has_unique_sku ) {
				/**
				 * Gives plugins an opportunity to verify SKU uniqueness themselves. Filter added to keep backwards
				 * compatibility with `wc_product_has_unique_sku()`.
				 * See: https://github.com/woocommerce/woocommerce/pull/62628
				 *
				 * @since 10.5.0
				 *
				 * @param bool|null $sku_found Set to a boolean value to short-circuit the default SKU check.
				 * @param int $product_id The ID of the current product.
				 * @param string $sku The SKU to check for uniqueness.
				 */
				$sku_found = apply_filters( 'wc_product_has_unique_sku', false, $product_id, $new_sku );

				if ( ! $sku_found ) {
					return $new_sku;
				}
			}
			++$max_suffix;
		}

		// If limit reached, return the current SKU unchanged (matches WC behavior).
		return $product->get_sku();
	}
}
