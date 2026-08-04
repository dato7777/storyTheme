<?php
/**
 * Front-end asset loading for plugin-owned page templates.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the built CSS/JS and hands the Store API config to JavaScript.
 */
class StoryPhone_Pages_Assets {

	/**
	 * Script/style handle.
	 *
	 * @var string
	 */
	const HANDLE = 'storyphone-pages';

	/**
	 * Hook registrations.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dequeue_theme_assets' ), 100 );
		add_filter( 'wp_resource_hints', array( __CLASS__, 'resource_hints' ), 10, 2 );
	}

	/**
	 * Preconnect to the font CDN so the Hebrew webfonts arrive sooner.
	 *
	 * @param array  $urls     Resource hint URLs.
	 * @param string $relation Hint type.
	 * @return array
	 */
	public static function resource_hints( $urls, $relation ) {
		if ( 'preconnect' !== $relation || ! StoryPhone_Pages_Templates::is_active() ) {
			return $urls;
		}

		if ( ! apply_filters( 'storyphone_pages_load_webfonts', true ) ) {
			return $urls;
		}

		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);

		if ( self::is_home_template() ) {
			$urls[] = 'https://cdn.jsdelivr.net';
		}

		return $urls;
	}

	/**
	 * Enqueue Heebo + Assistant, both of which have proper Hebrew coverage.
	 *
	 * Disable with: add_filter( 'storyphone_pages_load_webfonts', '__return_false' );
	 *
	 * @return void
	 */
	private static function enqueue_fonts() {
		if ( ! apply_filters( 'storyphone_pages_load_webfonts', true ) ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE . '-fonts',
			'https://fonts.googleapis.com/css2?family=Assistant:wght@400;500;600;700&family=Heebo:wght@700;800;900&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts URLs are already versioned.
		);
	}

	/**
	 * Enqueue our build output on our templates only.
	 *
	 * @return void
	 */
	public static function enqueue() {
		if ( ! StoryPhone_Pages_Templates::is_active() ) {
			return;
		}

		self::enqueue_fonts();

		$css_path = STORYPHONE_PAGES_PLUGIN_DIR . 'build/main.css';
		$js_path  = STORYPHONE_PAGES_PLUGIN_DIR . 'build/main.js';

		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				self::HANDLE,
				STORYPHONE_PAGES_PLUGIN_URL . 'build/main.css',
				array(),
				(string) filemtime( $css_path )
			);
		}

		if ( ! file_exists( $js_path ) ) {
			return;
		}

		$deps = array();

		// GSAP powers the homepage cinematic orbit banner only.
		if ( self::is_home_template() ) {
			wp_enqueue_script(
				'gsap',
				'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js',
				array(),
				'3.12.5',
				true
			);
			$deps[] = 'gsap';
		}

		wp_enqueue_script(
			self::HANDLE,
			STORYPHONE_PAGES_PLUGIN_URL . 'build/main.js',
			$deps,
			(string) filemtime( $js_path ),
			true
		);

		wp_localize_script( self::HANDLE, 'storyphonePages', self::get_js_config() );
	}

	/**
	 * Whether the current request is the StoryPhone Home page template.
	 *
	 * @return bool
	 */
	private static function is_home_template() {
		if ( ! is_page() ) {
			return false;
		}

		return 'storyphone-home' === (string) get_page_template_slug( get_queried_object_id() );
	}

	/**
	 * Data handed to the front-end bundle.
	 *
	 * The Store API nonce is sent on every cart write. WooCommerce 8.x verifies
	 * it against the `wc_store_api` action; newer versions ignore it in favour
	 * of cart tokens, so sending it is safe in both directions.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_js_config() {
		$has_wc = storyphone_pages_has_woocommerce();

		return array(
			'storeApi'    => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
			'restUrl'     => esc_url_raw( rest_url( StoryPhone_Pages_REST::NS . '/' ) ),
			'nonce'       => wp_create_nonce( 'wc_store_api' ),
			'wpNonce'     => wp_create_nonce( 'wp_rest' ),
			'hasWoo'      => $has_wc,
			'homeUrl'     => StoryPhone_Pages_Templates::get_home_url(),
			'cartUrl'     => $has_wc ? wc_get_cart_url() : StoryPhone_Pages_Templates::get_home_url(),
			'checkoutUrl' => $has_wc ? wc_get_checkout_url() : StoryPhone_Pages_Templates::get_home_url(),
			'searchHints' => self::get_search_hints(),
			'i18n'        => array(
				'cartTitle'   => __( 'סל הקניות', 'storyphone-pages' ),
				'cartEmpty'   => __( 'הסל שלך ריק', 'storyphone-pages' ),
				'subtotal'    => __( 'סה"כ', 'storyphone-pages' ),
				'checkout'    => __( 'מעבר לתשלום', 'storyphone-pages' ),
				'viewCart'    => __( 'צפייה בסל', 'storyphone-pages' ),
				'remove'      => __( 'הסרה', 'storyphone-pages' ),
				'decrease'    => __( 'הפחתת כמות', 'storyphone-pages' ),
				'increase'    => __( 'הוספת כמות', 'storyphone-pages' ),
				'adding'      => __( 'מוסיף…', 'storyphone-pages' ),
				'added'       => __( 'נוסף לסל', 'storyphone-pages' ),
				'addToCart'   => __( 'הוספה לסל', 'storyphone-pages' ),
				'genericFail' => __( 'משהו נכשל. נסה שוב.', 'storyphone-pages' ),
				'closeCart'   => __( 'סגירת הסל', 'storyphone-pages' ),
				'outOfStock'  => __( 'אזל', 'storyphone-pages' ),

				'searchProducts'    => __( 'מוצרים', 'storyphone-pages' ),
				'searchEmpty'       => __( 'לא מצאנו התאמות. נסו מילה אחרת.', 'storyphone-pages' ),
				'searchError'       => __( 'החיפוש נכשל. נסו שוב.', 'storyphone-pages' ),
				'searchAll'         => __( 'לכל התוצאות עבור', 'storyphone-pages' ),
				'searchUnavailable' => __( 'החיפוש אינו זמין כרגע.', 'storyphone-pages' ),

				'catAll'       => __( 'הכל', 'storyphone-pages' ),
				'catEmpty'     => __( 'אין מוצרים בקטגוריה הזו כרגע.', 'storyphone-pages' ),
				'catError'     => __( 'טעינת המוצרים נכשלה. נסו שוב.', 'storyphone-pages' ),
				'catShowing'   => __( 'מציגים %1$s מוצרים ב%2$s', 'storyphone-pages' ),
				'catViewProduct' => __( 'לצפייה במוצר', 'storyphone-pages' ),
				'catOutOfStock'  => __( 'אזל מהמלאי', 'storyphone-pages' ),
				'catSale'        => __( '%d%%- הנחה', 'storyphone-pages' ),
			),
		);
	}

	/**
	 * Words cycled through the hero's search hint.
	 *
	 * Uses real category names so the hint always reflects what the shop
	 * actually sells.
	 *
	 * @return string[]
	 */
	private static function get_search_hints() {
		$hints = array();

		foreach ( StoryPhone_Pages_Catalog::get_categories( 5 ) as $term ) {
			$hints[] = $term->name;
		}

		if ( empty( $hints ) ) {
			$hints = array(
				__( 'אוזניות', 'storyphone-pages' ),
				__( 'מטען מהיר', 'storyphone-pages' ),
				__( 'שעון חכם', 'storyphone-pages' ),
			);
		}

		return $hints;
	}

	/**
	 * Drop the active theme's CSS/JS on our templates.
	 *
	 * Our templates render their own document shell, so Matat's stylesheets
	 * would only fight our layout. Plugin assets (analytics, accessibility
	 * widget, WooCommerce) are deliberately left alone.
	 *
	 * @return void
	 */
	public static function dequeue_theme_assets() {
		if ( ! StoryPhone_Pages_Templates::is_active() ) {
			return;
		}

		/**
		 * Filter whether to strip the active theme's assets on our templates.
		 *
		 * @param bool $strip Default true.
		 */
		if ( ! apply_filters( 'storyphone_pages_strip_theme_assets', true ) ) {
			return;
		}

		$theme_dirs = array_unique(
			array(
				trailingslashit( get_template_directory_uri() ),
				trailingslashit( get_stylesheet_directory_uri() ),
			)
		);

		foreach ( array( wp_styles(), wp_scripts() ) as $collection ) {
			if ( ! $collection instanceof WP_Dependencies ) {
				continue;
			}

			foreach ( $collection->registered as $handle => $dependency ) {
				if ( empty( $dependency->src ) || ! is_string( $dependency->src ) ) {
					continue;
				}

				foreach ( $theme_dirs as $dir ) {
					if ( 0 === strpos( $dependency->src, $dir ) ) {
						$collection->dequeue( $handle );
						break;
					}
				}
			}
		}
	}
}
