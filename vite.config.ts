import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { loadEnv } from 'vite';
import { defineConfig, lazyPlugins } from 'vite-plus';
import {
    parseThemes,
    themeCssEntries,
    themePath,
    themes,
} from './vite/plugins/themes';

// Tema yang dikompilasi; tema pertama menjadi bawaan dan target alias @theme.
const enabledThemes = parseThemes(
    process.env.VITE_THEMES ?? loadEnv('', process.cwd(), 'VITE_').VITE_THEMES,
);

export default defineConfig({
    resolve: {
        alias: {
            '@theme': themePath(enabledThemes[0]),
        },
    },
    plugins: lazyPlugins(() => [
        themes({ enabled: enabledThemes }),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.ts',
                ...themeCssEntries(enabledThemes),
            ],
            refresh: true,
            // Font di-self-host: diunduh saat build, disajikan dari origin sendiri.
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                // TemaModern (lihat config/themes.php → fonts).
                bunny('Plus Jakarta Sans', {
                    alias: 'jakarta',
                    weights: [400, 500, 600, 700, 800],
                    preload: [{ weight: 400 }, { weight: 700 }],
                    fallbacks: ['system-ui', 'sans-serif'],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            // Konfigurasi agent AI pihak ketiga (Impeccable, Boost): jangan diformat ulang.
            '.codex/**',
            '.gemini/**',
            '.impeccable/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
