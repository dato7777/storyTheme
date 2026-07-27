/**
 * Scroll-triggered reveal for elements marked with `data-sp-reveal`.
 *
 * Elements start hidden only when we can actually observe them, so JS failures
 * or unsupported browsers leave the content visible rather than blank.
 */

const REVEALED = 'is-revealed';

/**
 * Boot reveal animations.
 *
 * @return {void}
 */
export function initReveal() {
	const targets = Array.from(document.querySelectorAll('[data-sp-reveal]'));
	if (targets.length === 0) {
		return;
	}

	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	if (reducedMotion || typeof IntersectionObserver === 'undefined') {
		targets.forEach((node) => node.classList.add(REVEALED));
		return;
	}

	document.documentElement.classList.add('sp-can-reveal');

	const observer = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) {
					return;
				}
				entry.target.classList.add(REVEALED);
				observer.unobserve(entry.target);
			});
		},
		{ rootMargin: '0px 0px -10% 0px', threshold: 0.08 }
	);

	targets.forEach((node, index) => {
		// Stagger siblings slightly so grids cascade instead of popping at once.
		node.style.setProperty('--sp-reveal-delay', `${Math.min(index % 8, 7) * 60}ms`);
		observer.observe(node);
	});
}
