/**
 * Presentational motion: pointer tilt, the hero pick deck, the deal countdown
 * and the hero's rotating search hint.
 *
 * Everything here checks prefers-reduced-motion and simply does nothing when
 * the visitor has asked for less movement.
 */

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

/* ---------- pointer tilt ---------- */

function initTilt() {
	if (reduced.matches) {
		return;
	}

	const targets = document.querySelectorAll('[data-sp-tilt]');
	if (targets.length === 0) {
		return;
	}

	// Fine pointers only: on touch this would fight with scrolling.
	if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
		return;
	}

	targets.forEach((card) => {
		let frame = null;

		const onMove = (event) => {
			if (frame) {
				return;
			}
			// A running keyframe animation outranks inline styles in the
			// cascade, so the autoplay sweep has to yield before the pointer
			// can move the card at all.
			card.classList.remove('is-sweeping');

			frame = window.requestAnimationFrame(() => {
				frame = null;
				const rect = card.getBoundingClientRect();
				const px = (event.clientX - rect.left) / rect.width - 0.5;
				const py = (event.clientY - rect.top) / rect.height - 0.5;

				card.style.setProperty('--sp-tilt-x', `${(-py * 7).toFixed(2)}deg`);
				card.style.setProperty('--sp-tilt-y', `${(px * 9).toFixed(2)}deg`);
				card.style.setProperty('--sp-tilt-mx', `${((px + 0.5) * 100).toFixed(1)}%`);
				card.style.setProperty('--sp-tilt-my', `${((py + 0.5) * 100).toFixed(1)}%`);
			});
		};

		const reset = () => {
			if (frame) {
				window.cancelAnimationFrame(frame);
				frame = null;
			}
			card.style.setProperty('--sp-tilt-x', '0deg');
			card.style.setProperty('--sp-tilt-y', '0deg');
		};

		card.addEventListener('pointermove', onMove);
		card.addEventListener('pointerleave', reset);
	});
}

/* ---------- hero pick deck ---------- */

// Length of the sp-deck-sweep keyframes.
const SWEEP_MS = 1600;
// The first hand-off is deliberately quick: the best seller is the hook, the
// deal is the reason to act, and the visitor should meet both immediately.
const FIRST_DWELL_MS = 2100;
const DWELL_MS = 6200;
// Must cover the face transform transition or a card can be hidden mid-turn.
const SWAP_MS = 800;

function initPickDeck() {
	const deck = document.querySelector('[data-sp-deck]');
	if (!deck) {
		return;
	}

	const faces = Array.from(deck.querySelectorAll('[data-sp-deck-face]'));
	if (faces.length === 0) {
		return;
	}

	deck.style.setProperty('--sp-sweep-duration', `${SWEEP_MS}ms`);

	/**
	 * Replay the establishing sweep. Removing the class and reading a layout
	 * property in between is what forces the animation to restart rather than
	 * be treated as still running.
	 */
	const sweep = () => {
		if (reduced.matches) {
			return;
		}
		deck.classList.remove('is-sweeping');
		void deck.offsetWidth;
		deck.classList.add('is-sweeping');
	};

	deck.addEventListener('animationend', (event) => {
		if (event.animationName === 'sp-deck-sweep') {
			// The final keyframe equals the resting values, so dropping the
			// class hands the variables back to the pointer without a jump.
			deck.classList.remove('is-sweeping');
		}
	});

	sweep();

	if (faces.length < 2) {
		return;
	}

	deck.classList.add('is-live');

	// A face stays painted for the length of its turn-away, so without inert a
	// Tab press during the swap could land on a card nobody can see.
	faces.forEach((face) => {
		if (!face.classList.contains('is-active')) {
			face.setAttribute('inert', '');
		}
	});

	const pips = Array.from(deck.querySelectorAll('[data-sp-deck-pip]'));
	let index = 0;
	let timer = null;
	let startedAt = 0;
	let remaining = 0;
	let paused = false;

	const syncPips = (duration) => {
		pips.forEach((pip, i) => {
			const on = i === index;
			pip.classList.remove('is-on');
			pip.setAttribute('aria-pressed', on ? 'true' : 'false');
			if (on) {
				pip.style.setProperty('--sp-dwell-duration', `${duration}ms`);
				void pip.offsetWidth;
				pip.classList.add('is-on');
			}
		});
	};

	const schedule = (ms) => {
		window.clearTimeout(timer);
		remaining = ms;
		startedAt = Date.now();
		timer = window.setTimeout(advance, ms);
	};

	const show = (nextIndex) => {
		if (nextIndex === index || !faces[nextIndex]) {
			return;
		}

		const current = faces[index];
		const next = faces[nextIndex];

		current.classList.remove('is-active');
		current.classList.add('is-leaving');
		current.setAttribute('inert', '');
		next.removeAttribute('inert');
		window.setTimeout(() => {
			// Guard against a fast double swap bringing this face back.
			if (!current.classList.contains('is-active')) {
				current.classList.remove('is-leaving');
			}
		}, SWAP_MS);

		next.classList.add('is-active');
		index = nextIndex;

		sweep();
	};

	function advance() {
		show((index + 1) % faces.length);
		syncPips(DWELL_MS);
		schedule(DWELL_MS);
	}

	const pause = () => {
		if (paused) {
			return;
		}
		paused = true;
		window.clearTimeout(timer);
		remaining = Math.max(0, remaining - (Date.now() - startedAt));
		deck.classList.add('is-paused');
	};

	const resume = () => {
		if (!paused) {
			return;
		}
		paused = false;
		deck.classList.remove('is-paused');
		schedule(remaining > 0 ? remaining : DWELL_MS);
	};

	// Reading or reaching for the card should never make it swap underfoot.
	deck.addEventListener('pointerenter', pause);
	deck.addEventListener('pointerleave', resume);
	deck.addEventListener('focusin', pause);
	deck.addEventListener('focusout', (event) => {
		if (!deck.contains(event.relatedTarget)) {
			resume();
		}
	});

	document.addEventListener('visibilitychange', () => {
		if (document.hidden) {
			pause();
		} else {
			resume();
		}
	});

	pips.forEach((pip) => {
		pip.addEventListener('click', () => {
			show(Number(pip.dataset.index));
			syncPips(DWELL_MS);
			schedule(DWELL_MS);
		});
	});

	syncPips(FIRST_DWELL_MS);
	schedule(FIRST_DWELL_MS);
}

/* ---------- countdown ---------- */

function startCountdown(node) {
	const deadline = Date.parse(node.dataset.spCountdown);
	if (!Number.isFinite(deadline)) {
		return;
	}

	const hours = node.querySelector('[data-sp-cd-h]');
	const minutes = node.querySelector('[data-sp-cd-m]');
	const seconds = node.querySelector('[data-sp-cd-s]');

	const pad = (value) => String(Math.max(0, value)).padStart(2, '0');

	const render = () => {
		const remaining = deadline - Date.now();

		if (remaining <= 0) {
			if (hours) hours.textContent = '00';
			if (minutes) minutes.textContent = '00';
			if (seconds) seconds.textContent = '00';
			node.classList.add('is-done');
			window.clearInterval(timer);
			return;
		}

		const total = Math.floor(remaining / 1000);
		if (hours) hours.textContent = pad(Math.floor(total / 3600));
		if (minutes) minutes.textContent = pad(Math.floor((total % 3600) / 60));
		if (seconds) seconds.textContent = pad(total % 60);
	};

	const timer = window.setInterval(render, 1000);
	render();
}

function initCountdown() {
	document.querySelectorAll('[data-sp-countdown]').forEach(startCountdown);
}

/* ---------- hero search hint ---------- */

function initTyper() {
	const node = document.querySelector('[data-sp-typer]');
	if (!node) {
		return;
	}

	const config = window.storyphonePages || {};
	const words = Array.isArray(config.searchHints) && config.searchHints.length
		? config.searchHints
		: ['iPhone 17 Pro', 'Galaxy S26', 'AirPods', 'מטען מהיר', 'שעון חכם'];

	if (reduced.matches) {
		node.textContent = words[0];
		return;
	}

	let wordIndex = 0;
	let charIndex = 0;
	let deleting = false;

	const step = () => {
		const word = words[wordIndex];

		if (deleting) {
			charIndex -= 1;
		} else {
			charIndex += 1;
		}

		node.textContent = word.slice(0, charIndex);

		let delay = deleting ? 45 : 90;

		if (!deleting && charIndex === word.length) {
			delay = 1600;
			deleting = true;
		} else if (deleting && charIndex === 0) {
			deleting = false;
			wordIndex = (wordIndex + 1) % words.length;
			delay = 320;
		}

		window.setTimeout(step, delay);
	};

	step();
}

/**
 * Boot all motion helpers.
 *
 * @return {void}
 */
export function initMotion() {
	initTilt();
	initPickDeck();
	initCountdown();
	initTyper();
}
