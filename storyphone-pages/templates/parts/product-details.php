<?php
/**
 * Part: product long description, YouTube slot, and attributes.
 *
 * Video URL is read from product meta `_storyphone_youtube` (full YouTube URL
 * or bare video id). When empty, a styled placeholder still reserves the slot.
 *
 * @package StoryPhone_Pages
 *
 * @var array<string, mixed> $args product.
 */

defined( 'ABSPATH' ) || exit;

$sp_product = isset( $args['product'] ) ? $args['product'] : null;
if ( ! $sp_product instanceof WC_Product ) {
	return;
}

$sp_desc       = $sp_product->get_description();
$sp_attributes = function_exists( 'wc_attributes_array_filter_visible' )
	? array_filter( $sp_product->get_attributes(), 'wc_attributes_array_filter_visible' )
	: $sp_product->get_attributes();

$sp_yt_raw = (string) $sp_product->get_meta( '_storyphone_youtube', true );
if ( '' === $sp_yt_raw ) {
	$sp_yt_raw = (string) $sp_product->get_meta( 'storyphone_youtube', true );
}
$sp_yt_id = StoryPhone_Pages_Catalog::parse_youtube_id( $sp_yt_raw );

if ( ! $sp_desc && empty( $sp_attributes ) && '' === $sp_yt_raw ) {
	return;
}
?>
<section class="sp-pdpDetails" id="sp-details">
	<div class="sp-shell">

		<?php if ( $sp_desc || $sp_yt_id || '' !== $sp_yt_raw ) : ?>
			<div class="sp-pdpDetails__story">

				<?php if ( $sp_desc ) : ?>
					<article class="sp-pdpDetails__copy" data-sp-reveal>
						<header class="sp-section__head">
							<div>
								<p class="sp-eyebrow">
									<span class="sp-eyebrow__item">
										<span class="sp-eyebrow__dot" aria-hidden="true"></span>
										<?php esc_html_e( 'הסיפור המלא', 'storyphone-pages' ); ?>
									</span>
								</p>
								<h2 class="sp-section__title"><?php esc_html_e( 'תיאור המוצר', 'storyphone-pages' ); ?></h2>
							</div>
						</header>
						<div class="sp-prose">
							<?php echo wp_kses_post( wpautop( do_shortcode( $sp_desc ) ) ); ?>
						</div>
					</article>
				<?php endif; ?>

				<aside class="sp-pdpVideo" data-sp-reveal aria-label="<?php esc_attr_e( 'סרטון המוצר', 'storyphone-pages' ); ?>">
					<?php if ( $sp_yt_id ) : ?>
						<?php
						$sp_yt_watch = 'https://www.youtube.com/watch?v=' . rawurlencode( $sp_yt_id );
						$sp_yt_embed = 'https://www.youtube.com/embed/' . rawurlencode( $sp_yt_id );
						?>
						<div
							class="sp-pdpVideo__shell"
							data-sp-yt="<?php echo esc_attr( $sp_yt_id ); ?>"
							data-sp-yt-title="<?php echo esc_attr( $sp_product->get_name() ); ?>"
							data-sp-yt-embed="<?php echo esc_url( $sp_yt_embed ); ?>"
							data-sp-yt-autoplay="1"
						>
							<img
								class="sp-pdpVideo__poster"
								src="<?php echo esc_url( 'https://i.ytimg.com/vi/' . rawurlencode( $sp_yt_id ) . '/hqdefault.jpg' ); ?>"
								alt=""
								loading="lazy"
								decoding="async"
								data-sp-yt-poster
							>
							<button type="button" class="sp-pdpVideo__play" data-sp-yt-play aria-label="<?php esc_attr_e( 'נגן סרטון', 'storyphone-pages' ); ?>">
								<span class="sp-pdpVideo__playIcon" aria-hidden="true"></span>
							</button>
							<a
								class="sp-pdpVideo__caption"
								href="<?php echo esc_url( $sp_yt_watch ); ?>"
								target="_blank"
								rel="noopener noreferrer"
								data-sp-yt-open
							>
								<?php esc_html_e( 'פתחו ביוטיוב ↗', 'storyphone-pages' ); ?>
							</a>
						</div>
					<?php else : ?>
						<div class="sp-pdpVideo__shell sp-pdpVideo__shell--empty">
							<span class="sp-pdpVideo__emptyMark" aria-hidden="true"></span>
							<p class="sp-pdpVideo__emptyTitle"><?php esc_html_e( 'סרטון YouTube', 'storyphone-pages' ); ?></p>
							<p class="sp-pdpVideo__emptyHint">
								<?php esc_html_e( 'הוסיפו קישור בשדה המוצר _storyphone_youtube', 'storyphone-pages' ); ?>
							</p>
						</div>
					<?php endif; ?>
				</aside>

			</div>
		<?php endif; ?>

		<?php if ( ! empty( $sp_attributes ) ) : ?>
			<aside class="sp-pdpSpecs" data-sp-reveal>
				<h2 class="sp-pdpSpecs__title"><?php esc_html_e( 'מפרט', 'storyphone-pages' ); ?></h2>
				<table class="sp-pdpSpecs__table">
					<tbody>
						<?php foreach ( $sp_attributes as $sp_attr ) : ?>
							<?php
							if ( ! $sp_attr instanceof WC_Product_Attribute ) {
								continue;
							}
							$sp_label = wc_attribute_label( $sp_attr->get_name() );
							if ( $sp_attr->is_taxonomy() ) {
								$sp_values = wc_get_product_terms(
									$sp_product->get_id(),
									$sp_attr->get_name(),
									array( 'fields' => 'names' )
								);
								$sp_value = is_array( $sp_values ) ? implode( ', ', $sp_values ) : '';
							} else {
								$sp_value = implode( ', ', $sp_attr->get_options() );
							}
							if ( '' === $sp_value ) {
								continue;
							}
							?>
							<tr>
								<th scope="row"><?php echo esc_html( $sp_label ); ?></th>
								<td><?php echo esc_html( $sp_value ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</aside>
		<?php endif; ?>

	</div>
</section>
