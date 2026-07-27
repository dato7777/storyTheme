<?php
/**
 * Part: full-screen story viewer shell.
 *
 * Empty on purpose — JavaScript fills it from the JSON payload printed at the
 * end of the document. Add-to-cart inside a story goes through the same Store
 * API path as everywhere else, so WooCommerce still owns the transaction.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sp-viewer" data-sp-viewer hidden>
	<div class="sp-viewer__scrim" data-sp-viewer-close></div>

	<div
		class="sp-viewer__stage"
		role="dialog"
		aria-modal="true"
		aria-label="<?php esc_attr_e( 'סטורי קטגוריה', 'storyphone-pages' ); ?>"
	>
		<div class="sp-viewer__bars" data-sp-viewer-bars></div>

		<header class="sp-viewer__head">
			<div class="sp-viewer__id">
				<span class="sp-viewer__avatar" data-sp-viewer-avatar aria-hidden="true"></span>
				<span class="sp-viewer__meta">
					<span class="sp-viewer__cat" data-sp-viewer-category></span>
					<span class="sp-viewer__pos" data-sp-viewer-position></span>
				</span>
			</div>

			<div class="sp-viewer__tools">
				<button
					type="button"
					class="sp-viewer__tool"
					data-sp-viewer-pause
					aria-label="<?php esc_attr_e( 'השהיה', 'storyphone-pages' ); ?>"
				>
					<svg class="sp-viewer__iconPause" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5h3v14H8V5Zm5 0h3v14h-3V5Z"/></svg>
					<svg class="sp-viewer__iconPlay" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.2 19 12 8 18.8V5.2Z"/></svg>
				</button>
				<button
					type="button"
					class="sp-viewer__tool"
					data-sp-viewer-close
					aria-label="<?php esc_attr_e( 'סגירה', 'storyphone-pages' ); ?>"
				>
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.3 5.7 12 12l6.3 6.3-1.4 1.4L10.6 13.4 4.3 19.7 2.9 18.3 9.2 12 2.9 5.7 4.3 4.3l6.3 6.3 6.3-6.3 1.4 1.4Z"/></svg>
				</button>
			</div>
		</header>

		<div class="sp-viewer__slide" data-sp-viewer-slide></div>

		<button
			type="button"
			class="sp-viewer__zone sp-viewer__zone--prev"
			data-sp-viewer-prev
			aria-label="<?php esc_attr_e( 'הקודם', 'storyphone-pages' ); ?>"
		></button>
		<button
			type="button"
			class="sp-viewer__zone sp-viewer__zone--next"
			data-sp-viewer-next
			aria-label="<?php esc_attr_e( 'הבא', 'storyphone-pages' ); ?>"
		></button>
	</div>
</div>
