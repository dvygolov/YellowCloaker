function conversionStatusRows() {
    return Array.from(document.querySelectorAll('#conversion-status-rows [data-status-row]'));
}

function conversionStatusNames() {
    return conversionStatusRows()
        .map((row) => row.querySelector('.conversion-status-name-input')?.value.trim() || '')
        .filter(Boolean);
}

function reindexConversionStatuses() {
    conversionStatusRows().forEach((row, index) => {
        const name = row.querySelector('.conversion-status-name-input');
        const aliases = row.querySelector('input[name*="[aliases]"]');
        if (name) name.name = `conversions.statuses[${index}][name]`;
        if (aliases) aliases.name = `conversions.statuses[${index}][aliases]`;
    });
}

function refreshConversionStatusSelects() {
    const names = conversionStatusNames();
    document.querySelectorAll('.conversion-status-select').forEach((select) => {
        const current = select.value;
        select.innerHTML = names.map((name) => `<option value="${escapeConversionHtml(name)}">${escapeConversionHtml(name)}</option>`).join('');
        select.value = names.includes(current) ? current : (names[0] || '');
    });
    window.campaignConversionStatuses = names;
}

function escapeConversionHtml(value) {
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function addConversionStatusRow() {
    const index = conversionStatusRows().length;
    const row = document.createElement('div');
    row.className = 'conversion-status-row conversion-status-row-new';
    row.dataset.statusRow = '';
    row.dataset.builtIn = '0';
    row.innerHTML = `
        <div class="conversion-status-identity">
            <div class="conversion-status-name"><input type="text" class="form-control conversion-status-name-input" name="conversions.statuses[${index}][name]" placeholder="Status name" required maxlength="64"></div>
            <span class="conversion-status-kind">Custom</span>
        </div>
        <label class="visually-hidden" for="conversion-status-aliases-${index}">Incoming aliases</label>
        <input id="conversion-status-aliases-${index}" type="text" class="form-control conversion-status-aliases" name="conversions.statuses[${index}][aliases]" placeholder="alias, another_alias">
        <div class="conversion-status-action"><button type="button" class="btn btn-sm remove-conversion-status" title="Delete custom status" aria-label="Delete custom status"><i class="bi bi-trash" aria-hidden="true"></i></button></div>`;
    document.getElementById('conversion-status-rows')?.appendChild(row);
    row.querySelector('.conversion-status-name-input')?.focus();
}

async function removeConversionStatus(row) {
    const status = row.querySelector('.conversion-status-name-input')?.value.trim() || '';
    const campId = new URLSearchParams(window.location.search).get('campId');
    let usages = [];
    if (status && campId) {
        try {
            const response = await fetch(`conversionstatususage.php?campId=${encodeURIComponent(campId)}&status=${encodeURIComponent(status)}`);
            const payload = await response.json();
            usages = Array.isArray(payload.usages) ? payload.usages : [];
        } catch (error) {
            usages = ['Usage check failed; historical rows will still be preserved.'];
        }
    }
    const details = usages.length ? `\n\nUsed by:\n- ${usages.join('\n- ')}` : '';
    if (!window.confirm(`Delete custom status "${status || 'Unnamed'}"? Existing conversion history and saved references will not be removed.${details}`)) {
        return;
    }
    row.remove();
    reindexConversionStatuses();
    refreshConversionStatusSelects();
    if (typeof refreshFlowFilterBuilders === 'function') refreshFlowFilterBuilders();
}

document.getElementById('add-conversion-status')?.addEventListener('click', addConversionStatusRow);
document.getElementById('conversion-status-rows')?.addEventListener('click', (event) => {
    const button = event.target.closest('.remove-conversion-status');
    if (button) removeConversionStatus(button.closest('[data-status-row]'));
});
document.getElementById('conversion-status-rows')?.addEventListener('input', (event) => {
    if (event.target.matches('.conversion-status-name-input')) refreshConversionStatusSelects();
});

const tidDedupToggle = document.getElementById('conversion-tid-dedup-toggle');
const tidParametersInput = document.getElementById('conversion-tid-parameters');
const postbackUrlExample = document.getElementById('postback-url-example');
const paidRepeatSelect = document.getElementById('conversion-repeat-mode');
const paidRepeatValue = document.getElementById('conversion-repeat-mode-value');

function getTransactionIdParameterNames() {
    if (!tidParametersInput) return [];
    const seen = new Set();
    return tidParametersInput.value
        .split(',')
        .map((name) => name.trim())
        .filter((name) => {
            const key = name.toLowerCase();
            if (name === '' || seen.has(key)) return false;
            seen.add(key);
            return true;
        });
}

function syncPostbackUrlExample() {
    if (!postbackUrlExample) return;
    const firstParameter = getTransactionIdParameterNames()[0] || 'tid';
    const validParameter = /^[A-Za-z_][A-Za-z0-9_-]{0,63}$/.test(firstParameter)
        ? firstParameter
        : 'tid';
    postbackUrlExample.value = `${postbackUrlExample.dataset.urlPrefix || ''}${validParameter}={transaction_id}`;
}

function normalizeTransactionIdParametersInput() {
    if (!tidParametersInput) return;
    tidParametersInput.value = getTransactionIdParameterNames().join(', ');
    syncPostbackUrlExample();
}

window.normalizeTransactionIdParametersInput = normalizeTransactionIdParametersInput;
tidParametersInput?.addEventListener('blur', normalizeTransactionIdParametersInput);
tidParametersInput?.addEventListener('input', syncPostbackUrlExample);

function syncPaidRepeatControl() {
    if (paidRepeatSelect) paidRepeatSelect.disabled = tidDedupToggle?.checked === true;
}
tidDedupToggle?.addEventListener('change', syncPaidRepeatControl);
paidRepeatSelect?.addEventListener('change', () => {
    if (paidRepeatValue) paidRepeatValue.value = paidRepeatSelect.value;
});
syncPaidRepeatControl();
syncPostbackUrlExample();

document.getElementById('copy-conversion-snippet')?.addEventListener('click', async () => {
    const snippet = document.getElementById('conversion-site-snippet')?.textContent || "ytdsConversion('Reg');";
    await navigator.clipboard.writeText(snippet);
});

refreshConversionStatusSelects();
