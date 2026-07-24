const MAX_S2S_POSTBACKS = 5;

function uniqueStrings(values, caseInsensitive = false) {
    const result = [];
    const seen = new Set();
    for (const rawValue of Array.isArray(values) ? values : []) {
        if (typeof rawValue !== 'string') continue;
        const value = rawValue.trim();
        if (value === '') continue;
        const key = caseInsensitive ? value.toLowerCase() : value;
        if (seen.has(key)) continue;
        seen.add(key);
        result.push(value);
    }
    return result;
}

function filterCatalogValues(catalog, selected, query = '', caseInsensitive = false) {
    const selectedKeys = new Set(
        uniqueStrings(selected, caseInsensitive).map((value) => (
            caseInsensitive ? value.toLowerCase() : value
        ))
    );
    const needle = query.trim().toLowerCase();
    return uniqueStrings(catalog, caseInsensitive).filter((value) => {
        const key = caseInsensitive ? value.toLowerCase() : value;
        return !selectedKeys.has(key) && (needle === '' || value.toLowerCase().includes(needle));
    });
}

function parseThresholdCatalog(value, maximum) {
    const values = new Set();
    for (const rawToken of String(value || '').split(',')) {
        const token = rawToken.trim();
        if (!/^\d+$/.test(token)) continue;
        const parsed = Number(token);
        if (Number.isSafeInteger(parsed) && parsed >= 1 && parsed <= maximum) {
            values.add(parsed);
        }
    }
    return Array.from(values).sort((left, right) => left - right);
}

function ordinaryEventCatalogFromState(state) {
    const names = [];
    if (state?.scrollEnabled) {
        for (const threshold of parseThresholdCatalog(state.scrollThresholds, 100)) {
            names.push(`scroll_${threshold}`);
        }
    }
    if (state?.timeEnabled) {
        for (const threshold of parseThresholdCatalog(state.timeThresholds, 86400)) {
            names.push(`stay_${threshold}s`);
        }
    }
    for (const rawName of Array.isArray(state?.customEvents) ? state.customEvents : []) {
        const name = String(rawName || '').trim();
        if (
            /^[a-z][a-z0-9_]{0,63}$/.test(name)
            && name !== 'performance'
            && !name.startsWith('performance_')
            && !/^scroll_\d+$/.test(name)
            && !/^stay_\d+s$/.test(name)
        ) {
            names.push(name);
        }
    }
    return uniqueStrings(names);
}

function campaignStatusCatalog() {
    return uniqueStrings(
        Array.from(document.querySelectorAll('#conversion-status-rows .conversion-status-name-input'))
            .map((input) => input.value)
            .filter((name) => /^[A-Za-z][A-Za-z0-9 _-]{0,63}$/.test(name.trim())),
        true
    );
}

function campaignEventCatalog() {
    return ordinaryEventCatalogFromState({
        scrollEnabled: document.getElementById('events-scroll-tracking-toggle')?.checked === true,
        scrollThresholds: document.getElementById('events-scroll-thresholds')?.value || '',
        timeEnabled: document.getElementById('events-time-tracking-toggle')?.checked === true,
        timeThresholds: document.getElementById('events-time-thresholds')?.value || '',
        customEvents: Array.from(document.querySelectorAll('#custom-event-list .custom-event-name'))
            .map((input) => input.value),
    });
}

class CatalogChipField {
    constructor(root, options) {
        this.root = root;
        this.kind = options.kind;
        this.caseInsensitive = options.caseInsensitive === true;
        this.catalogProvider = options.catalogProvider;
        this.onChange = options.onChange;
        this.control = root.querySelector('[data-s2s-chip-control]');
        this.list = root.querySelector('[data-s2s-chip-list]');
        this.input = root.querySelector('[data-s2s-chip-input]');
        this.options = root.querySelector('[data-s2s-chip-options]');
        this.live = root.querySelector('[data-s2s-chip-live]');
        this.selected = [];
        this.catalog = [];
        this.activeIndex = -1;
        this.fieldName = '';
        this.bind();
        root._catalogChipField = this;
    }

    bind() {
        this.input.addEventListener('focus', () => this.open());
        this.input.addEventListener('click', () => this.open());
        this.input.addEventListener('input', () => {
            this.activeIndex = 0;
            this.renderOptions();
            this.open();
        });
        this.input.addEventListener('keydown', (event) => this.onKeyDown(event));
        this.input.addEventListener('blur', () => {
            window.setTimeout(() => this.close(), 0);
        });
        this.list.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-s2s-chip]');
            if (!button) return;
            this.remove(button.dataset.removeS2sChip || '');
        });
        this.options.addEventListener('mousedown', (event) => event.preventDefault());
        this.options.addEventListener('click', (event) => {
            const option = event.target.closest('[data-s2s-chip-option]');
            if (!option) return;
            this.add(option.dataset.s2sChipOption || '');
        });
    }

    setName(name) {
        this.fieldName = name;
        this.renderChips();
    }

    setIdentifiers(inputId, optionsId, label) {
        this.input.id = inputId;
        this.options.id = optionsId;
        this.input.setAttribute('aria-controls', optionsId);
        label.htmlFor = inputId;
        this.renderOptions();
    }

    setValues(values) {
        this.selected = uniqueStrings(values, this.caseInsensitive);
        this.refreshCatalog();
    }

    values() {
        return this.selected.slice();
    }

    refreshCatalog() {
        this.catalog = uniqueStrings(this.catalogProvider(), this.caseInsensitive);
        if (this.caseInsensitive) {
            const canonical = new Map(this.catalog.map((value) => [value.toLowerCase(), value]));
            this.selected = this.selected.map((value) => canonical.get(value.toLowerCase()) || value);
        }
        this.renderChips();
        this.renderOptions();
        this.onChange?.();
    }

    availableValue(value) {
        const needle = this.caseInsensitive ? value.toLowerCase() : value;
        return this.catalog.some((candidate) => (
            (this.caseInsensitive ? candidate.toLowerCase() : candidate) === needle
        ));
    }

    add(value) {
        const candidate = this.catalog.find((catalogValue) => (
            this.caseInsensitive
                ? catalogValue.toLowerCase() === value.toLowerCase()
                : catalogValue === value
        ));
        if (!candidate || this.selected.some((selected) => (
            this.caseInsensitive
                ? selected.toLowerCase() === candidate.toLowerCase()
                : selected === candidate
        ))) {
            return;
        }
        this.selected.push(candidate);
        this.input.value = '';
        this.activeIndex = 0;
        this.renderChips();
        this.renderOptions();
        this.announce(`${candidate} added.`);
        this.onChange?.();
        this.input.focus();
    }

    remove(value) {
        const key = this.caseInsensitive ? value.toLowerCase() : value;
        const removed = this.selected.find((selected) => (
            (this.caseInsensitive ? selected.toLowerCase() : selected) === key
        ));
        this.selected = this.selected.filter((selected) => (
            (this.caseInsensitive ? selected.toLowerCase() : selected) !== key
        ));
        this.renderChips();
        this.renderOptions();
        if (removed) this.announce(`${removed} removed.`);
        this.onChange?.();
        this.input.focus();
    }

    unavailableValues() {
        return this.selected.filter((value) => !this.availableValue(value));
    }

    onKeyDown(event) {
        const available = this.filteredOptions();
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (available.length === 0) return;
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            this.activeIndex = (this.activeIndex + direction + available.length) % available.length;
            this.renderOptions();
            this.open();
            return;
        }
        if (event.key === 'Enter') {
            if (!this.options.hidden && available[this.activeIndex]) {
                event.preventDefault();
                this.add(available[this.activeIndex]);
            }
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            this.close();
            return;
        }
        if (event.key === 'Backspace' && this.input.value === '' && this.selected.length > 0) {
            event.preventDefault();
            this.remove(this.selected[this.selected.length - 1]);
        }
    }

    filteredOptions() {
        return filterCatalogValues(
            this.catalog,
            this.selected,
            this.input.value,
            this.caseInsensitive
        );
    }

    renderChips() {
        this.list.replaceChildren();
        const emptyValue = document.createElement('input');
        emptyValue.type = 'hidden';
        emptyValue.name = this.fieldName;
        emptyValue.value = '';
        this.list.append(emptyValue);
        for (const value of this.selected) {
            const chip = document.createElement('span');
            chip.className = 's2s-chip';
            if (!this.availableValue(value)) {
                chip.classList.add('is-unavailable');
                chip.title = 'This value is no longer available in the campaign catalog.';
            }

            const text = document.createElement('span');
            text.textContent = value;
            chip.append(text);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.dataset.removeS2sChip = value;
            remove.setAttribute('aria-label', `Remove ${value}`);
            const icon = document.createElement('i');
            icon.className = 'bi bi-x';
            icon.setAttribute('aria-hidden', 'true');
            remove.append(icon);
            chip.append(remove);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = this.fieldName;
            hidden.value = value;
            chip.append(hidden);
            this.list.append(chip);
        }
    }

    renderOptions() {
        const values = this.filteredOptions();
        this.options.replaceChildren();
        if (values.length === 0) {
            const empty = document.createElement('li');
            empty.className = 's2s-chip-options-empty';
            empty.setAttribute('role', 'presentation');
            empty.textContent = this.catalog.length === 0 ? 'No configured values' : 'No matching values';
            this.options.append(empty);
            this.activeIndex = -1;
            this.input.removeAttribute('aria-activedescendant');
            return;
        }
        if (this.activeIndex < 0 || this.activeIndex >= values.length) {
            this.activeIndex = 0;
        }
        values.forEach((value, index) => {
            const option = document.createElement('li');
            option.id = `${this.options.id}-option-${index}`;
            option.dataset.s2sChipOption = value;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', index === this.activeIndex ? 'true' : 'false');
            option.textContent = value;
            this.options.append(option);
        });
        this.input.setAttribute('aria-activedescendant', `${this.options.id}-option-${this.activeIndex}`);
    }

    open() {
        this.options.hidden = false;
        this.input.setAttribute('aria-expanded', 'true');
        this.renderOptions();
    }

    close() {
        this.options.hidden = true;
        this.input.setAttribute('aria-expanded', 'false');
        this.input.removeAttribute('aria-activedescendant');
    }

    announce(message) {
        this.live.textContent = '';
        window.setTimeout(() => {
            this.live.textContent = message;
        }, 0);
    }
}

function parseInitialS2sRules() {
    const element = document.getElementById('s2s-initial-data');
    if (!element) return [];
    try {
        const parsed = JSON.parse(element.textContent || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        return [];
    }
}

function initializePostbackSettings() {
    const container = document.getElementById('s2s_container');
    const template = document.getElementById('s2s-rule-template');
    const addButton = document.getElementById('add-s2s-item');
    const emptyState = document.getElementById('s2s-empty-state');
    if (!container || !template || !addButton || !emptyState) return;

    const chipFields = [];

    const validateRule = (rule) => {
        const statuses = rule.querySelector('[data-s2s-chip-field="statuses"]')?._catalogChipField;
        const events = rule.querySelector('[data-s2s-chip-field="events"]')?._catalogChipField;
        if (!statuses || !events) return;

        const unavailableStatuses = statuses.unavailableValues();
        const unavailableEvents = events.unavailableValues();
        let statusError = '';
        let eventError = '';
        if (unavailableStatuses.length > 0) {
            statusError = `Remove unavailable statuses: ${unavailableStatuses.join(', ')}.`;
        } else if (statuses.values().length === 0 && events.values().length === 0) {
            statusError = 'Select at least one conversion status or event.';
        }
        if (unavailableEvents.length > 0) {
            eventError = `Remove unavailable events: ${unavailableEvents.join(', ')}.`;
        }
        statuses.input.setCustomValidity(statusError);
        events.input.setCustomValidity(eventError);
        statuses.input.setAttribute('aria-invalid', statusError === '' ? 'false' : 'true');
        events.input.setAttribute('aria-invalid', eventError === '' ? 'false' : 'true');
    };

    const reindexRules = () => {
        const rules = Array.from(container.querySelectorAll('[data-s2s-rule]'));
        rules.forEach((rule, index) => {
            const number = index + 1;
            const url = rule.querySelector('[data-s2s-url]');
            const method = rule.querySelector('[data-s2s-method]');
            const urlLabel = rule.querySelector('[data-s2s-url-label]');
            const methodLabel = rule.querySelector('[data-s2s-method-label]');
            const statusesLabel = rule.querySelector('[data-s2s-statuses-label]');
            const eventsLabel = rule.querySelector('[data-s2s-events-label]');
            const statuses = rule.querySelector('[data-s2s-chip-field="statuses"]')._catalogChipField;
            const events = rule.querySelector('[data-s2s-chip-field="events"]')._catalogChipField;

            rule.querySelector('[data-s2s-rule-title]').textContent = `S2S postback ${number}`;
            const remove = rule.querySelector('.remove-s2s-item');
            remove.setAttribute('aria-label', `Delete S2S postback ${number}`);
            remove.title = `Delete S2S postback ${number}`;

            url.id = `s2s-url-${index}`;
            url.name = `postback.s2s[${index}][url]`;
            urlLabel.htmlFor = url.id;
            method.id = `s2s-method-${index}`;
            method.name = `postback.s2s[${index}][method]`;
            methodLabel.htmlFor = method.id;
            statuses.setName(`postback.s2s[${index}][statuses][]`);
            statuses.setIdentifiers(
                `s2s-statuses-${index}`,
                `s2s-statuses-options-${index}`,
                statusesLabel
            );
            events.setName(`postback.s2s[${index}][events][]`);
            events.setIdentifiers(
                `s2s-events-${index}`,
                `s2s-events-options-${index}`,
                eventsLabel
            );
            validateRule(rule);
        });
        emptyState.hidden = rules.length !== 0;
        addButton.disabled = rules.length >= MAX_S2S_POSTBACKS;
        addButton.title = addButton.disabled
            ? `A campaign supports at most ${MAX_S2S_POSTBACKS} S2S postbacks.`
            : '';
    };

    const addRule = (data = {}, focus = false) => {
        if (container.querySelectorAll('[data-s2s-rule]').length >= MAX_S2S_POSTBACKS) return;
        const fragment = template.content.cloneNode(true);
        const rule = fragment.querySelector('[data-s2s-rule]');
        const statusRoot = rule.querySelector('[data-s2s-chip-field="statuses"]');
        const eventRoot = rule.querySelector('[data-s2s-chip-field="events"]');
        const statuses = new CatalogChipField(statusRoot, {
            kind: 'statuses',
            caseInsensitive: true,
            catalogProvider: campaignStatusCatalog,
            onChange: () => validateRule(rule),
        });
        const events = new CatalogChipField(eventRoot, {
            kind: 'events',
            caseInsensitive: false,
            catalogProvider: campaignEventCatalog,
            onChange: () => validateRule(rule),
        });
        chipFields.push(statuses, events);

        const url = rule.querySelector('[data-s2s-url]');
        const method = rule.querySelector('[data-s2s-method]');
        url.value = typeof data.url === 'string' ? data.url : '';
        method.value = data.method === 'POST' ? 'POST' : 'GET';
        container.append(rule);
        statuses.setValues(data.statuses);
        events.setValues(data.events);
        reindexRules();
        if (focus) url.focus();
    };

    for (const rule of parseInitialS2sRules()) {
        addRule(rule);
    }
    reindexRules();

    addButton.addEventListener('click', () => addRule({
        url: '',
        method: 'GET',
        statuses: [],
        events: [],
    }, true));
    container.addEventListener('click', (event) => {
        const remove = event.target.closest('.remove-s2s-item');
        if (!remove) return;
        const rule = remove.closest('[data-s2s-rule]');
        const removedFields = Array.from(rule.querySelectorAll('[data-s2s-chip-field]'))
            .map((root) => root._catalogChipField);
        rule.remove();
        for (const field of removedFields) {
            const index = chipFields.indexOf(field);
            if (index !== -1) chipFields.splice(index, 1);
        }
        reindexRules();
    });

    let refreshQueued = false;
    const refreshCatalogs = () => {
        if (refreshQueued) return;
        refreshQueued = true;
        queueMicrotask(() => {
            refreshQueued = false;
            chipFields.forEach((field) => field.refreshCatalog());
            container.querySelectorAll('[data-s2s-rule]').forEach(validateRule);
        });
    };
    document.addEventListener('input', (event) => {
        if (event.target.matches(
            '.conversion-status-name-input, .custom-event-name, #events-scroll-thresholds, #events-time-thresholds'
        )) {
            refreshCatalogs();
        }
    });
    document.addEventListener('change', (event) => {
        if (event.target.matches(
            '#events-scroll-tracking-toggle, #events-time-tracking-toggle'
        )) {
            refreshCatalogs();
        }
    });
    for (const target of [
        document.getElementById('conversion-status-rows'),
        document.getElementById('custom-event-list'),
    ]) {
        if (target) {
            new MutationObserver(refreshCatalogs).observe(target, { childList: true, subtree: true });
        }
    }
    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-s2s-chip-field]')) {
            chipFields.forEach((field) => field.close());
        }
    });
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePostbackSettings);
    } else {
        initializePostbackSettings();
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        filterCatalogValues,
        ordinaryEventCatalogFromState,
        parseThresholdCatalog,
        uniqueStrings,
    };
}
