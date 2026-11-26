(() => {
    document.querySelectorAll('[data-password-control]').forEach(control => {
        const input = control.querySelector('input');
        const button = control.querySelector('[data-password-toggle]');
        const hide = () => {
            input.type = 'password';
            button.setAttribute('aria-pressed', 'false');
            button.setAttribute('aria-label', 'Show password');
            button.title = 'Show password';
        };
        control.classList.add('has-password-eye');
        button.hidden = false;
        button.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            button.title = visible ? 'Hide password' : 'Show password';
        });
        input.form?.addEventListener('submit', hide);
        window.addEventListener('pagehide', hide);
        document.addEventListener('visibilitychange', () => { if (document.hidden) hide(); });
    });

    document.querySelectorAll('[data-password-reveal]').forEach(panel => {
        const input = panel.querySelector('[data-password-output]');
        const button = panel.querySelector('[data-password-reveal-toggle]');
        const status = panel.querySelector('[data-password-reveal-status]');
        let timer;
        let pending;
        const hide = () => {
            pending?.abort();
            pending = null;
            window.clearTimeout(timer);
            input.type = 'password';
            input.value = '';
            button.setAttribute('aria-pressed', 'false');
            button.setAttribute('aria-label', 'Show saved password');
            button.title = 'Show saved password';
            status.textContent = 'Click the eye to show the latest saved password. It hides after 30 seconds.';
        };
        input.parentElement.classList.add('has-password-eye');
        button.hidden = false;
        button.addEventListener('click', async () => {
            if (input.type === 'text') { hide(); return; }
            button.disabled = true;
            status.textContent = 'Loading saved password…';
            const request = new AbortController();
            pending = request;
            try {
                const response = await fetch(panel.dataset.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': panel.dataset.token },
                    signal: request.signal,
                });
                const data = await response.json();
                if (!response.ok) {
                    status.textContent = data.message || 'Unable to show this password. Refresh the page and try again.';
                    return;
                }
                if (pending !== request || document.hidden) return;
                input.value = data.password;
                input.type = 'text';
                button.setAttribute('aria-pressed', 'true');
                button.setAttribute('aria-label', 'Hide saved password');
                button.title = 'Hide saved password';
                status.textContent = 'Latest saved password. It hides automatically after 30 seconds.';
                timer = window.setTimeout(hide, 30000);
            } catch (error) {
                if (error.name !== 'AbortError') status.textContent = 'Unable to show the password. Refresh the page and try again.';
            } finally {
                if (pending === request) pending = null;
                button.disabled = false;
            }
        });
        window.addEventListener('pagehide', hide);
        document.addEventListener('visibilitychange', () => { if (document.hidden) hide(); });
    });
})();
