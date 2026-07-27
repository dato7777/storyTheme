/**
 * Header behaviour: mobile navigation toggle and scroll state.
 */

/**
 * Boot header interactions.
 *
 * @return {void}
 */
export function initNav() {
	const header = document.querySelector('[data-sp-header]');
	const toggle = document.querySelector('[data-sp-nav-toggle]');
	const nav = document.getElementById('sp-nav');

	if (header) {
		const onScroll = () => {
			header.classList.toggle('is-stuck', window.scrollY > 12);
		};
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	if (!toggle || !nav) {
		return;
	}

	const setOpen = (open) => {
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		toggle.classList.toggle('is-open', open);
		nav.classList.toggle('is-open', open);
		document.documentElement.classList.toggle('sp-nav-open', open);
	};

	toggle.addEventListener('click', () => {
		setOpen(toggle.getAttribute('aria-expanded') !== 'true');
	});

	nav.addEventListener('click', (event) => {
		if (event.target.closest('a')) {
			setOpen(false);
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
			setOpen(false);
			toggle.focus();
		}
	});

	// Leaving mobile width should never strand the page in the open-menu state.
	window.matchMedia('(min-width: 900px)').addEventListener('change', (event) => {
		if (event.matches) {
			setOpen(false);
		}
	});
}
