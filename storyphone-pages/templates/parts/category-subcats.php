<?php
/**
 * Part: subcategory chip selector.
 *
 * @package StoryPhone_Pages
 *
 * @var array<string, mixed> $args term, children, active_id.
 */

defined( 'ABSPATH' ) || exit;

$sp_term     = isset( $args['term'] ) ? $args['term'] : null;
$sp_children = isset( $args['children'] ) && is_array( $args['children'] ) ? $args['children'] : array();

if ( ! $sp_term instanceof WP_Term ) {
	return;
}

if ( empty( $sp_children ) ) {
	return;
}
?>
<section class="sp-catSubs" data-sp-cat-subs aria-label="<?php esc_attr_e( 'סינון לפי תת־קטגוריה', 'storyphone-pages' ); ?>">
	<div class="sp-shell">
		<div class="sp-catSubs__track" role="tablist" aria-label="<?php esc_attr_e( 'תת־קטגוריות', 'storyphone-pages' ); ?>" data-sp-cat-chips>
			<button
				type="button"
				class="sp-catChip is-active"
				role="tab"
				aria-selected="true"
				data-sp-cat-chip
				data-term-id="<?php echo esc_attr( (string) (int) $sp_term->term_id ); ?>"
				data-term-name="<?php echo esc_attr( $sp_term->name ); ?>"
			>
				<span class="sp-catChip__icon" aria-hidden="true"></span>
				<span class="sp-catChip__label"><?php esc_html_e( 'הכל', 'storyphone-pages' ); ?></span>
				<span class="sp-catChip__count"><?php echo esc_html( (string) (int) $sp_term->count ); ?></span>
			</button>

			<?php foreach ( $sp_children as $sp_child ) : ?>
				<?php
				if ( ! $sp_child instanceof WP_Term ) {
					continue;
				}
				$sp_child_img = StoryPhone_Pages_Catalog::get_category_cover( $sp_child, 'woocommerce_gallery_thumbnail' );
				?>
				<button
					type="button"
					class="sp-catChip"
					role="tab"
					aria-selected="false"
					data-sp-cat-chip
					data-term-id="<?php echo esc_attr( (string) (int) $sp_child->term_id ); ?>"
					data-term-name="<?php echo esc_attr( $sp_child->name ); ?>"
				>
					<span class="sp-catChip__icon" aria-hidden="true">
						<?php if ( $sp_child_img ) : ?>
							<img src="<?php echo esc_url( $sp_child_img ); ?>" alt="" loading="lazy" decoding="async">
						<?php endif; ?>
					</span>
					<span class="sp-catChip__label"><?php echo esc_html( $sp_child->name ); ?></span>
					<span class="sp-catChip__count"><?php echo esc_html( (string) (int) $sp_child->count ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	</div>
</section>
