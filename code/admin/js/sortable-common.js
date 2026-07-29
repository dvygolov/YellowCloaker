// Common functions for sortable lists
function formatColumnName(field) {
    return field.charAt(0).toUpperCase() + field.slice(1).replace(/_/g, ' ');
}

function initializeSortable(elementId, group) {
    return new Sortable(document.getElementById(elementId), {
        animation: 150,
        group: group,
        handle: '.drag-handle', // Add handle for dragging
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag'
    });
}

function convertArrayToObjects(array) {
    if (Array.isArray(array) && array.length > 0 && typeof array[0] === 'string') {
        return array.map(field => ({
            field: field,
            title: formatColumnName(field)
        }));
    }
    return array;
}

function convertToColumnObjects(array) {
    if (Array.isArray(array) && array.length > 0 && typeof array[0] === 'string') {
        return array.map(field => (field));
    }
    return array;
}

function createSortableItem(field, title, isChecked) {
    return `
        <div class="sortable-item" data-field="${field}">
            <span class="drag-handle">☰</span>
            <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="handleCheckboxChange(this)">
            <span>${title || formatColumnName(field)}</span>
        </div>
    `;
}

function createParamItemElement(containerId, paramName) {
    const field = 'param.' + paramName;
    const container = document.getElementById(containerId);

    // Prevent duplicates
    if (container.querySelector(`[data-field="${field}"]`)) return null;

    const div = document.createElement('div');
    div.className = 'sortable-item column-item param-item';
    div.dataset.field = field;
    div.innerHTML = `
        <span class="drag-handle">☰</span>
        <input type="checkbox" checked onchange="handleParamCheckboxChange(this)">
        <span>${paramName}\u{1F310}</span>
    `;
    return div;
}

function addParamItem(containerId, paramName) {
    const div = createParamItemElement(containerId, paramName);
    if (!div) return null;
    document.getElementById(containerId).prepend(div);
    return div;
}

function handleParamCheckboxChange(cb) {
    if (!cb.checked) {
        cb.closest('.param-item').remove();
    }
    handleCheckboxChange(cb);
}

function getSelectedItems(containerId) {
    return $(`#${containerId} .sortable-item`)
        .filter((_, item) => $(item).find('input').is(':checked'))
        .map((_, item) => $(item).data('field'))
        .get();
}

function setupSelectButtons(selectAllId, deselectAllId, containerId) {
    $(`#${selectAllId}`).click(() => {
        $(`#${containerId} input[type="checkbox"]`).prop('checked', true);
        updateSaveButtonState();
    });

    $(`#${deselectAllId}`).click(() => {
        $(`#${containerId} .param-item`).remove();
        $(`#${containerId} input[type="checkbox"]`).prop('checked', false);
        updateSaveButtonState();
    });
}

function setupModalButtons(saveButtonId, cancelButtonId, saveCallback) {
    const $saveButton = $(`#${saveButtonId}`);
    $saveButton.off('click').click(saveCallback);
    
    $(`#${cancelButtonId}`).click(() => {
        $.modal.close();
    });

    // Initial button state
    updateSaveButtonState();
}

function handleCheckboxChange() {
    updateSaveButtonState();
}

// ── Shared filter constants & functions ──

const FILTER_FIELDS = ['country','lang','os','osver','brand','model','device','isp','client','clientver','flow','step','path','status','param'];
let _extraFilterFields = [];
const FILTER_OPERATORS = [
    { value: '=', label: '=' },
    { value: '!=', label: '!=' },
    { value: 'in', label: 'in' },
    { value: 'not_in', label: 'not in' },
    { value: 'is_null', label: 'is null' },
    { value: 'is_not_null', label: 'is not null' },
];

function initializeFilters(existingFilters, extraFields) {
    _extraFilterFields = extraFields || [];
    const rows = document.getElementById('filterRows');
    rows.innerHTML = '';

    const conditionSelect = document.getElementById('filterCondition');
    conditionSelect.value = existingFilters?.condition || 'AND';

    if (existingFilters?.rules?.length) {
        for (const rule of existingFilters.rules) {
            addFilterRowToDOM(rule.field, rule.operator, rule.value);
        }
    }

    document.removeEventListener('click', handleAddFilterClick);
    document.addEventListener('click', handleAddFilterClick);
}

function handleAddFilterClick(e) {
    if (e.target.closest('#addFilterRow')) {
        addFilterRowToDOM('', '=', '');
    }
}

function addFilterRowToDOM(field, operator, value) {
    // Detect if this is a param.* field being restored
    const isParam = field.startsWith('param.');
    const paramKey = isParam ? field.substring(6) : '';
    const selectedField = isParam ? 'param' : field;

    const allFields = [...FILTER_FIELDS.slice(0, -1), ..._extraFilterFields, 'param'];
    const fieldOptions = allFields
        .map(f => `<option value="${f}"${f === selectedField ? ' selected' : ''}>${f}</option>`)
        .join('');

    const opOptions = FILTER_OPERATORS
        .map(o => `<option value="${o.value}"${o.value === operator ? ' selected' : ''}>${o.label}</option>`)
        .join('');

    const needsValue = operator !== 'is_null' && operator !== 'is_not_null';
    let displayValue = value ?? '';
    if (Array.isArray(displayValue)) displayValue = displayValue.join(', ');

    const row = document.createElement('div');
    row.className = 'filter-row';
    row.innerHTML = `
        <div class="filter-row-main">
            <select class="form-control filter-field">${fieldOptions}</select>
            <select class="form-control filter-op" style="display:${isParam ? 'none' : ''}">${opOptions}</select>
            <input type="text" class="form-control filter-value" style="display:${isParam || !needsValue ? 'none' : ''}" placeholder="value" value="${isParam ? '' : displayValue}">
            <button class="btn btn-sm btn-outline-danger filter-remove" title="Remove">&times;</button>
        </div>
        <div class="filter-row-param" style="display:${isParam ? '' : 'none'}">
            <span class="filter-param-label">param.</span>
            <input type="text" class="form-control filter-param-key" placeholder="key" value="${paramKey}">
            <select class="form-control filter-op-param">${opOptions}</select>
            <input type="text" class="form-control filter-value-param" ${needsValue ? '' : 'style="display:none;"'} placeholder="value" value="${isParam ? displayValue : ''}">
        </div>
    `;

    const toggleParamRow = (showParam) => {
        row.querySelector('.filter-row-param').style.display = showParam ? '' : 'none';
        row.querySelector('.filter-row-main .filter-op').style.display = showParam ? 'none' : '';
        row.querySelector('.filter-row-main .filter-value').style.display = showParam ? 'none' : '';
    };

    row.querySelector('.filter-field').addEventListener('change', (e) => {
        toggleParamRow(e.target.value === 'param');
    });

    row.querySelector('.filter-row-main .filter-op').addEventListener('change', (e) => {
        const valInput = row.querySelector('.filter-row-main .filter-value');
        const isNullOp = e.target.value === 'is_null' || e.target.value === 'is_not_null';
        valInput.style.display = isNullOp ? 'none' : '';
        if (isNullOp) valInput.value = '';
    });

    row.querySelector('.filter-op-param').addEventListener('change', (e) => {
        const valInput = row.querySelector('.filter-value-param');
        const isNullOp = e.target.value === 'is_null' || e.target.value === 'is_not_null';
        valInput.style.display = isNullOp ? 'none' : '';
        if (isNullOp) valInput.value = '';
    });

    row.querySelector('.filter-remove').addEventListener('click', () => row.remove());

    document.getElementById('filterRows').appendChild(row);
}

function collectFilters() {
    const rows = document.querySelectorAll('#filterRows .filter-row');
    const rules = [];

    for (const row of rows) {
        let field = row.querySelector('.filter-field').value;
        if (!field) continue;

        let op, value;

        if (field === 'param') {
            // Read from param sub-row
            const paramKey = row.querySelector('.filter-param-key').value.trim();
            if (!paramKey) continue;
            field = 'param.' + paramKey;
            op = row.querySelector('.filter-op-param').value;
            value = row.querySelector('.filter-value-param').value.trim();
        } else {
            op = row.querySelector('.filter-row-main .filter-op').value;
            value = row.querySelector('.filter-row-main .filter-value').value.trim();
        }

        if (op === 'is_null' || op === 'is_not_null') {
            rules.push({ field, operator: op, value: null });
        } else if (value) {
            if (op === 'in' || op === 'not_in') {
                value = value.split(',').map(v => v.trim()).filter(Boolean);
            }
            rules.push({ field, operator: op, value });
        }
    }

    if (!rules.length) return {};

    return {
        condition: document.getElementById('filterCondition').value || 'AND',
        rules,
    };
}

function updateSaveButtonState() {
    // For clmnspopup.html
    const $saveColumnsBtn = $('#saveColumns');
    if ($saveColumnsBtn.length) {
        const hasCheckedColumns = $('#columnsList input:checked').length > 0;
        $saveColumnsBtn.prop('disabled', !hasCheckedColumns);
        $saveColumnsBtn.toggleClass('btn-disabled', !hasCheckedColumns);
    }

    // For statstableeditor.html
    const $saveTableBtn = $('#saveTableBtn');
    if ($saveTableBtn.length) {
        const hasCheckedMetrics = $('#metricsColumns input:checked').length > 0;
        const hasCheckedDimensions = $('#dimensionsColumns input:checked').length > 0;
        const isEnabled = hasCheckedMetrics && hasCheckedDimensions;
        $saveTableBtn.prop('disabled', !isEnabled);
        $saveTableBtn.toggleClass('btn-disabled', !isEnabled);
    }
}
