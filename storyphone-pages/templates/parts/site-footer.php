<?php
/**
 * Part: site footer.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

$sp_cats  = StoryPhone_Pages_Catalog::get_categories( 5 );
$sp_pages = array();

if ( function_exists( 'wc_get_page_permalink' ) ) {
	$sp_pages = array(
		__( 'החנות', 'storyphone-pages' )     => wc_get_page_permalink( 'shop' ),
		__( 'סל הקניות', 'storyphone-pages' ) => wc_get_page_permalink( 'cart' ),
		__( 'האזור האישי', 'storyphone-pages' ) => wc_get_page_permalink( 'myaccount' ),
	);
}
?>
<footer class="sp-footer">
	<div class="sp-shell">

		<div class="sp-footer__top">

			<div class="sp-footer__brand">
				<p class="sp-footer__logo"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
				<p class="sp-footer__blurb">
					<?php esc_html_e( 'מכשירים ואביזרים מקוריים במחירים משתלמים, עם אחריות רשמית ושירות אנושי.', 'storyphone-pages' ); ?>
				</p>
			</div>

			<?php if ( ! empty( $sp_cats ) ) : ?>
				<nav class="sp-footer__col" aria-label="<?php esc_attr_e( 'קטגוריות', 'storyphone-pages' ); ?>">
					<h2 class="sp-footer__heading"><?php esc_html_e( 'קטגוריות', 'storyphone-pages' ); ?></h2>
					<ul class="sp-footer__list">
						<?php foreach ( $sp_cats as $sp_term ) : ?>
							<?php
							$sp_link = get_term_link( $sp_term );
							if ( is_wp_error( $sp_link ) ) {
								continue;
							}
							?>
							<li><a href="<?php echo esc_url( $sp_link ); ?>"><?php echo esc_html( $sp_term->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<?php if ( ! empty( $sp_pages ) ) : ?>
				<nav class="sp-footer__col" aria-label="<?php esc_attr_e( 'קישורים', 'storyphone-pages' ); ?>">
					<h2 class="sp-footer__heading"><?php esc_html_e( 'קישורים', 'storyphone-pages' ); ?></h2>
					<ul class="sp-footer__list">
						<?php foreach ( $sp_pages as $sp_label => $sp_url ) : ?>
							<?php if ( ! $sp_url ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<li><a href="<?php echo esc_url( $sp_url ); ?>"><?php echo esc_html( $sp_label ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

		</div>

		<div class="sp-footer__bottom">
			<p class="sp-footer__copy">
				<?php
				printf(
					/* translators: 1: current year, 2: site name. */
					esc_html__( '%1$s © %2$s. כל הזכויות שמורות.', 'storyphone-pages' ),
					esc_html( gmdate( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>
			<p class="sp-footer__secure">
				<?php esc_html_e( 'תשלום מאובטח · אחריות רשמית · משלוח לכל הארץ', 'storyphone-pages' ); ?>
			</p>
		</div>

	</div>
</footer>
