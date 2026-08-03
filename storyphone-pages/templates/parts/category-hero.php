<?php
/**
 * Part: category hero.
 *
 * Horizontal header only — the large stacked title lives in the browse rail
 * so it never competes with the product grid for width.
 *
 * @package StoryPhone_Pages
 *
 * @var array<string, mixed> $args term, meta, total.
 */

defined( 'ABSPATH' ) || exit;

$sp_term  = isset( $args['term'] ) ? $args['term'] : null;
$sp_meta  = isset( $args['meta'] ) && is_array( $args['meta'] ) ? $args['meta'] : array();
$sp_total = isset( $args['total'] ) ? (int) $args['total'] : 0;

if ( ! $sp_term instanceof WP_Term ) {
	return;
}

$sp_name  = $sp_term->name;
$sp_desc  = isset( $sp_meta['description'] ) ? (string) $sp_meta['description'] : wp_strip_all_tags( (string) $sp_term->description );
$sp_image = isset( $sp_meta['image'] ) ? (string) $sp_meta['image'] : '';
?>
<section class="sp-catHero" data-sp-cat-hero>
	<div class="sp-aurora" aria-hidden="true">
		<span class="sp-aurora__blob sp-aurora__blob--1"></span>
		<span class="sp-aurora__blob sp-aurora__blob--2"></span>
		<span class="sp-aurora__blob sp-aurora__blob--3"></span>
	</div>
	<div class="sp-noise" aria-hidden="true"></div>

	<div class="sp-shell sp-catHero__inner">
		<nav class="sp-crumbs" aria-label="<?php esc_attr_e( 'ניווט פירורים', 'storyphone-pages' ); ?>">
			<a href="<?php echo esc_url( StoryPhone_Pages_Templates::get_home_url() ); ?>"><?php esc_html_e( 'ראשי', 'storyphone-pages' ); ?></a>
			<span class="sp-crumbs__sep" aria-hidden="true">/</span>
			<span class="sp-crumbs__current" aria-current="page"><?php echo esc_html( $sp_name ); ?></span>
		</nav>

		<div class="sp-catHero__row">
			<div class="sp-catHero__badge" data-sp-cat-badge aria-hidden="true">
				<?php if ( $sp_image ) : ?>
					<img src="<?php echo esc_url( $sp_image ); ?>" alt="" loading="eager" decoding="async">
				<?php else : ?>
					<span class="sp-catHero__glyph"></span>
				<?php endif; ?>
			</div>

			<div class="sp-catHero__copy">
				<p class="sp-catHero__kicker">
					<span class="sp-catHero__pepper" aria-hidden="true"></span>
					<?php
					printf(
						/* translators: %d: product count. */
						esc_html( _n( '%d מוצר', '%d מוצרים', $sp_total, 'storyphone-pages' ) ),
						absint( $sp_total )
					);
					?>
				</p>

				<h1 class="sp-catHero__title" data-sp-cat-title><?php echo esc_html( $sp_name ); ?></h1>

				<?php if ( $sp_desc ) : ?>
					<p class="sp-catHero__desc" data-sp-cat-desc><?php echo esc_html( $sp_desc ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
