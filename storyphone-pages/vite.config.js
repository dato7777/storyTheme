import { defineConfig } from 'vite';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// Output names are stable (main.js / main.css) because PHP enqueues them by
// path and cache-busts with filemtime().
export default defineConfig({
	build: {
		outDir: 'build',
		emptyOutDir: true,
		manifest: false,
		cssCodeSplit: false,
		rollupOptions: {
			input: path.resolve(__dirname, 'src/main.js'),
			output: {
				entryFileNames: 'main.js',
				assetFileNames: (assetInfo) => {
					if (assetInfo.name && assetInfo.name.endsWith('.css')) {
						return 'main.css';
					}
					return 'assets/[name][extname]';
				},
				chunkFileNames: 'chunks/[name].js',
			},
		},
	},
});
