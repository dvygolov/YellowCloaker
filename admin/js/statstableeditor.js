const qs = (sel, ctx = document) => ctx.querySelector(sel);
const qsa = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

const MAX_ORDERBY_RULES = 3;
const CUSTOM_COLUMN_FORMATS = ['number', 'percent', 'currency'];
const FORMULA_OPERATOR_TOKENS = ['7', '8', '9', '-', '*', '4', '5', '6', '+', '/', '1', '2', '3', '(', ')', '0', '.'];
let customColumnsState = new Map();
let statusColumnsState = new Map();
let availableMetricsMeta = new Map();
let activeCustomFormulaField = null;
let campaignStatusesState = [];

function initializeStatsTableEditor(availableColumns, selectedMetrics, availableDimensions, selectedDimensions, tableName, saveUrl, existingFilters, existingOrderby, campaignStatuses = []) {
    const MAX_GROUPBY_SELECTIONS = 3;

    customColumnsState = new Map();
    statusColumnsState = new Map();
    availableMetricsMeta = new Map();
    campaignStatusesState = Array.isArray(campaignStatuses) ? campaignStatuses.filter(Boolean).map(String) : [];
    availableColumns.forEach((column) => {
        const field = typeof column === 'string' ? column : column.field;
        availableMetricsMeta.set(field, column);
    });

    const selectedMetricObjects = Array.isArray(selectedMetrics) ? selectedMetrics : [];
    const customMetrics = selectedMetricObjects.filter((item) => typeof item === 'object' && item?.custom);
    const statusMetrics = selectedMetricObjects.filter((item) => typeof item === 'object' && item?.status_metric);
    customMetrics.forEach((column) => customColumnsState.set(column.field, normalizeCustomColumn(column)));
    statusMetrics.forEach((column) => {
        const normalized = normalizeStatusColumn(column);
        statusColumnsState.set(normalized.field, normalized);
        availableMetricsMeta.set(normalized.field, normalized);
    });

    initializeSortable('metricsColumns', 'metrics');
    initializeSortable('dimensionsColumns', 'dimensions');

    const tableNameInput = document.getElementById('tableName');
    if (tableName) tableNameInput.value = tableName;

    const regularDimensions = selectedDimensions.filter((d) => !d.startsWith('param.'));
    const paramDimensions = selectedDimensions.filter((d) => d.startsWith('param.'));

    addColumnsToList('metricsColumns', selectedMetricObjects, [...availableColumns, ...statusMetrics, ...customMetrics], existingOrderby);
    addColumnsToList('dimensionsColumns', regularDimensions, availableDimensions);
    reorderItemsByFields('metricsColumns', selectedMetricObjects);

    for (const pd of paramDimensions) addParamItem('dimensionsColumns', pd.substring(6));
    reorderItemsByFields('dimensionsColumns', selectedDimensions);
    enforceDimensionLimit(MAX_GROUPBY_SELECTIONS);
    initializeFilters(existingFilters);
    renderCustomColumnsList();

    setupSelectButtons('selectAllMetrics', 'deselectAllMetrics', 'metricsColumns');

    document.getElementById('metricsColumns').addEventListener('change', (e) => {
        if (!e.target.matches('input[type="checkbox"]')) return;
        updateSaveButtonState();
        if (!e.target.checked) {
            const item = e.target.closest('.column-item');
            const btn = item?.querySelector('.sort-toggle');
            if (btn) setSortToggleState(btn, 'none');
        }
        updateSortToggleAvailability();
    });

    document.getElementById('dimensionsColumns').addEventListener('change', (e) => {
        if (!e.target.matches('input[type="checkbox"]')) return;
        enforceDimensionLimit(MAX_GROUPBY_SELECTIONS);
        updateSaveButtonState();
    });

    document.removeEventListener('click', handleAddParamDimension);
    document.addEventListener('click', handleAddParamDimension);

    document.getElementById('openCustomColumns').onclick = () => toggleCustomColumnsModal(true);
    document.getElementById('closeCustomColumns').onclick = () => toggleCustomColumnsModal(false);
    document.getElementById('addCustomColumn').onclick = () => addCustomColumn();
    document.getElementById('customColumnsList').oninput = handleCustomColumnInput;
    document.getElementById('customColumnsList').onclick = handleCustomColumnClick;
    document.getElementById('customColumnsList').onfocusin = handleCustomColumnFocus;
    document.getElementById('openStatusColumn').onclick = () => toggleStatusColumnModal(true);
    document.getElementById('closeStatusColumn').onclick = () => toggleStatusColumnModal(false);
    document.getElementById('addStatusColumn').onclick = addStatusColumn;
    document.getElementById('statusCalculationChoices').onchange = updateStatusOccurrenceVisibility;
    document.getElementById('configuredStatusColumns').onclick = handleConfiguredStatusColumnClick;
    document.getElementById('statusColumnStatus').onchange = updateStatusColumnDefaultTitle;
    document.getElementById('statusCalculationChoices').addEventListener('change', updateStatusColumnDefaultTitle);
    populateStatusColumnOptions();
    renderConfiguredStatusColumns();

    tableNameInput.addEventListener('input', () => updateSaveButtonState());
    updateSaveButtonState();

    document.getElementById('saveTableBtn').onclick = async () => {
        const name = tableNameInput.value.trim();
        if (!name) { alert('Please enter a table name'); return; }

        const columns = collectSelectedMetricConfigs();
        if (!columns.length) { alert('Please select at least one metric column'); return; }

        const invalidCustom = getInvalidCustomColumns(columns.filter((c) => typeof c === 'object' && c.custom));
        if (invalidCustom.length) {
            alert('Please fix custom columns: ' + invalidCustom.join(', '));
            return;
        }

        const groupby = getSelectedItems('dimensionsColumns');
        if (!groupby.length) { alert('Please select at least one dimension for grouping'); return; }
        if (groupby.length > MAX_GROUPBY_SELECTIONS) {
            alert(`You can select at most ${MAX_GROUPBY_SELECTIONS} dimensions for grouping`);
            return;
        }

        const filters = collectFilters();
        const orderby = collectOrderby();

        try {
            const res = await fetch(saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, columns, groupby, filters, orderby }),
            });
            if (!res.ok) throw new Error('Network response was not ok');

            const data = await res.json();
            if (data.error) throw new Error(data.msg || data.result || 'Save failed');
            window.location.reload();
        } catch (err) {
            alert('Error saving table: ' + err.message);
        }
    };

    document.getElementById('cancelTableBtn').onclick = () => jQuery.modal.close();
};

function toggleCustomColumnsModal(show) {
    const modal = document.getElementById('customColumnsModal');
    if (!modal) return;
    modal.style.display = show ? 'block' : 'none';
    if (show) {
        const firstInput = modal.querySelector('.custom-column-formula');
        if (firstInput) {
            activeCustomFormulaField = firstInput;
        }
    } else {
        activeCustomFormulaField = null;
    }
}

function normalizeStatusColumn(column) {
    const allowedCalculations = ['current', 'count', 'unique', 'nth'];
    const calculation = allowedCalculations.includes(column?.calculation) ? column.calculation : 'current';
    const occurrence = Math.max(1, Number.parseInt(column?.occurrence ?? 1, 10) || 1);
    return {
        field: String(column?.field || generateStatusColumnField(column?.status || 'status', calculation, occurrence)),
        title: String(column?.title || getStatusColumnDefaultTitle(column?.status || 'Status', calculation, occurrence)),
        status: String(column?.status || ''),
        calculation,
        occurrence,
        status_metric: true,
        width: Number(column?.width ?? -1),
    };
}

function toggleStatusColumnModal(show) {
    const modal = document.getElementById('statusColumnModal');
    if (!modal) return;
    if (show && campaignStatusesState.length === 0) {
        alert('Add at least one conversion status in Campaign settings first.');
        return;
    }
    modal.style.display = show ? 'block' : 'none';
    if (show) {
        populateStatusColumnOptions();
        updateStatusOccurrenceVisibility();
        updateStatusColumnDefaultTitle(true);
        renderConfiguredStatusColumns();
    }
}

function populateStatusColumnOptions() {
    const select = document.getElementById('statusColumnStatus');
    if (!select) return;
    const previous = select.value;
    select.innerHTML = campaignStatusesState
        .map((status) => `<option value="${escapeHtml(status)}">${escapeHtml(status)}</option>`)
        .join('');
    if (campaignStatusesState.includes(previous)) select.value = previous;
}

function getSelectedStatusCalculation() {
    return document.querySelector('input[name="statusCalculation"]:checked')?.value || 'current';
}

function updateStatusOccurrenceVisibility() {
    const row = document.getElementById('statusOccurrenceRow');
    if (row) row.style.display = getSelectedStatusCalculation() === 'nth' ? '' : 'none';
}

function getStatusCalculationLabel(calculation) {
    return {
        current: 'Current',
        count: 'Count',
        unique: 'Unique clickids',
        nth: 'Nth occurrence',
    }[calculation] || 'Current';
}

function getStatusColumnDefaultTitle(status, calculation, occurrence) {
    const suffix = calculation === 'nth'
        ? `${Math.max(1, Number.parseInt(occurrence, 10) || 1)}th occurrence`
        : getStatusCalculationLabel(calculation);
    return `${status} — ${suffix}`;
}

function updateStatusColumnDefaultTitle(force = false) {
    updateStatusOccurrenceVisibility();
    const input = document.getElementById('statusColumnTitle');
    const status = document.getElementById('statusColumnStatus')?.value || 'Status';
    const calculation = getSelectedStatusCalculation();
    const occurrence = document.getElementById('statusColumnOccurrence')?.value || 2;
    if (input && (force || !input.dataset.edited || input.value.trim() === '')) {
        input.value = getStatusColumnDefaultTitle(status, calculation, occurrence);
        input.dataset.edited = '';
    }
}

function generateStatusColumnField(status, calculation, occurrence) {
    const slug = String(status || 'status').toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '') || 'status';
    const occurrenceSuffix = calculation === 'nth' ? `_${Math.max(1, Number.parseInt(occurrence, 10) || 1)}` : '';
    const base = `status.${slug}_${calculation}${occurrenceSuffix}`;
    let field = base;
    let counter = 2;
    while (statusColumnsState.has(field) || availableMetricsMeta.has(field)) field = `${base}_${counter++}`;
    return field;
}

function addStatusColumn() {
    const status = document.getElementById('statusColumnStatus')?.value || '';
    const calculation = getSelectedStatusCalculation();
    const occurrence = Math.max(1, Number.parseInt(document.getElementById('statusColumnOccurrence')?.value || 1, 10) || 1);
    const titleInput = document.getElementById('statusColumnTitle');
    const title = titleInput?.value.trim() || getStatusColumnDefaultTitle(status, calculation, occurrence);
    if (!status) return;

    const field = generateStatusColumnField(status, calculation, occurrence);
    const column = normalizeStatusColumn({ field, title, status, calculation, occurrence, status_metric: true });
    statusColumnsState.set(field, column);
    availableMetricsMeta.set(field, column);
    rebuildMetricColumns([...getSelectedItems('metricsColumns'), field]);
    renderConfiguredStatusColumns();
    if (titleInput) {
        titleInput.dataset.edited = '';
        updateStatusColumnDefaultTitle(true);
    }
    updateSaveButtonState();
}

function rebuildMetricColumns(selectedFields) {
    const orderby = collectOrderby();
    const selected = selectedFields.map((field) => statusColumnsState.get(field) || customColumnsState.get(field) || field);
    addColumnsToList(
        'metricsColumns',
        selected,
        [...availableMetricsMeta.values(), ...statusColumnsState.values(), ...customColumnsState.values()],
        orderby
    );
    reorderItemsByFields('metricsColumns', selectedFields);
    updateSortToggleAvailability();
}

function renderConfiguredStatusColumns() {
    const container = document.getElementById('configuredStatusColumns');
    if (!container) return;
    if (statusColumnsState.size === 0) {
        container.innerHTML = '<div style="opacity:.65; padding:7px 0;">No custom status columns yet.</div>';
        return;
    }
    container.innerHTML = [...statusColumnsState.values()].map((column) => `
        <div class="configured-status-column" data-status-field="${escapeHtml(column.field)}">
            <span><strong>${escapeHtml(column.title)}</strong><small style="display:block; opacity:.68;">${escapeHtml(column.status)} · ${escapeHtml(getStatusCalculationLabel(column.calculation))}${column.calculation === 'nth' ? ` #${column.occurrence}` : ''}</small></span>
            <button type="button" class="btn btn-sm btn-danger remove-status-column">Delete</button>
        </div>
    `).join('');
}

function handleConfiguredStatusColumnClick(event) {
    const button = event.target.closest('.remove-status-column');
    if (!button) return;
    const row = button.closest('[data-status-field]');
    const field = row?.dataset.statusField;
    if (!field) return;
    statusColumnsState.delete(field);
    availableMetricsMeta.delete(field);
    qs(`#metricsColumns .column-item[data-field="${CSS.escape(field)}"]`)?.remove();
    renderConfiguredStatusColumns();
    updateSortToggleAvailability();
    updateSaveButtonState();
}

document.addEventListener('input', (event) => {
    if (event.target?.id === 'statusColumnTitle') event.target.dataset.edited = '1';
    if (event.target?.id === 'statusColumnOccurrence') updateStatusColumnDefaultTitle();
});

async function deleteStatsTable(tableName, deleteUrl) {
    if (!confirm(`Are you sure you want to delete table "${tableName}"?`)) return;
    try {
        const res = await fetch(deleteUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: tableName }),
        });
        const data = await res.json();
        if (data.error) throw new Error(data.msg || data.result || 'Delete failed');
        const url = new URL(window.location.href);
        if (url.searchParams.has('table')) {
            url.searchParams.delete('table');
            window.location.href = url.toString();
        } else {
            window.location.reload();
        }
    } catch (err) {
        alert('Error deleting table: ' + err.message);
    }
}

function normalizeCustomColumn(column) {
    return {
        field: String(column.field || generateCustomColumnField()),
        title: String(column.title || 'Custom metric'),
        formula: String(column.formula || '').replace(/\s+/g, '').toLowerCase(),
        decimals: Math.max(0, Math.min(8, Number(column.decimals ?? 2) || 0)),
        format: CUSTOM_COLUMN_FORMATS.includes(column.format) ? column.format : 'number',
        custom: true,
        width: Number(column.width ?? -1),
    };
}

function addColumnsToList(containerId, selectedItems, columns, orderbyRules) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    const isMetrics = containerId === 'metricsColumns';
    const selectedFields = selectedItems.map((item) => typeof item === 'string' ? item : item.field);
    const seen = new Set();

    for (const column of columns) {
        const field = typeof column === 'string' ? column : column.field;
        if (!field || seen.has(field)) continue;
        seen.add(field);
        const title = typeof column === 'string' ? formatColumnName(column) : (column.title || formatColumnName(column.field));
        const isSelected = selectedFields.includes(field);

        const div = document.createElement('div');
        div.className = 'column-item';
        div.dataset.field = field;
        if (column?.custom) div.dataset.custom = '1';
        if (column?.status_metric) div.dataset.statusMetric = '1';

        let sortToggleHtml = '';
        if (isMetrics) {
            const rule = orderbyRules?.find((r) => r.field === field);
            const state = rule ? rule.dir : 'none';
            const activeClass = state !== 'none' ? ' sort-active' : '';
            sortToggleHtml = `<button type="button" class="sort-toggle${activeClass}" data-sort="${state}" title="Sort">${getSortToggleIcon(state)}</button>`;
        }

        div.innerHTML = `
            <span class="drag-handle">☰</span>
            <input type="checkbox" ${isSelected ? 'checked' : ''}>
            <span class="column-label">${escapeHtml(title)}${column?.custom ? ' <span style="opacity:0.7">fx</span>' : ''}${column?.status_metric ? ' <span style="opacity:0.7">status</span>' : ''}</span>
            ${sortToggleHtml}
        `;

        if (isMetrics) {
            const btn = div.querySelector('.sort-toggle');
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const cb = div.querySelector('input[type="checkbox"]');
                if (!cb.checked) return;
                cycleSortToggle(btn);
            });
        }

        container.appendChild(div);
    }

    if (isMetrics) updateSortToggleAvailability();
}

function setupSelectButtons(selectAllId, deselectAllId, containerId) {
    document.getElementById(selectAllId).onclick = () => {
        for (const cb of qsa(`#${containerId} input[type="checkbox"]`)) {
            if (!cb.disabled) {
                cb.checked = true;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    };

    document.getElementById(deselectAllId).onclick = () => {
        document.querySelectorAll(`#${containerId} .param-item`).forEach((el) => el.remove());
        for (const cb of qsa(`#${containerId} input[type="checkbox"]`)) {
            cb.checked = false;
            cb.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };
}

function getSelectedItems(containerId) {
    return qsa(`#${containerId} .column-item`)
        .filter((el) => el.querySelector('input').checked)
        .map((el) => el.dataset.field);
}

function collectSelectedMetricConfigs() {
    return qsa('#metricsColumns .column-item')
        .filter((el) => el.querySelector('input').checked)
        .map((el) => {
            const field = el.dataset.field;
            if (customColumnsState.has(field)) {
                return normalizeCustomColumn(customColumnsState.get(field));
            }
            if (statusColumnsState.has(field)) {
                return normalizeStatusColumn(statusColumnsState.get(field));
            }
            const meta = availableMetricsMeta.get(field);
            if (meta && typeof meta === 'object' && meta.title) {
                return { field, title: meta.title };
            }
            return field;
        });
}

function handleAddParamDimension(e) {
    if (!e.target.closest('#addParamDimension')) return;
    const MAX_GROUPBY_SELECTIONS = 3;
    const total = qsa('#dimensionsColumns input[type="checkbox"]:checked').length;
    if (total >= MAX_GROUPBY_SELECTIONS) return;
    const name = prompt('URL param name:');
    if (!name || !name.trim()) return;
    const clean = name.trim().replace(/[^a-zA-Z0-9_]/g, '');
    if (!clean) return;
    addParamItem('dimensionsColumns', clean);
    enforceDimensionLimit(MAX_GROUPBY_SELECTIONS);
    updateSaveButtonState();
}

function addCustomColumn() {
    const field = generateCustomColumnField();
    const column = normalizeCustomColumn({ field, title: 'Custom metric', formula: '', decimals: 2, format: 'number', custom: true });
    customColumnsState.set(field, column);
    addColumnsToList('metricsColumns', collectSelectedMetricConfigs().concat([column]), [...availableMetricsMeta.values(), ...customColumnsState.values()], collectOrderby());
    reorderItemsByFields('metricsColumns', [...getSelectedItems('metricsColumns'), field]);
    const item = qs(`#metricsColumns .column-item[data-field="${CSS.escape(field)}"] input`);
    if (item) item.checked = true;
    renderCustomColumnsList();
    toggleCustomColumnsModal(true);
    const allFormulaInputs = document.querySelectorAll('#customColumnsList .custom-column-formula');
    if (allFormulaInputs.length > 0) {
        activeCustomFormulaField = allFormulaInputs[allFormulaInputs.length - 1];
        activeCustomFormulaField.focus();
    }
    updateSortToggleAvailability();
    updateSaveButtonState();
}

function renderCustomColumnsList() {
    const list = document.getElementById('customColumnsList');
    list.innerHTML = '';
    for (const column of customColumnsState.values()) {
        const row = document.createElement('div');
        row.className = 'form-group-inner';
        row.dataset.field = column.field;
        row.innerHTML = `
            <div class="row" style="row-gap:8px; margin-bottom:10px; border:1px solid #2a3245; border-radius:8px; padding:10px 6px;">
                <div class="col-lg-3 col-md-6 col-sm-12 col-xs-12">
                    <input type="text" class="form-control custom-column-title" value="${escapeHtml(column.title)}" placeholder="Column name">
                </div>
                <div class="col-lg-5 col-md-6 col-sm-12 col-xs-12">
                    <input type="text" class="form-control custom-column-formula" value="${escapeHtml(column.formula)}" placeholder="event.scroll_50/clicks*100">
                    <div style="font-size:12px; opacity:0.72; margin-top:6px;">Use metrics like <code>clicks</code>, <code>revenue</code>, <code>event.scroll_50</code>.</div>
                </div>
                <div class="col-lg-1 col-md-3 col-sm-4 col-xs-6">
                    <input type="number" min="0" max="8" class="form-control custom-column-decimals" value="${column.decimals}" title="Decimal places (0-8)">
                </div>
                <div class="col-lg-2 col-md-5 col-sm-6 col-xs-6">
                    <select class="form-control custom-column-format">
                        ${CUSTOM_COLUMN_FORMATS.map((format) => `<option value="${format}"${format === column.format ? ' selected' : ''}>${format}</option>`).join('')}
                    </select>
                </div>
                <div class="col-lg-1 col-md-4 col-sm-2 col-xs-12">
                    <button type="button" class="btn btn-danger btn-sm remove-custom-column">Delete</button>
                </div>
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="custom-column-status" style="font-size:12px; opacity:0.85;">${getCustomColumnStatus(column)}</div>
                </div>
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="custom-formula-builder" style="border-top:1px solid #25324a; margin-top:4px; padding-top:10px;">
                        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:flex-start;">
                            <div style="min-width:220px; flex:0 0 220px;">
                                <div style="font-size:12px; opacity:0.72; margin-bottom:6px;">Operators</div>
                                <div class="formula-operator-grid" style="display:grid; grid-template-columns:repeat(5, minmax(0, 1fr)); gap:6px;">
                                    ${FORMULA_OPERATOR_TOKENS.map((token) => `<button type="button" class="btn btn-sm btn-outline-light formula-token-btn" data-token="${escapeHtml(token)}">${escapeHtml(token)}</button>`).join('')}
                                </div>
                            </div>
                            <div style="flex:1 1 460px; min-width:260px;">
                                <div style="font-size:12px; opacity:0.72; margin-bottom:6px;">Metrics</div>
                                <div class="formula-metric-buttons" style="display:flex; flex-wrap:wrap; gap:6px;">${renderFormulaMetricButtons(column.field)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        list.appendChild(row);
    }
}

function renderFormulaMetricButtons(currentField) {
    const metrics = qsa('#metricsColumns .column-item')
        .map((item) => item.dataset.field)
        .filter((field, index, array) => field && field !== currentField && !field.startsWith('custom.') && array.indexOf(field) === index);
    return metrics.map((field) => {
        const meta = availableMetricsMeta.get(field);
        const title = typeof meta === 'object' && meta?.title ? meta.title : formatColumnName(field);
        return `<button type="button" class="btn btn-sm btn-outline-primary formula-token-btn" data-token="${escapeHtml(field)}">${escapeHtml(title)}</button>`;
    }).join('');
}

function handleCustomColumnInput(e) {
    const row = e.target.closest('[data-field]');
    if (!row) return;
    const field = row.dataset.field;
    const column = customColumnsState.get(field);
    if (!column) return;

    column.title = row.querySelector('.custom-column-title').value.trim() || 'Custom metric';
    column.formula = row.querySelector('.custom-column-formula').value.replace(/\s+/g, '');
    column.decimals = Math.max(0, Math.min(8, Number(row.querySelector('.custom-column-decimals').value) || 0));
    column.format = row.querySelector('.custom-column-format').value;
    customColumnsState.set(field, column);

    const label = qs(`#metricsColumns .column-item[data-field="${CSS.escape(field)}"] .column-label`);
    if (label) label.innerHTML = `${escapeHtml(column.title)} <span style="opacity:0.7">fx</span>`;
    const status = row.querySelector('.custom-column-status');
    if (status) status.textContent = getCustomColumnStatus(column);
    updateSaveButtonState();
}

function handleCustomColumnFocus(e) {
    const input = e.target.closest('.custom-column-formula');
    if (!input) return;
    activeCustomFormulaField = input;
}

function handleCustomColumnClick(e) {
    const tokenBtn = e.target.closest('.formula-token-btn');
    if (tokenBtn) {
        const row = tokenBtn.closest('[data-field]');
        if (row) {
            const formulaInput = row.querySelector('.custom-column-formula');
            if (formulaInput) activeCustomFormulaField = formulaInput;
        }
        insertFormulaToken(tokenBtn.dataset.token || '');
        return;
    }

    const btn = e.target.closest('.remove-custom-column');
    if (!btn) return;
    const row = btn.closest('[data-field]');
    if (!row) return;
    const field = row.dataset.field;
    customColumnsState.delete(field);
    qs(`#metricsColumns .column-item[data-field="${CSS.escape(field)}"]`)?.remove();
    row.remove();
    updateSortToggleAvailability();
    updateSaveButtonState();
}

function insertFormulaToken(token) {
    if (!token) return;
    const input = activeCustomFormulaField;
    if (!input) return;

    input.focus();
    const start = input.selectionStart ?? input.value.length;
    const end = input.selectionEnd ?? input.value.length;
    const currentValue = input.value || '';
    const nextValue = currentValue.slice(0, start) + token + currentValue.slice(end);
    input.value = nextValue;
    const caret = start + token.length;
    input.setSelectionRange(caret, caret);
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

function getInvalidCustomColumns(columns) {
    return columns
        .filter((column) => !isValidCustomColumn(column))
        .map((column) => column.title || column.field);
}

function tokenizeCustomFormula(formula) {
    const normalized = String(formula || '').replace(/\s+/g, '').toLowerCase();
    if (!normalized) {
        return null;
    }

    const tokens = [];
    let offset = 0;
    while (offset < normalized.length) {
        const chunk = normalized.slice(offset);
        const match = chunk.match(/^([a-z][a-z0-9_\.]*|\d+(?:\.\d+)?|[()+\-*/])/);
        if (!match) {
            return null;
        }

        const raw = match[1];
        offset += raw.length;

        if (/^\d+(?:\.\d+)?$/.test(raw)) {
            tokens.push({ type: 'number', value: raw });
        } else if (/^[()+\-*/]$/.test(raw)) {
            tokens.push({ type: 'operator', value: raw });
        } else if (/^(?:[a-z][a-z0-9_]*|event\.[a-z0-9_]+|status\.[a-z0-9_]+|custom\.[a-z0-9_]+)$/.test(raw)) {
            tokens.push({ type: 'field', value: raw });
        } else {
            return null;
        }
    }

    return tokens;
}

function hasValidCustomFormulaSyntax(formula) {
    const tokens = tokenizeCustomFormula(formula);
    if (!tokens || tokens.length === 0) {
        return false;
    }

    let balance = 0;
    let expectsOperand = true;

    for (const token of tokens) {
        if (token.type === 'number' || token.type === 'field') {
            if (!expectsOperand) {
                return false;
            }
            expectsOperand = false;
            continue;
        }

        if (token.value === '(') {
            if (!expectsOperand) {
                return false;
            }
            balance++;
            continue;
        }

        if (token.value === ')') {
            if (expectsOperand || balance === 0) {
                return false;
            }
            balance--;
            expectsOperand = false;
            continue;
        }

        if (expectsOperand) {
            if (token.value === '-') {
                continue;
            }
            return false;
        }

        expectsOperand = true;
    }

    return balance === 0 && !expectsOperand;
}

function isValidCustomColumn(column) {
    if (!column?.field || !/^custom\.[a-z0-9_]+$/.test(column.field)) return false;
    if (!column.title?.trim()) return false;
    if (!column.formula?.trim()) return false;
    const formula = String(column.formula).replace(/\s+/g, '').toLowerCase();
    return hasValidCustomFormulaSyntax(formula) && !/custom\./.test(formula);
}

function getCustomColumnStatus(column) {
    if (!column.formula?.trim()) {
        return 'Enter a formula.';
    }
    if (!isValidCustomColumn(column)) {
        return 'Invalid formula or column name.';
    }
    return `Format: ${column.format}, decimals: ${column.decimals}.`;
}

function generateCustomColumnField() {
    let counter = customColumnsState.size + 1;
    while (customColumnsState.has(`custom.metric_${counter}`)) counter++;
    return `custom.metric_${counter}`;
}

function updateSaveButtonState() {
    const metricsSelected = qsa('#metricsColumns input[type="checkbox"]').some((cb) => cb.checked);
    const dimensionsSelected = qsa('#dimensionsColumns input[type="checkbox"]').some((cb) => cb.checked);
    const name = document.getElementById('tableName').value.trim();
    const invalidSelectedCustom = getInvalidCustomColumns(collectSelectedMetricConfigs().filter((c) => typeof c === 'object' && c.custom));
    document.getElementById('saveTableBtn').disabled = !metricsSelected || !dimensionsSelected || !name || invalidSelectedCustom.length > 0;
}

function formatColumnName(field) {
    return field.split(/(?=[A-Z])/).join(' ').toLowerCase().replace(/\b\w/g, (l) => l.toUpperCase());
}

function initializeSortable(containerId, group) {
    new Sortable(document.getElementById(containerId), { animation: 150, group, handle: '.drag-handle' });
}

function reorderItemsByFields(containerId, orderedFields) {
    const container = document.getElementById(containerId);
    if (!container || !Array.isArray(orderedFields) || orderedFields.length === 0) return;
    const byField = new Map();
    qsa(`#${containerId} .column-item`).forEach((el) => byField.set(el.dataset.field, el));
    orderedFields.forEach((field) => {
        const el = byField.get(typeof field === 'string' ? field : field.field);
        if (el) container.appendChild(el);
    });
}

function getSortToggleIcon(state) {
    if (state === 'desc') return '▼';
    if (state === 'asc') return '▲';
    return '▼';
}

function setSortToggleState(btn, state) {
    btn.dataset.sort = state;
    btn.textContent = getSortToggleIcon(state);
    btn.className = 'sort-toggle' + (state !== 'none' ? ' sort-active' : '');
}

function cycleSortToggle(btn) {
    const current = btn.dataset.sort || 'none';
    if (current === 'none') {
        const activeCount = qsa('#metricsColumns .sort-toggle.sort-active').length;
        if (activeCount >= MAX_ORDERBY_RULES) return;
        setSortToggleState(btn, 'desc');
    } else if (current === 'desc') {
        setSortToggleState(btn, 'asc');
    } else {
        setSortToggleState(btn, 'none');
    }
    updateSortToggleAvailability();
}

function updateSortToggleAvailability() {
    const activeCount = qsa('#metricsColumns .sort-toggle.sort-active').length;
    for (const btn of qsa('#metricsColumns .sort-toggle')) {
        const item = btn.closest('.column-item');
        const cb = item.querySelector('input[type="checkbox"]');
        const isActive = btn.classList.contains('sort-active');
        btn.style.visibility = cb.checked ? 'visible' : 'hidden';
        btn.style.opacity = (!isActive && activeCount >= MAX_ORDERBY_RULES) ? '0.3' : '';
        btn.style.cursor = (!isActive && activeCount >= MAX_ORDERBY_RULES) ? 'not-allowed' : 'pointer';
    }
}

function collectOrderby() {
    const rules = [];
    for (const btn of qsa('#metricsColumns .sort-toggle.sort-active')) {
        const item = btn.closest('.column-item');
        rules.push({ field: item.dataset.field, dir: btn.dataset.sort });
    }
    return rules;
}

function enforceDimensionLimit(max) {
    const total = qsa('#dimensionsColumns input[type="checkbox"]:checked').length;
    const allBoxes = qsa('#dimensionsColumns input[type="checkbox"]');
    for (const cb of allBoxes) cb.disabled = !cb.checked && total >= max;
    const addBtn = document.getElementById('addParamDimension');
    if (addBtn) {
        addBtn.disabled = total >= max;
        addBtn.style.opacity = total >= max ? '0.5' : '';
    }
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
