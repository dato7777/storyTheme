<?php
/**
 * Plugin REST routes for category browsing.
 *
 * Complements the WooCommerce Store API: presentation payloads shaped for
 * our category page (cards, subcats, totals) without inventing commerce logic.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers storyphone-pages/v1 routes.
 */
class StoryPhone_Pages_REST {

	const NS = 'storyphone-pages/v1';

	/**
	 * Hook registrations.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::NS,
			'/category/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_category' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/products',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_products' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'category' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'page'     => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'type'              => 'integer',
						'default'           => 24,
						'sanitize_callback' => 'absint',
					),
					'search'   => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Category meta + direct children for the subcategory selector.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_category( $request ) {
		$term = get_term( (int) $request['id'], 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'sp_cat_missing', __( 'הקטגוריה לא נמצאה.', 'storyphone-pages' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response(
			array(
				'category'      => self::serialize_term( $term ),
				'subcategories' => array_map(
					array( __CLASS__, 'serialize_term' ),
					StoryPhone_Pages_Catalog::get_child_categories( $term, 24 )
				),
			)
		);
	}

	/**
	 * Paginated products for a category / subcategory.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_products( $request ) {
		$term_id  = (int) $request['category'];
		$page     = max( 1, (int) $request['page'] );
		$per_page = min( 48, max( 1, (int) $request['per_page'] ) );
		$search   = trim( (string) $request['search'] );

		$term = get_term( $term_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) ) {
			return new WP_Error( 'sp_cat_missing', __( 'הקטגוריה לא נמצאה.', 'storyphone-pages' ), array( 'status' => 404 ) );
		}

		$result = StoryPhone_Pages_Catalog::query_category_products(
			$term,
			array(
				'page'     => $page,
				'per_page' => $per_page,
				'search'   => $search,
			)
		);

		return rest_ensure_response(
			array(
				'products' => array_map( array( __CLASS__, 'serialize_product' ), $result['products'] ),
				'total'    => (int) $result['total'],
				'pages'    => (int) $result['pages'],
				'page'     => $page,
				'category' => self::serialize_term( $term ),
			)
		);
	}

	/**
	 * @param WP_Term $term Term.
	 * @return array<string, mixed>
	 */
	public static function serialize_term( $term ) {
		$image = StoryPhone_Pages_Catalog::get_category_cover( $term, 'woocommerce_thumbnail' );

		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			$link = '';
		}

		return array(
			'id'          => (int) $term->term_id,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => wp_strip_all_tags( (string) $term->description ),
			'count'       => (int) $term->count,
			'parent'      => (int) $term->parent,
			'permalink'   => $link,
			'image'       => $image ? $image : '',
		);
	}

	/**
	 * @param WC_Product $product Product.
	 * @return array<string, mixed>
	 */
	public static function serialize_product( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return array();
		}

		$image_id = (int) $product->get_image_id();
		$image    = $image_id > 0 ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : '';
		if ( ! $image && function_exists( 'wc_placeholder_img_src' ) ) {
			$image = wc_placeholder_img_src( 'woocommerce_thumbnail' );
		}

		$permalink = get_permalink( $product->get_id() );

		return array(
			'id'         => (int) $product->get_id(),
			'name'       => $product->get_name(),
			'permalink'  => $permalink ? $permalink : '',
			'priceHtml'  => $product->get_price_html(),
			'image'      => $image ? $image : '',
			'inStock'    => (bool) $product->is_in_stock(),
			'discount'   => StoryPhone_Pages_Render::get_discount_percent( $product ),
			'quickAdd'   => StoryPhone_Pages_Catalog::supports_quick_add( $product ),
			'type'       => $product->get_type(),
		);
	}
}
