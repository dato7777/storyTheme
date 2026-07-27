<?php
/**
 * Part: command palette search.
 *
 * Results come live from the WooCommerce Store API, so what a shopper sees is
 * exactly what WooCommerce would return — including its own visibility rules.
 * The form also degrades to a normal GET search if JavaScript is unavailable.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

$sp_suggestions = StoryPhone_Pages_Catalog::get_categories( 6 );
?>
<div class="sp-palette" data-sp-palette hidden>
	<div class="sp-palette__scrim" data-sp-palette-close></div>

	<div
		class="sp-palette__panel"
		role="dialog"
		aria-modal="true"
		aria-label="<?php esc_attr_e( 'חיפוש בחנות', 'storyphone-pages' ); ?>"
	>
		<form class="sp-palette__form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<svg class="sp-palette__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M10.5 3a7.5 7.5 0 1 0 4.55 13.46l4.24 4.25 1.42-1.42-4.25-4.24A7.5 7.5 0 0 0 10.5 3Zm0 2a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z"/>
			</svg>

			<input
				type="search"
				class="sp-palette__input"
				name="s"
				data-sp-palette-input
				placeholder="<?php esc_attr_e( 'חפשו iPhone, Galaxy, אוזניות, מטען…', 'storyphone-pages' ); ?>"
				autocomplete="off"
				autocorrect="off"
				spellcheck="false"
				aria-label="<?php esc_attr_e( 'מה אתם מחפשים?', 'storyphone-pages' ); ?>"
				aria-controls="sp-palette-results"
			>
			<input type="hidden" name="post_type" value="product">

			<span class="sp-palette__spinner" data-sp-palette-spinner hidden aria-hidden="true"></span>

			<button
				type="button"
				class="sp-palette__esc"
				data-sp-palette-close
				aria-label="<?php esc_attr_e( 'סגירת החיפוש', 'storyphone-pages' ); ?>"
			>
				<kbd>Esc</kbd>
			</button>
		</form>

		<div class="sp-palette__body" id="sp-palette-results" data-sp-palette-results role="listbox" aria-label="<?php esc_attr_e( 'תוצאות חיפוש', 'storyphone-pages' ); ?>">
			<div class="sp-palette__intro" data-sp-palette-intro>
				<?php if ( ! empty( $sp_suggestions ) ) : ?>
					<p class="sp-palette__label"><?php esc_html_e( 'קפיצה מהירה', 'storyphone-pages' ); ?></p>
					<div class="sp-palette__chips">
						<?php foreach ( $sp_suggestions as $sp_term ) : ?>
							<?php
							$sp_term_link = get_term_link( $sp_term );
							if ( is_wp_error( $sp_term_link ) ) {
								continue;
							}
							?>
							<a class="sp-chip" href="<?php echo esc_url( $sp_term_link ); ?>">
								<?php echo esc_html( $sp_term->name ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="sp-palette__recent" data-sp-palette-recent hidden>
					<p class="sp-palette__label"><?php esc_html_e( 'חיפושים אחרונים', 'storyphone-pages' ); ?></p>
					<div class="sp-palette__chips" data-sp-palette-recent-list></div>
				</div>
			</div>
		</div>

		<footer class="sp-palette__foot" aria-hidden="true">
			<span><kbd>&#8593;</kbd><kbd>&#8595;</kbd> <?php esc_html_e( 'ניווט', 'storyphone-pages' ); ?></span>
			<span><kbd>&#8629;</kbd> <?php esc_html_e( 'פתיחה', 'storyphone-pages' ); ?></span>
			<span><kbd>Esc</kbd> <?php esc_html_e( 'סגירה', 'storyphone-pages' ); ?></span>
		</footer>
	</div>
</div>
