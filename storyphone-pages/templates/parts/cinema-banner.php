<?php
/**
 * Part: cinematic full-bleed orbit banner (homepage).
 *
 * Products = transparent cutouts only (no cards). GSAP drives true ellipse math.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string|string[] $terms Search terms.
 * @return string
 */
$sp_img = static function ( $terms ) {
	$product = StoryPhone_Pages_Catalog::find_product_by_search( $terms );
	if ( ! $product ) {
		return '';
	}
	return StoryPhone_Pages_Catalog::get_product_image_url( $product, 'large' );
};

/* Replace empty images with transparent PNG cutouts for production. */
$sp_products = array(
	array(
		'alt'   => 'iPhone 17 Pro',
		'image' => $sp_img( array( 'iPhone 17', 'iPhone 16 Pro', 'iPhone' ) ),
		'hue'   => 'lime',
	),
	array(
		'alt'   => 'Samsung Galaxy S-series',
		'image' => $sp_img( array( 'Galaxy S25', 'Galaxy S24', 'Samsung Galaxy' ) ),
		'hue'   => 'mint',
	),
	array(
		'alt'   => 'MacBook',
		'image' => $sp_img( array( 'MacBook Pro', 'MacBook Air', 'MacBook' ) ),
		'hue'   => 'amber',
	),
	array(
		'alt'   => 'mirrorless camera',
		'image' => $sp_img( array( 'Sony', 'Canon', 'Camera', 'מצלמה' ) ),
		'hue'   => 'cyan',
	),
	array(
		'alt'   => 'gaming controller / headset',
		'image' => $sp_img( array( 'PlayStation', 'Xbox', 'Controller', 'גיימינג' ) ),
		'hue'   => 'coral',
	),
	array(
		'alt'   => 'smartwatch',
		'image' => $sp_img( array( 'Apple Watch', 'Watch', 'שעון' ) ),
		'hue'   => 'lime',
	),
	array(
		'alt'   => 'wireless earbuds',
		'image' => $sp_img( array( 'AirPods', 'Galaxy Buds', 'Earbuds' ) ),
		'hue'   => 'mint',
	),
	array(
		'alt'   => 'iPhone 17 series',
		'image' => $sp_img( array( 'iPhone 16', 'iPhone 15', 'iPhone' ) ),
		'hue'   => 'amber',
	),
);
?>
<section
	class="sp-cinema"
	data-sp-cinema
	aria-label="<?php esc_attr_e( 'באנר קולנועי — סטוריפון', 'storyphone-pages' ); ?>"
>
	<div class="sp-cinema__letterbox sp-cinema__letterbox--top" aria-hidden="true"></div>
	<div class="sp-cinema__letterbox sp-cinema__letterbox--bottom" aria-hidden="true"></div>

	<!-- Dolly + grade wrap everything except text (text stays crisp). -->
	<div class="sp-cinema__world" data-sp-cinema-world aria-hidden="true">
		<div class="sp-cinema__bg">
			<!--
				Optional muted looping bokeh MP4:
				<video class="sp-cinema__video" muted loop playsinline preload="none">
					<source src="/wp-content/uploads/cinema/storyphone-bokeh.mp4" type="video/mp4">
				</video>
			-->
			<div class="sp-cinema__gradient"></div>
			<div class="sp-cinema__noise"></div>
			<div class="sp-cinema__keylight" data-sp-cinema-key></div>
			<div class="sp-cinema__filllight" data-sp-cinema-fill></div>
		</div>

		<div class="sp-cinema__particles" data-sp-cinema-particles></div>
		<div class="sp-cinema__flares" data-sp-cinema-flares>
			<span class="sp-cinema__streak" data-sp-cinema-streak></span>
			<span class="sp-cinema__streak sp-cinema__streak--b" data-sp-cinema-streak></span>
		</div>
		<div class="sp-cinema__bursts" data-sp-cinema-bursts></div>

		<div class="sp-cinema__orbit" data-sp-cinema-orbit>
			<?php foreach ( $sp_products as $sp_i => $sp_item ) : ?>
				<figure
					class="sp-cinema__product sp-cinema__product--<?php echo esc_attr( $sp_item['hue'] ); ?>"
					data-sp-cinema-product
					data-orbit-index="<?php echo esc_attr( (string) $sp_i ); ?>"
				>
					<!-- Soft colored glow ONLY — never an opaque card. -->
					<span class="sp-cinema__glow" aria-hidden="true"></span>
					<span class="sp-cinema__trail" data-sp-cinema-trail aria-hidden="true">
						<?php if ( ! empty( $sp_item['image'] ) ) : ?>
							<img src="<?php echo esc_url( $sp_item['image'] ); ?>" alt="" draggable="false">
						<?php endif; ?>
					</span>
					<?php if ( ! empty( $sp_item['image'] ) ) : ?>
						<img
							class="sp-cinema__cutout"
							src="<?php echo esc_url( $sp_item['image'] ); ?>"
							alt="<?php echo esc_attr( $sp_item['alt'] ); ?>"
							loading="<?php echo 0 === $sp_i ? 'eager' : 'lazy'; ?>"
							decoding="async"
							draggable="false"
						>
					<?php else : ?>
						<!-- No gray box: glow-only stand-in until PNG cutout is uploaded. -->
						<span class="sp-cinema__ghost" aria-label="<?php echo esc_attr( $sp_item['alt'] ); ?>"></span>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>

		<!--
			Replace silhouettes with real lifestyle PNG cutouts for production.
		-->
		<div class="sp-cinema__people" data-sp-cinema-people>
			<div class="sp-cinema__person sp-cinema__person--a" data-sp-cinema-person>
				<span class="sp-cinema__silhouette"></span>
			</div>
			<div class="sp-cinema__person sp-cinema__person--b" data-sp-cinema-person>
				<span class="sp-cinema__silhouette"></span>
			</div>
		</div>
	</div>

	<!-- Text safe zone — always above orbit (z-index 100). -->
	<div class="sp-cinema__copy" data-sp-cinema-copy>
		<h2 class="sp-cinema__headline" data-sp-cinema-headline>
			<span class="sp-cinema__word"><?php esc_html_e( 'כל', 'storyphone-pages' ); ?></span>
			<span class="sp-cinema__word"><?php esc_html_e( 'מה', 'storyphone-pages' ); ?></span>
			<span class="sp-cinema__word"><?php esc_html_e( 'שאתה', 'storyphone-pages' ); ?></span>
			<span class="sp-cinema__word"><?php esc_html_e( 'צריך.', 'storyphone-pages' ); ?></span>
			<span class="sp-cinema__word sp-cinema__word--accent"><?php esc_html_e( 'במקום', 'storyphone-pages' ); ?></span>
			<span class="sp-cinema__word sp-cinema__word--accent"><?php esc_html_e( 'אחד.', 'storyphone-pages' ); ?></span>
		</h2>
		<p class="sp-cinema__sub" data-sp-cinema-sub>
			<?php esc_html_e( 'iPhone 17 · Samsung · MacBook · מצלמות · גיימינג', 'storyphone-pages' ); ?>
		</p>
		<a class="sp-cinema__cta" data-sp-cinema-cta href="#sp-hero">
			<?php esc_html_e( 'המשך', 'storyphone-pages' ); ?>
		</a>
		<p class="sp-cinema__trust" data-sp-cinema-trust>
			<?php esc_html_e( 'מיקום מרכזי בתל אביב · אחריות מלאה · משלוח מהיר', 'storyphone-pages' ); ?>
		</p>
	</div>
</section>
