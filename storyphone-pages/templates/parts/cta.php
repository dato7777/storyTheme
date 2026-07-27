<?php
/**
 * Part: closing call to action.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

$sp_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
?>
<section class="sp-cta">
	<div class="sp-shell">
		<div class="sp-cta__panel" data-sp-reveal>
			<div class="sp-cta__copy">
				<h2 class="sp-cta__title"><?php esc_html_e( 'לא בטוחים מה מתאים לכם?', 'storyphone-pages' ); ?></h2>
				<p class="sp-cta__text">
					<?php esc_html_e( 'ספרו לנו מה אתם מחפשים ואיזה תקציב יש לכם, ונמצא לכם את המכשיר הנכון. בלי לחץ ובלי שטויות.', 'storyphone-pages' ); ?>
				</p>
			</div>
			<div class="sp-cta__actions">
				<a class="sp-btn sp-btn--primary sp-btn--lg" href="<?php echo esc_url( $sp_shop_url ); ?>">
					<?php esc_html_e( 'להתחיל לבחור', 'storyphone-pages' ); ?>
				</a>
			</div>
		</div>
	</div>
</section>
