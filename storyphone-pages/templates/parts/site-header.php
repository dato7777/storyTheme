<?php
/**
 * Part: sticky site header.
 *
 * Navigation links come from Appearance → Menus so the client keeps editing
 * them; only the appearance is ours. Search is promoted to a primary control
 * rather than a lonely input, because on a catalog this size search is how
 * people actually find things.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

$sp_shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$sp_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : '';
?>
<header class="sp-header" data-sp-header>
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
			<?php
			$sp_menu_args = array(
				'container'   => false,
				'menu_class'  => 'sp-nav__list',
				'depth'       => 2,
				'fallback_cb' => false,
				'items_wrap'  => '<ul class="%2$s">%3$s</ul>',
			);

			if ( has_nav_menu( 'storyphone_primary' ) ) {
				wp_nav_menu( array_merge( $sp_menu_args, array( 'theme_location' => 'storyphone_primary' ) ) );
			} elseif ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array_merge( $sp_menu_args, array( 'theme_location' => 'primary' ) ) );
			} else {
				// No menu assigned yet: fall back to the busiest product categories.
				$sp_fallback = StoryPhone_Pages_Catalog::get_categories( 5 );
				if ( ! empty( $sp_fallback ) ) {
					echo '<ul class="sp-nav__list">';
					foreach ( $sp_fallback as $sp_term ) {
						$sp_term_link = get_term_link( $sp_term );
						if ( is_wp_error( $sp_term_link ) ) {
							continue;
						}
						printf(
							'<li class="menu-item"><a href="%1$s">%2$s</a></li>',
							esc_url( $sp_term_link ),
							esc_html( $sp_term->name )
						);
					}
					echo '</ul>';
				}
			}
			?>
		</nav>

		<button type="button" class="sp-searchpill" data-sp-search-open>
			<svg class="sp-searchpill__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M10.5 3a7.5 7.5 0 1 0 4.55 13.46l4.24 4.25 1.42-1.42-4.25-4.24A7.5 7.5 0 0 0 10.5 3Zm0 2a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z"/>
			</svg>
			<span class="sp-searchpill__label"><?php esc_html_e( 'חיפוש מכשיר, מותג או דגם…', 'storyphone-pages' ); ?></span>
			<kbd class="sp-searchpill__kbd">/</kbd>
		</button>

		<div class="sp-header__actions">
			<button
				type="button"
				class="sp-iconbtn sp-iconbtn--search"
				data-sp-search-open
				aria-label="<?php esc_attr_e( 'חיפוש', 'storyphone-pages' ); ?>"
			>
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M10.5 3a7.5 7.5 0 1 0 4.55 13.46l4.24 4.25 1.42-1.42-4.25-4.24A7.5 7.5 0 0 0 10.5 3Zm0 2a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z"/>
				</svg>
			</button>

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
