(() => {
    const clock = document.getElementById('login-clock');
    if (!clock) return;

    const serverTime = Number(clock.dataset.serverTime);
    if (!Number.isFinite(serverTime)) return;
    const startedAt = performance.now();
    const dateLabel = clock.querySelector('[data-clock-date]');
    const timeLabel = clock.querySelector('[data-clock-time]');
    const dateFormat = new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila', weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    });
    const timeFormat = new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
    });

    const updateClock = () => {
        // Advance the application clock by elapsed time, independently of the device timezone.
        const currentTime = new Date(serverTime + performance.now() - startedAt);
        dateLabel.textContent = dateFormat.format(currentTime);
        timeLabel.textContent = timeFormat.format(currentTime);
        clock.dateTime = currentTime.toISOString();
    };

    updateClock();
    window.setInterval(updateClock, 1000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) updateClock();
    });
})();
