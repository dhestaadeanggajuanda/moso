/**
 * Gaya panel di dalam Shadow DOM. Memakai px (bukan rem) agar panel tidak ikut
 * membesar saat pengunjung memperbesar teks halaman. Warna aksen bisa diatur
 * situs induk lewat variabel CSS `--a11y-accent`, yang menembus Shadow DOM.
 */
export const WIDGET_STYLES = `
:host {
    all: initial;
    position: fixed;
    z-index: 2147483000;
    right: 16px;
    bottom: 16px;
    color: #111827;
    font: 15px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", sans-serif;
    --accent: var(--a11y-accent, #1e3a8a);
    --accent-text: var(--a11y-accent-text, #ffffff);
}
:host([position="bottom-left"]) { right: auto; left: 16px; }
*, *::before, *::after { box-sizing: border-box; }
[hidden] { display: none !important; }

button {
    font: inherit;
    color: inherit;
    cursor: pointer;
    border: 0;
    background: none;
}
button:disabled { cursor: not-allowed; opacity: 0.45; }
button:focus-visible, [tabindex="-1"]:focus-visible {
    outline: 3px solid #f59e0b;
    outline-offset: 2px;
    box-shadow: 0 0 0 2px #111827;
}
svg { width: 22px; height: 22px; flex: none; }

.trigger {
    display: grid;
    place-items: center;
    width: 56px;
    height: 56px;
    border-radius: 999px;
    background: var(--accent);
    color: var(--accent-text);
    box-shadow: 0 2px 4px rgb(15 23 42 / 0.2), 0 12px 28px -10px rgb(15 23 42 / 0.45);
}
.trigger svg { width: 28px; height: 28px; }

.panel {
    position: absolute;
    right: 0;
    bottom: 68px;
    width: min(360px, calc(100vw - 32px));
    max-height: min(78vh, 640px);
    overflow-y: auto;
    overscroll-behavior: contain;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 2px 6px rgb(15 23 42 / 0.12), 0 24px 48px -16px rgb(15 23 42 / 0.4);
    border: 1px solid #e5e7eb;
}
:host([position="bottom-left"]) .panel { right: auto; left: 0; }

.head {
    position: sticky;
    top: 0;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 12px 12px 18px;
    background: var(--accent);
    color: var(--accent-text);
    border-radius: 15px 15px 0 0;
}
.head h2 { margin: 0; font-size: 17px; font-weight: 700; }
.icon-button {
    display: inline-grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border-radius: 999px;
}
.head .icon-button:hover { background: rgb(255 255 255 / 0.15); }

.body { padding: 4px 16px 8px; }
section { padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
section:last-child { border-bottom: 0; }
h3 { margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #374151; }

.stepper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
    padding: 6px 6px 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
}
.stepper .controls { display: flex; align-items: center; gap: 4px; }
.stepper output { min-width: 48px; text-align: center; font-weight: 700; font-variant-numeric: tabular-nums; }
.stepper .icon-button { border: 1px solid #d1d5db; }
.stepper .icon-button:hover:not(:disabled) { border-color: var(--accent); }

.tiles { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.tile {
    position: relative;
    display: flex;
    align-items: center;
    min-height: 52px;
    padding: 8px 30px 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    text-align: left;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
    background: #ffffff;
}
.tile:hover:not(:disabled) { border-color: var(--accent); }
.tile[aria-pressed="true"] {
    background: var(--accent);
    border-color: var(--accent);
    color: var(--accent-text);
}
.tile[aria-pressed="true"]::after {
    content: "";
    position: absolute;
    right: 12px;
    top: 50%;
    width: 10px;
    height: 6px;
    border-left: 2px solid currentColor;
    border-bottom: 2px solid currentColor;
    transform: translateY(-70%) rotate(-45deg);
}
.tile.wide { grid-column: 1 / -1; }
.tiles.spaced { margin-top: 8px; }
.hint { margin: 8px 0 0; font-size: 13px; color: #4b5563; }

.foot { padding: 12px 16px 16px; border-top: 1px solid #e5e7eb; }
.reset {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    min-height: 44px;
    border: 1px solid #d1d5db;
    border-radius: 999px;
    font-weight: 700;
}
.reset:hover { border-color: var(--accent); }
.reset svg { width: 18px; height: 18px; }
.note { margin: 10px 0 0; font-size: 13px; line-height: 1.45; color: #4b5563; }

.guide {
    position: fixed;
    left: 0;
    right: 0;
    top: 0;
    height: 44px;
    background: rgb(250 204 21 / 0.18);
    border-top: 2px solid #111827;
    border-bottom: 2px solid #111827;
    pointer-events: none;
}
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

@media (max-width: 479px) {
    .panel {
        position: fixed;
        left: 8px;
        right: 8px;
        bottom: 80px;
        width: auto;
        max-height: calc(100vh - 104px);
    }
}

@media (prefers-reduced-motion: no-preference) {
    .tile, .reset, .icon-button { transition: background-color 150ms, border-color 150ms, color 150ms; }
}
`;
