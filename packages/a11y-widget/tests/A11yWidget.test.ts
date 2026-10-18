import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { A11yWidget } from '../src';
import { DEFAULT_STORAGE_KEY, mount, unmount } from '../src';
import { sanitize } from '../src/preferences';

const html = document.documentElement;

function shadow(widget: A11yWidget): ShadowRoot {
    if (!widget.shadowRoot) {
        throw new Error('Shadow root tidak ada.');
    }

    return widget.shadowRoot;
}

function button(widget: A11yWidget, selector: string): HTMLButtonElement {
    const element = shadow(widget).querySelector<HTMLButtonElement>(selector);

    if (!element) {
        throw new Error(`Tombol ${selector} tidak ada.`);
    }

    return element;
}

function remount(widget: A11yWidget, lang?: 'en'): A11yWidget {
    widget.remove();

    return mount(lang ? { lang } : {});
}

beforeEach(() => {
    document.body.innerHTML = '';
    document.head.querySelectorAll('style').forEach((style) => style.remove());
    [...html.attributes]
        .filter((attribute) => attribute.name.startsWith('data-a11y-'))
        .forEach((attribute) => html.removeAttribute(attribute.name));
    window.localStorage.clear();
});

afterEach(() => {
    vi.useRealTimers();
});

describe('panel', () => {
    it('opens from the trigger, focuses the dialog title, and closes on Escape back to the trigger', () => {
        const widget = mount();
        const trigger = button(widget, '.trigger');

        trigger.click();

        expect(trigger.getAttribute('aria-expanded')).toBe('true');
        expect(
            shadow(widget).querySelector('.panel')?.hasAttribute('hidden'),
        ).toBe(false);
        expect(shadow(widget).activeElement?.id).toBe('a11y-title');

        shadow(widget)
            .getElementById('a11y-title')
            ?.dispatchEvent(
                new KeyboardEvent('keydown', {
                    key: 'Escape',
                    bubbles: true,
                    composed: true,
                }),
            );

        expect(trigger.getAttribute('aria-expanded')).toBe('false');
        expect(
            shadow(widget).querySelector('.panel')?.hasAttribute('hidden'),
        ).toBe(true);
        expect(shadow(widget).activeElement).toBe(trigger);
    });

    it('labels its controls in English when asked', () => {
        const widget = mount({ lang: 'en' });

        expect(button(widget, '.trigger').getAttribute('aria-label')).toBe(
            'Open accessibility menu',
        );
        expect(button(widget, '[data-action="reset"]').textContent).toContain(
            'Reset all',
        );
    });

    it('mounts only once', () => {
        const first = mount();
        const second = mount();

        expect(second).toBe(first);
        expect(document.querySelectorAll('a11y-widget')).toHaveLength(1);
    });

    it('restores the page when unmounted and reapplies settings on the next mount', () => {
        const widget = mount();
        button(widget, '[data-contrast="grayscale"]').click();

        unmount();

        expect(document.querySelector('a11y-widget')).toBeNull();
        expect(html.hasAttribute('data-a11y-contrast')).toBe(false);

        mount();

        expect(html.getAttribute('data-a11y-contrast')).toBe('grayscale');
    });
});

describe('preferences', () => {
    it('applies settings to the page and restores them on the next visit', () => {
        const widget = mount();

        button(widget, '[data-action="text-larger"]').click();
        button(widget, '[data-toggle="textSpacing"]').click();
        button(widget, '[data-contrast="high"]').click();

        expect(html.getAttribute('data-a11y-text')).toBe('115');
        expect(html.hasAttribute('data-a11y-spacing')).toBe(true);
        expect(html.getAttribute('data-a11y-contrast')).toBe('high');
        expect(
            JSON.parse(
                window.localStorage.getItem(DEFAULT_STORAGE_KEY) ?? '{}',
            ),
        ).toMatchObject({
            textScale: 115,
            textSpacing: true,
            contrast: 'high',
        });

        html.removeAttribute('data-a11y-text');
        const restored = remount(widget);

        expect(html.getAttribute('data-a11y-text')).toBe('115');
        expect(
            button(restored, '[data-toggle="textSpacing"]').getAttribute(
                'aria-pressed',
            ),
        ).toBe('true');
        expect(
            button(restored, '[data-contrast="high"]').getAttribute(
                'aria-pressed',
            ),
        ).toBe('true');
        expect(
            button(restored, '[data-contrast="normal"]').getAttribute(
                'aria-pressed',
            ),
        ).toBe('false');
    });

    it('keeps working when storage is blocked', () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('blocked');
        });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('blocked');
        });

        const widget = mount();

        expect(() =>
            button(widget, '[data-toggle="stopMotion"]').click(),
        ).not.toThrow();
        expect(html.getAttribute('data-a11y-motion')).toBe('off');
    });

    it('stops the text size at its smallest and largest step', () => {
        const widget = mount();
        const smaller = button(widget, '[data-action="text-smaller"]');
        const larger = button(widget, '[data-action="text-larger"]');

        expect(smaller.disabled).toBe(true);

        for (let step = 0; step < 5; step++) {
            larger.click();
        }

        expect(html.getAttribute('data-a11y-text')).toBe('150');
        expect(larger.disabled).toBe(true);
        expect(shadow(widget).querySelector('output')?.value).toBe('150%');
    });

    it('offers one reading font at a time and loads it only when chosen', () => {
        const widget = mount();

        expect(
            document.getElementById('a11y-widget-fonts-readable'),
        ).toBeNull();

        button(widget, '[data-font="readable"]').click();
        expect(html.getAttribute('data-a11y-font')).toBe('readable');
        expect(
            document.getElementById('a11y-widget-fonts-readable'),
        ).not.toBeNull();

        button(widget, '[data-font="dyslexic"]').click();
        expect(html.getAttribute('data-a11y-font')).toBe('dyslexic');
        expect(
            button(widget, '[data-font="readable"]').getAttribute(
                'aria-pressed',
            ),
        ).toBe('false');

        button(widget, '[data-font="dyslexic"]').click();
        expect(html.hasAttribute('data-a11y-font')).toBe(false);
    });

    it('resets everything and forgets the stored settings', () => {
        const widget = mount();
        button(widget, '[data-toggle="highlightLinks"]').click();
        button(widget, '[data-toggle="hideImages"]').click();

        button(widget, '[data-action="reset"]').click();

        expect(html.hasAttribute('data-a11y-links')).toBe(false);
        expect(html.hasAttribute('data-a11y-images')).toBe(false);
        expect(window.localStorage.getItem(DEFAULT_STORAGE_KEY)).toBeNull();
    });

    it('announces changes and tells the host page', () => {
        vi.useFakeTimers();
        const widget = mount();
        const listener = vi.fn();
        document.addEventListener('a11y-change', listener);

        button(widget, '[data-toggle="bigCursor"]').click();
        vi.advanceTimersByTime(60);

        expect(
            shadow(widget).querySelector('[data-ref="announce"]')?.textContent,
        ).toBe('Kursor besar: aktif');
        expect(listener).toHaveBeenCalledOnce();
        expect((listener.mock.calls[0][0] as CustomEvent).detail).toMatchObject(
            { bigCursor: true },
        );
        document.removeEventListener('a11y-change', listener);
    });

    it('ignores unknown or corrupted stored values', () => {
        expect(
            sanitize({
                textScale: 999,
                contrast: 'neon',
                font: 'comic',
                textSpacing: 'yes',
                bigCursor: true,
            }),
        ).toMatchObject({
            textScale: 100,
            contrast: 'normal',
            font: 'default',
            textSpacing: false,
            bigCursor: true,
        });
        expect(sanitize(null).textScale).toBe(100);
    });
});

describe('speech', () => {
    it('reads a clicked paragraph aloud in Indonesian, preferring a local voice, but leaves links alone', () => {
        const speak = vi.fn();
        const onlineVoice = { lang: 'id-ID', localService: false };
        const localVoice = { lang: 'id-ID', localService: true };
        class FakeUtterance {
            lang = '';
            voice: unknown = null;
            onend: (() => void) | null = null;
            onerror: (() => void) | null = null;
            constructor(public text: string) {}
        }
        vi.stubGlobal('SpeechSynthesisUtterance', FakeUtterance);
        vi.stubGlobal('speechSynthesis', {
            speak,
            cancel: vi.fn(),
            getVoices: () => [
                { lang: 'en-US', localService: true },
                onlineVoice,
                localVoice,
            ],
        });
        document.body.innerHTML =
            '<p id="isi">Layanan dibuka pukul delapan.</p><a id="tautan" href="#x">Lihat layanan</a>';

        const widget = mount();
        button(widget, '[data-toggle="speech"]').click();

        document.getElementById('tautan')?.click();
        expect(speak).not.toHaveBeenCalled();

        document.getElementById('isi')?.click();
        expect(speak).toHaveBeenCalledOnce();
        const utterance = speak.mock.calls[0][0] as FakeUtterance;
        expect(utterance.text).toBe('Layanan dibuka pukul delapan.');
        expect(utterance.lang).toBe('id-ID');
        expect(utterance.voice).toBe(localVoice);

        vi.unstubAllGlobals();
    });

    it('disables reading aloud when the browser has no speech support', () => {
        const widget = mount();

        expect(button(widget, '[data-toggle="speech"]').disabled).toBe(true);
        expect(
            shadow(widget)
                .querySelector('[data-ref="speech-hint"]')
                ?.hasAttribute('hidden'),
        ).toBe(false);
    });
});
