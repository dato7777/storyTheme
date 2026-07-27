<?php
/**
 * Plugin Name:       StoryPhone Pages
 * Plugin URI:        https://storyphone.co.il
 * Description:       Hand-built, theme-independent front-end page templates for StoryPhone. Presentation only — WooCommerce keeps full control of catalog, pricing, cart, checkout and payment.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            StoryPhone
 * Author URI:        https://storyphone.co.il
 * Text Domain:       storyphone-pages
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.0
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

define( 'STORYPHONE_PAGES_VERSION', '0.1.0' );
define( 'STORYPHONE_PAGES_PLUGIN_FILE', __FILE__ );
define( 'STORYPHONE_PAGES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STORYPHONE_PAGES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin classes.
 *
 * Unlike the Inventory Manager, this plugin does not self-deactivate when
 * WooCommerce is missing. It renders public pages, so a hard bail would take
 * the storefront down; instead every commerce section degrades to empty.
 *
 * @return void
 */
function storyphone_pages_bootstrap() {
	require_once STORYPHONE_PAGES_PLUGIN_DIR . 'includes/class-templates.php';
	require_once STORYPHONE_PAGES_PLUGIN_DIR . 'includes/class-assets.php';
	require_once STORYPHONE_PAGES_PLUGIN_DIR . 'includes/class-catalog.php';
	require_once STORYPHONE_PAGES_PLUGIN_DIR . 'includes/class-render.php';
	require_once STORYPHONE_PAGES_PLUGIN_DIR . 'includes/class-stories.php';

	StoryPhone_Pages_Templates::init();
	StoryPhone_Pages_Assets::init();
}
add_action( 'plugins_loaded', 'storyphone_pages_bootstrap' );

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage.
 *
 * @return void
 */
function storyphone_pages_declare_wc_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			STORYPHONE_PAGES_PLUGIN_FILE,
			true
		);
	}
}
add_action( 'before_woocommerce_init', 'storyphone_pages_declare_wc_compatibility' );

/**
 * Whether WooCommerce is available for catalog and cart calls.
 *
 * @return bool
 */
function storyphone_pages_has_woocommerce() {
	return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_products' );
}
