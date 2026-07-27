<?php
/**
 * Part: cinematic hero.
 *
 * Search is the hero's primary action rather than a banner CTA: on a catalog
 * this large, the fastest path to a sale is letting people type what they want.
 *
 * @package StoryPhone_Pages
 *
 * @var array<string, mixed> $args Expects 'product' => WC_Product|null.
 */

defined( 'ABSPATH' ) || exit;

$sp_product  = isset( $args['product'] ) ? $args['product'] : null;
$sp_shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

$sp_chips = StoryPhone_Pages_Catalog::get_categories( 5 );
?>
<section class="sp-hero">
	<div class="sp-aurora" aria-hidden="true">
		<span class="sp-aurora__blob sp-aurora__blob--1"></span>
		<span class="sp-aurora__blob sp-aurora__blob--2"></span>
		<span class="sp-aurora__blob sp-aurora__blob--3"></span>
	</div>
	<div class="sp-hero__grid-lines" aria-hidden="true"></div>
	<div class="sp-noise" aria-hidden="true"></div>

	<div class="sp-shell sp-hero__inner">

		<div class="sp-hero__copy">
			<p class="sp-eyebrow" data-sp-reveal>
				<span class="sp-eyebrow__dot" aria-hidden="true"></span>
				<?php esc_html_e( 'יבוא רשמי · אחריות מלאה · משלוח מהיר לכל הארץ', 'storyphone-pages' ); ?>
			</p>

			<h1 class="sp-hero__title" data-sp-reveal>
				<span class="sp-hero__line"><?php esc_html_e( 'לכל מכשיר', 'storyphone-pages' ); ?></span>
				<span class="sp-hero__line sp-hero__line--accent"><?php esc_html_e( 'יש סיפור.', 'storyphone-pages' ); ?></span>
				<span class="sp-hero__line sp-hero__line--sub"><?php esc_html_e( 'בואו נמצא את שלכם.', 'storyphone-pages' ); ?></span>
			</h1>

			<p class="sp-hero__lede" data-sp-reveal>
				<?php esc_html_e( 'אלפי מכשירים ואביזרים מקוריים. תגידו מה אתם מחפשים — ואנחנו נביא אתכם לזה בשלוש שניות.', 'storyphone-pages' ); ?>
			</p>

			<button type="button" class="sp-heroSearch" data-sp-search-open data-sp-reveal>
				<svg class="sp-heroSearch__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M10.5 3a7.5 7.5 0 1 0 4.55 13.46l4.24 4.25 1.42-1.42-4.25-4.24A7.5 7.5 0 0 0 10.5 3Zm0 2a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z"/>
				</svg>
				<span class="sp-heroSearch__text">
					<span class="sp-heroSearch__static"><?php esc_html_e( 'חיפוש', 'storyphone-pages' ); ?></span>
					<span class="sp-heroSearch__typer" data-sp-typer aria-hidden="true"></span>
				</span>
				<span class="sp-heroSearch__go"><?php esc_html_e( 'התחילו', 'storyphone-pages' ); ?></span>
			</button>

			<?php if ( ! empty( $sp_chips ) ) : ?>
				<div class="sp-hero__chips" data-sp-reveal>
					<span class="sp-hero__chipsLabel"><?php esc_html_e( 'פופולרי:', 'storyphone-pages' ); ?></span>
					<?php foreach ( $sp_chips as $sp_chip ) : ?>
						<?php
						$sp_chip_link = get_term_link( $sp_chip );
						if ( is_wp_error( $sp_chip_link ) ) {
							continue;
						}
						?>
						<a class="sp-chip" href="<?php echo esc_url( $sp_chip_link ); ?>">
							<?php echo esc_html( $sp_chip->name ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $sp_product instanceof WC_Product ) : ?>
			<?php
			$sp_link     = get_permalink( $sp_product->get_id() );
			$sp_link     = $sp_link ? $sp_link : home_url( '/' );
			$sp_discount = StoryPhone_Pages_Render::get_discount_percent( $sp_product );
			?>
			<aside class="sp-hero__pick" data-sp-reveal aria-label="<?php esc_attr_e( 'המוצר הנמכר ביותר', 'storyphone-pages' ); ?>">
				<div class="sp-pick" data-sp-tilt>
					<div class="sp-pick__glow" aria-hidden="true"></div>

					<p class="sp-pick__kicker">
						<span class="sp-pick__flame" aria-hidden="true">&#128293;</span>
						<?php esc_html_e( 'הנמכר ביותר', 'storyphone-pages' ); ?>
					</p>

					<a class="sp-pick__media" href="<?php echo esc_url( $sp_link ); ?>">
						<?php
						echo wp_kses_post(
							$sp_product->get_image(
								'woocommerce_single',
								array(
									'class'    => 'sp-pick__img',
									'loading'  => 'eager',
									'decoding' => 'async',
								)
							)
						);
						?>
						<?php if ( $sp_discount > 0 ) : ?>
							<span class="sp-badge sp-badge--sale">
								<?php
								printf(
									/* translators: %d: discount percentage. */
									esc_html__( '%d%%- הנחה', 'storyphone-pages' ),
									absint( $sp_discount )
								);
								?>
							</span>
						<?php endif; ?>
					</a>

					<h2 class="sp-pick__title">
						<a href="<?php echo esc_url( $sp_link ); ?>"><?php echo esc_html( $sp_product->get_name() ); ?></a>
					</h2>

					<div class="sp-pick__price"><?php echo wp_kses_post( $sp_product->get_price_html() ); ?></div>

					<?php if ( StoryPhone_Pages_Catalog::supports_quick_add( $sp_product ) ) : ?>
						<button
							type="button"
							class="sp-btn sp-btn--primary sp-btn--block"
							data-sp-add-to-cart
							data-product-id="<?php echo esc_attr( (string) $sp_product->get_id() ); ?>"
						>
							<span class="sp-btn__label"><?php esc_html_e( 'הוספה לסל', 'storyphone-pages' ); ?></span>
						</button>
					<?php else : ?>
						<a class="sp-btn sp-btn--primary sp-btn--block" href="<?php echo esc_url( $sp_link ); ?>">
							<?php esc_html_e( 'לצפייה במוצר', 'storyphone-pages' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</aside>
		<?php endif; ?>

	</div>

	<a class="sp-hero__scroll" href="#sp-stories" aria-label="<?php esc_attr_e( 'גלילה לסטוריז', 'storyphone-pages' ); ?>">
		<span class="sp-hero__scrollDot" aria-hidden="true"></span>
	</a>
</section>
