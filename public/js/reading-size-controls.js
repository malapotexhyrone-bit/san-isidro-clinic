/* Restore the reading preference before paint; no account or record changes are involved. */
(() => {
    'use strict';
    const key = 'clinic-reading-size';
    const root = document.documentElement;
    let size = 100;
    let canStore = true;
    function normalize(value) {
        if (value === null || String(value).trim() === '') return 100;
        const number = Number(value);
        return Number.isFinite(number) ? Math.max(100, Math.min(200, Math.round(number / 25) * 25)) : 100;
    }
    function apply(value) {
        size = normalize(value);
        root.style.setProperty('--clinic-reading-scale', String(size / 100));
        root.toggleAttribute('data-reading-enlarged', size > 100);
        document.querySelectorAll('[data-reading-controls]').forEach((control) => {
            const range = control.querySelector('[data-reading-range]');
            range.value = String(size);
            range.setAttribute('aria-valuetext', `${size} percent reading size`);
            control.style.setProperty('--clinic-glass-progress', `${size - 100}%`);
            control.querySelector('[data-reading-value]').textContent = `${size}%`;
            control.querySelector('[data-reading-decrease]').disabled = size === 100;
            control.querySelector('[data-reading-increase]').disabled = size === 200;
            control.querySelector('[data-reading-storage]').textContent = canStore ? 'Saved on this browser' : 'Applied to this page';
        });
    }
    function save(value) {
        size = normalize(value);
        try { localStorage.setItem(key, String(size)); } catch (_) { canStore = false; }
        apply(size);
    }
    try { size = normalize(localStorage.getItem(key)); } catch (_) { canStore = false; }
    apply(size);
    function initialize() {
        apply(size);
        document.querySelectorAll('[data-reading-controls]').forEach((control) => {
            const toggle = control.querySelector('[data-reading-toggle]');
            const settings = control.querySelector('.clinic-glass-settings');
            const range = control.querySelector('[data-reading-range]');
            function close(restoreFocus = false) {
                settings.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
                if (restoreFocus) toggle.focus({ preventScroll: true });
            }
            toggle.addEventListener('click', () => {
                const open = settings.hidden;
                settings.hidden = !open;
                toggle.setAttribute('aria-expanded', String(open));
                if (open) range.focus({ preventScroll: true });
            });
            range.addEventListener('input', () => save(range.value));
            control.querySelector('[data-reading-increase]').addEventListener('click', () => save(size + 25));
            control.querySelector('[data-reading-decrease]').addEventListener('click', () => save(size - 25));
            control.querySelector('[data-reading-reset]').addEventListener('click', () => save(100));
            document.addEventListener('pointerdown', (event) => { if (!control.contains(event.target)) close(); });
            control.addEventListener('focusout', (event) => {
                if (event.relatedTarget && !control.contains(event.relatedTarget)) close();
            });
            control.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !settings.hidden) { event.preventDefault(); close(true); }
            });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
    window.addEventListener('storage', (event) => {
        if (event.key === key || event.key === null) apply(event.key === null ? 100 : event.newValue);
    });
})();
