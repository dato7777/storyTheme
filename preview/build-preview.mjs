/**
 * Build a static preview of the homepage using real catalog data.
 *
 * This mirrors the PHP templates closely enough to catch layout, RTL and
 * animation problems without needing a WordPress install. It is a development
 * aid only and ships in no release.
 *
 * Usage: node preview/build-preview.mjs
 */

import { writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const API = 'https://storyphone.co.il/wp-json/wc/store/v1';
const out = fileURLToPath(new URL('./index.html', import.meta.url));

const FIELDS = 'id,name,permalink,prices,images,is_in_stock,on_sale,type';

/**
 * Fetch JSON from the Store API.
 *
 * @param {string} path Route with query string.
 * @return {Promise<any>} Parsed payload.
 */
async function api(path) {
	const response = await fetch(`${API}${path}`, {
		headers: { Accept: 'application/json', 'User-Agent': 'Mozilla/5.0' },
	});

	if (!response.ok) {
		throw new Error(`${path} -> ${response.status}`);
	}

	return response.json();
}

const esc = (value) =>
	String(value ?? '')
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');

/**
 * Format a minor-unit amount the way WooCommerce would.
 *
 * @param {string|number} amount Minor units.
 * @param {Object}        prices Store API prices object.
 * @return {string} Display string.
 */
function money(amount, prices = {}) {
	const minor = Number(prices.currency_minor_unit ?? 2);
	const value = Number(amount) / 10 ** minor;
	const [whole, fraction = ''] = value.toFixed(minor).split('.');
	const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, prices.currency_thousand_separator ?? ',');
	const body = minor > 0 ? `${grouped}${prices.currency_decimal_separator ?? '.'}${fraction}` : grouped;

	return `${prices.currency_prefix ?? ''}${body}${prices.currency_suffix ?? ''}`;
}

/**
 * Price markup matching WooCommerce's get_price_html() shape.
 *
 * @param {Object} product Store API product.
 * @return {string} HTML.
 */
function priceHtml(product) {
	const p = product.prices || {};
	const now = money(p.price, p);

	if (product.on_sale && p.regular_price && p.regular_price !== p.price) {
		return `<del>${money(p.regular_price, p)}</del> <ins>${now}</ins>`;
	}

	return `<span>${now}</span>`;
}

/**
 * Discount percentage, or 0.
 *
 * @param {Object} product Store API product.
 * @return {number} Percentage.
 */
function discount(product) {
	const p = product.prices || {};
	const regular = Number(p.regular_price);
	const sale = Number(p.price);

	if (!product.on_sale || !regular || !sale || sale >= regular) {
		return 0;
	}

	return Math.round((1 - sale / regular) * 100);
}

const img = (product) => (product.images && product.images[0] ? product.images[0].src : '');
const thumb = (product) => (product.images && product.images[0] ? product.images[0].thumbnail : '');

/* ---------- data ---------- */

console.log('Fetching catalog…');

const categories = (
	await api('/products/categories?per_page=14&orderby=count&order=desc&_fields=id,name,slug,count,image,permalink')
)
	.filter((term) => term.count > 0)
	.slice(0, 10);

const stories = [];
for (const term of categories) {
	const items = await api(`/products?category=${term.id}&per_page=6&_fields=${FIELDS}`);
	if (items.length === 0) {
		continue;
	}

	stories.push({
		id: `cat-${term.id}`,
		name: term.name,
		url: term.permalink || '#',
		cover: term.image ? term.image.thumbnail : thumb(items[0]),
		count: term.count,
		items: items.map((product) => ({
			id: product.id,
			name: product.name,
			url: product.permalink,
			image: img(product),
			priceHtml: priceHtml(product),
			canAdd: product.type === 'simple' && product.is_in_stock,
			inStock: product.is_in_stock,
			discount: discount(product),
		})),
	});
}

const hot = await api(`/products?per_page=6&orderby=popularity&_fields=${FIELDS}`);
const showcase = await api(`/products?per_page=8&orderby=date&_fields=${FIELDS}`);
const onSale = await api(`/products?per_page=20&on_sale=true&_fields=${FIELDS}`);

const deal = onSale.map((p) => ({ p, d: discount(p) })).sort((a, b) => b.d - a.d)[0]?.p || onSale[0];

console.log(`Stories: ${stories.length}, hot: ${hot.length}, showcase: ${showcase.length}`);

/* ---------- markup ---------- */

const productCard = (product) => `
<article class="sp-card" data-sp-reveal data-product-id="${product.id}">
	<a class="sp-card__media" href="${esc(product.permalink)}" tabindex="-1" aria-hidden="true">
		<img class="sp-card__img" src="${esc(img(product))}" alt="" loading="lazy" decoding="async">
		${
			!product.is_in_stock
				? '<span class="sp-badge sp-badge--muted">אזל מהמלאי</span>'
				: discount(product) > 0
					? `<span class="sp-badge sp-badge--sale">${discount(product)}%- הנחה</span>`
					: ''
		}
	</a>
	<div class="sp-card__body">
		<h3 class="sp-card__title"><a href="${esc(product.permalink)}">${esc(product.name)}</a></h3>
		<div class="sp-card__price">${priceHtml(product)}</div>
		${
			product.type === 'simple' && product.is_in_stock
				? `<button type="button" class="sp-btn sp-btn--add" data-sp-add-to-cart data-product-id="${product.id}"><span class="sp-btn__label">הוספה לסל</span></button>`
				: `<a class="sp-btn sp-btn--ghost" href="${esc(product.permalink)}">לצפייה במוצר</a>`
		}
	</div>
</article>`;

const heatMap = (() => {
	const sales = hot.map((_, index) => 100 - index * 13);
	return sales;
})();

const bubble = (story, index) => `
<li class="sp-rail__item">
	<a class="sp-bubble" href="${esc(story.url)}" data-sp-story-open data-story-index="${index}" data-story-id="${esc(story.id)}">
		<span class="sp-bubble__ring">
			<span class="sp-bubble__inner">
				${story.cover ? `<img class="sp-bubble__img" src="${esc(story.cover)}" alt="" loading="lazy">` : `<span class="sp-bubble__fallback">${esc(story.name.charAt(0))}</span>`}
			</span>
			<span class="sp-bubble__play"></span>
		</span>
		<span class="sp-bubble__name">${esc(story.name)}</span>
		<span class="sp-bubble__count">${story.count} פריטים</span>
	</a>
</li>`;

const tile = (term, index) => {
	// Mirrors Catalog::get_category_cover(): thumbnail, else first product.
	const cover = term.image ? term.image.thumbnail : stories.find((s) => s.id === `cat-${term.id}`)?.cover || '';

	return `
<a class="sp-tile" href="${esc(term.permalink || '#')}" data-sp-reveal style="--sp-tile-hue: ${(index * 47) % 360}">
	<span class="sp-tile__glow"></span>
	<span class="sp-tile__media${cover ? '' : ' sp-tile__media--empty'}">${cover ? `<img class="sp-tile__img" src="${esc(cover)}" alt="" loading="lazy">` : `<span class="sp-tile__mono">${esc(term.name.charAt(0))}</span>`}</span>
	<span class="sp-tile__body">
		<span class="sp-tile__name">${esc(term.name)}</span>
		<span class="sp-tile__count">${term.count} פריטים</span>
	</span>
	<span class="sp-tile__arrow">&#8592;</span>
</a>`;
};

const heatRow = (product, index) => `
<li class="sp-heat__row" data-sp-reveal>
	<span class="sp-heat__rank">${String(index + 1).padStart(2, '0')}</span>
	<a class="sp-heat__media" href="${esc(product.permalink)}" tabindex="-1" aria-hidden="true">
		<img class="sp-heat__img" src="${esc(thumb(product))}" alt="" loading="lazy">
	</a>
	<div class="sp-heat__body">
		<h3 class="sp-heat__name"><a href="${esc(product.permalink)}">${esc(product.name)}</a></h3>
		<div class="sp-heat__bar"><span class="sp-heat__fill" style="--sp-heat: ${heatMap[index]}%"></span></div>
	</div>
	<div class="sp-heat__side">
		<div class="sp-heat__price">${priceHtml(product)}</div>
		${
			product.type === 'simple' && product.is_in_stock
				? `<button type="button" class="sp-btn sp-btn--add sp-btn--sm" data-sp-add-to-cart data-product-id="${product.id}"><span class="sp-btn__label">הוספה</span></button>`
				: `<a class="sp-btn sp-btn--ghost sp-btn--sm" href="${esc(product.permalink)}">לפרטים</a>`
		}
	</div>
</li>`;

const trustItems = [
	['משלוח מהיר', 'עד הבית, לכל הארץ', 'M3 7h11v8H3V7Zm11 3h3.2l2.8 3v2h-6v-5ZM6.5 20a1.8 1.8 0 1 0 0-3.6 1.8 1.8 0 0 0 0 3.6Zm10 0a1.8 1.8 0 1 0 0-3.6 1.8 1.8 0 0 0 0 3.6Z'],
	['אחריות רשמית', 'על כל המוצרים בחנות', 'M12 2 4 5.2v6.3c0 4.5 3.2 8.6 8 10.5 4.8-1.9 8-6 8-10.5V5.2L12 2Zm3.9 7.6-4.6 4.6a1 1 0 0 1-1.4 0L8 12.3l1.4-1.4 1.2 1.2 3.9-3.9 1.4 1.4Z'],
	['החזרה תוך 14 יום', 'בלי שאלות מיותרות', 'M12 4V1L7 5l5 4V6a6 6 0 1 1-6 6H4a8 8 0 1 0 8-8Z'],
	['תשלום מאובטח', 'בכל כרטיסי האשראי', 'M4 6h16a1 1 0 0 1 1 1v2H3V7a1 1 0 0 1 1-1Zm-1 5h18v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-6Zm3 3v2h5v-2H6Z'],
	['יבוא רשמי', 'בלי הפתעות, בלי אפור', 'M12 2 3 6v6c0 5 3.8 9.4 9 10 5.2-.6 9-5 9-10V6l-9-4Zm0 5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5Zm0 11c-2 0-3.8-1-4.9-2.5.1-1.6 3.3-2.5 4.9-2.5s4.8.9 4.9 2.5A5.9 5.9 0 0 1 12 18Z'],
	['תמיכה אנושית', 'מדברים איתכם, לא בוט', 'M12 3a9 9 0 0 0-9 9v4.5A2.5 2.5 0 0 0 5.5 19H7a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a7 7 0 0 1 14 0h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.2a2.5 2.5 0 0 1-2.2 1.3H13v1.7h3a4.2 4.2 0 0 0 4-3.1A2.5 2.5 0 0 0 21 16.5V12a9 9 0 0 0-9-9Z'],
];

const marqueeRun = (hidden) => `
<ul class="sp-marquee__run"${hidden ? ' aria-hidden="true"' : ''}>
	${trustItems
		.map(
			([title, text, path]) => `
	<li class="sp-marquee__item">
		<span class="sp-marquee__icon"><svg viewBox="0 0 24 24"><path d="${path}"/></svg></span>
		<span class="sp-marquee__body">
			<strong class="sp-marquee__title">${title}</strong>
			<span class="sp-marquee__text">${text}</span>
		</span>
	</li>`
		)
		.join('')}
</ul>`;

const heroPick = hot[0];
// Mirrors hero.php: the deck must never rotate a card onto itself.
const heroDeal = deal && heroPick && deal.id === heroPick.id ? null : deal;
const dealDeadline = new Date();
dealDeadline.setHours(24, 0, 0, 0);

const searchIcon = `<path d="M10.5 3a7.5 7.5 0 1 0 4.55 13.46l4.24 4.25 1.42-1.42-4.25-4.24A7.5 7.5 0 0 0 10.5 3Zm0 2a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z"/>`;

const html = `<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#07091a">
<title>StoryPhone — תצוגה מקדימה</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Assistant:wght@400;500;600;700&family=Heebo:wght@700;800;900&display=swap">
<link rel="stylesheet" href="../storyphone-pages/build/main.css">
</head>
<body class="sp-page sp-page--home">

<a class="sp-skip" href="#sp-main">דלג לתוכן הראשי</a>

<header class="sp-header" data-sp-header>
	<div class="sp-header__inner sp-shell">
		<button type="button" class="sp-header__burger" data-sp-nav-toggle aria-expanded="false" aria-controls="sp-nav" aria-label="פתיחת תפריט">
			<span class="sp-burger__bar"></span><span class="sp-burger__bar"></span><span class="sp-burger__bar"></span>
		</button>
		<div class="sp-header__brand">
			<a class="sp-brand" href="#"><span class="sp-brand__mark"><span class="sp-brand__ring"></span></span><span class="sp-brand__text">Story Phone</span></a>
		</div>
		<nav id="sp-nav" class="sp-nav" aria-label="תפריט ראשי">
			<ul class="sp-nav__list">
				${categories.slice(0, 5).map((term) => `<li class="menu-item"><a href="${esc(term.permalink || '#')}">${esc(term.name)}</a></li>`).join('')}
			</ul>
		</nav>
		<button type="button" class="sp-searchpill" data-sp-search-open>
			<svg class="sp-searchpill__icon" viewBox="0 0 24 24">${searchIcon}</svg>
			<span class="sp-searchpill__label">חיפוש מכשיר, מותג או דגם…</span>
			<kbd class="sp-searchpill__kbd">/</kbd>
		</button>
		<div class="sp-header__actions">
			<button type="button" class="sp-iconbtn sp-iconbtn--search" data-sp-search-open aria-label="חיפוש"><svg viewBox="0 0 24 24">${searchIcon}</svg></button>
			<a class="sp-iconbtn sp-iconbtn--account" href="#" aria-label="האזור האישי"><svg viewBox="0 0 24 24"><path d="M12 12.8a4.4 4.4 0 1 0 0-8.8 4.4 4.4 0 0 0 0 8.8Zm0 2c-4 0-7.2 2.3-7.2 5.2 0 .6.4 1 1 1h12.4c.6 0 1-.4 1-1 0-2.9-3.2-5.2-7.2-5.2Z"/></svg></a>
			<button type="button" class="sp-iconbtn sp-iconbtn--cart" data-sp-cart-toggle aria-label="פתיחת סל הקניות">
				<svg viewBox="0 0 24 24"><path d="M7 8V6.8a5 5 0 0 1 10 0V8h2.1a1 1 0 0 1 1 1.1l-1 10.3a2 2 0 0 1-2 1.8H6.9a2 2 0 0 1-2-1.8l-1-10.3A1 1 0 0 1 4.9 8H7Zm2 0h6V6.8a3 3 0 0 0-6 0V8Z"/></svg>
				<span class="sp-cartcount" data-sp-cart-count hidden>0</span>
			</button>
			<a class="sp-btn sp-btn--primary sp-header__shop" href="#">לחנות</a>
		</div>
	</div>
</header>

<main id="sp-main" class="sp-main">

<section class="sp-hero">
	<div class="sp-aurora"><span class="sp-aurora__blob sp-aurora__blob--1"></span><span class="sp-aurora__blob sp-aurora__blob--2"></span><span class="sp-aurora__blob sp-aurora__blob--3"></span></div>
	<div class="sp-hero__grid-lines"></div>
	<div class="sp-noise"></div>
	<div class="sp-shell sp-hero__inner">
		<div class="sp-hero__copy">
			<p class="sp-eyebrow" data-sp-reveal><span class="sp-eyebrow__dot"></span>יבוא רשמי · אחריות מלאה · משלוח מהיר לכל הארץ</p>
			<h1 class="sp-hero__title" data-sp-reveal>
				<span class="sp-hero__line">לכל מכשיר</span>
				<span class="sp-hero__line sp-hero__line--accent">יש סיפור.</span>
				<span class="sp-hero__line sp-hero__line--sub">בואו נמצא את שלכם.</span>
			</h1>
			<p class="sp-hero__lede" data-sp-reveal>אלפי מכשירים ואביזרים מקוריים. תגידו מה אתם מחפשים — ואנחנו נביא אתכם לזה בשלוש שניות.</p>
			<button type="button" class="sp-heroSearch" data-sp-search-open data-sp-reveal>
				<svg class="sp-heroSearch__icon" viewBox="0 0 24 24">${searchIcon}</svg>
				<span class="sp-heroSearch__text"><span class="sp-heroSearch__static">חיפוש</span><span class="sp-heroSearch__typer" data-sp-typer></span></span>
				<span class="sp-heroSearch__go">התחילו</span>
			</button>
			<div class="sp-hero__chips" data-sp-reveal>
				<span class="sp-hero__chipsLabel">פופולרי:</span>
				${categories.slice(0, 5).map((term) => `<a class="sp-chip" href="${esc(term.permalink || '#')}">${esc(term.name)}</a>`).join('')}
			</div>
		</div>
		<aside class="sp-hero__pick" data-sp-reveal aria-label="מומלצים">
			<div class="sp-pickDeck" data-sp-deck data-sp-tilt>
				<div class="sp-pickDeck__stack">
					<article class="sp-pick sp-pick--hot is-active" data-sp-deck-face>
						<div class="sp-pick__glow"></div>
						<p class="sp-pick__kicker"><span class="sp-pick__flame">&#128293;</span>הנמכר ביותר</p>
						<a class="sp-pick__media" href="${esc(heroPick.permalink)}">
							<img class="sp-pick__img" src="${esc(img(heroPick))}" alt="">
							${discount(heroPick) > 0 ? `<span class="sp-badge sp-badge--sale">${discount(heroPick)}%- הנחה</span>` : ''}
						</a>
						<h2 class="sp-pick__title"><a href="${esc(heroPick.permalink)}">${esc(heroPick.name)}</a></h2>
						<div class="sp-pick__price">${priceHtml(heroPick)}</div>
						<button type="button" class="sp-btn sp-btn--primary sp-btn--block" data-sp-add-to-cart data-product-id="${heroPick.id}"><span class="sp-btn__label">הוספה לסל</span></button>
					</article>
					${heroDeal ? `<article class="sp-pick sp-pick--deal" data-sp-deck-face>
						<div class="sp-pick__glow"></div>
						<p class="sp-pick__kicker sp-pick__kicker--deal"><span class="sp-pick__bolt">&#9889;</span>הדיל של היום</p>
						<a class="sp-pick__media" href="${esc(heroDeal.permalink)}">
							<img class="sp-pick__img" src="${esc(img(heroDeal))}" alt="">
							${discount(heroDeal) > 0 ? `<span class="sp-badge sp-badge--sale">${discount(heroDeal)}%- הנחה</span>` : ''}
						</a>
						<h2 class="sp-pick__title"><a href="${esc(heroDeal.permalink)}">${esc(heroDeal.name)}</a></h2>
						<div class="sp-pick__price">${priceHtml(heroDeal)}</div>
						<div class="sp-pick__timer" data-sp-countdown="${dealDeadline.toISOString()}" aria-label="הזמן שנותר למבצע">
							<span class="sp-pick__timerIcon"></span>
							<b data-sp-cd-h>--</b><i>:</i><b data-sp-cd-m>--</b><i>:</i><b data-sp-cd-s>--</b>
							<span class="sp-pick__timerNote">עד סוף המבצע</span>
						</div>
						<button type="button" class="sp-btn sp-btn--primary sp-btn--block" data-sp-add-to-cart data-product-id="${heroDeal.id}"><span class="sp-btn__label">תפסו את המבצע</span></button>
					</article>` : ''}
				</div>
				${heroDeal ? `<div class="sp-pickDeck__pips">
					<button type="button" class="sp-pickDeck__pip is-on" data-sp-deck-pip data-index="0" aria-pressed="true"><span class="sp-pickDeck__pipFill"></span><span class="sp-srOnly">הנמכר ביותר</span></button>
					<button type="button" class="sp-pickDeck__pip" data-sp-deck-pip data-index="1" aria-pressed="false"><span class="sp-pickDeck__pipFill"></span><span class="sp-srOnly">הדיל של היום</span></button>
				</div>` : ''}
			</div>
		</aside>
	</div>
	<a class="sp-hero__scroll" href="#sp-stories" aria-label="גלילה לסטוריז"><span class="sp-hero__scrollDot"></span></a>
</section>

<section class="sp-stories" id="sp-stories" aria-labelledby="sp-stories-title">
	<div class="sp-shell">
		<header class="sp-stories__head" data-sp-reveal>
			<div>
				<h2 class="sp-section__title" id="sp-stories-title">הסטוריז של החנות</h2>
				<p class="sp-section__subtitle">הקטגוריות שלנו, בלי תפריטים. לחצו על עיגול וצפו במה שיש בפנים.</p>
			</div>
			<p class="sp-stories__hint">החליקו לצדדים</p>
		</header>
		<div class="sp-rail" data-sp-rail>
			<ul class="sp-rail__track" data-sp-rail-track>${stories.map(bubble).join('')}</ul>
			<button type="button" class="sp-rail__nav sp-rail__nav--prev" data-sp-rail-prev aria-label="הקודם"><svg viewBox="0 0 24 24"><path d="M9.3 12 15 6.3 13.6 4.9 6.5 12l7.1 7.1L15 17.7 9.3 12Z"/></svg></button>
			<button type="button" class="sp-rail__nav sp-rail__nav--next" data-sp-rail-next aria-label="הבא"><svg viewBox="0 0 24 24"><path d="M14.7 12 9 17.7l1.4 1.4 7.1-7.1-7.1-7.1L9 6.3 14.7 12Z"/></svg></button>
		</div>
	</div>
</section>

<section class="sp-section sp-reach" id="sp-reach">
	<div class="sp-shell">
		<header class="sp-section__head" data-sp-reveal>
			<div>
				<h2 class="sp-section__title">מגיעים ישר לעניין</h2>
				<p class="sp-section__subtitle">יודעים מה אתם רוצים? קפצו ישירות לקטגוריה</p>
			</div>
			<a class="sp-textlink" href="#">כל הקטגוריות <span>&#8592;</span></a>
		</header>
		<div class="sp-reach__grid">${categories.slice(0, 8).map(tile).join('')}</div>
	</div>
</section>

<section class="sp-heat" id="sp-heat">
	<div class="sp-aurora sp-aurora--soft"><span class="sp-aurora__blob sp-aurora__blob--2"></span></div>
	<div class="sp-shell">
		<header class="sp-section__head" data-sp-reveal>
			<div>
				<h2 class="sp-section__title"><span class="sp-heat__flame">&#128293;</span>הכי חם עכשיו</h2>
				<p class="sp-section__subtitle">מה שהלקוחות שלנו קונים הכי הרבה — מתעדכן לפי מכירות אמיתיות</p>
			</div>
			<p class="sp-heat__live"><span class="sp-heat__pulse"></span>לפי נתוני מכירות</p>
		</header>
		<ol class="sp-heat__list">${hot.map(heatRow).join('')}</ol>
	</div>
</section>

<section class="sp-section" id="sp-showcase">
	<div class="sp-shell">
		<header class="sp-section__head" data-sp-reveal>
			<div>
				<h2 class="sp-section__title">נבחרת הבית</h2>
				<p class="sp-section__subtitle">המכשירים והאביזרים שאנחנו עומדים מאחוריהם</p>
			</div>
			<a class="sp-textlink" href="#">לכל המוצרים <span>&#8592;</span></a>
		</header>
		<div class="sp-grid sp-grid--products">${showcase.map(productCard).join('')}</div>
	</div>
</section>

${
	deal
		? `<section class="sp-deal" id="sp-deal">
	<div class="sp-shell">
		<div class="sp-deal__panel" data-sp-reveal>
			<div class="sp-aurora sp-aurora--soft"><span class="sp-aurora__blob sp-aurora__blob--3"></span></div>
			<div class="sp-deal__copy">
				<p class="sp-deal__kicker"><span class="sp-deal__bolt">&#9889;</span>הדיל של היום</p>
				<h2 class="sp-deal__title"><a href="${esc(deal.permalink)}">${esc(deal.name)}</a></h2>
				<div class="sp-deal__price">${priceHtml(deal)}</div>
				<div class="sp-countdown" data-sp-countdown="${dealDeadline.toISOString()}">
					<span class="sp-countdown__cell"><b data-sp-cd-h>--</b><i>שעות</i></span>
					<span class="sp-countdown__sep">:</span>
					<span class="sp-countdown__cell"><b data-sp-cd-m>--</b><i>דקות</i></span>
					<span class="sp-countdown__sep">:</span>
					<span class="sp-countdown__cell"><b data-sp-cd-s>--</b><i>שניות</i></span>
				</div>
				<div class="sp-deal__actions">
					<button type="button" class="sp-btn sp-btn--primary sp-btn--lg" data-sp-add-to-cart data-product-id="${deal.id}"><span class="sp-btn__label">תפסו את המבצע</span></button>
					<a class="sp-btn sp-btn--quiet sp-btn--lg" href="${esc(deal.permalink)}">לפרטים המלאים</a>
				</div>
			</div>
			<a class="sp-deal__media" href="${esc(deal.permalink)}" data-sp-tilt>
				<img class="sp-deal__img" src="${esc(img(deal))}" alt="">
				${discount(deal) > 0 ? `<span class="sp-deal__save"><b>${discount(deal)}%</b><i>הנחה</i></span>` : ''}
			</a>
		</div>
	</div>
</section>`
		: ''
}

<section class="sp-marquee" aria-label="למה לקנות אצלנו">
	<div class="sp-marquee__viewport"><div class="sp-marquee__track">${marqueeRun(false)}${marqueeRun(true)}</div></div>
</section>

<section class="sp-cta">
	<div class="sp-shell">
		<div class="sp-cta__panel" data-sp-reveal>
			<div class="sp-cta__copy">
				<h2 class="sp-cta__title">לא בטוחים מה מתאים לכם?</h2>
				<p class="sp-cta__text">ספרו לנו מה אתם מחפשים ואיזה תקציב יש לכם, ונמצא לכם את המכשיר הנכון. בלי לחץ ובלי שטויות.</p>
			</div>
			<div class="sp-cta__actions"><a class="sp-btn sp-btn--primary sp-btn--lg" href="#">להתחיל לבחור</a></div>
		</div>
	</div>
</section>

</main>

<footer class="sp-footer">
	<div class="sp-shell">
		<div class="sp-footer__top">
			<div>
				<p class="sp-footer__logo">Story Phone</p>
				<p class="sp-footer__blurb">חנות סלולר ואביזרים עם יבוא רשמי, אחריות מלאה ומשלוח מהיר לכל הארץ.</p>
			</div>
			<div>
				<h2 class="sp-footer__heading">קטגוריות</h2>
				<ul class="sp-footer__list">${categories.slice(0, 5).map((t) => `<li><a href="${esc(t.permalink || '#')}">${esc(t.name)}</a></li>`).join('')}</ul>
			</div>
			<div>
				<h2 class="sp-footer__heading">שירות</h2>
				<ul class="sp-footer__list"><li><a href="#">צור קשר</a></li><li><a href="#">מדיניות החזרות</a></li><li><a href="#">תקנון</a></li><li><a href="#">משלוחים</a></li></ul>
			</div>
		</div>
		<div class="sp-footer__bottom"><span>© ${new Date().getFullYear()} Story Phone</span><span>תצוגה מקדימה מקומית</span></div>
	</div>
</footer>

<div class="sp-viewer" data-sp-viewer hidden>
	<div class="sp-viewer__scrim" data-sp-viewer-close></div>
	<div class="sp-viewer__stage" role="dialog" aria-modal="true" aria-label="סטורי קטגוריה">
		<div class="sp-viewer__bars" data-sp-viewer-bars></div>
		<header class="sp-viewer__head">
			<div class="sp-viewer__id">
				<span class="sp-viewer__avatar" data-sp-viewer-avatar></span>
				<span class="sp-viewer__meta"><span class="sp-viewer__cat" data-sp-viewer-category></span><span class="sp-viewer__pos" data-sp-viewer-position></span></span>
			</div>
			<div class="sp-viewer__tools">
				<button type="button" class="sp-viewer__tool" data-sp-viewer-pause aria-label="השהיה">
					<svg class="sp-viewer__iconPause" viewBox="0 0 24 24"><path d="M8 5h3v14H8V5Zm5 0h3v14h-3V5Z"/></svg>
					<svg class="sp-viewer__iconPlay" viewBox="0 0 24 24"><path d="M8 5.2 19 12 8 18.8V5.2Z"/></svg>
				</button>
				<button type="button" class="sp-viewer__tool" data-sp-viewer-close aria-label="סגירה"><svg viewBox="0 0 24 24"><path d="M18.3 5.7 12 12l6.3 6.3-1.4 1.4L10.6 13.4 4.3 19.7 2.9 18.3 9.2 12 2.9 5.7 4.3 4.3l6.3 6.3 6.3-6.3 1.4 1.4Z"/></svg></button>
			</div>
		</header>
		<div class="sp-viewer__slide" data-sp-viewer-slide></div>
		<button type="button" class="sp-viewer__zone sp-viewer__zone--prev" data-sp-viewer-prev aria-label="הקודם"></button>
		<button type="button" class="sp-viewer__zone sp-viewer__zone--next" data-sp-viewer-next aria-label="הבא"></button>
	</div>
</div>

<div class="sp-palette" data-sp-palette hidden>
	<div class="sp-palette__scrim" data-sp-palette-close></div>
	<div class="sp-palette__panel" role="dialog" aria-modal="true" aria-label="חיפוש בחנות">
		<form class="sp-palette__form" role="search" method="get" action="#">
			<svg class="sp-palette__icon" viewBox="0 0 24 24">${searchIcon}</svg>
			<input type="search" class="sp-palette__input" name="s" data-sp-palette-input placeholder="חפשו iPhone, Galaxy, אוזניות, מטען…" autocomplete="off" aria-label="מה אתם מחפשים?" aria-controls="sp-palette-results">
			<span class="sp-palette__spinner" data-sp-palette-spinner hidden></span>
			<button type="button" class="sp-palette__esc" data-sp-palette-close aria-label="סגירת החיפוש"><kbd>Esc</kbd></button>
		</form>
		<div class="sp-palette__body" id="sp-palette-results" data-sp-palette-results role="listbox" aria-label="תוצאות חיפוש">
			<div class="sp-palette__intro" data-sp-palette-intro>
				<p class="sp-palette__label">קפיצה מהירה</p>
				<div class="sp-palette__chips">${categories.slice(0, 6).map((t) => `<a class="sp-chip" href="${esc(t.permalink || '#')}">${esc(t.name)}</a>`).join('')}</div>
				<div class="sp-palette__recent" data-sp-palette-recent hidden>
					<p class="sp-palette__label">חיפושים אחרונים</p>
					<div class="sp-palette__chips" data-sp-palette-recent-list></div>
				</div>
			</div>
		</div>
		<footer class="sp-palette__foot">
			<span><kbd>&#8593;</kbd><kbd>&#8595;</kbd> ניווט</span><span><kbd>&#8629;</kbd> פתיחה</span><span><kbd>Esc</kbd> סגירה</span>
		</footer>
	</div>
</div>

<div class="sp-drawer" data-sp-drawer hidden>
	<div class="sp-drawer__scrim" data-sp-drawer-close></div>
	<aside class="sp-drawer__panel" role="dialog" aria-modal="true" aria-label="סל הקניות">
		<header class="sp-drawer__head">
			<h2 class="sp-drawer__title">סל הקניות</h2>
			<button type="button" class="sp-drawer__close" data-sp-drawer-close aria-label="סגירת הסל"><svg viewBox="0 0 24 24"><path d="M18.3 5.7 12 12l6.3 6.3-1.4 1.4L10.6 13.4 4.3 19.7 2.9 18.3 9.2 12 2.9 5.7 4.3 4.3l6.3 6.3 6.3-6.3 1.4 1.4Z"/></svg></button>
		</header>
		<div class="sp-drawer__body" data-sp-drawer-body><p class="sp-drawer__empty">הסל שלך ריק</p></div>
		<footer class="sp-drawer__foot" data-sp-drawer-foot hidden>
			<div class="sp-drawer__totals"><span>סה"כ</span><strong data-sp-cart-total></strong></div>
			<a class="sp-btn sp-btn--primary sp-btn--block" href="#">מעבר לתשלום</a>
			<a class="sp-drawer__cartlink" href="#">צפייה בסל</a>
		</footer>
	</aside>
</div>

<div class="sp-toast" data-sp-toast hidden role="status" aria-live="polite"></div>

<script type="application/json" id="sp-stories-data">${JSON.stringify(stories).replace(/</g, '\\u003C')}</script>

<script>
// Preview shim: the real page gets this from wp_localize_script().
window.storyphonePages = {
	storeApi: '${API}/',
	nonce: '',
	hasWoo: true,
	homeUrl: 'https://storyphone.co.il/',
	cartUrl: '#',
	checkoutUrl: '#',
	searchHints: ${JSON.stringify(categories.slice(0, 5).map((t) => t.name))},
	i18n: {
		cartTitle: 'סל הקניות', cartEmpty: 'הסל שלך ריק', subtotal: 'סה"כ',
		checkout: 'מעבר לתשלום', viewCart: 'צפייה בסל', remove: 'הסרה',
		decrease: 'הפחתת כמות', increase: 'הוספת כמות', adding: 'מוסיף…',
		added: 'נוסף לסל', addToCart: 'הוספה לסל', genericFail: 'משהו נכשל. נסה שוב.',
		closeCart: 'סגירת הסל', outOfStock: 'אזל',
		searchProducts: 'מוצרים', searchEmpty: 'לא מצאנו התאמות. נסו מילה אחרת.',
		searchError: 'החיפוש נכשל. נסו שוב.', searchAll: 'לכל התוצאות עבור',
		searchUnavailable: 'החיפוש אינו זמין כרגע.'
	}
};

// Offline search fixture so the palette can be screenshotted deterministically.
const PREVIEW_PRODUCTS = ${JSON.stringify(
	[...hot, ...showcase].map((p) => ({
		id: p.id,
		name: p.name,
		permalink: p.permalink,
		prices: p.prices,
		images: p.images ? p.images.slice(0, 1) : [],
		is_in_stock: p.is_in_stock,
	}))
)};

const nativeFetch = window.fetch.bind(window);
window.fetch = function (input, init) {
	const url = String(typeof input === 'string' ? input : input.url);

	if (url.includes('/products?') && url.includes('search=')) {
		const term = decodeURIComponent(new URL(url).searchParams.get('search') || '').toLowerCase();
		const hits = PREVIEW_PRODUCTS.filter((p) => p.name.toLowerCase().includes(term)).slice(0, 6);
		return Promise.resolve(new Response(JSON.stringify(hits), {
			status: 200, headers: { 'Content-Type': 'application/json' }
		}));
	}

	if (url.includes('/cart')) {
		return Promise.resolve(new Response(JSON.stringify({ items: [], items_count: 0, totals: {} }), {
			status: 200, headers: { 'Content-Type': 'application/json' }
		}));
	}

	return nativeFetch(input, init);
};
</script>
<script type="module" src="../storyphone-pages/build/main.js"></script>
</body>
</html>
`;

writeFileSync(out, html, 'utf8');
console.log(`Wrote ${out}`);
