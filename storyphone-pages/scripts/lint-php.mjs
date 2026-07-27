/**
 * PHP syntax check.
 *
 * The `php` binary is not always available on a front-end workstation, so this
 * runs the same grammar through php-parser instead. It catches syntax errors
 * before a file ever reaches WordPress, where a parse error is a white screen.
 *
 * Usage: node scripts/lint-php.mjs
 */

import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

import Engine from 'php-parser';

const root = fileURLToPath(new URL('..', import.meta.url));
const skip = new Set(['node_modules', 'build', '.git', 'vendor']);

const parser = new Engine({
	parser: { extractDoc: false, suppressErrors: false },
	ast: { withPositions: true },
});

/**
 * Collect every PHP file under a directory.
 *
 * @param {string} dir Directory to walk.
 * @return {string[]} Absolute file paths.
 */
function collect(dir) {
	const found = [];

	for (const entry of readdirSync(dir)) {
		if (skip.has(entry)) {
			continue;
		}

		const path = join(dir, entry);

		if (statSync(path).isDirectory()) {
			found.push(...collect(path));
		} else if (entry.endsWith('.php')) {
			found.push(path);
		}
	}

	return found;
}

const files = collect(root);
let failures = 0;

for (const file of files) {
	const source = readFileSync(file, 'utf8');

	try {
		const ast = parser.parseCode(source, file);
		const errors = ast.errors || [];

		if (errors.length > 0) {
			failures += 1;
			console.error(`\n✗ ${relative(root, file)}`);
			errors.forEach((error) => {
				console.error(`  line ${error.line}: ${error.message}`);
			});
		}
	} catch (error) {
		failures += 1;
		console.error(`\n✗ ${relative(root, file)}`);
		console.error(`  ${error.message}`);
	}
}

if (failures > 0) {
	console.error(`\n${failures} file(s) with syntax errors.\n`);
	process.exit(1);
}

console.log(`✓ ${files.length} PHP files parsed cleanly.`);
