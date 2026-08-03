<?php
/**
 * Template: StoryPhone — Single Product
 *
 * Full-canvas PDP. Theme header/footer are bypassed; WooCommerce still owns
 * the product object, prices, stock and the Store API cart path.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
}

if ( ! $product instanceof WC_Product ) {
	wp_safe_redirect( StoryPhone_Pages_Templates::get_home_url() );
	exit;
}

$sp_nav      = StoryPhone_Pages_Catalog::get_nav_tree( 9 );
$sp_related  = StoryPhone_Pages_Catalog::get_related_products( $product, 10 );
$sp_discount = StoryPhone_Pages_Render::get_discount_percent( $product );
$sp_gallery  = array();

$sp_main_id = (int) $product->get_image_id();
if ( $sp_main_id > 0 ) {
	$sp_gallery[] = $sp_main_id;
}
foreach ( $product->get_gallery_image_ids() as $sp_gid ) {
	$sp_gid = (int) $sp_gid;
	if ( $sp_gid > 0 && ! in_array( $sp_gid, $sp_gallery, true ) ) {
		$sp_gallery[] = $sp_gid;
	}
}

$sp_cats = get_the_terms( $product->get_id(), 'product_cat' );
if ( ! is_array( $sp_cats ) ) {
	$sp_cats = array();
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#07091a">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'sp-page sp-page--product' ); ?>>
<?php wp_body_open(); ?>

<a class="sp-skip" href="#sp-main"><?php esc_html_e( 'דלג לתוכן הראשי', 'storyphone-pages' ); ?></a>

<?php StoryPhone_Pages_Render::part( 'site-header', array( 'nav' => $sp_nav ) ); ?>

<main id="sp-main" class="sp-main sp-pdp">

	<section class="sp-pdpHero">
		<div class="sp-aurora" aria-hidden="true">
			<span class="sp-aurora__blob sp-aurora__blob--1"></span>
			<span class="sp-aurora__blob sp-aurora__blob--2"></span>
			<span class="sp-aurora__blob sp-aurora__blob--3"></span>
		</div>
		<div class="sp-noise" aria-hidden="true"></div>

		<div class="sp-shell sp-pdpHero__inner">

			<nav class="sp-crumbs" aria-label="<?php esc_attr_e( 'ניווט פירורים', 'storyphone-pages' ); ?>" data-sp-reveal>
				<a href="<?php echo esc_url( StoryPhone_Pages_Templates::get_home_url() ); ?>"><?php esc_html_e( 'ראשי', 'storyphone-pages' ); ?></a>
				<?php if ( ! empty( $sp_cats ) ) : ?>
					<?php
					$sp_crumb = $sp_cats[0];
					$sp_clink = get_term_link( $sp_crumb );
					if ( ! is_wp_error( $sp_clink ) ) :
						?>
						<span class="sp-crumbs__sep" aria-hidden="true">/</span>
						<a href="<?php echo esc_url( $sp_clink ); ?>"><?php echo esc_html( $sp_crumb->name ); ?></a>
					<?php endif; ?>
				<?php endif; ?>
				<span class="sp-crumbs__sep" aria-hidden="true">/</span>
				<span class="sp-crumbs__current" aria-current="page"><?php echo esc_html( $product->get_name() ); ?></span>
			</nav>

			<div class="sp-pdpHero__grid">

				<?php StoryPhone_Pages_Render::part( 'product-gallery', array( 'product' => $product, 'gallery' => $sp_gallery, 'discount' => $sp_discount ) ); ?>

				<?php StoryPhone_Pages_Render::part( 'product-buy', array( 'product' => $product, 'discount' => $sp_discount, 'categories' => $sp_cats ) ); ?>

			</div>
		</div>
	</section>

	<?php StoryPhone_Pages_Render::part( 'product-details', array( 'product' => $product ) ); ?>

	<?php StoryPhone_Pages_Render::part( 'product-related', array( 'products' => $sp_related, 'product' => $product ) ); ?>

	<?php StoryPhone_Pages_Render::part( 'trust' ); ?>

</main>

<?php StoryPhone_Pages_Render::part( 'site-footer' ); ?>
<?php StoryPhone_Pages_Render::part( 'command-palette' ); ?>
<?php StoryPhone_Pages_Render::part( 'cart-drawer' ); ?>

<?php wp_footer(); ?>
</body>
</html>
