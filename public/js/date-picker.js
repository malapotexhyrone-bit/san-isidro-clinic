(() => {
    const pad = (value) => String(value).padStart(2, '0');
    const isoDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const parseDate = (value) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
        return match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : null;
    };
    const displayDate = (value) => {
        const date = parseDate(value);
        return date ? new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(date) : '';
    };

    let openPicker = null;
    let openPopover = null;
    let closeOpenPicker = null;

    document.querySelectorAll('[data-date-picker]').forEach((picker) => {
        const input = picker.querySelector('input[type="hidden"]');
        const trigger = picker.querySelector('.clinic-date-trigger');
        const label = picker.querySelector('[data-date-label]');
        const popover = picker.querySelector('.clinic-date-popover');
        const title = picker.querySelector('[data-calendar-title]');
        const days = picker.querySelector('[data-calendar-days]');
        const previous = picker.querySelector('[data-calendar-previous]');
        const next = picker.querySelector('[data-calendar-next]');
        const todayButton = picker.querySelector('[data-calendar-today]');
        const clearButton = picker.querySelector('[data-calendar-clear]');
        const placeholder = label.textContent.trim();
        const min = picker.dataset.dateMin || '';
        const max = picker.dataset.dateMax || '';
        const today = new Date();
        let visibleMonth = parseDate(input.value) || parseDate(min) || today;
        visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth(), 1);

        function allowed(value) {
            return (!min || value >= min) && (!max || value <= max);
        }

        function updateLabel() {
            label.textContent = displayDate(input.value) || placeholder;
            trigger.classList.toggle('has-value', Boolean(input.value));
        }

        function close({ restoreFocus = false } = {}) {
            popover.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            picker.classList.remove('is-open');
            popover.classList.remove('is-portal');
            popover.style.removeProperty('--calendar-left');
            popover.style.removeProperty('--calendar-top');
            picker.append(popover);
            if (openPicker === picker) {
                openPicker = null;
                openPopover = null;
                closeOpenPicker = null;
            }
            if (restoreFocus) trigger.focus();
        }

        function positionPopover() {
            const triggerBounds = trigger.getBoundingClientRect();
            if (window.innerWidth <= 520) return;

            const gap = 10;
            const margin = 12;
            const width = popover.offsetWidth;
            const height = popover.offsetHeight;
            const roomOnRight = window.innerWidth - triggerBounds.right;
            const roomOnLeft = triggerBounds.left;
            let left;
            if (roomOnRight >= width + gap + margin) {
                left = triggerBounds.right + gap;
            } else if (roomOnLeft >= width + gap + margin) {
                left = triggerBounds.left - width - gap;
            } else {
                left = Math.min(Math.max(triggerBounds.left, margin), window.innerWidth - width - margin);
            }
            const top = Math.min(Math.max(triggerBounds.top, margin), window.innerHeight - height - margin);
            popover.style.setProperty('--calendar-left', `${left}px`);
            popover.style.setProperty('--calendar-top', `${top}px`);
        }

        function render() {
            title.textContent = new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(visibleMonth);
            days.replaceChildren();
            const year = visibleMonth.getFullYear();
            const month = visibleMonth.getMonth();
            const firstWeekday = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();

            for (let index = 0; index < firstWeekday; index += 1) {
                const spacer = document.createElement('span');
                spacer.className = 'clinic-date-spacer';
                days.append(spacer);
            }

            for (let day = 1; day <= totalDays; day += 1) {
                const date = new Date(year, month, day);
                const value = isoDate(date);
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = String(day);
                button.dataset.dateValue = value;
                button.disabled = !allowed(value);
                button.classList.toggle('is-selected', input.value === value);
                button.classList.toggle('is-today', value === isoDate(today));
                button.setAttribute('aria-label', new Intl.DateTimeFormat(undefined, { dateStyle: 'full' }).format(date));
                if (input.value === value) button.setAttribute('aria-current', 'date');
                button.addEventListener('click', () => {
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    updateLabel();
                    close({ restoreFocus: true });
                });
                days.append(button);
            }

            const monthStart = isoDate(new Date(year, month, 1));
            const monthEnd = isoDate(new Date(year, month + 1, 0));
            previous.disabled = Boolean(min && monthStart <= min.slice(0, 7) + '-01');
            next.disabled = Boolean(max && monthEnd >= max);
            todayButton.disabled = !allowed(isoDate(today));
        }

        function open() {
            if (openPicker && openPicker !== picker) closeOpenPicker?.();
            openPicker = picker;
            openPopover = popover;
            closeOpenPicker = () => close();
            picker.classList.add('is-open');
            popover.classList.add('is-portal');
            document.body.append(popover);
            popover.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            render();
            positionPopover();
            popover.querySelector('[aria-current="date"]:not(:disabled), [data-date-value]:not(:disabled)')?.focus();
        }

        trigger.addEventListener('click', () => popover.hidden ? open() : close());
        previous.addEventListener('click', () => { visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1); render(); });
        next.addEventListener('click', () => { visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1); render(); });
        todayButton.addEventListener('click', () => {
            visibleMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            input.value = isoDate(today);
            input.dispatchEvent(new Event('change', { bubbles: true }));
            updateLabel();
            close({ restoreFocus: true });
        });
        clearButton?.addEventListener('click', () => {
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
            updateLabel();
            close({ restoreFocus: true });
        });
        popover.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                close({ restoreFocus: true });
            }
        });
        updateLabel();
    });

    document.addEventListener('pointerdown', (event) => {
        if (openPicker && !openPicker.contains(event.target) && !openPopover?.contains(event.target)) {
            closeOpenPicker?.();
        }
    });
})();
