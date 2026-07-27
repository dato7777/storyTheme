/**
 * Cart drawer: a presentation layer over WooCommerce's real cart.
 *
 * Nothing here decides what an item costs or whether it can be bought. Every
 * mutation round-trips to the Store API and the drawer re-renders from whatever
 * WooCommerce says the cart now is. "Checkout" is a plain link to WooCommerce's
 * own checkout page.
 */

import {
	addItem,
	formatMoney,
	getCart,
	isAvailable,
	removeItem,
	updateItem,
} from './store-api.js';

const config = window.storyphonePages || {};
const i18n = config.i18n || {};

let drawer = null;
let panel = null;
let itemsEl = null;
let footEl = null;
let totalEl = null;
let toastEl = null;
let toastTimer = null;
let lastFocused = null;
let busy = false;

/**
 * Create an element with optional class and text.
 *
 * @param {string} tag       Tag name.
 * @param {string} [className] Class attribute.
 * @param {string} [text]      Text content.
 * @return {HTMLElement} New element.
 */
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

/**
 * Show a transient status message.
 *
 * @param {string} message Message text.
 * @param {boolean} [isError] Render as an error.
 * @return {void}
 */
function toast(message, isError = false) {
	if (!toastEl || !message) {
		return;
	}

	toastEl.textContent = message;
	toastEl.classList.toggle('is-error', Boolean(isError));
	toastEl.hidden = false;
	// Force a reflow so the entry transition replays on repeat messages.
	void toastEl.offsetWidth;
	toastEl.classList.add('is-visible');

	window.clearTimeout(toastTimer);
	toastTimer = window.setTimeout(() => {
		toastEl.classList.remove('is-visible');
		toastTimer = window.setTimeout(() => {
			toastEl.hidden = true;
		}, 250);
	}, 2600);
}

/**
 * Update every cart count badge in the page.
 *
 * @param {number} count Item count.
 * @return {void}
 */
function updateBadges(count) {
	document.querySelectorAll('[data-sp-cart-count]').forEach((badge) => {
		badge.textContent = String(count);
		badge.hidden = count < 1;
	});
}

/**
 * Build one drawer line item.
 *
 * @param {Object} item Store API cart item.
 * @return {HTMLElement} Line item element.
 */
function buildItem(item) {
	const row = el('li', 'sp-line');
	row.dataset.key = item.key;

	const thumb = item.images && item.images.length ? item.images[0] : null;
	if (thumb && thumb.thumbnail) {
		const media = el('span', 'sp-line__media');
		const img = document.createElement('img');
		img.src = thumb.thumbnail;
		img.alt = '';
		img.loading = 'lazy';
		img.className = 'sp-line__img';
		media.append(img);
		row.append(media);
	}

	const body = el('div', 'sp-line__body');

	const title = el('p', 'sp-line__name');
	if (item.permalink) {
		const link = el('a', '', item.name);
		link.href = item.permalink;
		title.append(link);
	} else {
		title.textContent = item.name;
	}
	body.append(title);

	const totals = item.totals || {};
	body.append(el('p', 'sp-line__price', formatMoney(totals.line_total, totals)));

	const controls = el('div', 'sp-line__controls');

	const stepper = el('div', 'sp-stepper');
	const minus = el('button', 'sp-stepper__btn', '\u2212');
	minus.type = 'button';
	minus.dataset.spItemStep = '-1';
	minus.setAttribute('aria-label', i18n.decrease || 'Decrease quantity');

	const qty = el('span', 'sp-stepper__value', String(item.quantity));

	const plus = el('button', 'sp-stepper__btn', '+');
	plus.type = 'button';
	plus.dataset.spItemStep = '1';
	plus.setAttribute('aria-label', i18n.increase || 'Increase quantity');

	const limits = item.quantity_limits || {};
	if (Number.isFinite(limits.maximum) && item.quantity >= limits.maximum) {
		plus.disabled = true;
	}

	stepper.append(minus, qty, plus);
	controls.append(stepper);

	const remove = el('button', 'sp-line__remove', i18n.remove || 'Remove');
	remove.type = 'button';
	remove.dataset.spItemRemove = '';
	controls.append(remove);

	body.append(controls);
	row.append(body);

	return row;
}

/**
 * Render a cart payload into the drawer.
 *
 * @param {Object|null} cart Store API cart response.
 * @return {void}
 */
function renderCart(cart) {
	const count = Number((cart && cart.items_count) || 0);
	updateBadges(count);

	if (!itemsEl) {
		return;
	}

	itemsEl.replaceChildren();

	const items = cart && Array.isArray(cart.items) ? cart.items : [];

	if (items.length === 0) {
		itemsEl.append(el('p', 'sp-drawer__empty', i18n.cartEmpty || 'Your cart is empty'));
		if (footEl) {
			footEl.hidden = true;
		}
		return;
	}

	const list = el('ul', 'sp-lines');
	items.forEach((item) => list.append(buildItem(item)));
	itemsEl.append(list);

	if (footEl) {
		footEl.hidden = false;
	}
	if (totalEl && cart.totals) {
		totalEl.textContent = formatMoney(cart.totals.total_price, cart.totals);
	}
}

/**
 * Focusable children of the drawer panel.
 *
 * @return {HTMLElement[]} Focusable elements.
 */
function focusables() {
	if (!panel) {
		return [];
	}
	return Array.from(
		panel.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
		)
	).filter((node) => node.offsetParent !== null);
}

/**
 * Open the drawer.
 *
 * @return {void}
 */
function openDrawer() {
	if (!drawer) {
		return;
	}

	lastFocused = document.activeElement;
	drawer.hidden = false;
	void drawer.offsetWidth;
	drawer.classList.add('is-open');
	document.documentElement.classList.add('sp-locked');

	const close = panel && panel.querySelector('[data-sp-drawer-close]');
	if (close) {
		close.focus();
	}
}

/**
 * Close the drawer and restore focus.
 *
 * @return {void}
 */
function closeDrawer() {
	if (!drawer || drawer.hidden) {
		return;
	}

	drawer.classList.remove('is-open');
	document.documentElement.classList.remove('sp-locked');

	window.setTimeout(() => {
		drawer.hidden = true;
	}, 280);

	if (lastFocused && typeof lastFocused.focus === 'function') {
		lastFocused.focus();
	}
}

/**
 * Fetch the cart and render it.
 *
 * @return {Promise<void>}
 */
async function refresh() {
	try {
		renderCart(await getCart());
	} catch (error) {
		// A failed read must never break the page; the cart page remains reachable.
		updateBadges(0);
	}
}

/**
 * Run a cart mutation with a shared busy guard.
 *
 * @param {Function} operation Async operation returning a cart payload.
 * @param {string}   [success] Optional success toast.
 * @return {Promise<void>}
 */
async function mutate(operation, success = '') {
	if (busy) {
		return;
	}
	busy = true;

	try {
		renderCart(await operation());
		if (success) {
			toast(success);
		}
	} catch (error) {
		toast(error.message || i18n.genericFail || 'Something went wrong', true);
	} finally {
		busy = false;
	}
}

/**
 * Wire up all event listeners.
 *
 * @return {void}
 */
function bindEvents() {
	document.addEventListener('click', (event) => {
		const toggle = event.target.closest('[data-sp-cart-toggle]');
		if (toggle) {
			event.preventDefault();
			openDrawer();
			refresh();
			return;
		}

		if (event.target.closest('[data-sp-drawer-close]')) {
			event.preventDefault();
			closeDrawer();
			return;
		}

		const add = event.target.closest('[data-sp-add-to-cart]');
		if (add) {
			event.preventDefault();
			handleAdd(add);
			return;
		}

		const step = event.target.closest('[data-sp-item-step]');
		if (step) {
			event.preventDefault();
			handleStep(step);
			return;
		}

		const remove = event.target.closest('[data-sp-item-remove]');
		if (remove) {
			event.preventDefault();
			const key = remove.closest('[data-key]')?.dataset.key;
			if (key) {
				mutate(() => removeItem(key));
			}
		}
	});

	document.addEventListener('keydown', (event) => {
		if (!drawer || drawer.hidden) {
			return;
		}

		if (event.key === 'Escape') {
			event.preventDefault();
			closeDrawer();
			return;
		}

		if (event.key !== 'Tab') {
			return;
		}

		const nodes = focusables();
		if (nodes.length === 0) {
			return;
		}

		const first = nodes[0];
		const last = nodes[nodes.length - 1];

		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	});
}

/**
 * Handle an add-to-cart click.
 *
 * @param {HTMLElement} button Clicked button.
 * @return {Promise<void>}
 */
async function handleAdd(button) {
	const productId = button.dataset.productId;
	if (!productId || busy) {
		return;
	}

	const label = button.querySelector('.sp-btn__label');
	const original = label ? label.textContent : '';

	button.disabled = true;
	button.classList.add('is-busy');
	if (label && i18n.adding) {
		label.textContent = i18n.adding;
	}

	await mutate(() => addItem(productId, 1), i18n.added);

	button.disabled = false;
	button.classList.remove('is-busy');
	if (label) {
		label.textContent = original;
	}

	// Adding from inside a story should not yank the shopper out of it; the
	// toast and the header counter already confirm the add.
	if (!button.closest('[data-sp-viewer]')) {
		openDrawer();
	}
}

/**
 * Handle a quantity stepper click.
 *
 * @param {HTMLElement} button Clicked button.
 * @return {Promise<void>}
 */
function handleStep(button) {
	const row = button.closest('[data-key]');
	if (!row) {
		return;
	}

	const key = row.dataset.key;
	const delta = Number(button.dataset.spItemStep);
	const current = Number(row.querySelector('.sp-stepper__value')?.textContent || 0);
	const next = current + delta;

	if (next < 1) {
		mutate(() => removeItem(key));
		return;
	}

	mutate(() => updateItem(key, next));
}

/**
 * Boot the cart drawer.
 *
 * @return {void}
 */
export function initCart() {
	drawer = document.querySelector('[data-sp-drawer]');
	panel = drawer ? drawer.querySelector('.sp-drawer__panel') : null;
	itemsEl = drawer ? drawer.querySelector('[data-sp-drawer-items]') : null;
	footEl = drawer ? drawer.querySelector('[data-sp-drawer-foot]') : null;
	totalEl = drawer ? drawer.querySelector('[data-sp-cart-total]') : null;
	toastEl = document.querySelector('[data-sp-toast]');

	if (!isAvailable()) {
		return;
	}

	bindEvents();
	refresh();
}
