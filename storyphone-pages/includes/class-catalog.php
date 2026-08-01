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
	 * Meta about the last get_nav_tree() resolution (for HTML debug attrs).
	 *
	 * @var array{mode:string,count:int,ids:int[]}
	 */
	private static $last_nav_meta = array(
		'mode'  => 'auto',
		'count' => 0,
		'ids'   => array(),
	);

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
	 * Resolve product IDs (order preserved) for Design / curated sections.
	 *
	 * @param int[] $ids   Product IDs.
	 * @param int   $limit Maximum returned.
	 * @return WC_Product[]
	 */
	public static function get_products_by_ids( array $ids, $limit = 12 ) {
		if ( ! storyphone_pages_has_woocommerce() ) {
			return array();
		}

		$clean = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		$limit = max( 1, absint( $limit ) );
		$out   = array();

		// Design curation: published is enough. Do not require is_visible()
		// (out-of-stock / catalog-hidden picks were blanking whole sections).
		foreach ( $clean as $id ) {
			if ( count( $out ) >= $limit ) {
				break;
			}
			$product = wc_get_product( (int) $id );
			if ( $product instanceof WC_Product && 'publish' === $product->get_status() ) {
				$out[] = $product;
			}
		}

		return $out;
	}

	/**
	 * Single published product by ID, or null.
	 *
	 * @param int $id Product ID.
	 * @return WC_Product|null
	 */
	public static function get_product_by_id( $id ) {
		$products = self::get_products_by_ids( array( (int) $id ), 1 );
		return ! empty( $products[0] ) ? $products[0] : null;
	}

	/**
	 * Resolve category IDs (order preserved).
	 *
	 * @param int[] $ids   Term IDs.
	 * @param int   $limit Maximum returned.
	 * @return WP_Term[]
	 */
	public static function get_categories_by_ids( array $ids, $limit = 12 ) {
		if ( ! storyphone_pages_has_woocommerce() || ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		$clean = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		$limit = max( 1, absint( $limit ) );
		$out   = array();

		foreach ( $clean as $term_id ) {
			if ( count( $out ) >= $limit ) {
				break;
			}
			$term = get_term( (int) $term_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$out[] = $term;
			}
		}

		return $out;
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
	 * Direct child categories of a term, busiest first.
	 *
	 * @param WP_Term $parent Parent category.
	 * @param int     $limit  Maximum children.
	 * @return WP_Term[]
	 */
	public static function get_child_categories( $parent, $limit = 6 ) {
		if ( ! $parent instanceof WP_Term || ! taxonomy_exists( 'product_cat' ) ) {
			return array();
		}

		$args = array(
			'taxonomy'   => 'product_cat',
			'parent'     => (int) $parent->term_id,
			'hide_empty' => true,
			'number'     => max( 1, absint( $limit ) ),
			'orderby'    => 'count',
			'order'      => 'DESC',
		);

		$default_cat = (int) get_option( 'default_product_cat', 0 );
		if ( $default_cat > 0 ) {
			$args['exclude'] = array( $default_cat );
		}

		$terms = get_terms( $args );

		return ( is_wp_error( $terms ) || ! is_array( $terms ) ) ? array() : $terms;
	}

	/**
	 * Top-level categories for the hoverable primary nav.
	 *
	 * Parents with children are preferred (they are the whole point of the
	 * expandable nav), then popular leaf categories fill the remaining slots
	 * so the bar still uses the space freed by removing the header search.
	 *
	 * @param int $limit Maximum nav items.
	 * @return array<int, array{term: WP_Term, children: WP_Term[]}>
	 */
	public static function get_nav_tree( $limit = 9 ) {
		$limit = max( 1, absint( $limit ) );

		// Always prefer Design option from DB (not object-cache / not IM helpers alone).
		// WP_CACHE=false only disables page cache — object cache can still stale get_option().
		$design = self::get_design_nav_config();
		if ( ! empty( $design['custom'] ) ) {
			$tree = array();
			$ids  = array();
			foreach ( $design['ids'] as $term_id ) {
				if ( count( $tree ) >= $limit ) {
					break;
				}
				$term = get_term( (int) $term_id, 'product_cat' );
				if ( ! $term || is_wp_error( $term ) ) {
					continue;
				}
				$ids[]  = (int) $term->term_id;
				$tree[] = array(
					'term'     => $term,
					'children' => self::get_child_categories( $term, 6 ),
				);
			}
			self::$last_nav_meta = array(
				'mode'  => 'custom',
				'count' => count( $tree ),
				'ids'   => $ids,
			);
			return $tree;
		}

		$parents = self::get_categories( max( $limit * 2, 16 ), true );

		if ( empty( $parents ) ) {
			self::$last_nav_meta = array(
				'mode'  => 'auto',
				'count' => 0,
				'ids'   => array(),
			);
			return array();
		}

		$with_kids    = array();
		$without_kids = array();

		foreach ( $parents as $parent ) {
			$children = self::get_child_categories( $parent, 6 );
			$entry    = array(
				'term'     => $parent,
				'children' => $children,
			);

			if ( ! empty( $children ) ) {
				$with_kids[] = $entry;
			} else {
				$without_kids[] = $entry;
			}
		}

		$tree = array_slice( array_merge( $with_kids, $without_kids ), 0, $limit );
		self::$last_nav_meta = array(
			'mode'  => 'auto',
			'count' => count( $tree ),
			'ids'   => array_map(
				static function ( $row ) {
					return isset( $row['term']->term_id ) ? (int) $row['term']->term_id : 0;
				},
				$tree
			),
		);
		return $tree;
	}

	/**
	 * Meta from the last get_nav_tree() call (mode/count/ids).
	 *
	 * @return array{mode:string,count:int,ids:int[]}
	 */
	public static function get_last_nav_meta() {
		return self::$last_nav_meta;
	}

	/**
	 * Read navbar Design config straight from the options table.
	 *
	 * Bypasses persistent object cache so staging/admin saves are visible immediately.
	 *
	 * @return array{custom:bool,ids:int[]}
	 */
	private static function get_design_nav_config() {
		$raw = self::read_design_option_fresh();

		$home = isset( $raw['pages']['home'] ) && is_array( $raw['pages']['home'] ) ? $raw['pages']['home'] : array();
		$ids  = array();
		if ( isset( $home['nav_category_ids'] ) && is_array( $home['nav_category_ids'] ) ) {
			$ids = array_values( array_filter( array_map( 'absint', $home['nav_category_ids'] ) ) );
		}

		$custom = ! empty( $home['nav_custom'] ) || ! empty( $ids );

		return array(
			'custom' => $custom,
			'ids'    => $ids,
		);
	}

	/**
	 * Fresh read of storyphone_design from DB (+ cache purge).
	 *
	 * @return array
	 */
	private static function read_design_option_fresh() {
		global $wpdb;

		wp_cache_delete( 'storyphone_design', 'options' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				'storyphone_design'
			)
		);

		if ( null === $row || false === $row ) {
			return array();
		}

		$data = maybe_unserialize( $row );
		return is_array( $data ) ? $data : array();
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
	 * Related products for a PDP reel.
	 *
	 * Ranking blends shared categories with overlapping name tokens (so
	 * "AirPods" surfaces other AirPods / Apple audio, not just the same shelf).
	 *
	 * @param WC_Product $product Seed product.
	 * @param int        $limit   Maximum results.
	 * @return WC_Product[]
	 */
	public static function get_related_products( $product, $limit = 10 ) {
		if ( ! $product instanceof WC_Product || ! storyphone_pages_has_woocommerce() ) {
			return array();
		}

		$limit = max( 1, absint( $limit ) );
		$key   = 'sp_pages_rel_' . $product->get_id() . '_' . $limit;
		$ids   = get_transient( $key );

		if ( ! is_array( $ids ) ) {
			$seed_id     = $product->get_id();
			$seed_tokens = self::name_tokens( $product->get_name() );
			$cat_ids     = wc_get_product_term_ids( $seed_id, 'product_cat' );
			$cat_slugs   = array();

			if ( ! empty( $cat_ids ) ) {
				foreach ( $cat_ids as $cat_id ) {
					$term = get_term( (int) $cat_id, 'product_cat' );
					if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
						$cat_slugs[] = $term->slug;
					}
				}
			}

			$candidates = array();

			if ( ! empty( $cat_slugs ) ) {
				$candidates = self::get_products(
					array(
						'category' => $cat_slugs,
						'limit'    => 40,
						'orderby'  => 'popularity',
					)
				);
			}

			// Broaden the pool so name-token matches (AirPods → other AirPods /
			// Apple audio) can surface even across neighbouring categories.
			$candidates = array_merge( $candidates, self::get_hot_products( 20 ), self::get_showcase_products( 16 ) );

			if ( empty( $candidates ) ) {
				$candidates = self::get_products(
					array(
						'limit'   => 40,
						'orderby' => 'date',
					)
				);
			}

			$scored = array();

			foreach ( $candidates as $candidate ) {
				if ( ! $candidate instanceof WC_Product || $candidate->get_id() === $seed_id ) {
					continue;
				}

				$cid = $candidate->get_id();
				if ( isset( $scored[ $cid ] ) ) {
					continue;
				}

				$score = 0;

				$cand_cats = wc_get_product_term_ids( $cid, 'product_cat' );
				$shared    = array_intersect( $cat_ids, $cand_cats );
				$score    += count( $shared ) * 12;

				$cand_tokens = self::name_tokens( $candidate->get_name() );
				$overlap     = array_intersect( $seed_tokens, $cand_tokens );
				$score      += count( $overlap ) * 18;

				// Strong brand / model tokens deserve an extra push.
				foreach ( $overlap as $token ) {
					if ( mb_strlen( $token ) >= 5 ) {
						$score += 8;
					}
				}

				if ( $candidate->is_on_sale() ) {
					$score += 2;
				}

				if ( $score < 1 ) {
					continue;
				}

				$scored[ $cid ] = $score;
			}

			arsort( $scored, SORT_NUMERIC );
			$ids = array_slice( array_map( 'intval', array_keys( $scored ) ), 0, $limit );

			set_transient( $key, $ids, self::CACHE_TTL );
		}

		return self::ids_to_products( $ids, $limit );
	}

	/**
	 * Extract a YouTube video id from a URL or bare id.
	 *
	 * @param string $value Raw meta value.
	 * @return string Video id, or empty string.
	 */
	public static function parse_youtube_id( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/^[a-zA-Z0-9_-]{11}$/', $value ) ) {
			return $value;
		}

		if ( preg_match( '#(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([a-zA-Z0-9_-]{11})#', $value, $match ) ) {
			return $match[1];
		}

		return '';
	}

	/**
	 * Meaningful tokens from a product name (Hebrew + Latin).
	 *
	 * @param string $name Product name.
	 * @return string[]
	 */
	private static function name_tokens( $name ) {
		$name = is_string( $name ) ? $name : '';
		if ( '' === $name ) {
			return array();
		}

		$lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $name, 'UTF-8' ) : strtolower( $name );
		$parts = preg_split( '/[^\p{L}\p{N}]+/u', $lower, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			return array();
		}

		$stop = array(
			'the', 'and', 'for', 'with', 'set', 'pro', 'max', 'mini', 'new',
			'סט', 'של', 'עם', 'לכל', 'מקורי', 'מקוריים', 'מכשיר', 'מוצר', 'מוצרי',
		);

		$tokens = array();
		foreach ( $parts as $part ) {
			$len = function_exists( 'mb_strlen' ) ? mb_strlen( $part, 'UTF-8' ) : strlen( $part );
			if ( $len < 3 || in_array( $part, $stop, true ) ) {
				continue;
			}
			$tokens[] = $part;
		}

		return array_values( array_unique( $tokens ) );
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
