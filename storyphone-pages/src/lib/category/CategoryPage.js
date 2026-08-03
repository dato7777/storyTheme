/**
 * CategoryPage — orchestrates hero, subcategory chips, and product grid.
 *
 * Mirrors a React <CategoryPage categoryId={…} />: state lives here, children
 * are pure render helpers. Sorting/extra filters can hang off `state` later.
 *
 * Fetch race guard: AbortController cancels superseded subcategory clicks.
 */

import { CategoryHero } from './CategoryHero.js';
import { SubcategorySelector } from './SubcategorySelector.js';
import { ProductGrid } from './ProductGrid.js';

const config = window.storyphonePages || {};

/**
 * Boot the category page when the archive shell is present.
 *
 * @return {void}
 */
export function initCategory() {
	const root = document.querySelector('[data-sp-category-page]');
	if (!root) {
		return;
	}

	CategoryPage(root);
}

/**
 * @param {HTMLElement} root
 */
function CategoryPage(root) {
	const bootNode = root.querySelector('[data-sp-category-boot]');
	let boot = {};
	try {
		boot = bootNode ? JSON.parse(bootNode.textContent || '{}') : {};
	} catch (error) {
		boot = {};
	}

	const parentId = Number(root.dataset.categoryId || boot.category?.id || 0);
	const state = {
		parentId,
		activeId: Number(boot.activeId || parentId),
		activeName: boot.category?.name || '',
		products: Array.isArray(boot.products) ? boot.products : [],
		total: Number(boot.total || 0),
		// Reserved for future sort / attribute filters.
		sort: 'popularity',
	};

	CategoryHero(root.querySelector('[data-sp-cat-hero]'));

	const grid = ProductGrid(
		root.querySelector('[data-sp-cat-grid]'),
		root.querySelector('[data-sp-cat-live]')
	);

	// SSR cards: apply stagger indices and play the same entrance as fetches.
	const initialList = root.querySelector('[data-sp-cat-list]');
	if (initialList) {
		initialList.querySelectorAll('.sp-card').forEach((card, index) => {
			card.classList.add('sp-catCard');
			card.style.setProperty('--i', String(index));
		});
		window.requestAnimationFrame(() => {
			initialList.classList.add('is-in');
		});
	}

	let controller = null;
	let requestSeq = 0;

	const select = async ({ id, name }) => {
		if (!id || id === state.activeId) {
			return;
		}

		state.activeId = id;
		state.activeName = name || state.activeName;
		subs.setActive(id);
		updateRailTitle(state.activeName);

		if (controller) {
			controller.abort();
		}
		controller = new AbortController();
		const seq = (requestSeq += 1);

		await grid.render({
			products: state.products,
			total: state.total,
			label: state.activeName,
			status: 'loading',
		});

		try {
			const payload = await fetchProducts(id, { signal: controller.signal });
			if (seq !== requestSeq) {
				return;
			}
			state.products = payload.products;
			state.total = payload.total;
			await grid.render({
				products: state.products,
				total: state.total,
				label: state.activeName,
				status: 'idle',
			});
		} catch (error) {
			if (error.name === 'AbortError' || seq !== requestSeq) {
				return;
			}
			await grid.render({
				products: [],
				total: 0,
				label: state.activeName,
				status: 'error',
			});
		}
	};

	const subsRoot = root.querySelector('[data-sp-cat-subs]');
	const subs = subsRoot
		? SubcategorySelector(subsRoot, select)
		: { setActive() {} };

	subs.setActive(state.activeId);
	updateRailTitle(state.activeName);
}

/**
 * Rebuild the vertical stacked title when the active subcategory changes.
 *
 * @param {string} name
 * @return {void}
 */
function updateRailTitle(name) {
	const title = document.querySelector('[data-sp-cat-rail-title]');
	if (!title || !name) {
		return;
	}

	const chars = Array.from(name);
	title.replaceChildren();
	chars.forEach((ch, index) => {
		const span = document.createElement('span');
		span.className = 'sp-catRail__char';
		span.style.setProperty('--i', String(index));
		span.textContent = ch === ' ' ? '\u00A0' : ch;
		title.append(span);
	});
	title.dataset.len = chars.length > 14 ? 'xl' : chars.length > 9 ? 'long' : 'normal';
}

/**
 * @param {number} categoryId
 * @param {{ signal?: AbortSignal }} [options]
 * @return {Promise<{ products: Array, total: number }>}
 */
async function fetchProducts(categoryId, { signal } = {}) {
	const base = String(config.restUrl || '').replace(/\/+$/, '');
	const url = new URL(`${base}/products`, window.location.origin);
	url.searchParams.set('category', String(categoryId));
	url.searchParams.set('per_page', '24');
	url.searchParams.set('page', '1');

	const headers = { Accept: 'application/json' };
	if (config.wpNonce) {
		headers['X-WP-Nonce'] = config.wpNonce;
	}

	const response = await fetch(url.toString(), {
		method: 'GET',
		credentials: 'same-origin',
		cache: 'no-store',
		headers,
		signal,
	});

	if (!response.ok) {
		throw new Error(`Category products failed (${response.status})`);
	}

	const payload = await response.json();
	return {
		products: Array.isArray(payload.products) ? payload.products : [],
		total: Number(payload.total || 0),
	};
}
