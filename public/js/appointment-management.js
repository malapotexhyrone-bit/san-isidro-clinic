(() => {
    const reschedule = document.getElementById('reschedule');
    if (!reschedule) return;
    const revealReschedule = () => {
        if (window.location.hash === '#reschedule') {
            reschedule.open = true;
            reschedule.scrollIntoView({ block: 'start' });
        }
    };
    revealReschedule();
    window.addEventListener('hashchange', revealReschedule);
    document.querySelectorAll('a[href="#reschedule"]').forEach(link => {
        link.addEventListener('click', () => { reschedule.open = true; });
    });
})();
