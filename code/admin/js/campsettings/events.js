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

function sanitizeEventThresholdInput(input) {
    if (/^[0-9,]*$/.test(input.value)) {
        input.dataset.lastValidThresholdValue = input.value;
        return;
    }
    input.value = input.dataset.lastValidThresholdValue || '';
}

function normalizeEventThresholdInput(inputId, toggleId, maximum) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    if (!input || !toggle) return [];

    const parsed = parseEventThresholds(input.value, maximum);
    if (!toggle.checked || parsed.invalidValues.length === 0) {
        input.value = parsed.values.join(',');
    }
    return parsed.values;
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
    if (!input || !toggle) return;

    input.required = toggle.checked;
    if (!toggle.checked) {
        input.setCustomValidity('');
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
    EVENT_THRESHOLD_FIELDS.forEach(({ inputId, toggleId, maximum }) => {
        const input = document.getElementById(inputId);
        if (input) {
            input.dataset.lastValidThresholdValue = /^[0-9,]*$/.test(input.value)
                ? input.value
                : '';
        }
        input?.addEventListener('beforeinput', (event) => {
            if (typeof event.data === 'string' && /[^0-9,]/.test(event.data)) {
                event.preventDefault();
            }
        });
        input?.addEventListener('input', () => {
            sanitizeEventThresholdInput(input);
            validateEventThresholds();
        });
        input?.addEventListener('blur', () => {
            normalizeEventThresholdInput(inputId, toggleId, maximum);
            validateEventThresholds();
        });
        document.getElementById(toggleId)?.addEventListener('change', () => {
            normalizeEventThresholdInput(inputId, toggleId, maximum);
            validateEventThresholds();
        });
    });

    window.normalizeEventThresholdInputs = normalizeEventThresholdInputs;
    normalizeEventThresholdInputs();
    reindexCustomEvents();
    validateCustomEvents();
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        parseEventThresholds,
    };
}
