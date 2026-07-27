<?php
/**
 * Part: optional page content from the WordPress editor.
 *
 * Keeps the page editable: anything typed into the Page editor still renders,
 * styled by our own prose rules.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

if ( ! have_posts() ) {
	return;
}

while ( have_posts() ) :
	the_post();

	if ( '' === trim( (string) get_the_content() ) ) {
		continue;
	}
	?>
	<section class="sp-section">
		<div class="sp-shell sp-shell--narrow sp-prose" data-sp-reveal>
			<?php the_content(); ?>
		</div>
	</section>
	<?php
endwhile;

rewind_posts();
