import { applyPreferences, clearPreferences } from './apply';
import { ReadingGuide } from './features/readingGuide';
import { SpeechReader, speechSupported } from './features/speech';
import { messagesFor } from './i18n';
import type { Language, Messages } from './i18n';
import { icons } from './icons';
import {
    DEFAULT_PREFERENCES,
    DEFAULT_STORAGE_KEY,
    TEXT_SCALES,
    loadPreferences,
    savePreferences,
    stepTextScale,
} from './preferences';
import type { ContrastMode, FontMode, Preferences } from './preferences';
import { WIDGET_STYLES } from './widgetStyles';

type ToggleKey =
    | 'textSpacing'
    | 'highlightLinks'
    | 'bigCursor'
    | 'readingGuide'
    | 'stopMotion'
    | 'hideImages'
    | 'speech';

/**
 * <a11y-widget>: menu aksesibilitas sebagai Web Component dengan Shadow DOM.
 *
 * Atribut:
 * - `lang`: "id" (bawaan) atau "en"; bila kosong mengikuti <html lang>.
 * - `position`: "bottom-right" (bawaan) atau "bottom-left".
 * - `storage-key`: kunci localStorage, bila satu domain memuat beberapa aplikasi.
 *
 * Mengirim event `a11y-change` (detail: preferensi) setiap kali pengaturan berubah.
 */
export class A11yWidget extends HTMLElement {
    private readonly root: ShadowRoot;
    private preferences: Preferences = { ...DEFAULT_PREFERENCES };
    private messages!: Messages;
    private language: Language = 'id';
    private rendered = false;
    private speechReader!: SpeechReader;
    private guide!: ReadingGuide;

    private readonly onPageClick = (event: MouseEvent): void => {
        if (event.composedPath().includes(this)) {
            return;
        }

        const target = SpeechReader.readableTarget(event.target);

        if (target instanceof HTMLElement) {
            this.speechReader.speak(target.innerText, target);
        }
    };

    constructor() {
        super();
        this.root = this.attachShadow({ mode: 'open' });
    }

    get storageKey(): string {
        return this.getAttribute('storage-key') || DEFAULT_STORAGE_KEY;
    }

    /** Salinan preferensi yang sedang berlaku. */
    get currentPreferences(): Preferences {
        return { ...this.preferences };
    }

    connectedCallback(): void {
        if (this.rendered) {
            // Elemen dipindah atau dipasang ulang: terapkan lagi ke halaman.
            this.sync();

            return;
        }

        const { language, messages } = messagesFor(
            this.getAttribute('lang') ||
                this.ownerDocument.documentElement.lang,
        );
        this.language = language;
        this.messages = messages;
        this.preferences = loadPreferences(this.storageKey);
        this.speechReader = new SpeechReader(
            language === 'en' ? 'en-US' : 'id-ID',
        );

        this.render();
        this.rendered = true;
        this.sync();
    }

    /** Widget dilepas: halaman kembali ke tampilan asli, preferensi tersimpan tetap ada. */
    disconnectedCallback(): void {
        this.guide?.disable();
        this.ownerDocument.removeEventListener('click', this.onPageClick, true);
        this.speechReader?.stop();
        clearPreferences(this.ownerDocument);
    }

    open(): void {
        this.panel.hidden = false;
        this.trigger.setAttribute('aria-expanded', 'true');
        this.query<HTMLElement>('#a11y-title').focus();
    }

    close(returnFocus = true): void {
        this.panel.hidden = true;
        this.trigger.setAttribute('aria-expanded', 'false');

        if (returnFocus) {
            this.trigger.focus();
        }
    }

    get isOpen(): boolean {
        return !this.panel.hidden;
    }

    /** Kembalikan semua pengaturan ke bawaan. */
    reset(): void {
        this.update({ ...DEFAULT_PREFERENCES }, this.messages.resetDone);
    }

    private get trigger(): HTMLButtonElement {
        return this.query<HTMLButtonElement>('.trigger');
    }

    private get panel(): HTMLElement {
        return this.query<HTMLElement>('.panel');
    }

    private query<T extends Element>(selector: string): T {
        const element = this.root.querySelector<T>(selector);

        if (!element) {
            throw new Error(`a11y-widget: elemen ${selector} tidak ditemukan.`);
        }

        return element;
    }

    private render(): void {
        const m = this.messages;
        const tile = (key: ToggleKey, label: string, extra = ''): string =>
            `<button type="button" class="tile ${extra}" data-toggle="${key}" aria-pressed="false">${label}</button>`;
        const contrast = (value: ContrastMode, label: string): string =>
            `<button type="button" class="tile" data-contrast="${value}" aria-pressed="false">${label}</button>`;
        const font = (
            value: Exclude<FontMode, 'default'>,
            label: string,
        ): string =>
            `<button type="button" class="tile" data-font="${value}" aria-pressed="false">${label}</button>`;

        this.root.innerHTML = `
<style>${WIDGET_STYLES}</style>
<button type="button" class="trigger" aria-expanded="false" aria-controls="a11y-panel" aria-label="${m.open}">${icons.accessibility}</button>
<div class="panel" id="a11y-panel" role="dialog" aria-labelledby="a11y-title" hidden>
    <div class="head">
        <h2 id="a11y-title" tabindex="-1">${m.title}</h2>
        <button type="button" class="icon-button" data-action="close" aria-label="${m.close}">${icons.close}</button>
    </div>
    <div class="body">
        <section aria-labelledby="a11y-section-text">
            <h3 id="a11y-section-text">${m.sectionText}</h3>
            <div class="stepper" role="group" aria-labelledby="a11y-text-size">
                <span id="a11y-text-size">${m.textSize}</span>
                <span class="controls">
                    <button type="button" class="icon-button" data-action="text-smaller" aria-label="${m.textSmaller}">${icons.minus}</button>
                    <output aria-live="polite" data-ref="text-scale">100%</output>
                    <button type="button" class="icon-button" data-action="text-larger" aria-label="${m.textLarger}">${icons.plus}</button>
                </span>
            </div>
            <div class="tiles">
                ${tile('textSpacing', m.textSpacing)}
                ${tile('highlightLinks', m.highlightLinks)}
                ${font('readable', m.fontReadable)}
                ${font('dyslexic', m.fontDyslexic)}
            </div>
        </section>
        <section aria-labelledby="a11y-section-display">
            <h3 id="a11y-section-display">${m.sectionDisplay}</h3>
            <div class="tiles" role="group" aria-label="${m.contrast}">
                ${contrast('normal', m.contrastNormal)}
                ${contrast('high', m.contrastHigh)}
                ${contrast('grayscale', m.contrastGrayscale)}
                ${contrast('invert', m.contrastInvert)}
            </div>
            <div class="tiles spaced">
                ${tile('hideImages', m.hideImages)}
                ${tile('stopMotion', m.stopMotion)}
            </div>
        </section>
        <section aria-labelledby="a11y-section-reading">
            <h3 id="a11y-section-reading">${m.sectionReading}</h3>
            <div class="tiles">
                ${tile('bigCursor', m.bigCursor)}
                ${tile('readingGuide', m.readingGuide)}
                ${tile('speech', m.speech, 'wide')}
                <button type="button" class="tile wide" data-action="read-selection">${m.readSelection}</button>
            </div>
            <p class="hint" data-ref="speech-hint" hidden>${m.speechUnavailable}</p>
        </section>
    </div>
    <div class="foot">
        <button type="button" class="reset" data-action="reset">${icons.reset}${m.reset}</button>
        <p class="note">${m.storageNote}</p>
    </div>
</div>
<div class="guide" aria-hidden="true" hidden></div>
<p class="sr-only" aria-live="polite" data-ref="announce"></p>`;

        this.guide = new ReadingGuide(
            this.query<HTMLElement>('.guide'),
            this.ownerDocument,
        );

        this.trigger.addEventListener('click', () =>
            this.isOpen ? this.close() : this.open(),
        );
        this.panel.addEventListener('click', (event) =>
            this.onPanelClick(event),
        );
        this.root.addEventListener('keydown', (event) => {
            if ((event as KeyboardEvent).key === 'Escape' && this.isOpen) {
                event.stopPropagation();
                this.close();
            }
        });

        if (!speechSupported()) {
            this.query<HTMLButtonElement>('[data-toggle="speech"]').disabled =
                true;
            this.query<HTMLButtonElement>(
                '[data-action="read-selection"]',
            ).disabled = true;
            this.query<HTMLElement>('[data-ref="speech-hint"]').hidden = false;
        }
    }

    private onPanelClick(event: MouseEvent): void {
        const button = (event.target as Element).closest<HTMLButtonElement>(
            'button',
        );

        if (!button || button.disabled) {
            return;
        }

        const { action, toggle, contrast, font } = button.dataset;
        const m = this.messages;
        const next = { ...this.preferences };

        if (action === 'close') {
            this.close();

            return;
        }

        if (action === 'reset') {
            this.reset();

            return;
        }

        if (action === 'read-selection') {
            this.speechReader.speak(
                this.ownerDocument.getSelection()?.toString() ?? '',
            );

            return;
        }

        if (action === 'text-smaller' || action === 'text-larger') {
            next.textScale = stepTextScale(
                next.textScale,
                action === 'text-larger' ? 1 : -1,
            );
            this.update(next, `${m.textSize} ${next.textScale}%`);

            return;
        }

        if (toggle) {
            const key = toggle as ToggleKey;
            next[key] = !next[key];
            this.update(
                next,
                `${button.textContent?.trim()}: ${next[key] ? m.on : m.off}`,
            );

            return;
        }

        if (contrast) {
            next.contrast = contrast as ContrastMode;
            this.update(next, `${m.contrast}: ${button.textContent?.trim()}`);

            return;
        }

        if (font) {
            next.font = next.font === font ? 'default' : (font as FontMode);
            this.update(
                next,
                `${button.textContent?.trim()}: ${next.font === font ? m.on : m.off}`,
            );
        }
    }

    private update(next: Preferences, announcement: string): void {
        this.preferences = next;
        savePreferences(this.storageKey, next);
        this.sync();
        this.announce(announcement);
        this.dispatchEvent(
            new CustomEvent<Preferences>('a11y-change', {
                detail: { ...next },
                bubbles: true,
                composed: true,
            }),
        );
    }

    /** Selaraskan halaman dan tampilan panel dengan preferensi. */
    private sync(): void {
        const p = this.preferences;
        applyPreferences(this.ownerDocument, p);

        this.query<HTMLOutputElement>('[data-ref="text-scale"]').value =
            `${p.textScale}%`;
        this.query<HTMLButtonElement>('[data-action="text-smaller"]').disabled =
            p.textScale === TEXT_SCALES[0];
        this.query<HTMLButtonElement>('[data-action="text-larger"]').disabled =
            p.textScale === TEXT_SCALES[TEXT_SCALES.length - 1];

        this.root
            .querySelectorAll<HTMLButtonElement>('[data-toggle]')
            .forEach((button) => {
                button.setAttribute(
                    'aria-pressed',
                    String(p[button.dataset.toggle as ToggleKey]),
                );
            });
        this.root
            .querySelectorAll<HTMLButtonElement>('[data-contrast]')
            .forEach((button) => {
                button.setAttribute(
                    'aria-pressed',
                    String(p.contrast === button.dataset.contrast),
                );
            });
        this.root
            .querySelectorAll<HTMLButtonElement>('[data-font]')
            .forEach((button) => {
                button.setAttribute(
                    'aria-pressed',
                    String(p.font === button.dataset.font),
                );
            });

        if (p.readingGuide) {
            this.guide.enable();
        } else {
            this.guide.disable();
        }

        if (p.speech && speechSupported()) {
            this.ownerDocument.addEventListener(
                'click',
                this.onPageClick,
                true,
            );
        } else {
            this.ownerDocument.removeEventListener(
                'click',
                this.onPageClick,
                true,
            );
            this.speechReader.stop();
        }
    }

    private announce(message: string): void {
        const region = this.query<HTMLElement>('[data-ref="announce"]');
        region.textContent = '';
        // Jeda singkat agar pembaca layar membacakan pesan yang sama dua kali berturut-turut.
        window.setTimeout(() => (region.textContent = message), 50);
    }
}
