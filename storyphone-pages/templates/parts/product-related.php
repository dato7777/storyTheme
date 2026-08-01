<?php
/**
 * Part: related products reel — same family / name-token matches.
 *
 * @package StoryPhone_Pages
 *
 * @var array<string, mixed> $args products, product (seed).
 */

defined( 'ABSPATH' ) || exit;

$sp_products = isset( $args['products'] ) && is_array( $args['products'] ) ? $args['products'] : array();
$sp_seed     = isset( $args['product'] ) ? $args['product'] : null;

if ( empty( $sp_products ) ) {
	return;
}
?>
<section class="sp-pdpRelated" id="sp-related" aria-labelledby="sp-related-title">
	<div class="sp-shell">
		<header class="sp-section__head" data-sp-reveal>
			<div>
				<p class="sp-eyebrow">
					<span class="sp-eyebrow__item">
						<span class="sp-eyebrow__dot" aria-hidden="true"></span>
						<?php esc_html_e( 'אותה משפחה', 'storyphone-pages' ); ?>
					</span>
				</p>
				<h2 class="sp-section__title" id="sp-related-title">
					<?php esc_html_e( 'אולי זה גם בשבילכם', 'storyphone-pages' ); ?>
				</h2>
				<p class="sp-section__subtitle">
					<?php
					if ( $sp_seed instanceof WC_Product ) {
						printf(
							/* translators: %s: product name fragment. */
							esc_html__( 'מוצרים קרובים ל־%s — אותה קטגוריה, אותו סיפור', 'storyphone-pages' ),
							esc_html( wp_trim_words( $sp_seed->get_name(), 4, '…' ) )
						);
					} else {
						esc_html_e( 'מוצרים קרובים מהקטגוריה ומהשם', 'storyphone-pages' );
					}
					?>
				</p>
			</div>
		</header>

		<div class="sp-reel" data-sp-reel data-sp-reveal>
			<button type="button" class="sp-reel__nav sp-reel__nav--prev" data-sp-reel-prev aria-label="<?php esc_attr_e( 'הקודם', 'storyphone-pages' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9.3 12 15 6.3 13.6 4.9 6.5 12l7.1 7.1L15 17.7 9.3 12Z"/></svg>
			</button>

			<ul class="sp-reel__track" data-sp-reel-track>
				<?php foreach ( $sp_products as $sp_i => $sp_item ) : ?>
					<?php
					if ( ! $sp_item instanceof WC_Product ) {
						continue;
					}
					$sp_link     = get_permalink( $sp_item->get_id() );
					$sp_link     = $sp_link ? $sp_link : home_url( '/' );
					$sp_img      = StoryPhone_Pages_Catalog::get_product_image_url( $sp_item, 'woocommerce_thumbnail' );
					$sp_off      = StoryPhone_Pages_Render::get_discount_percent( $sp_item );
					$sp_can_add  = StoryPhone_Pages_Catalog::supports_quick_add( $sp_item );
					?>
					<li class="sp-reel__item">
						<article class="sp-reelCard" data-sp-reel-card data-sp-tilt>
							<span class="sp-reelCard__shine" aria-hidden="true"></span>
							<a class="sp-reelCard__media" href="<?php echo esc_url( $sp_link ); ?>" tabindex="-1" aria-hidden="true">
								<?php if ( $sp_img ) : ?>
									<img class="sp-reelCard__img" src="<?php echo esc_url( $sp_img ); ?>" alt="" loading="lazy" decoding="async">
								<?php endif; ?>
								<?php if ( $sp_off > 0 ) : ?>
									<span class="sp-badge sp-badge--sale"><?php echo esc_html( $sp_off . '%-' ); ?></span>
								<?php endif; ?>
							</a>
							<div class="sp-reelCard__body">
								<h3 class="sp-reelCard__title">
									<a href="<?php echo esc_url( $sp_link ); ?>"><?php echo esc_html( $sp_item->get_name() ); ?></a>
								</h3>
								<div class="sp-reelCard__price"><?php echo wp_kses_post( $sp_item->get_price_html() ); ?></div>
								<?php if ( $sp_can_add ) : ?>
									<button
										type="button"
										class="sp-btn sp-btn--add"
										data-sp-add-to-cart
										data-product-id="<?php echo esc_attr( (string) $sp_item->get_id() ); ?>"
									>
										<span class="sp-btn__label"><?php esc_html_e( 'הוספה לסל', 'storyphone-pages' ); ?></span>
									</button>
								<?php else : ?>
									<a class="sp-btn sp-btn--ghost" href="<?php echo esc_url( $sp_link ); ?>">
										<?php esc_html_e( 'לצפייה', 'storyphone-pages' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</article>
					</li>
				<?php endforeach; ?>
			</ul>

			<button type="button" class="sp-reel__nav sp-reel__nav--next" data-sp-reel-next aria-label="<?php esc_attr_e( 'הבא', 'storyphone-pages' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14.7 12 9 17.7l1.4 1.4 7.1-7.1-7.1-7.1L9 6.3 14.7 12Z"/></svg>
			</button>
		</div>
	</div>
</section>
