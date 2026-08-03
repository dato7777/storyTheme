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

/* ---------- buy-box short description (neon lightning points) ---------- */

/**
 * Rebuild the short description into a styled lead + neon feature list.
 *
 * @return {void}
 */
function initBuyReveal() {
	const shell = document.querySelector('[data-sp-pdp-lede]');
	const node = shell?.querySelector('[data-sp-pdp-reveal]') || document.querySelector('[data-sp-pdp-reveal]');
	if (!node) {
		return;
	}

	const rawText = node.textContent.replace(/\s+/g, ' ').trim();
	if (!rawText) {
		return;
	}

	const { leadHtml, sparks } = extractLedeParts(node);

	node.replaceChildren();
	shell?.classList.add('is-ready');

	if (leadHtml) {
		const lead = document.createElement('div');
		lead.className = 'sp-pdpLede__lead';
		lead.innerHTML = leadHtml;
		paintLeadAccents(lead);
		node.append(lead);
	}

	if (sparks.length > 0) {
		const list = document.createElement('ul');
		list.className = 'sp-pdpLede__points';
		list.setAttribute('role', 'list');

		sparks.forEach((html, index) => {
			const item = document.createElement('li');
			item.className = 'sp-pdpLede__point';
			item.style.setProperty('--i', String(index));

			const zap = document.createElement('span');
			zap.className = 'sp-pdpLede__zap';
			zap.setAttribute('aria-hidden', 'true');
			zap.innerHTML =
				'<span class="sp-pdpLede__zapBolt"></span>' +
				'<span class="sp-pdpLede__zapFlash"></span>';

			const text = document.createElement('span');
			text.className = 'sp-pdpLede__pointText';
			text.innerHTML = html;

			item.append(zap, text);
			list.append(item);
		});

		node.append(list);
	} else if (!leadHtml) {
		const lead = document.createElement('div');
		lead.className = 'sp-pdpLede__lead is-plain';
		node.append(lead);
		typePlainLead(lead, rawText);
		revealLede(shell, node);
		return;
	}

	revealLede(shell, node);
}

/**
 * Split admin short-description HTML into lead copy + bullet lines.
 *
 * @param {HTMLElement} node
 * @return {{ leadHtml: string, sparks: string[] }}
 */
function extractLedeParts(node) {
	const clone = node.cloneNode(true);
	const sparks = [];

	clone.querySelectorAll('li').forEach((li) => {
		const html = li.innerHTML.replace(/\s+/g, ' ').trim();
		if (html) {
			sparks.push(html);
		}
	});
	clone.querySelectorAll('ul, ol').forEach((list) => list.remove());

	// Soft-split br-only “lists” that editors paste without real <ul>.
	if (sparks.length === 0) {
		const brBlocks = [];
		clone.querySelectorAll('p').forEach((p) => {
			if (p.querySelector('br')) {
				const parts = p.innerHTML.split(/<br\s*\/?>/i);
				parts.forEach((part) => {
					const clean = part.replace(/\s+/g, ' ').trim();
					if (clean) {
						brBlocks.push(clean);
					}
				});
				p.remove();
			}
		});
		if (brBlocks.length >= 2) {
			sparks.push(...brBlocks);
		}
	}

	let leadHtml = clone.innerHTML
		.replace(/<p>\s*<\/p>/gi, '')
		.replace(/\s+/g, ' ')
		.trim();

	// Plain “• item” / “- item” lines pasted without a real list.
	if (sparks.length === 0 && leadHtml) {
		const plain = clone.textContent.replace(/\r/g, '').trim();
		const lines = plain
			.split(/\n+/)
			.map((line) => line.trim())
			.filter(Boolean);
		const bulletLines = lines.filter((line) => /^[-–—*•●▪]\s+/.test(line));
		if (bulletLines.length >= 2) {
			bulletLines.forEach((line) => {
				sparks.push(line.replace(/^[-–—*•●▪]\s+/, ''));
			});
			const intro = lines.filter((line) => !/^[-–—*•●▪]\s+/.test(line));
			leadHtml = intro.length
				? `<p>${intro.map((line) => escapeHtml(line)).join('<br>')}</p>`
				: '';
		}
	}

	return { leadHtml, sparks };
}

/**
 * @param {string} value
 * @return {string}
 */
function escapeHtml(value) {
	return value
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}

/**
 * Tint the first strong/em phrase and wrap the opening words for gradient.
 *
 * @param {HTMLElement} lead
 * @return {void}
 */
function paintLeadAccents(lead) {
	lead.querySelectorAll('strong, b').forEach((el, i) => {
		el.classList.add('sp-pdpLede__neon');
		if (i === 0) {
			el.classList.add('is-primary');
		}
	});
	lead.querySelectorAll('em, i').forEach((el) => {
		el.classList.add('sp-pdpLede__cool');
	});

	const firstP = lead.querySelector('p') || lead;
	if (firstP.querySelector('.sp-pdpLede__neon, .sp-pdpLede__cool')) {
		return;
	}

	const walker = document.createTreeWalker(firstP, NodeFilter.SHOW_TEXT);
	const textNode = walker.nextNode();
	if (!textNode || !textNode.textContent) {
		return;
	}

	const raw = textNode.textContent;
	const match = raw.match(/^(\s*.{0,42}?(?:[.!?…׃:]|\s|$))/u);
	if (!match) {
		return;
	}

	const head = match[1];
	const rest = raw.slice(head.length);
	const mark = document.createElement('span');
	mark.className = 'sp-pdpLede__neon is-primary';
	mark.textContent = head.trimEnd();
	const after = document.createTextNode((head.endsWith(' ') ? ' ' : '') + rest);
	textNode.replaceWith(mark, after);
}

/**
 * @param {HTMLElement} lead
 * @param {string} text
 * @return {void}
 */
function typePlainLead(lead, text) {
	if (reduced.matches) {
		lead.textContent = text;
		return;
	}

	lead.classList.add('is-typing');
	const stream = document.createElement('span');
	stream.className = 'sp-typeStream';
	const caret = document.createElement('span');
	caret.className = 'sp-typeCaret';
	caret.setAttribute('aria-hidden', 'true');
	lead.append(stream, caret);

	const chars = Array.from(text);
	const step = Math.max(12, Math.min(28, 1100 / Math.max(chars.length, 1)));
	let i = 0;

	const tick = () => {
		if (i >= chars.length) {
			lead.classList.remove('is-typing');
			caret.remove();
			return;
		}
		stream.textContent += chars[i];
		i += 1;
		window.setTimeout(tick, step);
	};

	window.setTimeout(tick, 120);
}

/**
 * @param {HTMLElement|null|undefined} shell
 * @param {HTMLElement} node
 * @return {void}
 */
function revealLede(shell, node) {
	const target = shell || node;
	target.classList.add('is-built');

	const charge = () => {
		target.classList.add('is-lit');
		node.querySelectorAll('.sp-pdpLede__point').forEach((point, index) => {
			window.setTimeout(() => {
				point.classList.add('is-struck');
			}, reduced.matches ? 0 : 120 + index * 180);
		});
	};

	if (reduced.matches) {
		charge();
		return;
	}

	window.requestAnimationFrame(() => {
		window.requestAnimationFrame(charge);
	});
}

/* ---------- YouTube (muted autoplay embed + watch fallback) ---------- */

function initProductVideo() {
	const shell = document.querySelector('[data-sp-yt]');
	if (!shell) {
		return;
	}

	const id = shell.dataset.spYt;
	if (!id) {
		return;
	}

	const title = shell.dataset.spYtTitle || 'YouTube video';
	const watchUrl = `https://www.youtube.com/watch?v=${encodeURIComponent(id)}`;
	const play = shell.querySelector('[data-sp-yt-play]');
	let mounted = false;

	const buildEmbedSrc = () => {
		const origin = encodeURIComponent(window.location.origin);
		const params = new URLSearchParams({
			autoplay: '1',
			mute: '1',
			playsinline: '1',
			rel: '0',
			modestbranding: '1',
			controls: '1',
			enablejsapi: '1',
			origin,
		});
		return `https://www.youtube.com/embed/${encodeURIComponent(id)}?${params.toString()}`;
	};

	const mountPlayer = () => {
		if (mounted) {
			return;
		}
		mounted = true;

		const frame = document.createElement('iframe');
		frame.className = 'sp-pdpVideo__frame';
		frame.src = buildEmbedSrc();
		frame.title = title;
		frame.allow =
			'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
		frame.allowFullscreen = true;
		frame.setAttribute('allowfullscreen', '');
		// Keep a normal referrer so YouTube is less likely to trip bot checks.
		frame.referrerPolicy = 'origin-when-cross-origin';
		frame.loading = 'eager';

		shell.append(frame);
		shell.classList.add('is-playing');
		play?.setAttribute('hidden', '');
		shell.querySelector('[data-sp-yt-poster]')?.setAttribute('hidden', '');
	};

	play?.addEventListener('click', (event) => {
		event.preventDefault();
		mountPlayer();
	});

	// Autoplay as soon as the video slot enters the viewport (muted — required
	// by browser + YouTube policy). Caption still opens the full watch page.
	if ('IntersectionObserver' in window) {
		const io = new IntersectionObserver(
			(entries) => {
				if (entries.some((entry) => entry.isIntersecting)) {
					mountPlayer();
					io.disconnect();
				}
			},
			{ rootMargin: '120px 0px', threshold: 0.2 }
		);
		io.observe(shell);
	} else {
		mountPlayer();
	}
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
