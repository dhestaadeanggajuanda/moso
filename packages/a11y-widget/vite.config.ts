import { readFile } from 'node:fs/promises';
import { basename, dirname, join } from 'node:path';
import type { Plugin } from 'vite';
import { defineConfig } from 'vitest/config';

/**
 * Mode pustaka Vite selalu menyisipkan aset `?url` sebagai data URI, sehingga
 * font (sekitar 150 KB) ikut terunduh walau tidak dipakai. Plugin ini menulis
 * font ke `dist/fonts/` dan menghitung URL-nya relatif terhadap berkas skrip,
 * jadi font baru diunduh saat pengunjung memilihnya.
 */
function separateFonts(): Plugin {
    const licensedPackages = new Set<string>();

    return {
        name: 'a11y-widget-separate-fonts',
        apply: 'build',
        enforce: 'pre',
        async load(id) {
            if (!id.endsWith('.woff2?url')) {
                return null;
            }

            const file = id.slice(0, -'?url'.length);
            // Lisensi SIL OFL 1.1 wajib ikut setiap salinan font.
            const fontPackage = dirname(dirname(file));

            if (!licensedPackages.has(fontPackage)) {
                licensedPackages.add(fontPackage);
                this.emitFile({
                    type: 'asset',
                    fileName: `fonts/LICENSE-${basename(fontPackage)}.txt`,
                    source: await readFile(join(fontPackage, 'LICENSE')),
                });
            }

            const referenceId = this.emitFile({
                type: 'asset',
                fileName: `fonts/${basename(file)}`,
                source: await readFile(file),
            });

            // Bundler mengganti placeholder ini sesuai format: import.meta.url
            // untuk ESM, document.currentScript untuk IIFE.
            return `export default import.meta.ROLLUP_FILE_URL_${referenceId};`;
        },
    };
}

/** Build pustaka: ESM (untuk bundler) dan IIFE (satu tag <script>). */
export default defineConfig({
    // Base relatif: URL font dihitung dari lokasi skrip, di mana pun dipasang.
    base: './',
    plugins: [separateFonts()],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        lib: {
            entry: 'src/standalone.ts',
            name: 'MosoA11yWidget',
            formats: ['es', 'iife'],
            fileName: (format) =>
                format === 'es' ? 'a11y-widget.js' : 'a11y-widget.iife.js',
        },
    },
    test: {
        environment: 'happy-dom',
        include: ['tests/**/*.test.ts'],
        restoreMocks: true,
    },
});
