/**
 * Stories: category browsing as full-screen, auto-advancing product stories.
 *
 * Data is read from a JSON script tag printed by PHP. Every value is inserted
 * with textContent or as an attribute, never as raw HTML — the single
 * exception is the WooCommerce-generated price markup.
 */

import { trapFocus } from './focus-trap.js';

const DURATION = 6000;
const SEEN_KEY = 'sp_stories_seen';

const config = window.storyphonePages || {};
const i18n = config.i18n || {};

let stories = [];
let viewer = null;
let barsEl = null;
let slideEl = null;
let categoryEl = null;
let positionEl = null;
let avatarEl = null;
let pauseBtn = null;

let storyIndex = 0;
let itemIndex = 0;
let elapsed = 0;
let lastFrame = 0;
let rafId = null;
let paused = false;
let lastFocused = null;
let releaseFocus = null;

const rtl = document.documentElement.dir === 'rtl';

/**
 * Read the story payload printed by PHP.
 *
 * @return {Array} Story list.
 */
function readData() {
	const node = document.getElementById('sp-stories-data');
	if (!node) {
		return [];
	}
	try {
		const parsed = JSON.parse(node.textContent || '[]');
		return Array.isArray(parsed) ? parsed : [];
	} catch (error) {
		return [];
	}
}

/* ---------- seen state ---------- */

function readSeen() {
	try {
		const raw = window.localStorage.getItem(SEEN_KEY);
		const parsed = raw ? JSON.parse(raw) : [];
		return Array.isArray(parsed) ? parsed : [];
	} catch (error) {
		return [];
	}
}

function markSeen(id) {
	if (!id) {
		return;
	}
	try {
		const seen = readSeen();
		if (!seen.includes(id)) {
			seen.push(id);
			window.localStorage.setItem(SEEN_KEY, JSON.stringify(seen.slice(-60)));
		}
	} catch (error) {
		/* Private browsing: seen state is a nicety, not a requirement. */
	}
	paintSeen();
}

function paintSeen() {
	const seen = readSeen();
	document.querySelectorAll('[data-sp-story-open]').forEach((bubble) => {
		bubble.classList.toggle('is-seen', seen.includes(bubble.dataset.storyId));
	});
}

/* ---------- rendering ---------- */

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

function currentStory() {
	return stories[storyIndex] || null;
}

function currentItem() {
	const story = currentStory();
	return story && story.items ? story.items[itemIndex] : null;
}

function renderBars() {
	const story = currentStory();
	if (!barsEl || !story) {
		return;
	}

	barsEl.replaceChildren();

	story.items.forEach((_, index) => {
		const bar = el('span', 'sp-viewer__bar');
		if (index < itemIndex) {
			bar.classList.add('is-done');
		}
		if (index === itemIndex) {
			bar.classList.add('is-active');
		}
		bar.append(el('span', 'sp-viewer__barFill'));
		barsEl.append(bar);
	});
}

function renderSlide() {
	const story = currentStory();
	const item = currentItem();
	if (!slideEl || !story || !item) {
		return;
	}

	if (categoryEl) {
		categoryEl.textContent = story.name;
	}
	if (positionEl) {
		positionEl.textContent = `${itemIndex + 1}/${story.items.length}`;
	}
	if (avatarEl) {
		avatarEl.replaceChildren();
		if (story.cover) {
			const img = document.createElement('img');
			img.src = story.cover;
			img.alt = '';
			avatarEl.append(img);
		}
	}

	slideEl.replaceChildren();

	const card = el('div', 'sp-slide');

	const media = document.createElement('a');
	media.className = 'sp-slide__media';
	media.href = item.url;

	const img = document.createElement('img');
	img.className = 'sp-slide__img';
	img.src = item.image;
	img.alt = item.name;
	img.decoding = 'async';
	media.append(img);

	if (item.discount > 0) {
		media.append(el('span', 'sp-badge sp-badge--sale', `${item.discount}%- הנחה`));
	} else if (!item.inStock) {
		media.append(el('span', 'sp-badge sp-badge--muted', 'אזל מהמלאי'));
	}

	card.append(media);

	const body = el('div', 'sp-slide__body');

	const title = el('h3', 'sp-slide__title');
	const titleLink = el('a', '', item.name);
	titleLink.href = item.url;
	title.append(titleLink);
	body.append(title);

	const price = el('div', 'sp-slide__price');
	// WooCommerce generates this markup itself (<del>/<ins>/currency spans).
	price.innerHTML = item.priceHtml;
	body.append(price);

	const actions = el('div', 'sp-slide__actions');

	if (item.canAdd) {
		const add = el('button', 'sp-btn sp-btn--primary sp-btn--block');
		add.type = 'button';
		add.dataset.spAddToCart = '';
		add.dataset.productId = String(item.id);
		add.append(el('span', 'sp-btn__label', i18n.addToCart || 'הוספה לסל'));
		actions.append(add);
	}

	const view = el('a', 'sp-btn sp-btn--quiet sp-btn--block', 'לעמוד המוצר');
	view.href = item.url;
	actions.append(view);

	body.append(actions);
	card.append(body);
	slideEl.append(card);
}

/* ---------- playback ---------- */

function tick(timestamp) {
	if (!lastFrame) {
		lastFrame = timestamp;
	}
	const delta = timestamp - lastFrame;
	lastFrame = timestamp;

	if (!paused) {
		elapsed += delta;
	}

	const ratio = Math.min(1, elapsed / DURATION);
	const fill = barsEl && barsEl.querySelector('.is-active .sp-viewer__barFill');
	if (fill) {
		fill.style.transform = `scaleX(${ratio})`;
	}

	if (ratio >= 1) {
		next();
		return;
	}

	rafId = window.requestAnimationFrame(tick);
}

function play() {
	stop();
	elapsed = 0;
	lastFrame = 0;
	rafId = window.requestAnimationFrame(tick);
}

function stop() {
	if (rafId) {
		window.cancelAnimationFrame(rafId);
		rafId = null;
	}
}

function setPaused(value) {
	paused = value;
	if (viewer) {
		viewer.classList.toggle('is-paused', paused);
	}
	if (pauseBtn) {
		pauseBtn.setAttribute('aria-pressed', paused ? 'true' : 'false');
	}
}

function show() {
	renderBars();
	renderSlide();
	play();
}

function next() {
	const story = currentStory();
	if (!story) {
		return;
	}

	if (itemIndex < story.items.length - 1) {
		itemIndex += 1;
		show();
		return;
	}

	if (storyIndex < stories.length - 1) {
		storyIndex += 1;
		itemIndex = 0;
		markSeen(currentStory().id);
		show();
		return;
	}

	close();
}

function prev() {
	if (itemIndex > 0) {
		itemIndex -= 1;
		show();
		return;
	}

	if (storyIndex > 0) {
		storyIndex -= 1;
		itemIndex = 0;
		show();
	}
}

/* ---------- open / close ---------- */

function open(index) {
	if (!viewer || !stories[index]) {
		return;
	}

	lastFocused = document.activeElement;
	storyIndex = index;
	itemIndex = 0;

	viewer.hidden = false;
	void viewer.offsetWidth;
	viewer.classList.add('is-open');
	document.documentElement.classList.add('sp-locked');

	setPaused(false);
	markSeen(stories[index].id);
	show();

	releaseFocus = trapFocus(viewer);

	const closeBtn = viewer.querySelector('[data-sp-viewer-close]');
	if (closeBtn) {
		closeBtn.focus();
	}
}

function close() {
	if (!viewer || viewer.hidden) {
		return;
	}

	stop();

	if (releaseFocus) {
		releaseFocus();
		releaseFocus = null;
	}

	viewer.classList.remove('is-open');
	document.documentElement.classList.remove('sp-locked');

	window.setTimeout(() => {
		viewer.hidden = true;
		if (slideEl) {
			slideEl.replaceChildren();
		}
	}, 260);

	if (lastFocused && typeof lastFocused.focus === 'function') {
		lastFocused.focus();
	}
}

/* ---------- rail ---------- */

function initRail() {
	const rail = document.querySelector('[data-sp-rail]');
	if (!rail) {
		return;
	}

	const track = rail.querySelector('[data-sp-rail-track]');
	const prevBtn = rail.querySelector('[data-sp-rail-prev]');
	const nextBtn = rail.querySelector('[data-sp-rail-next]');
	if (!track) {
		return;
	}

	const step = () => Math.max(240, track.clientWidth * 0.7);

	// scrollLeft is negative in RTL, so scrollBy handles direction for us.
	const scrollByAmount = (amount) => track.scrollBy({ left: amount, behavior: 'smooth' });

	if (prevBtn) {
		prevBtn.addEventListener('click', () => scrollByAmount(rtl ? step() : -step()));
	}
	if (nextBtn) {
		nextBtn.addEventListener('click', () => scrollByAmount(rtl ? -step() : step()));
	}

	const updateNav = () => {
		// RTL reports scrollLeft as a negative offset, hence the abs().
		const max = track.scrollWidth - track.clientWidth;
		const pos = Math.abs(track.scrollLeft);
		rail.classList.toggle('is-start', pos <= 8);
		rail.classList.toggle('is-end', pos >= max - 8);
	};

	track.addEventListener('scroll', updateNav, { passive: true });
	window.addEventListener('resize', updateNav);

	// Bubble images change the track width as they decode, so re-measure once
	// everything has settled.
	window.addEventListener('load', updateNav);
	updateNav();
}

/* ---------- events ---------- */

function bindEvents() {
	document.addEventListener('click', (event) => {
		const bubble = event.target.closest('[data-sp-story-open]');
		if (bubble) {
			const index = Number(bubble.dataset.storyIndex);
			if (Number.isInteger(index) && stories[index]) {
				event.preventDefault();
				open(index);
			}
			return;
		}

		if (!viewer || viewer.hidden) {
			return;
		}

		if (event.target.closest('[data-sp-viewer-close]')) {
			event.preventDefault();
			close();
			return;
		}
		if (event.target.closest('[data-sp-viewer-pause]')) {
			event.preventDefault();
			setPaused(!paused);
			return;
		}
		if (event.target.closest('[data-sp-viewer-next]')) {
			event.preventDefault();
			next();
			return;
		}
		if (event.target.closest('[data-sp-viewer-prev]')) {
			event.preventDefault();
			prev();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (!viewer || viewer.hidden) {
			return;
		}

		if (event.key === 'Escape') {
			event.preventDefault();
			close();
			return;
		}
		if (event.key === ' ') {
			event.preventDefault();
			setPaused(!paused);
			return;
		}

		// In RTL, "forward" is leftwards.
		if (event.key === 'ArrowLeft') {
			event.preventDefault();
			(rtl ? next : prev)();
		} else if (event.key === 'ArrowRight') {
			event.preventDefault();
			(rtl ? prev : next)();
		}
	});

	if (!viewer) {
		return;
	}

	// Hold to pause, like a native story.
	const stage = viewer.querySelector('.sp-viewer__stage');
	if (stage) {
		let holdTimer = null;

		const startHold = () => {
			holdTimer = window.setTimeout(() => setPaused(true), 220);
		};
		const endHold = () => {
			window.clearTimeout(holdTimer);
			if (paused) {
				setPaused(false);
			}
		};

		stage.addEventListener('pointerdown', startHold);
		stage.addEventListener('pointerup', endHold);
		stage.addEventListener('pointercancel', endHold);
		stage.addEventListener('pointerleave', endHold);

		let startX = 0;
		stage.addEventListener(
			'touchstart',
			(event) => {
				startX = event.touches[0].clientX;
			},
			{ passive: true }
		);
		stage.addEventListener(
			'touchend',
			(event) => {
				const deltaX = event.changedTouches[0].clientX - startX;
				if (Math.abs(deltaX) < 45) {
					return;
				}
				const forward = rtl ? deltaX > 0 : deltaX < 0;
				(forward ? next : prev)();
			},
			{ passive: true }
		);
	}

	// Pause while the tab is hidden so a story does not silently run out.
	document.addEventListener('visibilitychange', () => {
		if (viewer && !viewer.hidden) {
			setPaused(document.hidden);
		}
	});
}

/**
 * Boot the stories rail and viewer.
 *
 * @return {void}
 */
export function initStories() {
	stories = readData();
	initRail();

	if (stories.length === 0) {
		return;
	}

	viewer = document.querySelector('[data-sp-viewer]');
	if (viewer) {
		barsEl = viewer.querySelector('[data-sp-viewer-bars]');
		slideEl = viewer.querySelector('[data-sp-viewer-slide]');
		categoryEl = viewer.querySelector('[data-sp-viewer-category]');
		positionEl = viewer.querySelector('[data-sp-viewer-position]');
		avatarEl = viewer.querySelector('[data-sp-viewer-avatar]');
		pauseBtn = viewer.querySelector('[data-sp-viewer-pause]');
	}

	paintSeen();
	bindEvents();
}
