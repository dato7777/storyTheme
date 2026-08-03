/**
 * CategoryHero — badge + title fade-in; also marks the page ready so the
 * vertical rail letters can stagger in.
 *
 * Timing (tune here): badge/title ~520ms; rail letters use CSS --i delays.
 */

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

/**
 * @param {HTMLElement} root [data-sp-cat-hero]
 */
export function CategoryHero(root) {
	const page = document.body;

	const ready = () => {
		root?.classList.add('is-ready');
		page?.classList.add('sp-cat-ready');
		sizeRailTitle();
	};

	if (!root) {
		page?.classList.add('sp-cat-ready');
		sizeRailTitle();
		return;
	}

	if (reduced.matches) {
		ready();
		return;
	}

	window.requestAnimationFrame(() => {
		ready();
	});
}

/**
 * Scale the stacked rail title for long category names.
 *
 * @return {void}
 */
function sizeRailTitle() {
	const title = document.querySelector('[data-sp-cat-rail-title]');
	if (!title) {
		return;
	}
	const len = title.querySelectorAll('.sp-catRail__char').length;
	title.dataset.len = len > 14 ? 'xl' : len > 9 ? 'long' : 'normal';
}
