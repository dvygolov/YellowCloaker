const MAX_EVENT_THRESHOLDS = 32;
const MAX_CUSTOM_EVENTS = 64;
const EVENT_THRESHOLD_FIELDS = [
    {
        inputId: 'events-scroll-thresholds',
        toggleId: 'events-scroll-tracking-toggle',
        maximum: 100,
        label: 'scroll',
    },
    {
        inputId: 'events-time-thresholds',
        toggleId: 'events-time-tracking-toggle',
        maximum: 86400,
        label: 'visible-time',
    },
];

function customEventRows() {
    return Array.from(document.querySelectorAll('#custom-event-list [data-custom-event-row]'));
}

function normalizeEventName(value) {
    return String(value || '').trim().toLowerCase();
}

function isReservedEventName(name) {
    return name === 'performance'
        || name.startsWith('performance_')
        || /^scroll_\d+$/.test(name)
        || /^stay_\d+s$/.test(name);
}

function reindexCustomEvents() {
    const rows = customEventRows();
    rows.forEach((row, index) => {
        const input = row.querySelector('.custom-event-name');
        const copyButton = row.querySelector('.copy-custom-event');
        const removeButton = row.querySelector('.remove-custom-event');
        if (input) {
            input.id = `custom-event-name-${index}`;
            input.name = `events.custom[${index}]`;
        }
        const label = row.querySelector('label');
        if (label) label.htmlFor = `custom-event-name-${index}`;
        if (removeButton) {
            const eventName = input?.value.trim() || 'event';
            removeButton.setAttribute('aria-label', `Delete ${eventName}`);
        }
        if (copyButton) {
            const eventName = input?.value.trim() || 'event';
            copyButton.setAttribute('aria-label', `Copy code for ${eventName}`);
        }
    });

    const emptyState = document.getElementById('custom-event-empty');
    if (emptyState) emptyState.hidden = rows.length !== 0;
    const addButton = document.getElementById('add-custom-event');
    if (addButton) {
        addButton.disabled = rows.length >= MAX_CUSTOM_EVENTS;
        addButton.title = rows.length >= MAX_CUSTOM_EVENTS
            ? `A campaign can contain at most ${MAX_CUSTOM_EVENTS} custom events.`
            : '';
    }
}

function validateCustomEvents() {
    const rows = customEventRows();
    const counts = new Map();

    rows.forEach((row) => {
        const input = row.querySelector('.custom-event-name');
        const name = normalizeEventName(input?.value);
        if (name) counts.set(name, (counts.get(name) || 0) + 1);
    });

    rows.forEach((row) => {
        const input = row.querySelector('.custom-event-name');
        if (!input) return;
        const name = normalizeEventName(input.value);
        let message = '';

        if (name && input.value !== name) {
            message = 'Use lowercase letters, numbers and underscores only.';
        } else if (name && !/^[a-z][a-z0-9_]{0,63}$/.test(name)) {
            message = 'Start with a lowercase letter; then use only lowercase letters, numbers and underscores.';
        } else if (name && isReservedEventName(name)) {
            message = 'This name is reserved for a built-in YellowTDS event.';
        } else if (name && (counts.get(name) || 0) > 1) {
            message = 'Custom event names must be unique.';
        } else if (rows.length > MAX_CUSTOM_EVENTS) {
            message = `A campaign can contain at most ${MAX_CUSTOM_EVENTS} custom events.`;
        }

        input.setCustomValidity(message);
        const copyButton = row.querySelector('.copy-custom-event');
        if (copyButton) {
            copyButton.disabled = name === '' || message !== '';
            copyButton.dataset.snippet = name === '' || message !== ''
                ? ''
                : `ytdsEvent('${name}');`;
        }
    });
}

function addCustomEventRow() {
    if (customEventRows().length >= MAX_CUSTOM_EVENTS) {
        return;
    }
    const index = customEventRows().length;
    const row = document.createElement('div');
    row.className = 'events-custom-row events-custom-row-new';
    row.dataset.customEventRow = '';
    row.innerHTML = `
        <div>
            <label class="visually-hidden" for="custom-event-name-${index}">Custom event name</label>
            <input id="custom-event-name-${index}" type="text" class="form-control custom-event-name" name="events.custom[${index}]" value="" placeholder="cta_click" pattern="[a-z][a-z0-9_]{0,63}" maxlength="64" required>
            <small>Lowercase letters, numbers and underscores; start with a letter.</small>
        </div>
        <div class="events-custom-actions">
            <button type="button" class="btn btn-outline-light btn-sm copy-custom-event" title="Copy landing code" aria-label="Copy code for event" disabled><i class="bi bi-copy"></i><span>Copy</span></button>
            <button type="button" class="btn btn-danger campaign-icon-btn remove-custom-event" title="Delete event" aria-label="Delete event"><i class="bi bi-trash"></i></button>
        </div>`;

    document.getElementById('custom-event-list')?.appendChild(row);
    reindexCustomEvents();
    validateCustomEvents();
    row.querySelector('.custom-event-name')?.focus();
}

function parseEventThresholds(value, maximum) {
    const validValues = new Set();
    const invalidValues = [];
    const tokens = String(value || '').split(',').filter((token) => token !== '');

    for (const token of tokens) {
        if (!/^\d+$/.test(token)) {
            invalidValues.push(token);
            continue;
        }
        const number = Number(token);
        if (!Number.isSafeInteger(number) || number < 1 || number > maximum) {
            invalidValues.push(token);
            continue;
        }
        validValues.add(number);
    }

    return {
        values: Array.from(validValues).sort((left, right) => left - right),
        invalidValues,
    };
}

function normalizeEventThresholdInput(inputId, toggleId, maximum) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    if (!input || !toggle) return [];

    const parsed = parseEventThresholds(input.value, maximum);
    input.value = parsed.values.join(',');
    renderEventThresholdTags(inputId, parsed.values, toggle.checked);
    return parsed.values;
}

function eventThresholdField(inputId) {
    return EVENT_THRESHOLD_FIELDS.find((field) => field.inputId === inputId) || null;
}

function eventThresholdElements(inputId) {
    const input = document.getElementById(inputId);
    const control = document.querySelector(`[data-threshold-control="${inputId}"]`);
    const chips = control?.querySelector('[data-threshold-chips]');
    const entry = control?.querySelector(`[data-threshold-entry-for="${inputId}"]`);
    return { input, chips, entry };
}

function updateEventThresholdValue(input, values, emit = false) {
    const nextValue = values.join(',');
    if (input.value === nextValue) return;
    input.value = nextValue;
    if (emit) {
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function renderEventThresholdTags(inputId, values, enabled) {
    const { chips, entry } = eventThresholdElements(inputId);
    if (!chips || !entry) return;

    chips.querySelectorAll('.events-threshold-chip').forEach((chip) => chip.remove());
    values.forEach((value) => {
        const chip = document.createElement('span');
        chip.className = 'events-threshold-chip';
        chip.dataset.thresholdValue = String(value);
        chip.innerHTML = `<span>${value}</span><button type="button" class="events-threshold-remove" aria-label="Remove threshold ${value}" title="Remove ${value}">&times;</button>`;
        chip.querySelector('button').addEventListener('click', () => removeEventThresholdTag(inputId, value));
        chips.insertBefore(chip, entry);
    });
    entry.disabled = !enabled;
    entry.placeholder = enabled ? entry.dataset.placeholder || entry.placeholder : '';
}

function setEventThresholdEntryMessage(entry, message) {
    entry.setCustomValidity(message);
    entry.title = message;
}

function addEventThresholdTags(inputId, rawValue) {
    const field = eventThresholdField(inputId);
    const { input, entry } = eventThresholdElements(inputId);
    if (!field || !input || !entry) return;

    const parsedCurrent = parseEventThresholds(input.value, field.maximum);
    const parsedNew = parseEventThresholds(rawValue, field.maximum);
    const invalid = parsedNew.invalidValues.length !== 0;
    const nextValues = new Set(parsedCurrent.values);
    parsedNew.values.forEach((value) => nextValues.add(value));
    const values = Array.from(nextValues).sort((left, right) => left - right);

    if (invalid) {
        setEventThresholdEntryMessage(entry, `Use whole numbers from 1 to ${field.maximum}.`);
        return;
    }
    if (values.length > MAX_EVENT_THRESHOLDS) {
        setEventThresholdEntryMessage(entry, `Use at most ${MAX_EVENT_THRESHOLDS} ${field.label} thresholds.`);
        return;
    }

    setEventThresholdEntryMessage(entry, '');
    entry.value = '';
    updateEventThresholdValue(input, values, true);
    renderEventThresholdTags(inputId, values, true);
    validateEventThresholds();
}

function removeEventThresholdTag(inputId, value) {
    const field = eventThresholdField(inputId);
    const { input } = eventThresholdElements(inputId);
    if (!field || !input) return;
    const values = parseEventThresholds(input.value, field.maximum).values
        .filter((threshold) => threshold !== value);
    updateEventThresholdValue(input, values, true);
    renderEventThresholdTags(inputId, values, true);
    validateEventThresholds();
}

function initializeEventThresholdTags({ inputId, toggleId, maximum }) {
    const { input, entry } = eventThresholdElements(inputId);
    if (!input || !entry) return;
    entry.dataset.placeholder = entry.placeholder;
    entry.addEventListener('beforeinput', (event) => {
        if (typeof event.data === 'string' && /[^0-9,\s]/.test(event.data)) {
            event.preventDefault();
        }
    });
    entry.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            addEventThresholdTags(inputId, entry.value);
        } else if (event.key === 'Backspace' && entry.value === '') {
            const field = eventThresholdField(inputId);
            const values = field ? parseEventThresholds(input.value, field.maximum).values : [];
            if (values.length) removeEventThresholdTag(inputId, values[values.length - 1]);
        }
    });
    entry.addEventListener('input', () => {
        entry.value = entry.value.replace(/[^0-9,\s]/g, '');
        setEventThresholdEntryMessage(entry, '');
        if (entry.value.includes(',')) addEventThresholdTags(inputId, entry.value);
    });
    entry.addEventListener('blur', () => {
        if (entry.value.trim() !== '') addEventThresholdTags(inputId, entry.value);
    });
    document.getElementById(toggleId)?.addEventListener('change', () => {
        normalizeEventThresholdInput(inputId, toggleId, maximum);
        validateEventThresholds();
    });
}

function normalizeEventThresholdInputs() {
    EVENT_THRESHOLD_FIELDS.forEach(({ inputId, toggleId, maximum }) => {
        normalizeEventThresholdInput(inputId, toggleId, maximum);
    });
    validateEventThresholds();
}

function validateEventThresholdInput(inputId, toggleId, maximum, label) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    const entry = eventThresholdElements(inputId).entry;
    if (!input || !toggle) return;

    input.required = toggle.checked;
    if (!toggle.checked) {
        input.setCustomValidity('');
        entry?.setCustomValidity('');
        return;
    }

    const parsed = parseEventThresholds(input.value, maximum);
    let message = '';
    if (parsed.values.length === 0 && parsed.invalidValues.length === 0) {
        message = `Add at least one ${label} threshold.`;
    } else if (parsed.invalidValues.length !== 0) {
        message = `${label} thresholds must be whole numbers from 1 to ${maximum}.`;
    } else if (parsed.values.length > MAX_EVENT_THRESHOLDS) {
        message = `Use at most ${MAX_EVENT_THRESHOLDS} ${label} thresholds.`;
    }
    input.setCustomValidity(message);
    entry?.setCustomValidity(message);
}

function validateEventThresholds() {
    EVENT_THRESHOLD_FIELDS.forEach(({ inputId, toggleId, maximum, label }) => {
        validateEventThresholdInput(inputId, toggleId, maximum, label);
    });
}

async function copyText(text) {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
        return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
}

async function copyCustomEventSnippet(button) {
    const snippet = button.dataset.snippet || '';
    if (button.disabled || snippet === '') return;

    try {
        await copyText(snippet);
        const original = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check-lg"></i><span>Copied</span>';
        window.setTimeout(() => {
            button.innerHTML = original;
        }, 1400);
    } catch (error) {
        window.prompt('Copy this code:', snippet);
    }
}

if (typeof document !== 'undefined') {
    document.getElementById('add-custom-event')?.addEventListener('click', addCustomEventRow);
    document.getElementById('custom-event-list')?.addEventListener('click', (event) => {
        const copyButton = event.target.closest('.copy-custom-event');
        if (copyButton) {
            void copyCustomEventSnippet(copyButton);
            return;
        }

        const removeButton = event.target.closest('.remove-custom-event');
        if (!removeButton) return;
        removeButton.closest('[data-custom-event-row]')?.remove();
        reindexCustomEvents();
        validateCustomEvents();
    });
    document.getElementById('custom-event-list')?.addEventListener('input', (event) => {
        if (!event.target.matches('.custom-event-name')) return;
        reindexCustomEvents();
        validateCustomEvents();
    });
    EVENT_THRESHOLD_FIELDS.forEach(initializeEventThresholdTags);

    window.normalizeEventThresholdInputs = normalizeEventThresholdInputs;
    normalizeEventThresholdInputs();
    reindexCustomEvents();
    validateCustomEvents();
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        parseEventThresholds,
        addEventThresholdTags,
    };
}
