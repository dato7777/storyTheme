/**
 * StoryPhone cinematic orbit banner — REBUILT.
 *
 * RULE 1: True ellipse via trig every frame (GSAP onUpdate).
 * RULE 2: Cutouts only — no cards (CSS enforces).
 * RULE 3: radiusX ≈ 44% of viewport width, centerX = section center.
 * RULE 4: Text z-index 100; products in safe zone fade/blur behind glass.
 *
 * Requires window.gsap (CDN). Transform/opacity only.
 */

const ORBIT_SECONDS = 22;
const TWO_PI = Math.PI * 2;

/**
 * @return {void}
 */
export function initCinema() {
	const root = document.querySelector('[data-sp-cinema]');
	if (!root || typeof window.gsap === 'undefined') {
		return;
	}

	const gsap = window.gsap;
	const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	const words = root.querySelectorAll('[data-sp-cinema-headline] .sp-cinema__word');
	const sub = root.querySelector('[data-sp-cinema-sub]');
	const cta = root.querySelector('[data-sp-cinema-cta]');
	const trust = root.querySelector('[data-sp-cinema-trust]');

	// Text intro always (even reduced motion settles visible).
	if (reduce) {
		gsap.set([words, sub, cta, trust], { opacity: 1, x: 0, y: 0 });
		root.classList.add('is-ready');
		return;
	}

	const isMobile = window.matchMedia('(max-width: 768px)').matches;
	const world = root.querySelector('[data-sp-cinema-world]');
	const products = Array.from(root.querySelectorAll('[data-sp-cinema-product]'));
	const particleHost = root.querySelector('[data-sp-cinema-particles]');
	const burstsHost = root.querySelector('[data-sp-cinema-bursts]');
	const streaks = Array.from(root.querySelectorAll('[data-sp-cinema-streak]'));
	const people = Array.from(root.querySelectorAll('[data-sp-cinema-person]'));
	const key = root.querySelector('[data-sp-cinema-key]');
	const fill = root.querySelector('[data-sp-cinema-fill]');

	const timelines = [];
	const state = { t: 0 }; // radians 0→2π
	const firedBurst = new Set();
	const prevXY = products.map(() => ({ x: 0, y: 0 }));

	/* ---------- lighting sweeps (coral key + teal fill) ---------- */
	if (key) {
		timelines.push(
			gsap.to(key, {
				xPercent: 55,
				yPercent: 40,
				rotation: 25,
				duration: 18,
				ease: 'sine.inOut',
				yoyo: true,
				repeat: -1,
			})
		);
	}
	if (fill) {
		timelines.push(
			gsap.to(fill, {
				xPercent: -45,
				yPercent: -35,
				rotation: -20,
				duration: 20,
				ease: 'sine.inOut',
				yoyo: true,
				repeat: -1,
			})
		);
	}

	/* ---------- dolly breath on world (not text) ---------- */
	if (world) {
		timelines.push(
			gsap.to(world, {
				scale: 1.04,
				duration: 20,
				ease: 'sine.inOut',
				yoyo: true,
				repeat: -1,
			})
		);
	}

	/* ---------- ambient particles ---------- */
	if (particleHost) {
		const count = isMobile ? 28 : 52;
		const frag = document.createDocumentFragment();
		const nodes = [];

		for (let i = 0; i < count; i += 1) {
			const el = document.createElement('span');
			el.className = 'sp-cinema__particle';
			const size = 2 + Math.random() * 5;
			el.style.width = `${size}px`;
			el.style.height = `${size}px`;
			el.style.left = `${Math.random() * 100}%`;
			el.style.top = `${Math.random() * 100}%`;
			el.style.opacity = String(0.2 + Math.random() * 0.5);
			if (Math.random() > 0.65) {
				el.style.filter = 'blur(1px)';
			}
			frag.appendChild(el);
			nodes.push(el);
		}
		particleHost.appendChild(frag);

		nodes.forEach((el) => {
			const duration = 9 + Math.random() * 14;
			const xDrift = (Math.random() - 0.5) * 160;
			timelines.push(
				gsap.to(el, {
					y: `-=${root.clientHeight + 120}`,
					x: xDrift,
					duration,
					ease: 'none',
					repeat: -1,
					delay: -Math.random() * duration,
					modifiers: {
						y: gsap.utils.unitize((y) =>
							gsap.utils.wrap(-50, root.clientHeight + 50, parseFloat(y))
						),
					},
				})
			);
		});
	}

	/* ---------- TRUE ELLIPSE ORBIT ---------- */
	/**
	 * Recalculate every product from trig. Center = section center.
	 * radiusX ≈ 44% of width so products reach both edges.
	 *
	 * @return {void}
	 */
	function layoutOrbit() {
		const w = root.clientWidth;
		const h = root.clientHeight;
		const centerX = w * 0.5;
		const centerY = h * 0.46;
		const radiusX = w * (isMobile ? 0.4 : 0.44);
		const radiusY = h * (isMobile ? 0.2 : 0.22);
		const n = products.length;
		const safeHalf = w * 0.19; // ~38% text column

		products.forEach((el, i) => {
			const baseAngle = (i / n) * TWO_PI;
			const angle = baseAngle + state.t;
			const x = centerX + radiusX * Math.cos(angle);
			const y = centerY + radiusY * Math.sin(angle) * 0.4;
			const depth = (Math.sin(angle) + 1) / 2; // 0 back → 1 front
			const z = Math.sin(angle);
			let scale = 0.6 + 0.5 * depth;
			let opacity = 0.35 + 0.65 * depth;
			let blur = (1 - depth) * 2.4;
			let zIndex = Math.round(10 + depth * 80); // max 90 < text 100

			// Text sits at z-index 100 — only soften items under the column, don't hide them.
			const inTextColumn = Math.abs(x - centerX) < safeHalf;
			if (inTextColumn) {
				opacity *= 0.72;
				blur = Math.max(blur, 0.8);
				zIndex = Math.min(zIndex, 50);
			}

			if (isMobile && depth < 0.28) {
				opacity *= 0.35;
			}

			const halfW = (el.offsetWidth || 160) * 0.5;
			const halfH = (el.offsetHeight || 180) * 0.5;

			// Always anchor from physical top-left (left:0), then translate into ellipse space.
			gsap.set(el, {
				left: 0,
				top: 0,
				right: 'auto',
				x: x - halfW,
				y: y - halfH,
				scale,
				opacity,
				zIndex,
				filter: blur > 0.2 ? `blur(${blur}px)` : 'none',
				force3D: true,
			});

			const glow = el.querySelector('.sp-cinema__glow');
			if (glow) {
				gsap.set(glow, {
					opacity: 0.2 + depth * 0.7,
					scale: 0.8 + depth * 0.5,
				});
			}

			// Motion trail — short ghost opposite velocity on fast front arc.
			const trail = el.querySelector('[data-sp-cinema-trail]');
			if (trail) {
				const px = prevXY[i].x;
				const py = prevXY[i].y;
				const vx = x - px;
				const vy = y - py;
				const speed = Math.hypot(vx, vy);
				const trailOn = depth > 0.72 && speed > 1.2 && !inTextColumn;
				if (trailOn) {
					gsap.set(trail, {
						opacity: 0.35,
						x: -vx * 1.8,
						y: -vy * 1.8,
						scale: 0.96,
					});
				} else {
					gsap.set(trail, { opacity: 0, x: 0, y: 0 });
				}
			}

			prevXY[i].x = x;
			prevXY[i].y = y;

			// Impact burst + lens flare when crossing dead-center front (sin ≈ 1).
			const front = Math.sin(angle);
			const wasFront = el.dataset.wasFront === '1';
			const isFront = front > 0.92;
			if (isFront && !wasFront && !firedBurst.has(i)) {
				firedBurst.add(i);
				window.setTimeout(() => firedBurst.delete(i), 900);
				spawnBurst(x, y);
				flashStreak();
			}
			el.dataset.wasFront = isFront ? '1' : '0';
		});
	}

	/**
	 * @param {number} x
	 * @param {number} y
	 * @return {void}
	 */
	function spawnBurst(x, y) {
		if (!burstsHost || isMobile) {
			return;
		}
		for (let i = 0; i < 7; i += 1) {
			const dot = document.createElement('span');
			dot.className = 'sp-cinema__burstDot';
			burstsHost.appendChild(dot);
			const ang = (i / 7) * TWO_PI + Math.random() * 0.4;
			const dist = 28 + Math.random() * 50;
			gsap.set(dot, { x, y, opacity: 1, scale: 1 });
			gsap.to(dot, {
				x: x + Math.cos(ang) * dist,
				y: y + Math.sin(ang) * dist,
				opacity: 0,
				scale: 0.2,
				duration: 0.55,
				ease: 'power2.out',
				onComplete: () => dot.remove(),
			});
		}
	}

	/**
	 * @return {void}
	 */
	function flashStreak() {
		const streak = streaks[Math.floor(Math.random() * streaks.length)];
		if (!streak) {
			return;
		}
		gsap.killTweensOf(streak);
		gsap.fromTo(
			streak,
			{ opacity: 0, scaleX: 0.15, xPercent: -20 },
			{
				opacity: 0.85,
				scaleX: 1,
				xPercent: 40,
				duration: 0.45,
				ease: 'power2.out',
				onComplete: () => {
					gsap.to(streak, { opacity: 0, duration: 0.35 });
				},
			}
		);
	}

	// Continuous angle drive — ease:none, seamless 0→2π wrap.
	const orbitTween = gsap.to(state, {
		t: TWO_PI,
		duration: ORBIT_SECONDS,
		ease: 'none',
		repeat: -1,
		onUpdate: layoutOrbit,
	});
	timelines.push(orbitTween);
	layoutOrbit();

	// Re-measure after cutout images/videos load (first paint often has 0 size).
	products.forEach((el) => {
		const media = el.querySelector('.sp-cinema__cutout');
		if (!media) {
			return;
		}
		if (media.tagName === 'VIDEO') {
			media.addEventListener('loadeddata', layoutOrbit, { once: true });
			try {
				media.play()?.catch(() => {});
			} catch {
				/* autoplay may be blocked — muted loop usually ok */
			}
			return;
		}
		if (!media.complete) {
			media.addEventListener('load', layoutOrbit, { once: true });
		}
	});

	window.addEventListener(
		'resize',
		() => {
			layoutOrbit();
		},
		{ passive: true }
	);

	// Independent micro float (doesn't replace orbit — additive on card content).
	products.forEach((el, i) => {
		const cutout = el.querySelector('.sp-cinema__cutout, .sp-cinema__ghost');
		if (!cutout) {
			return;
		}
		timelines.push(
			gsap.to(cutout, {
				y: -5 - (i % 3),
				rotation: -1.5 + (i % 4) * 0.8,
				duration: 2.4 + (i % 4) * 0.4,
				yoyo: true,
				repeat: -1,
				ease: 'sine.inOut',
				delay: i * 0.1,
			})
		);
	});

	/* ---------- people breath + mouse parallax ---------- */
	people.forEach((el, i) => {
		timelines.push(
			gsap.to(el, {
				scale: 1.035,
				duration: 3.2 + i,
				yoyo: true,
				repeat: -1,
				ease: 'sine.inOut',
			})
		);
	});

	if (!isMobile && people.length) {
		root.addEventListener(
			'mousemove',
			(event) => {
				const rect = root.getBoundingClientRect();
				const nx = (event.clientX - rect.left) / rect.width - 0.5;
				const ny = (event.clientY - rect.top) / rect.height - 0.5;
				people.forEach((el, i) => {
					const s = i === 0 ? 16 : -18;
					gsap.to(el, {
						x: nx * s,
						y: ny * s * 0.55,
						duration: 0.65,
						ease: 'power2.out',
						overwrite: 'auto',
					});
				});
			},
			{ passive: true }
		);
	}

	/* ---------- text intro (once) ---------- */
	const intro = gsap.timeline({
		defaults: { ease: 'power3.out' },
		onComplete: () => root.classList.add('is-ready'),
	});
	intro
		.to(words, { opacity: 1, x: 0, duration: 0.65, stagger: 0.07 })
		.to(sub, { opacity: 1, y: 0, duration: 0.5 }, '-=0.2')
		.to(cta, { opacity: 1, y: 0, duration: 0.45 }, '-=0.15')
		.to(trust, { opacity: 1, y: 0, duration: 0.4 }, '-=0.2');

	/* ---------- pause off-screen ---------- */
	const io = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				timelines.forEach((tl) => {
					if (!tl) return;
					if (entry.isIntersecting) tl.play();
					else tl.pause();
				});
			});
		},
		{ threshold: 0.1 }
	);
	io.observe(root);
}
