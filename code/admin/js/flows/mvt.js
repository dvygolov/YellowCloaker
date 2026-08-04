function valueCode(index) {
    var code = '';
    do {
        code = String.fromCharCode(65 + (index % 26)) + code;
        index = Math.floor(index / 26) - 1;
    } while (index >= 0);
    return code;
}

function copyText(text, button) {
    function markCopied() {
        var originalTitle = button.title;
        button.title = 'Copied';
        window.setTimeout(function () {
            button.title = originalTitle;
        }, 1200);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(markCopied).catch(function () {
            fallbackCopy();
        });
        return;
    }
    fallbackCopy();

    function fallbackCopy() {
        var input = document.createElement('textarea');
        input.value = text;
        input.setAttribute('readonly', '');
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        try {
            document.execCommand('copy');
            markCopied();
        } finally {
            input.remove();
        }
    }
}

function createValueRow(value, valueIndex) {
    var row = document.createElement('div');
    row.className = 'flow-mvt-value' + (value.active === false ? ' is-archived' : '');
    row.dataset.active = value.active === false ? 'false' : 'true';
    row.dataset.saved = value.saved === false ? 'false' : 'true';

    var badge = document.createElement('span');
    badge.className = 'flow-mvt-value-code';
    badge.textContent = valueCode(valueIndex);

    var textarea = document.createElement('textarea');
    textarea.className = 'form-control flow-mvt-value-input';
    textarea.rows = 2;
    textarea.value = String(value.value || '');
    textarea.placeholder = 'Text or trusted HTML';
    textarea.readOnly = value.saved !== false;

    var archive = document.createElement('button');
    archive.type = 'button';
    archive.className = 'btn btn-danger campaign-icon-btn flow-mvt-archive-value';
    archive.title = 'Archive Value';
    archive.innerHTML = '<i class="bi bi-trash"></i>';
    archive.disabled = value.active === false;

    row.append(badge, textarea, archive);
    return row;
}

function createTestCard(test, testIndex) {
    var card = document.createElement('div');
    card.className = 'flow-mvt-test' + (test.active === false ? ' is-archived' : '');
    card.dataset.active = test.active === false ? 'false' : 'true';
    card.dataset.saved = test.saved === false ? 'false' : 'true';

    var header = document.createElement('div');
    header.className = 'flow-mvt-test-header';

    var title = document.createElement('strong');
    title.className = 'flow-mvt-test-title';
    title.textContent = 'Test ' + (testIndex + 1);

    var copy = document.createElement('button');
    copy.type = 'button';
    copy.className = 'btn btn-outline-light btn-sm flow-mvt-copy';
    copy.title = 'Copy placeholder';
    copy.setAttribute('aria-label', 'Copy placeholder for Test ' + (testIndex + 1));
    copy.dataset.placeholder = '#TEST' + (testIndex + 1) + '#';
    copy.innerHTML = '<i class="bi bi-copy"></i>';

    var archive = document.createElement('button');
    archive.type = 'button';
    archive.className = 'btn btn-danger campaign-icon-btn flow-mvt-archive-test';
    archive.title = 'Archive TEST';
    archive.innerHTML = '<i class="bi bi-trash"></i>';
    archive.disabled = test.active === false;

    header.append(title, copy, archive);

    var values = document.createElement('div');
    values.className = 'flow-mvt-values';
    (test.values || []).forEach(function (value, valueIndex) {
        values.appendChild(createValueRow(value, valueIndex));
    });

    var addValue = document.createElement('button');
    addValue.type = 'button';
    addValue.className = 'btn btn-outline-info btn-sm flow-mvt-add-value';
    addValue.textContent = '+ Value';
    addValue.disabled = test.active === false;

    card.append(header, values, addValue);
    return card;
}

export function renderMvtPanel(panel, mvt) {
    mvt = mvt && typeof mvt === 'object' ? mvt : {};
    panel.innerHTML = '';

    var header = document.createElement('div');
    header.className = 'flow-mvt-panel-header';
    var title = document.createElement('span');
    title.innerHTML = '<strong>MVT</strong><small>Equal distribution</small>';

    var toggle = document.createElement('label');
    toggle.className = 'campaign-switch flow-mvt-switch';
    var checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.className = 'campaign-switch-input flow-mvt-enabled';
    checkbox.checked = mvt.enabled === true;
    checkbox.setAttribute('aria-label', 'Enable MVT');
    var track = document.createElement('span');
    track.className = 'campaign-switch-track';
    track.innerHTML = '<span class="campaign-switch-option campaign-switch-option-off">Off</span>'
        + '<span class="campaign-switch-option campaign-switch-option-on">On</span>'
        + '<span class="campaign-switch-thumb"></span>';
    toggle.append(checkbox, track);
    header.append(title, toggle);

    var body = document.createElement('div');
    body.className = 'flow-mvt-body';
    body.hidden = !checkbox.checked;
    var tests = document.createElement('div');
    tests.className = 'flow-mvt-tests';
    (mvt.tests || []).forEach(function (test, testIndex) {
        var normalized = Object.assign({ saved: true }, test);
        normalized.values = (test.values || []).map(function (value) {
            return Object.assign({ saved: true }, value);
        });
        tests.appendChild(createTestCard(normalized, testIndex));
    });
    var addTest = document.createElement('button');
    addTest.type = 'button';
    addTest.className = 'btn btn-outline-info btn-sm flow-mvt-add-test';
    addTest.textContent = '+ TEST';
    body.append(tests, addTest);
    panel.append(header, body);
}

export function collectMvt(folderItem) {
    var enabled = folderItem.querySelector('.flow-mvt-enabled');
    var tests = [];
    folderItem.querySelectorAll('.flow-mvt-test').forEach(function (testCard) {
        var values = [];
        testCard.querySelectorAll('.flow-mvt-value').forEach(function (valueRow) {
            values.push({
                active: valueRow.dataset.active !== 'false',
                value: valueRow.querySelector('.flow-mvt-value-input').value
            });
        });
        tests.push({
            active: testCard.dataset.active !== 'false',
            values: values
        });
    });
    return {
        enabled: Boolean(enabled && enabled.checked),
        tests: tests
    };
}

export function initializeMvtPanels() {
    document.querySelectorAll('.flow-mvt-panel').forEach(function (panel) {
        var item = panel.closest('.flow-path-item');
        var data = {};
        try {
            data = JSON.parse(item.dataset.mvt || '{}');
        } catch (e) {}
        renderMvtPanel(panel, data);
    });
}

export function handleMvtClick(e) {
    var panel = e.target.closest('.flow-mvt-panel');
    if (!panel) return false;

    var copy = e.target.closest('.flow-mvt-copy');
    if (copy) {
        copyText(copy.dataset.placeholder, copy);
        return true;
    }

    var addTest = e.target.closest('.flow-mvt-add-test');
    if (addTest) {
        var tests = panel.querySelector('.flow-mvt-tests');
        tests.appendChild(createTestCard({
            active: true,
            saved: false,
            values: [{ active: true, value: '', saved: false }]
        }, tests.children.length));
        return true;
    }

    var addValue = e.target.closest('.flow-mvt-add-value');
    if (addValue) {
        var values = addValue.closest('.flow-mvt-test').querySelector('.flow-mvt-values');
        values.appendChild(createValueRow({
            active: true,
            value: '',
            saved: false
        }, values.children.length));
        return true;
    }

    var archiveValue = e.target.closest('.flow-mvt-archive-value');
    if (archiveValue) {
        var valueRow = archiveValue.closest('.flow-mvt-value');
        valueRow.dataset.active = 'false';
        valueRow.classList.add('is-archived');
        archiveValue.disabled = true;
        return true;
    }

    var archiveTest = e.target.closest('.flow-mvt-archive-test');
    if (archiveTest) {
        var test = archiveTest.closest('.flow-mvt-test');
        test.dataset.active = 'false';
        test.classList.add('is-archived');
        archiveTest.disabled = true;
        var add = test.querySelector('.flow-mvt-add-value');
        if (add) add.disabled = true;
        return true;
    }
    return false;
}

export function handleMvtChange(e) {
    if (!e.target.classList.contains('flow-mvt-enabled')) return false;
    var body = e.target.closest('.flow-mvt-panel').querySelector('.flow-mvt-body');
    body.hidden = !e.target.checked;
    return true;
}
