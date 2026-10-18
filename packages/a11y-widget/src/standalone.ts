/**
 * Entri build IIFE untuk aplikasi tanpa bundler:
 *
 *   <script src="/a11y-widget.iife.js" data-lang="id" data-position="bottom-right" defer></script>
 *
 * Widget langsung dipasang saat dokumen siap. API yang sama tersedia di
 * `window.MosoA11yWidget` (mount, define, A11yWidget).
 */
import { mount } from './index';
import type { MountOptions } from './index';

export * from './index';

const script = document.currentScript as HTMLScriptElement | null;

function autoMount(): void {
    if (script?.dataset.autoMount === 'false') {
        return;
    }

    mount({
        lang: script?.dataset.lang === 'en' ? 'en' : 'id',
        position:
            script?.dataset.position === 'bottom-left'
                ? 'bottom-left'
                : 'bottom-right',
        storageKey: script?.dataset.storageKey || undefined,
    } satisfies MountOptions);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoMount, { once: true });
} else {
    autoMount();
}
