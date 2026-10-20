import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeA11yWidget } from '@/lib/a11yWidget';
import { initializeFlashToast } from '@/lib/flashToast';

const fallbackAppName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    // Nama dari APP_NAME instance (prop bersama `name`), bukan dari build,
    // agar satu image bisa melayani beberapa dinas.
    title: (title, page) => {
        const appName =
            typeof page.props.name === 'string' && page.props.name !== ''
                ? page.props.name
                : fallbackAppName;

        return title ? `${title} - ${appName}` : appName;
    },
    layout: (name) => {
        switch (true) {
            // Situs publik memakai layout dari tema aktif (Themes/{Tema}/Layouts).
            case name.startsWith('site/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.use(createPinia());

        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// Widget aksesibilitas di situs publik (pelengkap, bukan pengganti tema yang patuh WCAG)...
initializeA11yWidget();
