<?php
/**
 * Markup helpers shared by the page templates.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders reusable front-end fragments.
 */
class StoryPhone_Pages_Render {

	/**
	 * Include a template part from templates/parts/.
	 *
	 * @param string               $name Part name without extension.
	 * @param array<string, mixed> $args Variables exposed to the part as $args.
	 * @return void
	 */
	public static function part( $name, array $args = array() ) {
		$path = STORYPHONE_PAGES_PLUGIN_DIR . 'templates/parts/' . sanitize_file_name( $name ) . '.php';
		if ( ! file_exists( $path ) ) {
			return;
		}

		include $path;
	}

	/**
	 * Discount percentage for a product on sale, or 0.
	 *
	 * @param WC_Product $product Product.
	 * @return int
	 */
	public static function get_discount_percent( $product ) {
		if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
			return 0;
		}

		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();

		if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
			return 0;
		}

		return (int) round( ( 1 - ( $sale / $regular ) ) * 100 );
	}

	/**
	 * Render one product card.
	 *
	 * @param WC_Product           $product Product.
	 * @param array<string, mixed> $args    Optional. `reveal` (bool) toggles scroll-reveal.
	 * @return void
	 */
	public static function product_card( $product, array $args = array() ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$permalink = get_permalink( $product->get_id() );
		$permalink = $permalink ? $permalink : home_url( '/' );
		$in_stock  = $product->is_in_stock();
		$discount  = self::get_discount_percent( $product );
		$quick_add = StoryPhone_Pages_Catalog::supports_quick_add( $product );
		$reveal    = ! array_key_exists( 'reveal', $args ) || (bool) $args['reveal'];
		$image     = $product->get_image(
			'woocommerce_thumbnail',
			array(
				'class'   => 'sp-card__img',
				'loading' => 'lazy',
				'decoding' => 'async',
			)
		);
		?>
		<article
			class="sp-card"
			data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
			<?php echo $reveal ? ' data-sp-reveal' : ''; ?>
		>
			<a class="sp-card__media" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
				<?php echo wp_kses_post( $image ); ?>
				<?php if ( ! $in_stock ) : ?>
					<span class="sp-badge sp-badge--muted"><?php esc_html_e( 'אזל מהמלאי', 'storyphone-pages' ); ?></span>
				<?php elseif ( $discount > 0 ) : ?>
					<span class="sp-badge sp-badge--sale">
						<?php
						printf(
							/* translators: %d: discount percentage. */
							esc_html__( '%d%%- הנחה', 'storyphone-pages' ),
							absint( $discount )
						);
						?>
					</span>
				<?php endif; ?>
			</a>

			<div class="sp-card__body">
				<h3 class="sp-card__title">
					<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
				</h3>

				<div class="sp-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>

				<?php if ( $quick_add ) : ?>
					<button
						type="button"
						class="sp-btn sp-btn--add"
						data-sp-add-to-cart
						data-product-id="<?php echo esc_attr( (string) $product->get_id() ); ?>"
					>
						<span class="sp-btn__label"><?php esc_html_e( 'הוספה לסל', 'storyphone-pages' ); ?></span>
					</button>
				<?php else : ?>
					<a class="sp-btn sp-btn--ghost" href="<?php echo esc_url( $permalink ); ?>">
						<?php echo esc_html( $in_stock ? $product->add_to_cart_text() : __( 'לצפייה במוצר', 'storyphone-pages' ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Render one category card.
	 *
	 * @param WP_Term $term Product category.
	 * @return void
	 */
	public static function category_card( $term ) {
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$link  = get_term_link( $term );
		$link  = is_wp_error( $link ) ? home_url( '/' ) : $link;
		$image = StoryPhone_Pages_Catalog::get_category_image( $term, 'woocommerce_thumbnail' );
		?>
		<a class="sp-cat" href="<?php echo esc_url( $link ); ?>" data-sp-reveal>
			<span class="sp-cat__media">
				<?php if ( $image ) : ?>
					<img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy" decoding="async" class="sp-cat__img">
				<?php endif; ?>
			</span>
			<span class="sp-cat__meta">
				<span class="sp-cat__name"><?php echo esc_html( $term->name ); ?></span>
				<span class="sp-cat__count">
					<?php
					printf(
						/* translators: %s: number of products. */
						esc_html__( '%s מוצרים', 'storyphone-pages' ),
						esc_html( number_format_i18n( (int) $term->count ) )
					);
					?>
				</span>
			</span>
		</a>
		<?php
	}
}
