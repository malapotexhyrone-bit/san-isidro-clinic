(() => {
    const forms = [...document.forms];
    const feedback = new Map();
    const draftModes = new WeakMap();
    let sequence = 0;
    const controls = form => [...form.elements].filter(field =>
        field.matches('input, select, textarea') && !field.disabled && field.type !== 'hidden' &&
        !['submit', 'button', 'reset'].includes(field.type));
    const fieldLabel = field => {
        const label = field.labels?.[0] || field.closest('.field')?.querySelector('label');
        return (label?.textContent || field.getAttribute('aria-label') || field.name.replaceAll('_', ' '))
            .replace(/\*/g, '').trim();
    };
    const clearError = field => {
        const state = feedback.get(field);
        if (!state) return;
        state.message.remove();
        field.removeAttribute('aria-invalid');
        const description = (field.getAttribute('aria-describedby') || '').split(/\s+/)
            .filter(id => id && id !== state.message.id).join(' ');
        if (description) field.setAttribute('aria-describedby', description);
        else field.removeAttribute('aria-describedby');
        feedback.delete(field);
        const group = field.closest('.field');
        if (group && !group.querySelector('[aria-invalid="true"]')) group.classList.remove('field-has-error');
    };
    const showError = (field, message, source = 'client') => {
        let state = feedback.get(field);
        if (!state) {
            const error = document.createElement('p');
            error.className = 'field-error';
            error.id = `field-error-${++sequence}`;
            const group = field.closest('.field');
            (group || field.parentElement).append(error);
            group?.classList.add('field-has-error');
            const descriptions = new Set((field.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
            descriptions.add(error.id);
            field.setAttribute('aria-describedby', [...descriptions].join(' '));
            state = { message: error };
            feedback.set(field, state);
        }
        state.message.textContent = message;
        state.source = source;
        state.value = field.value;
        field.setAttribute('aria-invalid', 'true');
    };
    const revealField = field => {
        for (let parent = field.parentElement; parent; parent = parent.parentElement) {
            if (parent.tagName === 'DETAILS') parent.open = true;
        }
        window.requestAnimationFrame(() => {
            field.focus({ preventScroll: true });
            field.scrollIntoView({ block: 'center', behavior: 'auto' });
        });
    };
    const issueFor = (field, draft = false) => {
        const label = fieldLabel(field).toLowerCase();
        const isPassword = ['password', 'password_confirmation', 'current_password'].includes(field.name);
        const value = isPassword ? field.value : field.value.trim();
        if (!draft && field.required && ((!value && !['checkbox', 'radio'].includes(field.type)) || field.validity.valueMissing)) {
            if (field.readOnly && field.name === 'appointment_date') return 'Choose an available date from the calendar.';
            if (field.readOnly && field.name === 'appointment_time') return 'Choose an available time after selecting a date.';
            if (field.readOnly && field.name === 'notification_email') return 'Select a patient with an email address. Update the patient record if the email is missing.';
            return field.tagName === 'SELECT' ? `Select ${label}.` : `Enter ${label}.`;
        }
        if (!value && !field.validity.customError) return '';
        if (field.validity.customError) return field.validationMessage;
        if (field.validity.typeMismatch && field.type === 'email') return 'Enter a valid email address, such as name@example.com.';
        if (field.minLength > 0 && value.length < field.minLength) return `Use at least ${field.minLength} characters for ${label}.`;
        if (field.maxLength > 0 && value.length > field.maxLength) return `Use no more than ${field.maxLength} characters for ${label}.`;
        if (field.validity.rangeUnderflow) return `Choose ${label} on or above ${field.min}.`;
        if (field.validity.rangeOverflow) return `Choose ${label} on or below ${field.max}.`;
        if (field.validity.patternMismatch) return `Enter ${label} in the required format.`;
        return field.willValidate && !field.validity.valid ? field.validationMessage : '';
    };
    const issuesFor = (form, draft = false) => {
        const issues = new Map();
        for (const field of controls(form)) {
            const issue = issueFor(field, draft);
            if (issue) issues.set(field, issue);
        }
        const password = form.querySelector('[name="password"]');
        const confirmation = form.querySelector('[name="password_confirmation"]');
        const current = form.querySelector('[name="current_password"]');
        if (password && confirmation && (password.value || confirmation.value)) {
            if (!password.value) issues.set(password, 'Enter a new password before confirming it.');
            if (!confirmation.value) issues.set(confirmation, 'Re-enter the password in the confirmation field.');
            else if (confirmation.value !== password.value) issues.set(confirmation, 'The passwords do not match. Re-enter the same password in both fields.');
            if (current && !current.value) issues.set(current, 'Enter your current password before changing your password.');
        }
        return issues;
    };

    for (const form of forms) {
        // Keep native validation as the fallback when JavaScript is unavailable.
        form.noValidate = true;
        for (const field of controls(form)) {
            if (!field.id) field.id = `form-field-${++sequence}`;
            const label = field.closest('.field')?.querySelector('label');
            if (label && !label.htmlFor && !label.contains(field)) label.htmlFor = field.id;
        }
        const scope = document.createElement('input');
        scope.type = 'hidden';
        scope.name = '_validation_form';
        scope.value = form.action;
        form.append(scope);
        const refresh = () => {
            const issues = issuesFor(form, draftModes.get(form));
            for (const field of controls(form)) {
                const state = feedback.get(field);
                if (!state) continue;
                if (state.source === 'server' && state.value === field.value) continue;
                const issue = issues.get(field);
                if (issue) showError(field, issue);
                else clearError(field);
            }
        };
        form.addEventListener('input', refresh);
        form.addEventListener('change', refresh);
        form.addEventListener('click', refresh);
    }

    // Run before the existing save confirmation, and block invalid submission in place.
    document.addEventListener('submit', event => {
        const form = event.target;
        const draft = event.submitter?.dataset.validationMode === 'draft';
        if (!forms.includes(form) || (event.submitter?.formNoValidate && !draft)) return;
        draftModes.set(form, draft);
        const issues = issuesFor(form, draft);
        for (const field of controls(form)) {
            if (issues.has(field)) showError(field, issues.get(field));
            else clearError(field);
        }
        if (!issues.size) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        delete form.dataset.confirmed;
        const firstInvalid = controls(form).find(field => issues.has(field));
        revealField(firstInvalid);
    }, true);

    const errorData = document.getElementById('validation-errors');
    if (!errorData) return;
    const errors = JSON.parse(errorData.textContent);
    const matchingForm = forms.find(form => form.action === errorData.dataset.validationScope);
    const candidates = matchingForm ? [matchingForm] : forms;
    const summary = document.querySelector('[data-validation-summary]');
    const mapped = new Map();
    for (const [name, messages] of Object.entries(errors)) {
        let field;
        for (const form of candidates) {
            const named = [...form.elements].find(control => control.name === name);
            if (!named || ['submit', 'button', 'reset'].includes(named.type)) continue;
            field = named.type === 'hidden'
                ? named.closest('.field')?.querySelector('input:not([type="hidden"]), select, textarea')
                : named;
            if (field) break;
        }
        if (!field) continue;
        showError(field, messages.join(' '), 'server');
        mapped.set(name, field);
    }
    summary?.querySelectorAll('[data-validation-key]').forEach(item => {
        if (mapped.has(item.dataset.validationKey)) item.hidden = true;
    });
    if (summary && ![...summary.querySelectorAll('[data-validation-key]')].some(item => !item.hidden)) summary.hidden = true;
    const firstInvalid = forms.flatMap(controls).find(field => feedback.has(field));
    const focusError = () => {
        if (firstInvalid) revealField(firstInvalid);
        else if (summary) { summary.focus({ preventScroll: true }); summary.scrollIntoView({ block: 'center' }); }
    };
    if (document.readyState === 'complete') focusError();
    else window.addEventListener('load', focusError, { once: true });
})();
