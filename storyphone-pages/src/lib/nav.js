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

	initNavMega(triggers, stage);
	initMobileAccordions(triggers);
}

/**
 * Desktop: hovering / focusing a parent paints its panel into the hero stage.
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
	let activeId = null;
	let leaveTimer = null;

	const showPanel = (id) => {
		if (!id || activeId === id) {
			return;
		}

		activeId = id;

		if (idle) {
			idle.classList.remove('is-active');
			idle.hidden = true;
		}

		panels.forEach((panel) => {
			const match = panel.getAttribute('data-sp-nav-panel') === id;
			panel.hidden = !match;
			panel.classList.toggle('is-active', match);
		});

		triggers.forEach((trigger) => {
			const on = trigger.getAttribute('data-sp-nav-trigger') === id;
			trigger.classList.toggle('is-hot', on);
			trigger.setAttribute('aria-expanded', on ? 'true' : 'false');
		});

		stage.classList.add('is-open');
	};

	const showIdle = () => {
		activeId = null;

		panels.forEach((panel) => {
			panel.hidden = true;
			panel.classList.remove('is-active');
		});

		if (idle) {
			idle.hidden = false;
			idle.classList.add('is-active');
		}

		triggers.forEach((trigger) => {
			trigger.classList.remove('is-hot');
			// Leave mobile accordion state alone when the sheet is open.
			if (DESKTOP_NAV.matches) {
				trigger.setAttribute('aria-expanded', 'false');
			}
		});

		stage.classList.remove('is-open');
	};

	const cancelLeave = () => {
		if (leaveTimer) {
			window.clearTimeout(leaveTimer);
			leaveTimer = null;
		}
	};

	const scheduleLeave = () => {
		cancelLeave();
		// A short grace period lets the pointer travel from the bar into the
		// stage without the panel collapsing underfoot.
		leaveTimer = window.setTimeout(() => {
			if (DESKTOP_NAV.matches) {
				showIdle();
			}
		}, 160);
	};

	const bindDesktop = (trigger) => {
		const id = trigger.getAttribute('data-sp-nav-trigger');

		trigger.addEventListener('pointerenter', () => {
			if (!FINE_HOVER.matches || !DESKTOP_NAV.matches) {
				return;
			}
			cancelLeave();
			showPanel(id);
		});

		trigger.addEventListener('focus', () => {
			if (!DESKTOP_NAV.matches) {
				return;
			}
			cancelLeave();
			showPanel(id);
		});

		trigger.addEventListener('pointerleave', () => {
			if (!FINE_HOVER.matches || !DESKTOP_NAV.matches) {
				return;
			}
			scheduleLeave();
		});
	};

	triggers.forEach(bindDesktop);

	stage.addEventListener('pointerenter', cancelLeave);
	stage.addEventListener('pointerleave', () => {
		if (DESKTOP_NAV.matches) {
			scheduleLeave();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && activeId && DESKTOP_NAV.matches) {
			showIdle();
		}
	});

	// Parents are deliberately not links — swallow activation so Enter does
	// not "submit" anything and instead keeps the panel open for Tab.
	triggers.forEach((trigger) => {
		trigger.addEventListener('click', (event) => {
			if (DESKTOP_NAV.matches) {
				event.preventDefault();
				const id = trigger.getAttribute('data-sp-nav-trigger');
				if (activeId === id) {
					showIdle();
				} else {
					showPanel(id);
				}
			}
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
