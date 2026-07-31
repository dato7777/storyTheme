<?php
/**
 * Template: StoryPhone — Home
 *
 * A full-canvas document. It deliberately does not call get_header()/get_footer(),
 * so the active theme has no say over the layout. wp_head() and wp_footer() are
 * still called, which keeps analytics, the accessibility widget and WooCommerce
 * scripts working exactly as before.
 *
 * Section order / visibility / content can be controlled from Inventory Manager → Design.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'StoryPhone_IM_Storefront_Design' ) && method_exists( 'StoryPhone_IM_Storefront_Design', 'resolve_home_data' ) ) {
	$sp_home     = StoryPhone_IM_Storefront_Design::resolve_home_data();
	$sp_nav      = $sp_home['nav'];
	$sp_stories  = $sp_home['stories'];
	$sp_hot      = $sp_home['hot'];
	$sp_showcase = $sp_home['showcase'];
	$sp_families = $sp_home['families'];
	$sp_deal     = $sp_home['deal'];
	$sp_pick     = $sp_home['pick'];
	$sp_chips    = $sp_home['chips'];
	$sp_sections = $sp_home['sections'];
	$sp_content  = $sp_home['section_content'];
} else {
	$sp_stories  = StoryPhone_Pages_Stories::build( 10, 6 );
	$sp_hot      = StoryPhone_Pages_Catalog::get_hot_products( 6 );
	$sp_showcase = StoryPhone_Pages_Catalog::get_showcase_products( 8 );
	$sp_families = StoryPhone_Pages_Catalog::get_categories( 8, true );
	$sp_deal     = StoryPhone_Pages_Catalog::get_deal_product();
	$sp_nav      = StoryPhone_Pages_Catalog::get_nav_tree( 9 );
	$sp_pick     = ! empty( $sp_hot ) ? $sp_hot[0] : ( ! empty( $sp_showcase ) ? $sp_showcase[0] : null );
	$sp_chips    = StoryPhone_Pages_Catalog::get_categories( 5 );
	$sp_content  = array();
	$sp_sections = array(
		'hero',
		'story-rail',
		'pick-deck',
		'quick-reach',
		'heat-board',
		'showcase',
		'deal',
		'trust',
		'editor-content',
		'cta',
	);

	if ( class_exists( 'StoryPhone_IM_Design' ) ) {
		$configured = StoryPhone_IM_Design::get_enabled_home_sections();
		if ( ! empty( $configured ) ) {
			$sp_sections = $configured;
		}
		if ( method_exists( 'StoryPhone_IM_Design', 'get_all_section_content' ) ) {
			$sp_content = StoryPhone_IM_Design::get_all_section_content();

			$hero = isset( $sp_content['hero'] ) ? $sp_content['hero'] : array();
			if ( ! empty( $hero['chip_category_ids'] ) ) {
				$custom = StoryPhone_Pages_Catalog::get_categories_by_ids( $hero['chip_category_ids'], 8 );
				if ( $custom ) {
					$sp_chips = $custom;
				}
			}
			$story_c = isset( $sp_content['story-rail'] ) ? $sp_content['story-rail'] : array();
			if ( ! empty( $story_c['category_ids'] ) ) {
				$custom = StoryPhone_Pages_Stories::build_from_category_ids( $story_c['category_ids'], 6 );
				if ( $custom ) {
					$sp_stories = $custom;
				}
			}
			$pick_c = isset( $sp_content['pick-deck'] ) ? $sp_content['pick-deck'] : array();
			if ( ! empty( $pick_c['product_id'] ) ) {
				$custom = StoryPhone_Pages_Catalog::get_product_by_id( $pick_c['product_id'] );
				if ( $custom ) {
					$sp_pick = $custom;
				}
			}
			$reach_c = isset( $sp_content['quick-reach'] ) ? $sp_content['quick-reach'] : array();
			if ( ! empty( $reach_c['category_ids'] ) ) {
				$custom = StoryPhone_Pages_Catalog::get_categories_by_ids( $reach_c['category_ids'], 12 );
				if ( $custom ) {
					$sp_families = $custom;
				}
			}
			$heat_c = isset( $sp_content['heat-board'] ) ? $sp_content['heat-board'] : array();
			if ( ! empty( $heat_c['product_ids'] ) ) {
				$custom = StoryPhone_Pages_Catalog::get_products_by_ids( $heat_c['product_ids'], 12 );
				if ( $custom ) {
					$sp_hot = $custom;
				}
			}
			$show_c = isset( $sp_content['showcase'] ) ? $sp_content['showcase'] : array();
			if ( ! empty( $show_c['product_ids'] ) ) {
				$custom = StoryPhone_Pages_Catalog::get_products_by_ids( $show_c['product_ids'], 12 );
				if ( $custom ) {
					$sp_showcase = $custom;
				}
			}
			$deal_c = isset( $sp_content['deal'] ) ? $sp_content['deal'] : array();
			if ( ! empty( $deal_c['product_id'] ) ) {
				$custom = StoryPhone_Pages_Catalog::get_product_by_id( $deal_c['product_id'] );
				if ( $custom ) {
					$sp_deal = $custom;
				}
			}
		}
	}
}

/**
 * Content bag for a section id.
 *
 * @param string $id Section slug.
 * @return array<string, mixed>
 */
$sp_sc = static function ( $id ) use ( $sp_content ) {
	return ( isset( $sp_content[ $id ] ) && is_array( $sp_content[ $id ] ) ) ? $sp_content[ $id ] : array();
};

/**
 * Render one homepage section by id.
 *
 * @param string $sp_section_id Section slug.
 * @return void
 */
$sp_render_section = static function ( $sp_section_id ) use ( $sp_nav, $sp_stories, $sp_pick, $sp_deal, $sp_families, $sp_hot, $sp_showcase, $sp_chips, $sp_sc ) {
	$c = $sp_sc( $sp_section_id );
	switch ( $sp_section_id ) {
		case 'hero':
			StoryPhone_Pages_Render::part(
				'hero',
				array(
					'nav'      => $sp_nav,
					'chips'    => $sp_chips,
					'title'    => isset( $c['title'] ) ? $c['title'] : '',
					'subtitle' => isset( $c['subtitle'] ) ? $c['subtitle'] : '',
				)
			);
			break;
		case 'story-rail':
			StoryPhone_Pages_Render::part(
				'story-rail',
				array(
					'stories'  => $sp_stories,
					'title'    => isset( $c['title'] ) ? $c['title'] : '',
					'subtitle' => isset( $c['subtitle'] ) ? $c['subtitle'] : '',
				)
			);
			break;
		case 'pick-deck':
			StoryPhone_Pages_Render::part(
				'pick-deck',
				array(
					'product'  => $sp_pick,
					'deal'     => $sp_deal,
					'title'    => isset( $c['title'] ) ? $c['title'] : '',
					'subtitle' => isset( $c['subtitle'] ) ? $c['subtitle'] : '',
				)
			);
			break;
		case 'quick-reach':
			StoryPhone_Pages_Render::part(
				'quick-reach',
				array(
					'categories' => $sp_families,
					'title'      => isset( $c['title'] ) ? $c['title'] : '',
					'subtitle'   => isset( $c['subtitle'] ) ? $c['subtitle'] : '',
				)
			);
			break;
		case 'heat-board':
			StoryPhone_Pages_Render::part(
				'heat-board',
				array(
					'products' => $sp_hot,
					'title'    => isset( $c['title'] ) ? $c['title'] : '',
					'subtitle' => isset( $c['subtitle'] ) ? $c['subtitle'] : '',
				)
			);
			break;
		case 'showcase':
			StoryPhone_Pages_Render::part(
				'showcase',
				array(
					'products' => $sp_showcase,
					'title'    => ! empty( $c['title'] ) ? $c['title'] : __( 'נבחרת הבית', 'storyphone-pages' ),
					'subtitle' => ! empty( $c['subtitle'] ) ? $c['subtitle'] : __( 'המכשירים והאביזרים שאנחנו עומדים מאחוריהם', 'storyphone-pages' ),
				)
			);
			break;
		case 'deal':
			StoryPhone_Pages_Render::part( 'deal', array( 'product' => $sp_deal ) );
			break;
		case 'trust':
			StoryPhone_Pages_Render::part(
				'trust',
				array(
					'title' => isset( $c['title'] ) ? $c['title'] : '',
					'items' => isset( $c['items'] ) && is_array( $c['items'] ) ? $c['items'] : array(),
				)
			);
			break;
		case 'editor-content':
			StoryPhone_Pages_Render::part( 'editor-content' );
			break;
		case 'cta':
			StoryPhone_Pages_Render::part(
				'cta',
				array(
					'title'        => isset( $c['title'] ) ? $c['title'] : '',
					'text'         => isset( $c['text'] ) ? $c['text'] : '',
					'button_label' => isset( $c['button_label'] ) ? $c['button_label'] : '',
					'button_url'   => isset( $c['button_url'] ) ? $c['button_url'] : '',
				)
			);
			break;
	}
};

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#07091a">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'sp-page sp-page--home' ); ?>>
<?php wp_body_open(); ?>

<a class="sp-skip" href="#sp-main"><?php esc_html_e( 'דלג לתוכן הראשי', 'storyphone-pages' ); ?></a>

<?php StoryPhone_Pages_Render::part( 'site-header', array( 'nav' => $sp_nav ) ); ?>

<main id="sp-main" class="sp-main">

	<?php foreach ( $sp_sections as $sp_section_id ) : ?>
		<?php $sp_render_section( $sp_section_id ); ?>
	<?php endforeach; ?>

</main>

<?php StoryPhone_Pages_Render::part( 'site-footer' ); ?>

<?php StoryPhone_Pages_Render::part( 'story-viewer' ); ?>
<?php StoryPhone_Pages_Render::part( 'command-palette' ); ?>
<?php StoryPhone_Pages_Render::part( 'cart-drawer' ); ?>

<?php StoryPhone_Pages_Stories::print_json( $sp_stories ); ?>

<?php wp_footer(); ?>
</body>
</html>
