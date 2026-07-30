<?php
/**
 * Template: StoryPhone — Home
 *
 * A full-canvas document. It deliberately does not call get_header()/get_footer(),
 * so the active theme has no say over the layout. wp_head() and wp_footer() are
 * still called, which keeps analytics, the accessibility widget and WooCommerce
 * scripts working exactly as before.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

$sp_stories   = StoryPhone_Pages_Stories::build( 10, 6 );
$sp_hot       = StoryPhone_Pages_Catalog::get_hot_products( 6 );
$sp_showcase  = StoryPhone_Pages_Catalog::get_showcase_products( 8 );
$sp_families  = StoryPhone_Pages_Catalog::get_categories( 8, true );
$sp_deal      = StoryPhone_Pages_Catalog::get_deal_product();
$sp_nav       = StoryPhone_Pages_Catalog::get_nav_tree( 9 );
$sp_pick      = ! empty( $sp_hot ) ? $sp_hot[0] : ( ! empty( $sp_showcase ) ? $sp_showcase[0] : null );

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

	<?php StoryPhone_Pages_Render::part( 'hero', array( 'nav' => $sp_nav ) ); ?>

	<?php StoryPhone_Pages_Render::part( 'story-rail', array( 'stories' => $sp_stories ) ); ?>

	<?php
	StoryPhone_Pages_Render::part(
		'pick-deck',
		array(
			'product' => $sp_pick,
			'deal'    => $sp_deal,
		)
	);
	?>

	<?php StoryPhone_Pages_Render::part( 'quick-reach', array( 'categories' => $sp_families ) ); ?>

	<?php StoryPhone_Pages_Render::part( 'heat-board', array( 'products' => $sp_hot ) ); ?>

	<?php
	StoryPhone_Pages_Render::part(
		'showcase',
		array(
			'products' => $sp_showcase,
			'title'    => __( 'נבחרת הבית', 'storyphone-pages' ),
			'subtitle' => __( 'המכשירים והאביזרים שאנחנו עומדים מאחוריהם', 'storyphone-pages' ),
		)
	);
	?>

	<?php StoryPhone_Pages_Render::part( 'deal', array( 'product' => $sp_deal ) ); ?>

	<?php StoryPhone_Pages_Render::part( 'trust' ); ?>

	<?php StoryPhone_Pages_Render::part( 'editor-content' ); ?>

	<?php StoryPhone_Pages_Render::part( 'cta' ); ?>

</main>

<?php StoryPhone_Pages_Render::part( 'site-footer' ); ?>

<?php StoryPhone_Pages_Render::part( 'story-viewer' ); ?>
<?php StoryPhone_Pages_Render::part( 'command-palette' ); ?>
<?php StoryPhone_Pages_Render::part( 'cart-drawer' ); ?>

<?php StoryPhone_Pages_Stories::print_json( $sp_stories ); ?>

<?php wp_footer(); ?>
</body>
</html>
