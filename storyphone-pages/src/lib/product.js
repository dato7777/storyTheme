/**
 * Single-product interactions: gallery, quantity, variations, related reel,
 * and the buy-box text reveal.
 */

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

const SWEEP_MS = 1600;
const SLIDE_MS = 2000;

/**
 * Boot PDP modules when the product template is present.
 *
 * @return {void}
 */
export function initProduct() {
	if (!document.body.classList.contains('sp-page--product')) {
		return;
	}

	initGallery();
	initBuyReveal();
	initProductVideo();
	initQuantity();
	initVariations();
	initRelatedReel();
}

/* ---------- gallery ---------- */

function initGallery() {
	const root = document.querySelector('[data-sp-gallery]');
	if (!root) {
		return;
	}

	const stage = root.querySelector('[data-sp-gallery-stage]');
	const images = Array.from(root.querySelectorAll('[data-sp-gallery-img]'));
	const thumbs = Array.from(root.querySelectorAll('[data-sp-gallery-thumb]'));

	if (stage && !reduced.matches) {
		stage.addEventListener('animationend', (event) => {
			if (event.animationName === 'sp-pdp-sweep') {
				stage.classList.remove('is-sweeping');
			}
		});

		// Pointer tilt wins over the keyframe sweep the moment someone aims.
		stage.addEventListener(
			'pointermove',
			() => {
				stage.classList.remove('is-sweeping');
			},
			{ once: true }
		);
	} else if (stage) {
		stage.classList.remove('is-sweeping');
	}

	if (images.length < 2) {
		return;
	}

	let index = 0;
	let timer = null;
	let paused = false;

	const show = (nextIndex, { manual = false } = {}) => {
		if (nextIndex === index || !images[nextIndex]) {
			return;
		}

		const current = images[index];
		const next = images[nextIndex];

		current.classList.remove('is-active');
		current.classList.add('is-leaving');
		window.setTimeout(() => {
			if (!current.classList.contains('is-active')) {
				current.classList.remove('is-leaving');
				current.hidden = true;
			}
		}, 480);

		next.hidden = false;
		void next.offsetWidth;
		next.classList.add('is-active');
		index = nextIndex;

		thumbs.forEach((thumb) => {
			const on = Number(thumb.dataset.index) === index;
			thumb.classList.toggle('is-active', on);
			thumb.setAttribute('aria-selected', on ? 'true' : 'false');
		});

		if (manual) {
			schedule();
		}
	};

	const advance = () => {
		show((index + 1) % images.length);
		schedule();
	};

	const schedule = () => {
		window.clearTimeout(timer);
		if (paused || reduced.matches || document.hidden) {
			return;
		}
		timer = window.setTimeout(advance, SLIDE_MS);
	};

	thumbs.forEach((thumb) => {
		thumb.addEventListener('click', () => {
			show(Number(thumb.dataset.index), { manual: true });
		});
	});

	root.addEventListener('pointerenter', () => {
		paused = true;
		window.clearTimeout(timer);
	});
	root.addEventListener('pointerleave', () => {
		paused = false;
		schedule();
	});

	document.addEventListener('visibilitychange', () => {
		if (document.hidden) {
			window.clearTimeout(timer);
		} else if (!paused) {
			schedule();
		}
	});

	// Let the establishing sweep finish before the first hand-off.
	window.setTimeout(schedule, reduced.matches ? 0 : SWEEP_MS);
}

/* ---------- buy-box letter typing ---------- */

function initBuyReveal() {
	const node = document.querySelector('[data-sp-pdp-reveal]');
	if (!node) {
		return;
	}

	const text = node.textContent.replace(/\s+/g, ' ').trim();
	if (!text) {
		return;
	}

	if (reduced.matches) {
		node.classList.add('is-revealed');
		return;
	}

	node.textContent = '';
	node.classList.add('is-typing');

	const chars = Array.from(text);
	const stream = document.createElement('span');
	stream.className = 'sp-typeStream';
	const caret = document.createElement('span');
	caret.className = 'sp-typeCaret';
	caret.setAttribute('aria-hidden', 'true');
	node.append(stream, caret);

	// Cap total runtime ~1.1s so longer blurbs still feel snappy.
	const step = Math.max(12, Math.min(28, 1100 / Math.max(chars.length, 1)));
	let i = 0;

	const tick = () => {
		if (i >= chars.length) {
			node.classList.remove('is-typing');
			node.classList.add('is-revealed');
			caret.remove();
			return;
		}
		stream.textContent += chars[i];
		i += 1;
		window.setTimeout(tick, step);
	};

	window.setTimeout(tick, 160);
}

/* ---------- YouTube placeholder (click-to-play) ---------- */

function initProductVideo() {
	const shell = document.querySelector('[data-sp-yt]');
	if (!shell) {
		return;
	}

	const id = shell.dataset.spYt;
	const play = shell.querySelector('[data-sp-yt-play]');
	if (!id || !play) {
		return;
	}

	const mount = () => {
		if (shell.dataset.spYtReady) {
			return;
		}
		shell.dataset.spYtReady = '1';

		const iframe = document.createElement('iframe');
		iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(id)}?autoplay=1&rel=0&modestbranding=1`;
		iframe.title = shell.getAttribute('data-sp-yt-title') || 'YouTube';
		iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
		iframe.allowFullscreen = true;
		iframe.loading = 'lazy';
		iframe.referrerPolicy = 'strict-origin-when-cross-origin';
		iframe.className = 'sp-pdpVideo__frame';

		shell.innerHTML = '';
		shell.append(iframe);
		shell.classList.add('is-playing');
	};

	play.addEventListener('click', mount);
}

/* ---------- quantity ---------- */

function initQuantity() {
	document.querySelectorAll('[data-sp-qty-wrap]').forEach((wrap) => {
		const input = wrap.querySelector('[data-sp-qty]');
		if (!input) {
			return;
		}

		wrap.addEventListener('click', (event) => {
			const btn = event.target.closest('[data-sp-qty-step]');
			if (!btn) {
				return;
			}
			const delta = Number(btn.dataset.spQtyStep);
			const next = Math.min(99, Math.max(1, (Number(input.value) || 1) + delta));
			input.value = String(next);
		});

		input.addEventListener('change', () => {
			input.value = String(Math.min(99, Math.max(1, Number(input.value) || 1)));
		});
	});
}

/* ---------- variations ---------- */

function initVariations() {
	const select = document.querySelector('[data-sp-variation]');
	const buy = document.querySelector('[data-sp-buy]');
	if (!select || !buy) {
		return;
	}

	const button = buy.querySelector('[data-sp-add-to-cart]');
	if (!button) {
		return;
	}

	const sync = () => {
		const id = select.value;
		button.dataset.productId = id || '';
		button.disabled = !id;
	};

	select.addEventListener('change', sync);
	sync();
}

/* ---------- related reel ---------- */

function initRelatedReel() {
	const reel = document.querySelector('[data-sp-reel]');
	if (!reel) {
		return;
	}

	const track = reel.querySelector('[data-sp-reel-track]');
	const cards = Array.from(reel.querySelectorAll('[data-sp-reel-card]'));
	const prev = reel.querySelector('[data-sp-reel-prev]');
	const next = reel.querySelector('[data-sp-reel-next]');

	if (!track || cards.length === 0) {
		return;
	}

	const updateNav = () => {
		const max = track.scrollWidth - track.clientWidth;
		const left = Math.abs(track.scrollLeft);
		reel.classList.toggle('is-start', left <= 8);
		reel.classList.toggle('is-end', left >= max - 8);
	};

	const scrollByDir = (dir) => {
		const amount = Math.max(220, track.clientWidth * 0.7) * dir;
		track.scrollBy({ left: amount * (document.documentElement.dir === 'rtl' ? -1 : 1), behavior: 'smooth' });
	};

	prev?.addEventListener('click', () => scrollByDir(-1));
	next?.addEventListener('click', () => scrollByDir(1));
	track.addEventListener('scroll', updateNav, { passive: true });
	window.addEventListener('resize', updateNav);
	window.addEventListener('load', updateNav);
	updateNav();

	if (reduced.matches || cards.length < 2) {
		return;
	}

	let index = 0;
	const light = () => {
		cards.forEach((card) => card.classList.remove('is-lit'));
		const card = cards[index % cards.length];
		void card.offsetWidth;
		card.classList.add('is-lit');

		const rect = card.getBoundingClientRect();
		const trackRect = track.getBoundingClientRect();
		if (rect.left < trackRect.left || rect.right > trackRect.right) {
			card.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
		}

		index = (index + 1) % cards.length;
	};

	light();
	window.setInterval(light, 2000);
}
