/**
 * Panduan baca: pita horizontal yang mengikuti posisi penunjuk agar mata
 * tetap pada satu baris. Pita berada di Shadow DOM widget dan tidak menangkap klik.
 */
export class ReadingGuide {
    private readonly onMove = (event: PointerEvent): void => {
        this.element.style.top = `${Math.max(0, event.clientY - this.element.offsetHeight / 2)}px`;
    };

    constructor(
        private readonly element: HTMLElement,
        private readonly doc: Document,
    ) {}

    enable(): void {
        this.element.hidden = false;
        this.doc.addEventListener('pointermove', this.onMove, {
            passive: true,
        });
    }

    disable(): void {
        this.element.hidden = true;
        this.doc.removeEventListener('pointermove', this.onMove);
    }
}
