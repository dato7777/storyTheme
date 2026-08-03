<?php
/**
 * Registers plugin-owned page templates.
 *
 * These templates appear in the Page editor's "Template" dropdown but live in
 * this plugin rather than the active theme, so they survive a theme switch
 * (Matat today, possibly Blocksy later) without any change.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Page template registration and dispatch.
 */
class StoryPhone_Pages_Templates {

	/**
	 * Template slug currently being rendered, or empty string.
	 *
	 * @var string
	 */
	private static $active_slug = '';

	/**
	 * Available templates: slug => array( label, file ).
	 *
	 * Slugs are stored in the `_wp_page_template` post meta. They intentionally
	 * do not match any theme file, since we intercept rendering ourselves.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_templates() {
		$templates = array(
			'storyphone-home' => array(
				'label' => __( 'StoryPhone — Home', 'storyphone-pages' ),
				'file'  => 'templates/home.php',
			),
		);

		/**
		 * Filter the plugin-owned page templates.
		 *
		 * @param array<string, array<string, string>> $templates Template map.
		 */
		return apply_filters( 'storyphone_pages_templates', $templates );
	}

	/**
	 * Hook registrations.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'after_setup_theme', array( __CLASS__, 'register_menu_location' ) );
		add_filter( 'theme_page_templates', array( __CLASS__, 'add_to_dropdown' ) );
		add_filter( 'template_include', array( __CLASS__, 'dispatch' ), 99 );
	}

	/**
	 * Give our templates their own menu location.
	 *
	 * Navigation stays editable in Appearance → Menus, so the client keeps
	 * control of the links while we control how they look.
	 *
	 * @return void
	 */
	public static function register_menu_location() {
		register_nav_menu( 'storyphone_primary', __( 'StoryPhone Pages — תפריט ראשי', 'storyphone-pages' ) );
	}

	/**
	 * Add our templates to the Page editor dropdown.
	 *
	 * @param array<string, string> $templates Existing theme templates.
	 * @return array<string, string>
	 */
	public static function add_to_dropdown( $templates ) {
		foreach ( self::get_templates() as $slug => $config ) {
			$templates[ $slug ] = $config['label'];
		}

		return $templates;
	}

	/**
	 * Serve our template file when a page is assigned one of our slugs.
	 *
	 * @param string $template Template path resolved by WordPress.
	 * @return string
	 */
	public static function dispatch( $template ) {
		// Single products always use our cinematic PDP — WooCommerce keeps
		// catalog/pricing/cart authority; we only own presentation.
		if ( function_exists( 'is_product' ) && is_product() ) {
			$path = STORYPHONE_PAGES_PLUGIN_DIR . 'templates/product.php';
			if ( file_exists( $path ) ) {
				self::$active_slug = 'storyphone-product';
				return $path;
			}
		}

		if ( ! is_page() ) {
			return $template;
		}

		$slug = (string) get_page_template_slug( get_queried_object_id() );
		if ( '' === $slug ) {
			return $template;
		}

		$templates = self::get_templates();
		if ( ! isset( $templates[ $slug ] ) ) {
			return $template;
		}

		$path = STORYPHONE_PAGES_PLUGIN_DIR . $templates[ $slug ]['file'];
		if ( ! file_exists( $path ) ) {
			return $template;
		}

		self::$active_slug = $slug;

		return $path;
	}

	/**
	 * Template slug being rendered on this request, or empty string.
	 *
	 * @return string
	 */
	public static function get_active_slug() {
		return self::$active_slug;
	}

	/**
	 * URL of the StoryPhone storefront home.
	 *
	 * Prefers a published page assigned the StoryPhone Home template (preview
	 * and production before it is set as the WP front page). Falls back to
	 * the site front URL once that page is (or when none is assigned).
	 *
	 * @return string
	 */
	public static function get_home_url() {
		$cached = wp_cache_get( 'storyphone_pages_home_url', 'storyphone_pages' );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		$pages = get_posts(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'orderby'                => 'menu_order date',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_key'               => '_wp_page_template',
				'meta_value'             => 'storyphone-home',
				'fields'                 => 'ids',
			)
		);

		$url = '';
		if ( ! empty( $pages[0] ) ) {
			$permalink = get_permalink( (int) $pages[0] );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				$url = $permalink;
			}
		}

		if ( '' === $url ) {
			$url = home_url( '/' );
		}

		/**
		 * Filter the StoryPhone storefront home URL (logo, crumbs, etc.).
		 *
		 * @param string $url Resolved home URL.
		 */
		$url = (string) apply_filters( 'storyphone_pages_home_url', $url );

		wp_cache_set( 'storyphone_pages_home_url', $url, 'storyphone_pages', 5 * MINUTE_IN_SECONDS );

		return $url;
	}

	/**
	 * Whether this request renders one of our templates.
	 *
	 * Resolved without relying on dispatch() having run, so it is safe to call
	 * from `wp_enqueue_scripts` regardless of hook order.
	 *
	 * @return bool
	 */
	public static function is_active() {
		if ( '' !== self::$active_slug ) {
			return true;
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			return file_exists( STORYPHONE_PAGES_PLUGIN_DIR . 'templates/product.php' );
		}

		if ( ! is_page() ) {
			return false;
		}

		$slug = (string) get_page_template_slug( get_queried_object_id() );
		if ( '' === $slug ) {
			return false;
		}

		$templates = self::get_templates();

		return isset( $templates[ $slug ] );
	}
}
