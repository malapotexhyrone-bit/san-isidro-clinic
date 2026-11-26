(() => {
    const overview = document.getElementById('schedule-overview');
    const overviewSupport = document.querySelector('.schedule-overview-support');
    const viewer = document.getElementById('schedule-slot-viewer');
    const returnButton = document.getElementById('slot-screen-return');

    if (!overview || !viewer || !returnButton) return;

    const title = document.getElementById('slot-screen-title');
    const specialization = document.getElementById('slot-screen-specialization');
    const date = document.getElementById('slot-screen-date');
    const hours = document.getElementById('slot-screen-hours');
    const count = document.getElementById('slot-screen-count');
    const times = document.getElementById('slot-screen-times');
    let trigger = null;

    function showOverview() {
        viewer.hidden = true;
        overview.hidden = false;
        if (overviewSupport) overviewSupport.hidden = false;
        trigger?.focus({ preventScroll: true });
        trigger?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    document.querySelectorAll('[data-slot-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const slots = JSON.parse(button.dataset.slots || '[]');
            trigger = button;
            title.textContent = button.dataset.physician || 'Physician schedule';
            specialization.textContent = button.dataset.specialization || '';
            date.textContent = button.dataset.date || '';
            hours.textContent = button.dataset.hours || '';
            count.textContent = `${slots.length} open ${slots.length === 1 ? 'slot' : 'slots'}`;
            times.replaceChildren(...slots.map((slot) => {
                const item = document.createElement('span');
                item.className = 'slot-screen-time';
                item.textContent = slot.label;
                return item;
            }));

            overview.hidden = true;
            if (overviewSupport) overviewSupport.hidden = true;
            viewer.hidden = false;
            viewer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            title.focus({ preventScroll: true });
        });
    });

    returnButton.addEventListener('click', showOverview);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !viewer.hidden) showOverview();
    });
})();
