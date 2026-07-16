// ── White folder management ──
var whiteActionSelect = document.querySelector('.white-action-select');

function updateWhiteActionBlocks(action) {
    var blocks = {
        folder: document.getElementById('b_2'),
        redirect: document.getElementById('b_3'),
        curl: document.getElementById('b_4'),
        error: document.getElementById('b_5')
    };
    Object.keys(blocks).forEach(function (key) {
        if (blocks[key]) blocks[key].style.display = key === action ? 'block' : 'none';
    });
}

if (whiteActionSelect) {
    whiteActionSelect.addEventListener('change', function () {
        updateWhiteActionBlocks(this.value);
    });
    updateWhiteActionBlocks(whiteActionSelect.value);
}

function buildWhiteFolderRow(folderName, mode) {
    mode = mode || 'base';
    var info = window.LOAD_MODE_INFO || {};
    var icon = (info[mode] || {}).icon || 'bi-house-door';
    return '<div class="form-group-inner white-folder-item"><div class="row">' +
        '<div class="col-lg-3"><label class="login2 pull-left pull-left-pro">Safe page folder:</label></div>' +
        '<div class="col-lg-3"><input type="text" class="form-control folder-value-input white-folder-name" value="' + folderName + '" placeholder="safe1" readonly tabindex="-1" /></div>' +
        '<div class="col-lg-4"><div class="btn-group campaign-icon-group">' +
        '<a href="javascript:void(0)" class="btn btn-outline-secondary load-mode-btn" data-mode="' + mode + '" data-modes="base,rewrite,direct" title="Loading mode"><i class="bi ' + icon + '"></i></a>' +
        '<a href="javascript:void(0)" class="btn btn-warning white-edit-folder" title="Edit files"><i class="bi bi-pencil-square"></i></a>' +
        '<a href="javascript:void(0)" class="btn btn-danger remove-white-folder-item" title="Delete"><i class="bi bi-trash"></i></a>' +
        '</div></div></div></div>';
}

document.addEventListener('click', function (e) {
    // Delete white folder row
    var delBtn = e.target.closest('.remove-white-folder-item');
    if (delBtn) {
        var row = delBtn.closest('.white-folder-item');
        if (row) row.remove();
        return;
    }
    // Edit white folder
    var editBtn = e.target.closest('.white-edit-folder');
    if (editBtn) {
        var row = editBtn.closest('.white-folder-item');
        var folder = row.querySelector('.white-folder-name').value;
        if (window.openFileEditor) window.openFileEditor(folder, 'white');
        return;
    }
});

// Add Existing white folder
document.querySelector('.white-add-existing')?.addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    fetch('listfolders.php?type=white').then(function (r) { return r.json(); }).then(function (data) {
        btn.disabled = false;
        if (data.error) { alert(data.result); return; }
        if (!data.folders.length) { alert('No safe page folders found. Upload a ZIP first.'); return; }
        if (window.openFolderPicker) {
            window.openFolderPicker(data.folders).then(function (choices) {
                if (!choices || !choices.length) return;
                choices.forEach(function (folder) {
                    document.getElementById('white_folder_container').insertAdjacentHTML('beforeend', buildWhiteFolderRow(folder));
                });
            });
        }
    }).catch(function (err) { btn.disabled = false; alert('Error: ' + err); });
});

// Upload ZIP for white folder
document.querySelector('.white-upload-zip')?.addEventListener('click', function () {
    var btn = this;
    var fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = '.zip';
    fileInput.style.display = 'none';
    document.body.appendChild(fileInput);

    fileInput.addEventListener('change', function () {
        if (!fileInput.files.length) { fileInput.remove(); return; }
        var file = fileInput.files[0];
        var folderName = prompt('Enter folder name for the new safe page:');
        if (!folderName || !folderName.trim()) { fileInput.remove(); return; }
        folderName = folderName.trim();
        if (!/^[a-zA-Z0-9_\-\.]+$/.test(folderName)) {
            alert('Invalid folder name. Use only letters, numbers, hyphens, underscores, dots.');
            fileInput.remove();
            return;
        }
        var fd = new FormData();
        fd.append('zipfile', file);
        fd.append('folder', folderName);
        fd.append('type', 'white');

        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';
        btn.style.pointerEvents = 'none';

        fetch('zipupload.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) {
                    alert('Upload error: ' + data.result);
                } else {
                    document.getElementById('white_folder_container').insertAdjacentHTML('beforeend', buildWhiteFolderRow(data.folder));
                }
            })
            .catch(function (err) { alert('Upload failed: ' + err); })
            .finally(function () {
                btn.innerHTML = '<i class="bi bi-upload"></i> Upload ZIP';
                btn.style.pointerEvents = '';
                fileInput.remove();
            });
    });
    fileInput.click();
});

// Collect white folders + loadmode + curl loadmode before save
window.collectWhiteData = function () {
    var folders = [];
    var loadmode = {};
    document.querySelectorAll('#white_folder_container .white-folder-name').forEach(function (inp) {
        var name = inp.value.trim();
        if (name) {
            folders.push(name);
            var modeBtn = inp.closest('.white-folder-item').querySelector('.load-mode-btn');
            if (modeBtn) loadmode[name] = modeBtn.dataset.mode || 'base';
        }
    });
    // Also collect curl loadmode
    document.querySelectorAll('#curl_container .white-curl-url').forEach(function (inp) {
        var url = inp.value.trim();
        if (url) {
            var modeBtn = inp.closest('.curl-item').querySelector('.load-mode-btn');
            if (modeBtn) loadmode[url] = modeBtn.dataset.mode || 'rewrite';
        }
    });
    return { folders: folders, loadmode: loadmode };
};
