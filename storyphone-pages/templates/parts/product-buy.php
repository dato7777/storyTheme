<?php
/**
 * Part: product buy box.
 *
 * @package StoryPhone_Pages
 *
 * @var array<string, mixed> $args product, discount, categories.
 */

defined( 'ABSPATH' ) || exit;

$sp_product  = isset( $args['product'] ) ? $args['product'] : null;
$sp_discount = isset( $args['discount'] ) ? (int) $args['discount'] : 0;
$sp_cats     = isset( $args['categories'] ) && is_array( $args['categories'] ) ? $args['categories'] : array();

if ( ! $sp_product instanceof WC_Product ) {
	return;
}

$sp_quick   = StoryPhone_Pages_Catalog::supports_quick_add( $sp_product );
$sp_in_stock = $sp_product->is_in_stock();
$sp_type    = $sp_product->get_type();
$sp_sku     = $sp_product->get_sku();
$sp_short   = $sp_product->get_short_description();

$sp_variations = array();
if ( $sp_product->is_type( 'variable' ) && method_exists( $sp_product, 'get_available_variations' ) ) {
	$sp_variations = $sp_product->get_available_variations();
	if ( ! is_array( $sp_variations ) ) {
		$sp_variations = array();
	}
}
?>
<div class="sp-pdpBuy" data-sp-buy data-sp-reveal>
	<?php if ( ! empty( $sp_cats ) ) : ?>
		<div class="sp-pdpBuy__tags">
			<?php foreach ( array_slice( $sp_cats, 0, 3 ) as $sp_cat ) : ?>
				<?php
				if ( ! $sp_cat instanceof WP_Term ) {
					continue;
				}
				$sp_clink = get_term_link( $sp_cat );
				if ( is_wp_error( $sp_clink ) ) {
					continue;
				}
				?>
				<a class="sp-chip" href="<?php echo esc_url( $sp_clink ); ?>"><?php echo esc_html( $sp_cat->name ); ?></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<h1 class="sp-pdpBuy__title"><?php echo esc_html( $sp_product->get_name() ); ?></h1>

	<div class="sp-pdpBuy__price">
		<?php echo wp_kses_post( $sp_product->get_price_html() ); ?>
		<?php if ( $sp_discount > 0 ) : ?>
			<span class="sp-pdpBuy__save">
				<?php
				printf(
					/* translators: %d: discount percentage. */
					esc_html__( 'חוסכים %d%%', 'storyphone-pages' ),
					absint( $sp_discount )
				);
				?>
			</span>
		<?php endif; ?>
	</div>

	<p class="sp-pdpBuy__stock <?php echo $sp_in_stock ? 'is-in' : 'is-out'; ?>">
		<span class="sp-pdpBuy__stockDot" aria-hidden="true"></span>
		<?php
		echo esc_html(
			$sp_in_stock
				? __( 'במלאי · מוכן למשלוח', 'storyphone-pages' )
				: __( 'אזל מהמלאי כרגע', 'storyphone-pages' )
		);
		?>
	</p>

	<?php if ( $sp_short ) : ?>
		<section class="sp-pdpLede" data-sp-pdp-lede aria-label="<?php esc_attr_e( 'תיאור קצר', 'storyphone-pages' ); ?>">
			<header class="sp-pdpLede__head">
				<span class="sp-pdpLede__kicker">
					<span class="sp-pdpLede__kickerZap" aria-hidden="true"></span>
					<?php esc_html_e( 'בקצרה', 'storyphone-pages' ); ?>
				</span>
				<span class="sp-pdpLede__rail" aria-hidden="true"></span>
			</header>
			<div class="sp-pdpLede__body" data-sp-pdp-reveal>
				<?php echo wp_kses_post( wpautop( $sp_short ) ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $sp_variations ) ) : ?>
		<div class="sp-pdpBuy__variations" data-sp-variations>
			<label class="sp-pdpBuy__label" for="sp-variation">
				<?php esc_html_e( 'בחרו אפשרות', 'storyphone-pages' ); ?>
			</label>
			<select id="sp-variation" class="sp-pdpBuy__select" data-sp-variation>
				<option value=""><?php esc_html_e( 'בחרו…', 'storyphone-pages' ); ?></option>
				<?php foreach ( $sp_variations as $sp_var ) : ?>
					<?php
					if ( empty( $sp_var['variation_id'] ) || empty( $sp_var['is_in_stock'] ) ) {
						continue;
					}
					$sp_label_bits = array();
					if ( ! empty( $sp_var['attributes'] ) && is_array( $sp_var['attributes'] ) ) {
						foreach ( $sp_var['attributes'] as $sp_attr_val ) {
							if ( '' !== (string) $sp_attr_val ) {
								$sp_label_bits[] = $sp_attr_val;
							}
						}
					}
					$sp_vlabel = ! empty( $sp_label_bits ) ? implode( ' · ', $sp_label_bits ) : (string) $sp_var['variation_id'];
					$sp_vprice = isset( $sp_var['price_html'] ) ? wp_strip_all_tags( $sp_var['price_html'] ) : '';
					?>
					<option
						value="<?php echo esc_attr( (string) $sp_var['variation_id'] ); ?>"
						data-price="<?php echo esc_attr( $sp_vprice ); ?>"
					>
						<?php echo esc_html( $sp_vlabel . ( $sp_vprice ? ' — ' . $sp_vprice : '' ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	<?php endif; ?>

	<div class="sp-pdpBuy__actions">
		<?php if ( $sp_in_stock && ( $sp_quick || ! empty( $sp_variations ) ) ) : ?>
			<div class="sp-qty" data-sp-qty-wrap>
				<button type="button" class="sp-qty__btn" data-sp-qty-step="-1" aria-label="<?php esc_attr_e( 'הקטנת כמות', 'storyphone-pages' ); ?>">−</button>
				<input class="sp-qty__input" type="number" min="1" max="99" value="1" inputmode="numeric" data-sp-qty aria-label="<?php esc_attr_e( 'כמות', 'storyphone-pages' ); ?>">
				<button type="button" class="sp-qty__btn" data-sp-qty-step="1" aria-label="<?php esc_attr_e( 'הגדלת כמות', 'storyphone-pages' ); ?>">+</button>
			</div>

			<button
				type="button"
				class="sp-btn sp-btn--primary sp-btn--lg sp-pdpBuy__cta"
				data-sp-add-to-cart
				data-product-id="<?php echo esc_attr( (string) ( $sp_quick ? $sp_product->get_id() : '' ) ); ?>"
				<?php echo empty( $sp_variations ) ? '' : ' disabled'; ?>
			>
				<span class="sp-btn__label"><?php esc_html_e( 'הוספה לסל', 'storyphone-pages' ); ?></span>
			</button>
		<?php elseif ( $sp_in_stock && 'external' === $sp_type ) : ?>
			<a class="sp-btn sp-btn--primary sp-btn--lg sp-pdpBuy__cta" href="<?php echo esc_url( $sp_product->get_product_url() ); ?>" rel="noopener noreferrer" target="_blank">
				<?php echo esc_html( $sp_product->single_add_to_cart_text() ); ?>
			</a>
		<?php else : ?>
			<button type="button" class="sp-btn sp-btn--primary sp-btn--lg sp-pdpBuy__cta" disabled>
				<span class="sp-btn__label"><?php esc_html_e( 'לא זמין כרגע', 'storyphone-pages' ); ?></span>
			</button>
		<?php endif; ?>
	</div>

	<ul class="sp-pdpBuy__trust">
		<li><span class="sp-eyebrow__dot" aria-hidden="true"></span><?php esc_html_e( 'יבוא רשמי', 'storyphone-pages' ); ?></li>
		<li><span class="sp-eyebrow__dot" aria-hidden="true"></span><?php esc_html_e( 'אחריות מלאה', 'storyphone-pages' ); ?></li>
		<li><span class="sp-eyebrow__dot" aria-hidden="true"></span><?php esc_html_e( 'משלוח מהיר', 'storyphone-pages' ); ?></li>
	</ul>

	<?php if ( $sp_sku ) : ?>
		<p class="sp-pdpBuy__sku">
			<span class="sp-pdpBuy__skuLabel"><?php esc_html_e( 'מק״ט:', 'storyphone-pages' ); ?></span>
			<span class="sp-pdpBuy__skuValue"><?php echo esc_html( $sp_sku ); ?></span>
		</p>
	<?php endif; ?>
</div>
