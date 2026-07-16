// ── Scope radio: Global / Domain-Specific toggle ──
document.querySelectorAll('.white-scope-radio').forEach(function (radio) {
    radio.addEventListener('change', function () {
        var isDomainSpecific = this.value === 'true';
        document.getElementById('global-white-config').style.display = isDomainSpecific ? 'none' : 'block';
        // Ensure sections + nav items exist for all domains
        if (isDomainSpecific && window.syncDomainWhiteSections) window.syncDomainWhiteSections();
        if (window.refreshCampaignNavTree) window.refreshCampaignNavTree();
    });
});

// ── Domain-specific: method dropdown toggle ──
document.addEventListener('change', function (e) {
    var actionSelect = e.target.closest('.dws-action');
    if (!actionSelect) return;
    var section = actionSelect.closest('.dws-section');
    if (!section) return;
    var action = actionSelect.value;
    section.querySelector('.dws-folder-block').style.display = action === 'folder' ? 'block' : 'none';
    section.querySelector('.dws-redirect-block').style.display = action === 'redirect' ? 'block' : 'none';
    section.querySelector('.dws-curl-block').style.display = action === 'curl' ? 'block' : 'none';
    section.querySelector('.dws-error-block').style.display = action === 'error' ? 'block' : 'none';
});

// ── Domain-specific: delegated click handlers ──
document.addEventListener('click', function (e) {
    // Remove folder
    var btn = e.target.closest('.dws-remove-folder');
    if (btn) { var row = btn.closest('.dws-folder-item'); if (row) row.remove(); return; }
    // Remove redirect
    btn = e.target.closest('.dws-remove-redirect');
    if (btn) { var row = btn.closest('.dws-redirect-item'); if (row) row.remove(); return; }
    // Remove curl
    btn = e.target.closest('.dws-remove-curl');
    if (btn) { var row = btn.closest('.dws-curl-item'); if (row) row.remove(); return; }
    // Remove error
    btn = e.target.closest('.dws-remove-error');
    if (btn) { var row = btn.closest('.dws-error-item'); if (row) row.remove(); return; }
    // Edit folder
    btn = e.target.closest('.dws-edit-folder');
    if (btn) {
        var row = btn.closest('.dws-folder-item');
        var folder = row.querySelector('.dws-folder-name').value;
        if (window.openFileEditor) window.openFileEditor(folder, 'white');
        return;
    }
    // Add existing folder
    btn = e.target.closest('.dws-add-existing');
    if (btn) {
        var section = btn.closest('.dws-section');
        btn.disabled = true;
        fetch('listfolders.php?type=white').then(function (r) { return r.json(); }).then(function (data) {
            btn.disabled = false;
            if (data.error) { alert(data.result); return; }
            if (!data.folders.length) { alert('No safe page folders found. Upload a ZIP first.'); return; }
            if (window.openFolderPicker) {
                window.openFolderPicker(data.folders).then(function (choices) {
                    if (!choices || !choices.length) return;
                    choices.forEach(function (folder) {
                        section.querySelector('.dws-folder-items').insertAdjacentHTML('beforeend', buildDwsFolderRow(folder));
                    });
                });
            }
        }).catch(function (err) { btn.disabled = false; alert('Error: ' + err); });
        return;
    }
    // Upload ZIP
    btn = e.target.closest('.dws-upload-zip');
    if (btn) {
        var section = btn.closest('.dws-section');
        var fileInput = document.createElement('input');
        fileInput.type = 'file'; fileInput.accept = '.zip'; fileInput.style.display = 'none';
        document.body.appendChild(fileInput);
        fileInput.addEventListener('change', function () {
            if (!fileInput.files.length) { fileInput.remove(); return; }
            var folderName = prompt('Enter folder name for this safe page:', fileInput.files[0].name.replace(/\.zip$/i, ''));
            if (!folderName || !folderName.trim()) { fileInput.remove(); return; }
            var fd = new FormData();
            fd.append('zipfile', fileInput.files[0]);
            fd.append('folder', folderName.trim());
            fd.append('type', 'white');
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';
            btn.style.pointerEvents = 'none';
            fetch('zipupload.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) { alert('Upload error: ' + data.result); }
                    else { section.querySelector('.dws-folder-items').insertAdjacentHTML('beforeend', buildDwsFolderRow(data.folder)); }
                })
                .catch(function (err) { alert('Upload failed: ' + err); })
                .finally(function () {
                    btn.innerHTML = '<i class="bi bi-upload"></i> Upload ZIP';
                    btn.style.pointerEvents = '';
                    fileInput.remove();
                });
        });
        fileInput.click();
        return;
    }
    // Add redirect URL
    btn = e.target.closest('.dws-add-redirect');
    if (btn) {
        var url = prompt('Enter redirect URL:');
        if (!url || !url.trim()) return;
        var section = btn.closest('.dws-section');
        section.querySelector('.dws-redirect-items').insertAdjacentHTML('beforeend',
            '<div class="form-group-inner dws-redirect-item"><div class="row">' +
            '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Redirect address:</label></div>' +
            '<div class="col-lg-3"><input type="text" class="form-control dws-redirect-url" value="' + escapeHtml(url.trim()) + '" placeholder="https://example.com" /></div>' +
            '<div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn dws-remove-redirect" title="Delete"><i class="bi bi-trash"></i></a></div>' +
            '</div></div>');
        return;
    }
    // Add CURL URL
    btn = e.target.closest('.dws-add-curl');
    if (btn) {
        var url = prompt('Enter CURL URL:');
        if (!url || !url.trim()) return;
        var section = btn.closest('.dws-section');
        section.querySelector('.dws-curl-items').insertAdjacentHTML('beforeend',
            '<div class="form-group-inner dws-curl-item"><div class="row">' +
            '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Curl address:</label></div>' +
            '<div class="col-lg-3"><input type="text" class="form-control dws-curl-url" value="' + escapeHtml(url.trim()) + '" placeholder="https://example.com" /></div>' +
            '<div class="col-lg-2"><div class="btn-group campaign-icon-group">' +
            '<a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="rewrite" data-modes="rewrite,direct" title="Loading mode"><i class="bi bi-arrow-repeat"></i></a>' +
            '<a href="javascript:void(0)" class="btn btn-danger dws-remove-curl" title="Delete"><i class="bi bi-trash"></i></a>' +
            '</div></div></div></div>');
        return;
    }
    // Add error code
    btn = e.target.closest('.dws-add-error');
    if (btn) {
        var code = prompt('Enter HTTP code (e.g. 404):');
        if (!code || !code.trim()) return;
        var section = btn.closest('.dws-section');
        section.querySelector('.dws-error-items').insertAdjacentHTML('beforeend',
            '<div class="form-group-inner dws-error-item"><div class="row">' +
            '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">HTTP code: <i class="bi bi-info-circle admin-info-icon setting-help-icon" tabindex="0" role="img" aria-label="Examples: 404 Not Found or 200 OK." data-tooltip="Examples: 404 Not Found or 200 OK."></i></label></div>' +
            '<div class="col-lg-3"><input type="text" class="form-control dws-error-code" value="' + escapeHtml(code.trim()) + '" placeholder="404" /></div>' +
            '<div class="col-lg-1"><a href="javascript:void(0)" class="btn btn-danger campaign-icon-btn dws-remove-error" title="Delete"><i class="bi bi-trash"></i></a></div>' +
            '</div></div>');
        return;
    }
});

function buildDwsFolderRow(folderName, mode) {
    mode = mode || 'base';
    var info = window.LOAD_MODE_INFO || {};
    var icon = (info[mode] || {}).icon || 'bi-house-door';
    return '<div class="form-group-inner dws-folder-item"><div class="row">' +
        '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Safe page folder:</label></div>' +
        '<div class="col-lg-3"><input type="text" class="form-control folder-value-input dws-folder-name" value="' + escapeHtml(folderName) + '" readonly tabindex="-1" /></div>' +
        '<div class="col-lg-4"><div class="btn-group campaign-icon-group">' +
        '<a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="' + mode + '" data-modes="base,rewrite,direct" title="Loading mode"><i class="bi ' + icon + '"></i></a>' +
        '<a href="javascript:void(0)" class="btn btn-warning dws-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a>' +
        '<a href="javascript:void(0)" class="btn btn-danger dws-remove-folder" title="Delete"><i class="bi bi-trash"></i></a>' +
        '</div></div></div></div>';
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ── Collect domain-specific white data for save ──
window.collectDomainSpecificData = function () {
    var result = [];
    document.querySelectorAll('section.dws-section').forEach(function (section) {
        var domain = section.dataset.domain;
        var actionSelect = section.querySelector('.dws-action');
        var action = actionSelect ? actionSelect.value : 'folder';

        var folders = [];
        var loadmode = {};
        section.querySelectorAll('.dws-folder-name').forEach(function (inp) {
            var name = inp.value.trim();
            if (name) {
                folders.push(name);
                var mb = inp.closest('.dws-folder-item').querySelector('.load-mode-btn');
                if (mb) loadmode[name] = mb.dataset.mode || 'base';
            }
        });

        var redirectUrls = [];
        section.querySelectorAll('.dws-redirect-url').forEach(function (inp) {
            var u = inp.value.trim(); if (u) redirectUrls.push(u);
        });
        var redirectType = 302;
        var rtSel = section.querySelector('.dws-redirect-type');
        if (rtSel) redirectType = parseInt(rtSel.value) || 302;

        var curlUrls = [];
        section.querySelectorAll('.dws-curl-url').forEach(function (inp) {
            var u = inp.value.trim();
            if (u) {
                curlUrls.push(u);
                var mb = inp.closest('.dws-curl-item').querySelector('.load-mode-btn');
                if (mb) loadmode[u] = mb.dataset.mode || 'rewrite';
            }
        });

        var errorCodes = [];
        section.querySelectorAll('.dws-error-code').forEach(function (inp) {
            var c = inp.value.trim(); if (c) errorCodes.push(parseInt(c) || 0);
        });

        result.push({
            domain: domain,
            action: action,
            folders: folders,
            redirect: { urls: redirectUrls, type: redirectType },
            curls: curlUrls,
            errorcodes: errorCodes,
            loadmode: loadmode
        });
    });
    return result;
};
