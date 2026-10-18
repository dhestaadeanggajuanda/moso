import fs from 'node:fs';
import path from 'node:path';
import type { Plugin, ViteDevServer } from 'vite';

/**
 * Pluggable Themes untuk Vite.
 *
 * Hanya tema yang tercantum di VITE_THEMES (dipisah koma) yang dikompilasi.
 * Plugin ini membuat virtual module `virtual:themes` berisi import lazy untuk
 * komponen tema tersebut; tema lain tidak pernah direferensikan sehingga
 * tidak masuk bundle JS maupun CSS.
 */

const VIRTUAL_ID = 'virtual:themes';
const RESOLVED_ID = `\0${VIRTUAL_ID}`;
const THEMES_DIR = 'resources/js/Themes';
const SECTIONS = ['Layouts', 'Components', 'Views'] as const;
const THEME_NAME = /^[A-Z][A-Za-z0-9]*$/;
const COMPONENT_FILE = /^([A-Z][A-Za-z0-9]*)\.vue$/;

export function parseThemes(value: string | undefined): string[] {
    const names = (value ?? '')
        .split(',')
        .map((name) => name.trim())
        .filter(Boolean);

    for (const name of names) {
        if (!THEME_NAME.test(name)) {
            throw new Error(
                `[themes] Nama tema "${name}" tidak valid (PascalCase, huruf/angka).`,
            );
        }
    }

    return names.length > 0 ? [...new Set(names)] : ['TemaModern'];
}

export function themePath(name: string, root = process.cwd()): string {
    return path.resolve(root, THEMES_DIR, name);
}

/**
 * Entry CSS tiap tema, untuk input laravel-vite-plugin.
 */
export function themeCssEntries(names: string[]): string[] {
    return names.map((name) => `${THEMES_DIR}/${name}/theme.css`);
}

function componentFiles(directory: string): string[] {
    if (!fs.existsSync(directory)) {
        return [];
    }

    return fs
        .readdirSync(directory)
        .filter((file) => COMPONENT_FILE.test(file))
        .sort();
}

function generateModule(root: string, names: string[]): string {
    const lines = [
        `export const enabledThemes = ${JSON.stringify(names)};`,
        `export const defaultTheme = ${JSON.stringify(names[0])};`,
        'export const themes = {',
    ];

    for (const name of names) {
        lines.push(`  ${JSON.stringify(name)}: {`);

        for (const section of SECTIONS) {
            lines.push(`    ${section}: {`);

            for (const file of componentFiles(
                path.join(themePath(name, root), section),
            )) {
                const component = file.replace(/\.vue$/, '');
                const importPath = `/${THEMES_DIR}/${name}/${section}/${file}`;
                lines.push(
                    `      ${JSON.stringify(component)}: () => import(${JSON.stringify(importPath)}),`,
                );
            }

            lines.push('    },');
        }

        lines.push('  },');
    }

    lines.push('};');

    return lines.join('\n');
}

export function themes(options: { enabled: string[] }): Plugin {
    let root = process.cwd();

    return {
        name: 'moso-themes',
        enforce: 'pre',

        configResolved(config) {
            root = config.root;

            for (const name of options.enabled) {
                if (!fs.existsSync(themePath(name, root))) {
                    throw new Error(
                        `[themes] Folder tema "${THEMES_DIR}/${name}" tidak ditemukan (cek VITE_THEMES).`,
                    );
                }
            }
        },

        resolveId(id) {
            return id === VIRTUAL_ID ? RESOLVED_ID : undefined;
        },

        load(id) {
            return id === RESOLVED_ID
                ? generateModule(root, options.enabled)
                : undefined;
        },

        configureServer(server: ViteDevServer) {
            const themesRoot = path.resolve(root, THEMES_DIR);

            // Komponen tema baru/dihapus: bangun ulang virtual module.
            const refresh = (file: string): void => {
                if (!file.startsWith(themesRoot) || !file.endsWith('.vue')) {
                    return;
                }

                const module = server.moduleGraph.getModuleById(RESOLVED_ID);

                if (module) {
                    server.moduleGraph.invalidateModule(module);
                }

                server.ws.send({ type: 'full-reload' });
            };

            server.watcher.on('add', refresh);
            server.watcher.on('unlink', refresh);
        },
    };
}
