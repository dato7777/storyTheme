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
