<?php
/**
 * Part: cinematic full-bleed orbit banner (homepage).
 *
 * Design (IM) can supply mixed orbit items: image / video / product, each with
 * optional caption text. Without custom items, search-term product fallbacks run.
 *
 * @package StoryPhone_Pages
 */

defined( 'ABSPATH' ) || exit;

$sp_hues = array( 'lime', 'mint', 'amber', 'cyan', 'coral', 'lime', 'mint', 'amber' );

/**
 * @param string|string[] $terms Search terms.
 * @return array{alt:string,image:string,video:string,text:string,hue:string}|null
 */
$sp_from_search = static function ( $terms, $alt, $hue ) {
	$product = StoryPhone_Pages_Catalog::find_product_by_search( $terms );
	if ( ! $product ) {
		return array(
			'alt'   => $alt,
			'image' => '',
			'video' => '',
			'text'  => '',
			'hue'   => $hue,
		);
	}
	return array(
		'alt'   => $product->get_name(),
		'image' => StoryPhone_Pages_Catalog::get_product_image_url( $product, 'large' ),
		'video' => '',
		'text'  => '',
		'hue'   => $hue,
	);
};

/**
 * Resolve one Design cinema item into a render slot.
 *
 * @param array  $item Raw item.
 * @param string $hue  Hue token.
 * @return array{alt:string,image:string,video:string,text:string,hue:string}|null
 */
$sp_resolve_item = static function ( $item, $hue ) {
	if ( ! is_array( $item ) ) {
		return null;
	}
	$type = isset( $item['type'] ) ? (string) $item['type'] : '';
	$text = isset( $item['text'] ) ? trim( (string) $item['text'] ) : '';
	$alt  = isset( $item['label'] ) ? trim( (string) $item['label'] ) : '';
	$url  = isset( $item['url'] ) ? (string) $item['url'] : '';

	if ( 'product' === $type ) {
		$pid = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
		$product = $pid ? StoryPhone_Pages_Catalog::get_product_by_id( $pid ) : null;
		if ( ! $product ) {
			return null;
		}
		return array(
			'alt'   => $alt ? $alt : $product->get_name(),
			'image' => StoryPhone_Pages_Catalog::get_product_image_url( $product, 'large' ),
			'video' => '',
			'text'  => $text,
			'hue'   => $hue,
		);
	}

	$att_id = isset( $item['attachment_id'] ) ? absint( $item['attachment_id'] ) : 0;
	if ( 'video' === $type ) {
		$video = $att_id ? (string) wp_get_attachment_url( $att_id ) : $url;
		if ( ! $video ) {
			return null;
		}
		if ( ! $alt && $att_id ) {
			$alt = (string) get_the_title( $att_id );
		}
		return array(
			'alt'   => $alt ? $alt : __( 'Video', 'storyphone-pages' ),
			'image' => '',
			'video' => $video,
			'text'  => $text,
			'hue'   => $hue,
		);
	}

	if ( 'image' === $type ) {
		$image = '';
		if ( $att_id ) {
			$image = (string) wp_get_attachment_image_url( $att_id, 'large' );
			if ( ! $image ) {
				$image = (string) wp_get_attachment_url( $att_id );
			}
		}
		if ( ! $image ) {
			$image = $url;
		}
		if ( ! $image ) {
			return null;
		}
		if ( ! $alt && $att_id ) {
			$alt = (string) get_the_title( $att_id );
		}
		return array(
			'alt'   => $alt ? $alt : __( 'Image', 'storyphone-pages' ),
			'image' => $image,
			'video' => '',
			'text'  => $text,
			'hue'   => $hue,
		);
	}

	return null;
};

$sp_products = array();

$sp_cinema_bag = ( class_exists( 'StoryPhone_IM_Design' ) )
	? StoryPhone_IM_Design::get_section_content( 'cinema-banner' )
	: array();
$sp_is_custom = ! empty( $sp_cinema_bag['custom'] );
$sp_raw_items = ( ! empty( $sp_cinema_bag['items'] ) && is_array( $sp_cinema_bag['items'] ) )
	? $sp_cinema_bag['items']
	: array();

if ( $sp_is_custom && ! empty( $sp_raw_items ) ) {
	foreach ( $sp_raw_items as $sp_i => $sp_raw ) {
		$slot = $sp_resolve_item( $sp_raw, $sp_hues[ $sp_i % count( $sp_hues ) ] );
		if ( $slot ) {
			$sp_products[] = $slot;
		}
	}
}

if ( empty( $sp_products ) && ! $sp_is_custom ) {
	$sp_products = array(
		$sp_from_search( array( 'iPhone 17', 'iPhone 16 Pro', 'iPhone' ), 'iPhone 17 Pro', 'lime' ),
		$sp_from_search( array( 'Galaxy S25', 'Galaxy S24', 'Samsung Galaxy' ), 'Samsung Galaxy S-series', 'mint' ),
		$sp_from_search( array( 'MacBook Pro', 'MacBook Air', 'MacBook' ), 'MacBook', 'amber' ),
		$sp_from_search( array( 'Sony', 'Canon', 'Camera', 'מצלמה' ), 'mirrorless camera', 'cyan' ),
		$sp_from_search( array( 'PlayStation', 'Xbox', 'Controller', 'גיימינג' ), 'gaming controller / headset', 'coral' ),
		$sp_from_search( array( 'Apple Watch', 'Watch', 'שעון' ), 'smartwatch', 'lime' ),
		$sp_from_search( array( 'AirPods', 'Galaxy Buds', 'Earbuds' ), 'wireless earbuds', 'mint' ),
		$sp_from_search( array( 'iPhone 16', 'iPhone 15', 'iPhone' ), 'iPhone 17 series', 'amber' ),
	);
}
?>
<section
	class="sp-cinema"
	data-sp-cinema
	aria-label="<?php esc_attr_e( 'באנר קולנועי — סטוריפון', 'storyphone-pages' ); ?>"
>
	<div class="sp-cinema__letterbox sp-cinema__letterbox--top" aria-hidden="true"></div>
	<div class="sp-cinema__letterbox sp-cinema__letterbox--bottom" aria-hidden="true"></div>

	<div class="sp-cinema__world" data-sp-cinema-world aria-hidden="true">
		<div class="sp-cinema__bg">
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
					class="sp-cinema__product sp-cinema__product--<?php echo esc_attr( $sp_item['hue'] ); ?><?php echo ! empty( $sp_item['video'] ) ? ' is-video' : ''; ?>"
					data-sp-cinema-product
					data-orbit-index="<?php echo esc_attr( (string) $sp_i ); ?>"
				>
					<span class="sp-cinema__glow" aria-hidden="true"></span>
					<span class="sp-cinema__trail" data-sp-cinema-trail aria-hidden="true">
						<?php if ( ! empty( $sp_item['image'] ) ) : ?>
							<img src="<?php echo esc_url( $sp_item['image'] ); ?>" alt="" draggable="false">
						<?php endif; ?>
					</span>
					<?php if ( ! empty( $sp_item['video'] ) ) : ?>
						<video
							class="sp-cinema__cutout sp-cinema__cutout--video"
							src="<?php echo esc_url( $sp_item['video'] ); ?>"
							muted
							loop
							playsinline
							autoplay
							preload="metadata"
							aria-label="<?php echo esc_attr( $sp_item['alt'] ); ?>"
						></video>
					<?php elseif ( ! empty( $sp_item['image'] ) ) : ?>
						<img
							class="sp-cinema__cutout"
							src="<?php echo esc_url( $sp_item['image'] ); ?>"
							alt="<?php echo esc_attr( $sp_item['alt'] ); ?>"
							loading="<?php echo 0 === $sp_i ? 'eager' : 'lazy'; ?>"
							decoding="async"
							draggable="false"
						>
					<?php else : ?>
						<span class="sp-cinema__ghost" aria-label="<?php echo esc_attr( $sp_item['alt'] ); ?>"></span>
					<?php endif; ?>
					<?php if ( ! empty( $sp_item['text'] ) ) : ?>
						<figcaption class="sp-cinema__caption"><?php echo esc_html( $sp_item['text'] ); ?></figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>

		<div class="sp-cinema__people" data-sp-cinema-people>
			<div class="sp-cinema__person sp-cinema__person--a" data-sp-cinema-person>
				<span class="sp-cinema__silhouette"></span>
			</div>
			<div class="sp-cinema__person sp-cinema__person--b" data-sp-cinema-person>
				<span class="sp-cinema__silhouette"></span>
			</div>
		</div>
	</div>

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
