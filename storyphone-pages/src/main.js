/**
 * Front-end entry point for StoryPhone Pages.
 */

import './styles/main.css';

import { initCart } from './lib/cart.js';
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
function boot() {
	[initNav, initReveal, initMotion, initStories, initSearch, initCart, initProduct].forEach((init) => {
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
