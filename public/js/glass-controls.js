/* Load in the head so saved readability settings apply before the page is painted. */
(() => {
    'use strict';
    const key = 'clinic-glass-blur';
    const defaultBlur = 22;
    const root = document.documentElement;
    let blur = defaultBlur;

    function normalize(value) {
        if (value === null || String(value).trim() === '') return defaultBlur;
        const number = Number(value);
        return Number.isFinite(number) ? Math.max(0, Math.min(40, Math.round(number))) : defaultBlur;
    }

    function apply(value) {
        blur = normalize(value);
        root.style.setProperty('--clinic-glass-blur', `${blur}px`);
        root.style.setProperty('--clinic-glass-tint', ((blur - defaultBlur) / 100).toFixed(2));
        root.style.setProperty('--clinic-glass-progress', `${blur / 40 * 100}%`);
        document.querySelectorAll('[data-glass-controls]').forEach((control) => {
            const range = control.querySelector('[data-glass-range]');
            range.value = String(blur);
            range.setAttribute('aria-valuetext', `${blur} pixels of background blur`);
            control.querySelector('[data-glass-value]').textContent = `${blur} px`;
        });
    }

    function save(value) {
        apply(value);
        try { localStorage.setItem(key, String(blur)); } catch (_) { /* Keep working without storage. */ }
    }

    try { blur = normalize(localStorage.getItem(key)); } catch (_) { /* Use the default. */ }
    apply(blur);

    function initialize() {
        apply(blur);
        document.querySelectorAll('[data-glass-controls]').forEach((control) => {
            const toggle = control.querySelector('[data-glass-toggle]');
            const settings = control.querySelector('.clinic-glass-settings');
            const range = control.querySelector('[data-glass-range]');
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
            control.querySelector('[data-glass-reset]').addEventListener('click', () => save(defaultBlur));
            document.addEventListener('pointerdown', (event) => {
                if (!control.contains(event.target)) close();
            });
            control.addEventListener('focusout', (event) => {
                if (event.relatedTarget && !control.contains(event.relatedTarget)) close();
            });
            control.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !settings.hidden) {
                    event.preventDefault();
                    close(true);
                }
            });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
    window.addEventListener('storage', (event) => {
        if (event.key === key || event.key === null) apply(event.key === null ? defaultBlur : event.newValue);
    });
})();
