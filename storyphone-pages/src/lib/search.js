/**
 * Command palette search.
 *
 * Opens on click, on "/" or on Cmd/Ctrl+K, and streams live results from the
 * WooCommerce Store API while typing. Every result value is written with
 * textContent, so a product name can never inject markup into the page.
 */

import { trapFocus } from './focus-trap.js';
import { searchProducts, formatMoney, isAvailable } from './store-api.js';

const DEBOUNCE = 220;
const MIN_CHARS = 2;
const RECENT_KEY = 'sp_recent_searches';
const RECENT_MAX = 5;

const config = window.storyphonePages || {};
const i18n = config.i18n || {};

let palette = null;
let input = null;
let results = null;
let intro = null;
let spinner = null;
let recentWrap = null;
let recentList = null;

let debounceTimer = null;
let controller = null;
let activeIndex = -1;
let lastFocused = null;
let releaseFocus = null;

/* ---------- recent searches ---------- */

function readRecent() {
	try {
		const raw = window.localStorage.getItem(RECENT_KEY);
		const parsed = raw ? JSON.parse(raw) : [];
		return Array.isArray(parsed) ? parsed.filter((entry) => typeof entry === 'string') : [];
	} catch (error) {
		return [];
	}
}

function pushRecent(term) {
	const clean = term.trim();
	if (clean.length < MIN_CHARS) {
		return;
	}
	try {
		const next = [clean, ...readRecent().filter((entry) => entry !== clean)].slice(0, RECENT_MAX);
		window.localStorage.setItem(RECENT_KEY, JSON.stringify(next));
	} catch (error) {
		/* Storage is optional. */
	}
}

function renderRecent() {
	if (!recentWrap || !recentList) {
		return;
	}

	const recent = readRecent();
	recentWrap.hidden = recent.length === 0;
	recentList.replaceChildren();

	recent.forEach((term) => {
		const chip = document.createElement('button');
		chip.type = 'button';
		chip.className = 'sp-chip sp-chip--recent';
		chip.textContent = term;
		chip.addEventListener('click', () => {
			input.value = term;
			run(term);
			input.focus();
		});
		recentList.append(chip);
	});
}

/* ---------- result rendering ---------- */

function el(tag, className = '', text = '') {
	const node = document.createElement(tag);
	if (className) {
		node.className = className;
	}
	if (text) {
		node.textContent = text;
	}
	return node;
}

function showIntro() {
	if (!results || !intro) {
		return;
	}
	results.replaceChildren(intro);
	intro.hidden = false;
	activeIndex = -1;
	renderRecent();
}

function showMessage(message) {
	if (!results) {
		return;
	}
	results.replaceChildren(el('p', 'sp-palette__empty', message));
	activeIndex = -1;
}

function renderResults(products, term) {
	if (!results) {
		return;
	}

	if (products.length === 0) {
		showMessage(i18n.searchEmpty || 'לא מצאנו התאמות. נסו מילה אחרת.');
		appendViewAll(term);
		return;
	}

	results.replaceChildren();
	activeIndex = -1;

	const label = el('p', 'sp-palette__label', i18n.searchProducts || 'מוצרים');
	results.append(label);

	const list = el('div', 'sp-palette__list');

	products.forEach((product) => {
		const item = document.createElement('a');
		item.className = 'sp-result';
		item.href = product.permalink || '#';
		item.setAttribute('role', 'option');
		item.setAttribute('aria-selected', 'false');

		const media = el('span', 'sp-result__media');
		const image = Array.isArray(product.images) ? product.images[0] : null;
		if (image && image.thumbnail) {
			const img = document.createElement('img');
			img.src = image.thumbnail;
			img.alt = '';
			img.loading = 'lazy';
			media.append(img);
		}
		item.append(media);

		const body = el('span', 'sp-result__body');
		body.append(el('span', 'sp-result__name', product.name || ''));

		if (product.prices) {
			const price = formatMoney(product.prices.price, product.prices);
			const regular = formatMoney(product.prices.regular_price, product.prices);
			const priceEl = el('span', 'sp-result__price');

			if (regular && regular !== price) {
				priceEl.append(el('del', '', regular));
			}
			priceEl.append(el('ins', '', price));
			body.append(priceEl);
		}

		item.append(body);

		if (product.is_in_stock === false) {
			item.append(el('span', 'sp-result__flag', i18n.outOfStock || 'אזל'));
		} else {
			item.append(el('span', 'sp-result__go', '\u2190'));
		}

		list.append(item);
	});

	results.append(list);
	appendViewAll(term);
}

function appendViewAll(term) {
	if (!results) {
		return;
	}

	const url = new URL(config.homeUrl || window.location.origin, window.location.origin);
	url.searchParams.set('s', term);
	url.searchParams.set('post_type', 'product');

	const all = document.createElement('a');
	all.className = 'sp-palette__all';
	all.href = url.toString();
	all.setAttribute('role', 'option');
	all.setAttribute('aria-selected', 'false');
	all.textContent = (i18n.searchAll || 'לכל התוצאות עבור') + ` “${term}”`;

	results.append(all);
}

/* ---------- querying ---------- */

async function run(term) {
	const clean = term.trim();

	if (clean.length < MIN_CHARS) {
		showIntro();
		return;
	}

	if (!isAvailable()) {
		showMessage(i18n.searchUnavailable || 'החיפוש אינו זמין כרגע.');
		return;
	}

	if (controller) {
		controller.abort();
	}
	controller = new AbortController();

	if (spinner) {
		spinner.hidden = false;
	}

	try {
		const products = await searchProducts(clean, { signal: controller.signal, limit: 6 });
		renderResults(products, clean);
		pushRecent(clean);
	} catch (error) {
		if (error.name !== 'AbortError') {
			showMessage(i18n.searchError || 'החיפוש נכשל. נסו שוב.');
		}
	} finally {
		if (spinner) {
			spinner.hidden = true;
		}
	}
}

function schedule(term) {
	window.clearTimeout(debounceTimer);
	debounceTimer = window.setTimeout(() => run(term), DEBOUNCE);
}

/* ---------- keyboard navigation ---------- */

function options() {
	return results ? Array.from(results.querySelectorAll('[role="option"]')) : [];
}

function moveActive(step) {
	const list = options();
	if (list.length === 0) {
		return;
	}

	activeIndex = (activeIndex + step + list.length) % list.length;

	list.forEach((option, index) => {
		const isActive = index === activeIndex;
		option.classList.toggle('is-active', isActive);
		option.setAttribute('aria-selected', isActive ? 'true' : 'false');
		if (isActive) {
			option.scrollIntoView({ block: 'nearest' });
		}
	});
}

/* ---------- open / close ---------- */

function open() {
	if (!palette || !palette.hidden) {
		return;
	}

	lastFocused = document.activeElement;
	palette.hidden = false;
	void palette.offsetWidth;
	palette.classList.add('is-open');
	document.documentElement.classList.add('sp-locked');

	showIntro();
	releaseFocus = trapFocus(palette);

	window.setTimeout(() => {
		if (input) {
			input.focus();
			input.select();
		}
	}, 40);
}

function close() {
	if (!palette || palette.hidden) {
		return;
	}

	window.clearTimeout(debounceTimer);
	if (controller) {
		controller.abort();
		controller = null;
	}

	if (releaseFocus) {
		releaseFocus();
		releaseFocus = null;
	}

	palette.classList.remove('is-open');
	document.documentElement.classList.remove('sp-locked');

	window.setTimeout(() => {
		palette.hidden = true;
	}, 200);

	if (lastFocused && typeof lastFocused.focus === 'function') {
		lastFocused.focus();
	}
}

/**
 * Boot the command palette.
 *
 * @return {void}
 */
export function initSearch() {
	palette = document.querySelector('[data-sp-palette]');
	if (!palette) {
		return;
	}

	input = palette.querySelector('[data-sp-palette-input]');
	results = palette.querySelector('[data-sp-palette-results]');
	intro = palette.querySelector('[data-sp-palette-intro]');
	spinner = palette.querySelector('[data-sp-palette-spinner]');
	recentWrap = palette.querySelector('[data-sp-palette-recent]');
	recentList = palette.querySelector('[data-sp-palette-recent-list]');

	document.addEventListener('click', (event) => {
		if (event.target.closest('[data-sp-search-open]')) {
			event.preventDefault();
			open();
			return;
		}
		if (event.target.closest('[data-sp-palette-close]')) {
			event.preventDefault();
			close();
		}
	});

	if (input) {
		input.addEventListener('input', () => schedule(input.value));
	}

	// The form still submits to WordPress search if someone hits Enter with no
	// result highlighted, which keeps the palette useful without JavaScript.
	palette.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			event.preventDefault();
			close();
			return;
		}
		if (event.key === 'ArrowDown') {
			event.preventDefault();
			moveActive(1);
			return;
		}
		if (event.key === 'ArrowUp') {
			event.preventDefault();
			moveActive(-1);
			return;
		}
		if (event.key === 'Enter') {
			const list = options();
			if (activeIndex >= 0 && list[activeIndex]) {
				event.preventDefault();
				list[activeIndex].click();
			}
		}
	});

	document.addEventListener('keydown', (event) => {
		const typingElsewhere =
			event.target instanceof HTMLElement &&
			(event.target.closest('input, textarea, select') || event.target.isContentEditable);

		if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
			event.preventDefault();
			open();
			return;
		}

		if (event.key === '/' && !typingElsewhere && palette.hidden) {
			event.preventDefault();
			open();
		}
	});
}
