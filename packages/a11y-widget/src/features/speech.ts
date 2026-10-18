/**
 * Pembaca suara berbasis Web Speech API, memakai suara bawaan browser atau
 * sistem operasi. Suara lokal diutamakan; bila hanya ada suara daring (mis.
 * suara Google di Chrome), browser mengirim teks yang dibacakan ke penyedianya.
 * Mode "bacakan teks yang diklik" hanya membaca elemen teks biasa; tautan dan
 * tombol tetap berfungsi normal agar pengguna tidak tersesat.
 */

const READABLE =
    'p, h1, h2, h3, h4, h5, h6, li, dt, dd, td, th, figcaption, blockquote, caption, label';
const INTERACTIVE =
    'a, button, input, select, textarea, summary, [role="button"], [role="link"], [contenteditable]';
const READING_ATTRIBUTE = 'data-a11y-reading';
const MAX_LENGTH = 2000;

export function speechSupported(win: Window = window): boolean {
    return (
        'speechSynthesis' in win &&
        typeof (win as Window & { SpeechSynthesisUtterance?: unknown })
            .SpeechSynthesisUtterance === 'function'
    );
}

export class SpeechReader {
    private reading: Element | null = null;

    constructor(private readonly lang: string) {}

    speak(text: string, source: Element | null = null): void {
        if (!speechSupported()) {
            return;
        }

        const content = text.replace(/\s+/g, ' ').trim().slice(0, MAX_LENGTH);

        if (!content) {
            return;
        }

        const synth = window.speechSynthesis;
        this.stop();

        const utterance = new SpeechSynthesisUtterance(content);
        utterance.lang = this.lang;
        const voices = synth
            .getVoices()
            .filter((candidate) =>
                candidate.lang.toLowerCase().startsWith(this.lang.slice(0, 2)),
            );
        const voice =
            voices.find((candidate) => candidate.localService) ?? voices[0];

        if (voice) {
            utterance.voice = voice;
        }

        this.reading = source;
        source?.setAttribute(READING_ATTRIBUTE, '');
        utterance.onend = () => this.clearHighlight();
        utterance.onerror = () => this.clearHighlight();
        synth.speak(utterance);
    }

    stop(): void {
        if (speechSupported()) {
            window.speechSynthesis.cancel();
        }

        this.clearHighlight();
    }

    /**
     * Elemen teks yang dibacakan untuk klik ini, atau null bila klik mengenai
     * kontrol interaktif atau di luar teks.
     */
    static readableTarget(target: EventTarget | null): Element | null {
        if (!(target instanceof Element) || target.closest(INTERACTIVE)) {
            return null;
        }

        return target.closest(READABLE);
    }

    private clearHighlight(): void {
        this.reading?.removeAttribute(READING_ATTRIBUTE);
        this.reading = null;
    }
}
