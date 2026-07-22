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
            <span class="conversion-status-kind">New status</span>
        </div>
        <label class="visually-hidden" for="conversion-status-aliases-${index}">Incoming aliases</label>
        <input id="conversion-status-aliases-${index}" type="text" class="form-control conversion-status-aliases" name="conversions.statuses[${index}][aliases]" placeholder="alias, another_alias">
        <div class="conversion-status-action"><button type="button" class="btn btn-sm remove-conversion-status" title="Remove new status" aria-label="Remove new status"><i class="bi bi-x-lg"></i></button></div>`;
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
function syncPaidRepeatControl() {
    const select = document.querySelector('[name="conversions.deduplication.paid_repeat_without_tid"]');
    if (select) select.disabled = tidDedupToggle?.checked === true;
}
tidDedupToggle?.addEventListener('change', syncPaidRepeatControl);
syncPaidRepeatControl();

document.getElementById('copy-conversion-snippet')?.addEventListener('click', async () => {
    const snippet = document.getElementById('conversion-site-snippet')?.textContent || "ytdsConversion('Reg');";
    await navigator.clipboard.writeText(snippet);
});

refreshConversionStatusSelects();
