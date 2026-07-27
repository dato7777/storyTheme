<?php
/**
 * Read-only catalog queries for the custom page templates.
 *
 * Everything here goes through WooCommerce's own APIs, so pricing, stock and
 * visibility stay authoritative. Nothing in this class writes.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Catalog lookups used by templates.
 */
class StoryPhone_Pages_Catalog {

	/**
	 * Transient TTL for cached ID lists.
	 *
	 * Only post IDs are cached, never prices or stock — product objects are
	 * always re-read, so nothing shown to a customer can go stale.
	 */
	const CACHE_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Fetch storefront-visible products.
	 *
	 * Results are additionally filtered through `WC_Product::is_visible()` so the
	 * Inventory Manager's "Disabled" rules apply here for free.
	 *
	 * @param array<string, mixed> $args Overrides for wc_get_products().
	 * @return WC_Product[]
	 */
	public static function get_products( array $args = array() ) {
		if ( ! storyphone_pages_has_woocommerce() ) {
			return array();
		}

		$defaults = array(
			'status'     => 'publish',
			'limit'      => 8,
			'visibility' => 'visible',
			'orderby'    => 'date',
			'order'      => 'DESC',
		);

		$products = wc_get_products( array_merge( $defaults, $args ) );
		if ( ! is_array( $products ) ) {
			return array();
		}

		return self::only_visible( $products );
	}

	/**
	 * Keep only products the storefront may show.
	 *
	 * @param array $products Mixed list.
	 * @return WC_Product[]
	 */
	private static function only_visible( array $products ) {
		return array_values(
			array_filter(
				$products,
				static function ( $product ) {
					return $product instanceof WC_Product && $product->is_visible();
				}
			)
		);
	}

	/**
	 * Turn a list of post IDs into visible product objects.
	 *
	 * @param int[] $ids   Post IDs.
	 * @param int   $limit Maximum returned.
	 * @return WC_Product[]
	 */
	private static function ids_to_products( array $ids, $limit ) {
		$products = array();

		foreach ( $ids as $id ) {
			if ( count( $products ) >= $limit ) {
				break;
			}

			$product = wc_get_product( (int) $id );
			if ( $product instanceof WC_Product && $product->is_visible() ) {
				$products[] = $product;
			}
		}

		return $products;
	}

	/**
	 * Featured products, topped up with the newest ones.
	 *
	 * A homepage should never render an empty grid just because nobody ticked
	 * "Featured" in wp-admin.
	 *
	 * @param int $limit Maximum products.
	 * @return WC_Product[]
	 */
	public static function get_showcase_products( $limit = 8 ) {
		$limit = max( 1, absint( $limit ) );

		$products = self::get_products(
			array(
				'featured' => true,
				'limit'    => $limit,
			)
		);

		if ( count( $products ) >= $limit ) {
			return $products;
		}

		return self::top_up( $products, $limit );
	}

	/**
	 * Pad a product list with recent products until it reaches $limit.
	 *
	 * @param WC_Product[] $products Existing list.
	 * @param int          $limit    Target size.
	 * @return WC_Product[]
	 */
	private static function top_up( array $products, $limit ) {
		$seen = array();
		foreach ( $products as $product ) {
			$seen[ $product->get_id() ] = true;
		}

		$fallback = self::get_products(
			array(
				'limit'   => $limit * 3,
				'orderby' => 'date',
			)
		);

		foreach ( $fallback as $product ) {
			if ( count( $products ) >= $limit ) {
				break;
			}
			if ( isset( $seen[ $product->get_id() ] ) ) {
				continue;
			}
			$products[]                 = $product;
			$seen[ $product->get_id() ] = true;
		}

		return $products;
	}

	/**
	 * Best sellers, ordered by WooCommerce's own `total_sales` counter.
	 *
	 * `wc_get_products()` only special-cases `orderby => include` and passes
	 * everything else to WP_Query, where "popularity" is not a valid value — so
	 * this queries the meta key directly.
	 *
	 * @param int $limit Maximum products.
	 * @return WC_Product[]
	 */
	public static function get_hot_products( $limit = 6 ) {
		if ( ! storyphone_pages_has_woocommerce() ) {
			return array();
		}

		$limit = max( 1, absint( $limit ) );
		$key   = 'sp_pages_hot_' . $limit;
		$ids   = get_transient( $key );

		if ( ! is_array( $ids ) ) {
			$query = new WP_Query(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => $limit * 4,
					'fields'                 => 'ids',
					'meta_key'               => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'orderby'                => 'meta_value_num',
					'order'                  => 'DESC',
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_term_cache' => false,
					'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'product_visibility',
							'field'    => 'name',
							'terms'    => array( 'exclude-from-catalog' ),
							'operator' => 'NOT IN',
						),
					),
				)
			);

			$ids = array_map( 'intval', (array) $query->posts );
			set_transient( $key, $ids, self::CACHE_TTL );
		}

		$products = self::ids_to_products( $ids, $limit );

		// A brand new shop has no sales yet; never render an empty section.
		if ( count( $products ) < $limit ) {
			$products = self::top_up( $products, $limit );
		}

		return $products;
	}

	/**
	 * Relative popularity of each product, as a 0-100 scale.
	 *
	 * Returns a ratio rather than raw sales counts, so the storefront never
	 * publishes the client's actual sales volume.
	 *
	 * @param WC_Product[] $products Products in rank order.
	 * @return array<int, int> Product ID => heat 0-100.
	 */
	public static function get_heat_map( array $products ) {
		$sales = array();

		foreach ( $products as $product ) {
			if ( $product instanceof WC_Product ) {
				$sales[ $product->get_id() ] = (int) $product->get_total_sales();
			}
		}

		if ( empty( $sales ) ) {
			return array();
		}

		$max  = max( $sales );
		$heat = array();

		foreach ( $sales as $id => $count ) {
			// With no sales data at all, show a pleasant descending ramp.
			$heat[ $id ] = $max > 0 ? (int) max( 18, round( ( $count / $max ) * 100 ) ) : 0;
		}

		if ( 0 === $max ) {
			$step = 0;
			foreach ( $heat as $id => $unused ) {
				$heat[ $id ] = max( 25, 100 - ( $step * 14 ) );
				++$step;
			}
		}

		return $heat;
	}

	/**
	 * Product categories that have products.
	 *
	 * @param int  $limit      Maximum categories.
	 * @param bool $top_level  Restrict to top-level categories.
	 * @return WP_Term[]
	 */
	public static function get_categories( $limit = 6, $top_level = false ) {
		if ( ! storyphone_pages_has_woocommerce() || ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => max( 1, absint( $limit ) ),
			'orderby'    => 'count',
			'order'      => 'DESC',
		);

		if ( $top_level ) {
			$args['parent'] = 0;
		}

		// "Uncategorized" is noise on a homepage, but only exclude a real term.
		$default_cat = (int) get_option( 'default_product_cat', 0 );
		if ( $default_cat > 0 ) {
			$args['exclude'] = array( $default_cat );
		}

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}

		// Fall back to any depth if the shop has no top-level categories in use.
		if ( $top_level && empty( $terms ) ) {
			return self::get_categories( $limit, false );
		}

		return $terms;
	}

	/**
	 * Products belonging to one category.
	 *
	 * @param WP_Term $term  Category.
	 * @param int     $limit Maximum products.
	 * @return WC_Product[]
	 */
	public static function get_category_products( $term, $limit = 6 ) {
		if ( ! $term instanceof WP_Term ) {
			return array();
		}

		$limit = max( 1, absint( $limit ) );
		$key   = 'sp_pages_cat_' . $term->term_id . '_' . $limit;
		$ids   = get_transient( $key );

		if ( ! is_array( $ids ) ) {
			$products = self::get_products(
				array(
					'category' => array( $term->slug ),
					'limit'    => $limit * 2,
					'orderby'  => 'date',
				)
			);

			$ids = array_map(
				static function ( $product ) {
					return $product->get_id();
				},
				$products
			);

			set_transient( $key, $ids, self::CACHE_TTL );
		}

		return self::ids_to_products( $ids, $limit );
	}

	/**
	 * The on-sale product with the deepest discount.
	 *
	 * @return WC_Product|null
	 */
	public static function get_deal_product() {
		$products = self::get_products(
			array(
				'limit'   => 24,
				'orderby' => 'date',
			)
		);

		$best     = null;
		$best_pct = 0;

		foreach ( $products as $product ) {
			if ( ! $product->is_on_sale() || ! $product->is_in_stock() ) {
				continue;
			}

			$regular = (float) $product->get_regular_price();
			$sale    = (float) $product->get_sale_price();

			if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
				continue;
			}

			$pct = ( 1 - ( $sale / $regular ) ) * 100;
			if ( $pct > $best_pct ) {
				$best_pct = $pct;
				$best     = $product;
			}
		}

		return $best;
	}

	/**
	 * Image URL for a product category, or empty string.
	 *
	 * @param WP_Term $term Category term.
	 * @param string  $size Image size.
	 * @return string
	 */
	public static function get_category_image( $term, $size = 'woocommerce_thumbnail' ) {
		$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		if ( $thumbnail_id < 1 ) {
			return '';
		}

		$src = wp_get_attachment_image_url( $thumbnail_id, $size );

		return $src ? $src : '';
	}

	/**
	 * Best available cover image for a category.
	 *
	 * Most shops never set a category thumbnail, so fall back to the first
	 * product in that category rather than rendering an empty frame.
	 *
	 * @param WP_Term $term Category term.
	 * @param string  $size Image size.
	 * @return string Image URL, or empty string.
	 */
	public static function get_category_cover( $term, $size = 'woocommerce_thumbnail' ) {
		$image = self::get_category_image( $term, $size );
		if ( '' !== $image ) {
			return $image;
		}

		$products = self::get_category_products( $term, 1 );
		if ( empty( $products ) ) {
			return '';
		}

		return self::get_product_image_url( $products[0], $size );
	}

	/**
	 * First product image URL, used when a category has no thumbnail set.
	 *
	 * @param WC_Product|null $product Product.
	 * @param string          $size    Image size.
	 * @return string
	 */
	public static function get_product_image_url( $product, $size = 'woocommerce_thumbnail' ) {
		if ( ! $product instanceof WC_Product ) {
			return '';
		}

		$image_id = (int) $product->get_image_id();
		if ( $image_id < 1 ) {
			return '';
		}

		$src = wp_get_attachment_image_url( $image_id, $size );

		return $src ? $src : '';
	}

	/**
	 * Whether a product can be added to the cart straight from a grid card.
	 *
	 * Variable, grouped and external products need their own page to pick
	 * options, so those cards link out instead of adding.
	 *
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	public static function supports_quick_add( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		return $product->is_type( 'simple' )
			&& $product->is_purchasable()
			&& $product->is_in_stock();
	}
}
