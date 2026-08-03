/**
 * ProductCard — builds one category-grid card from a REST product payload.
 *
 * Timing note: entrance delay is applied by ProductGrid via --i; keep card
 * CSS transitions ~380ms so the stagger (50ms) stays readable.
 */

const config = window.storyphonePages || {};
const i18n = config.i18n || {};

/**
 * @param {Object} product Serialized product from storyphone-pages/v1.
 * @param {number} index   Stagger index.
 * @return {HTMLElement}
 */
export function ProductCard(product, index = 0) {
	const article = document.createElement('article');
	article.className = 'sp-card sp-catCard';
	article.dataset.productId = String(product.id || '');
	article.style.setProperty('--i', String(index));

	const media = document.createElement('a');
	media.className = 'sp-card__media';
	media.href = product.permalink || '#';
	media.tabIndex = -1;
	media.setAttribute('aria-hidden', 'true');

	if (product.image) {
		const img = document.createElement('img');
		img.className = 'sp-card__img';
		img.src = product.image;
		img.alt = '';
		img.loading = 'lazy';
		img.decoding = 'async';
		media.append(img);
	}

	if (!product.inStock) {
		media.append(badge('sp-badge sp-badge--muted', i18n.catOutOfStock || 'אזל מהמלאי'));
	} else if (product.discount > 0) {
		const label = (i18n.catSale || '%d%%- הנחה').replace('%d', String(product.discount));
		media.append(badge('sp-badge sp-badge--sale sp-catCard__pepper', label));
	}

	const body = document.createElement('div');
	body.className = 'sp-card__body';

	const title = document.createElement('h3');
	title.className = 'sp-card__title';
	const titleLink = document.createElement('a');
	titleLink.href = product.permalink || '#';
	titleLink.textContent = product.name || '';
	title.append(titleLink);

	const price = document.createElement('div');
	price.className = 'sp-card__price';
	price.innerHTML = product.priceHtml || '';

	body.append(title, price);

	if (product.quickAdd) {
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'sp-btn sp-btn--add sp-catCard__quick';
		btn.dataset.spAddToCart = '';
		btn.dataset.productId = String(product.id || '');
		btn.setAttribute('aria-label', i18n.addToCart || 'הוספה לסל');
		btn.innerHTML = '<span class="sp-btn__label">' + (i18n.addToCart || 'הוספה לסל') + '</span>';
		body.append(btn);
	} else {
		const link = document.createElement('a');
		link.className = 'sp-btn sp-btn--ghost';
		link.href = product.permalink || '#';
		link.textContent = i18n.catViewProduct || 'לצפייה במוצר';
		body.append(link);
	}

	article.append(media, body);
	return article;
}

/**
 * @param {string} className
 * @param {string} text
 * @return {HTMLElement}
 */
function badge(className, text) {
	const node = document.createElement('span');
	node.className = className;
	node.textContent = text;
	return node;
}
