<?php
/**
 * Part: cinematic product gallery.
 *
 * @package StoryPhone_Pages
 *
 * @var array<string, mixed> $args product, gallery (attachment ids), discount.
 */

defined( 'ABSPATH' ) || exit;

$sp_product  = isset( $args['product'] ) ? $args['product'] : null;
$sp_gallery  = isset( $args['gallery'] ) && is_array( $args['gallery'] ) ? $args['gallery'] : array();
$sp_discount = isset( $args['discount'] ) ? (int) $args['discount'] : 0;

if ( ! $sp_product instanceof WC_Product ) {
	return;
}

$sp_placeholder = function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_single' ) : '';
?>
<div class="sp-pdpGallery" data-sp-gallery data-sp-reveal>
	<div class="sp-pdpGallery__stage is-sweeping" data-sp-gallery-stage data-sp-tilt>
		<div class="sp-pdpGallery__glow" aria-hidden="true"></div>
		<div class="sp-pdpGallery__frame">
			<?php if ( ! empty( $sp_gallery ) ) : ?>
				<?php foreach ( $sp_gallery as $sp_i => $sp_aid ) : ?>
					<?php
					$sp_full = wp_get_attachment_image_url( $sp_aid, 'woocommerce_single' );
					$sp_alt  = get_post_meta( $sp_aid, '_wp_attachment_image_alt', true );
					if ( ! is_string( $sp_alt ) || '' === $sp_alt ) {
						$sp_alt = $sp_product->get_name();
					}
					?>
					<img
						class="sp-pdpGallery__img<?php echo 0 === $sp_i ? ' is-active' : ''; ?>"
						src="<?php echo esc_url( $sp_full ? $sp_full : $sp_placeholder ); ?>"
						alt="<?php echo esc_attr( $sp_alt ); ?>"
						data-sp-gallery-img
						data-index="<?php echo esc_attr( (string) $sp_i ); ?>"
						<?php echo 0 === $sp_i ? '' : ' hidden'; ?>
						<?php echo 0 === $sp_i ? 'loading="eager"' : 'loading="lazy"'; ?>
						decoding="async"
					>
				<?php endforeach; ?>
			<?php else : ?>
				<img
					class="sp-pdpGallery__img is-active"
					src="<?php echo esc_url( $sp_placeholder ); ?>"
					alt="<?php echo esc_attr( $sp_product->get_name() ); ?>"
					data-sp-gallery-img
					data-index="0"
					loading="eager"
					decoding="async"
				>
			<?php endif; ?>

			<?php if ( $sp_discount > 0 ) : ?>
				<span class="sp-badge sp-badge--sale sp-pdpGallery__sale">
					<?php
					printf(
						/* translators: %d: discount percentage. */
						esc_html__( '%d%%- הנחה', 'storyphone-pages' ),
						absint( $sp_discount )
					);
					?>
				</span>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( count( $sp_gallery ) > 1 ) : ?>
		<div class="sp-pdpGallery__thumbs" role="tablist" aria-label="<?php esc_attr_e( 'תמונות המוצר', 'storyphone-pages' ); ?>">
			<?php foreach ( $sp_gallery as $sp_i => $sp_aid ) : ?>
				<?php
				$sp_thumb = wp_get_attachment_image_url( $sp_aid, 'woocommerce_gallery_thumbnail' );
				if ( ! $sp_thumb ) {
					$sp_thumb = wp_get_attachment_image_url( $sp_aid, 'thumbnail' );
				}
				?>
				<button
					type="button"
					class="sp-pdpGallery__thumb<?php echo 0 === $sp_i ? ' is-active' : ''; ?>"
					data-sp-gallery-thumb
					data-index="<?php echo esc_attr( (string) $sp_i ); ?>"
					role="tab"
					aria-selected="<?php echo 0 === $sp_i ? 'true' : 'false'; ?>"
				>
					<img src="<?php echo esc_url( $sp_thumb ? $sp_thumb : $sp_placeholder ); ?>" alt="" loading="lazy" decoding="async">
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
