/**
 * Presentational motion: pointer tilt, the deal countdown and the hero's
 * rotating search hint.
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

/* ---------- countdown ---------- */

function initCountdown() {
	const node = document.querySelector('[data-sp-countdown]');
	if (!node) {
		return;
	}

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
	initCountdown();
	initTyper();
}
