<?php
/**
 * Part: sticky site header.
 *
 * Primary nav items are parent product categories. They are hoverable (not
 * clickable) so child categories can appear in the hero stage — that saves
 * shoppers an extra category-page hop. Search lives only in the hero.
 *
 * @package StoryPhone_Pages
 *
 * @var array<string, mixed> $args Expects 'nav' => nav tree from Catalog::get_nav_tree().
 */

defined( 'ABSPATH' ) || exit;

$sp_shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$sp_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
$sp_nav         = isset( $args['nav'] ) && is_array( $args['nav'] ) ? $args['nav'] : StoryPhone_Pages_Catalog::get_nav_tree( 9 );
$sp_nav_meta    = StoryPhone_Pages_Catalog::get_last_nav_meta();
$sp_nav_mode    = isset( $sp_nav_meta['mode'] ) ? (string) $sp_nav_meta['mode'] : 'auto';
$sp_nav_count   = isset( $sp_nav_meta['count'] ) ? (int) $sp_nav_meta['count'] : count( $sp_nav );
$sp_nav_ids     = isset( $sp_nav_meta['ids'] ) && is_array( $sp_nav_meta['ids'] ) ? implode( ',', array_map( 'intval', $sp_nav_meta['ids'] ) ) : '';
?>
<!-- sp-nav mode=<?php echo esc_html( $sp_nav_mode ); ?> count=<?php echo esc_html( (string) $sp_nav_count ); ?> ids=<?php echo esc_html( $sp_nav_ids ); ?> catalog=<?php echo esc_html( (string) filemtime( STORYPHONE_PAGES_PLUGIN_DIR . 'includes/class-catalog.php' ) ); ?> -->
<header
	class="sp-header"
	data-sp-header
	data-sp-nav-mode="<?php echo esc_attr( $sp_nav_mode ); ?>"
	data-sp-nav-count="<?php echo esc_attr( (string) $sp_nav_count ); ?>"
>
	<div class="sp-header__inner sp-shell">

		<button
			type="button"
			class="sp-header__burger"
			data-sp-nav-toggle
			aria-expanded="false"
			aria-controls="sp-nav"
			aria-label="<?php esc_attr_e( 'פתיחת תפריט', 'storyphone-pages' ); ?>"
		>
			<span class="sp-burger__bar"></span>
			<span class="sp-burger__bar"></span>
			<span class="sp-burger__bar"></span>
		</button>

		<div class="sp-header__brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="sp-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="sp-brand__mark" aria-hidden="true">
						<span class="sp-brand__ring"></span>
					</span>
					<span class="sp-brand__text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<nav id="sp-nav" class="sp-nav" aria-label="<?php esc_attr_e( 'תפריט ראשי', 'storyphone-pages' ); ?>">
			<?php if ( ! empty( $sp_nav ) ) : ?>
				<ul class="sp-nav__list">
					<?php foreach ( $sp_nav as $sp_entry ) : ?>
						<?php
						if ( empty( $sp_entry['term'] ) || ! $sp_entry['term'] instanceof WP_Term ) {
							continue;
						}
						$sp_parent   = $sp_entry['term'];
						$sp_panel_id = 'cat-' . (int) $sp_parent->term_id;
						$sp_children = isset( $sp_entry['children'] ) && is_array( $sp_entry['children'] ) ? $sp_entry['children'] : array();
						?>
						<li class="sp-nav__item">
							<button
								type="button"
								class="sp-nav__trigger"
								data-sp-nav-trigger="<?php echo esc_attr( $sp_panel_id ); ?>"
								aria-expanded="false"
								aria-controls="sp-nav-panel-<?php echo esc_attr( $sp_panel_id ); ?>"
							>
								<?php echo esc_html( $sp_parent->name ); ?>
								<span class="sp-nav__chev" aria-hidden="true"></span>
							</button>

							<?php /* Mobile accordion: same children, plain links. Desktop uses the hero stage. */ ?>
							<?php if ( ! empty( $sp_children ) ) : ?>
								<ul class="sp-nav__drawer" id="sp-nav-panel-<?php echo esc_attr( $sp_panel_id ); ?>" hidden>
									<?php foreach ( $sp_children as $sp_child ) : ?>
										<?php
										if ( ! $sp_child instanceof WP_Term ) {
											continue;
										}
										$sp_child_link = get_term_link( $sp_child );
										if ( is_wp_error( $sp_child_link ) ) {
											continue;
										}
										?>
										<li>
											<a href="<?php echo esc_url( $sp_child_link ); ?>">
												<?php echo esc_html( $sp_child->name ); ?>
												<span class="sp-nav__drawerCount"><?php echo esc_html( (string) (int) $sp_child->count ); ?></span>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php else : ?>
								<?php
								$sp_parent_link = get_term_link( $sp_parent );
								if ( ! is_wp_error( $sp_parent_link ) ) :
									?>
									<ul class="sp-nav__drawer" id="sp-nav-panel-<?php echo esc_attr( $sp_panel_id ); ?>" hidden>
										<li>
											<a href="<?php echo esc_url( $sp_parent_link ); ?>">
												<?php
												printf(
													/* translators: %s: category name. */
													esc_html__( 'לכל המוצרים ב%s', 'storyphone-pages' ),
													esc_html( $sp_parent->name )
												);
												?>
											</a>
										</li>
									</ul>
								<?php endif; ?>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</nav>

		<div class="sp-header__actions">
			<?php if ( $sp_account_url ) : ?>
				<a class="sp-iconbtn sp-iconbtn--account" href="<?php echo esc_url( $sp_account_url ); ?>" aria-label="<?php esc_attr_e( 'האזור האישי', 'storyphone-pages' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M12 12.8a4.4 4.4 0 1 0 0-8.8 4.4 4.4 0 0 0 0 8.8Zm0 2c-4 0-7.2 2.3-7.2 5.2 0 .6.4 1 1 1h12.4c.6 0 1-.4 1-1 0-2.9-3.2-5.2-7.2-5.2Z"/>
					</svg>
				</a>
			<?php endif; ?>

			<button
				type="button"
				class="sp-iconbtn sp-iconbtn--cart"
				data-sp-cart-toggle
				aria-label="<?php esc_attr_e( 'פתיחת סל הקניות', 'storyphone-pages' ); ?>"
			>
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M7 8V6.8a5 5 0 0 1 10 0V8h2.1a1 1 0 0 1 1 1.1l-1 10.3a2 2 0 0 1-2 1.8H6.9a2 2 0 0 1-2-1.8l-1-10.3A1 1 0 0 1 4.9 8H7Zm2 0h6V6.8a3 3 0 0 0-6 0V8Z"/>
				</svg>
				<span class="sp-cartcount" data-sp-cart-count hidden>0</span>
			</button>

			<a class="sp-btn sp-btn--primary sp-header__shop" href="<?php echo esc_url( $sp_shop_url ); ?>">
				<?php esc_html_e( 'לחנות', 'storyphone-pages' ); ?>
			</a>
		</div>

	</div>
</header>
