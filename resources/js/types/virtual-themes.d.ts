/**
 * Virtual module dari vite/plugins/themes.ts: hanya berisi tema yang
 * tercantum di VITE_THEMES.
 */
declare module 'virtual:themes' {
    import type { Component } from 'vue';

    export type ComponentLoader = () => Promise<{ default: Component }>;

    export type ThemeModules = {
        Layouts: Record<string, ComponentLoader>;
        Components: Record<string, ComponentLoader>;
        Views: Record<string, ComponentLoader>;
    };

    export const enabledThemes: string[];
    export const defaultTheme: string;
    export const themes: Record<string, ThemeModules>;
}
