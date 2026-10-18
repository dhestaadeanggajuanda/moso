import { router } from '@inertiajs/vue3';

/**
 * Pasang widget aksesibilitas (packages/a11y-widget) hanya di halaman situs
 * publik. Widget ditempel ke <body>, di luar pohon Inertia, sehingga tetap ada
 * selama navigasi dan tidak ikut terkena filter kontras halaman. Saat pindah ke
 * halaman lain (mis. kembali ke login), widget dilepas dan tampilan halaman
 * dipulihkan. Hanya di browser: paket dimuat dinamis supaya tidak ikut SSR.
 */
function mountForComponent(component: string | undefined): void {
    if (component?.startsWith('site/')) {
        void import('@moso/a11y-widget').then(({ mount }) =>
            mount({ lang: 'id', position: 'bottom-right' }),
        );

        return;
    }

    if (document.querySelector('a11y-widget')) {
        void import('@moso/a11y-widget').then(({ unmount }) => unmount());
    }
}

function initialComponent(): string | undefined {
    const data = document.querySelector<HTMLScriptElement>(
        'script[data-page="app"]',
    )?.textContent;

    try {
        return data
            ? (JSON.parse(data) as { component?: string }).component
            : undefined;
    } catch {
        return undefined;
    }
}

export function initializeA11yWidget(): void {
    if (typeof window === 'undefined') {
        return;
    }

    mountForComponent(initialComponent());
    router.on('navigate', (event) =>
        mountForComponent(event.detail.page.component),
    );
}
