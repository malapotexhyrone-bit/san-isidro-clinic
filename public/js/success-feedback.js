(() => {
    const dialog = document.getElementById('success-modal');
    const okay = document.getElementById('success-continue');
    if (!dialog || !okay) return;
    const returnToQueue = () => window.location.assign(okay.href);
    // A real modal prevents interactions with the page and traps keyboard focus.
    if (typeof dialog.showModal === 'function') {
        dialog.close();
        dialog.showModal();
    }
    document.body.style.overflow = 'hidden';
    okay.focus({ preventScroll: true });
    dialog.addEventListener('cancel', event => {
        event.preventDefault();
        returnToQueue();
    });
    window.addEventListener('pagehide', () => {
        dialog.close();
        document.body.style.overflow = '';
    });
})();
