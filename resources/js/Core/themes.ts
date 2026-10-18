import type { Component } from 'vue';
import { defineAsyncComponent } from 'vue';
import { defaultTheme, themes } from 'virtual:themes';

export type ThemeSection = 'Layouts' | 'Components' | 'Views';

const cache = new Map<string, Component>();

/**
 * Nama tema yang benar-benar dikompilasi; selain itu jatuh ke tema bawaan.
 */
export function resolveThemeName(name?: string | null): string {
    return name && name in themes ? name : defaultTheme;
}

/**
 * Komponen tema (dimuat lazy), atau null bila tema tidak menyediakannya.
 */
export function themeComponent(
    theme: string | null | undefined,
    section: ThemeSection,
    name: string,
): Component | null {
    const themeName = resolveThemeName(theme);
    const key = `${themeName}/${section}/${name}`;
    const cached = cache.get(key);

    if (cached) {
        return cached;
    }

    const loader = themes[themeName]?.[section]?.[name];

    if (!loader) {
        return null;
    }

    const component = defineAsyncComponent(loader);
    cache.set(key, component);

    return component;
}
