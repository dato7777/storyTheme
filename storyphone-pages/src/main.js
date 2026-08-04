/**
 * Front-end entry point for StoryPhone Pages.
 */

import './styles/main.css';

import { initCart } from './lib/cart.js';
import { initCategory } from './lib/category/CategoryPage.js';
import { initMotion } from './lib/motion.js';
import { initNav } from './lib/nav.js';
import { initProduct } from './lib/product.js';
import { initReveal } from './lib/reveal.js';
import { initSearch } from './lib/search.js';
import { initStories } from './lib/stories.js';

/**
 * Boot every module independently, so one failure cannot take down the rest.
 *
 * @return {void}
 */
/**
 * Remove leftover Design-nav debug badge from older Inventory Manager builds.
 *
 * @return {void}
 */
function killDesignNavBadge() {
	const needle = 'StoryPhone Design nav';
	document.querySelectorAll('div').forEach((el) => {
		const text = (el.textContent || '').trim();
		if (
			text.includes(needle) &&
			text.includes('IM override') &&
			text.length < 240
		) {
			el.remove();
		}
	});
}

function boot() {
	killDesignNavBadge();
	[initNav, initReveal, initMotion, initStories, initSearch, initCart, initProduct, initCategory].forEach((init) => {
		try {
			init();
		} catch (error) {
			if (window.console && console.error) {
				console.error('[storyphone-pages]', error);
			}
		}
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
	boot();
}
