<?php
/**
 * Part: desktop nav mega panel for non-home pages.
 *
 * Same child-category cards as the homepage hero stage, rendered as a dropdown
 * under the sticky header so product/category pages behave identically.
 *
 * @package StoryPhone_Pages
 *
 * @var array<string, mixed> $args Expects 'nav' => nav tree.
 */

defined( 'ABSPATH' ) || exit;

$sp_nav = isset( $args['nav'] ) && is_array( $args['nav'] ) ? $args['nav'] : array();
if ( empty( $sp_nav ) ) {
	return;
}
?>
<aside
	class="sp-navMega"
	data-sp-nav-stage
	data-sp-nav-mega
	aria-live="polite"
	aria-label="<?php esc_attr_e( 'קטגוריות משנה', 'storyphone-pages' ); ?>"
>
	<div class="sp-shell sp-navMega__inner">
		<?php foreach ( $sp_nav as $sp_entry ) : ?>
			<?php
			if ( empty( $sp_entry['term'] ) || ! $sp_entry['term'] instanceof WP_Term ) {
				continue;
			}
			$sp_parent   = $sp_entry['term'];
			$sp_children = isset( $sp_entry['children'] ) && is_array( $sp_entry['children'] ) ? $sp_entry['children'] : array();
			$sp_panel_id = 'cat-' . (int) $sp_parent->term_id;
			?>
			<div class="sp-navStage" data-sp-nav-panel="<?php echo esc_attr( $sp_panel_id ); ?>" hidden>
				<p class="sp-navStage__label"><?php echo esc_html( $sp_parent->name ); ?></p>
				<div class="sp-navCards">
					<?php if ( ! empty( $sp_children ) ) : ?>
						<?php foreach ( $sp_children as $sp_child ) : ?>
							<?php
							if ( ! $sp_child instanceof WP_Term ) {
								continue;
							}
							$sp_child_link = get_term_link( $sp_child );
							if ( is_wp_error( $sp_child_link ) ) {
								continue;
							}
							$sp_cover = StoryPhone_Pages_Catalog::get_category_cover( $sp_child, 'woocommerce_thumbnail' );
							$sp_mono  = function_exists( 'mb_substr' )
								? mb_substr( $sp_child->name, 0, 1 )
								: substr( $sp_child->name, 0, 1 );
							?>
							<a class="sp-navCard" href="<?php echo esc_url( $sp_child_link ); ?>" data-sp-tilt>
								<span class="sp-navCard__glow" aria-hidden="true"></span>
								<span class="sp-navCard__shine" aria-hidden="true"></span>
								<span class="sp-navCard__media<?php echo '' === $sp_cover ? ' sp-navCard__media--empty' : ''; ?>">
									<?php if ( '' !== $sp_cover ) : ?>
										<img class="sp-navCard__img" src="<?php echo esc_url( $sp_cover ); ?>" alt="" loading="lazy" decoding="async">
									<?php else : ?>
										<span class="sp-navCard__mono" aria-hidden="true"><?php echo esc_html( $sp_mono ); ?></span>
									<?php endif; ?>
								</span>
								<span class="sp-navCard__body">
									<span class="sp-navCard__name"><?php echo esc_html( $sp_child->name ); ?></span>
								</span>
							</a>
						<?php endforeach; ?>
					<?php else : ?>
						<?php
						$sp_leaf_products = StoryPhone_Pages_Catalog::get_category_products( $sp_parent, 4 );
						foreach ( $sp_leaf_products as $sp_leaf ) :
							$sp_leaf_link = get_permalink( $sp_leaf->get_id() );
							$sp_leaf_link = $sp_leaf_link ? $sp_leaf_link : home_url( '/' );
							$sp_leaf_img  = StoryPhone_Pages_Catalog::get_product_image_url( $sp_leaf, 'woocommerce_thumbnail' );
							?>
							<a class="sp-navCard" href="<?php echo esc_url( $sp_leaf_link ); ?>" data-sp-tilt>
								<span class="sp-navCard__glow" aria-hidden="true"></span>
								<span class="sp-navCard__shine" aria-hidden="true"></span>
								<span class="sp-navCard__media">
									<?php if ( '' !== $sp_leaf_img ) : ?>
										<img class="sp-navCard__img" src="<?php echo esc_url( $sp_leaf_img ); ?>" alt="" loading="lazy" decoding="async">
									<?php endif; ?>
								</span>
								<span class="sp-navCard__body">
									<span class="sp-navCard__name"><?php echo esc_html( $sp_leaf->get_name() ); ?></span>
								</span>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</aside>
