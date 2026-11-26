(() => {
    const form = document.getElementById('appointment-form');
    if (!form) return;
    const picker = document.getElementById('availability-picker');
    const monthLabel = document.getElementById('calendar-month');
    const days = document.getElementById('calendar-days');
    const calendarMessage = document.getElementById('calendar-message');
    const previous = document.getElementById('calendar-previous');
    const next = document.getElementById('calendar-next');
    const timeMessage = document.getElementById('time-message');
    const timeSlots = document.getElementById('time-slots');
    const timeCount = document.getElementById('time-count');
    const dateInput = document.getElementById('appointment-date');
    const timeInput = document.getElementById('appointment-time');
    const minimumDate = picker.dataset.minimumDate;
    const maximumDate = picker.dataset.maximumDate;
    let restoreDate = picker.dataset.restoreDate;
    let restoreTime = picker.dataset.restoreTime;
    let patientId = null;
    let physicianId = null;
    let dates = {};
    let requestNumber = 0;
    const dateFromKey = key => {
        const [year, month, day] = key.split('-').map(Number);
        return new Date(year, month - 1, day);
    };
    const dateKey = date => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
    const monthKey = date => dateKey(date).slice(0, 7);
    const monthStart = key => { const date = dateFromKey(key); date.setDate(1); return date; };
    // Calendar dates use clinic-provided date strings, independent of the browser's timezone.
    let currentMonth = monthStart(restoreDate || minimumDate);
    const selectionChanged = () => {
        dateInput.dispatchEvent(new Event('change', { bubbles: true }));
        timeInput.dispatchEvent(new Event('change', { bubbles: true }));
    };
    function clearSelection() {
        dateInput.value = '';
        timeInput.value = '';
        timeSlots.replaceChildren();
        timeCount.textContent = '';
        timeMessage.textContent = 'Choose a date to see its available times.';
        timeMessage.hidden = false;
        selectionChanged();
    }
    function renderCalendar() {
        days.replaceChildren();
        const total = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0).getDate();
        for (let offset = 0; offset < currentMonth.getDay(); offset++) {
            const blank = document.createElement('span');
            blank.className = 'booking-blank';
            blank.setAttribute('aria-hidden', 'true');
            days.append(blank);
        }
        for (let day = 1; day <= total; day++) {
            const date = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day);
            const key = dateKey(date);
            const entry = dates[key];
            const available = key >= minimumDate && key <= maximumDate && entry?.open_slots > 0;
            const selected = dateInput.value === key;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `booking-day${available ? ' available' : ''}${selected ? ' selected' : ''}`;
            button.textContent = day;
            button.disabled = !available;
            const label = date.toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
            const status = available ? `${entry.open_slots} available times` : 'Unavailable';
            button.setAttribute('aria-label', `${label}, ${status}`);
            button.setAttribute('aria-pressed', String(selected));
            button.title = `${label} · ${status}`;
            if (available) button.addEventListener('click', () => selectDate(key));
            days.append(button);
        }
        previous.disabled = currentMonth <= monthStart(minimumDate);
        next.disabled = currentMonth >= monthStart(maximumDate);
    }
    function renderTimes() {
        const open = (dates[dateInput.value]?.times || []).filter(slot => slot.available);
        timeSlots.replaceChildren();
        timeCount.textContent = `${open.length} open`;
        timeMessage.hidden = open.length > 0;
        if (!open.length) timeMessage.textContent = 'No available times. Choose another date.';
        for (const slot of open) {
            const button = document.createElement('button');
            button.type = 'button';
            const selected = timeInput.value === slot.time;
            button.className = `booking-time-slot${selected ? ' selected' : ''}`;
            button.textContent = slot.label;
            button.setAttribute('aria-pressed', String(selected));
            button.addEventListener('click', () => {
                timeInput.value = slot.time;
                // Preserve keyboard focus while updating the selected state.
                for (const item of timeSlots.children) {
                    const chosen = item === button;
                    item.classList.toggle('selected', chosen);
                    item.setAttribute('aria-pressed', String(chosen));
                }
                selectionChanged();
            });
            timeSlots.append(button);
        }
    }
    function selectDate(key) {
        if (!dates[key]?.open_slots) return;
        dateInput.value = key;
        timeInput.value = '';
        for (const button of days.querySelectorAll('button')) {
            const selected = button.textContent === String(dateFromKey(key).getDate());
            button.classList.toggle('selected', selected);
            button.setAttribute('aria-pressed', String(selected));
        }
        renderTimes();
        selectionChanged();
    }
    async function loadMonth() {
        const thisRequest = ++requestNumber;
        dates = {};
        days.replaceChildren();
        previous.disabled = true;
        next.disabled = true;
        calendarMessage.hidden = false;
        if (!patientId || !physicianId) {
            monthLabel.textContent = !patientId ? 'Select patient first' : 'Choose a physician';
            calendarMessage.textContent = 'Choose a patient and physician to see available dates.';
            return;
        }
        monthLabel.textContent = currentMonth.toLocaleDateString('en-PH', { month: 'long', year: 'numeric' });
        calendarMessage.textContent = 'Loading available dates…';
        picker.setAttribute('aria-busy', 'true');
        try {
            const query = new URLSearchParams({ physician_id: physicianId, month: monthKey(currentMonth) });
            const response = await fetch(`${picker.dataset.url}?${query}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Availability request failed');
            const payload = await response.json();
            if (thisRequest !== requestNumber) return;
            dates = payload.dates || {};
            monthLabel.textContent = payload.month_label;
            calendarMessage.hidden = true;
            renderCalendar();
            if (!Object.values(dates).some(entry => entry.open_slots > 0)) {
                calendarMessage.textContent = 'No open dates this month. Try the next month.';
                calendarMessage.hidden = false;
            }
            if (restoreDate && monthKey(dateFromKey(restoreDate)) === monthKey(currentMonth)) {
                const savedDate = restoreDate;
                const savedTime = restoreTime;
                restoreDate = restoreTime = '';
                if (dates[savedDate]?.open_slots > 0) {
                    selectDate(savedDate);
                    if (dates[savedDate].times.some(slot => slot.time === savedTime && slot.available)) {
                        timeInput.value = savedTime;
                        renderTimes();
                        selectionChanged();
                    }
                }
            }
        } catch (error) {
            if (thisRequest !== requestNumber) return;
            dates = {};
            calendarMessage.textContent = 'Availability could not be loaded. Refresh the page to try again.';
            calendarMessage.hidden = false;
            previous.disabled = currentMonth <= monthStart(minimumDate);
            next.disabled = currentMonth >= monthStart(maximumDate);
        } finally {
            if (thisRequest === requestNumber) picker.removeAttribute('aria-busy');
        }
    }
    function updateContext(kind, id) {
        const value = id || null;
        if ((kind === 'patient' ? patientId : physicianId) === value) return;
        if (kind === 'patient') patientId = value;
        else physicianId = value;
        clearSelection();
        currentMonth = monthStart(restoreDate || minimumDate);
        picker.removeAttribute('aria-busy');
        loadMonth();
    }
    previous.addEventListener('click', () => {
        if (previous.disabled) return;
        restoreDate = restoreTime = '';
        currentMonth.setMonth(currentMonth.getMonth() - 1);
        clearSelection();
        loadMonth();
    });
    next.addEventListener('click', () => {
        if (next.disabled) return;
        restoreDate = restoreTime = '';
        currentMonth.setMonth(currentMonth.getMonth() + 1);
        clearSelection();
        loadMonth();
    });
    function bindLookup(inputId, hiddenId, listId, onMatch) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const options = [...document.getElementById(listId).options];
        const sync = () => {
            const match = options.find(option => option.value === input.value);
            hidden.value = match?.dataset.id || '';
            input.setCustomValidity(!input.value || match ? '' : 'Select one of the matching records shown.');
            onMatch(match || null);
        };
        if (!input.value && hidden.value) input.value = options.find(option => option.dataset.id === hidden.value)?.value || '';
        const edited = () => { restoreDate = restoreTime = ''; sync(); };
        input.addEventListener('input', edited);
        input.addEventListener('change', edited);
        sync();
        return sync;
    }
    const email = document.getElementById('notification-email');
    const emailHelp = document.getElementById('email-help');
    const syncPatient = bindLookup('patient-lookup', 'patient-id', 'patient-options', match => {
        email.value = match?.dataset.email || '';
        emailHelp.textContent = match && !email.value ? 'Add an email in Patient Edit before booking.' : 'Manage this email in Patient Edit.';
        updateContext('patient', match?.dataset.id);
    });
    const syncPhysician = bindLookup('physician-lookup', 'physician-id', 'physician-options', match => updateContext('physician', match?.dataset.id));
    // The shared validation script displays field errors before the save confirmation.
    form.addEventListener('submit', event => {
        syncPatient();
        syncPhysician();
        const validSlot = dates[dateInput.value]?.times.some(slot => slot.time === timeInput.value && slot.available);
        if (!validSlot || !form.checkValidity()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (!validSlot) {
                timeInput.value = '';
                selectionChanged();
                timeMessage.textContent = 'Choose a date and an available time before saving.';
                timeMessage.hidden = false;
                timeInput.focus();
            }
        }
    });
})();
