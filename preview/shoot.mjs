/**
 * Screenshot the preview across viewports and interaction states.
 *
 * Usage: node preview/shoot.mjs
 */

import { createReadStream, mkdirSync, statSync } from 'node:fs';
import { createServer } from 'node:http';
import { extname, join, normalize } from 'node:path';
import { fileURLToPath } from 'node:url';

import { chromium } from 'playwright';

const shots = fileURLToPath(new URL('./shots/', import.meta.url));
const root = fileURLToPath(new URL('..', import.meta.url));

mkdirSync(shots, { recursive: true });

/*
 * ES modules are blocked over file:// by the browser's CORS rules, which would
 * silently leave every script in this page unloaded. Serve the workspace over
 * HTTP so the preview runs the same code path the real site does.
 */
const types = {
	'.html': 'text/html; charset=utf-8',
	'.js': 'text/javascript; charset=utf-8',
	'.mjs': 'text/javascript; charset=utf-8',
	'.css': 'text/css; charset=utf-8',
	'.json': 'application/json; charset=utf-8',
	'.svg': 'image/svg+xml',
	'.png': 'image/png',
	'.jpg': 'image/jpeg',
	'.webp': 'image/webp',
};

const server = createServer((request, response) => {
	const path = join(root, normalize(decodeURIComponent(request.url.split('?')[0])));

	if (!path.startsWith(root)) {
		response.writeHead(403).end();
		return;
	}

	try {
		if (!statSync(path).isFile()) {
			throw new Error('not a file');
		}
		response.writeHead(200, { 'Content-Type': types[extname(path)] || 'application/octet-stream' });
		createReadStream(path).pipe(response);
	} catch {
		response.writeHead(404).end('not found');
	}
});

await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
const page_url = `http://127.0.0.1:${server.address().port}/preview/index.html`;
console.log(`Serving ${page_url}`);

const browser = await chromium.launch();
const errors = [];

/**
 * Open the preview at a given viewport.
 *
 * @param {number} width  Viewport width.
 * @param {number} height Viewport height.
 * @return {Promise<import('playwright').Page>} Ready page.
 */
async function openPage(width, height) {
	const context = await browser.newContext({
		viewport: { width, height },
		deviceScaleFactor: 2,
		locale: 'he-IL',
	});
	const page = await context.newPage();

	page.on('console', (message) => {
		if (message.type() === 'error') {
			errors.push(`[${width}px] ${message.text()}`);
		}
	});
	page.on('pageerror', (error) => errors.push(`[${width}px] ${error.message}`));

	await page.goto(page_url, { waitUntil: 'networkidle' });
	await page.waitForTimeout(1200);

	return page;
}

/**
 * Scroll the whole page so IntersectionObserver reveals fire, then pin
 * everything visible. Playwright's stitched full-page capture does not drive
 * the observer, so without this the lower sections photograph blank.
 *
 * @param {import('playwright').Page} page Page.
 * @return {Promise<void>}
 */
async function settle(page) {
	await page.evaluate(async () => {
		const step = window.innerHeight * 0.7;
		for (let y = 0; y < document.body.scrollHeight; y += step) {
			window.scrollTo(0, y);
			await new Promise((r) => setTimeout(r, 120));
		}
		window.scrollTo(0, 0);
	});
	await page.waitForTimeout(900);
}

/**
 * Force every reveal element into its final state.
 *
 * @param {import('playwright').Page} page Page.
 * @return {Promise<void>}
 */
async function pinReveals(page) {
	await page.evaluate(() => {
		document.querySelectorAll('[data-sp-reveal]').forEach((node) => node.classList.add('is-revealed'));
	});
	await page.waitForTimeout(500);
}

/* ---------- hero deck ---------- */

/*
 * The deck is the one element whose correctness is a matter of timing, so it
 * gets its own pass: a recorder installed before any page script runs logs
 * every face change, and a pip click replays the sweep for frame captures.
 */
const deckContext = await browser.newContext({
	viewport: { width: 1440, height: 960 },
	deviceScaleFactor: 2,
	locale: 'he-IL',
});

await deckContext.addInitScript(() => {
	window.__deckLog = [];
	const start = () => {
		const t0 = performance.now();
		const tick = () => {
			const faces = Array.from(document.querySelectorAll('[data-sp-deck-face]'));
			const active = faces.findIndex((face) => face.classList.contains('is-active'));
			const last = window.__deckLog[window.__deckLog.length - 1];
			if (!last || last.face !== active) {
				window.__deckLog.push({ face: active, at: Math.round(performance.now() - t0) });
			}
			if (performance.now() - t0 < 11000) {
				requestAnimationFrame(tick);
			}
		};
		tick();
	};
	document.addEventListener('DOMContentLoaded', start);
});

const deckPage = await deckContext.newPage();
deckPage.on('pageerror', (error) => errors.push(`[deck] ${error.message}`));
deckPage.on('console', (message) => {
	if (message.type() === 'error') errors.push(`[deck] ${message.text()}`);
});

await deckPage.goto(page_url, { waitUntil: 'networkidle' });
await deckPage.evaluate(() => {
	document.querySelectorAll('[data-sp-reveal]').forEach((node) => node.classList.add('is-revealed'));
});

// Wait for the state rather than for a duration: network idle lands at an
// unpredictable point relative to the deck's own 2.1s handover.
await deckPage.waitForSelector('.sp-pick--deal.is-active', { timeout: 8000 });
await deckPage.waitForTimeout(1200);

const deckBox = await deckPage.locator('.sp-hero__pick').boundingBox();
// A fixed clip, padded out, so the tilt never pushes the card out of frame.
const deckClip = deckBox
	? {
		x: Math.max(0, deckBox.x - 80),
		y: Math.max(0, deckBox.y - 50),
		width: Math.min(1440, deckBox.width + 160),
		height: deckBox.height + 100,
	}
	: null;

if (deckClip) {
	await deckPage.screenshot({ path: `${shots}deck-deal.png`, clip: deckClip });

	await deckPage.click('[data-sp-deck-pip][data-index="0"]');
	await deckPage.waitForSelector('.sp-pick--hot.is-active');
	await deckPage.waitForTimeout(1000);

	/*
	 * Park the pointer off the card first. Moving a real cursor onto the deck
	 * hands the tilt over to the pointer and cancels the sweep, which is the
	 * behaviour we want but makes the sweep impossible to photograph.
	 */
	await deckPage.mouse.move(1400, 920);
	await deckPage.waitForTimeout(300);

	await deckPage.evaluate(() => {
		const deck = document.querySelector('[data-sp-deck]');
		deck.classList.remove('is-sweeping');
		void deck.offsetWidth;
		deck.classList.add('is-sweeping');
	});

	/*
	 * Screenshots taken while the animation runs come back flat, so the three
	 * poses the sweep passes through are pinned explicitly instead. The
	 * numbers below are the sweep's own keyframe values.
	 */
	const poses = [
		['right', 11, -3.5, 97, 74],
		['left', -10, 3, 4, 22],
		['settled', 0, 0, 50, 0],
	];

	for (const [name, y, x, mx, my] of poses) {
		await deckPage.evaluate(([ty, tx, tmx, tmy]) => {
			const deck = document.querySelector('[data-sp-deck]');
			deck.classList.remove('is-sweeping');
			deck.style.transition = 'none';
			deck.style.setProperty('--sp-tilt-y', `${ty}deg`);
			deck.style.setProperty('--sp-tilt-x', `${tx}deg`);
			deck.style.setProperty('--sp-tilt-mx', `${tmx}%`);
			deck.style.setProperty('--sp-tilt-my', `${tmy}%`);
		}, [y, x, mx, my]);
		await deckPage.waitForTimeout(250);
		await deckPage.screenshot({ path: `${shots}deck-pose-${name}.png`, clip: deckClip });
	}

	await deckPage.evaluate(() => {
		const deck = document.querySelector('[data-sp-deck]');
		deck.style.transition = '';
		['--sp-tilt-x', '--sp-tilt-y', '--sp-tilt-mx', '--sp-tilt-my'].forEach((name) =>
			deck.style.removeProperty(name)
		);
	});

	// Objective read of the sweep: rotateY should swing positive, then
	// negative, then return to zero.
	const angles = await deckPage.evaluate(async () => {
		const deck = document.querySelector('[data-sp-deck]');
		deck.classList.remove('is-sweeping');
		void deck.offsetWidth;
		deck.classList.add('is-sweeping');

		const samples = [];
		const t0 = performance.now();
		while (performance.now() - t0 < 1750) {
			const y = getComputedStyle(deck).getPropertyValue('--sp-tilt-y').trim();
			samples.push(`${Math.round(performance.now() - t0)}ms ${y}`);
			await new Promise((r) => setTimeout(r, 150));
		}
		return samples;
	});
	console.log('\nSweep rotateY:', angles.join(' | '));
}

await deckPage.waitForTimeout(4000);
const deckLog = await deckPage.evaluate(() => window.__deckLog || []);
console.log('\nDeck face changes (ms from DOMContentLoaded):', JSON.stringify(deckLog));
console.log(
	'Deck countdown:',
	await deckPage.textContent('.sp-pick__timer').catch(() => 'missing')
);

await deckContext.close();

/* ---------- desktop ---------- */

const desktop = await openPage(1440, 960);

await settle(desktop);

await desktop.screenshot({ path: `${shots}desktop-hero.png` });
await pinReveals(desktop);
await desktop.screenshot({ path: `${shots}desktop-full.png`, fullPage: true });

// Stories viewer.
await desktop.click('[data-sp-story-open]');
await desktop.waitForTimeout(1100);
await desktop.screenshot({ path: `${shots}desktop-story.png` });
await desktop.keyboard.press('Escape');
await desktop.waitForTimeout(500);

// Command palette, empty then with results.
await desktop.click('[data-sp-search-open]');
await desktop.waitForTimeout(600);
await desktop.screenshot({ path: `${shots}desktop-palette-empty.png` });

await desktop.fill('[data-sp-palette-input]', 'אוזניות');
await desktop.waitForTimeout(900);
await desktop.screenshot({ path: `${shots}desktop-palette-results.png` });
await desktop.keyboard.press('Escape');
await desktop.waitForTimeout(400);

// Heat board in view.
await desktop.evaluate(() => document.querySelector('#sp-heat').scrollIntoView());
await desktop.waitForTimeout(900);
await desktop.screenshot({ path: `${shots}desktop-heat.png` });

// Deal section.
await desktop.evaluate(() => {
	const deal = document.querySelector('#sp-deal');
	if (deal) deal.scrollIntoView();
});
await desktop.waitForTimeout(800);
await desktop.screenshot({ path: `${shots}desktop-deal.png` });

/* ---------- mobile ---------- */

const mobile = await openPage(390, 844);

await settle(mobile);

await mobile.screenshot({ path: `${shots}mobile-hero.png` });
await pinReveals(mobile);
await mobile.screenshot({ path: `${shots}mobile-full.png`, fullPage: true });

await mobile.click('[data-sp-story-open]');
await mobile.waitForTimeout(1100);
await mobile.screenshot({ path: `${shots}mobile-story.png` });
await mobile.keyboard.press('Escape');
await mobile.waitForTimeout(400);

await mobile.click('.sp-iconbtn--search');
await mobile.waitForTimeout(500);
await mobile.fill('[data-sp-palette-input]', 'JBL');
await mobile.waitForTimeout(900);
await mobile.screenshot({ path: `${shots}mobile-palette.png` });
await mobile.keyboard.press('Escape');
await mobile.waitForTimeout(400);

await mobile.click('[data-sp-nav-toggle]');
await mobile.waitForTimeout(600);
await mobile.screenshot({ path: `${shots}mobile-nav.png` });

/* ---------- tablet ---------- */

const tablet = await openPage(834, 1112);
await settle(tablet);
await pinReveals(tablet);
await tablet.screenshot({ path: `${shots}tablet-full.png`, fullPage: true });

/* ---------- small phone ---------- */

const small = await openPage(360, 740);
await settle(small);
await pinReveals(small);
await small.screenshot({ path: `${shots}small-full.png`, fullPage: true });

// Guard against horizontal overflow, the classic RTL layout failure.
const overflow = await small.evaluate(() => {
	const bad = [];
	document.querySelectorAll('body *').forEach((node) => {
		const rect = node.getBoundingClientRect();
		if (rect.width > 0 && (rect.right > window.innerWidth + 2 || rect.left < -2)) {
			bad.push(`${node.tagName.toLowerCase()}.${String(node.className).split(' ')[0]}`);
		}
	});
	return [...new Set(bad)].slice(0, 12);
});

console.log('\nHorizontal overflow at 360px:', overflow.length ? overflow : 'none');

await browser.close();
server.close();

if (errors.length > 0) {
	console.log('\nConsole errors:');
	[...new Set(errors)].forEach((error) => console.log('  -', error));
} else {
	console.log('\nNo console errors.');
}

console.log(`Screenshots in ${shots}`);
