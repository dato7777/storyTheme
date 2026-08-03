<?php
/**
 * Template: StoryPhone — Product Category
 *
 * Layout (top → bottom, independent rows):
 *   1. Hero
 *   2. Trust marquee (fixed under hero — not tied to grid height)
 *   3. Subcategory chips
 *   4. Browse row: vertical title rail + product grid
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

$sp_term = get_queried_object();
if ( ! $sp_term instanceof WP_Term || 'product_cat' !== $sp_term->taxonomy ) {
	wp_safe_redirect( StoryPhone_Pages_Templates::get_home_url() );
	exit;
}

$sp_nav      = StoryPhone_Pages_Catalog::get_nav_tree( 9 );
$sp_children = StoryPhone_Pages_Catalog::get_child_categories( $sp_term, 24 );
$sp_initial  = StoryPhone_Pages_Catalog::query_category_products(
	$sp_term,
	array(
		'page'     => 1,
		'per_page' => 24,
	)
);
$sp_cat_meta = StoryPhone_Pages_REST::serialize_term( $sp_term );
$sp_subs     = array_map( array( 'StoryPhone_Pages_REST', 'serialize_term' ), $sp_children );
$sp_boot     = array(
	'category'      => $sp_cat_meta,
	'subcategories' => $sp_subs,
	'products'      => array_map( array( 'StoryPhone_Pages_REST', 'serialize_product' ), $sp_initial['products'] ),
	'total'         => (int) $sp_initial['total'],
	'pages'         => (int) $sp_initial['pages'],
	'activeId'      => (int) $sp_term->term_id,
);

$sp_rail_chars = preg_split( '//u', $sp_term->name, -1, PREG_SPLIT_NO_EMPTY );
if ( ! is_array( $sp_rail_chars ) ) {
	$sp_rail_chars = array( $sp_term->name );
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
<body <?php body_class( 'sp-page sp-page--category' ); ?>>
<?php wp_body_open(); ?>

<a class="sp-skip" href="#sp-main"><?php esc_html_e( 'דלג לתוכן הראשי', 'storyphone-pages' ); ?></a>

<?php StoryPhone_Pages_Render::part( 'site-header', array( 'nav' => $sp_nav ) ); ?>

<main
	id="sp-main"
	class="sp-main sp-cat"
	data-sp-category-page
	data-category-id="<?php echo esc_attr( (string) (int) $sp_term->term_id ); ?>"
>
	<script type="application/json" data-sp-category-boot><?php echo wp_json_encode( $sp_boot ); ?></script>

	<?php
	StoryPhone_Pages_Render::part(
		'category-hero',
		array(
			'term'  => $sp_term,
			'meta'  => $sp_cat_meta,
			'total' => (int) $sp_initial['total'],
		)
	);
	?>

	<?php /* Trust sits in its own row under the hero — never a child of the product grid. */ ?>
	<?php StoryPhone_Pages_Render::part( 'trust' ); ?>

	<?php
	StoryPhone_Pages_Render::part(
		'category-subcats',
		array(
			'term'      => $sp_term,
			'children'  => $sp_children,
			'active_id' => (int) $sp_term->term_id,
		)
	);
	?>

	<section class="sp-catBrowse" aria-labelledby="sp-cat-grid-title">
		<div class="sp-shell sp-catBrowse__inner">

			<aside class="sp-catRail" data-sp-cat-rail aria-hidden="true">
				<div class="sp-catRail__frame">
					<p class="sp-catRail__title" data-sp-cat-rail-title>
						<?php foreach ( $sp_rail_chars as $sp_i => $sp_ch ) : ?>
							<span class="sp-catRail__char" style="--i: <?php echo esc_attr( (string) $sp_i ); ?>">
								<?php echo ' ' === $sp_ch ? '&nbsp;' : esc_html( $sp_ch ); ?>
							</span>
						<?php endforeach; ?>
					</p>
				</div>
			</aside>

			<div class="sp-catGrid">
				<div class="sp-catGrid__bar">
					<h2 id="sp-cat-grid-title" class="sp-catGrid__title">
						<?php esc_html_e( 'מוצרים', 'storyphone-pages' ); ?>
					</h2>
					<p class="sp-catGrid__live" data-sp-cat-live aria-live="polite">
						<?php
						printf(
							/* translators: 1: product count, 2: category name. */
							esc_html__( 'מציגים %1$s מוצרים ב%2$s', 'storyphone-pages' ),
							esc_html( (string) (int) $sp_initial['total'] ),
							esc_html( $sp_term->name )
						);
						?>
					</p>
				</div>

				<div class="sp-catGrid__stage" data-sp-cat-grid>
					<?php if ( ! empty( $sp_initial['products'] ) ) : ?>
						<div class="sp-catGrid__list" data-sp-cat-list>
							<?php foreach ( $sp_initial['products'] as $sp_product ) : ?>
								<?php StoryPhone_Pages_Render::product_card( $sp_product, array( 'reveal' => false ) ); ?>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<div class="sp-catEmpty" data-sp-cat-empty>
							<span class="sp-catEmpty__mark" aria-hidden="true"></span>
							<p class="sp-catEmpty__title"><?php esc_html_e( 'אין מוצרים בקטגוריה הזו כרגע.', 'storyphone-pages' ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>

		</div>
	</section>
</main>

<?php StoryPhone_Pages_Render::part( 'site-footer' ); ?>
<?php StoryPhone_Pages_Render::part( 'command-palette' ); ?>
<?php StoryPhone_Pages_Render::part( 'cart-drawer' ); ?>

<?php wp_footer(); ?>
</body>
</html>
