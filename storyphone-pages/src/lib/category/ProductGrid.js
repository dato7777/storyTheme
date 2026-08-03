/**
 * ProductGrid — swap product cards with fade/slide + optional skeleton.
 *
 * Animation timing (tune here):
 * - EXIT_MS: old grid fades out (~220ms)
 * - STAGGER_MS: delay between each new card (~50ms — in the 40–60ms band)
 * - SKELETON_AFTER_MS: show shimmer only if fetch exceeds 150ms
 */

import { ProductCard } from './ProductCard.js';

const EXIT_MS = 220;
const STAGGER_MS = 50;
const SKELETON_AFTER_MS = 150;
const SKELETON_COUNT = 8;

const config = window.storyphonePages || {};
const i18n = config.i18n || {};
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

/**
 * @param {HTMLElement} stage  [data-sp-cat-grid]
 * @param {HTMLElement|null} live [data-sp-cat-live]
 */
export function ProductGrid(stage, live) {
	let skeletonTimer = null;

	/**
	 * @param {Object} options
	 * @param {Array}  options.products
	 * @param {number} options.total
	 * @param {string} options.label
	 * @param {'idle'|'loading'|'error'} [options.status]
	 * @return {Promise<void>}
	 */
	async function render({ products, total, label, status = 'idle' }) {
		if (status === 'loading') {
			scheduleSkeleton();
			return;
		}

		clearSkeletonTimer();

		if (status === 'error') {
			stage.replaceChildren(emptyNode(i18n.catError || 'טעינת המוצרים נכשלה. נסו שוב.'));
			announce(0, label);
			return;
		}

		await exitCurrent();

		if (!products || products.length === 0) {
			stage.replaceChildren(emptyNode(i18n.catEmpty || 'אין מוצרים בקטגוריה הזו כרגע.'));
			announce(0, label);
			return;
		}

		const list = document.createElement('div');
		list.className = 'sp-catGrid__list';
		list.dataset.spCatList = '';

		products.forEach((product, index) => {
			list.append(ProductCard(product, index));
		});

		stage.replaceChildren(list);
		announce(total, label);

		// Force reflow so entrance keyframes restart after replaceChildren.
		void list.offsetWidth;
		list.classList.add('is-in');
	}

	function scheduleSkeleton() {
		clearSkeletonTimer();
		skeletonTimer = window.setTimeout(() => {
			stage.replaceChildren(skeletonNode());
		}, SKELETON_AFTER_MS);
	}

	function clearSkeletonTimer() {
		if (skeletonTimer) {
			window.clearTimeout(skeletonTimer);
			skeletonTimer = null;
		}
	}

	function exitCurrent() {
		const list = stage.querySelector('[data-sp-cat-list]');
		if (!list || reduced.matches) {
			return Promise.resolve();
		}
		list.classList.add('is-out');
		return new Promise((resolve) => {
			window.setTimeout(resolve, EXIT_MS);
		});
	}

	function announce(total, label) {
		if (!live) {
			return;
		}
		const template = i18n.catShowing || 'מציגים %1$s מוצרים ב%2$s';
		live.textContent = template
			.replace('%1$s', String(total))
			.replace('%2$s', label || '');
	}

	return { render, STAGGER_MS };
}

/**
 * @param {string} message
 * @return {HTMLElement}
 */
function emptyNode(message) {
	const wrap = document.createElement('div');
	wrap.className = 'sp-catEmpty';
	wrap.dataset.spCatEmpty = '';
	wrap.innerHTML =
		'<span class="sp-catEmpty__mark" aria-hidden="true"></span>' +
		`<p class="sp-catEmpty__title"></p>`;
	wrap.querySelector('.sp-catEmpty__title').textContent = message;
	return wrap;
}

/**
 * @return {HTMLElement}
 */
function skeletonNode() {
	const list = document.createElement('div');
	list.className = 'sp-catGrid__list sp-catGrid__list--skeleton';
	list.setAttribute('aria-hidden', 'true');
	for (let i = 0; i < SKELETON_COUNT; i += 1) {
		const card = document.createElement('div');
		card.className = 'sp-catSkeleton';
		card.innerHTML =
			'<span class="sp-catSkeleton__media"></span>' +
			'<span class="sp-catSkeleton__line"></span>' +
			'<span class="sp-catSkeleton__line sp-catSkeleton__line--short"></span>';
		list.append(card);
	}
	return list;
}
