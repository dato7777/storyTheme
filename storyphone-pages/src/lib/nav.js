/**
 * Header behaviour: sticky state, mobile sheet, and the hoverable category
 * mega-menu that paints child cards into the hero stage.
 */

const FINE_HOVER = window.matchMedia('(hover: hover) and (pointer: fine)');
const DESKTOP_NAV = window.matchMedia('(min-width: 861px)');

/**
 * Boot header interactions.
 *
 * @return {void}
 */
export function initNav() {
	const header = document.querySelector('[data-sp-header]');
	const toggle = document.querySelector('[data-sp-nav-toggle]');
	const nav = document.getElementById('sp-nav');
	const stage = document.querySelector('[data-sp-nav-stage]');
	const triggers = Array.from(document.querySelectorAll('[data-sp-nav-trigger]'));

	if (header) {
		const onScroll = () => {
			header.classList.toggle('is-stuck', window.scrollY > 12);
		};
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	if (toggle && nav) {
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

		DESKTOP_NAV.addEventListener('change', (event) => {
			if (event.matches) {
				setOpen(false);
			}
		});
	}

	if (triggers.length === 0) {
		return;
	}

	// Homepage: hero stage. Product/category: header dropdown mega (same cards).
	if (stage) {
		initNavMega(triggers, stage);
	}
	initMobileAccordions(triggers);
}

/**
 * Desktop: hovering / focusing a parent paints its panel into the hero stage.
 *
 * The panel stays latched until another parent is hovered — leaving the bar
 * must never clear it, or shoppers cannot reach the child cards.
 * Idle ("גלו קטגוריה") is the cold-start / refresh default only.
 *
 * @param {HTMLElement[]} triggers Nav trigger buttons.
 * @param {Element|null}  stage    Hero stage container.
 * @return {void}
 */
function initNavMega(triggers, stage) {
	if (!stage) {
		return;
	}

	const idle = stage.querySelector('[data-sp-nav-idle]');
	const panels = Array.from(stage.querySelectorAll('[data-sp-nav-panel]'));
	const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
	let activeId = null;
	let glowTimer = null;
	let glowIndex = 0;

	const stopCardGlow = () => {
		if (glowTimer) {
			window.clearInterval(glowTimer);
			glowTimer = null;
		}
		stage.querySelectorAll('.sp-navCard.is-lit').forEach((card) => {
			card.classList.remove('is-lit');
		});
		glowIndex = 0;
	};

	/**
	 * Walk a soft glow across the visible cards, one every 2 seconds.
	 *
	 * @param {Element} panel Active panel.
	 * @return {void}
	 */
	const startCardGlow = (panel) => {
		stopCardGlow();

		if (reduced.matches) {
			return;
		}

		const cards = Array.from(panel.querySelectorAll('.sp-navCard'));
		if (cards.length === 0) {
			return;
		}

		const light = () => {
			cards.forEach((card) => card.classList.remove('is-lit'));
			const card = cards[glowIndex % cards.length];
			// Retrigger the shine keyframes even when the same card is alone.
			void card.offsetWidth;
			card.classList.add('is-lit');
			glowIndex = (glowIndex + 1) % cards.length;
		};

		light();
		glowTimer = window.setInterval(light, 2000);
	};

	const showPanel = (id) => {
		if (!id || activeId === id) {
			return;
		}

		activeId = id;

		if (idle) {
			idle.classList.remove('is-active');
			idle.hidden = true;
		}

		let activePanel = null;

		panels.forEach((panel) => {
			const match = panel.getAttribute('data-sp-nav-panel') === id;
			panel.hidden = !match;
			panel.classList.toggle('is-active', match);
			if (match) {
				activePanel = panel;
			}
		});

		triggers.forEach((trigger) => {
			const on = trigger.getAttribute('data-sp-nav-trigger') === id;
			trigger.classList.toggle('is-hot', on);
			trigger.setAttribute('aria-expanded', on ? 'true' : 'false');
		});

		stage.hidden = false;
		stage.classList.add('is-open');
		// Dropdown mega: frost the sticky header so the panel reads as one surface.
		stage.closest('[data-sp-header]')?.classList.add('is-mega-open');

		if (activePanel) {
			startCardGlow(activePanel);
		}
	};

	triggers.forEach((trigger) => {
		const id = trigger.getAttribute('data-sp-nav-trigger');

		trigger.addEventListener('pointerenter', () => {
			if (!FINE_HOVER.matches || !DESKTOP_NAV.matches) {
				return;
			}
			showPanel(id);
		});

		trigger.addEventListener('focus', () => {
			if (!DESKTOP_NAV.matches) {
				return;
			}
			showPanel(id);
		});

		// Parents are not links; click latches the same panel (no toggle-off).
		trigger.addEventListener('click', (event) => {
			if (!DESKTOP_NAV.matches) {
				return;
			}
			event.preventDefault();
			showPanel(id);
		});
	});
}

/**
 * Mobile: tap a parent to expand its plain-link drawer inside the sheet.
 *
 * @param {HTMLElement[]} triggers Nav trigger buttons.
 * @return {void}
 */
function initMobileAccordions(triggers) {
	triggers.forEach((trigger) => {
		const item = trigger.closest('.sp-nav__item');
		const drawer = item ? item.querySelector('.sp-nav__drawer') : null;

		if (!drawer) {
			return;
		}

		trigger.addEventListener('click', () => {
			if (DESKTOP_NAV.matches) {
				return;
			}

			const open = trigger.getAttribute('aria-expanded') === 'true';

			triggers.forEach((other) => {
				if (other === trigger) {
					return;
				}
				other.setAttribute('aria-expanded', 'false');
				other.classList.remove('is-hot');
				const otherItem = other.closest('.sp-nav__item');
				const otherDrawer = otherItem ? otherItem.querySelector('.sp-nav__drawer') : null;
				if (otherDrawer) {
					otherDrawer.hidden = true;
				}
			});

			trigger.setAttribute('aria-expanded', open ? 'false' : 'true');
			trigger.classList.toggle('is-hot', !open);
			drawer.hidden = open;
		});
	});
}
