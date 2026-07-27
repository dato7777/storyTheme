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
