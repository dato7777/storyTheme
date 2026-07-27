<?php
/**
 * Part: cart drawer shell.
 *
 * The drawer is a *view* of WooCommerce's real cart, filled by JavaScript from
 * the Store API. Checkout and cart links point at WooCommerce's own pages, so
 * the customer always completes the order through the existing flow.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

$sp_cart_url     = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
$sp_checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' );
?>
<div class="sp-drawer" data-sp-drawer hidden>
	<div class="sp-drawer__scrim" data-sp-drawer-close></div>

	<aside
		class="sp-drawer__panel"
		role="dialog"
		aria-modal="true"
		aria-labelledby="sp-drawer-title"
	>
		<header class="sp-drawer__head">
			<h2 class="sp-drawer__title" id="sp-drawer-title"><?php esc_html_e( 'סל הקניות', 'storyphone-pages' ); ?></h2>
			<button
				type="button"
				class="sp-drawer__close"
				data-sp-drawer-close
				aria-label="<?php esc_attr_e( 'סגירת הסל', 'storyphone-pages' ); ?>"
			>
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="M18.3 5.7 12 12l6.3 6.3-1.4 1.4L10.6 13.4 4.3 19.7 2.9 18.3 9.2 12 2.9 5.7 4.3 4.3l6.3 6.3 6.3-6.3 1.4 1.4Z"/>
				</svg>
			</button>
		</header>

		<div class="sp-drawer__body" data-sp-drawer-items>
			<p class="sp-drawer__empty"><?php esc_html_e( 'הסל שלך ריק', 'storyphone-pages' ); ?></p>
		</div>

		<footer class="sp-drawer__foot" data-sp-drawer-foot hidden>
			<div class="sp-drawer__totals">
				<span><?php esc_html_e( 'סה"כ', 'storyphone-pages' ); ?></span>
				<strong data-sp-cart-total>&mdash;</strong>
			</div>
			<p class="sp-drawer__note"><?php esc_html_e( 'משלוח ומיסים מחושבים בעמוד התשלום', 'storyphone-pages' ); ?></p>
			<a class="sp-btn sp-btn--primary sp-btn--block" href="<?php echo esc_url( $sp_checkout_url ); ?>">
				<?php esc_html_e( 'מעבר לתשלום', 'storyphone-pages' ); ?>
			</a>
			<a class="sp-textlink sp-drawer__cartlink" href="<?php echo esc_url( $sp_cart_url ); ?>">
				<?php esc_html_e( 'צפייה בסל המלא', 'storyphone-pages' ); ?>
			</a>
		</footer>
	</aside>
</div>

<div class="sp-toast" data-sp-toast role="status" aria-live="polite" hidden></div>
