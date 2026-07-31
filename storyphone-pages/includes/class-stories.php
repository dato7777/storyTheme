<?php
/**
 * Builds the "Stories" payload: one story per product category, each holding a
 * handful of products shown as full-screen slides.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Story data assembly.
 */
class StoryPhone_Pages_Stories {

	/**
	 * Build the story list.
	 *
	 * @param int $story_limit Maximum stories (categories).
	 * @param int $per_story   Maximum slides per story.
	 * @return array<int, array<string, mixed>>
	 */
	public static function build( $story_limit = 8, $per_story = 6 ) {
		$categories = StoryPhone_Pages_Catalog::get_categories( $story_limit );
		return self::from_categories( $categories, $per_story );
	}

	/**
	 * Build stories for an explicit list of category IDs (Design content).
	 *
	 * @param int[] $category_ids Term IDs in display order.
	 * @param int   $per_story    Maximum slides per story.
	 * @return array<int, array<string, mixed>>
	 */
	public static function build_from_category_ids( array $category_ids, $per_story = 6 ) {
		$categories = StoryPhone_Pages_Catalog::get_categories_by_ids( $category_ids, 12 );
		return self::from_categories( $categories, $per_story );
	}

	/**
	 * Assemble story payloads from category terms.
	 *
	 * @param WP_Term[] $categories Categories.
	 * @param int       $per_story  Max slides.
	 * @return array<int, array<string, mixed>>
	 */
	private static function from_categories( array $categories, $per_story = 6 ) {
		if ( empty( $categories ) ) {
			return array();
		}

		$per_story = max( 1, absint( $per_story ) );
		$stories   = array();

		foreach ( $categories as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$products = StoryPhone_Pages_Catalog::get_category_products( $term, $per_story );
			if ( empty( $products ) ) {
				continue;
			}

			$term_link = get_term_link( $term );
			$cover     = StoryPhone_Pages_Catalog::get_category_image( $term );

			if ( '' === $cover ) {
				$cover = StoryPhone_Pages_Catalog::get_product_image_url( $products[0] );
			}

			$items = array();
			foreach ( $products as $product ) {
				$items[] = self::map_product( $product );
			}

			$stories[] = array(
				'id'    => 'cat-' . (int) $term->term_id,
				'name'  => $term->name,
				'url'   => is_wp_error( $term_link ) ? home_url( '/' ) : $term_link,
				'cover' => $cover,
				'count' => (int) $term->count,
				'items' => $items,
			);
		}

		return $stories;
	}

	/**
	 * Reduce a product to the fields a slide needs.
	 *
	 * Only public catalog data is exposed. Prices come from WooCommerce's own
	 * `get_price_html()`, so formatting, tax display and sale styling stay
	 * consistent with the rest of the shop.
	 *
	 * @param WC_Product $product Product.
	 * @return array<string, mixed>
	 */
	private static function map_product( $product ) {
		$permalink = get_permalink( $product->get_id() );
		$image     = StoryPhone_Pages_Catalog::get_product_image_url( $product, 'woocommerce_single' );

		if ( '' === $image ) {
			$image = wc_placeholder_img_src( 'woocommerce_single' );
		}

		return array(
			'id'        => $product->get_id(),
			'name'      => $product->get_name(),
			'url'       => $permalink ? $permalink : home_url( '/' ),
			'image'     => $image,
			// The viewer injects this as HTML, so sanitise before it leaves PHP.
			'priceHtml' => wp_kses_post( $product->get_price_html() ),
			'canAdd'    => StoryPhone_Pages_Catalog::supports_quick_add( $product ),
			'inStock'   => $product->is_in_stock(),
			'discount'  => StoryPhone_Pages_Render::get_discount_percent( $product ),
		);
	}

	/**
	 * Print the payload inside a JSON script tag.
	 *
	 * JSON_HEX_TAG neutralises any "</script>" sequence that could otherwise
	 * break out of the tag, which is the injection risk with inline data.
	 *
	 * @param array  $stories Story list.
	 * @param string $id      Element id.
	 * @return void
	 */
	public static function print_json( array $stories, $id = 'sp-stories-data' ) {
		if ( empty( $stories ) ) {
			return;
		}

		$json = wp_json_encode( $stories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE );

		if ( false === $json ) {
			return;
		}

		printf(
			'<script type="application/json" id="%1$s">%2$s</script>',
			esc_attr( $id ),
			$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Encoded above with HEX flags for safe inline embedding.
		);
	}
}
